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
//	0.8:	supports gif
//	0.9:	doTemplateVar calls DB data for other PreItem Plugin
//	0.9:	change '&' to '&amp;'
//	1.1:	NP_Paint support.
//			Security Fix.
//	2.0: 	use phpThumb() (http://phpthumb.sourceforge.net)

define('NP_TRIMIMAGE_CACHE_MAXAGE', 86400 * 30); // 30days

require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('phpthumb/phpthumb.functions.php');
require_once('phpthumb/phpthumb.class.php');

class NP_TrimImage extends NucleusPlugin
{
	function getName ()
	{
		return 'TrimImage';
	}

	function getAuthor ()
	{
		return 'nakahara21 + hsur';
	}

	function getURL () {
		return 'http://nakahara21.com/';
	}
	
	function getVersion () {
		return '2.0';
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
		return 'Trim image in items, and embed these images.';
	}
			function getEventList() { 
		return array(
			'PostAddItem',
			'PostUpdateItem',
			'PostDeleteItem',
		); 
	}
	
	function event_PostAddItem(&$data){
		$this->_clearCache();
	}
	function event_PostUpdateItem(&$data){		$this->_clearCache();
	}
	function event_PostDeleteItem(&$data){
		$this->_clearCache();
	}
	function _clearCache(){
		$phpThumb = new phpThumb();
		foreach($this->phpThumbParams as $paramKey => $paramValue ){
			$phpThumb->setParameter($paramKey, $paramValue);
		}
		$phpThumb->setParameter('config_cache_maxage', 1);
		$phpThumb->CleanUpCacheDirectory();
		//var_dump($phpThumb);
	}

/*
	function instaii()
	{
		$ver_min = (getNucleusVersion() < $this->getMinNucleusVersion());
		$pat_min = ((getNucleusVersion() == $this->getMinNucleusVersion()) &&
			(getNucleusPatchLevel() < $this->getMinNucleusPatchLevel()));
		if ($ver_min || $pat_min) {
			$this->_attention();
		}
	}

	function _attention()
	{
		global $admin;
		$admin->pagehead();
		echo '<h2>ATTENTION</h2>';
		echo 'Your Nucleus version is old<br />';
		echo 'Please version-up Nucleus CORE<br />';
		echo 'newest version is 3.23 !!<br />';
		echo '<a href="index.php" onclick="history.back()">'._BACK.'</a>';
		$admin->pagefoot();
		return;
	}
*/
	function init()
	{
		global $DIR_MEDIA;
		$this->fileex = array('.gif', '.jpg', '.png');
		$this->random = 1;
		
		$this->phpThumbParams = array(
			'config_document_root' => $DIR_MEDIA,
			'config_cache_directory' => $DIR_MEDIA.'phpthumb/',
			'config_cache_disable_warning' => true,
			'config_cache_directory_depth' => 0,
			'config_cache_maxage' => NP_TRIMIMAGE_CACHE_MAXAGE,
			'config_cache_maxsize' => 10 * 1024 * 1024, // 10MB
			'config_cache_maxfiles' => 1000,
			'config_cache_source_filemtime_ignore_local' => false,
			'config_cache_source_filemtime_ignore_remote' => true,
			'config_cache_cache_default_only_suffix' => '',
			'config_cache_prefix' => 'phpThumb_cache',
			'config_cache_force_passthru' => true,
			'config_max_source_pixels' => 3871488, //4Mpx
			'config_output_format' => 'jpg',
			'config_disable_debug' => true,
		);
	}
	
