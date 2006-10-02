<?php

//history
//	0.2:	$archive, $blogid and $catid suppot ($exmode=all ready)
//			echos 'no images' 
//	0.3:	add strtolower 
//			Initialize $this->exquery
//	0.5:	use createGlobalItemLink
//			sql_table support :-P
//	0.6:	parameter supports blogid and catid
//	0.7:	supports templatevar
//			supports <%popup()%> 
//	0.8:		supports gif 

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}


class NP_TrimImage extends NucleusPlugin {
	function getName () {return 'TrimImage'; }
	function getAuthor () {return 'nakahara21'; }
	function getURL () {return 'http://xx.nakahara21.net/';}
	function getVersion () {return '0.8';}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getDescription () {
		return 'Extract image in items, and embed these images.';
	}

	function install () {
/*
		$this->createOption('default_catname','Default Category Name.','text','');
*/	
	}

	function init() {
		$this->fileex = array('.gif','.jpg','.png');
		$this->random = 1;
	}
	
	function doSkinVar($skinType, $amount=10, $wsize=80, $hsize=80, $point=0, $random=0, $exmode='') {
		global $CONF, $blog;
		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		
		if($amount=='') $amount = 10;
		if($hsize=='') $hsize = 80;
		if($wsize=='') $wsize = 80;
		if($point != 'lefttop') $point = 0;
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
				if($exmode == ''){
					$this->exquery .= ' and iblog =' . $b->getID();
					global $catid;
					if($catid)	$this->exquery .= ' and icat =' . $catid;
				}elseif($exmode == 'all'){
				}else{
					$spid_array = $spbid = $spcid = array();
					$spid_array = explode('/',$exmode);
					foreach($spid_array as $spid){
						if(substr($spid, 0, 1) == 'b')
							$spbid[] = intval(substr($spid, 1));
						if(substr($spid, 0, 1) == 'c')
							$spcid[] = intval(substr($spid, 1));
					}
					$spbid = implode(',',$spbid);
					$spcid = implode(',',$spcid);
					if($spbid && $spcid){
						$this->exquery .= ' and ( iblog IN ('.$spbid.') or icat IN ('.$spcid.') )';
					}elseif($spbid){
						$this->exquery .= ' and iblog IN ('.$spbid.') ';
					}elseif($spcid){
						$this->exquery .= ' and icat IN ('.$spcid.') ';
					}
				}
		}


		$filelist = array();
		$this->imglists = array();
		$this->imgfilename = array();
		if(!($filelist = $this->listup())){
			echo 'No images here.';
			return;
		}
//		print_r($filelist);
		$amount = min($amount,count($filelist));
		if($random){
			srand((float)microtime()*1000000);
			shuffle($filelist);
		}

