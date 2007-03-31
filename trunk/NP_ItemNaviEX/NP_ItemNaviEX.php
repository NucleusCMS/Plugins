<?php
/**
 *
 * BreadCrumbsList PLUG-IN FOR NucleusCMS
 * PHP versions 4 and 5
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * @author     Original Author nakahara21
 * @copyright  2005-2006 nakahara21
 * @license     http://www.gnu.org/licenses/gpl.txt  GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 * @version    0.992
 * @link       http://japan.nucleuscms.org/wiki/plugins:itemnaviex
 *
 * 0.993  Henceforth to template
 * 0.992  display mode '0' fix
 * 0.991  add sub-blog home mode
 * 0.99   sec fix
 *
 **************************************************************************
 *
 * THESE PLUG-INS ARE DEDICATED TO ALL THOSE NucleusCMS USERS
 * WHO FIGHT CORRUPTION AND IRRATIONAL IN EVERY DAY OF THEIR LIVES.
 *
 **************************************************************************/

class NP_ItemNaviEX extends NucleusPlugin
{
	function getName()
	{
		return 'Navigation Bar'; 
	}

	function getAuthor()
	{ 
		return 'nakahara21 + shizuki'; 
	}

	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:itemnaviex'; 
	}

	function getVersion()
	{
		return '0.993'; 
	}

	function getDescription()
	{
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory() . $language . '.php')) {
			include_once($this->getDirectory() . $language . '.php'); 
		} else {
			include_once($this->getDirectory() . 'english.php');
		}
		$description = _NP_INEX_DESC;
