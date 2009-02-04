<?

class NP_ArchiveListEX extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'ArchiveListEX'; 
	}
	
	// author of plugin
	function getAuthor()  { 
		return 'nakahara21'; 
	}
	
	// an URL to the plugin website
	function getURL() 
	{
		return 'http://nakahara21.com/'; 
	}
	
	// version of the plugin
	function getVersion() {
		return '1.01'; 
	}
	
	function supportsFeature($what) { 
		switch($what){ 
			case 'SqlTablePrefix': 
				return 1; 
			default: 
				return 0; 
		} 
	}

	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return 'Show all item title on each archive list. ';
	}

	function doAction($type) {
		
		$archiveMonth = intRequestVar('a');
		$bid = intRequestVar('b');
		$tid = intRequestVar('t');

		switch ($type) {
			case 'ga' :
				echo '<ul>'.$this->getArchives($tid, $bid, $archiveMonth).'</ul>';
				break;
			default :
				return 'No such action';
				break;
		}
	}

	function getArchives($tid, $bid, $archiveMonth){
		global $manager;
		$b =& $manager->getBlog($bid);
		
		list($year, $month) = sscanf($archiveMonth, "%4s%2s");

		$extraQuery = ' and SUBSTRING(itime,1,4)=' . $year
					. ' and SUBSTRING(itime,6,2)=' . $month;
		
		$template = TEMPLATE::getNameFromId($tid);

		ob_start();
		$b->readLogAmount($template, 0, $extraQuery, 0, 1, 0);
		$contents =  ob_get_contents();
		ob_end_clean();

		if (_CHARSET != 'UTF-8'){
			$contents = mb_convert_encoding($contents, 'UTF-8', _CHARSET);
		}
		
		return $contents;
	}


	function doSkinVar($skinType, $template = 'default/index', $mode = 'month', $limit = 0) { 
		global $manager, $blog, $CONF, $catid, $itemid; 

		$params = func_get_args();
		if ($params[1]){ $template = $params[1]; }
		if ($params[2]){
			if ($params[2] == 'month' | $params[2] == 'day'){ $mode = $params[2]; }
			else{ $limit = intval($params[2]); }
		}
		if ($params[3]){ $limit = intval($params[3]); }

	if ($blog) { 
		$b =& $blog; 
	} else { 
		$b =& $manager->getBlog($CONF['DefaultBlog']); 
	} 

		if ($catid) {
			$this->linkparams = array('catid' => $catid);
		}

//switch($skinType) { 
//	case 'archivelist': 

		$tid = TEMPLATE::getIdFromName($template);
		$template = TEMPLATE::read($template);
		$data['blogid'] = $b->getID();

		$jshref = $CONF['PluginURL'].'sharedlibs/miniajax.js';
		$href = htmlspecialchars($CONF['ActionURL'], ENT_QUOTES) . '?action=plugin&name=ArchiveListEX&type=ga&t='.$tid.'&b='.$b->getID().'&a=';


?>
<script type="text/javascript" src="<?php echo $jshref; ?>"></script>
<script type="text/javascript"><!--
function getData(id){
	ajax.update("<?php echo $href; ?>"+id, "result"+id);
}
// --></script>
<?php

//===================================
		$query = 'SELECT count(*) as sum, itime, SUBSTRING(itime,1,4) AS Year, SUBSTRING(itime,6,2) AS Month, SUBSTRING(itime,9,2) as Day FROM '.sql_table('item')
		. ' WHERE iblog=' . $b->getID()
		. ' and itime <=' . mysqldate($b->getCorrectTime())	// don't show future items!
		. ' and idraft=0'; // don't show draft items
		
		if ($catid)
			$query .= ' and icat=' . intval($catid);
/**/		
		$query .= ' GROUP BY Year, Month';
		if ($mode == 'day')
			$query .= ', Day';
		
			
//		$query .= ' ORDER BY itime ASC';	
		$query .= ' ORDER BY itime DESC';	
		
		if ($limit > 0) 
			$query .= ' LIMIT ' . $limit;
		
		$res = sql_query($query);

		$oldYear = 0;
		while ($current = mysql_fetch_object($res)) {
			$current->itime = strtotime($current->itime);	// string time -> unix timestamp
			if ($mode == 'day') {
				$archivedate = date('Y-m-d',$current->itime);
				$archive['day'] = date('d',$current->itime);
			} else {
				$archivedate = date('Y-m',$current->itime);			
			}
			$data['month'] = date('m',$current->itime);
			$data['year'] = date('Y',$current->itime);
			$data['archivelink'] = createArchiveLink($b->getID(),$archivedate,$this->linkparams);
			$data['sum'] = $current->sum;
			$data['monthid'] = $current->Year.$current->Month;
			
			if($oldYear && $data['year'] != $oldYear){
				$tempf = TEMPLATE::fill($template['ARCHIVELIST_FOOTER'],$data);
				echo $lastFooter = strftime($tempf,$current->itime);
			}

			if($data['year'] != $oldYear){
				$temph = TEMPLATE::fill($template['ARCHIVELIST_HEADER'],$data);
				echo strftime($temph,$current->itime);
			}

			$temp = TEMPLATE::fill($template['ARCHIVELIST_LISTITEM'],$data);
			echo strftime($temp,$current->itime);

			$oldYear = $data['year'];

		}
		mysql_free_result($res);
		echo $lastFooter;

//		break;
//===================================
//	default: 
//				echo "tttt";
//		} 
	}
}
?>