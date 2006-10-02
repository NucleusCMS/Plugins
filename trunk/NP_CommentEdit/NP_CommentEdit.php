<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_CommentEdit extends NucleusPlugin {

	function getName() {	// name of plugin
		return 'Comment Editable'; 
	}
	
	function getAuthor()  {	// author of plugin 
		return 'nakahara21'; 
	}
	
	function getURL() 	{	// an URL to the plugin website
		return 'http://xx.nakahara21.net/'; 
	}
	
	function getVersion() {	// version of the plugin
		return '0.3'; 
	}
	
	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return 'Comment Editable';
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
//__________________________________________
/*
	function install() {
	}

	function uninstall() {
	}

	function getTableList() {
		return array(sql_table(plug_QQQQQ),sql_table(plug_QQQQQ_cache));
	}

//__________________________________________

	function getEventList() {
		return array('PostAddItem','PreUpdateItem','AddItemFormExtras','EditItemFormExtras');
	}

	function event_PostAddItem($data) {
	}

	function event_PreUpdateItem($data) {
	}

	function event_AddItemFormExtras($data) {
	}

	function event_EditItemFormExtras($data) {
	}

//__________________________________________

	function init() {

	}
*/
//__________________________________________

	function doTemplateCommentsVar(&$item, &$comment, $type, $param1 = 'QQQQQ') { 
	global $CONF, $member;
/*
	global $manager, $blog;
	global $catid, $itemid;
*/	

	if($member->isLoggedIn()){
	if($member->canAlterComment($comment['commentid'])){
	echo '<small>';
	echo '<a href="';
	echo  $CONF['AdminURL'].'index.php?action=commentedit&commentid='.$comment['commentid'];
	echo '" target="_blank">[edit]</a>';
	echo ' <a href="';
	echo  $CONF['AdminURL'].'index.php?action=commentdelete&commentid='.$comment['commentid'];
	echo '" target="_blank">[del]</a>';
	echo '</small>';
	}
	}

	}
	//__________________________________________

/*
	function doAction($type) {
		echo 'QQQQQ';
	}
*/
	//__________________________________________

}
?>