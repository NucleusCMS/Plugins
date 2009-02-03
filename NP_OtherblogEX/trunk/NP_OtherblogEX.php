<? 
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_OtherblogEX extends NucleusPlugin { 
	function getEventList() { return array(); } 
	function getName() { return 'OtherblogEX'; } 
	function getAuthor() { return 'nakahara21'; } 
	function getURL() { return 'http://xx.nakahara21.net/'; } 
	function getVersion() { return '0.3'; } 
	function getDescription() { 
		return 'OtherblogEX'; 
	} 

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function doSkinVar($skinType, $blogname, $template, $amount = 10, $catname = '', $sort) { 

		list($limit, $offset) = sscanf($amount, '%d(%d)');
		if($sort == 'ASC'){
			$sort = 'ASC';
		}else{
			$sort = 'DESC';
		}
		$this->readLogAmountex($blogname,$template,$catname,$limit,'','',1,1,$offset, $startpos, $sort);
	}// doSkinVar end

	function readLogAmountex($blogname, $template, $catname, $amountEntries, $extraQuery, $highlight, $comments, $dateheads, $offset = 0, $startpos = 0, $sort) {
		global $manager;

		$b =& $manager->getBlog(getBlogIDFromName($blogname));
		if ($catname != '')
			$b->setSelectedCategoryByName($catname);
		else
			$b->setSelectedCategory($catid);

		$query =  'SELECT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, UNIX_TIMESTAMP(i.itime) as timestamp, i.imore as more, m.mnumber as authorid, m.memail as authormail, m.murl as authorurl, c.cname as category, i.icat as catid, i.iclosed as closed'
		       . ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c'
		       . ' WHERE i.iblog='.$b->getID()
		       . ' and i.iauthor=m.mnumber'
		       . ' and i.icat=c.catid'
		       . ' and i.idraft=0'	// exclude drafts
					// don't show future items
		       . ' and i.itime<=' . mysqldate($b->getCorrectTime());

		if ($b->getSelectedCategory())
			$query .= ' and i.icat=' . $b->getSelectedCategory() . ' ';

		$query .= $extraQuery
		       . ' ORDER BY i.itime '.$sort;

		if ($amountEntries > 0) {
		        // $offset zou moeten worden:
		        // (($startpos / $amountentries) + 1) * $offset ... later testen ...
		       $query .= ' LIMIT ' . intval($startpos + $offset).',' . intval($amountEntries);
		}

		return $b->showUsingQuery($template, $query, $highlight, $comments, $dateheads);
	}

} 
?>