//		$description = 'Add link to prev item and next item. '
//					 . 'Usage: &lt;%ItemNaviEX%&gt; or &lt;%ItemNaviEX(0)%&gt;'
//					 . ' or &lt;%ItemNaviEX(1)%&gt; or  &lt;%ItemNaviEX(2)%&gt;';
		return $description;
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

	function scanEndKey($array)
	{
		$keys = array_keys($array);
		rsort($keys);
		return $keys[0];
	}

	function createNaviLink($unitArray)
	{
		if ($unitArray[1]) {
			$tempLink = '<a href="' . htmlspecialchars($unitArray[1], ENT_QUOTES, _CHARSET) . '">'
					  . htmlspecialchars($unitArray[0], ENT_QUOTES, _CHARSET) . '</a>';
		} else {
			$tempLink = htmlspecialchars($unitArray[0], ENT_QUOTES, _CHARSET);
		}
		return $tempLink;
	}

	function checkParent()
	{
		global $manager; 
//		if ($manager->pluginInstalled('NP_MultipleCategories')) {
//			$mplugin =& $manager->getPlugin('NP_MultipleCategories');
//			if (method_exists($mplugin,"getRequestName")) {
				$query = 'SHOW FIELDS FROM ' . sql_table('plug_multiple_categories_sub');
				$res   = sql_query($query);
				while ($co = mysql_fetch_assoc($res)) {
					if ($co['Field'] == 'parentid') {
						return TRUE;
					}
				}
//			}
//		}
	}

	function doSkinVar($skinType, $showHome = 1)
	{ 
		global $manager, $CONF, $blog, $itemid, $itemidprev, $itemidnext;
		global $catid, $subcatid, $archive, $archiveprev, $archivenext, $param; 

// sanitize
		$y = $m = $d = '';
		$itemid     = intval($itemid);
		$catid      = intval($catid);
		$subcatid   = intval($subcatid);
		$itemidprev = intval($itemidprev);
		$itemidnext = intval($itemidnext);
		if (isset($archive)) {
			sscanf($archive,'%d-%d-%d', $y, $m, $d);
			if ($y && $m && !empty($d)) {
				$archive = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} elseif ($y && $m && empty($d)) {
				$archive = sprintf('%04d-%02d',      $y, $m);
			}			
		}
		if (isset($archiveprev)) {
			sscanf($archiveprev,'%d-%d-%d', $y, $m, $d);
			if ($y && $m && !empty($d)) {
				$archiveprev = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} elseif ($y && $m && empty($d)) {
				$archiveprev = sprintf('%04d-%02d',      $y, $m);
			}			
		}
		if (isset($archiveprev)) {
			sscanf($archiveprev,'%d-%d-%d', $y, $m, $d);
			if ($y && $m && !empty($d)) {
				$archiveprev = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} elseif ($y && $m && empty($d)) {
				$archiveprev = sprintf('%04d-%02d',      $y, $m);
			}			
		}
		if ($showHome == '') {
			$showHome = 1;
		} else {
			$showHome = intval($showHome);
		}

		if ($catid) { 
			$blogid = getBlogIDFromCatID($catid);
			$b =& $manager->getBlog($blogid); 
		} elseif ($blog) { 
			$b =& $blog; 
		} else { 
			$b =& $manager->getBlog($CONF['DefaultBlog']); 
		} 
		$blogid = intval($b->getID());

		$abuf   = '';
		$mtable = '';
		$where .= ' and i.iblog=' . $blogid;
		if (!empty($catid)) {
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$where .= ' and ((i.inumber = p.item_id'
						. ' and (p.categories REGEXP "(^|,)' . $catid . '(,|$)"'
						. ' or i.icat = ' . $catid . '))'
						. ' or (i.icat = ' . $catid
						. ' and p.item_id IS NULL))';
				$mtable = ' LEFT JOIN ' . sql_table('plug_multiple_categories') . ' as p'
						. ' ON  i.inumber = p.item_id';
				$mplugin =& $manager->getPlugin('NP_MultipleCategories');
				if ($subcatid && method_exists($mplugin, 'getRequestName')) {
//family
					if ($this->checkParent()) {
						$Children = array();
						$Children = explode('/', $subcatid . $this->getChildren($subcatid));
					}
					if ($Children[1]) {
						for ($i=0;$i<count($Children);$i++) {
							$temp_whr[] = ' p.subcategories REGEXP "(^|,)' . intval($Children[$i]) . '(,|$)" ';
						}
						$where .= ' and ';
						$where .= ' ( ';
						$where .= join(' or ', $temp_whr);
						$where .= ' )';
					}else{
						$where .= ' and p.subcategories REGEXP "(^|,)' . $subcatid . '(,|$)"';
					}
//family end
				}
			} else {
				$where .= ' and i.icat=' . $catid;
			}
		}
		$naviUnit         = array();
		$subNaviUnit      = array();
		$this->linkparams = array();
		$nextLabel        = $this->getOption('PNNextLabel');
		$prevLabel        = $this->getOption('PNPrevLabel');
//store Home =====================================
//*// comment out this block when HOME is sub-blog top
      if ($showHome == 1) {
//         $defBlogid  =intval($CONF['DefaultBlog']);
         $tempBlog =& $manager->getBlog(intval($CONF['DefaultBlog']));
         $naviUnit[] = array(
            0 => 'Home',
//            1 => createBlogidLink($defBlogid),
            1 => $tempBlog->getURL(),
            2 => createArchiveListLink($blogid)
         );
      }
//*/
//store Blog =====================================
		if ($showHome == 1 && ($blogid <> $CONF['DefaultBlog'])) {
			$naviUnit[] = array(
				0 => getBlogNameFromID($blogid),
				1 => createBlogidLink($blogid),
				2 => createArchiveListLink($blogid)
			);
		} elseif ($showHome >= 2 || ($showHome == 0 && ($catid || $itemid))) {
			$naviUnit[] = array(
				0 => 'Home',                          // when HOME is sub-blog top
				1 => createBlogidLink($blogid),       // when HOME is sub-blog top
				2 => createArchiveListLink($blogid)
			);
		}

