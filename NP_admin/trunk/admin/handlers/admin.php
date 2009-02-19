<?php

class NP_admin_admin extends ADMIN {
	var $ob=false;
	function NP_admin_action($action,$post=array(),$get=array()){
		// Modify $_POST and $_GET
		$oPOST=$_POST;
		$oGET=$_GET;
		foreach($post as $key=>$value) $_POST[$key]=$value;
		foreach($get as $key=>$value) $_GET[$key]=$value;
		// Take action
		$this->ob=(bool)ob_start();
		$this->action($action);
		if ($this->ob) {
			$this->ob=ob_get_contents();
			ob_end_clean();
		}
		// Restore $_POST and $_GET
		$_POST=$oPOST;
		$_GET=$oGET;
	}
	function error($msg) {
		if ($this->ob===true) ob_end_flush();
		echo '<h2>Error!</h2>'.htmlspecialchars($msg,ENT_QUOTES,_CHARSET)/'<br />';
		echo '<a href="index.php" onclick="history.back()">'._BACK.'</a>';
		exit;
	}
	function pagehead() {}
	function pagefoot() {}
	/* Following actions are not used.
	 * Instead, skin engine is used.
	 */
	function action_showlogin() {}
	function action_login() {}
	function action_overview() {}
	function action_manage() {}
	function action_itemlist() {}
	function action_browseownitems() {}
	function action_itemcommentlist() {}
	function action_browseowncomments() {}
	function action_blogcommentlist() {}
	function action_createitem() {}
	function action_itemedit() {}
	function action_itemdelete() {}
	function action_itemmove() {}
	function action_sendping() {}
	function action_rawping() {}
	function action_commentedit() {}
	function action_commentdeleteconfirm() {}
	function action_usermanagement() {}
	function action_memberedit() {}
	function action_editmembersettings() {}
	function action_activate() {}
	function action_manageteam() {}
	function action_teamdelete() {}
	function action_blogsettings() {}
	function action_categoryedit() {}
	function action_blogsettingsupdate() {}
	function action_deleteblog() {}
	function action_memberdelete() {}
	function action_createnewlog() {}
	function action_skinieoverview() {}
	function action_skinieimport() {}
	function action_templateoverview() {}
	function action_templateedit() {}
	function action_templatedelete() {}
	function action_skinoverview() {}
	function action_skinedit() {}
	function action_skinedittype() {}
	function action_skindelete() {}
	function action_skinremovetype() {}
	function action_settingsedit() {}
	function action_bookmarklet() {}
	function action_actionlog() {}
	function action_banlist() {}
	function action_banlistdelete() {}
	function action_banlistnewfromitem() {}
	function action_banlistnew() {}
	function action_backupoverview() {}
	function action_pluginlist() {}
	function action_pluginhelp() {}
	function action_plugindelete() {}
	function action_pluginoptions() {}
	/* Following actions and methods are used from NP_admin_xxxx
	 */
	//function action_batchitem() {
	//function action_batchcomment() {
	//function action_batchmember() {
	//function action_batchteam() {
	//function action_batchcategory() {
	//function batchMoveSelectDestination($type, $ids) {
	//function batchMoveCategorySelectDestination($type, $ids) {
	//function batchAskDeleteConfirmation($type, $ids) {
	//function action_itemupdate() {
	//function action_itemdeleteconfirm() {
	//function deleteOneItem($itemid) {
	//function updateFuturePosted($blogid) {
	//function action_itemmoveto() {
	//function moveOneItem($itemid, $destCatid) {
	//function action_additem() {
	//function action_commentupdate() {
	//function action_commentdelete() {
	//function deleteOneComment($commentid) {
	//function action_changemembersettings() {
	//function action_memberadd() {
	//function _showActivationPage($key, $message = '')
	//function action_activatesetpwd() {
	//function action_teamaddmember() {
	//function action_teamdeleteconfirm() {
	//function deleteOneTeamMember($blogid, $memberid) {
	//function action_teamchangeadmin() {
	//function action_categorynew() {
	//function action_categoryupdate() {
	//function action_categorydelete() {
	//function action_categorydeleteconfirm() {
	//function deleteOneCategory($catid) {
	//function moveOneCategory($catid, $destblogid) {
	//function action_deleteblogconfirm() {
	//function action_memberdeleteconfirm() {
	//function deleteOneMember($memberid) {
	//function action_addnewlog() {
	//function action_addnewlog2() {
	//function action_skiniedoimport() {
	//function action_skinieexport() {
	//function action_templateupdate() {
	//function addToTemplate($id, $partname, $content) {
	//function action_templatedeleteconfirm() {
	//function action_templatenew() {
	//function action_templateclone() {
	//function action_skinnew() {
	//function action_skineditgeneral() {
	//function action_skinupdate() {
	//function action_skindeleteconfirm() {
	//function action_skinremovetypeconfirm() {
	//function action_skinclone() {
	//function skinclonetype($skin, $newid, $type) {
	//function action_settingsupdate() {
	//function updateConfig($name, $val) {
	//function action_regfile() {
	//function action_banlistdeleteconfirm() {
	//function action_banlistadd() {
	//function action_clearactionlog() {
	//function action_backupcreate() {
	//function action_backuprestore() {
	//function action_pluginadd() {
	//function action_pluginupdate() {
	//function action_plugindeleteconfirm() {
	//function deleteOnePlugin($pid, $callUninstall = 0) {
	//function action_pluginup() {
	//function action_plugindown() {
	//function action_pluginoptionsupdate() {
	//function _insertPluginOptions($context, $contextid = 0) {
}