		echo '<div>';
		for($i=0;$i<$amount;$i++){
			$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');
			echo '<a href="'.$itemlink.'">';
			
			$exq = '';
			if($point) 	$exq = '&pnt=lefttop';
				
			echo '<img src="'.$CONF['ActionURL'].'?action=plugin&name=TrimImage&type=draw&p='.$filelist[$i][0][0].'&wsize='.$wsize.'&hsize='.$hsize.$exq.'" />';
			echo "</a>\n";
		}
		echo "</div>\n";
	}

	function listup(){
		global $blog;
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
	
		$res = sql_query($query);
		
		if(!mysql_num_rows($res)) return FALSE;
		
		while ($it = mysql_fetch_object($res)){
			$txt = $it->body.$it->more;
			preg_match_all("/\<\%image\((.*)\)\%\>/Us",$txt,$imgpnt,PREG_PATTERN_ORDER);
			@array_walk($imgpnt[1], array(&$this, "exarray"), array($it->itemid,$it->iauthor));
			preg_match_all("/\<\%popup\((.*)\)\%\>/Us",$txt,$imgpntp,PREG_PATTERN_ORDER);
			@array_walk($imgpntp[1], array(&$this, "exarray"), array($it->itemid,$it->iauthor));
		}
		return $this->imglists;
	}

	function exarray($imginfo,$key,$iaid){
		$imginfo = explode("|",$imginfo);
		if(!in_array(strtolower(strrchr($imginfo[0], "." )),$this->fileex)) return;
		if(in_array($imginfo[0],$this->imgfilename)) return;
		$this->imgfilename[] = $imginfo[0];
		if (!strstr($imginfo[0],'/')) {
			$imginfo[0] = $iaid[1] . '/' . $imginfo[0];
		}
		$this->imglists[] = array($imginfo,$iaid[0]);
	}

	function baseimageCreate($p,$imgtype){
		switch($imgtype){
			case 2:
			return ImageCreateFromJpeg($p);
			case 3:
			return ImageCreateFromPng($p);
			default:
			return;
		}
	}

	function doTemplateVar(&$item, $wsize=80, $hsize=80, $point=0){
		global $CONF;
		if($hsize=='') $hsize = 80;
		if($wsize=='') $wsize = 80;
		if($point != 'lefttop') $point = 0;
		
		$filelist = array();
		$this->imglists = array();
		$this->imgfilename = array();
			$txt = $item->body.$item->more;
			preg_match_all("/\<\%image\((.*)\)\%\>/Us",$txt,$imgipnt,PREG_PATTERN_ORDER);
			@array_walk($imgipnt[1], array(&$this, "exarray"), array($item->itemid,$item->authorid));
			preg_match_all("/\<\%popup\((.*)\)\%\>/Us",$txt,$imgipntp,PREG_PATTERN_ORDER);
			@array_walk($imgipntp[1], array(&$this, "exarray"), array($item->itemid,$item->authorid));
			
			$filelist = $this->imglists;
//			print_r($filelist);
		$amount = count($filelist);

//		echo '<div style="text-align:center;padding:3px;">';

		if(!$amount){
//			echo '<img src="" width="'.$wsize.'" height="'.$hsize.'" />';
			echo '<img src="'.$CONF['ActionURL'].'?action=plugin&name=TrimImage&type=draw&p=non&wsize='.$wsize.'&hsize='.$hsize.$exq.'" width="'.$wsize.'" height="'.$hsize.'" />';
		}


		for($i=0;$i<$amount;$i++){
//			$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');
//			echo '<a href="'.$itemlink.'">';
			
			$exq = '';
			if($point) 	$exq = '&pnt=lefttop';
				
			echo '<img src="'.$CONF['ActionURL'].'?action=plugin&name=TrimImage&type=draw&p='.$filelist[$i][0][0].'&wsize='.$wsize.'&hsize='.$hsize.$exq.'" width="'.$wsize.'" height="'.$hsize.'" />';
//			echo '<br />'.shorten(strip_tags($item->title),16,'...');
//			echo "</a>\n";
		}
//		echo "</div>\n";
	}


	function doAction($type) {
		global $CONF;
		global $DIR_MEDIA;
		$return = serverVar('HTTP_REFERER');
		switch($type) {
			case draw:
				if(!requestVar('p')) return;
				$p = $DIR_MEDIA.requestVar('p');	//path
		
				
				if(requestVar('p') == 'non'){
					$im = ImageCreate(requestVar('wsize'),requestVar('hsize')) or die ("Cannnot Initialize new GD image stream");
					$bgcolor = ImageColorAllocate($im,0,255,255); //color index:0
//					$strcolor = ImageColorAllocate($im,153,153,153); //color index:1
					imagecolortransparent($im, $bgcolor);
//					imageString($im, 1, 4, 0,'No images',$strcolor);
					header ("Content-type: image/png");
					ImagePng($im);
					imagedestroy($im);
					berak;
				}

				list($imgwidth, $imgheight, $imgtype) = GetImageSize($p);
		
				$tsize['w'] = requestVar('wsize');
				$tsize['h'] = requestVar('hsize');
				$point = requestVar('pnt');
				
				if($imgwidth / $imgheight < $tsize['w'] / $tsize['h']){ // height longer
					$trimX = 0;
					$trimW = $imgwidth;
					$trimH = intval($tsize['h']/$tsize['w']*$imgwidth);
					$trimY = intval(($imgheight - $trimH) / 2);
				}else{ // width longer
					$trimY = 0;
					$trimH = $imgheight;
					$trimW = intval($tsize['w']/$tsize['h']*$imgheight);
					$trimX = intval(($imgwidth - $trimW) / 2);
				}
				
				if($point == 'lefttop'){
					$trimX = $trimY = 0;
				}
				
				$im_r = $this->baseimageCreate($p,$imgtype);
				$im = ImageCreateTrueColor($tsize['w'],$tsize['h']);
				ImageCopyResampled( $im, $im_r, 0, 0, $trimX, $trimY, $tsize['w'], $tsize['h'], $trimW, $trimH);
				switch($imgtype){
					case 1:
					header ("Content-type: image/gif");
					Imagegif($im);
					imagedestroy($im);
					break;
					case 2:
					header ("Content-type: image/jpeg");
					ImageJpeg($im);
					imagedestroy($im);
					break;
					case 3:
					header ("Content-type: image/png");
					ImagePng($im);
					imagedestroy($im);
					break;
					default:
					return;
				}
			break;

			default:
				Header('Location: ' . $return);
				break;
//_=======
		}
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