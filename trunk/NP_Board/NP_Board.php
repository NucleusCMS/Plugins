<?
if (!function_exists('sql_table'))
{
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Board extends NucleusPlugin {

	function getName() {return 'Board'; }
	function getAuthor()  { return 'nakahara21'; }
	function getURL() { return 'http://xx.nakahara21.net/'; }
	function getVersion() { return '0.31'; }
	function getDescription() { 
		return 'BBS!';
	}
	function getTableList() { return array( sql_table('plugin_bbs') ); }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
function install() {
	$this->createOption("bbstitle", "Title of BBS", "text", "何でもBBS");
	$this->createOption("bbsdesc", "説明文", "text", "何でもご自由に<br />お書き下さいね！");
	$this->createOption("bbsamount", "1ページに表示する記事数", "text", "15");
//	$this->createOption("name2", "desc2", "yesno", "yes");
	$this->createOption("smily", "Convert smilies?", "yesno", "no");
	$this->createOption("adminpass", "BBS admin password", "password", "password");
//	$this->createOption("name4", "desc4", "textarea", "");
//	$this->createOption("name5", "desc5", "select", "val1", "Opt1|val1|Opt2|val2|Opt3|val3");
	$this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");

	$query = "CREATE TABLE IF NOT EXISTS ".sql_table('plugin_bbs');
	$query .= "( bbsid int(11) NOT NULL auto_increment,";
	$query .= " bbs_number int(11) NOT NULL default '0',";
	$query .= " bbs_baseid VARCHAR(11) NOT NULL default 'B',";
	$query .= " bbs_name varchar(50) default NULL,";
	$query .= " bbs_userinfo varchar(100) default NULL,";
	$query .= " bbs_pass varchar(50) NOT NULL default '',";
	$query .= " bbs_mes text default NULL,";
	$query .= " bbs_host varchar(60) default NULL,";
	$query .= " bbs_ip varchar(15) NOT NULL default '',";
	$query .= " bbs_date datetime NOT NULL default '0000-00-00 00:00:00',";
	$query .= " PRIMARY KEY (bbsid))";
	mysql_query($query);	
	}

function uninstall() {
	if ($this->getOption('del_uninstall') == "yes") {
		mysql_query ("DROP table ".sql_table('plugin_bbs'));
	}
}

	function init() {
		$this->pageamount = $this->getOption('bbsamount');
		$this->startpos = 0;

		//禁止name
		//削除されたログに適用するnameとなる
		$this->deny_name = "-";

		$smileys0 = array(
			':mrgreen:' => 'smiles/icon_mrgreen.gif',			
			':wink:' => 'smiles/icon_wink.gif',			
			':lol:' => 'smiles/icon_lol.gif',			
			':oops:' => 'smiles/icon_redface.gif',			
			':cry:' => 'smiles/icon_cry.gif',			
			':roll:' => 'smiles/icon_rolleyes.gif',			
			'8)' => 'smiles/icon_cool.gif',			
			':?:' => 'smiles/icon_question.gif',			
		);
		$smileys = array(
			':-D' => 'smiles/icon_biggrin.gif',						
			':P' => 'smiles/icon_razz.gif',			
			':-)' => 'smiles/icon_smile.gif',
			':o' => 'smiles/icon_surprised.gif',						
			';-)' => 'smiles/icon_wink.gif',			
			':|' => 'smiles/icon_neutral.gif',						
			':?' => 'smiles/icon_confused.gif',			
			':-(' => 'smiles/icon_sad.gif',
			'8O' => 'smiles/icon_eek.gif',			
			':idea:' => 'smiles/icon_idea.gif',			
			':arrow:' => 'smiles/icon_arrow.gif',			
			':!:' => 'smiles/icon_exclaim.gif',			
			':x' => 'smiles/icon_mad.gif',			
		);
		$smileys21 = array(
			':D' => 'smiles21/biglaugh.gif',
			':r' => 'smiles21/rolleyes.gif',
			':)' => 'smiles21/smile.gif',
			';)' => 'smiles21/wink.gif',			
			':(' => 'smiles21/frown.gif',
			':n' => 'smiles21/eek.gif',			
			':O' => 'smiles21/redface.gif',			
		);

		$this->smiley = array_merge($smileys, $smileys21);
	}

	function doSkinVar($skinType, $bbs_number = '0', $show = '') {
		global $CONF, $manager, $member;

		$temp_params[1] = $bbs_number;
		$temp_params[2] = $show;
		if(!is_numeric($temp_params[1])){	$show = $temp_params[1];	}
		if( is_numeric($temp_params[2])){	$bbs_number = intval($temp_params[2]);	}
		if(!is_numeric($bbs_number) || (!$temp_params[1] && !$temp_params[1]) ){ $bbs_number = '0';}
		$data['bbs_number'] = $bbs_number;
		$data['actionurl'] = $CONF['ActionURL'];

		if($show == 'last'){
			$sql = "SELECT * FROM ".sql_table('plugin_bbs');
			$sql .= " WHERE bbs_number=".$bbs_number;
			$sql .= ' ORDER BY bbsid DESC';
			$sql .= ' LIMIT 1';
			$res = mysql_query($sql);
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				echo $row["bbs_name"].'<br />';
				echo " (".date("Y-m-d H:i", strtotime($row["bbs_date"])).")<br />";
//				echo shorten(strip_tags($row["bbs_mes"]),40,'...');
				echo $this->bbsBody(shorten(strip_tags($row["bbs_mes"]),40,'...'));
			}
			return;
		}

//======================================
//uri分解
//======================================
		$uri = sprintf("%s%s%s","http://",$_SERVER["HTTP_HOST"],$_SERVER["REQUEST_URI"]);
	
		$bbs_uri = $uri;
		$bbs_uri = parse_url($bbs_uri);
		parse_str($bbs_uri['query'],$bbs_uri_query);

		$this->currentpage = $bbs_uri_query['bbsp'];
		if(intval($this->currentpage)>0){
			$this->startpos = (intval($this->currentpage)-1) * $this->pageamount;
		}else{
			$this->currentpage = 1;
		}

		$bbs_uri['query'] = '';
		foreach($bbs_uri_query as $q_key => $q_value){
			if(!ereg('mode|bbsid|bbsp|bbsq', $q_key)){
				$bbs_uri['query'][] = $q_key.'='.$q_value;
			}
		}



		$bbs_uri['query'] = @join("&",$bbs_uri['query']);
		if($bbs_uri['query']){
			$bbs_uri = 'http://' . $bbs_uri['host'].$bbs_uri['path'].'?'.$bbs_uri['query'];
			$this->pagelink = $bbs_uri.'&';
		}else{
			$bbs_uri = 'http://' . $bbs_uri['host'].$bbs_uri['path'];
			$this->pagelink = $bbs_uri.'?';
		}
	

//======================================
//データ初期化
//======================================
		$data['redirectTo'] = $bbs_uri;
		$data['pagelink'] = $this->pagelink;;
		$data['bbs_mes'] = '';
		$data['type'] = 'add_mess';
		$data['extrainput'] = '';
		$data['submit'] = '送信';
		if ($this->getOption('smily') == "yes") {
			$data['smilies'] = $this->insertSmilies();
		}else{
			$data['smilies'] = '';
		}

//======================================
//クッキー読みとり (データをセット)
//======================================
		if($member->isLoggedIn()){
			$data['bbs_name'] = $member->getDisplayName();
			$data['bbs_userinfo'] = $member->geturl();
		}else{
			if(cookieVar('comment_user')){
				$data['bbs_name'] = htmlspecialchars(cookieVar('comment_user'));
			}else{
				$data['bbs_name'] = '';
			}
			if(cookieVar('comment_userid')){
				$data['bbs_userinfo'] = htmlspecialchars(cookieVar('comment_userid'));
			}else{
				$data['bbs_userinfo'] = '';
			}
		}
		if(cookieVar('bbs_pass')){
			$data['bbs_pass'] = htmlspecialchars(cookieVar('bbs_pass'));
		}else{
			$data['bbs_pass'] = '';
		}
		if(cookieVar('comment_user')){
			$data['check'] = '<input type="checkbox" value="1" name="remember" checked="checked" />cookie ';
		}else{
			$data['check'] = '<input type="checkbox" value="1" name="remember" />cookie ';
		}

//======================================
//検索結果表示 (記事表示で処理中止)
//======================================
		if(getVar('bbsq')){
			echo $this->getBBSTemplate(bbs_css);
			echo '<h1>Search Results</h1>';
			$this->bHighlight = explode(" ", getVar('bbsq'));

			$sql = "SELECT * FROM ".sql_table('plugin_bbs');
			$sql .= " WHERE bbs_number=".$bbs_number;
			foreach($this->bHighlight as $s){
//				$sql .= " and ((bbs_name LIKE '%" . addslashes($s) . "%') or (bbs_mes LIKE '%" . addslashes($s) . "%') or (bbs_host LIKE '%" . addslashes($s) . "%'))";
				$sql .= " and ((bbs_mes LIKE '%" . addslashes($s) . "%'))";
			}
			$sql .= " ORDER BY bbsid DESC";
			$res = mysql_query($sql);
			
			$num_rows = mysql_num_rows($res); 

			if(cookieVar('comment_user')){
				$data['extrainput'] = '<input type="hidden" value="2" name="remember" />';
			}else{
				$data['extrainput'] = '<input type="hidden" value="0" name="remember" />';
			}
			$data['bbsq'] = getVar('bbsq');
		echo TEMPLATE::fill($this->getBBSTemplate(bbs_search),$data);

			$pageswitch = $this->parse_pageswitch($bbs_number, $num_rows, urlencode(getVar('bbsq')));
			echo $pageswitch;

			$sql .= ' LIMIT ' . $this->startpos .',' . $this->pageamount;

			$res = mysql_query($sql);
			
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$data = $row;
				if (isValidMailAddress($row["bbs_userinfo"]))
					$data["bbs_userinfo"]  = '<a href="mailto:'.$row["bbs_userinfo"].'">M</a>';
				elseif (strstr($row["bbs_userinfo"],'http://') != false)  
					$data["bbs_userinfo"]  = '<a href="'.$row["bbs_userinfo"].'">W</a>';
				else
					$data["bbs_userinfo"] = '';

				$data['bbs_date'] = date("Y-m-d H:i", strtotime($row["bbs_date"]));

				if($row["bbs_name"]== $this->deny_name){
					$data["bbs_mes"] .= 'に削除されました';
				}

				$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbsid"];
				$data['current_bbsid'] = mysql_result(mysql_query($query), 0, ct);

				$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbs_baseid"];
				$data['baseid'] = mysql_result(mysql_query($query), 0, ct);

				$data['editurl'] = $this->pagelink.'mode=edit&bbsid='.$row["bbsid"];
				$data['resurl'] = $this->pagelink.'mode=res&bbsid='.$row["bbsid"];
				$data['permalinkurl'] = $this->pagelink.'bbsid='.$row["bbsid"];
				$data['bbs_mes'] = $this->bbshighlight($row["bbs_mes"]);
				$data['bbs_mes'] = $this->bbsBody($data['bbs_mes']);
				if($data['baseid'] > 0){
					$data['bbs_mes'] = '[記事No.'.$data['baseid'].' への返信]<br />'.$data['bbs_mes'];
				}
				echo TEMPLATE::fill($this->getBBSTemplate(bbslog),$data);
			}

			echo $pageswitch;

			return;
		}


//======================================
//parmalink表示 (記事1つ表示で処理中止)
//======================================
		if(!postVar('mode') && !getVar('mode') && intGetVar('bbsid')){
			echo '<h1>permalink</h1>';

			echo $this->getBBSTemplate(bbs_css);
			$sql = "SELECT * FROM ".sql_table('plugin_bbs');
			$sql .= " WHERE bbs_number=".$bbs_number;
			$sql .= " AND bbsid=".intGetVar('bbsid');
			$sql .= " ORDER BY bbsid DESC";
			$sql .= ' LIMIT 1';

			$res = mysql_query($sql);

			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$data = $row;
				if (isValidMailAddress($row["bbs_userinfo"]))
					$data["bbs_userinfo"]  = '<a href="mailto:'.$row["bbs_userinfo"].'">M</a>';
				elseif (strstr($row["bbs_userinfo"],'http://') != false)  
					$data["bbs_userinfo"]  = '<a href="'.$row["bbs_userinfo"].'">W</a>';
				else
					$data["bbs_userinfo"] = '';

				$data['bbs_date'] = date("Y-m-d H:i", strtotime($row["bbs_date"]));

				if($row["bbs_name"]== $this->deny_name){
					$data["bbs_mes"] .= 'に削除されました';
				}

				$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbsid"];
				$data['current_bbsid'] = mysql_result(mysql_query($query), 0, ct);

				$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbs_baseid"];
				$data['baseid'] = mysql_result(mysql_query($query), 0, ct);

				$data['editurl'] = $this->pagelink.'mode=edit&bbsid='.$row["bbsid"];
				$data['resurl'] = $this->pagelink.'mode=res&bbsid='.$row["bbsid"];
				$data['permalinkurl'] = $this->pagelink.'bbsid='.$row["bbsid"];
				$data['bbs_mes'] = $this->bbsBody($row["bbs_mes"]);
				if($data['baseid'] > 0){
					$data['bbs_mes'] = '[記事No.'.$data['baseid'].' への返信]<br />'.$data['bbs_mes'];
				}
				echo TEMPLATE::fill($this->getBBSTemplate(bbslog),$data);
			}
			return;
		}