	function doSkinVar($skinType, $amount = 10, $wsize = 80, $hsize = 80, $point = 0, $random = 0, $exmode = '')
	{
		global $CONF, $manager, $blog;
		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
/*		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);*/
		
		if ($amount=='') $amount = 10;
		if ($hsize=='') $hsize = 80;
		if ($wsize=='') $wsize = 80;
		if ($point != 'lefttop') $point = 0;
		$this->exquery = '';
		

		switch($skinType){
			case 'archive':
				global $archive;
				$year = $month = $day = '';
				sscanf($archive, '%d-%d-%d', $year, $month, $day);
				if (empty($day)) {
					$timestamp_start = mktime(0, 0, 0, $month, 1, $year);
					$timestamp_end = mktime(0, 0, 0, $month + 1, 1, $year);  // also works when $month==12
				} else {
					$timestamp_start = mktime(0, 0, 0, $month, $day, $year);
					$timestamp_end = mktime(0, 0, 0, $month, $day + 1, $year);  
				}
				$this->exquery .= ' and itime >= ' . mysqldate($timestamp_start)
								. ' and itime < ' . mysqldate($timestamp_end);

//			break;
			default:
				if ($exmode == '') {
					$this->exquery .= ' and iblog = ' . intval($b->getID());
					global $catid;
					if ($catid) $this->exquery .= ' and icat = ' . intval($catid);
				} elseif ($exmode == 'all') {
					// nothing
				} else {
					$spid_array = $spbid = $spcid = array();
					$spid_array = explode('/', $exmode);
					foreach ($spid_array as $spid) {
						if (substr($spid, 0, 1) == 'b')
							$spbid[] = intval(substr($spid, 1));
						if (substr($spid, 0, 1) == 'c')
							$spcid[] = intval(substr($spid, 1));
					}
					$spbid = implode(',', $spbid);
					$spcid = implode(',', $spcid);
					if($spbid) {
						$this->exquery .= ' and iblog IN (' . $spbid . ') ';
					}
					if($spcid) {
						$this->exquery .= ' and icat IN (' . $spcid . ') ';
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
		$amount = min($amount,count($filelist));
		if ($random) {
			srand((float)microtime()*1000000);
			shuffle($filelist);
		}

		echo '<div>';
		for ($i=0;$i<$amount;$i++) {
			$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');	// why not createItemLink ?
//			$itemlink = $this->createItemLink($filelist[$i][1]);
			echo '<a href="' . $itemlink . '">';
			
			$exq = '';
			if ($point) $exq = '&amp;pnt=lefttop';
				
			echo '<img src="' . $CONF['ActionURL'] . '?action=plugin&amp;name=TrimImage&amp;type=draw&amp;p=' . htmlspecialchars($filelist[$i][0][0], ENT_QUOTES) . '&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . $exq . '" />';
			echo "</a>\n";
		}
		echo "</div>\n";
	}

	function listup()
	{
		global $CONF, $manager, $blog;
		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
/*		($blog)?
			$b =& $blog :
			$b =& $manager->getBlog($CONF['DefaultBlog']);*/

		$query = 'SELECT inumber as itemid, ititle as title, ibody as body, iauthor, itime, imore as more,';
		$query .= ' icat as catid, iclosed as closed';
		$query .= ' FROM ' . sql_table('item');
		$query .= ' WHERE idraft = 0';
		$query .= ' and itime <= ' . mysqldate($b->getCorrectTime());	// don't show future items!
		$query .= $this->exquery;
		$query .= ' ORDER BY itime DESC'; 
	
		$res = sql_query($query);
		
		if (!mysql_num_rows($res)) return FALSE;
		
		while ($it = mysql_fetch_object($res)) {
			$txt = $it->body . $it->more;
			preg_match_all("/\<\%image\((.*)\)\%\>/Us", $txt, $imgpnt, PREG_PATTERN_ORDER);
			@array_walk($imgpnt[1], array(&$this, "exarray"), array($it->itemid, $it->iauthor));
			preg_match_all("/\<\%popup\((.*)\)\%\>/Us", $txt, $imgpntp, PREG_PATTERN_ORDER);
			@array_walk($imgpntp[1], array(&$this, "exarray"), array($it->itemid, $it->iauthor));
			preg_match_all("/\<\%paint\((.*)\)\%\>/Us", $txt, $imgpnta, PREG_PATTERN_ORDER);
			@array_walk($imgpnta[1], array(&$this, "exarray"), array($it->itemid, $it->iauthor));
		}
		return $this->imglists;
	}

	function exarray($imginfo,$key,$iaid)
	{
		$imginfo = explode("|", $imginfo);
		if (!in_array(strtolower(strrchr($imginfo[0], "." )), $this->fileex)) return;
		if (in_array($imginfo[0], $this->imgfilename)) return;
		$this->imgfilename[] = $imginfo[0];
		if (!strstr($imginfo[0], '/')) {
			$imginfo[0] = $iaid[1] . '/' . $imginfo[0];
		}
		$this->imglists[] = array($imginfo, $iaid[0]);
	}

	function doTemplateVar(&$item, $wsize=80, $hsize=80, $point=0, $maxAmount=0)
	{
		global $CONF;
		if ($hsize=='') $hsize = 80;
		if ($wsize=='') $wsize = 80;
		if ($point != 'lefttop') $point = 0;
		
		$filelist = array();
		$this->imglists = array();
		$this->imgfilename = array();
//			$txt = $item->body.$item->more;
			$txt = '';
			$q = 'SELECT ibody as body, imore as more FROM '.sql_table('item').' WHERE inumber='.intval($item->itemid);
			$r = sql_query($q);
			while ($d = mysql_fetch_object($r)) {
				$txt .= $d->body.$d->more;
			}

			preg_match_all("/\<\%image\((.*)\)\%\>/Us", $txt, $imgipnt, PREG_PATTERN_ORDER);
			@array_walk($imgipnt[1], array(&$this, "exarray"), array($item->itemid, $item->authorid));
			preg_match_all("/\<\%popup\((.*)\)\%\>/Us",$txt,$imgipntp, PREG_PATTERN_ORDER);
			@array_walk($imgipntp[1], array(&$this, "exarray"), array($item->itemid, $item->authorid));
			preg_match_all("/\<\%paint\((.*)\)\%\>/Us",$txt,$imgipnta, PREG_PATTERN_ORDER);
			@array_walk($imgipnta[1], array(&$this, "exarray"), array($item->itemid, $item->authorid));

			$filelist = $this->imglists;
//			print_r($filelist);
			if(!$maxAmount)
				$amount = count($filelist);
			else
				$amount = min($maxAmount, count($filelist));

//		echo '<div style="text-align:center;padding:3px;">';

		if (!$amount) {
//			echo '<img src="" width="'.$wsize.'" height="'.$hsize.'" />';
			$img_tag = '<img src="' . $CONF['ActionURL'] . '?action=plugin&amp;name=TrimImage';
			$img_tag .= '&amp;type=draw&amp;p=non&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . $exq;
			$img_tag .= '" width="' . $wsize . '" height="' . $hsize . '" />';
			echo $img_tag;
		}


		for ($i=0;$i<$amount;$i++) {
//			$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');
//			echo '<a href="'.$itemlink.'">';
			
			$exq = '';
			if ($point) $exq = '&amp;pnt=lefttop';
				
			$img_tag = '<img src="' . $CONF['ActionURL'] . '?action=plugin&amp;name=TrimImage&amp;type=draw';
			$img_tag .= '&amp;p=' . $filelist[$i][0][0] . '&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . $exq;
			$img_tag .= '" width="' . $wsize . '" height="' . $hsize . '" />';
			echo $img_tag;
//			echo '<img src="'.$CONF['ActionURL'].'?action=plugin&name=TrimImage&type=draw&p='.$filelist[$i][0][0].'&wsize='.$wsize.'&hsize='.$hsize.$exq.'" width="'.$wsize.'" height="'.$hsize.'" />';
//			echo '<br />'.shorten(strip_tags($item->title),16,'...');
//			echo "</a>\n";
		}
//		echo "</div>\n";
	}


	function doAction($type)
	{
		$w = intRequestVar('wsize') ? intRequestVar('wsize') : 80;
		$h = intRequestVar('hsize') ? intRequestVar('hsize') : 80;
		$pnt = requestVar('pnt');
		
		switch ($type) {
			case 'draw':
				$this->createImage(requestVar('p'), $w, $h, $pnt);
				break;
			default:
				return 'No such action';
				break;
		}
	}
	
	function createImage($p, $w, $h, $pnt){
		$phpThumb = new phpThumb();
		foreach($this->phpThumbParams as $paramKey => $paramValue ){
			$phpThumb->setParameter($paramKey, $paramValue);
		}
		$phpThumb->setParameter('w', $w);
		$phpThumb->setParameter('h', $h);
		
		if ($p == 'non') {
			$phpThumb->setParameter('new', 'FFFFFF');
		} else {
			$phpThumb->setParameter('src', $p);
			$phpThumb->setParameter('zc', 1);
			if ($pnt == 'lefttop') {
				$phpThumb->setParameter('sx', 0);
				$phpThumb->setParameter('sy', 0);
			}
		}
		
		// getCache	
		$phpThumb->SetCacheFilename();
		if( file_exists($phpThumb->cache_filename) ){
			$nModified  = filemtime($phpThumb->cache_filename);
			if( time() - $nModified < NP_TRIMIMAGE_CACHE_MAXAGE ){
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $nModified).' GMT');
				if (@serverVar('HTTP_IF_MODIFIED_SINCE') && ($nModified == strtotime(serverVar('HTTP_IF_MODIFIED_SINCE'))) && @serverVar('SERVER_PROTOCOL')) {
					header(serverVar('SERVER_PROTOCOL').' 304 Not Modified');
					return;
				}
				if ($getimagesize = @GetImageSize($phpThumb->cache_filename)) {
					header('Content-Type: '.phpthumb_functions::ImageTypeToMIMEtype($getimagesize[2]));
				} elseif (eregi('\.ico$', $phpThumb->cache_filename)) {
					header('Content-Type: image/x-icon');
				}
				@readfile($phpThumb->cache_filename);
				return;
			}
		}
		
		// generate
		$phpThumb->GenerateThumbnail();

		// putCache
		if( !rand(0,20) ) $phpThumb->CleanUpCacheDirectory();
		$phpThumb->RenderToFile($phpThumb->cache_filename);
		chmod($phpThumb->cache_filename, 0666);
		
		// to browser
		$phpThumb->OutputThumbnail();
		unset($phpThumb);
	}

	function canEdit()
	{
		global $member, $manager;
		if (!$member->isLoggedIn()) return 0;
		return $member->isAdmin();
	}


	function createGlobalItemLink($itemid, $extra = '')
	{
		global $CONF, $manager;
		$itemid = intval($itemid);
		if ($CONF['URLMode'] == 'pathinfo') {
			$link = $CONF['ItemURL'] . '/item/' . $itemid;
		} else {
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
