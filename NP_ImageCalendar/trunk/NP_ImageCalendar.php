<?php

//history
//	0.2:	$archive, $blogid and $catid suppot ($exmode=all ready)
//			echos 'no images' 
//	0.3:	add strtolower 
//			Initialize $this->exquery
//	0.4:	change css
//			allblogmode ready
//	0.5:	use createGlobalArchiveLink
//			sql_table support :-P
//	0.6:	support gif format
//			print archive month for the first
//	0.7:	linkmode: [0]archive day link, [1]item link for the image


// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}


class NP_ImageCalendar extends NucleusPlugin {
	function getName () {return 'ImageCalendar'; }
	function getAuthor () {return 'nakahara21'; }
	function getURL () {return 'http://nakahara21.com/';}
	function getVersion () {return '0.7';}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function getDescription () {
		return 'Embed the image of day into Calendar.';
	}

	function install () {
/*
		$this->createOption('default_catname','Default Category Name.','text','');
*/	
	}

	function init() {
		$this->fileex = array('.gif','.jpg','.png');
		$this->linkmode = 0; // create link for image [0]archive day link, [1]item link for the image
//		$this->random = 1;
	}
	
	function doSkinVar($skinType, $cnt=1, $wsize=50, $hsize=35, $point=0, $exmode=0) {
		global $CONF, $blog;
		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		
		if($cnt=='') $cnt = 1;
		if($hsize=='') $hsize = 80;
		if($wsize=='') $wsize = 80;
		if($exmode != 'all') $exmode = 0;
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
				$monthForPrint = getDate(mktime (0,0,0,$month,1, $year));


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
			echo 'No images here.';
			return;
		}
//		print_r($filelist);
/*
		echo '<div>';
		foreach($filelist as $key => $value){
//			$itemlink = createItemLink($value[1], '');
			$archivedaylink = createArchiveLink($b->getID(),$key,'');
			
			$exq = '';
			if($point) 	$exq = '&pnt=lefttop';
				
			echo '<a href="'.$archivedaylink.'">';
			echo '<img src="'.$CONF['ActionURL'].'?action=plugin&name=ImageCalendar&type=draw&p='.$value[0].'&wsize='.$wsize.'&hsize='.$hsize.$exq.'" />';
			echo "</a>\n";
		}
		echo "</div>\n";
*/


//==Calendar start==============
	$waku = '#9fdf9f';
	$waku = '#e8e9da';
	$wakurgb = $this->func_SetRgbValueFromHex($waku);
	$wakugr = $this->func_GetRgbFamilyFromRgb($waku,3,23,5);
//	print_r($wakugr);
?>
<style type="text/css">
<!--
	table.imgcalendar th{
		FONT-FAMILY: verdana, arial, sans-serif;
		FONT-WEIGHT: bold;
		FONT-SIZE: 9px;
		text-align:center;
		background-color:<?php echo $waku; ?>;
		color:RGB(<?php echo $wakugr[9]; ?>);
	}
	table.imgcalendar th.monthname{
		text-align:center;
		background-color:RGB(<?php echo $wakugr[2]; ?>);
		border:0px solid RGB(<?php echo $wakugr[2]; ?>);
	}
	td.empcell{
		width : <?php echo $wsize; ?>px;
		height : <?php echo $hsize; ?>px;
		Vertical-align: middle;
		text-align: center;
		padding:0px;
		margin:1px;
		background-color:none;
		border: 1px solid RGB(<?php echo $wakurgb; ?>);
		FONT-FAMILY: verdana, arial, sans-serif;
		FONT-WEIGHT: bold;
		FONT-SIZE: 9px;
		COLOR: <?php echo $waku; ?>;
	}
	td.imgcell{
		width : <?php echo $wsize; ?>px;
		Vertical-align: top;
		text-align: left;
		padding:0px;
		margin:1px;
		background-color:RGB(<?php echo $wakugr[8]; ?>);
		border: 1px solid <?php echo $waku; ?>;
	}
	.cellimg {
		MARGIN-TOP: 0px;
		MARGIN-LEFT: 0px;
		POSITION: absolute;
	}
	.cellspacer{
		height : <?php echo $hsize; ?>px;
	}
	p.anchor{
		width : <?php echo $wsize; ?>px;
		height : <?php echo $hsize; ?>px;
		background-color:silver;
	}
	div.d{
		FONT-FAMILY: verdana, arial, sans-serif;
		FONT-WEIGHT: bold;
		FONT-SIZE: 9px;
		COLOR: RGB(<?php echo $wakugr[2]; ?>);
		background-color:#fff;
		border-right: 1px solid <?php echo $waku; ?>;
		border-bottom: 1px solid <?php echo $waku; ?>;
		width:2em;
		TEXT-ALIGN: center;
		z-index:2;
		POSITION: absolute;
	}
-->
</style>
<?php
	$monthForPrint = ($monthForPrint)? $monthForPrint: getDate($b->getCorrectTime());

for($a=0;$a<$cnt;$a++){
	$p_month = date("Y-m",mktime(0,0,0,$monthForPrint['mon']-$a,1,$monthForPrint['year']));
	sscanf($p_month,'%4c-%2c',$year,$month);
	$date = getDate(mktime (0,0,0,$month,1, $year));
	$d = 1; 
	$out = '<table class="imgcalendar"><tr><th colspan="7" class="monthname">'.$p_month.'</th></tr><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr><tr>';
	while (checkdate($date['mon'], $d, $date['year'])){
		$p = date("w",mktime(0,0,0,$date['mon'],$d,$date['year']));
		$di = date("Y-m-d",mktime(0,0,0,$date['mon'],$d,$date['year']));
		if($d == 1) for($s=0;$s<$p;$s++){ $out .= '<td class="empcell">*</td>';}	// filling
		if(!$p) $out .= "</tr><tr>";
		$out .= '<td class="imgcell">';

		if($filelist[$di]){
			if($this->linkmode){
				$linkurl = createItemLink($filelist[$di][1],'');
				$itemtitle = quickQuery('SELECT ititle as result FROM '.sql_table('item').' WHERE inumber='.intval($filelist[$di][1]));
				$alt = shorten(strip_tags($itemtitle),25,'..');
			}else{
				$linkurl = $this->createGlobalArchiveLink($filelist[$di][2],$di,'');
				$alt = $di;
			}
				
			$out .= '<div class="d">'.$d.'</div>';
			$out .= '<DIV class=cellimg>';
			$out .= '<a href="'.$linkurl.'">';
			$out .= '<img src="'.$CONF['ActionURL'].'?action=plugin&amp;name=ImageCalendar&amp;type=draw&amp;p='.$filelist[$di][0].'&amp;wsize='.$wsize.'&amp;hsize='.$hsize.'" alt="'.$alt.'" />';
			$out .= '</a>';
			$out .= '</div>';
		}else{
			$out .= '<div class="d">'.$d.'</div>';
		}


		$out .= '<div class="cellspacer">&#160;<div>';
		$out .= '</td>';
		if($d == date("t",mktime(0,0,0,$date['mon'],$d,$date['year'])))	for($s=$p+1;$s<=6;$s++){ $out .= '<td class="empcell">*</td>';}	// filling
		$d++;
	}
	$out .= '</tr></table>';
	echo $out;
}
//==Calendar end==============

	}

