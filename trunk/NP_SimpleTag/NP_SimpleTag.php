<?php
/*
	NP_SimpleTag
	
	License
	-------
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	Usage
	-----
	1.  Install this plugin.
	2.  First, access ".../nucleus/simpletag/?action=setshadow" directly and update tag data.
	3.  Set template var:
		<%SimpleTag()%> in item body part.
	4.  Set skinvar:
		<%SimpleTag(tpl:template_name, type:showitem, range:blog, amount:10)%> //<%blog%> like
		<a href="<%SimpleTag(type:prevlink, range:blog, amount:10)%>">Prev</a> //prev/nextlink
		<%SimpleTag(type:tagcloud, range:all, amount:100)%> //tagcloud
		<%SimpleTag(type:related, range:all, amount:10)%> //related item list in item page
		<%if(SimpleTag,isset)%>
			<!-- execute if tag is set -->
		<%endif%>
	5.  Input tags in item option.
	6.  If you rename a category, click "update button" in blog settings to update tag data.
	
	History
	-------
	2008-12-11 v0.42: Improve related scoring and listing.
	                  Fix bug in 'error' skintype. (yu)
	2008-07-21 v0.41: Add 'range:searchable' and fix some sql queries. (yu)
	2008-06-22 v0.4 : Show tag hints near the input field. (yu)
	2008-06-20 v0.3 : Add table column 'catid' (which represents 'shadow tag' - a connection from tag to category). 
	                  Add 'prev/nextlink' and 'related' type. (yu)
	2008-06-19 v0.2 : Add 'showitem' type. (yu)
	2008-06-05 v0.1 : First release. (yu http://nucleus.datoka.jp/)
*/

define('NP_SIMPLETAG_DELIM', ' ');

class NP_SimpleTag extends NucleusPlugin 
{
	function getName() { return 'Simple Tag'; }
	function getAuthor() { return 'yu'; }
	function getURL() { return 'http://nucleus.datoka.jp/'; }
	function getVersion() { return '0.42'; }
	function getMinNucleusVersion() { return 322; }
	function supportsFeature($what) { return (int)($what == 'SqlTablePrefix'); }
	function hasAdminArea() { return 1; }
	function getDescription() 
	{
		return 'Simple tagging system for item.';
	}
	
	function getEventList() 
	{ 
		return array(
			'PostAuthentication',
			'AddItemFormExtras', 
			'EditItemFormExtras', 
			'PostAddItem', 
			'PreUpdateItem', 
			'PostMoveItem', 
			'PostDeleteItem', 
			'BlogSettingsFormExtras', 
			'QuickMenu'
			);
	}
	
	var $params;
	var $tpl;
	var $container;   //container obj
	var $searchables; //searchable blogs
	
	function init() 
	{
		global $manager, $blogid;
		
		//if NP_Container is installed, hold reference of container instance.
		if ($manager->pluginInstalled('NP_Container')) {
			$this->container =& $manager->getPlugin('NP_Container');
			$this->_checkContainer();
		}
		
		if ($this->tpl['LINK'] == '')   $this->tpl['LINK']   = '<a href="<%link%>" title="タグ：<%tag%>"><%tag%></a>';
		if ($this->tpl['NOLINK'] == '') $this->tpl['NOLINK'] = '<span title="タグ：<%tag%>"><%tag%></span>';
		
		if ($this->tpl['CLOUDHEADER'] == '') $this->tpl['CLOUDHEADER'] = '<ul class="tagcloud">';
		if ($this->tpl['CLOUDBODY'] == '')   $this->tpl['CLOUDBODY']   = '<li class="__<%amount%>__<%extraclass%>"><a href="<%link%>" title="タグ：<%tag%>"><%tag%></a></li>';
		if ($this->tpl['CLOUDFOOTER'] == '') $this->tpl['CLOUDFOOTER'] = '</ul>';
		
		if ($this->tpl['RELHEADER'] == '') $this->tpl['RELHEADER'] = '<dl class="related">';
		if ($this->tpl['RELBODY'] == '')   $this->tpl['RELBODY']   = '<dt><span class="date">[<%date%>]</span> <a href="<%link%>"><%title%></a> <span class="score">スコア <%score%></span></dt><dd><%body%></dd>';
		if ($this->tpl['RELFOOTER'] == '') $this->tpl['RELFOOTER'] = '</dl>';
		if ($this->tpl['RELNONE'] == '')   $this->tpl['RELNONE']   = '<p>見つかりませんでした。</p>';
		
	}
	
