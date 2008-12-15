<?php
/*
	NP_SimpleURL : Improve url handling with a simple format.
	
	License
	-------
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	Usage
	-----
	Install this plugin and turn "FancyURLs mode" on.
	
	In index.php :
	Set absolute url (http://...) to $CONF['Self'].
	Set blog's shortname to $CONF['NP_SimpleURL']['selectBlog'] before including config.php. (optional) 
	Set selectBlog('shortname') before selector(). (optional)
	
	In fancyurls.config.php or index.php :
	You can use some features (flags).
	
	$CONF['NP_SimpleURL']['UseAlias'] = 1;             //convert id number to alias (shortname). don't turn "DropKeys" on if "UseAlias" is off.
	$CONF['NP_SimpleURL']['DropKeys'] = 1;             //drop keys for blog, category and item
	$CONF['NP_SimpleURL']['ShowBlogAlways'] = 0;       //show blog always
	$CONF['NP_SimpleURL']['ShowCategoryAlways'] = 1;   //show category always (in permalink)
	
	誤動作にハマらないために注意すべき事柄：
		1.実在するディレクトリ名と同じ名前を、URL修飾キーやエイリアスに使わない。
		2.スキンタイプ、URL修飾キーなどで予約済みの名前をエイリアスに使わない。
		3.ブログ間で同名のカテゴリーエイリアスを使う場合は index.phpに $CONF['NP_SimpleURL']['selectBlog']をセットする。
	※2の制約から解放されたい場合は DropKeys を無効にすること。
	※3の制約は ShowBlogAlways を有効にしてURLにブログ情報を必ず含めることでも解消できる。
	
	History
	-------
	2008-12-15 v0.3 : Add old url redirection. Add item var.
	                : Change item page's url format. (yu)
	2008-07-12 v0.21: Improve function for category alias. (yu)
	2008-06-08 v0.2 : Add some new features. Support alias names. (yu)
	2008-05-02 v0.1 : First release. (yu http://nucleus.datoka.jp/)
*/


/* ----------------- additional global functions ----------------- */

/* addLinkParams()をグローバル関数として切り出せるように下記も一応グローバルで宣言しておく */

if(!function_exists('getBlogURLFromID')) {
	function getBlogURLFromID($blogid)
	{
		static $buff;
		
		$blogid = (int)$blogid;
		if (!$buff[$blogid]) {
			$res = sql_query('SELECT bnumber, burl FROM ' . sql_table('blog'));
			if (mysql_num_rows($res)) {
				while ($data = mysql_fetch_assoc($res)) {
					$buff[ $data['bnumber'] ] = $data['burl'];
				}
			}
		}
		return $buff[$blogid];
	}
}

if(!function_exists('getBlogAliasFromID')) {
	function getBlogAliasFromID($id)
	{
		static $buff;
		
		$id = (int)$id;
		if (!$buff[$id]) {
			$buff[$id] = quickQuery('SELECT bshortname as result FROM ' . sql_table('blog') . ' WHERE bnumber=' . $id);
		}
		return ($buff[$id]) ? $buff[$id] : $id;
	}
}

if(!function_exists('getMemberAliasFromID')) {
	function getMemberAliasFromID($id)
	{
		static $buff;
		
		$id = (int)$id;
		if (!$buff[$id]) {
			$buff[$id] = quickQuery('SELECT mname as result FROM ' . sql_table('member') . ' WHERE mnumber=' . $id);
			$buff[$id] = str_replace(' ', '_', $buff[$id]);
		}
		return ($buff[$id]) ? $buff[$id] : $id;
	}
}

if(!function_exists('getCatAliasFromID')) {
	function getCatAliasFromID($id, $blogid)
	{
		global $manager;
		static $buff;
		
		$id = (int)$id;
		if (! isset($buff)) {
			$buff = getCatAliasesFromDB();
		}
		return ($buff[$blogid][$id]) ? $buff[$blogid][$id] : $id;
	}
}

if(!function_exists('getCatAliasesFromDB')) {
	function getCatAliasesFromDB()
	{
		$arr = array();
		$res = sql_query('SELECT catid, blogid, cshortname FROM ' . sql_table('plug_simpleurl') .' ORDER BY catid');
		while($data = mysql_fetch_assoc($res)) {
			$arr[ $data['blogid'] ][$data['catid']] = $data['cshortname'];
		}
		return $arr;
	}
}

