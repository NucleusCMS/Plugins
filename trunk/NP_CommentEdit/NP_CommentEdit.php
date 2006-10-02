<?

class NP_CommentEdit extends NucleusPlugin
{

	function getName()
	{
		return 'Comment Editable';
	}
	
	function getAuthor()
	{ 
		return 'nakahara21';
	}
	
	function getURL()
	{
		return 'http://nakahara21.com'; 
	}
	
	function getVersion()
	{
		return '0.3';
	}
	
	function getDescription()
	{
		return 'Comment Editable';
	}

	function supportsFeature($what)
	{
		switch ($what) {
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

		if ($member->isLoggedIn()) {
			if ($member->canAlterComment($comment['commentid'])) {
			echo '<small class="commedit">';
			echo '<a href="';
			echo  $CONF['AdminURL'] . 'index.php?action=commentedit&commentid=' . $comment['commentid'];
			echo '" target="_blank">[edit]</a>';
			echo ' <a href="';
			echo  $CONF['AdminURL'] . 'index.php?action=commentdelete&commentid=' . $comment['commentid'];
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