//==color manegagement start==========
function func_SetRgbValueFromHex ($pHexColor)
{
	// INPUT :
	// $pHexColor : ie #339933
	// OUTPUT :
	// return rgb array
	// Source : Rini Setiadarma, http://www.oodie.com/

	$l_returnarray = array ();
	$pHexColor = str_replace ("#", "", $pHexColor);
	for ($l_counter=0; $l_counter<3; $l_counter++){ 
		$l_temp = substr($pHexColor, 2*$l_counter, 2); 
		$l_returnarray[$l_counter] = 16 * hexdec(substr($l_temp, 0, 1)) + hexdec(substr($l_temp, 1, 1)); 
	} 
	return implode(",",$l_returnarray);
}

function func_GetRgbFamilyFromRgb ($pHexColor, $pPrimary, $pIncrement, $pNum){
	
	$rgbarr = array();
	$pHexColor = str_replace ("#", "", $pHexColor);
	for ($l=0; $l<3; $l++){ 
		$l_temp = substr($pHexColor, 2*$l, 2); 
		$rgbarr[$l] = 16 * hexdec(substr($l_temp, 0, 1)) + hexdec(substr($l_temp, 1, 1)); 
	} 
	$pRgbColor = implode(",",$rgbarr);
	
	
	// INPUT :
	// $pRgbColor : ie 255,255,255 ; $pPrimary = pos. int (0-3); $pIncrement = pos. int ; $pNum = pos. int
	// OUTPUT :
	// array family from input
	
	$l_returnarray = array();
	$l_rgbarray = explode (",",$pRgbColor);
	if (($l_rgbarray != array ()) AND (count ($l_rgbarray) == 3))
	{	
		$l_start = $l_basevalue - ($pNum*$pIncrement);
		$l_end = $l_basevalue + ($pNum*$pIncrement);
		If ($pPrimary == 3)
		{
			$l_startin = 0;
			$l_endin = 2;
		} else {
			$l_startin = $pPrimary;
			$l_endin = $pPrimary;
		}
		for ($l_counter=$l_start;$l_counter<$l_end;$l_counter = $l_counter+$pIncrement)
		{
			$l_rgbarraytemp = $l_rgbarray;
			for ($l_counterint=$l_startin;$l_counterint<=$l_endin;$l_counterint++){
				$l_value = $l_rgbarray[$l_counterint]+$l_counter;
				if ($l_value < 0) $l_value = 0;
				if ($l_value > 255) $l_value = 255;
				$l_rgbarraytemp[$l_counterint] = $l_value;
			}
			if ($l_rgbarraytemp != array ()) $l_returnarray[] = implode (",",$l_rgbarraytemp);
		}
	}
	return $l_returnarray;
}