if(!function_exists('getItemAliasFromID')) {
	function getItemAliasFromID($id)
	{
		global $manager;
		
		//return (int)$id; //use itemid only
		
		if ($manager->pluginInstalled('NP_SimpleURL')) { //use title
			$obj =& $manager->getPlugin('NP_SimpleURL');
			$t = strip_tags($obj->_getDataFromItemID($id, $type='ititle'));
			if (preg_match('/[^\x00-\x7F]/', $t)) { 
				//半角文字以外が入っている場合はアイテムIDのみ
				$t = (int)$id;
			}
			else {
				//半角文字の場合はURLで安全に使用可能な文字以外を'_'で置換
				$t = mb_substr(preg_replace('/[^a-zA-Z0-9_.!~*()-]/', '_', $t), 0, 40) .'-'. (int)$id;
			}
			return $t;
		}
	}
}


class NP_SimpleURL extends NucleusPlugin
{
	function getName() { return 'Simple URL'; }
	function getAuthor() { return 'yu'; }
	function getURL() { return 'http://nucleus.datoka.jp/'; }
	function getVersion() { return '0.3'; }
	function getMinNucleusVersion() { return 322; }
	function supportsFeature($what) { return (int)($what == 'SqlTablePrefix'); }
	function hasAdminArea() { return 1; }
	function getDescription()
	{
		return 'Improve url handling with a simple format.';
	}
	function getEventList()
	{
		return array(
			'PostAuthentication',
			'ParseURL', 
			'GenerateURL', 
			'PostAddCategory', 
			'PostDeleteCategory', 
			'PostMoveCategory', 
			'QuickMenu'
			);
	}
	
	var $flg_fancy;
	var $arr_item;
	
