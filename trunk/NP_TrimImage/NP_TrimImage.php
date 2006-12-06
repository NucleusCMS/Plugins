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
//	    	Security Fix.
//	2.0: 	use phpThumb() (http://phpthumb.sourceforge.net)
// 	2.1:	update regex
//	    	add alt/title attribute
//	    	bug fix

define('NP_TRIMIMAGE_FORCE_PASSTHRU', true); //passthru(standard)
//define('NP_TRIMIMAGE_FORCE_PASSTHRU', false); //redirect(advanced)

define('NP_TRIMIMAGE_CACHE_MAXAGE', 86400 * 30); // 30days
define('NP_TRIMIMAGE_PREFER_IMAGEMAGICK', false);

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
		return '2.1.1';
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
		//$phpThumb = new phpThumb();
		//foreach($this->phpThumbParams as $paramKey => $paramValue ){
		//	$phpThumb->setParameter($paramKey, $paramValue);
		//}
		//$phpThumb->setParameter('config_cache_maxage', 1);
		//$phpThumb->CleanUpCacheDirectory();
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
		$cacheDir = $DIR_MEDIA.'phpthumb/';
		$cacheDir = (is_dir($cacheDir) && @is_writable($cacheDir) ) ? $cacheDir : null;
		
		$this->phpThumbParams = array(
			'config_document_root' => $DIR_MEDIA,
			'config_cache_directory' => $cacheDir,
			'config_cache_disable_warning' => true,
			'config_cache_directory_depth' => 0,
			'config_cache_maxage' => NP_TRIMIMAGE_CACHE_MAXAGE,
			'config_cache_maxsize' => 10 * 1024 * 1024, // 10MB
			'config_cache_maxfiles' => 1000,
			'config_cache_source_filemtime_ignore_local' => true,
			'config_cache_cache_default_only_suffix' => '',
			'config_cache_prefix' => 'phpThumb_cache',
			'config_cache_force_passthru' => NP_TRIMIMAGE_FORCE_PASSTHRU,
			'config_max_source_pixels' => 3871488, //4Mpx
			'config_output_format' => 'jpg',
			'config_disable_debug' => true,
			'config_prefer_imagemagick' => NP_TRIMIMAGE_PREFER_IMAGEMAGICK,
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
		
		if ( !is_numeric($amount) ) $amount = 10;
		if ( !is_numeric($hsize) ) $hsize = 80;
		if ( !is_numeric($wsize) ) $wsize = 80;
		$point = ($point == 'lefttop' ) ? true : false;
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
		$random =  $random ? true : false;
		if (!($filelist = $this->listup($amount,$random))) {
			echo 'No images here.';
			return;
		}
//		print_r($filelist);

		echo '<div>';
		for ($i=0;$i<$amount;$i++) {
			$itemlink = $this->createGlobalItemLink($filelist[$i][1], '');	// why not createItemLink ?
//			$itemlink = $this->createItemLink($filelist[$i][1]);
			echo '<a href="' . $itemlink . '">';
			
			$src = '';
			if( ! $this->phpThumbParams['config_cache_force_passthru'] ){
				$src = $this->createImage($filelist[$i][0], $wsize, $hsize, $point, true);
			}
			if(!$src) {
				$src = htmlspecialchars($CONF['ActionURL'],ENT_QUOTES)
				. '?action=plugin&amp;name=TrimImage&amp;type=draw'
				. '&amp;p=' . $filelist[$i][0] . '&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . ( $point ? '&amp;pnt=lefttop' : '');
			}
			echo '<img src="'.$src.'" width="'.$wsize.'" height="'.$hsize.'" alt="'.htmlspecialchars($filelist[$i][2]).'" title="'.htmlspecialchars($filelist[$i][2]).'"/>';
			echo "</a>\n";
		}
		echo "</div>\n";
	}

	function listup($amount = 10, $random = false)
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
		$query .= ' ORDER BY itime DESC LIMIT ' . intval($amount * 10);
				
		$res = sql_query($query);
		
		if (!mysql_num_rows($res)) return FALSE;
		
		while ($it = mysql_fetch_object($res)) {
			$txt = $it->body . $it->more;
			if( preg_match_all("/<%(image|popup|paint)\((.*?)\)%>/s", $txt, $imgpnt) )
				@array_walk($imgpnt[2], array(&$this, "exarray"), array($it->itemid, $it->iauthor));
			if( count($this->imglists) >= $amount ) break;
		}
		mysql_free_result($res);
	
		if($random) shuffle($this->imglists);
		$this->imglists = array_slice($this->imglists, 0, $amount);
		return $this->imglists;
	}

	function exarray($imginfo,$key,$iaid)
	{
		list($url, $w, $h, $alt, $ext)  = explode("|", $imginfo, 5);
		if (!in_array(strtolower(strrchr($url, "." )), $this->fileex)) return;
		if (in_array($url, $this->imgfilename)) return;
		$this->imgfilename[] = $url;
		if (!strstr($url, '/')) {
			$url = $iaid[1] . '/' . $url;
		}
		$this->imglists[] = array($url,$iaid[0],$alt,$ext);
	}

	function doTemplateVar(&$item, $wsize=80, $hsize=80, $point=0, $maxAmount=0)
	{
		global $CONF;
		if ( !is_numeric($hsize) ) $hsize = 80;
		if ( !is_numeric($wsize) ) $wsize = 80;
		$point = ($point == 'lefttop' ) ? true : false;
		
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

			if( preg_match_all("/<%(image|popup|paint)\((.*?)\)%>/s", $txt, $imgpnt) )
				@array_walk($imgpnt[2], array(&$this, "exarray"), array($item->itemid, $item->authorid));

			$filelist = $this->imglists;
			if(!$maxAmount)
				$amount = count($filelist);
			else
				$amount = min($maxAmount, count($filelist));

		if (!$amount) {
			$img_tag = '<img src="' . htmlspecialchars($CONF['ActionURL'],ENT_QUOTES) . '?action=plugin&amp;name=TrimImage';
			$img_tag .= '&amp;type=draw&amp;p=non&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . $exq;
			$img_tag .= '" width="' . $wsize . '" height="' . $hsize . '" />';
			echo $img_tag;
		}


		for ($i=0;$i<$amount;$i++) {
			$src = '';
			if( ! $this->phpThumbParams['config_cache_force_passthru'] ){
				$src = $this->createImage($filelist[$i][0], $wsize, $hsize, $point, true);
			}
			if(!$src) {
				$src = htmlspecialchars($CONF['ActionURL'],ENT_QUOTES)
					. '?action=plugin&amp;name=TrimImage&amp;type=draw'
					. '&amp;p=' . $filelist[$i][0] . '&amp;wsize=' . $wsize . '&amp;hsize=' . $hsize . ( $point ? '&amp;pnt=lefttop' : '');
			}
			echo '<img src="'.$src.'" width="'.$wsize.'" height="'.$hsize.'" alt="'.htmlspecialchars($filelist[$i][2]).'" title="'.htmlspecialchars($filelist[$i][2]).'"/>';
		}
	}


	function doAction($type)
	{
		$w = intRequestVar('wsize') ? intRequestVar('wsize') : 80;
		$h = intRequestVar('hsize') ? intRequestVar('hsize') : 80;
		$isLefttop = (requestVar('pnt') == 'lefttop') ? true : false;
		
		switch ($type) {
			case 'draw':
				$this->createImage(requestVar('p'), $w, $h, $isLefttop);
				break;
			default:
				return 'No such action';
				break;
		}
	}
	
	function createImage($p, $w, $h, $isLefttop, $cacheCheckOnly = false){
		$phpThumb = new phpThumb();
		foreach($this->phpThumbParams as $paramKey => $paramValue ){
			$phpThumb->setParameter($paramKey, $paramValue);
		}
		
		$phpThumb->setParameter('w', intval($w) );
		$phpThumb->setParameter('h', intval($h) );
		if ($p == 'non') {
			$phpThumb->setParameter('new', 'FFFFFF');
		} else {
			$phpThumb->setParameter('src', '/' . $p);
			$phpThumb->setParameter('zc', $isLefttop ? 2 : 1 );
		}
		
		// getCache	
		$phpThumb->cache_filename = null;
		$phpThumb->CalculateThumbnailDimensions();
		$phpThumb->SetCacheFilename();
		if( file_exists($phpThumb->cache_filename) ){
			$nModified  = filemtime($phpThumb->cache_filename);
			if( time() - $nModified < NP_TRIMIMAGE_CACHE_MAXAGE ){
				global $CONF;
				preg_match('/^'.preg_quote($this->phpThumbParams['config_document_root'], '/').'(.*)$/', $phpThumb->cache_filename, $matches);
				$fileUrl = $CONF['MediaURL'].$matches[1];
				if( $cacheCheckOnly ) return $fileUrl;

				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $nModified).' GMT');
				if (@serverVar('HTTP_IF_MODIFIED_SINCE') && ($nModified == strtotime(serverVar('HTTP_IF_MODIFIED_SINCE'))) && @serverVar('SERVER_PROTOCOL')) {
					header(serverVar('SERVER_PROTOCOL').' 304 Not Modified');
					return true;
				}
				if ($getimagesize = @GetImageSize($phpThumb->cache_filename)) {
					header('Content-Type: '.phpthumb_functions::ImageTypeToMIMEtype($getimagesize[2]));
				} elseif (eregi('\.ico$', $phpThumb->cache_filename)) {
					header('Content-Type: image/x-icon');
				}
				if( $this->phpThumbParams['config_cache_force_passthru'] ){
					@readfile($phpThumb->cache_filename);
				} else {
					header('Location: '.$fileUrl);
				}
				return true;
			}
		}
		if( $cacheCheckOnly ){
			unset($phpThumb);
			return false;
		}
		
		// generate
		$phpThumb->GenerateThumbnail();

		// putCache
		if( !rand(0,20) ) $phpThumb->CleanUpCacheDirectory();
		$phpThumb->RenderToFile($phpThumb->cache_filename);
		@chmod($phpThumb->cache_filename, 0666);
		
		// to browser
		$phpThumb->OutputThumbnail();
		unset($phpThumb);
		return true;
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