	function install() 
	{
		$this->createOption('flg_qmenu', 'Show on quick menu', 'yesno', 'yes');
		$this->createOption('flg_erase', 'Erase data on uninstallation', 'yesno', 'no');
		
		sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plug_simpletag') ." (
			`id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`tag`    VARCHAR(200) NOT NULL DEFAULT '',
			`itemid` INT UNSIGNED NOT NULL DEFAULT 0,
			`catid`  INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY `id` (`id`),
			KEY `tag` (`tag`),
			KEY `itemid` (`itemid`),
			KEY `catid` (`catid`)
			)"
			);
	}
	
	function uninstall() 
	{
		if ($this->getOption('flg_erase') == 'yes') {
			sql_query("DROP TABLE ". sql_table('plug_simpletag'));
		}
	}
	
	
	/* ----------------- event methods ----------------- */
	
	function event_PostAuthentication(&$data) 
	{
		global $CONF, $manager;
		if ($manager->pluginInstalled('NP_SimpleURL')) { //URL修飾キーを登録する
			$CONF['TagKey'] = 'tag';
			$obj =& $manager->getPlugin('NP_SimpleURL');
			$obj->SetURLOrder($CONF['TagKey'], 3);
		}
	}
	
	function event_AddItemFormExtras(&$data) 
	{
		global $CONF;
		
		$delim = NP_SIMPLETAG_DELIM;
		echo <<< EOH
<h3>Simple Tag</h3>
<p>
	<label for="inputtags">Tags (delimiter "{$delim}"):</label><br />
	<input type="text" value="" id="inputtags" name="tags" size="60" onclick="storeCaret(this);" onfocus="simpletag_gethints();" />
</p>
<div id="simpletag_hints">-</div>
EOH;
		$this->_outputScriptForInput();
	}
	
	function event_EditItemFormExtras(&$data) 
	{
		$query = sprintf("SELECT tag, catid FROM %s WHERE itemid=%d ORDER BY id",
			sql_table('plug_simpletag'),
			$this->_quoteSmart($data['variables']['itemid'])
			);
		$res = sql_query($query);
		
		if (! mysql_num_rows($res)) { //タグ無しなので新規フォームへ遷移する
			$this->event_AddItemFormExtras($data);
			return;
		}
		
		$tags = array();
		while ($row = mysql_fetch_assoc($res)) {
			if ($row['catid'] != 0) { //同名タグ（カテゴリー連結タグ）を抽出
				$cid   = $row['catid'];
				//$cname = $row['tag'];
			}
			else {
				$tags[] = $row['tag'];
			}
		}
		
		$val = htmlspecialchars( join(NP_SIMPLETAG_DELIM, $tags) );
		$delim = NP_SIMPLETAG_DELIM;
		echo <<< EOH
<h3>Simple Tag</h3>
<p>
	<label for="inputtags">Tags (delimiter "{$delim}"):</label><br />
	<input type="text" value="{$val}" id="inputtags" name="tags" size="60" onclick="storeCaret(this);" onfocus="simpletag_gethints();" />
</p>
<div id="simpletag_hints">-</div>
<input type="hidden" value="{$val}" id="simpletag_chk" name="simpletag_chk" />
<input type="hidden" value="{$cid}" id="simpletag_cid" name="simpletag_cid" />
EOH;
		$this->_outputScriptForInput();
	}
	
	function event_PostAddItem(&$data) 
	{
		$tags = explode(NP_SIMPLETAG_DELIM, trim(requestVar('tags'), NP_SIMPLETAG_DELIM." \t\n\r\0\x0B"));
		
		//同名タグ（カテゴリーと同名のタグ）を用意する（itemidから）
		$query = sprintf('SELECT c.cname, c.catid FROM %s AS i, %s AS c WHERE i.inumber=%d AND i.icat=c.catid',
			sql_table('item'),
			sql_table('category'),
			$data['itemid']
			);
		$res = sql_query($query);
		if (! mysql_num_rows($res)) return; //取得失敗、返してしまう
		$row = mysql_fetch_assoc($res);
		$cname = $row['cname'];
		$catid = (int)$row['catid'];
		
		$tags2 = array();
		foreach ($tags as $tag) {
			if ($tag == '') continue;
			$tags2[$tag] = 0; //通常タグはcatid=0
		}
		$tags2[ $cname ] = $catid; //同名タグを仕込む
		
		foreach ($tags2 as $tag => $cid) {
			$query = sprintf('INSERT INTO %s SET tag=%s, itemid=%d, catid=%d',
				sql_table('plug_simpletag'),
				$this->_quoteSmart($tag),
				$this->_quoteSmart($data['itemid']),
				$this->_quoteSmart($cid)
				);
			sql_query($query);
		}
	}
	
	function event_PreUpdateItem(&$data) 
	{
		$tags = explode(NP_SIMPLETAG_DELIM, trim(requestVar('tags'), NP_SIMPLETAG_DELIM." \t\n\r\0\x0B"));
		$chks = explode(NP_SIMPLETAG_DELIM, trim(requestVar('simpletag_chk'),  NP_SIMPLETAG_DELIM." \t\n\r\0\x0B"));
		$oldcid  = intRequestVar('simpletag_cid');
		
		if ($tags != $chks) { //通常タグの更新
			//まずは古いデータを削除
			$query = sprintf('DELETE FROM %s WHERE itemid=%d AND catid=0',
				sql_table('plug_simpletag'),
				$this->_quoteSmart($data['itemid'])
				);
			sql_query($query);
			
			//新しいデータを挿入
			foreach ($tags as $tag) {
				if ($tag == '') continue;
				$query = sprintf('INSERT INTO %s SET tag=%s, itemid=%d, catid=0',
					sql_table('plug_simpletag'),
					$this->_quoteSmart($tag),
					$this->_quoteSmart($data['itemid'])
					);
				sql_query($query);
			}
		}
		
		if ($oldcid != $data['catid']) { //同名タグの更新
			//同名タグを用意する（catidから）
			$query = sprintf('SELECT cname AS result FROM %s WHERE catid=%d',
				sql_table('category'),
				$data['catid']
				);
			$cname = quickQuery($query);
			
			if ($oldcid == 0) { //タグ無しアイテムは新規フォームに遷移（$oldcidも未セット）。ただ_setShadowTag()後は問題なしか
				$query = sprintf('INSERT INTO %s SET tag=%s, itemid=%d, catid=%d',
					sql_table('plug_simpletag'),
					$this->_quoteSmart($cname),
					$this->_quoteSmart($data['itemid']),
					$this->_quoteSmart($data['catid'])
					);
			}
			else {
				$query = sprintf('UPDATE %s SET tag=%s, catid=%d WHERE itemid=%d AND catid>0',
					sql_table('plug_simpletag'),
					$this->_quoteSmart($cname),
					$this->_quoteSmart($data['catid']),
					$this->_quoteSmart($data['itemid'])
					);
			} 
			sql_query($query);
		}
	}
	
	function event_PostMoveItem(&$data) 
	{ //移動リンクで変化する場合
		$query = "SELECT cname AS result FROM ". sql_table('category') ." WHERE catid=". $data['destcatid'];
		$tag = quickQuery($query);
		if ($tag == '') return;
		
		$query = sprintf('UPDATE %s SET tag=%s, catid=%d WHERE itemid=%d AND catid>0',
			sql_table('plug_simpletag'),
			$this->_quoteSmart($tag),
			$this->_quoteSmart($data['destcatid']),
			$this->_quoteSmart($data['itemid'])
			);
		$res = sql_query($query);
	}
	
	function event_PostDeleteItem(&$data) 
	{
		$query = "SELECT tag FROM ". sql_table('plug_simpletag') ." WHERE itemid=". $data['itemid'];
		$res = sql_query($query);
		
		if (mysql_num_rows($res)) {
			$query = sprintf("DELETE FROM %s WHERE itemid=%d",
				sql_table('plug_simpletag'),
				$this->_quoteSmart($data['itemid'])
				);
			sql_query($query);
		}
	}
	
	function event_BlogSettingsFormExtras(&$data) 
	{
		global $CONF;
		$url = $CONF['ActionURL'].'?action=plugin&amp;name=SimpleTag&amp;type=update_shadowtag';
		$bid = $data['blog']->getID();
		echo <<< EOH
<h4>Simple Tag</h4>
<form method="post" action="$url">
<input type="hidden" value="$bid" id="simpletag_bid" name="simpletag_bid" />
<p>カテゴリー名を変更した後はタグ情報を更新してください:
<input type="submit" value="タグ情報を更新" id="simpletag_submit" name="simpletag_submit" />
</p>
</form>
EOH;
	}
	
	function event_QuickMenu(&$data) 
	{
		global $member;
		
		if ($this->getOption('flg_qmenu') != 'yes') return;
		if (!$this->_canEdit()) return;
		
		array_push(
			$data['options'], 
			array(
				'title'   => 'Simple Tag',
				'url'     => $this->getAdminURL(),
				'tooltip' => 'Operation for tags'
			)
		);
	}
	
	
	/* ----------------- do methods ----------------- */
	
	function doIf($var) 
	{
		$ret = false;
		switch ($var) {
		case 'isset':
			$ret = ($_REQUEST['tag'] != '');
			break;
		}
		
		return $ret;
	}
	
	function doAction($type) 
	{
		switch ($type) {
		case 'js_gethints':
			$tag = requestVar('sourcetag');
			if ($tag == '') break;
			
			$buff = '';
			$hints = $this->_getHints($tag);
			if (!count($hints)) {
				echo '(no hints)';
				break;
			}
			foreach ($hints as $hint) {
				$buff .= "<span style='text-decoration:underline; cursor:pointer;' onclick='simpletag_input(\"{$hint}\");return false;'>{$hint}</span> ";
			}
			echo $buff;
			break;
		case 'update_shadowtag':
			$bid = requestVar('simpletag_bid');
			$this->_updateShadowTag($bid);
			redirect( serverVar('HTTP_REFERER') );
			break;
		}
	}
	
	function doTemplateVar(&$item) 
	{
		global $blogid;
		
		if (is_object($item)) $itemid = $item->itemid;
		else $itemid = $item['itemid'];
		
		//set params
		$params = func_get_args();
		array_shift($params); // remove skintype parameter
		$this->_setParams($params);
		
		$query = sprintf('SELECT t.tag AS tag, i.iblog AS blogid FROM %s AS t, %s AS i '
			.'WHERE t.itemid=%d %s AND t.itemid=i.inumber ORDER BY t.id', 
			sql_table('plug_simpletag'),
			sql_table('item'),
			$this->_quoteSmart($itemid),
			($this->params['shadowtag'] == 'show') ? '' : ' AND t.catid=0'
			);
		$res = sql_query($query);
		
		if (! mysql_num_rows($res)) return;
		
		$tags = array();
		while ($data = mysql_fetch_assoc($res)) {
			$extra = array('tag' => $data['tag']); //エスケープかける前の文字列を渡す
			if ($this->params['special']) $extra['special'] = $this->params['special']; //スペシャルスキン情報をセット
			//$data['link'] = createBlogidLink($blogid, $extra); //現在のブログ起点のURL
			$data['link'] = createBlogidLink($data['blogid'], $extra); //アイテム所属のブログ起点のURL
			$data['tag'] = htmlspecialchars($data['tag']);
			
			if ($this->params['link'] == 'no') {
				$output = TEMPLATE::fill($this->tpl['NOLINK'], $data);
			}
			else {
				$output = TEMPLATE::fill($this->tpl['LINK'], $data);
			}
			$tags[] = $output;
		}
		
		$delim = ($this->params['delim']) ? $this->params['delim'] : NP_SIMPLETAG_DELIM;
		$out = '';
		$out .= ($this->params['prefix']) ? $this->params['prefix'] : '';
		$out .= join($delim, $tags);
		$out .= ($this->params['postfix']) ? $this->params['postfix'] : '';
		echo $out;
	}
	
	function doSkinVar($skinType) 
	{
		global $blogid, $catid, $itemid, $startpos;
		
		//set params
		$params = func_get_args();
		array_shift($params); // remove skintype parameter
		$this->_setParams($params);
		
		if ($this->params['amount']) {
			list($this->params['amount'], $this->params['offset']) = sscanf($this->params['amount'], '%d(%d)');
		}
		
		//get searchable blogs
		if (!count($this->searchables)) {
			$res = sql_query('SELECT bnumber FROM '.sql_table('blog').' WHERE bincludesearch=1');
			if (mysql_num_rows($res)) {
				while ($row = mysql_fetch_assoc($res)) {
					$this->searchables[] = intval($row['bnumber']);
				}
			}
			$this->searchables[] = $blogid;
			$this->searchables = array_unique($this->searchables);
		}
		
		switch ($this->params['type']) {
		case 'tagcloud':
			if ($this->params['range'] == 'category' and $catid) { //category
				$this->_makeTagCloud($this->params['range']);
			}
			else if ($this->params['range'] == 'tag' and $_REQUEST['tag']) { //tag
				$this->_makeTagCloud($this->params['range'], array('tag'=>$_REQUEST['tag']));
			}
			else if ( in_array($this->params['range'], array('recent','blog','searchable','all')) ) { //others
				$this->_makeTagCloud($this->params['range']);
			}
			break;
		case 'showtag':
			if (is_numeric($this->params['range'])) { //item #1 (range:N(itemid))
				$data = array('itemid' => (int)$this->params['range']);
				$this->doTemplateVar($data);
			}
			else if ($skinType == 'item' and $this->params['range'] == 'item') { //item #2 (skintype:item)
				$data = array('itemid' => $GLOBALS['itemid']);
				$this->doTemplateVar($data);
			}
			break;
		case 'showitem':
			if ($_REQUEST['tag']) {
				$amount = ($this->params['amount']) ? $this->params['amount'] : 10;
				$offset = ($this->params['offset']) ? $this->params['offset'] : 0;
				$offset += $startpos;
				$this->amountfound = $this->_blogQuery($_REQUEST['tag'], $skinType, $this->params['range'], $amount, $offset); //range = blog | all
			}
			break;
		case 'related':
			if ($skinType == 'item') {
				$this->_makeRelated($itemid, $this->params['range']);
			}
			break;
		case 'prevlink':
		case 'nextlink':
			$amount = ($this->params['amount']) ? $this->params['amount'] : 10;
			$this->_makeLink($amount, $startpos, $this->params['type'], $this->params['linktext'], $skinType);
			break;
		case 'name':
			if (!$_REQUEST['tag']) return;
			echo $this->params['prefix'] . htmlspecialchars($_REQUEST['tag']) .$this->params['postfix'];
			break;
		default:
			//nothing to do.
			break;
		}
	}
	
	
	/* ----------------- helper methods ----------------- */
	
	function _makeRelated($itemid, $range='blog') 
	{
		global $blog, $blogid, $catid, $CONF, $manager;
		
		if($blog){
			$b =& $blog; 
		}else{
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		
		//アイテムに属するタグを取得する
		$query = sprintf('SELECT tag, catid FROM %s WHERE itemid=%d',
			sql_table('plug_simpletag'),
			$this->_quoteSmart($itemid)
			);
		$res = sql_query($query);
		if (! mysql_num_rows($res)) {
			echo $this->tpl['RELNONE'];
			return;
		}
		
		$tags = array();
		while ($data = mysql_fetch_assoc($res)) {
			$tags[ $data['tag'] ] = $data['catid'];
		}
		$maxscore = count($tags);
		$limit = ($this->params['amount']) ? $this->params['amount'] : 5;
		
		//タグとマッチするアイテムを取得、スコアを重み付けする
		$scores = array();
		$times = array();
		$items = array();
		$cnt_torder = $maxscore; //タグ並びでの重み付け用
		if ($range == 'blog') $cond = 'iblog='. $blogid;
		else if ($range == 'searchable') $cond = 'iblog IN ('. join(',', $this->searchables) .')';
		else if ($range == 'category' and $catid) $cond = 'icat='. $catid;
		else $cond = '1'; //dummy
		foreach ($tags as $tag => $tagcat) {
/*			$query = sprintf('SELECT itemid FROM %s WHERE tag=%s AND '.
				'itemid IN (SELECT inumber FROM %s WHERE %s AND idraft=0 AND itime<%s) ',
				sql_table('plug_simpletag'),
				$this->_quoteSmart($tag),
				sql_table('item'),
				$cond,
				mysqldate($b->getCorrectTime())
				);*/
			$query = sprintf('SELECT itemid, itime FROM %s AS t, %s AS i WHERE t.itemid=i.inumber '.
				'AND tag=%s AND idraft=0 AND itime<%s AND %s ',
				sql_table('plug_simpletag'),
				sql_table('item'),
				$this->_quoteSmart($tag),
				mysqldate($b->getCorrectTime()),
				$cond
				);
			$res = sql_query($query);
			if (! mysql_num_rows($res)) continue;
			
			while ($data = mysql_fetch_assoc($res)) {
				if ($data['itemid'] == $itemid) continue; //現アイテム自体は却下
				
				$add = ($tagcat) ? 25 : 100; //シャドウタグでの重み付けは1/4
				$add += ($tagcat) ? 0 : round(10 * ($cnt_torder / $maxscore)); //タグは前の方が少し重い
				$scores[ $data['itemid'] ] += $add;
				$times[ $data['itemid'] ] = $data['itime'];
			}
			$cnt_torder --;
		}
		if (!count($scores)) {
			echo $this->tpl['RELNONE'];
			return;
		}
		
		foreach (array_keys($scores) as $iid) {
			$items[$iid] = array(
				'itemid' => $iid,
				'score' => $scores[$iid],
				'time' => $times[$iid]
				);
		}
		//マルチソートを掛ける（スコア、日付の順）
		array_multisort($scores, SORT_NUMERIC, SORT_DESC, $times, SORT_STRING, SORT_DESC, $items);
		$items = array_slice($items, 0, $limit);
		//multisortの時点で数値キーがリセットされたことに注意
		
		//アイテム情報を取得する
		$iids = array();
		foreach ($items as $i) {
			$iids[] = $i['itemid'];
		}
		$query = sprintf('SELECT * FROM %s WHERE inumber IN (%s)',
			sql_table('item'),
			join(',', $iids)
			);
		$res = sql_query($query);
		
		if (! mysql_num_rows($res)) {
			echo $this->tpl['RELNONE'];
			return;
		}
		
		while ($data = mysql_fetch_assoc($res)) {
			for ($i=0; $i<count($items); $i++) {
				if ($items[$i]['itemid'] == $data['inumber']) {
					$items[$i]['title']  = $data['ititle'];
					$items[$i]['body']   = $data['ibody'];
					$items[$i]['catid']  = $data['icat'];
					break;
				}
			}
		}
		
		//関連アイテムのリストを表示する
		$cnt = 0;
		$len = ($this->params['len']) ? $this->params['len'] : 150;
		$adjscore = ($maxscore > 2) ? $maxscore : 2; //スコア調整用、タグが多い時は2で切る。
		echo $this->tpl['RELHEADER'];
		foreach ($items as $key => $data) {
			if (++$cnt > $limit) break;
			$data['score'] = round($data['score'] / $adjscore);
			$data['title'] = strip_tags($data['title']);
			$data['body']  = shorten(strip_tags($data['body']), $len, '...');
			$data['date']  = substr($data['time'], 0, 10);
			$data['link']  = createItemLink($data['itemid'], array('catid'=>$data['catid']));
			echo TEMPLATE::fill($this->tpl['RELBODY'], $data);
		}
		echo $this->tpl['RELFOOTER'];
	}
	
	//based on _searchlink() in libs/ACTIONS.php
	function _makeLink($maxresults, $startpos, $direction, $linktext = '', $skinType) 
	{
		global $CONF, $query, $blogid, $catid, $archive;
		// TODO: Move request uri to linkparams. this is ugly. sorry for that.
		$startpos	= intval($startpos);		// will be 0 when empty.
		$parsed		= parse_url(serverVar('REQUEST_URI'));
		$parsed		= $parsed['query'];
		$url		= '';
		$tag        = $_REQUEST['tag'];
		
		$extra['tag'] = $tag;
		if ($this->params['special']) $extra['special'] = $this->params['special']; //スペシャルスキン情報をセット
		
		if ($catid) $extra['catid'] = $catid;
		if ($skinType == 'archive'){
			$baseurl = createArchiveLink($blogid, $archive, $extra);
		}
		else {
			$baseurl = createBlogidLink($blogid, $extra);
		}
		
		switch ($direction) {
			case 'prevlink':
				if ( intval($startpos) - intval($maxresults) > 0) {
					$startpos = intval($startpos) - intval($maxresults);
					$url = $baseurl.'?'.alterQueryStr($parsed,'startpos',$startpos);
				}
				else {
					$url = $baseurl;
				}
				break;
			case 'nextlink':
				$iAmountOnPage = $this->amountfound;
				if ($iAmountOnPage == 0) {
					// [%nextlink%] or [%prevlink%] probably called before [%blog%] or [%searchresults%]
					// try a count query
					$range = $this->params['range'];
					$amount = $maxresults;
					$offset = ($this->params['offset']) ? $this->params['offset'] : 0;
					$offset += $startpos;
					$iAmountOnPage = $this->_blogQuery($tag, $skinType, $range, $amount, $offset, 'count') - intval($startpos);
				}
				if (intval($iAmountOnPage) >= intval($maxresults)) {
					$startpos = intval($startpos) + intval($maxresults);
					$url = $baseurl.'?'.alterQueryStr($parsed,'startpos',$startpos);
				}
				else {
					$url = $baseurl;
				}
				break;
			default:
				break;
		} // switch($direction)
		
		if ($url != '')
			echo $this->_link($url, $linktext);
	}
	
	//copy of _link() in libs/ACTIONS.php
	function _link($url, $linktext = '')
	{
		$u = htmlspecialchars($url);
		$u = preg_replace("/&amp;amp;/",'&amp;',$u); // fix URLs that already had encoded ampersands
		if ($linktext != '')
			$l = '<a href="' . $u .'">'.htmlspecialchars($linktext).'</a>';
		else
			$l = $u;
		return $l;
	}
	
	function _blogQuery($tag, $skinType, $range='blog', $amount='10', $offset='0', $mode='') 
	{
		global $blog, $CONF, $catid, $archive, $manager;
		
		$tag = mysql_real_escape_string($tag);
		
		if($blog){
			$b =& $blog; 
		}else{
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		
		if ($mode == 'count') {
			$query = 'SELECT COUNT(*) as result ';
		}
		else {
			$query = 'SELECT '
				.'i.inumber AS itemid, '
				.'i.ititle AS title, '
				.'i.ibody AS body, '
				.'m.mname AS author, '
				.'m.mrealname AS authorname, '
				.'i.itime, '
				.'i.imore AS more, '
				.'m.mnumber AS authorid, '
				.'m.memail AS authormail, '
				.'m.murl AS authorurl, '
				.'c.cname AS category, '
				.'i.icat AS catid, '
				.'i.iclosed AS closed';
		}
		
		$query .= ' FROM '.sql_table('item').' AS i, '.sql_table('member').' AS m, '.sql_table('category').' AS c WHERE 1';
		
		if ($range == 'blog') {
			$query .= ' AND i.iblog='. $b->blogid;
		}
		else if ($range == 'searchable') {
			$query .= ' AND i.iblog IN ('. join(',', $this->searchables) .')';
		}
		if ($catid) {
			$query .= ' AND i.icat='. $catid;
		}
		
		$query .= ' AND i.inumber IN (SELECT itemid FROM '.sql_table('plug_simpletag').' WHERE tag="'. $tag .'")'
			.' AND i.iauthor=m.mnumber'
			.' AND i.icat=c.catid'
			.' AND i.idraft=0';
		
		if ($skinType == 'archive') {
			list($y, $m, $d) = sscanf($archive,'%d-%d-%d');
			if ($d) {
				$ts_start = mktime(0,0,0,$m,$d,$y);
				$ts_end   = mktime(0,0,0,$m,$d+1,$y);  
			} 
			else {
				$ts_start = mktime(0,0,0,$m,1,$y);
				$ts_end   = mktime(0,0,0,$m+1,1,$y);
			}
			$query .= ' AND i.itime>=' . mysqldate($ts_start) .' AND i.itime<' . mysqldate($ts_end);
		}
		else {
			$query .= ' AND i.itime<=' . mysqldate($b->getCorrectTime());
		}
		
		if ($mode != 'count') $query .= ' ORDER BY i.itime DESC';
		$query .= ' LIMIT '. $offset .','. $amount;
		
		if ($mode == 'count') {
			$amountfound = intval(quickQuery($query));
		}
		else {
			$amountfound = $b->showUsingQuery($this->params['tpl'], $query);
		}
		
		return $amountfound;
	}
	
	function _makeTagCloud($type, $extra=null) 
	{
		global $blog, $blogid, $catid, $CONF, $manager;
		
		$minfreq = ($this->params['freq']) ? $this->params['freq'] : 1;
		$amount  = ($this->params['amount']) ? $this->params['amount'] : 50;
		
		if($blog){
			$b =& $blog; 
		}else{
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$itime = mysqldate($b->getCorrectTime());
		
		//get data
		switch ($type) {
		case 'category':
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT inumber FROM %s WHERE icat=%d AND idraft=0 AND itime<%s)'.
				' GROUP BY tag HAVING amount>=%d ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('item'),
				$this->_quoteSmart($catid),
				$itime,
				$this->_quoteSmart($minfreq),
				($this->params['order']) ? $this->params['order'] : 'amount DESC',
				$this->_quoteSmart($amount)
				);
			break;
		case 'blog':
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT inumber FROM %s WHERE iblog=%d AND idraft=0 AND itime<%s)'.
				' GROUP BY tag HAVING amount>=%d ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('item'),
				$this->_quoteSmart($blogid),
				$itime,
				$this->_quoteSmart($minfreq),
				($this->params['order']) ? $this->params['order'] : 'amount DESC',
				$this->_quoteSmart($amount)
				);
			break;
		case 'searchable':
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT inumber FROM %s WHERE iblog IN (%s) AND idraft=0 AND itime<%s)'.
				' GROUP BY tag HAVING amount>=%d ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('item'),
				join(',', $this->searchables),
				$itime,
				$this->_quoteSmart($minfreq),
				($this->params['order']) ? $this->params['order'] : 'amount DESC',
				$this->_quoteSmart($amount)
				);
			break;
		case 'tag':
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT itemid FROM %s WHERE tag=%s) AND tag!=%s'.
				' GROUP BY tag HAVING amount>=%d ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('plug_simpletag'),
				$this->_quoteSmart($extra['tag']),
				$this->_quoteSmart($extra['tag']),
				$this->_quoteSmart($minfreq),
				($this->params['order']) ? $this->params['order'] : 'amount DESC',
				$this->_quoteSmart($amount)
				);
			break;
		case 'recent':
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT inumber FROM %s WHERE idraft=0 AND itime<%s)'.
				' GROUP BY tag ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('item'),
				$itime,
				($this->params['order']) ? $this->params['order'] : 'id DESC',
				$this->_quoteSmart($amount)
				);
			break;
		default: //all
			$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE'.
				' catid=0 AND itemid IN (SELECT inumber FROM %s WHERE idraft=0 AND itime<%s)'.
				' GROUP BY tag HAVING amount>=%d ORDER BY %s LIMIT %d',
				sql_table('plug_simpletag'),
				sql_table('item'),
				$itime,
				$this->_quoteSmart($minfreq),
				($this->params['order']) ? $this->params['order'] : 'amount DESC',
				$this->_quoteSmart($amount)
				);
			break;
		}
		
		$res = sql_query($query);
		
		//echo
		if (mysql_num_rows($res)) {
			echo $this->tpl['CLOUDHEADER'];
			
			$tags = array();
			$this->maxamount = 0;
			while ($data = mysql_fetch_assoc($res)) {
				$extra = array('tag' => $data['tag']); //エスケープかける前の文字列を渡す
				if ($this->params['special']) $extra['special'] = $this->params['special']; //スペシャルスキン情報をセット
				$flg_current = ($_REQUEST['tag'] == $data['tag']);
				$data['link'] = createBlogidLink($blogid, $extra);
				$data['tag'] = htmlspecialchars($data['tag']);
				$data['extraclass'] = '';
				if ($flg_current) $data['extraclass'] .= ' current';
				
				if ($data['amount'] > $this->maxamount) $this->maxamount = $data['amount']; //最大値を記録する
				$tags[] = TEMPLATE::fill($this->tpl['CLOUDBODY'], $data) ."\n";
			}
			mysql_free_result($res);
			
			$this->flg_initmax = true;
			if ($this->maxamount < 10) $this->maxamount += ((10 - $this->maxamount) / $this->maxamount) * 0.1; //補正係数
			foreach ($tags as $t) { //後からタグレベルを計算、置換する
				echo preg_replace_callback('/__([0-9]+?)__/', array(&$this, '_cbMakeTagClass'), $t);
			}
			
			echo $this->tpl['CLOUDFOOTER'];
		}
	}
	
	function _cbMakeTagClass($m) 
	{
		static $inc;
		
		$min = 1;
		if ($this->flg_initmax) {
			$inc = $this->maxamount / 10;
			$this->flg_initmax = false;
		}
		for ($i=9; $i>=0; $i--) {
			if ($m[1] > $inc * $i) return 'tag'.$i;
		}
	}
	
	function _getHints($tag) 
	{
		$hints = array();
		
		//前方一致のタグを探す
		$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE tag LIKE "%s%%" AND tag!=%s'
			.' GROUP BY tag ORDER BY amount DESC LIMIT 20',
			sql_table('plug_simpletag'),
			mysql_real_escape_string($tag), //no quote!
			$this->_quoteSmart($tag)
			);
		$res = sql_query($query);
		
		if (mysql_num_rows($res)) {
			while ($data = mysql_fetch_assoc($res)) {
				$hints[] = $data['tag'];
			}
			mysql_free_result($res);
		}
		
		//関連タグを探す
		$query = sprintf('SELECT tag, COUNT(tag) AS amount FROM %s WHERE catid=0 AND itemid IN (SELECT itemid FROM %s WHERE tag=%s)'.
			' AND tag!=%s GROUP BY tag ORDER BY amount DESC LIMIT 20',
			sql_table('plug_simpletag'),
			sql_table('plug_simpletag'),
			$this->_quoteSmart($tag),
			$this->_quoteSmart($tag)
			);
		$res = sql_query($query);
		
		if (mysql_num_rows($res)) {
			while ($data = mysql_fetch_assoc($res)) {
				$hints[] = $data['tag'];
			}
			mysql_free_result($res);
		}
		
		if (count($hints)) $hints = array_unique($hints);
		return $hints;
	}
	
	function _updateShadowTag($bid) 
	{
		//ブログに属するカテゴリー情報を取得する
		$query = sprintf('SELECT catid, cname FROM %s WHERE cblog=%d', 
			sql_table('category'),
			$this->_quoteSmart($bid)
			);
		$res = sql_query($query);
		if (! mysql_num_rows($res)) return;
		
		$catdata = array();
		while ($data = mysql_fetch_assoc($res)) {
			$catdata[$data['catid']] = $data['cname'];
		}
		
		//同名タグ情報を取得する
		$query = sprintf('SELECT DISTINCT catid, tag FROM %s WHERE catid>0', 
			sql_table('plug_simpletag')
			);
		$res = sql_query($query);
		if (! mysql_num_rows($res)) return;
		
		//同名タグが古ければ更新する
		while ($data = mysql_fetch_assoc($res)) {
			if ($catdata[$data['catid']] and $catdata[$data['catid']] != $data['tag']) {
				$query = sprintf('UPDATE %s SET tag=%s WHERE catid=%d',
					sql_table('plug_simpletag'),
					$this->_quoteSmart($catdata[$data['catid']]),
					$this->_quoteSmart($data['catid'])
					);
				sql_query($query);
			}
		}
	}
	
	function _outputScriptForInput() 
	{
		global $CONF;
		
		$delim = NP_SIMPLETAG_DELIM;
		$actionurl = $CONF['ActionURL'].'?action=plugin&name=SimpleTag&type='; //& (not &amp;)
		echo <<< EOH
<script type='text/javascript'>
//<!--

/*
	defined in edit.js
	------------------
	function getCaretText()
	var lastCaretPos;
	var lastSelected;

	change element id 'simpletag_tags' to 'inputtags' ('input' + nonie_FormType in edit.js)
*/

var simpletag_flgDebug = 0;
var simpletag_caretAt = new Array(0,0);
var simpletag_modLen = 0;
var simpletag_chktag = '';
var simpletag_xhr;

simpletag_xhr = createHTTPHandler();
document.getElementById('inputtags').onkeydown = simpletag_keymap;
document.getElementById('inputtags').onkeyup = simpletag_gethints;


function simpletag_input(str) {
	var input = document.getElementById('inputtags');
	
	if (input.value.slice(-1) != '{$delim}') input.value += '{$delim}'; /* add a delimiter automatically */
	input.value += str;
	input.focus();
}

function simpletag_gethints() {
	str = document.getElementById('inputtags').value;
	if (str.slice(-1) != '{$delim}') return; /* only works when last letter is a delimiter */
	
	tags = str.replace(/^\s+|\s+$/g, '').replace(/[{$delim}]$/g, '').split('{$delim}'); /* trim spaces and remove a last delimiter */
	chktag = tags[tags.length -1]; 
	if (chktag == '' || chktag == simpletag_chktag) return;
	
	document.getElementById('simpletag_hints').innerHTML = chktag +" - ";
	goalurl = '{$actionurl}js_gethints&sourcetag='+ encodeURI(chktag);
	simpletag_xhr.open('GET', goalurl, true);
	simpletag_xhr.onreadystatechange = simpletag_check_response;
	simpletag_xhr.send(null);
	simpletag_chktag = chktag;
}

function simpletag_check_response() {
	if (simpletag_xhr.readyState == 4) {
		if (simpletag_xhr.responseText) {
			goal = document.getElementById('simpletag_hints');
			if (simpletag_xhr.responseText.substr(0, 4) == 'err:') {
				goal.innerHTML += simpletag_xhr.responseText.substr(4);
			}
			else {
				goal.innerHTML += simpletag_xhr.responseText;
			}
		}
	}
}

function simpletag_gethints2() {
	simpletag_caretAt = simpletag_getCaretAt();
	var chktag = getCaretText();
	
	if (chktag == '' || chktag == simpletag_chktag) return;
	
	document.getElementById('simpletag_hints').innerHTML = chktag +" - ";
	goalurl = '{$actionurl}js_gethints&sourcetag='+ encodeURI(chktag);
	simpletag_xhr.open('GET', goalurl, true);
	simpletag_xhr.onreadystatechange = simpletag_check_response;
	simpletag_xhr.send(null);
	simpletag_chktag = chktag;
	
/*	var browser = (window.opera) ? 'op' : ( (document.all) ? 'ie' : ( (document.getElementById) ? 'moz' : '' ) );
	var textEl = lastSelected;
	var newText = 'hoge'; //test
	if (textEl && textEl.createTextRange && lastCaretPos) {
		var caretPos = lastCaretPos;
		caretPos.text = newText;
		simpletag_modLen += newText.length - seltext.length;
		//simpletag_trace("simpletag_modLen: "+ simpletag_modLen);
	}
	else if (browser == 'moz' || browser == 'op') {
		mozReplace(document.getElementById('inputtags'), newText);
		simpletag_modLen += newText.length - seltext.length;
		//simpletag_trace("simpletag_modLen: "+ simpletag_modLen);
	}*/
}

function simpletag_keymap(e) {
	var shift, alt, ctrl; 

	if (e != null) { // moz and op 
		keycode = e.which; 
		ctrl  = typeof e.modifiers == 'undefined' ? e.ctrlKey  : e.modifiers & Event.CONTROL_MASK; 
		alt   = typeof e.modifiers == 'undefined' ? e.altKey   : e.modifiers & Event.ALT_MASK; 
		shift = typeof e.modifiers == 'undefined' ? e.shiftKey : e.modifiers & Event.SHIFT_MASK; 
	}
	else { // ie
		keycode = event.keyCode; 
		ctrl  = event.ctrlKey; 
		alt   = event.altKey; 
		shift = event.shiftKey; 
	} 

	var k = keychar = '';
	var spCodeTbl = {
		8: 'BackSpace',
		9: 'Tab',
		13:'Enter',
		27:'Esc',
		32:'Space',
		33:'PageUp',
		34:'PageDown',
		35:'End',
		36:'Home',
		37:'Left',
		38:'Up',
		39:'Right',
		40:'Down',
		45:'Insert',
		46:'Delete'
		};
	if (! (keychar = spCodeTbl[''+keycode])) {
		keychar = String.fromCharCode(keycode).toUpperCase(); 
	}
	if (ctrl)  k += 'Ctrl+';
	if (alt)   k += 'Alt+';
	if (shift) k += 'Shift+';
	k += keychar;
	//simpletag_trace('key:'+k);
	switch (k) {
	case 'Ctrl+Alt+T':
		simpletag_keystop(e);
		simpletag_gethints2();
		//simpletag_restoreCaret('range');
		break;
	}
}

function simpletag_keystop(e) {
	if (e != null) {
		e.preventDefault();
		e.stopPropagation();
	} 
	else {
		event.returnValue = false;
		event.cancelBubble = true;
	}
}

function simpletag_getCaretAt() {
	var browser = (window.opera) ? 'op' : ( (document.all) ? 'ie' : ( (document.getElementById) ? 'moz' : '' ) );
	
	var txtarea = document.getElementById('inputtags');
	var startPos, endPos;
	if (browser == 'moz' || browser == 'op') {
		startPos = txtarea.selectionStart;
		endPos = txtarea.selectionEnd;
	}
	else {
		var docRange = document.selection.createRange();
		var textRange = document.body.createTextRange();
		textRange.moveToElementText(txtarea);
		
		var range = textRange.duplicate();
		range.setEndPoint('EndToStart', docRange);
		startPos = range.text.length;
		
		var range = textRange.duplicate();
		range.setEndPoint('EndToEnd', docRange);
		endPos = range.text.length;
	}
	return new Array(startPos, endPos);
}

function simpletag_restoreCaret(type) {
	var browser = (window.opera) ? 'op' : ( (document.all) ? 'ie' : ( (document.getElementById) ? 'moz' : '' ) );
	
	switch (type) {
	case 'top':
		simpletag_caretAt[1] = simpletag_caretAt[0];
		break;
	case 'range':
		if (flgSelectedRange) {
			simpletag_caretAt[1] += simpletag_modLen;
			if (simpletag_caretAt[0] > simpletag_caretAt[1]) simpletag_caretAt[0] = simpletag_caretAt[1];
		}
		else {
			simpletag_caretAt[1] = simpletag_caretAt[0];
		}
		break;
	}
	simpletag_modLen = 0; //clear
	
	var textEl = document.getElementById('inputtags');
	if (textEl && textEl.createTextRange && lastCaretPos) {
		var tRange = textEl.createTextRange();
		tRange.move("character", simpletag_caretAt[0]);
		tRange.moveEnd("character", simpletag_caretAt[1] - simpletag_caretAt[0]);
		tRange.select();
	}
	else if (browser == 'moz' || browser == 'op') {
		textEl.selectionStart = simpletag_caretAt[0];
		textEl.selectionEnd = simpletag_caretAt[1];
		textEl.focus();
	}
}

function simpletag_trace(logtxt) {
	if (! simpletag_flgDebug) return;
	if (window.console) console.log(logtxt);
	else alert(logtxt);
}

//-->
</script>
EOH;
	}
	
	function _setParams($params) 
	{
		$this->params = array(); //init
		
		foreach ($params as $param) {
			if ($flg_esc = (strpos($param, '\:') !== false)) $param = str_replace('\:', '[[ESCAPED-COLON]]', $param);
			list($key, $value) = explode(':', $param);
			if ($flg_esc) $value = str_replace('[[ESCAPED-COLON]]', ':', $value);
			
			$this->params[$key] = $value;
		}
	}
	
	function _checkContainer() 
	{
		static $flg_setparts;
		
		//get container parts (if exists)
		if (isset($this->container) and !$flg_setparts) {
			if ( is_array($cparts = $this->container->getParts($this->getName())) ) {
				foreach ($cparts as $ckey => $cval) {
					$this->tpl[$ckey] = $cval;
				}
			}
			$flg_setparts = true;
		}
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
		
		if (!$member->isLoggedIn() or !$this->_canEdit('admin')) {
			$oPluginAdmin->start();
			echo '<p class="error">' . _ERROR_DISALLOWED . '</p>';
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
-->
</style>
");
		
		echo "<h2>Simple Tag</h2>";
		
		$action = (requestVar('action')) ? requestVar('action') : 'index';
		$actions = array (
			'index',
			'update',
			'setshadow',
		);
		
		if (in_array($action, $actions)) { 
			if ($action != 'index' and !$manager->checkTicket()) {
				echo '<p class="error">Error: ' . _ERROR_BADTICKET . '</p>';
			} 
			else {
				switch ($action) {
				case 'setshadow': //既存のアイテムがある場合は、最初に同名タグをセットする必要あり。?action=setshadow
					echo '<p class="message">Set shadow tag (category - tag linkage) ... </p>';
					$this->_setShadowTag();
					echo '<p class="message">Done!</p>';
					break;
				case 'update':
					$msg = $this->_makeUpdate(
						requestVar('simpletag_action'),
						requestVar('simpletag_field1'),
						requestVar('simpletag_field2'),
						intRequestVar('simpletag_target')
						);
					echo '<p class="message">'. $msg .'</p>';
					$this->_makePage('index');
					break;
				default:
					$this->_makePage($action);
					break;
				}
			}
		} 
		else {
			echo '<p class="error">Error: Invalid action type.</p>';
		}
		$oPluginAdmin->end();
	}
	
	function _makePage($action='index') 
	{
		global $manager;
		
		$adminurl = $this->getAdminURL();
		
		switch ($action) {
		case 'index':
			$nextaction = 'update';
			echo <<< EOH
<form method="post" action="$adminurl">
<input type="hidden" name="action" value="$nextaction" />
EOH;
		$manager->addTicketHidden();
		echo <<<EOH
<fieldset>
<legend>タグの編集</legend>
タグ名:<input type="text" value="" name="simpletag_field1" size="16" />
<select name="simpletag_action">
<option value="update">を次に置換</option>
<option value="delete">を削除</option>
</select>
<input type="text" value="" name="simpletag_field2" size="16" />
対象ブログID:<input type="text" value="0" name="simpletag_target" size="3" /> ※0=全ブログ
</fieldset>

<input type="submit" value="更新する" name="simlpletag_submit" />
</form>
EOH;
			break;
		}
	}
	
	function _makeUpdate($action, $field1, $field2, $target) 
	{
		$msg = '';
		
		$tag = htmlspecialchars($field1);
		$target = (int)$target;
		if ($target > 0) $extra = 'AND itemid IN (SELECT inumber FROM '. sql_table('item') .' WHERE iblog='.$target.')';
		else $extra = '';
		
		switch ($action) {
		case 'delete':
			$query = sprintf("DELETE FROM %s WHERE tag=%s AND catid=0 %s",
				sql_table('plug_simpletag'),
				$this->_quoteSmart($field1),
				$extra
				);
			sql_query($query);
			
			$msg  = "タグ「{$tag}」を削除しました ";
			$msg .= '('. (int)mysql_affected_rows() .'件)';
			break;
		case 'update':
			if ($field2 == '') {
				$msg = "置換タグ名が入力されていません。";
				break;
			}
			$query = sprintf('UPDATE %s SET tag=%s WHERE tag=%s AND catid=0 %s',
				sql_table('plug_simpletag'),
				$this->_quoteSmart($field2),
				$this->_quoteSmart($field1),
				$extra
				);
			sql_query($query);
			
			$tag2 = htmlspecialchars($field2);
			$msg  = "タグ「{$tag}」を「{$tag2}」に置換しました ";
			$msg .= '('. (int)mysql_affected_rows() .'件)';
			break;
		default:
			$msg = 'ERROR: 不正なアクションです。';
			break;
		}
		
		return $msg;
	}
	
	function _setShadowTag() 
	{
		$query = sprintf('DELETE FROM %s WHERE catid>0',
			sql_table('plug_simpletag')
			);
		sql_query($query);
		
		//同名タグを一括で挿入する。INSERT ... SELECT なのでMySQLのバージョンを選ぶか
		$query = sprintf('INSERT INTO %s (tag, itemid, catid)'.
			' SELECT c.cname, i.inumber, i.icat FROM %s AS i, %s AS c WHERE i.icat=c.catid',
			sql_table('plug_simpletag'),
			sql_table('item'),
			sql_table('category')
			);
		sql_query($query);
	}
	
}
?>