//store Category =====================================
		if (!empty($catid)) {
			$this->linkparams['catid'] = $catid;
			$naviUnit[] = array(
				0 => $b->getCategoryName($catid),
				1 => createCategoryLink($catid),
//				1 => createBlogidLink($blogid, $this->linkparams),
				2 => createArchiveListLink($blogid, $this->linkparams)
			);
		}

//store subCategory =====================================
		if (!empty($subcatid)) {
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$mplugin =& $manager->getPlugin('NP_MultipleCategories');
				if (method_exists($mplugin, 'getRequestName')) {
					$subrequest = $mplugin->getRequestName(array());
					$this->linkparams[$subrequest] = $subcatid;
					if ($this->checkParent()) {
						$tog = $this->getParenta($subcatid, $blogid);
						for ($i=0; $i<count($this->r); $i++) {
							$naviUnit[] = $this->r[$i];
						}
						$naviUnit[] = $tog;
					} else {
						$naviUnit[] = array(
							0 => $mplugin->_getScatNameFromID($subcatid),
							1 => createCategoryLink($catid, array($subrequest => $subcatid)),
//							1 => createCategoryLink($catid, array('subcatid' => $subcatid)),
//							1 => createBlogidLink($blogid, $this->linkparams),
							2 => createArchiveListLink($blogid, $this->linkparams)
						);
					}
				}
			}
		}

//store Page ===================================== todo How to get PageNo. ? ...cookie... 
		if (intRequestVar('page') != 0) {
			$naviUnit[] = array(
				0 => 'Page.' . intRequestVar('page'),
				2 => createArchiveListLink($blogid, $this->linkparams)
			);
		}

//store Item =====================================
		if ($skinType == 'item') {
			$item =& $manager->getItem($itemid, 0, 0);
			$naviUnit[] = array(
				0 => $item['title']
			);

			$query = 'SELECT i.ititle, i.inumber'
				   . ' FROM ' . sql_table('item') . ' as i' . $mtable
				   . ' WHERE i.idraft = 0'
				   . " and i.itime < '" . $item['itime'] . "' " . $where
				   . ' ORDER BY i.itime DESC'; 
			$res   = sql_query($query);
			if ($ares = mysql_fetch_row($res)) {
				$alink          = createItemLink($ares[1], $this->linkparams);
				$subNaviUnit[1] = '<a href="'
								. htmlspecialchars($alink, ENT_QUOTES, _CHARSET)
								. '" rel="prev"> ' . $prevLabel . ' '
								. shorten($ares[0], 14, '...')
								. '</a>';
			}

			$query = 'SELECT i.ititle, i.inumber'
				   . ' FROM ' . sql_table('item') . ' as i' . $mtable
				   . ' WHERE i.idraft = 0'
				   . " and i.itime > '" . $item['itime'] . "' " . $where
				   . ' ORDER BY i.itime ASC'; 
			$res   = sql_query($query);
			if ($ares = mysql_fetch_row($res)) {
				$alink          = createItemLink($ares[1], $this->linkparams);
				$subNaviUnit[2] = '<a href="'
								. htmlspecialchars($alink, ENT_QUOTES, _CHARSET)
								. '" rel="next"> '
								. shorten($ares[0], 14, '...')
								. ' ' . $nextLabel . '</a>';
			}

		}