//======================================
//記事の編集、削除画面表示 (フォーム表示だけで処理中止)
//======================================
		if(getVar('mode') == 'edit' && intGetVar('bbsid')){
			$data['bbsid'] = intGetVar('bbsid');
			$data['type'] = 'update';
			$data['extrainput'] = '<input type="hidden" name="bbsid" value="'.$data['bbsid'].'" />';
			if(cookieVar('comment_user')){
				$data['extrainput'] .= '<input type="hidden" value="2" name="remember" />';
			}else{
				$data['extrainput'] .= '<input type="hidden" value="0" name="remember" />';
			}
			$data['check'] = '*cookie will NOT be updated';
			$data['submit'] = '編集内容を反映';

			echo '記事の編集';
			$check_pass = addslashes(md5(postVar('bbs_pass')));

			$sql = "SELECT * FROM ".sql_table('plugin_bbs');
			$sql .= ' WHERE bbsid='.$data['bbsid'];
			$sql .= ' AND bbs_number='.$bbs_number;
			$sql .= ' LIMIT 1';
			$res = mysql_query($sql);

			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				if(postVar('bbs_pass') && $check_pass == $row["bbs_pass"]){
					$data['bbs_name'] = $row["bbs_name"];
					$data['bbs_userinfo'] = $row["bbs_userinfo"];
					$data['bbs_mes'] = $row["bbs_mes"];

					$data['bbs_pass'] = postVar('bbs_pass');
				}else{
					echo "パスワードが違います。";
					return;
				}
			}

			echo $this->getBBSTemplate(bbs_css);
			$data['DELETE'] = TEMPLATE::fill($this->getBBSTemplate(bbs_delete),$data);
			echo TEMPLATE::fill($this->getBBSTemplate(bbsform),$data);
			return;
		}

