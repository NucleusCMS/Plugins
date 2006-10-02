<?php

//history
//	0.5:	test version
//	0.51	htmlspecialchars text
//	0.52	linkurl ready :: rename to pholiot

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_Pholiot extends NucleusPlugin {
	function getName () {return 'Pholiot'; }
	function getAuthor () {return 'nakahara21'; }
	function getURL () {return 'http://xx.nakahara21.net/';}
	function getVersion () {return '0.52';}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getDescription () {
		return 'Extract image in items, and embed these images into Pholiot!';
	}

	function install () {
/*
		$this->createOption('default_catname','Default Category Name.','text','');
*/	
	}

	function init() {
		$this->fileex = array('.jpg','.swf');
	}
	
	function doSkinVar($skinType, $ss='', $amount=30, $random=0, $exmode=0) {
		global $CONF, $blog,$manager;
		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		
		if($amount=='') $amount = 30;
		if($exmode != 'all') $exmode = 0;
		$this->exquery = '';

		switch($skinType){
			case 'archive': 
				global $archive;
				sscanf($archive,'%4c-%2c-%2c',$year,$month,$day);
				if ($day == 0) {
					$timestamp_start = mktime(0,0,0,$month,1,$year);
					$timestamp_end = mktime(0,0,0,$month+1,1,$year);  // also works when $month==12
				} else {
					$timestamp_start = mktime(0,0,0,$month,$day,$year);
					$timestamp_end = mktime(0,0,0,$month,$day+1,$year);  
				}
				$this->exquery .= ' and itime>=' . mysqldate($timestamp_start)
				                . ' and itime<' . mysqldate($timestamp_end);

//			break;
			default:
				if(!$exmode){
					$this->exquery .= ' and iblog =' . $b->getID();
					global $catid;
					if($catid)	$this->exquery .= ' and icat =' . $catid;
				}
		}

		$filelist = array();
		$this->imglists = array();
		$this->imgfilename = array();
		if(!($filelist = $this->listup())){
			$durl = $this->getAdminURL().'pholiot.xml';
		}else{
//		print_r($filelist);
			$amount = min($amount,count($filelist));
			if($random){
				srand((float)microtime()*1000000);
				shuffle($filelist);
			}
	
					$feed = '<';
					$feed .= '?';
					$feed .= 'xml version="1.0" encoding="UTF-8"';
					$feed .= '?';
					$feed .= '>';
					$feed .= <<<EOD
<pholiotdata>
	<customize defaultmode="slideshow" fitimagetoview="true" fitviewtoimage="true" pan="false" zoom="true" zoomrandomxy="true" zoomdepth="2" zoomrandomdepth="false" panzoomtime="5000" crossfadetime="3000" slidedelaytime="6000" motionmode="liner" playallgalleries="true" returntobrowse="false" galleries="true" thumbnail="true" xmargin="0" ymargin="0" />
	<gallery name="pholiot" description="TEST"> 
EOD;
					foreach($filelist as $imglist){
						$linkurl = $this->createGlobalItemLink($imglist[1],'');
						$feed .= '<image>';
						$feed .= '<imageurl>'.$CONF[MediaURL].$imglist[0].'</imageurl>';
						$feed .= '<caption>'.$imglist[2].'</caption>';
						$feed .= '<linkurl name="'.$imglist[3].' ID:'.$imglist[1].'">'.$linkurl.'</linkurl>';
						$feed .= '</image>';
					}
					$feed .= <<<EOD
	</gallery>
</pholiotdata>
EOD;
			$feed = mb_convert_encoding($feed, "UTF-8", _CHARSET);
	
			$fp = @fopen($this->getDirectory()."feed.xml","w+"); 
			if (!$fp)
				$durl = $this->getAdminURL().'pholiot.xml';
			fputs($fp,$feed); 
			fclose($fp); 
			
			$durl = $this->getAdminURL().'feed.xml';
		}

		switch($ss){
			case 'head':
				echo '<script type="text/javascript" language="JavaScript" src="'.$this->getAdminURL().'pholiot.js"></script>';
				break;
			default:
				$surl = $this->getAdminURL().'pholiot.swf';
				echo <<<EOD
<script type="text/javascript" language="JavaScript"> 
showPholiot({url: '{$surl}', data_url: '{$durl}', bgcolor: '#e8e9da', width: '120', height: '160', menu: 'false'}); 
</script>
EOD;


		}
	}

	function listup(){
		global $blog,$manager;
		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);

		$query = 'SELECT inumber as itemid, ititle as title, ibody as body, iauthor, itime, imore as more,' ;
		$query .= ' icat as catid, iclosed as closed' ;
		$query .= ' FROM '.sql_table('item');
		$query .= ' WHERE idraft=0';
		$query .= ' and itime <=' . mysqldate($b->getCorrectTime());	// don't show future items!
		$query .= $this->exquery;
		$query .= ' ORDER BY itime DESC'; 
//		echo $query;
	
		$res = sql_query($query);
		
		if(!mysql_num_rows($res)) return FALSE;
		
		while ($it = mysql_fetch_object($res)){
			$ititle = $it->title;
			$txt = $it->body.$it->more;
			$item_ymd = date("Y-m-d",strtotime($it->itime));
			$capt = htmlspecialchars(shorten(strip_tags( (!$ititle)? $txt: $ititle ),30,'..'));
			preg_match_all("/\<\%image\((.*)\)\%\>/Us",$txt,$imgpnt,PREG_PATTERN_ORDER);
			@array_walk($imgpnt[1], array(&$this, "exarray"), array($it->itemid,$it->iauthor,$capt,$item_ymd));
			preg_match_all("/\<\%popup\((.*)\)\%\>/Us",$txt,$imgpntp,PREG_PATTERN_ORDER);
			@array_walk($imgpntp[1], array(&$this, "exarray"), array($it->itemid,$it->iauthor,$capt,$item_ymd));
		}
		return $this->imglists;
	}

	function exarray($imginfo,$key,$iaid){
		list($iid, $auid, $capt,$item_ymd) = $iaid;
		$imginfo = explode("|",$imginfo);
		if(trim($imginfo[3])) $capt = htmlspecialchars(shorten(strip_tags($imginfo[3]),30,'..'));
		
		if(!in_array(strtolower(strrchr($imginfo[0], "." )),$this->fileex)) return;
		if(in_array($imginfo[0],$this->imgfilename)) return;
		$this->imgfilename[] = $imginfo[0];
		
		if (!strstr($imginfo[0],'/')) {
			$imginfo[0] = $auid . '/' . $imginfo[0];
		}
		
		$this->imglists[] = array($imginfo[0],$iid,$capt,$item_ymd);
	}

	function canEdit() {
		global $member, $manager;
		if (!$member->isLoggedIn()) return 0;
		return $member->isAdmin();
	}


	function createGlobalItemLink($itemid, $extra = '') {
		global $CONF, $manager;

		if ($CONF['URLMode'] == 'pathinfo'){
			$link = $CONF['ItemURL'] . '/item/' . $itemid;
		}else{
			$blogid = getBlogIDFromItemID($itemid);
			$b_tmp =& $manager->getBlog($blogid);
			$blogurl = $b_tmp->getURL() ;
			if(!$blogurl){
				$blogurl = $CONF['IndexURL'];
			}
			if(substr($blogurl, -4) != '.php'){
				if(substr($blogurl, -1) != '/')
					$blogurl .= '/';
				$blogurl .= 'index.php';
			}
			$link = $blogurl . '?itemid=' . $itemid;
		}
		return addLinkParams($link, $extra);
	}


}
?>