//store ArchiveList =====================================
		if ($skinType == 'archivelist' || $skinType == 'archive') {
			$naviUnit[] = array(
				0 => $this->getOption('archivelinkLabel'),
//				0 => 'ArchiveList',
				1 => createArchiveListLink($blogid, $this->linkparams)
			);
		}

		if ($skinType == 'archive') {
			sscanf($archive,'%d-%d-%d', $y, $m, $d);
//store ArchiveMonth
			$archiveMonth = $y . '-' . $m;
				$naviUnit[] = array(
					0 => $archiveMonth,
					1 => createArchiveLink($blogid, $archiveMonth, $this->linkparams)
				);
			if (empty($d)) {
				$timestamp_start = mktime(0, 0, 0, $m,   1, $y);
				$timestamp_end   = mktime(0, 0, 0, $m+1, 1, $y);
				$date_str        = 'SUBSTRING(i.itime, 1, 7)';
			} else {
				$timestamp_start = mktime(0, 0, 0, $m, $d,   $y);
				$timestamp_end   = mktime(0, 0, 0, $m, $d+1, $y);
				$date_str        = 'SUBSTRING(i.itime, 1, 10)';
//store ArchiveDay
				$naviUnit[] = array(
					0 => $y . '-' . $m . '-' . $d,
					1 => createArchiveLink($blogid, $archive, $this->linkparams)
				);
			}

//=============================
			$query = 'SELECT ' . $date_str . ' as Date'
				   . ' FROM ' . sql_table('item') . ' as i' . $mtable
				   . ' WHERE i.idraft = 0'
				   . ' and i.itime < ' . mysqldate($timestamp_start) . $where
				   . ' GROUP BY Date'
				   . ' ORDER BY i.itime DESC'; 
			$res   = sql_query($query);
			if ($ares = mysql_fetch_row($res)) {
//				$prev_date = $ares[0];
				sscanf($ares[0], '%d-%d-%d', $y, $m, $d);
				if (empty($d)) {
					$prev_date = sprintf('%04d-%02d',      $y, $m);
				} else {
					$prev_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
				}
				$prev_alink = createArchiveLink($blogid, $prev_date, $this->linkparams);
				$subNaviUnit[1] = '<a href="'
								. htmlspecialchars($prev_alink, ENT_QUOTES, _CHARSET)
								. '" class="prevlink" rel="prev">'
								. ' ' . $prevLabel . ' '
								. htmlspecialchars($prev_date, ENT_QUOTES, _CHARSET)
								. '</a>';
//				$abuf .= '<a href="'.$prev_alink.'" class="prevlink" rel="prev">'.$prev_date.'</a>';
//			} else {
//				$today_link = createBlogidLink($b->getID(), $this->linkparams);
//				$abuf .= '  ( <a href="'.$today_link.'">Today</a> )';
			}
			$abuf .= ' | <strong>' . htmlspecialchars($archive, ENT_QUOTES, _CHARSET) . '</strong> ';
//=============================
			$query = 'SELECT ' . $date_str . ' as Date'
				   . ' FROM ' . sql_table('item') . ' as i' . $mtable
				   . ' WHERE i.idraft = 0'
				   . ' and i.itime < ' . mysqldate($b->getCorrectTime())
				   . ' and i.itime >= ' . mysqldate($timestamp_end) . $where
				   . ' GROUP BY Date'
				   . ' ORDER BY i.itime ASC'; 
			$res   = sql_query($query);
			if ($ares = mysql_fetch_row($res)) {
//				$next_date = $ares[0];
				sscanf($ares[0],'%d-%d-%d', $y, $m, $d);
				if (empty($d)) {
					$next_date = sprintf('%04d-%02d',      $y, $m);
				} else {
					$next_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
				}
				$next_alink     = createArchiveLink($blogid, $next_date, $this->linkparams);
				$subNaviUnit[2] = '<a href="'
								. htmlspecialchars($next_alink, ENT_QUOTES, _CHARSET)
								. '" class="nextlink" rel="next">'
								. htmlspecialchars($next_date, ENT_QUOTES, _CHARSET)
								. ' ' . $nextLabel . '</a>';
//				$a2buf = ' | <a href="'.$next_alink.'" class="nextlink" rel="next">'.$next_date.'</a>';
//			} else {
//				$today_link = createBlogidLink($b->getID(), $this->linkparams);
//				$a2buf .= ' | ( <a href="'.$today_link.'">Today</a> )';
			}
		}
//============================= // end of archive(s)

// Print subNavi
// todo Henceforth to template
//		echo ' <div style="text-align: center;">';
//		echo ' <span style="text-align:right;">';
//		echo ' <span class="prevnextnavi">';
		$endKey = $this->scanEndKey($naviUnit);