//======================================
//引用して返信 (メッセージエリアに表示するデータを追加)
//======================================
		if(getVar('mode') == 'res' && intGetVar('bbsid')){
			
			$data['bbs_baseid'] = intGetVar('bbsid');
			$data['extrainput'] = '<input type="hidden" name="bbs_baseid" value="'.$data['bbs_baseid'].'" />';
			
			$sql = "SELECT * FROM ".sql_table('plugin_bbs');
			$sql .= ' WHERE bbsid='.$data['bbs_baseid'];
			$sql .= ' AND bbs_number='.$bbs_number;
			$sql .= ' LIMIT 1';
			$res = mysql_query($sql);

			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbsid"];
				$data['current_bbsid'] = mysql_result(mysql_query($query), 0, ct);
				
//				$quote_char = '*'.$row["bbs_number"]."-".$data['current_bbsid']."*&gt;";
				$quote_char = "&gt;";
				$data['bbs_mes'] = strip_tags($row["bbs_mes"]);
				$data['bbs_mes'] = $this->indent($data['bbs_mes'],70,$quote_char);
			}
		}

//======================================
//通常表示 (新規投稿フォームと記事一覧)
//======================================
?>
<script language="JavaScript" type="text/javascript">
<!--
function paste_strinL(strinL){ 
var input=document.forms["postMsg"].elements["bbs_mes"];
input.value=input.value+strinL; 
}

