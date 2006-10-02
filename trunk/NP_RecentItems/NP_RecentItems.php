<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) { return 'nucleus_' . $name;}
}


class NP_RecentItems extends NucleusPlugin {
	function getName() {	return 'RecentItems'; }
	function getAuthor() { 	return 'nakahara21'; }
	function getURL() {	return 'http://nakahara21.com/'; }
	function getVersion() {	return '0.5'; }
	function getDescription() { 
		return 'Display Recent Items. Usage: &lt;%RecentItems(blogname,templatename,5)%&gt;';
	}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function doSkinVar($skinType, $blogName='', $templateName='', $amountEntries=5) { 
	global $manager;


		if(!BLOG::exists($blogName)) return;
		if(!TEMPLATE::exists($templateName)) return;
		if($amountEntries=='') $amountEntries=5;

		$tempBid = getBlogIDFromName($blogName);
		$b =& $manager->getBlog($tempBid); 

		$query = $this->_getsqlquery($b, $amountEntries, '');
		$b->showUsingQuery($templateName, $query, 0, 1, 0);
	}

	function _getsqlquery($blogObj, $amountEntries, $extraQuery)
	{
		$query = 'SELECT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, i.itime, i.imore as more, m.mnumber as authorid, m.memail as authormail, m.murl as authorurl, c.cname as category, i.icat as catid, i.iclosed as closed';
		
		$query .= ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c'
		       . ' WHERE i.iblog='.$blogObj->getID()
		       . ' and i.iauthor=m.mnumber'
		       . ' and i.icat=c.catid'
		       . ' and i.idraft=0'	// exclude drafts
					// don't show future items
		       . ' and i.itime<=' . mysqldate($blogObj->getCorrectTime());

//		if ($blogObj->getSelectedCategory())
//			$query .= ' and i.icat=' . $blogObj->getSelectedCategory() . ' ';

		$query .= $extraQuery;
		
		$query .= ' ORDER BY i.itime DESC';
		$query .= ' LIMIT '.$amountEntries;
		
		return $query;
	}
}
?>