//		if ($skinType != 'archivelist' && $skinType != 'archive' && $skinType != 'item') {
//			echo '<a href="' . $naviUnit[$endKey][2] . '">&raquo; ArchiveList</a>';
//		}
//		echo @join(' :: ', $subNaviUnit);
//		echo '</div>';
//		echo '</span>';
///

/*
<%subnavi%>     --> prev. and next link
<%archivedata%> --> ArchiveList link
*/

		$subNaviData['subnavi'] = implode($this->getOption('PNSeparator'), $subNaviUnit);
		if ($skinType != 'archivelist' && $skinType != 'archive' && $skinType != 'item') {
			if (!empty($naviUnit)) {
				$subNaviData['archivedata'] .= '<a href="'
										 . $naviUnit[$endKey][2]
										 . '">' . $this->getOption('archivelinkLabel') . '</a>';
			}
		}
		$subnaviTemplate              = $this->getOption('subnaviTemplate');
		$navigateData['subnaviblock'] = TEMPLATE::fill($subnaviTemplate, $subNaviData);
//		echo TEMPLATE::fill($subnaviTemplate, $subNaviData);
//*/
// Print mainNavi
		unset($naviUnit[$endKey][1]);
		$naviVar = array_map(array(&$this, 'createNaviLink'), $naviUnit);

//		echo '<span class="breadcrumbslist">', @join(' &gt; ', $naviVar);

		$topicPathData['topicpath'] = implode($this->getOption('topicPathSeparator'), $naviVar);

//add Taginfo =====================================
// display selected TAGs whith link mod by shizuki
		if ($manager->pluginInstalled('NP_TagEX')) {
//			if (requestVar('tag')) {
			$tagPlugin =& $manager->getPlugin('NP_TagEX');
			$requestT  =  $tagPlugin->getNoDecodeQuery('tag');
			if (!empty($requestT)) {
				$requestTarray = $tagPlugin->splitRequestTags($requestT);
				$reqAND        = array_map(array(&$tagPlugin, "_rawdecode"), $requestTarray['and']);
				if ($requestTarray['or']) {
					$reqOR = array_map(array(&$tagPlugin, "_rawdecode"), $requestTarray['or']);
				}
				if ($reqOR) {
					$reqTags = array_merge($reqAND, $reqOR);
				} else {
					$reqTags = $reqAND;
				}
				for ($i=0;$i<count($reqTags);$i++) {
					$tag = trim($reqTags[$i]);
					$taglist[$i] = '<a href="' . $tagPlugin->creatTagLink($tag, 0) . '">'
								 . htmlspecialchars($tag, ENT_QUOTES, _CHARSET) . '</a>';
				}
//				echo ' <small style="font-family:Tahoma;">';
//				echo ' (Tag for "'.$tagPlugin->_rawdecode(requestVar('tag')).'")';
//				echo ' (Tag for "' . @join(' / ', $taglist) . '")';
//				echo '</small>';
/*
<%tags%> --> selected TAGs with own link
*/
				$tagsData['tags']         = implode(' / ', $taglist);
				$tagListTemplate          = $this->getOption('tagListTemplate');
				$topicPathData['taglist'] = TEMPLATE::fill($tagListTemplate, $tagsData);
//*/
			}
		}
/*
<%topicpath%> --> topicpath
<%taglist%>   --> selected TAG list
*/
		$topicPathTemplate              = $this->getOption('topicPathTemplate');
		$navigateData['topicpathblock'] = TEMPLATE::fill($topicPathTemplate, $topicPathData);
//		echo TEMPLATE::fill($topicPathTemplate, $topicPathData);
		$navigateBlockTemplate          = $this->getOption('ItemNaviTemplate');
/*
<%subnaviblock%>
<%topicpathblock%>
*/
		echo TEMPLATE::fill($navigateBlockTemplate, $navigateData);