//-->
</script>
<?php
		echo $this->getBBSTemplate(bbs_css);

		echo '<h1>'.$this->getOption('bbstitle').'</h1>';
		echo '<h2>'.$this->getOption('bbsdesc').'</h2>';

		echo TEMPLATE::fill($this->getBBSTemplate(bbsform),$data);

		$sql = "SELECT * FROM ".sql_table('plugin_bbs');
		$sql .= " WHERE bbs_number=".$bbs_number;
		$sql .= " ORDER BY bbsid DESC";
			$res = mysql_query($sql);
			
			$num_rows = mysql_num_rows($res); 
			if(cookieVar('comment_user')){
				$data['extrainput'] = '<input type="hidden" value="2" name="remember" />';
			}else{
				$data['extrainput'] = '<input type="hidden" value="0" name="remember" />';
			}
		echo TEMPLATE::fill($this->getBBSTemplate(bbs_search),$data);
				$data['extrainput'] = '';

			$pageswitch = $this->parse_pageswitch($bbs_number, $num_rows, '');
			echo $pageswitch;

		$sql .= ' LIMIT ' . $this->startpos .',' . $this->pageamount;

		$res = mysql_query($sql);

		while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$data = $row;
			if (isValidMailAddress($row["bbs_userinfo"]))
				$data["bbs_userinfo"]  = '<a href="mailto:'.$row["bbs_userinfo"].'">M</a>';
			elseif (strstr($row["bbs_userinfo"],'http://') != false)  
				$data["bbs_userinfo"]  = '<a href="'.$row["bbs_userinfo"].'">W</a>';
			else
				$data["bbs_userinfo"] = '';

			$data['bbs_date'] = date("Y-m-d H:i", strtotime($row["bbs_date"]));

			if($row["bbs_name"]== $this->deny_name){
				$data["bbs_mes"] .= 'に削除されました';
			}

			
			$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbsid"];
			$data['current_bbsid'] = mysql_result(mysql_query($query), 0, ct);

			$query = "SELECT count(*) as ct FROM ".sql_table('plugin_bbs')." WHERE bbs_number=".$bbs_number." AND bbsid<=".$row["bbs_baseid"];
			$data['baseid'] = mysql_result(mysql_query($query), 0, ct);

			$data['editurl'] = $this->pagelink.'mode=edit&bbsid='.$row["bbsid"];
			$data['resurl'] = $this->pagelink.'mode=res&bbsid='.$row["bbsid"];
			$data['permalinkurl'] = $this->pagelink.'bbsid='.$row["bbsid"];
			$data['bbs_mes'] = $this->bbsBody($row["bbs_mes"]);
			if($data['baseid'] > 0){
				$data['bbs_mes'] = '[記事No.'.$data['baseid'].' への返信]<br />'.$data['bbs_mes'];
			}
			echo TEMPLATE::fill($this->getBBSTemplate(bbslog),$data);
		}

			echo $pageswitch;

	} //end of function doSkinVar


	function doAction($type) {
		global $manager, $CONF;
		$blog =& $manager->getBlog($CONF['DefaultBlog']);

		// フォームからデータを受け取る
		$bbs_number = intval(postVar('bbs_number'));
		$data['bbs_baseid'] = intval(postVar('bbs_baseid'));
		$data['bbs_name'] = addslashes(strip_tags(postVar('bbs_name')));
		if($data['bbs_name'] == $this->deny_name){
			$data['bbs_name'] = '';
		}
	
		$data['bbs_userinfo'] = strip_tags(postVar('bbs_userinfo'));
		$data['bbs_pass'] = addslashes(md5(postVar('bbs_pass')));
		$data['bbs_mes'] = trim(postVar('bbs_mes'));
		$data['bbs_mes'] = addslashes(strip_tags($data['bbs_mes']));
		$data['bbs_host'] = gethostbyaddr(serverVar('REMOTE_ADDR'));
		$data['bbs_ip'] = serverVar('REMOTE_ADDR');
		$data['bbs_date'] = date('Y-m-d H:i:s', $blog->getCorrectTime());
		$data['redirectTo'] = postVar('redirectTo');

		$remember = intPostVar('remember');
		if ($remember == 1) {
			$lifetime = $blog->getCorrectTime()+2592000;
			setcookie('comment_user',$data['bbs_name'],$lifetime,'/','',0);
			setcookie('comment_userid', $data['bbs_userinfo'],$lifetime,'/','',0);
			setcookie('bbs_pass', postVar('bbs_pass'),$lifetime,'/','',0);
		}elseif($remember == 0){
			$lifetime = $blog->getCorrectTime()-1;
			setcookie('comment_user','',$lifetime,'/','',0);
			setcookie('comment_userid', '',$lifetime,'/','',0);
			setcookie('bbs_pass', '',$lifetime,'/','',0);
		}

		if (isValidMailAddress($data['bbs_userinfo']))
			$data['bbs_userinfo']  = $data['bbs_userinfo'];
		elseif (strstr($data['bbs_userinfo'],'http://') != false)  
			$data['bbs_userinfo']  = $data['bbs_userinfo'];
		elseif (strstr($data['bbs_userinfo'],'www') != false)
			$data['bbs_userinfo']  = 'http://'.$data['bbs_userinfo'];
		else
			$data['bbs_userinfo'] = '';
	
		switch($type){
			case 'add_mess': 

				if (!empty($data['bbs_name']) and !empty($data['bbs_mes'])) {
					// データを追加する
					$sql = "INSERT INTO ".sql_table('plugin_bbs')."(bbs_number, bbs_baseid, bbs_name, bbs_userinfo, bbs_pass, bbs_mes, bbs_host, bbs_ip, bbs_date) ";
					$sql .= "VALUES(";
					$sql .= "'" . $bbs_number . "',";
					$sql .= "'" . $data['bbs_baseid'] . "',";
					$sql .= "'" . $data['bbs_name'] . "',";
					$sql .= "'" . $data['bbs_userinfo'] . "',";
					$sql .= "'" . $data['bbs_pass'] . "',";
					$sql .= "'" . $data['bbs_mes'] . "',";
					$sql .= "'" . $data['bbs_host'] . "',";
					$sql .= "'" . $data['bbs_ip'] . "',";
					$sql .= "'" . $data['bbs_date'] . "'";
					$sql .= ")";
					$res = mysql_query($sql) or die("データ追加エラー");
					if ($res) {
//						echo "<p>書き込みありがとうございました!<br />BBSに自動で戻ります</p>";
//						echo '<META HTTP-EQUIV="refresh" content="5;URL='.$data['redirectTo'].'">';
						header('Location: ' . $data['redirectTo']);
					}
				}else {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>	
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo _CHARSET?>">
<title>エラー</title>
<body>
	<p align="center"><b>お名前とメッセージを入力してください</b>
	<p align="center"><b>お名前に「<?php echo $this->deny_name?>」は使えません</b>
	<br /><a href="javascript:history.go(-1);">戻る</a></p>
</body>
</html>
<?php
				}
				break;

			case 'update': 
				$data['bbsid'] = intval(postVar('bbsid'));
//				$query =  'UPDATE '.sql_table('plugin_bbs')
//				       . " SET bbs_mes='" .addslashes($data['bbs_mes']). "'"
//				       . " WHERE bbsid=" . $data['bbsid'];
				$query =  'UPDATE '.sql_table('plugin_bbs')
				       . " SET bbs_name='" .$data['bbs_name']. "',"
				       . " bbs_userinfo='" .$data['bbs_userinfo']. "',"
				       . " bbs_pass='" .$data['bbs_pass']. "',"
				       . " bbs_mes='" .$data['bbs_mes']. "'"
				       . " WHERE bbsid=" . $data['bbsid'];
				$res = mysql_query($query) or die("データ更新エラー");
				if ($res) {
//					echo "<p>書き込みありがとうございました!<br />BBSに自動で戻ります</p>";
//					echo '<META HTTP-EQUIV="refresh" content="0;URL='.$data['redirectTo'].'">';
					header('Location: ' . $data['redirectTo']);
				}
				break;

			case 'delete': 
				$data['bbsid'] = intval(postVar('bbsid'));
				$data['bbs_name'] = "-";
				$data['bbs_userinfo'] = "-";
				$query =  'UPDATE '.sql_table('plugin_bbs')
				       . " SET bbs_name='" .$data['bbs_name']. "',"
				       . " bbs_userinfo='" .$data['bbs_userinfo']. "',"
				       . " bbs_mes='" .$data['bbs_date']. "'"
				       . " WHERE bbsid=" . $data['bbsid'];
				$res = mysql_query($query) or die("データ更新エラー");
				if ($res) {
					header('Location: ' . $data['redirectTo']);
				}
				break;

			case 'search': 
				$bbsq = postVar('bbsq');
				$bbsq = preg_replace("/(\xA1{2}|\xe3\x80{2}|\x20)+/"," ",$bbsq);
				$bbsq = trim($bbsq);
				if($bbsq != ""){
					$bbsq = urlencode($bbsq);
					header('Location: ' . postVar('pagelink') . 'bbsq=' . $bbsq );
				}else{
					header('Location: ' . postVar('redirectTo') );
				}
				break;
			default:
				break;
		}	//end of switch
	}	//end of function doAction

 //////////////////////////////////////
