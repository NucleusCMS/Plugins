<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}


class NP_Dtree extends NucleusPlugin {
	function getName() {
		return 'Navigation Tree'; 
	}
	function getAuthor() { 
		return 'nakahara21'; 
	}
	function getURL() {
		return 'http://nakahara21.com/'; 
	}
	function getVersion() {
		return '0.92'; 
	}
	function getDescription() { 
		return 'Show Navigation Tree. Usage: &lt;%Dtree()%&gt;';
	}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function doSkinVar($skinType, $itemid=0) { 
		global $blogid, $catid, $subcatid;
		
		$randomID = 'tree'.uniqid(rand());

		echo '<script type="text/javascript" src="'.$this->getAdminURL().'dtree.php"></script>';

		if($skinType == 'template'){
			echo '<script type="text/javascript" src="' . $this->getAdminURL() . 'dtreedata.php?o='.$randomID.'a&amp;bid=' . $blogid . '&amp;id='.$itemid.'"></script>';
			echo '<a href="javascript: '.$randomID.'a.openAll();">open all</a> | <a href="javascript: '.$randomID.'a.closeAll();">close all</a>';
			return;
		}

		$eq = '';
		if($catid)
			$eq .= '&amp;cid='.$catid;
		if($subcatid)
			$eq .= '&amp;sid='.$subcatid;

		echo '<script type="text/javascript" src="' . $this->getAdminURL() . 'dtreedata.php?o='.$randomID.'d&amp;bid=' . $blogid . $eq . '"></script>';
		echo '<a href="javascript: '.$randomID.'d.openAll();">open all</a> | <a href="javascript: '.$randomID.'d.closeAll();">close all</a>';

	}

		function doTemplateVar(&$item) {
			$this->doSkinVar('template', $item->itemid);
		}

}
?>