<?

class NP_DateLink extends NucleusPlugin {
	function getEventList() { return array(); }
	function getName() { return 'DateLink'; }
	function getAuthor()  { return 'nakahara21'; }
	function getURL()  { return 'http://nakahara21.com'; }
	function getMinNucleusVersion() {	return 200;	}
	function getVersion() { return '0.9'; }
	function getDescription() {
		return '&lt;%DateLink%&gt; on TEMPLATE displays links to the archives for the same date. ';
	}
	
	function supportsFeature($what) {
		switch($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function install() {
		$this->createOption('dl_limit','Limit to create links (years)','text','3');
		$this->createOption('dl_header','Header of links','text','<ul>');
		$this->createOption('dl_footer','Footer of links','text','</ul>');
		$this->createOption('template_ago','Template for older (Blank for no link.)','text','<li><a href="<%linkurl%>" title="Archive for <%date%>">&laquo; <%year%> year(s) ago</a></li>');
		$this->createOption('template_after','Template for newer (Blank for no link.)','text','<li><a href="<%linkurl%>" title="Archive for <%date%>"><%year%> year(s) after &raquo;</a></li>');
		$this->createOption('template_separator','Separator for links','text','');
	}

	function doTemplateVar(&$item){
		$today = $item->timestamp;
		$tcat = $item->catid;
		if($linkForPrint = $this->LinksDate($today, $tcat, $this->getOption('dl_limit'))){
			echo $this->getOption('dl_header');
			echo @join($this->getOption('template_separator'), $linkForPrint);
			echo $this->getOption('dl_footer');
		}
	}
	
	function LinksDate($timestamp, $catid, $limitYear){
		global $manager, $blog, $CONF, $archive;

		if(!$this->getOption('template_ago') && !$this->getOption('template_after'))
			return FALSE;

		$blogid = getBlogIDFromCatID($catid);
		$b = & $manager->getBlog($blogid);
		$from = array('<%linkurl%>','<%date%>','<%year%>');

		for($i=$limitYear;$i>0;$i--){
			if(!$this->getOption('template_ago')) break;
			$target_date = date('Y-m-d', strtotime("-".$i." years", $timestamp));
			$s = $this->getArchiveForDate($target_date, $b);
			if($s) {
				$linkurl = createArchiveLink($blogid,$target_date);
				$to = array(
					$linkurl,
					$target_date,
					$i
				);
				$print_data[] = str_replace($from,$to, $this->getOption('template_ago'));
			}
		}

		for($i=1;$i<=$limitYear;$i++){
			if(!$this->getOption('template_after')) break;
			$target_date = date('Y-m-d', strtotime("+".$i." years", $timestamp));
			$s = $this->getArchiveForDate($target_date, $b);
			if($s) {
				$linkurl = createArchiveLink($blogid,$target_date);
				$to = array(
					$linkurl,
					$target_date,
					$i
				);
				$print_data[] = str_replace($from,$to, $this->getOption('template_after'));
			}
		}

		return $print_data;
	}
	
	function getArchiveForDate($target_date, $blog){
		$query = 'SELECT inumber FROM '.sql_table('item')
			.' WHERE iblog = '.$blog->getID().' AND itime BETWEEN "'
			.$target_date.' 00:00:00" AND "'
			.$target_date.' 23:59:59"'
			.' AND idraft=0'
			.' AND itime<=' . mysqldate($blog->getCorrectTime())
			.' LIMIT 1';
		$result = sql_query($query);
		if(mysql_num_rows($result)) return TRUE;
		return FALSE;
	}
}
?>