///.	折り返しとインデント
	function indent( $str, $width, $ind ){
		///	$ind が数字の時は全てインデント、文字の時はぶら下がり
		if( is_int( $ind ) ){
			$spc = str_repeat( ' ', $ind );
			$lc = 1;
		}else{
//			$spc = str_repeat( ' ', strlen( $ind ) );
			$spc = str_repeat( $ind, 1 );
			$lc = 0;	///	ぶら下がりフラグ
		}
		$lines = explode( "\n", $str );
		foreach( $lines as $line ){
			if(ereg($ind, $line)){
				$width += strlen( $line ) * substr_count($line,$ind);
			}
			$p = 0;		///	分割位置
			$len = strlen( $line );
			while( $p < $len ){
				if (extension_loaded('mbstring')) { 
					$tmp = mb_strcut( $line, $p, $width ,_CHARSET);
				}elseif(function_exists('jstrcut') && strtolower (_CHARSET) == 'euc-jp'){
					$tmp = jstrcut( $line, $p, $width );
				}else{
					$tmp = $line;
				}
				///	最初の行だけぶら下がりインデント
				if( 0 == ($p+$lc) ){
					$newstr .= $ind.$tmp."\n";
					$lc++;
				}
				else	$newstr .= $spc.$tmp."\n";
				$p += strlen( $tmp );
			}
		}
		return	$newstr;
	}

 //////////////////////////////////////

	function bbshighlight($data) {
		if ($this->bHighlight){
			$temp = implode($this->bHighlight,'|');
			$data = preg_replace("/($temp)/","<span class=\"bbs_search\">\\1</span>",$data);
		}
		return $data;
	}

	function bbsBody($body) {
	
		// trim away whitespace and newlines at beginning and end
		$body = trim($body);

		// add <br /> tags
		$body = addBreaks($body);
	
		// create hyperlinks for http:// addresses
		// there's a testcase for this in /build/testcases/urllinking.txt
		$replaceFrom = array(
			'/([^:\/\/\w]|^)((https:\/\/)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',		
			'/([^:\/\/\w]|^)((http:\/\/|www\.)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',
			'/([^:\/\/\w]|^)((ftp:\/\/|ftp\.)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',
			'/([^:\/\/\w]|^)(mailto:(([a-zA-Z\@\%\.\-\+_])+))/ie'			
		);
		$replaceTo = array(
			'$this->bbscreateLinkCode("\\1", "\\2","https")',		
			'$this->bbscreateLinkCode("\\1", "\\2","http")',
			'$this->bbscreateLinkCode("\\1", "\\2","ftp")',
			'$this->bbscreateLinkCode("\\1", "\\3","mailto")'			
		);
		$body = preg_replace($replaceFrom, $replaceTo, $body);

		if ($this->getOption('smily') == "yes") {
			$body = $this->doSmilies($body);
		}

		return $body;
	}
	
	function bbscreateLinkCode($pre, $url, $protocol = 'http') {
		$post = '';
	
		// it's possible that $url ends with an entities 
		// since htmlspecialchars is applied before URL linking
		if (preg_match('/(&\w+;)+$/i', $url, $matches)) {
			$post = $matches[0];	// found entities (1 or more)
			$url = substr($url, 0, strlen($url) - strlen($post));
		}

		if (!ereg('^'.$protocol.'://',$url))
			$linkedUrl = $protocol . (($protocol == 'mailto') ? ':' : '://') . $url;
		else
			$linkedUrl = $url;
			
			
		if ($protocol != 'mailto')
			$displayedUrl = $linkedUrl;
		else
			$displayedUrl = $url;
		return $pre . '<a href="'.$linkedUrl.'">'.$displayedUrl.'</a>' . $post;
	}
	

	function insertSmilies() {
		global $CONF;

//		$url = $this->getAdminURL();
		$url = $CONF['PluginURL'] . 'fancytext/';
		
		$i = 0;
		foreach ($this->smiley as $smile => $img) {
			$data .= "<a href=\"JavaScript:paste_strinL('".$smile."')\">";
			$data .= '<img src="'.$url.$img.'" align="absmiddle" />';
			$data .= '</a>';
			$i++;
			if($i % 4 == 0){
				$data .= "<br />\n";
			}
		}	
		return $data;
	}
	
	function doSmilies($data) {
		global $CONF;
		
//		$url = $this->getAdminURL();
		$url = $CONF['PluginURL'] . 'fancytext/';
		
		foreach ($this->smiley as $smile => $img) {
			$data = str_replace($smile, '<img src="'.$url.$img.'" align="absmiddle" />', $data);
		}	
		
		return $data;
	}
	

	function parse_pageswitch($bbs_number, $totalamount = 0, $bbsq = ''){
		if($bbsq !== ''){
			$pagelink = $this->pagelink . 'bbsq=' . $bbsq .'&';
		}else{
			$pagelink = $this->pagelink;
		}

		$totalpages = ceil($totalamount/$this->pageamount);
		if($this->startpos > $totalamount){
			$this->currentpage = $totalpages;
			$this->startpos = $totalamount-$this->pageamount;
		}
	
		$buf = '<div class="pageswitch">'."\n";
	
		$this->currentpage > 1 ? $prevpage = $this->currentpage - 1 : $prevpage = 0;
		$nextpage = $this->currentpage + 1;
		
		if($prevpage){
			$prevpagelink = $pagelink. 'bbsp=' . $prevpage;
			$buf .= "\n".'<a href="'.$prevpagelink.'" title="前のページ">&laquo;Prev</a>';
		}
	
		$buf .= "\n |";
	
		for($i=1; $i<=$totalpages; $i++){
			if($i == $this->currentpage){
				$buf .= " <strong>{$this->currentpage}</strong> |";
			}elseif($totalpages<10 || $i<4 || $i>$totalpages-3){
				$buf .= ' <a href="'.$pagelink. 'bbsp=' . $i.'">'.$i.'</a> |';
			}else{
				if($i<$this->currentpage-1 || $i>$this->currentpage+1){
					if(($i==4 && ($this->currentpage>5 || $this->currentpage==1)) || $i==$this->currentpage+2){
						$buf .= '...|';
					}
				}else{
					$buf .= ' <a href="'.$pagelink. 'bbsp=' . $i.'">'.$i.'</a> |';
				}
			}
		}
		
		if($totalpages >= $nextpage){
			$nextpagelink = $pagelink. 'bbsp=' . $nextpage;
			$buf .= ' <a href="'.$nextpagelink.'" title="次のページ">Next&raquo;</a>'."\n";
		}
		$buf .= "</div>\n</form>\n";
		
		return $buf;
		
	}

	function getBBSTemplate($type) {
		global $DIR_PLUGINS;
		
//		$url = $this->getDirectory() . $type . '.template';
		$filename = $DIR_PLUGINS . 'fancytext/' . $type . '.template';
		
		if (!file_exists($filename)) 
			return '';
			
		// read file and return it
		$fd = fopen ($filename, 'r');
		$contents = fread ($fd, filesize ($filename));
		fclose ($fd);
		
		return $contents;
		
	}


}
?>