//==color manegagement end==========


	function listup(){
		global $blog;
		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);

		$query = 'SELECT inumber as itemid, ititle as title, ibody as body, iauthor, itime, imore as more, iblog, ' ;
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
			$item_ymd = date("Y-m-d",strtotime($it->itime));
			preg_match_all("/\<\%image\((.*)\)\%\>/Us",$txt,$imgpnt,PREG_PATTERN_ORDER);
			@array_walk($imgpnt[1], array(&$this, "exarray"), array($it->itemid,$it->iauthor,$item_ymd,$it->iblog));
		}
		return $this->imglists;
	}

	function exarray($imginfo,$key,$iaid){
		$imginfo = explode("|",$imginfo);
		if(!in_array(strtolower(strrchr($imginfo[0], "." )),$this->fileex)) return;
//		if(in_array($imginfo[0],$this->imgfilename)) return;
//		$this->imgfilename[] = $imginfo[0];
		list($iid,$auid,$item_ymd,$iblog) = $iaid;
		if (!strstr($imginfo[0],'/')) {
			$imginfo[0] = $auid . '/' . $imginfo[0];
		}
//		$this->imglists[] = array($imginfo,$iaid[0]);
//		$this->imglists[] = array($imginfo[0],$iaid[0],$iaid[2]);
		$this->imglists[$item_ymd] = array($imginfo[0],$iid,$iblog);
	}

	function baseimageCreate($p,$imgtype){
		switch($imgtype){
			case 1:
			return ImageCreateFromGif($p);
			case 2:
			return ImageCreateFromJpeg($p);
			case 3:
			return ImageCreateFromPng($p);
			default:
			return;
		}
	}

	function doAction($type) {
		global $CONF;
		global $DIR_MEDIA;
		$return = serverVar('HTTP_REFERER');
		switch($type) {
			case draw:
				if(!requestVar('p')) return;
				$p = $DIR_MEDIA.requestVar('p');	//path
		
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
					ImageGif($im);
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

	function createGlobalArchiveLink($blogid, $archive, $extra = '') {
		global $CONF, $manager;
		if ($CONF['URLMode'] == 'pathinfo'){
			$link = $CONF['ArchiveURL'] . '/archive/'.$blogid.'/' . $archive;
		}else{
			$script_name = $CONF['Self'];
			if(!$script_name) $script_name = 'index.php';
			
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

			$link = $blogurl . '?blogid='.$blogid.'&amp;archive=' . $archive;
		}
		return addLinkParams($link, $extra);
	}

}
?>