//*/
//		echo '</span>';

	}

    function getParenta($subcat_id, $blogid=0)
    {
    	$subcat_id = intval($subcat_id);
    	$blogid    = intval($blogid);
    	$r         = array();
    	$que       = 'SELECT scatid, parentid, sname, catid FROM %s WHERE scatid = %d';
    	$que       = sprintf($que, sql_table('plug_multiple_categories_sub'), $subcat_id);
    	$res       = sql_query($que);
        list ($sid, $parent, $sname, $cat_id) = mysql_fetch_row($res);
		if (intval($parent) != 0) {
			$this->r[]                  =  $this->getParenta(intval($parent), $blogid);
			$this->linkparams[subcatid] = $sid;
			$r =  array(
				0 => $sname,
				1 => createBlogidLink($blogid, $this->linkparams),
				2 => createArchiveListLink($blogid, $this->linkparams)
			);
		} else {
			$this->linkparams[subcatid] = $sid;
			$r =  array(
				0 => $sname,
				1 => createBlogidLink($blogid, $this->linkparams),
				2 => createArchiveListLink($blogid, $this->linkparams)
			);
		}
        return $r;
    }

	function getParent($subcat_id)
    {
    	$subcat_id = intval($subcat_id);
    	$que       = 'SELECT scatid, parentid, sname FROM %s WHERE scatid = %d';
    	$que       = sprintf($que, sql_table('plug_multiple_categories_sub'), $subcat_id);
    	$res       = sql_query($que);
        list ($sid, $parent, $sname) = mysql_fetch_row($res);
        if (intval($parent) != 0) {
        	$r = $this->getParent(intval($parent)) . " -> <a href=$subcat_id>$sname</a>";
        } else {
        	$r = "<a href=$subcat_id>" . htmlspecialchars($sname) . "</a>";
    	}
        return $r;
    }

    function getChildren($subcat_id)
    {
    	$subcat_id = intval($subcat_id);
    	$que       = 'SELECT scatid, parentid, sname FROM %s WHERE parentid = %d';
    	$que       = sprintf($que, sql_table('plug_multiple_categories_sub'), $subcat_id);
    	$res       = sql_query($que);
		while ($so =  mysql_fetch_object($res)) {
			$r .= $this->getChildren($so->scatid) . '/' . intval($so->scatid);
		}
        return $r;
    }

	function install()
	{
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory() . $language . '.php')) {
			include_once($this->getDirectory() . $language . '.php'); 
		} else {
			include_once($this->getDirectory() . 'english.php');
		}
		$sub  = '<div style="text-align:right;"><%archivedata%><%subnavi%></div>';
		$path = '<div style="text-align:left;"><%topicpath%><%taglist%></div>';
		$tags = ' (Selected TAG(s) : <small style="font-family:Tahoma;"><%tags%></small>)';
		$navi = '<div class="itemnaviex"><%subnaviblock%><%topicpathblock%></div>';
		$this->createOption('subnaviTemplate',    _INEXOP_SUB,  'text', $sub);
		$this->createOption('PNSeparator',        _INEXOP_PNSP, 'tect', ' :: ');
		$this->createOption('PNNextLabel',        _INEXOP_PNNX, 'tect', '&raquo;');
		$this->createOption('PNPrevLabel',        _INEXOP_PNPR, 'tect', '&laquo;');
		$this->createOption('topicPathTemplate',  _INEXOP_PATH, 'text', $path);
		$this->createOption('topicPathSeparator', _INEXOP_PSEP, 'text', ' &gt; ');
		$this->createOption('tagListTemplate',    _INEXOP_TAGS, 'text', $tags);
		$this->createOption('ItemNaviTemplate',   _INEXOP_NAVI, 'text', $navi);
		$this->createOption('archivelinkLabel',   _INEXOP_ALBL, 'text', '&raquo; ArchiveList');
	}

}
