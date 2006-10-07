<?php

//history
//	0.2:	$archive, $blogid and $catid suppot ($exmode=all ready)
//			echos 'no images' 
//	0.3:	add strtolower 
//			Initialize $this->exquery
//	0.5:	use createGlobalItemLink
//			sql_table support :-P
//	0.6:	GIF supported
//			Security Fix
	
class NP_ExtractImage extends NucleusPlugin
{
	function getName ()
	{
		return 'ExtractImage';
	}

	function getAuthor ()
	{
		return 'nakahara21';
	}

	function getURL ()
	{
		return 'http://nakahara21.com';
	}

	function getVersion ()
	{
		return '0.6';
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

	function getDescription ()
	{
		return 'Extract image in items, and embed these images.';
	}

	function install () {
/*
		$this->createOption('default_catname','Default Category Name.','text','');
*/	
	}
	function init() {
		$this->fileex = array('.jpg', '.png', '.gif');
		$this->random = 1;
	}
	
	function doSkinVar($skinType, $amount=10, $align = 'yoko', $hsize='60', $random=0, $exmode=0)
	{
		global $CONF, $manager, $blog;

		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		
		if ($amount=='') {
			$amount = 10;
		}
		if ($align=='') {
			$align = 'yoko';
		}
		if ($hsize=='') {
			$hsize = 60;
		}
		if ($align == 'tate'){ 
			$wsize = $hsize;
		}
		if ($exmode != 'all') {
			$exmode = 0;
		}

		$this->exquery = '';

		switch ($skinType) {
			case 'archive': 
				global $archive;
				$y = $m = $d = '';
				sscanf($archive, '%d-%d-%d', $y,$m,$d);
				if (empty($d)) {
					$timestamp_start = mktime(0, 0, 0, $m, 1, $y);
					$timestamp_end = mktime(0, 0, 0, $m + 1, 1, $y);  // also works when $month==12
				} else {
					$timestamp_start = mktime(0, 0, 0, $m, $d,$y);
					$timestamp_end = mktime(0, 0, 0, $m,$d + 1,$y);  
				}
				$this->exquery .= ' and itime >= ' . mysqldate($timestamp_start)
				                . ' and itime < ' . mysqldate($timestamp_end);

//			break;
			default:
				if (empty($exmode)) {
						$this->exquery .= ' and iblog = ' . intval($b->getID());
					global $catid;
					if ($catid) {	
						$this->exquery .= ' and icat = ' . intval($catid);
					}
				}
		}

		$filelist = array();
		$this->imglists = array();
		$this->imgfilename = array();
		if (!($filelist = $this->listup())) {
			echo 'No images here.';
			return;
		}
//		print_r($filelist);
		$amount = min($amount, count($filelist));
		if ($random) {
			srand((float)microtime()*1000000);
			shuffle($filelist);
		}

		switch ($align) {
			case 'head':
				break;
			case 'tate':
				for ($i=0;$i<$amount;$i++) {
					$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');
//					$itemlink = createItemLink($filelist[$i][1]);
					echo '<div>';
					echo '<a href="' . $itemlink . '">';
					echo '<img src="' . $CONF['ActionURL'] . '?action=plugin&name=ExtractImage&type=draw&p=' . $filelist[$i][0][0] . '&wsize=' . $wsize . '" vspace="1" />';
					echo "</a></div>\n";
				}
				break;
			case 'yoko':
			default:
				echo '<div>';
				for ($i=0;$i<$amount;$i++) {
					$itemlink =$this->createGlobalItemLink($filelist[$i][1], '');
//					$itemlink = createItemLink($filelist[$i][1]);
					echo '<a href="'.$itemlink.'">';
					echo '<img src="' . $CONF['ActionURL'] . '?action=plugin&name=ExtractImage&type=draw&p=' . htmlspecialchars($filelist[$i][0][0], ENT_QUOTES) . '&hsize=' . $hsize . '" />';
					echo "</a>\n";
				}
					echo "</div>\n";
				break;
			
		}
	}

	function listup(){
		global $CONF, $manager, $blog;

		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}

		$query = 'SELECT inumber as itemid, ititle as title, ibody as body, iauthor, itime, imore as more,' ;
		$query .= ' icat as catid, iclosed as closed' ;
		$query .= ' FROM ' . sql_table('item');
		$query .= ' WHERE idraft = 0';
		$query .= ' and itime <= ' . mysqldate($b->getCorrectTime());	// don't show future items!
		$query .= $this->exquery;
		$query .= ' ORDER BY itime DESC'; 
//		echo $query;
	
		$res = sql_query($query);
		
		if (!mysql_num_rows($res)) {
			return FALSE;
		}
		
		while ($it = mysql_fetch_object($res)) {
			$txt = $it->body . $it->more;
			preg_match_all("/\<\%image\((.*)\)\%\>/Us", $txt, $imgpnt, PREG_PATTERN_ORDER);
			@array_walk($imgpnt[1], array(&$this, "exarray"), array($it->itemid, $it->iauthor));
		}
//		$list = array('http://blog.nakahara21.net/media/1/bbb.jpg','http://yukarin.s43.xrea.com/blog/media/1/20040616-146.jpg');
		return $this->imglists;
	}

	function exarray($imginfo, $key, $iaid)
	{
		$imginfo = explode("|", $imginfo);
//		if(strrchr($imginfo[0], "." ) != '.jpg') return;
		if (!in_array(strtolower(strrchr($imginfo[0], "." )), $this->fileex)) {
			return;
		}
		if (in_array($imginfo[0], $this->imgfilename)) {
			return;
		}
		$this->imgfilename[] = $imginfo[0];
		if (!strstr($imginfo[0], '/')) {
			$imginfo[0] = $iaid[1] . '/' . $imginfo[0];
		}
//		$this->imglists[] = $imginfo;
		$this->imglists[] = array($imginfo, $iaid[0]);
	}

	function baseimageCreate($p, $im_info)
	{
		switch($im_info[2]){
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

	function doAction($type)
	{
		global $DIR_MEDIA;
		
		if(!requestVar('p')) return 'No such file';
		$p = $DIR_MEDIA.requestVar('p');	//path
		$p = realpath($p);
		if( !$p ) return 'No such file';
		if( strpos($p, $DIR_MEDIA) !== 0 ) return 'No such file';
		
		switch ($type) {
			case 'draw':
				$this->im_info = GetImageSize($p);
		
				$tsize['h'] = intRequestVar('hsize');
				if (!$tsize['h'] && intRequestVar('wsize')){
					$tsize['w'] = intRequestVar('wsize');
					$tsize['h'] = intval($this->im_info[1] * $tsize['w'] / $this->im_info[0]);
				}
				if (!$tsize['h']) {
					$tsize['h'] = 50;
				}
				
				if (!$tsize['w']) {
					$tsize['w'] = intval($this->im_info[0] * $tsize['h'] / $this->im_info[1]);
				}

				$im_r = $this->baseimageCreate($p,$this->im_info);
				$im = ImageCreateTrueColor($tsize['w'], $tsize['h']);
				ImageCopyResampled( $im, $im_r, 0, 0, 0, 0, $tsize['w'], $tsize['h'], $this->im_info[0], $this->im_info[1] );

				switch ($this->im_info[2]) {
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
				return 'No such action';
				break;
//_=======
		}
	}

	function canEdit()
	{
		global $member, $manager;
		if (!$member->isLoggedIn()) {
			return 0;
		}
		return $member->isAdmin();
	}


	function createGlobalItemLink($itemid, $extra = '')
	{
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