	function install()
	{
		$this->createOption('flg_qmenu', 'Show on quick menu', 'yesno', 'yes');
		$this->createOption('flg_erase', 'Erase data on uninstallation', 'yesno', 'no');
		
		//テーブルが存在するかチェックする
		$chktable = sql_query("SHOW TABLES LIKE '". sql_table('plug_simpleurl') ."'");
		if (mysql_num_rows($chktable)) return;
		
		//存在しなければテーブル生成＆データ代入
		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plug_simpleurl') ." (
			catid       INT UNSIGNED NOT NULL DEFAULT 0 PRIMARY KEY,
			blogid      INT UNSIGNED NOT NULL DEFAULT 0,
			cshortname  VARCHAR(50) NOT NULL DEFAULT '')");
		$res = sql_query("SELECT catid, cblog FROM ". sql_table('category'));
		while ($data = mysql_fetch_assoc($res)) {
			sql_query("INSERT ". sql_table('plug_simpleurl') 
				." SET catid=". $data['catid'] .", blogid=". $data['cblog'] .", cshortname='c". $data['catid'] ."'");
		}
	}
	
	function uninstall()
	{
		if ($this->getOption('flg_erase') == 'yes') {
			sql_query("DROP TABLE ". sql_table('plug_simpleurl'));
		}
	}
	
	function init()
	{
		global $CONF;
		
		$this->flg_fancy = ($CONF['URLMode'] == 'pathinfo');
		
		if ($this->flg_fancy) {
			//URLパラメータの並び順（あらかじめキーを作っておく）
			//外部プラグインは各々init()で$CONF['HogeKey']登録と、NP_SimpleURL::setURLOrder('hoge',N)する。
			$CONF['NP_SimpleURL']['URLOrder'] = array(
				$CONF['BlogKey']        => 0,  //1番目
				$CONF['CategoryKey']    => 1,  //2番目
				$CONF['SpecialskinKey'] => 2,  //3番目
				$CONF['ArchiveKey']     => 10, //以下同順
				$CONF['ArchivesKey']    => 10, 
				$CONF['ItemKey']        => 10, 
				$CONF['MemberKey']      => 10, 
				);
		}
	}
	
	/* ----------------- event methods ----------------- */
	
	function event_PostAuthentication(&$data)
	{
		//wake up! (nothing to do)
		//外部からNP_SimpleURLのメソッドを静的呼び出しするときの便宜を考えて。
		//NP_SimpleURL::addLinkParams()など
	}
	
	function event_QuickMenu(&$data)
	{
		global $member;
		
		if ($this->getOption('flg_qmenu') != 'yes') return;
		if (!$this->_canEdit()) return;
		
		array_push(
			$data['options'], 
			array(
				'title'   => 'Simple URL',
				'url'     => $this->getAdminURL(),
				'tooltip' => 'Setting of category alias'
			)
		);
	}
	
	function event_PostMoveCategory(&$data)
	{
		sql_query("UPDATE ". sql_table('plug_simpleurl') 
			." SET blogid=". $data['destblog']->getID() ." WHERE catid=". $data['catid']);
	}
	
	function event_PostAddCategory(&$data)
	{
		sql_query("INSERT ". sql_table('plug_simpleurl') 
			." SET catid=". $data['catid'] .", blogid=". $data['blog']->getID() .", cshortname='c". $data['catid'] ."'");
	}
	
	function event_PostDeleteCategory($data)
	{
		sql_query("DELETE ". sql_table('plug_simpleurl') ." WHERE catid=". $data['catid']);
	}
	
	//URLパース
	function event_ParseURL(&$pdata)
	{
		
		
		// nothing to do if another plugin already parsed the URL
		if ($pdata['complete']) return;
		
		global $CONF, $blogid, $catid, $itemid, $memberid, $archive, $archivelist;
		
		// in admin area
		if ($CONF['UsingAdminArea']) return;
		
		$complete =& $pdata['complete'];
		$virtualpath =& $pdata['info'];
		$virtualpath = preg_replace('/\.html$/', '', $virtualpath);
		
		//selectBlog()の先取り。URLパースのタイミング上、selectBlog()では遅いため。
		if (!$blogid and $CONF['SimpleURL']['selectBlog']) {
			selectBlog( $CONF['SimpleURL']['selectBlog'] );
		}
		
		//リダイレクト処理（NormalURLからFancyURLへ）
		if ($virtualpath == '') {
			$extra = '';
			$newurl = '';
			if ($itemid) {
				if ($blogid) $extra['blogid'] = $blogid;
				if ($catid)  $extra['catid'] = $catid;
				$newurl = createItemLink($itemid, $extra);
			}
			else if ($memberid) {
				if ($blogid) $extra['blogid'] = $blogid;
				$newurl = createMemberLink($memberid, $extra);
			}
			else if ($archivelist) {
				if ($catid)  $extra['catid'] = $catid;
				$newurl = createArchiveListLink($archivelist, $extra);
			}
			else if ($archive) {
				if ($catid)  $extra['catid'] = $catid;
				$newurl = createArchiveLink($blogid, $archive, $extra);
			}
			else if ($catid) {
				if ($blogid) $extra['blogid'] = $blogid;
				$newurl = createCategoryLink($catid, $extra);
			}
			/*else if ($blogid) {
				$newurl = createBlogidLink($blogid, $extra);
				$this->_redirect301($newurl);
			}*/
			if ($newurl != '') {
				if ($this->_isValidURL($newurl)) $this->_http301($newurl);
				else $this->_http404();
			}
		}
		
		//パース処理開始
		$data = explode("/", $virtualpath );
		for ($i = 0; $i < sizeof($data); $i++) {
			switch ($data[$i]) {
			case $CONF['ItemKey']: // item/1 (itemid) or item/name (urlencoded ititle)
				$i++;
				if ($i < sizeof($data) ) {
					if (is_numeric($data[$i])) $itemid = intval($data[$i]);
					else $itemid = $this->_getItemIDFromAlias($data[$i]);
				}
				break;

			case $CONF['ArchivesKey']: // archives
				if ($blogid) {
					$archivelist = $blogid;
				}
				else {
					$archivelist = $CONF['DefaultBlog'];
				}
				break;

			case $CONF['ArchiveKey']: // archive/yyyy-mm
				$i++;
				if ($i < sizeof($data) ) {
					$archive = $data[$i];
				}
				break;

			case $CONF['BlogKey']: // blog/1 or blog/bshortname
			case 'blogid':
				$i++;
				if ($i < sizeof($data) ) {
					if (is_numeric($data[$i])) $blogid = intval($data[$i]);
					else $blogid = $this->_getBlogIDFromAlias($data[$i]);
				}
				break;

			case $CONF['CategoryKey']: // category/1 (catid) or category/aliasname
			case 'catid':
				$i++;
				if ($i < sizeof($data) ) {
					if (is_numeric($data[$i])) $catid = intval($data[$i]);
					else $catid = $this->_getCatIDFromAlias($data[$i]);
				}
				break;

			case $CONF['MemberKey']: // member/1 (memberid) or member/mname
				$i++;
				if ($i < sizeof($data) ) {
					if (is_numeric($data[$i])) $memberid = intval($data[$i]);
					else $memberid = $this->_getMemberIDFromAlias($data[$i]);
				}
				break;

			case $CONF['SpecialskinKey']: //extra/specialname
				$i++;
				if ($i < sizeof($data) ) {
					$_REQUEST['special'] = $data[$i];
				}
				break;

			default:
				//無効なキーは捨てる
				if ($data[$i] == '') break; //skip
				
				$flg_num = (is_numeric($data[$i]));
				
				//拡張キーと合致するかスキャンする
				$exkey = ucfirst($data[$i]) . 'Key';
				if (isset($CONF[$exkey])) {
					$exstore =& $_REQUEST; //代入先はリクエスト変数
					
					if (!$exstore[ $CONF[$exkey] ]) { //対象が未定義なら代入を許可する
						$i++;
						$exstore[ $CONF[$exkey] ] = $data[$i]; //そのまま代入してるので、利用時は値のチェックを忘れずに。
					}
					break;
				}
				
				//debuglog('check non-key val: '.$data[$i]);
				//「キー無し」の可能性をチェック。ここでは数値のみの値を避ける
				$tempid = 0;
				if ($CONF['NP_SimpleURL']['DropKeys'] and !$flg_num and preg_match('/^[0-9a-z_-]+$/', $data[$i])) {
					if (!$blog_done and $i == 0 and $CONF['NP_SimpleURL']['ShowBlogAlways']) { //ブログのキー無しは ShowBlogAlways が有効なときのみ
						$tempid = $this->_getBlogIDFromAlias($data[$i]);
						if ($tempid) {
							//debuglog('hit: blog'.$tempid. ' ' .$data[$i]);
							$blogid = $tempid;
							$blog_done = true;
							break;
						}
						//debuglog('through: blog '.$i);
					}
					if (!$cat_done and $i < 2) { //カテゴリーのキー無しの可能性をチェック
						$tempid = $this->_getCatIDFromAlias($data[$i]);
						if ($tempid) {
							//debuglog('hit: cat'.$tempid. ' ' .$data[$i]);
							$catid = $tempid;
							$cat_done = true;
							break;
						}
						//debuglog('through: cat '.$i);
					}
				}
				//アイテムのキー無しの可能性をチェック。数値のみも受け付ける（キー無し数値はアイテムとみなされる）
				if ($CONF['NP_SimpleURL']['DropKeys'] and !$item_done and
					(
					($i >= 0 and !$CONF['NP_SimpleURL']['ShowBlogAlways']) or 
					($i >= 1 and $CONF['NP_SimpleURL']['ShowBlogAlways'] ^ $CONF['NP_SimpleURL']['ShowCategoryAlways']) or 
					($i >= 2 and $CONF['NP_SimpleURL']['ShowBlogAlways'] & $CONF['NP_SimpleURL']['ShowCategoryAlways']) 
					)
					) {
					if ($flg_num) $tempid = (int)$data[$i];
					else $tempid = $this->_getItemIDFromAlias($data[$i]);
					
					if ($tempid) {
						//debuglog('hit: item'.$tempid. ' ' .$data[$i]);
						$itemid = $tempid;
						$item_done = true;
						break;
					}
					//debuglog('through: item '.$i);
				}
				//debuglog('through: last '.$i);
				//意味のないURLとみなし、404にする
				$this->_http404();
			}
		}
		//debuglog(array('blogid'=>$blogid, 'catid'=>$catid, 'vpath'=>$virtualpath));
		$complete = true;
	}
	
	//URL生成
	//ここでは$baseurlの決定と、$set(=extra)の用意だけをする。最終的な組み立てはaddLinkParams()に委ねる。
	//ノーマルURLでも動くと思われるが、きちんと検証してない。
	function event_GenerateURL(&$data)
	{
		// if another plugin already generated the URL
		if ($data['completed']) return;
		
		global $manager, $CONF, $blog, $blogid;
		static $defBlogURL, $oldItemID, $oldBlogID, $oldBaseurl;
		
		//フィード配信時の例外。必ずDB側のburlを取得する。
		$flg_feed = (strpos($CONF['Self'], 'xml') === 0 or strpos($CONF['Self'], 'atom') === 0);
		
		if ($defBlogURL == null) {
			$defBlogURL = getBlogURLFromID($CONF['DefaultBlog']);
		}
		
		$params =& $data['params'];
		$set =& $params['extra']; //addLinkParams()に送る追加パラメータ
		
		//追加パラメータの例外処理
		if ($this->flg_fancy and $set['archivelist']) {
			$set['archivelist'] = '[[NONE]]'; //キーのみ、値無し(dummy)
		}
		
		//blogidはこの時点で必ず決まっているのでそれと$params['hogeid']から割り出したblogidとを比較
		//呼び出しブログのときはCONF['Self'](絶対URL指定)、それ以外のときは必ずburlを参照してベースurlとする
		switch ($data['type']) {
		case 'item':
			if ($params['itemid'] == $oldItemID) {
				$params['blogid'] = $oldBlogID;
				$baseurl = $oldBaseurl;
			}
			else {
				$params['blogid'] = $this->_getBlogIDFromItemID($params['itemid']);
				$baseurl = ($blogid == $params['blogid'] and !$flg_feed) ? $CONF['ItemURL'] : getBlogURLFromID($params['blogid']);
				$oldItemID = $params['itemid'];
				$oldBlogID = $params['blogid'];
				$oldBaseurl = $baseurl;
			}
			
			$set['blogid'] = $params['blogid'];
			$set['itemid'] = $params['itemid'];
			if ($CONF['NP_SimpleURL']['ShowCategoryAlways']) {
				$set['catid'] = ($params['catid']) ? $params['catid'] : $this->_getCatIDFromItemID($params['itemid']);
			}
			break;
		
		case 'member':
			$params['blogid'] = $blogid;
			$baseurl = $CONF['MemberURL'];
			
			$set['blogid'] = $params['blogid'];
			$set['memberid'] = $params['memberid'];
			if (isset($set['catid'])) unset($set['catid']); //例外
			break;
		
		case 'category':
			$params['blogid'] = $this->_getBlogIDFromCatID($params['catid']);
			$baseurl = ($blogid == $params['blogid'] and !$flg_feed) ? $CONF['CategoryURL'] : getBlogURLFromID($params['blogid']);
			
			$set['blogid'] = $params['blogid'];
			$set['catid'] = $params['catid'];
			break;
		
		case 'archivelist':
			if (!$params['blogid']) $params['blogid'] = $CONF['DefaultBlog'];
			$baseurl = ($blogid == $params['blogid'] and !$flg_feed) ? $CONF['ArchiveListURL'] : getBlogURLFromID($params['blogid']);
			
			$set['blogid'] = $params['blogid'];
			if ($this->flg_fancy) {
				$set['archivelist'] = '[[NONE]]'; //キーのみ、値無し(dummy)
			}
			else {
				$set['archivelist'] = $params['blogid'];
			}
			break;
		
		case 'archive':
			$baseurl = ($blogid == $params['blogid'] and !$flg_feed) ? $CONF['ArchiveURL'] : getBlogURLFromID($params['blogid']);
			
			$set['blogid'] = $params['blogid'];
			$set['archive'] = $params['archive'];
			break;
		
		case 'blog':
			$baseurl = ($blogid == $params['blogid'] and !$flg_feed) ? $CONF['BlogURL'] : getBlogURLFromID($params['blogid']);
			
			$set['blogid'] = $params['blogid'];
			break;
		}
		
		//addLinkParams()の変更はコアファイルを書き換えるか、NP_SimpleURL::addLinkParams()を呼ぶかのいずれか
		$data['url'] = $this->addLinkParams($baseurl, $set, '.html', true);
		$data['completed'] = true;
	}
	
	/**
	 * add link params
	 * 
	 * 単独でglobalfunctions.phpの addLinkParams() にコピペ・上書きしても動くはず。
	 * 
	 * @static
	 * @access public
	 * @param $link       base link
	 * @param $params     parameters (assoc array)
	 * @param $postfix    postfix to add extension like ".html"
	 * @param $flg_check  flag for event_GenerateURL
	 */
	function addLinkParams($link, $params, $postfix='', $flg_check=false)
	{
		global $manager, $CONF, $blog, $blogid;
		static $defBlogURL, $orderbase;
		
		if (! is_array($params) ) return $link;
		
		$blogid_copy = $params['blogid']; //後の処理で消える場合があるのでコピーをとっておく
		
		if ($flg_check) { //GenerateURLからの処理の続き
			if (!$CONF['NP_SimpleURL']['ShowBlogAlways']) {
				if ($defBlogURL == null) $defBlogURL = getBlogURLFromID($CONF['DefaultBlog']);
				
				if ($params['archivelist']) { //archivelistの例外
					unset($params['blogid']);
				}
				else if ($CONF['URLMode'] == 'pathinfo' and 
					($params['blogid'] == $CONF['DefaultBlog'] or //既定のブログ→blogid不要
					 $link != $defBlogURL) //既定以外の呼び出しURLはselectBlog()前提→blogid不要
					) {
					unset($params['blogid']);
				}
			}
		}
			
		if ($CONF['URLMode'] == 'pathinfo') {
			//パラメータの並び順を取得する
			$order = array();
			if (!$orderbase) {
				$orderbase = $CONF['NP_SimpleURL']['URLOrder'];
				asort($orderbase); //優先順位でソート
			}
			foreach (array_keys($orderbase) as $key) $order[$key] = ''; //先にキーをセット
			
			//$paramsに対応するキーのマッピング
			//ここに割り込む必要がないように、外部からのURL拡張は キーを書き換えずに使える名前にすること。
			$keymap = array(
				'blogid'      => $CONF['BlogKey'],
				'catid'       => $CONF['CategoryKey'],
				'special'     => $CONF['SpecialskinKey'],
				'itemid'      => $CONF['ItemKey'],
				'archivelist' => $CONF['ArchivesKey'],
				'archive'     => $CONF['ArchiveKey'],
				'memberid'    => $CONF['MemberKey'],
				);
			
			//キーや値の書き換え、並び順への追加
			foreach ($params as $key => $value) {
				//blogid, catid, memberid, itemidの書き換え（数値→文字列へ）
				if ($CONF['NP_SimpleURL']['UseAlias']) {
					switch ($key) {
					case 'blogid':
						$value = getBlogAliasFromID($value);
						break;
					case 'catid':
						$value = getCatAliasFromID($value, $blogid_copy);
						break;
					case 'itemid':
						$value = getItemAliasFromID($value);
						break;
					case 'memberid':
						$value = getMemberAliasFromID($value);
						break;
					}
				}
				
				if ($keymap[$key]) $key = $keymap[$key]; //該当キーを$CONF['hogeKey']に書き換え
				$order[$key] = $value; //並び順指定があればそのキーに値を格納。なければ$orderの最後尾に新規追加されていく
			}
			
			//実際にURLを組み立てる
			foreach ($order as $key => $value) {
				if ($value == '') continue;
				
				if ($CONF['NP_SimpleURL']['DropKeys'] and ($key == $CONF['BlogKey'] or $key == $CONF['CategoryKey'])) {
					$link .= ((substr($link, -1) == '/') ? '' : '/') . $value . '/'; //ディレクトリ構造を模して最後を閉じる
				}
				else if ($CONF['NP_SimpleURL']['DropKeys'] and $key == $CONF['ItemKey']) {
					$link .= ((substr($link, -1) == '/') ? '' : '/') . rawurlencode($value);
				}
				else if ($value == '[[NONE]]') { //キーのみ、値無し
					$link .= ((substr($link, -1) == '/') ? '' : '/') . $key;
				}
				else {
					$link .= ((substr($link, -1) == '/') ? '' : '/') . $key . '/' . rawurlencode($value);
				}
			}
			
			$link .= (substr($link, -1) == '/') ? '' : $postfix;
		}
		else { //normal url
			foreach ($params as $param => $value) {
				$link .= ((strpos($link, '?') === false) ? '?' : '&amp;') . $param . '=' . urlencode($value);
			}
		}
		return $link;
	}
	
	/**
	 * setter of url order
	 * 
	 * @static
	 * @access public
	 * @param $key   key of the parameter
	 * @param $rank  rank for order (integer; the smaller number is the higher rank)
	 */
	function setURLOrder($key, $rank)
	{
		global $CONF;
		$CONF['NP_SimpleURL']['URLOrder'][$key] = $rank;
	}
	
	
	function doItemVar(&$item, $id, $text='') {
		$id = (int)$id;
		if ($id) {
			$link = createItemLink($id);
			if ($text == '') $text = strip_tags($this->_getDataFromItemID($id, $type='ititle'));
			echo "<a href=\"{$link}\">{$text}</a>";
		}
	}
	
	
	
	/* ----------------- helper methods ----------------- */
	
	function _http301($url)
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $url);
		exit;
	}
	
	function _http404()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().$language.'.php'))
			include_once($this->getDirectory().$language.'.php');
		else
			include_once($this->getDirectory().'english.php');
		
		header('HTTP/1.1 404 Not Found');
		doError(_PLUG_SIMPLEURL_MSG404);
	}
	
	function _isValidURL($url)
	{
		return preg_match('/^https?\:\/\//', $url);
	}
	
	function _quoteSmart($val)
	{
		if (get_magic_quotes_gpc()) $val = stripslashes($val);
		if (preg_match('/^[0-9]+$/', $val)) {
			$qmark = '';
		}
		else {
			$qmark = '"';
			$val = mysql_real_escape_string($val);
		}
		return $qmark . $val . $qmark;
	}
	
	function _getBlogIDFromAlias($name)
	{
		static $buff;
		
		if (! isset($buff)) {
			$buff[$name] = quickQuery('SELECT bnumber as result FROM ' . sql_table('blog') . 
				' WHERE bshortname="' . mysql_real_escape_string($name) . '"');
		}
		return ($buff[$name]) ? $buff[$name] : 0;
	}
	
	function _getMemberIDFromAlias($name)
	{
		static $buff;
		
		$name = str_replace('_', ' ', $name);
		if (!$buff[$name]) {
			$buff[$name] = quickQuery('SELECT mnumber as result FROM ' . sql_table('member') . 
				' WHERE mname="' . mysql_real_escape_string($name) .'"');
		}
		return ($buff[$name]) ? $buff[$name] : 0;
	}
	
	function _getCatIDFromAlias($name)
	{
		global $blogid;
		static $buff;
		
		if (! isset($buff)) {
			//$buff = array_flip($this->getAllCategoryOptions('cshortname'));
			$query = 'SELECT catid, cshortname FROM ' . sql_table('plug_simpleurl');
			if ($blogid) $query .=' WHERE blogid='. (int)$blogid;
			$res = sql_query($query);
			
			while($data = mysql_fetch_assoc($res)) {
				$buff[ $data['cshortname'] ] = $data['catid'];
			}
		}
		
		return ($buff[$name]) ? $buff[$name] : 0;
	}
	
	function _getItemIDFromAlias($name)
	{
		/*static $buff;
		
		$name = urldecode($name);
		if (!$buff[$name]) {
			$buff[$name] = quickQuery('SELECT inumber as result FROM ' . sql_table('item') . 
				' WHERE ititle="' . mysql_real_escape_string($name) . '"');
		}
		return ($buff[$name]) ? $buff[$name] : 0;
		*/
		if (preg_match('/-([0-9]+?)$/', $name, $m)) {
			return (int)$m[1];
		}
		else {
			return 0;
		}
	}
	
	function _getDataFromItemID($itemid, $type='')
	{
		$itemid = (int)$itemid;
		
		if (!$this->arr_item[$itemid]) {
			$res = sql_query('SELECT iblog, icat, ititle FROM ' . sql_table('item') . ' WHERE inumber=' . $itemid);
			if (mysql_num_rows($res)) {
				$this->arr_item[$itemid] = mysql_fetch_assoc($res);
			}
		}
		if ($type) return $this->arr_item[$itemid][$type];
		else return $this->arr_item[$itemid];
	}
	
	function _getBlogIDFromItemID($itemid)
	{
		return $this->_getDataFromItemID($itemid, 'iblog');
	}
	
	function _getCatIDFromItemID($itemid)
	{
		return $this->_getDataFromItemID($itemid, 'icat');
	}
	
	function _getBlogIDFromCatID($catid)
	{
		static $buff;
		
		$catid = (int)$catid;
		if (! $buff[$catid]) {
			$res = sql_query('SELECT catid, cblog FROM ' . sql_table('category'));
			if (mysql_num_rows($res)) {
				while ($data = mysql_fetch_assoc($res)) {
					$buff[ $data['catid'] ] = $data['cblog'];
				}
			}
		}
		return $buff[$catid];
	}
	
	function _getCatNameFromID($id)
	{
		return quickQuery('SELECT cname as result FROM '. sql_table('category') .' WHERE catid='. (int)$id);
	}
	
	function _canEdit($type='admin')
	{
		global $member;
		
		$query = 'SELECT * FROM '.sql_table('team').' WHERE tmember='. $member->getID();
		if ($type == 'admin') $query .= ' and tadmin=1';
		return (mysql_num_rows(sql_query($query)) != 0);
	}
	
	
	/* ----------------- methods for admin area ----------------- */
	
	function _makeAdminArea(&$oPluginAdmin)
	{
		global $manager, $member;
		
		$adminurl = $this->getAdminURL();
		
		if (!$member->isLoggedIn() or !$this->_canEdit()) {
			$oPluginAdmin->start();
			echo '<p>' . _ERROR_DISALLOWED . '</p>';
			$oPluginAdmin->end();
			exit;
		}
		
//<script type='text/javascript' src='../../javascript/numbercheck.js'></script>
		$oPluginAdmin->start("
<script type='text/javascript'>
<!--

function confirm_check(message)  {
  if( confirm(message) ){
    sent = true;
    return true;
  }
  else {
    return false;
  }
}

// -->
</script>
<style type='text/css'>
<!--
p.message {
	font-weight: bold;
	color: #c00;
}
form.button {
	display: inline;
}
table.group {
	margin: 5px 0;
}
table.group td {
	background-color: #ddd;
}
table.link {
	margin: 0;
}
table.link th {
	/*background-color: #ddd;*/
}
table.link td.stripe {
	background-color: #eee;
}
span.idlabel {
	border: 1px solid #666;
	padding: 1px 2px;
	background-color: #999;
	color: white;
}
-->
</style>
");
		
		echo "<h2>SimpleURL</h2>";
		
		$action = requestVar('action');
		$actions = array (
			'index',
			'update',
		);
		
		if ($action == '') {
			$this->_makeCatAliasForm();
		}
		else if (in_array($action, $actions)) { 
			if (!$manager->checkTicket()) {
				echo '<p class="error">Error: ' . _ERROR_BADTICKET . '</p>';
			} 
			else {
				switch ($action) {
				case 'index':
					$this->_makeCatAliasForm();
					break;
				case 'update':
					$isok = $this->_updateCatAlias();
					if ($isok) echo '<p class="message">Updated successfully.</p>';
					else echo '<p class="error">Error: Update failed (in some records).</p>';
					
					$this->_makeCatAliasForm();
					break;
				}
			}
		} 
		else {
			echo '<p class="error">Error: Invalid action type.</p>';
		}
		$oPluginAdmin->end();
	}
	
	function _makeCatAliasForm()
	{
		global $manager;
		
		$adminurl = $this->getAdminURL();
		$action = 'update';
		
		echo <<<OUT
<h3>Setting: Category Alias</h3>
<table>
<form method="post" action="{$adminurl}">
<input type="hidden" name="action" value="{$action}" />
<input type="hidden" name="type" value="categoryalias" />
OUT;
		$manager->addTicketHidden();
		echo <<<OUT
<tr>
	<th>Blog</th><th>Category</th><th>Alias Name [0-9a-z_-]</th>
</tr>
OUT;
		$data = getCatAliasesFromDB();
		foreach ($data as $blogid => $bdata) {
			$blogname = htmlspecialchars(getBlogNameFromID($blogid));
			foreach ($bdata as $catid => $cshortname) {
				$catname = htmlspecialchars($this->_getCatNameFromID($catid));
				echo <<<OUT
<tr>
	<td><span class="idlabel">ID:{$blogid}</span> {$blogname}</td><td><span class="idlabel">ID:{$catid}</span> {$catname}</td><td><input type="text" name="catids[{$catid}]" value="{$cshortname}" size="20" /></td>
</tr>
OUT;
			}
		}
		echo <<<OUT
<tr>
	<th colspan="3" style="text-align:center"><input type="submit" value="Submit"></th>
</tr>
</form>
</table>

OUT;
	}
	
	function _updateCatAlias()
	{
		if (!is_array($_POST['catids'])) return false;
		
		$err = 0;
		foreach ($_POST['catids'] as $catid => $cshortname) {
			$cshortname = strtolower($cshortname);
			$cshortname = preg_replace('/[^0-9a-z_-]/', '', $cshortname);
			if ($cshortname == '') {
				$err++;
				continue;
			}
			$query = sprintf("UPDATE %s SET cshortname=%s WHERE catid=%d",
				sql_table("plug_simpleurl"),
				$this->_quoteSmart($cshortname),
				$this->_quoteSmart($catid)
				);
			$res = sql_query($query);
			if (!$res) $err++;
		}
		return ($err == 0);
	}
	
}
?>
