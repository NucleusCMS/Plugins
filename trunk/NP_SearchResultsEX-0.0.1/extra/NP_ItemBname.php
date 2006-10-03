<?

class NP_ItemBname extends NucleusPlugin {

	function getName() { return 'ItemBname'; }	
	function getAuthor() { return ''; }
	function getURL() { return '../../index.html'; }
	function getVersion() { return '1.0'; }
	function getDescription() { return 'Usage:&lt;%ItemBname%&gt; in template'; }

	function doTemplateVar($item) {
		global $CONF;
		$thisblogid = getBlogIDFromItemID($item->itemid);
		$thisblogname = getBlogNameFromID($thisblogid);
		echo '<a href="'.createBlogIDLink($thisblogid).'">'.$thisblogname.'</a>';
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
		
}
?>
