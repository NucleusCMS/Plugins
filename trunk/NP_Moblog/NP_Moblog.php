<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_Moblog ($Revision: 1.137 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_Moblog.php,v 1.137 2010/06/23 14:20:28 hsur Exp $
  *
  * Based on NP_HeelloWorld v0.8 
  * http://nakahara21.com/?itemid=133
*/

/*
  * Copyright (C) 2003 nakahara21 All rights reserved.
  * Copyright (C) 2004-2010 cles All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, mamio and cles gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

if (!class_exists('NucleusPlugin')) exit;
global $DIR_LIBS;
require_once($DIR_LIBS . 'MEDIA.php');

// クラスのロード
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('Net/POP3.php');
require_once('Mail/mimeDecode.php');
require_once('Mail/RFC822.php');

// バージョンチェック
$required = '4.3.0';
if( ! version_compare(phpversion() , $required , '>=') ){
	ACTIONLOG :: add(WARNING, 'NP_MoblogはPHP>=4.3.0であることが必要です。');
}

class NP_Moblog extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'Moblog';
	}

	// author of plugin
	function getAuthor() {
		return 'hsur';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/2';
	}

	// version of the plugin
	function getVersion() {
		return '1.17.1';
	}
	
	function hasAdminArea() {
		return 1;
	}
	
	function getEventList() {
		return array('PrePluginOptionsEdit');
	}
	
	function event_PrePluginOptionsEdit(&$data) {
		switch($data['context']){
			case 'member':
				// idandcat
				$m =& MEMBER :: createFromID($data['contextid']);
				$trimChar = array(
					'=' => '',
					'|' => '',
					';' => '',
				);
				
				$blogs = Array();
				$res = sql_query('SELECT bnumber, bname FROM '.sql_table('blog'));
				while( $o = mysql_fetch_object($res) ){
					 if( $m->isTeamMember($o->bnumber) ){
						$blogs[$o->bnumber] = $o->bname;
					 }
				}
				
				$idandcatTypeInfo = '';
				foreach($blogs as $blogid => $blogname){
					$res = sql_query('SELECT catid, cname FROM '.sql_table('category').' WHERE cblog='.$blogid);
					if( @mysql_num_rows($res) > 0) {
						while( $o = mysql_fetch_object($res) ){
							if($idandcatTypeInfo)
								$idandcatTypeInfo .= '|';
							$o->cname = strtr($o->cname, $trimChar);
							$blogname = strtr($blogname, $trimChar);
							$idandcatTypeInfo .= "{$o->cname} ({$blogname})|{$blogid},{$o->catid}";
						}
					}
				}
				if( ! $idandcatTypeInfo ){
					$idandcatTypeInfo = "!!投稿可能なblogがありません!!|0,0";
				}
				
				// collection & thumb_col
				$collections = MEDIA::getCollectionList();
				$mid = intval($m->getID());
				$collections[$mid] = 'デフォルト(useridディレクトリ)';
				
				$collectionTypeInfo = '';
				foreach( $collections as $collection => $name ){
					if($collectionTypeInfo) $collectionTypeInfo .= '|';
					$name = strtr($name, $trimChar);
					$collection = strtr($collection, $trimChar);
					$collectionTypeInfo .= "{$name}|{$collection}";
				}
				
				// set options
				foreach($data['options'] as $oid => $option ){
					switch($data['options'][$oid]['name']){
						case 'idandcat':
							$data['options'][$oid]['typeinfo'] = $idandcatTypeInfo;
							break;
						case 'collection':
						case 'thumb_col':
							$data['options'][$oid]['typeinfo'] = $collectionTypeInfo;
							break;
					}
				}
								
				break;
			default:
				// nothing	
		}
	}
	
	function install() {
		$this->createMemberOption('enable', 'プラグインを有効にするか？', 'yesno', 'no');

		$this->createMemberOption('host', 'POP3 ホスト名', 'text', 'localhost');
		$this->createMemberOption('port', 'POP3 ポート', 'text', '110', 'numerical=true');
		$this->createMemberOption('user', 'POP3 ユーザー名', 'text', '');
		$this->createMemberOption('pass', 'POP3 パスワード', 'password', '');
		$this->createMemberOption('useAPOP', 'APOPを使用するか？', 'yesno', 'no');

		$this->createMemberOption('idandcat', 'Nucleusカテゴリ(Blog)', 'select', '', '');

		$this->createMemberOption('collection', '画像を保存するディレクトリ', 'select', '', '');
		$this->createMemberOption('thumb_col', 'サムネイルをを保存するディレクトリ', 'select', '', '');

		$this->createMemberOption('imgonly', 'イメージ添付メールのみ追加？', 'yesno', 'no');
		$this->createMemberOption('DefaultPublish', 'デフォルトで公開するか？', 'yesno', 'no');

		$this->createMemberOption('optionsKeyword', 'オプション記述開始の区切り文字', 'text', '@');
		$this->createMemberOption('blogKeyword', 'オプションでblogidを指定する場合のキー', 'text', 'b');
		$this->createMemberOption('categoryKeyword', 'オプションでカテゴリを指定する場合のキー', 'text', 'c');
		$this->createMemberOption('publishKeyword', 'オプションでストレートにpublish指定する場合のキー', 'text', 's');

		$this->createMemberOption('moreDelimiter', '追記にする場合の区切り文字(利用しない場合は空欄)', 'text', '');

		$this->createMemberOption('accept', '投稿許可アドレス（複数の場合改行で区切ってください）', 'textarea', '');
		$this->createMemberOption('acceptSubjectPrefix','投稿許可SubjectPrefix(制限無しの場合は空欄)','text','');

		$this->createMemberOption('nosubject', '件名がないときの題名', 'text', '');
		$this->createMemberOption('no_strip_tags', 'htmlメールの場合に除去しないタグ', 'textarea', '<title><hr><h1><h2><h3><h4><h5><h6><div><p><pre><sup><ul><ol><br><dl><dt><table><caption><tr><li><dd><th><td><a><area><img><form><input><textarea><button><select><option>');
		$this->createMemberOption('maxbyte', '最大添付量(B)', 'text', '300000', 'numerical=true');
		$this->createMemberOption('subtype', '対応MIMEタイプ(正規表現)', 'text', 'gif|jpe?g|png|bmp|octet-stream|x-pmd|x-mld|x-mid|x-smd|x-smaf|x-mpeg|pdf');
		$this->createMemberOption('viri', '保存しないファイル(正規表現)', 'text', '.+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$');
		$this->createMemberOption('imgExt', '画像ファイルの拡張子(正規表現)', 'text', '.+\.png$|.+\.jpe?g$|.+\.gif$|.+\.bmp$');

		$this->createMemberOption('thumb_ok', 'サムネイルを使用する？', 'yesno', 'yes');
		$this->createMemberOption('W', 'サムネイルの大きさ(Width)', 'text', '120', 'numerical=true');
		$this->createMemberOption('H', 'サムネイルの大きさ(Hight)', 'text', '120', 'numerical=true');
		$this->createMemberOption('thumb_ext', 'サムネイルを作る対象画像', 'text', '.+\.jpe?g$|.+\.png$');
		$this->createMemberOption('smallW', 'アイテム内に表示する画像の最大横幅', 'text', '120', 'numerical=true');

		$this->createMemberOption('textTpl', 'テキストテンプレート', 'textarea', '<%body%>');
		$this->createMemberOption('withThumbTpl', 'サムネイル付きテンプレート', 'textarea', '<div class="leftbox"><a href="<%mediaUrl%><%imageUrl%>" target="_blank"><%image(<%thumbUrl%>|<%thumbW%>|<%thumbH%>|)%></a></div><%body%>');
		$this->createMemberOption('withoutThumbTpl', 'サムネイルなしテンプレート', 'textarea', '<div class="leftbox"><%image(<%imageUrl%>|<%sizeW%>|<%sizeH%>|)%></div><%body%>');
		$this->createMemberOption('reductionTpl', 'サムネイルなしテンプレート(縮小)', 'textarea', '<div class="leftbox"><a href="<%mediaUrl%><%imageUrl%>" target="_blank"><%image(<%imageUrl%>|<%reductionW%>|<%reductionH%>|)%></a></div><%body%>');
		$this->createMemberOption('dataTpl', 'データファイルテンプレート', 'textarea', '<div class="leftbox"><a href="<%mediaUrl%><%imageUrl%>" target="_blank"><%fileName%></a></div><%body%>');
		
		$this->createOption('execMode', '動作モード', 'select', '1', '振分対応モード|0|互換モード|1');
		$this->createOption('spamCheck', 'SPAMチェックを有効にする', 'yesno', 'no');
		
		$this->createOption('interval', 'メール取得の間隔(秒)', 'text', '600', 'numerical=true');
		$this->createOption('nextUpdate', '次回更新時刻(変更できません)', 'text', '-', 'access=readonly');
		$this->createOption('lastUpdate', '最終更新時刻(UnixTimeStamp,)', 'text', '0', 'access=hidden');
		$this->createOption('debug', 'ログを出力を行うか？', 'yesno', 'no');
	}
	
	function unInstall() {}
	function getMinNucleusVersion() { return 320; }
	function getMinNucleusPatchLevel() { return 0; }

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return '[$Revision: 1.137 $]<br />メールを拾ってアイテムを追加します。&lt;%Moblog%&gt;の記述のあるスキンを適用するページを開くと実行されます。<br />
				&lt;%Moblog(link)%&gt;と記入することでメールを取得するためのリンクを表示することができます（要ログイン）<br />
				個人ごとに設定ができるようになりましたので「あなたの設定」か「メンバー管理」から設定を行ってください。';
	}

	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
			case 'HelpPage':
				return 1;
			default :
				return 0;
		}
	}

	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'Moblog: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'Moblog: '.$msg);
	}

	function _getEnableUserId() {
		$userOptions = $this->getAllMemberOptions('enable');
		$userIds = Array ();
		foreach( $userOptions as $userId => $value ){
			if( $value == 'yes') $userIds[] = $userId;
		}
		return $userIds;
	}

	function _initMediaDirByUserId($userId) {
		global $DIR_MEDIA;

		$this->_info(__LINE__ . ": ユーザ($userId)の初期設定");
		$this->memid = $userId;

		/*-- 受信メールサーバーの設定--*/
		$this->host = $this->getMemberOption($userId, 'host');
		$this->port = $this->getMemberOption($userId, 'port');
		$this->user = $this->getMemberOption($userId, 'user');
		$this->pass = $this->getMemberOption($userId, 'pass');	

		// メールでアイテムを追加するblogのID
		$idandcat = $this->getMemberOption($userId, 'idandcat');
		list($this->blogid, $this->categoryNameOrId) = explode(",", $idandcat);
		
		$this->imgonly = ($this->getMemberOption($userId, 'imgonly') == 'yes') ? 1 : 0;
		$this->DefaultPublish = ($this->getMemberOption($userId, 'DefaultPublish') == 'yes') ? 1 : 0;

		/*-- メールのタイトルに各種オプションを含める場合の設定--*/
		$this->optionsKeyword = $this->getMemberOption($userId, 'optionsKeyword');
		$this->blogKeyword = $this->getMemberOption($userId, 'blogKeyword');
		$this->categoryKeyword = $this->getMemberOption($userId, 'categoryKeyword');
		$this->publishKeyword = $this->getMemberOption($userId, 'publishKeyword');

		// 投稿許可アドレス
		$this->accept = explode("\n", $this->getMemberOption($userId, 'accept'));
		$this->accept = Array_Map("Trim", $this->accept);
		$this->accept = Array_Map("strtolower", $this->accept);
		foreach( $this->accept as $mailAddr ){
			$this->_info(__LINE__ . "許可アドレス user:$userId, $mailAddr");
		}		

		// 投稿許可SubjectPrefix
		$this->acceptSubjectPrefix = Trim($this->getMemberOption($userId, 'acceptSubjectPrefix'));
		
		// 追記の区切り
		$this->moreDelimiter = Trim($this->getMemberOption($userId, 'moreDelimiter'));
		
		// 件名がないときの題名
		$this->nosubject = $this->getMemberOption($userId, 'nosubject');
		$this->no_strip_tags = $this->getMemberOption($userId, 'no_strip_tags');

		// 最大添付量（バイト・1ファイルにつき）※超えるものは保存しない
		$this->maxbyte = $this->getMemberOption($userId, 'maxbyte');
		$this->subtype = $this->getMemberOption($userId, 'subtype');
		$this->viri = $this->getMemberOption($userId, 'viri');
		$this->imgExt = $this->getMemberOption($userId, 'imgExt');

		// サムネイル
		$this->thumb_ok = ($this->getMemberOption($userId, 'thumb_ok') == 'yes') ? 1 : 0;
		$this->W = $this->getMemberOption($userId, 'W');
		$this->H = $this->getMemberOption($userId, 'H');
		$this->thumb_ext = $this->getMemberOption($userId, 'thumb_ext');
		$this->smallW = $this->getMemberOption($userId, 'smallW');
		
		// 画像保存ディレクトリ
		$collections = MEDIA::getCollectionList();
		$collection = $this->getMemberOption($userId, 'collection');
		if( $collection && isset($collections[$collection]) ){
			$this->collection = $collection;
		} else {
			$this->_info(__LINE__ . ": 画像保存ディレクトリが正しくありません。デフォルトを使用します");
			$this->collection = $this->memid;
		}
		
		$this->tmpdir = $DIR_MEDIA.$this->collection.'/';
		$this->_info(__LINE__ . ": 画像保存ディレクトリ: $this->tmpdir");
		
		if (!@is_dir($this->tmpdir)) {
			$this->_warn(__LINE__ . ": {$DIR_MEDIA}.{$this->collection} ディレクトリが存在しないので、ディレクトリを作成します。");
			$oldumask = umask(0000);
			if (!@mkdir($this->tmpdir, 0777))
				return $this->_warn(__LINE__ . ": 設定エラー: {$DIR_MEDIA}.{$this->collection} ディレクトリの作成に失敗しました。パーミッションを確認してください。");
			umask($oldumask);				
		} 

		if (!is_writable($this->tmpdir)) {
			$this->_warn(__LINE__ . ": 設定エラー: {$DIR_MEDIA}.{$this->collection} ディレクトリが存在しないか、書き込み可能になっていません");
		}
		
		// サムネイル保存ディレクトリ
		$thumb_collection = $this->getMemberOption($userId, 'thumb_col');
		if( $collection && isset($collections[$thumb_collection]) ){
			$this->thumb_collection = $thumb_collection;
		} else {
			$this->_info(__LINE__ . ": サムネイル画像保存ディレクトリが正しくありません。デフォルトを使用します");
			$this->thumb_collection = $this->memid;
		}

		$this->thumb_dir = $DIR_MEDIA.$this->thumb_collection.'/';
		$this->_info(__LINE__ . ": サムネイル画像保存ディレクトリ: $this->thumb_dir");
		
		if (!@is_dir($this->thumb_dir)) {
		$this->_warn(__LINE__ . ": {$DIR_MEDIA}.{$this->thumb_dir} ディレクトリが存在しないので、ディレクトリを作成します。");
			$oldumask = umask(0000);
			if (!@mkdir($this->thumb_dir, 0777))
				return $this->_warn(__LINE__ . ": 設定エラー: {$DIR_MEDIA}.{$this->thumb_dir} ディレクトリの作成に失敗しました。パーミッションを確認してください。");
			umask($oldumask);				
		} 
		
		if (!is_writable($this->thumb_dir)) {
			$this->_warn(__LINE__ . ": 設定エラー: {$DIR_MEDIA}.{$this->thumb_dir} ディレクトリが存在しないか、書き込み可能になっていません");
		}
	}

	function _convert($str, $input_encoding = false) {
		if( ! $input_encoding ){
			$input_encoding = "ISO-2022-JP,ASCII,JIS,UTF-8,EUC-JP,SJIS,ISO-2022-JP";
			$encoding = mb_detect_encoding($str, $input_encoding);
			if( ! $encoding )
				$input_encoding = "ISO-2022-JP";
		}
		return mb_convert_encoding($str, _CHARSET, $input_encoding);
	}
	
	function _addr_search($str) {
		if( PEAR::isError($addresses = Mail_RFC822::parseAddressList($str)) ){
			return false;
		}
		$addr = array_shift($addresses);
		if($addr)
			return $addr->mailbox . "@" .$addr->host;
		return false;
	}

	function _thumb_create($src, $W, $H, $thumb_dir = "./") {
			// 画像の幅と高さとタイプを取得
				$size = GetImageSize($src);
		switch ($size[2]) {
			case 1 :
				return false;
				break;
			case 2 :
				$im_in = @ImageCreateFromJPEG($src);
				break;
			case 3 :
				$im_in = @ImageCreateFromPNG($src);
				break;
		}
		if (!$im_in) {
			$this->_warn(__LINE__ . ": GDをサポートしていないか、ソースが見つかりません<br>phpinfo()でGDオプションを確認してください");
			return false;
		}
		// リサイズ
		if ($size[0] > $W || $size[1] > $H) {
			$key_w = $W / $size[0];
			$key_h = $H / $size[1];
			($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
			$out_w = $size[0] * $keys;
			$out_h = $size[1] * $keys;
		} else {
			$out_w = $size[0];
			$out_h = $size[1];
		}
		// 出力画像（サムネイル）のイメージを作成し、元画像をコピーします。(GD2.0用)
		$im_out = ImageCreateTrueColor($out_w, $out_h);
		$resize = ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);

		// サムネイル画像をブラウザに出力、保存
		$filename = substr($src, strrpos($src, "/") + 1);
		ImageJPEG($im_out, $thumb_dir.$this->_getThumbFileName($filename)); //jpgサムネイル作成
		// 作成したイメージを破棄
		ImageDestroy($im_in);
		ImageDestroy($im_out);
		
		$this->_info(__LINE__ . ": サムネイルを作成しました" . $thumb_dir.$this->_getThumbFileName($filename));
		return true;
	}
	
	function _getThumbFileName($filename){
		$filename = substr($filename, 0, strrpos($filename, "."));
		return $filename."-small.jpg";
	}

	function _getExecuteLink() {
		global $CONF;
		return $CONF['ActionURL'].'?action=plugin&amp;name=Moblog&amp;type=execute';
	}

	function _checkLastupdate() {
		$now = time();
		$lastUpdate = $this->getOption('lastUpdate');
		$interval = $this->getOption('interval');

		if ($lastUpdate + $interval < $now) {
			$this->setOption('lastUpdate', $now);
			$this->setOption('nextUpdate', date("Y-m-d H:i:s", $lastUpdate + $interval));
			$this->_info(__LINE__ . ": 更新します。");
			return true;
		}
		$this->_info(__LINE__ . ": 更新しません。次回更新は".date("Y-m-d H:i:s", $lastUpdate + $interval)."以降です。");
		return false;
	}

	function doSkinVar($skinType, $type = "") {
		global $member;
		switch ($type) {
			case '' :
			case 'execute' :
				if ( $this->_checkLastupdate() )
					$this->execute();
				break;

			case 'link' :
				if ( $member->isLoggedIn() )
					echo '<a href="'.$this->_getExecuteLink().'">Add Item by Mail</a>';
				break;
		}
	} //end of function doSkinVar($skinType)

	function doAction($type) {
		global $member;

		switch ($type) {
			case '' :
			case 'execute' :
				if (!$member->isLoggedIn())
					return "ログインが必要です";
				$this->execute();
				header('Location: ' . serverVar('HTTP_REFERER'));
				break;

			default :
				return 'アクションが定義されていません: '.$type;
		}
	}

	function execute() {
		$this->execMode = intval($this->getOption('execMode'));
		if( $this->execMode == 0 ){
			// false
			$this->_info(__LINE__ . ": 振分対応モードで動作します。");
		} elseif ( $this->execMode == 1 ) {
			// true
			$this->_info(__LINE__ . ": 互換モードで動作します");			
		}
		
		$enabledUserIds = $this->_getEnableUserId();
		foreach ($enabledUserIds as $userId) {
			$this->_info(__LINE__ . ": ユーザ($userId)のメールを取得開始します");
			$this->_initMediaDirByUserId($userId);
						
			// 接続
			$pop3 =& new Net_POP3();
			$pop3->_timeout = 10;
			
			if( ! $pop3->connect($this->host, $this->port) ){
				$this->_warn(__LINE__ . ": POPサーバーに接続できません");
				continue;
			}
			
			// 認証
			$authMethod = $this->getMemberOption($this->memid, 'useAPOP') == 'yes' ? 'APOP' : 'USER';
			$this->_info(__LINE__ . ": $authMethod で認証を行います");
	        if(PEAR::isError($ret =& $pop3->login($this->user , $this->pass , $authMethod)) ){		
				$this->_warn(__LINE__ . ": 認証に失敗しました:" . $ret->getMessage() );
				$pop3->disconnect();
				continue;
	        }
			
			// 件数確認
			$num = $pop3->numMsg();
			$this->_info(__LINE__ . ": $num 件のメールがあります");
			if ($num == "0") {
				$pop3->disconnect();
				continue;
			}
	
			// Msg取得
			for ($i = 1; $i <= $num; $i ++) {
				if(! $msg =& $pop3->getMsg($i) ){
					$this->_warn(__LINE__ . ": メールの取得に失敗しました。");
				}
				
				$result = $this->addItemByMail($userId, $msg);
				if( $result ){
					$this->_info(__LINE__ . ": メッセージを削除します");
					$pop3->deleteMsg($i);
				}
			}
			//切断
			$pop3->disconnect();
			$this->_info(__LINE__ . ": ユーザ($userId)のメールを取得終了しました");
		}
	}

	function addItemByMail($userId, $msg) {
		// メールデコード
		$params['include_bodies'] = TRUE;
		$params['decode_bodies']  = TRUE;
		$params['decode_headers'] = TRUE;
		$params['input'] = $msg;
        if(PEAR::isError( $decodedMsg = Mail_mimeDecode::decode($params)) ){		
			$this->_warn(__LINE__ . ": メールデコードに失敗しました:" . $decodedMsg->getMessage() );
			return true;
        }

		// From:
		if ( $decodedMsg->headers['from'] ) {
			$from = $this->_addr_search($decodedMsg->headers['from']);
		}
		if ( (! $from ) && $decodedMsg->headers['reply-to'] ) {
			$from = $this->_addr_search($decodedMsg->headers['reply-to']);
		}
		if ( (! $from ) && $decodedMsg->headers['return-path'] ) {
			$from = $this->_addr_search($decodedMsg->headers['return-path']);
		}
		if ( ! $from ){
			$this->_warn(__LINE__ . ": メールに送信者アドレスが見つかりません");
			return true;
		}
		$this->_info(__LINE__ . ": From($from)");

		// 投稿可能かチェック
		$from = strtolower(Trim($from));
		if ( in_array($from, $this->accept) ) {
			$this->_info(__LINE__ . ": 投稿許可アドレスに含まれているので受付($from)");
		} elseif( in_array( "*", $this->accept) ){
			$this->_info(__LINE__ . ": 投稿許可アドレスにワイルドカードが含まれているので受付($from)");
		}else {
			if( $this->execMode == 0 ){
				$this->_info(__LINE__ . ": 投稿許可アドレスに含まれていないので拒否($from)。振り分け対応モードなので他のアカウントで取得が行われる場合があります。");
			} else {
				$this->_warn(__LINE__ . ": 投稿許可アドレスに含まれていないので拒否($from)");
			}
			// 互換の場合は true
			// 振り分けの場合は false
			return $this->execMode;
		}

		// Date:
		$blog =& new BLOG($this->blogid);
		
		$timestamp = strtotime( trim($decodedMsg->headers['date']) );
		if ($timestamp == -1){
			$this->_info(__LINE__ . ": Dateヘッダからのtimestamp取得に失敗しました。");
			$timestamp = 0;	
		}
		$timestamp = $blog->getCorrectTime($timestamp);

		// Subject:
		$subject = $this->_convert($decodedMsg->headers['subject']);
		
		// Subject: prefixチェック				
		if ( $this->acceptSubjectPrefix ){
			$this->_info(__LINE__ . ": 投稿許可SubjectPrefixがあります(prefix: $this->acceptSubjectPrefix)");	
			$pos = mb_strpos($subject, $this->acceptSubjectPrefix);
			if( $pos === 0 ){
				// prefix切り取り
				$subject = mb_substr($subject, mb_strlen($this->acceptSubjectPrefix) );
				$this->_info(__LINE__ . ": 投稿許可SubjectPrefixをみつけました(prefix削除後subject: $subject)");				
			} else {
				$this->_warn(__LINE__ . ": 投稿許可SubjectPrefixがないので拒否します($subject)");
				return true;
			}
		}
		
		// subject: オプション分割
		if (preg_match('/'.$this->optionsKeyword.'/i', $subject)) {
			list ($subject, $option) = spliti($this->optionsKeyword, $subject, 2);

			$this->_info(__LINE__ . ": Subject($subject), Option($option)");
						
			$option = '&'.$option;
			if (preg_match('/&' . $this->blogKeyword . '=([^&=]+)/i', $option, $word)) {
				$this->blogid = $word[1];
				$this->_info(__LINE__ . ': blogidを' . $this->blogid . 'で上書きします');
			}
			if (preg_match('/&' . $this->categoryKeyword . '=([^&=]+)/i', $option, $word)) {
				$this->categoryNameOrId = $word[1];
				$this->_info(__LINE__ . ': Categoryを' . $this->categoryNameOrId . 'で上書きします');
			}
			if (preg_match('/&' . $this->publishKeyword . '=([^&=]+)/i', $option, $word)) {
				$this->DefaultPublish = $word[1];
				$this->_info(__LINE__ . ($this->DefaultPublish ? ': 投稿を公開に上書きします' : ': 投稿を下書きに上書きします'));
			}
		}
		
		// Subject: 空の場合
		if( ! $subject = trim(htmlspecialchars($subject, ENT_QUOTES)) ){
			$subject = $this->nosubject;
		}

		// body
		$text = "";	
		if( strtolower($decodedMsg->ctype_primary) == "text" ){
			$this->_info(__LINE__ . ": single partメッセージです");
			$text = $this->_textPart($decodedMsg);
		} elseif ( strtolower($decodedMsg->ctype_primary) == "multipart" ){
			$this->_info(__LINE__ . ": multipart partメッセージです");
			$texts = Array();
			$fileNames = Array();			
			$this->_decodeMultiPart($decodedMsg->parts, $texts, $fileNames);
			
			$text = $texts['plain'];
			if( $texts['html'] ) $text = $texts['html'];
		}
		
		if ($this->imgonly && (! $fileNames) ) {
			$this->_info(__LINE__ . ": 添付ファイルがないので書き込みません");
			return true;
		}

		if( $this->_isSpam($text) ){
			$this->_warn(__LINE__ . ": SPAMのため追加しません");
			return true;
		}
		
		$body = '';
		// body 生成
		if ( ! $fileNames ) {
			// 添付ファイルがない場合のbody			
			$vars = array (
				'body' => $text,
			);
			// textTpl
			$body .= TEMPLATE :: fill($this->getMemberOption($this->memid, 'textTpl'), $vars);
		} else {
			// 添付ファイルがある場合のbody
			$lastFile = array_pop($fileNames);
			
			foreach( $fileNames as $filename ){
				$body .= $this->_imageHtml($filename);
			}
			
			$body .= $this->_imageHtml($lastFile, $text);
		}
				
		// item追加
		$this->_info(__LINE__ . ": アイテム追加します");
		
		// bodyをbodyとmoreに分割
		$more = '';
		if( $this->moreDelimiter )
			list($body, $more) = spliti($this->moreDelimiter, $body, 2);
			
		$body = trim($body);
		$more = trim($more);
			
		$this->_addDatedItem($this->blogid, $subject, $body, $more, 0, $timestamp, 0, $this->categoryNameOrId);
		return true;
	}
	
	function _decodeMultiPart($parts, &$texts, &$fileNames){
		foreach($parts as $part){			switch ( strtolower( $part->ctype_primary )){
				// multipart
				case 'multipart':
					$this->_decodeMultiPart($part->parts, $texts, $fileNames);
					break;
				//text part
				case 'text':
					$this->_info(__LINE__ . ": text part をみつけました[{$part->ctype_primary}/{$part->ctype_secondary}]");
					$texts[$part->ctype_secondary] = $this->_textPart($part);
					break;
				// imagepart
				case 'image':
				default:
					$this->_info(__LINE__ . ": image/data part をみつけました[{$part->ctype_primary}/{$part->ctype_secondary}]");
					if( $fileName = $this->_imagePart($part) )
						$fileNames[] = $fileName;
					break;
			}
		}
	}

	function _textPart(&$part){
		$encoding = false;
		if( $part->ctype_parameters )
			$encoding = $this->ctype_parameters['charset'];
		
		$text = $this->_convert($part->body, $encoding);
		$text = strip_tags($text, $this->no_strip_tags);
		
		$blog =& new BLOG($this->blogid);
		//blog設定で改行を<br />に置換onの場合
		if ($blog->getSetting('bconvertbreaks')) { 
			if ( strtolower($part->ctype_secondary) == 'html' ) {
				//改行文字を削除、<br>タグを\nへ
				$text = str_replace("\r\n", "\r", $text);
				$text = str_replace("\r", "\n", $text);
				$text = str_replace("\n", "", $text);
				$text = str_replace("<br>", "\n", $text);
			}
		}
		return $text;
	}
	
	function _imagePart(&$part){
		if( !$this->prefixDate ){
			$this->prefixDate  = date('YmdHis');
			$this->fileCount = 0;
		} else {
			$this->fileCount += 1;
		}
		$this->filePrefix = $this->prefixDate . sprintf('%02d', $this->fileCount);
		
		$filename = "";
		if( $part->d_parameters ){
			$filename = $part->d_parameters['filename'];
		} elseif( $part->ctype_parameters ){
			$filename = $part->ctype_parameters['name'];
		} else {
			$filename = $part->ctype_secondary;
		}

		$filename = $this->_convert($filename);
		$filename = $this->filePrefix . "-" . $filename;		
		$this->_info(__LINE__ . ": FileName($filename)");
		
		// subtypeチェック
		$size = strlen($part->body);
		if( preg_match("/".$this->subtype."/i", trim($part->ctype_secondary) )){
			// サイズ、拡張子チェック
			if ($size < $this->maxbyte && !preg_match("/".$this->viri.'/i', $filename)) {
								
				$fp = fopen($this->tmpdir.$filename, "w");
				fputs($fp, $part->body);
				fclose($fp);
					
				$size = @getimagesize($this->tmpdir.$filename);
				//サムネイル作成する場合
				if ($this->thumb_ok && function_exists('ImageCreate')) {
					//サムネイル作成する拡張子の場合
					if ( preg_match("/$this->thumb_ext/i", $filename) ) {
						if ($size[0] > $this->W || $size[1] > $this->H) {
							$this->_thumb_create($this->tmpdir.$filename, $this->W, $this->H, $this->thumb_dir);
						}
					}
				}
				return $filename;
			}
			$this->_warn(__LINE__ . ": 添付ファイルを無視します。(サイズ超過: $size B or 保存しないファイルに該当しています) [$part->ctype_primary/$part->ctype_secondary]");
			return false;
		}
		$this->_warn(__LINE__ . ": 添付ファイルを無視します。(subtypeチェック: $part->ctype_secondary が対応MIMEタイプに入っていますか？) [$part->ctype_primary/$part->ctype_secondary]");
		return false;	
	}
	
	function _imageHtml($filename, $body = ""){
		global $CONF;
		
		$size = @getimagesize($this->tmpdir.$filename);
		$thumb_size = @getimagesize($this->thumb_dir.$this->_getThumbFileName($filename));
		$smallH = round($this->smallW / $size[0] * $size[1], 0);

		$vars = array (
			'thumbW' => $thumb_size[0],
			'thumbH' => $thumb_size[1],
			'reductionW' => $this->smallW, 
			'reductionH' => $smallH,
			'sizeW' => $size[0],
			'sizeH' => $size[1],
			'body' => $body,
			'thumbUrl' => $this->thumb_collection.'/' . urlencode($this->_getThumbFileName($filename)),
			'imageUrl' => $this->collection.'/' . urlencode($filename),
			'mediaUrl' => $CONF['MediaURL'],
			'fileName' => $filename
		);
		
		// データファイルチェック
		if( ! preg_match("/$this->imgExt/i", $filename) ){
			$this->_info(__LINE__ . ": 画像ファイルに該当しないので、データファイルテンプレートを使用します");
			return TEMPLATE :: fill($this->getMemberOption($this->memid, 'dataTpl'), $vars);
		}

		if ( $thumb_size[0] ) { //サムネイルがある場合のソース
			$this->_info(__LINE__ . ": サムネイルがあります");
			return TEMPLATE :: fill($this->getMemberOption($this->memid, 'withThumbTpl'), $vars);
		} else { //サムネイルがない場合のソース
			if ($size[0] > $this->smallW) { //縮小表示
				$this->_info(__LINE__ . ": サムネイルがありません、縮小表示します");
				return TEMPLATE :: fill($this->getMemberOption($this->memid, 'reductionTpl'), $vars);
			} else { //そのまま表示
				$this->_info(__LINE__ . ": サムネイルがありません");
				return TEMPLATE :: fill($this->getMemberOption($this->memid, 'withoutThumbTpl'), $vars);
			}
		}
	}
	
	function _addDatedItem($blogid, $title, $body, $more, $closed, $timestamp, $future, $catNameOrId = "") {
		// 1. ログイン======================
		$mem = MEMBER :: createFromID($this->memid);

		// 2. ブログ追加できるかチェック======================
		if (!BLOG :: existsID($this->blogid)) {
			$this->_info(__LINE__ . ": 存在しないblogです");
			return false;
		}
		$this->_info(__LINE__ . ": blogidはOK!");

		if (!$mem->isTeamMember($blogid)) {
			$this->_warn(__LINE__ . ": メンバーではありません");
			return false;
		}
		$this->_info(__LINE__ . ": メンバーチェックもok!");
		
		if (!trim($body)) {
			$this->_warn(__LINE__ . ": 空のアイテムは追加できません");
			return false;
		}
		$this->_info(__LINE__ . ": アイテムは空じゃないです");

		// 3. 値の補完
		$blog =& new BLOG($this->blogid);
		if( $blog->isValidCategory($catNameOrId) ){
			// カテゴリIDとして有効なときはそのまま使う
			$catid = $catNameOrId;
		} else {
			// カテゴリID ゲット (誤ったカテゴリID使用時はデフォを使用)
			$catid = $blog->getCategoryIdFromName($catNameOrId);
		}
		
		$this->_info(__LINE__ . ": 追加するcatid: ".$catid);
		if ($this->DefaultPublish) {
			$draft = 0;
		} else {
			$draft = 1; //ドラフト追加
			$this->_info(__LINE__ . ": ドラフトで追加します");
		}
		if ($closed != 1)
			$closed = 0; //コメントを許可
		$this->_info(__LINE__ . ": \$catid:".$catid.", \$draft:".$draft.", \$closed:".$closed);

		// 4. blogに追加
		$itemid = $blog->additem($catid, $title, $body, $more, $blogid, $mem->getID(), $timestamp, $closed, $draft);
		
		$this->_info(__LINE__ . ": itemid: $itemid");
		return $itemid;
	}
	
	function _isSpam($str){
		global $manager;
		if( $this->getOption('spamCheck') == 'yes' ){	
			$spamcheck = array (
				'type'  	=> 'Moblog',
				'data'  	=> $str,
				'return'	=> true,
				'ipblock'   => false
			);
			$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
			if (isset($spamcheck['result']) && $spamcheck['result'] == true)
				return true;
		}
		return false;
	}
}
