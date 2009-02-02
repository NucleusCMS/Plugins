<?php 
/**
 *
 * SHOWING BLOGS PLUG-IN FOR NucleusCMS
 * PHP versions 4 and 5
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * @author    Original Author nakahara21
 * @copyright 2005-2006 nakahara21
 * @license   http://www.gnu.org/licenses/gpl.txt  GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 * @version   2.71
 * @link      http://japan.nucleuscms.org/wiki/plugins:showblogs
 *
 * 2.71     fix typo
 * 2.7      add doIf function requier Nucleus version 3.3 or later
 *              ex. <%ifnot(ShowBlogs,page)%>top page contents<%else%>other pages contents<%endif%>
 *              ex. <%if(ShowBlogs,cstik|bmode|stick|amont|tmplt,1|all|23|5|myTemplate)%><%endif%>
 *               is <%ShowBlogs(myTemplate,5,all,,,23,,,,1)%>
 * 2.66.4.1 cahnge prev/next pagelink label class <span class="npsb_prevlink"></span>
 *                 and page label to BlogOption
 * 2.66.4   fix catformat
 * 2.66.3   fix display offset
 * 2.66.2   fix display Item when $q_amount=0
 * 2.66.1   fix sticky mode
 * 2.66     default argument bug fix
 * 2.65     add AD code control
 *          add Category mode
 *          fix stickies bug
 * 2.64     fix page switch URL generate
 * 2.62     security fix and tag related
 * 2.61     security fix
 * 2.6      security fix
 *
 ****************************************************************************
 *
 * THESE PLUG-INS ARE DEDICATED TO ALL THOSE NucleusCMS USERS
 * WHO FIGHT CORRUPTION AND IRRATIONAL IN EVERY DAY OF THEIR LIVES.
 *
 ****************************************************************************/

class NP_ShowBlogs extends NucleusPlugin
{

	function getName()
	{
		return 'Show Blogs';
	}

	function getMinNucleusVersion()
	{
		return '322';
	}

	function getAuthor()
	{
		return 'Taka + nakahara21 + kimitake + shizuki';
	}

	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:showblogs';
	}

	function getVersion()
	{
		return '2.7';
	}

	function getDescription()
	{
		return _SHOWB_DESC; 
	} 

	function supportsFeature($what)
	{
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		return array(
			'InitSkinParse'
		);
	}

	function init()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory()  . $language . '.php')) {
			include_once($this->getDirectory() . $language . '.php');
		}else {
			include_once($this->getDirectory() . 'english.php');
		}
	}

	function install()
	{
		$this->createOption('catformat',     _CAT_FORMAT, 'text',    '<%category%> on <%blogname%>');
		$this->createOption('stickmode',     _STICKMODE,  'select',   '1', _STICKSELECT);
		$this->createOption('ads',           _ADCODE_1,   'textarea', '' . "\n");
		$this->createOption('ads2',          _ADCODE_2,   'textarea', '' . "\n");
		$this->createOption('tagMode',       _TAG_MODE,   'select',   '2', _TAG_SELECT);
		$this->createBlogOption('nextLabel', _SB_NEXTL,   'text',     'Next&raquo;');
		$this->createBlogOption('prevLabel', _SB_PREVL,   'text',     '&laquo;Prev');
/* todo can't install ? only warning ?
 * douyatte 'desc' ni keikoku wo daseba iinoka wakaranai desu
		$ver_min = (getNucleusVersion() < $this->getMinNucleusVersion());
		$pat_min = ((getNucleusVersion() == $this->getMinNucleusVersion()) &&
				(getNucleusPatchLevel() < $this->getMinNucleusPatchLevel()));
		if ($ver_min) {	// || $pat_min) {
			global $DIR_LIBS;
			// uninstall plugin again...
			include_once($DIR_LIBS . 'ADMIN.php');
			$admin = new ADMIN();
			$admin->deleteOnePlugin($this->getID());
		
			// ...and show error
			$admin->error(_ERROR_NUCLEUSVERSIONREQ .
			$this->getMinNucleusVersion() . ' patch ' .
			$this->getMinNucleusPatchLevel());
		}
*/
// </mod by shizuki>
	}

	function doSkinVar($skinType,
					   $template      = 'default/index',
					   $amount        = 10,              // amount/page
					   $bmode         = '',              // show or hide Blogs
					   $type          = 1,               // pagw switch type
					   $sort          = 'DESC',          // item sort mode (DESC or ASC)
					   $sticky        = '',              // sticky item id
					   $sticktemplate = '',
					   $catmode       = 'all',           // show or hide categories
					   $showAdCode    = 1,               // AdCode switch
					   $catStick      = 0                // show sticky item when category selected ?
					  )
	{
		global $manager;
		if ($skinType == 'item' && !$manager->pluginInstalled('NP_TagEX')) {
			return;
		}
		global $CONF, $blog, $blogid, $catid, $itemid, $archive, $subcatid;

		if (!$template) {
			$template = 'default/index';
		}

// initialize hide blogID
		$hide = array();
// initialize show blogID
		$show = array();
// limit number of pages(months) 
		$pagelimit  = 0;
		$monthlimit = 0;
		$catformat  = $this->getOption('catformat');

		$params = func_get_args();
		switch ($amount) {
			case '0':
				$type = $amount;
				break;
			case 'all':
				$bmode = $amount;
				if (is_numeric($params[3]) ||is_float($params[3])) {
					$type = $params[3];
				}
				break;
		}
		if (preg_match("/^(<>)?([0-9\/]+)$/", $bmode, $matches)) {
			if ($matches[1]) {
				$hide = explode("/", $matches[2]);
				$show = array();
			} else {
				$hide = array();
				$show = explode("/", $matches[2]);
			}
			$bmode = 'all';
		}

		$type             = (float) $type;
		$typeExp          = intval(($type - floor($type))*10); //0 or 1 or 9
		$this->showAdCode = $showAdCode;

		list ($pageamount, $offset) = sscanf($amount, '%d(%d)');
		if (!$pageamount) {
			$pageamount = 10;
		}

		if ($sort != 'ASC') {
			$sort = 'DESC';
		}

/*		if ($sort != 'ASC' && $sort != 'DESC') {
			$sticktemplate = $sticky;
			$sticky        = $sort;
			$sort          = 'DESC';
		}*/

		if (!empty($sticky) && empty($sticktemplate)) {
			$sticktemplate = $template;
		}

		if (preg_match("/^(<>)?([0-9\/]+)$/", $catmode, $matches)) {
			if ($matches[1]) {
				$hideCat = explode("/", $matches[2]);
				$showCat = array();
			} else {
				$hideCat = array();
				$showCat = explode("/", $matches[2]);
			}
			$catmode = 'all';
		}

		if (!$template) {
			$template = 'default/index';
		}
		if (!$amount) {
			$amount = 10;
		}
		if (!isset($type)) {
			$type = 1;
		}
		if (!$sort) {
			$sort = 'DESC';
		}
		if (!$showAdCode) {
			$showAdCode = 1;
		}
		if (!$catStick) {
			$catStick = 0;
		}

		if ($blog) {
			$b =& $blog; 
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$this->nowbid = $nowbid = intval($b->getID());

		$where       = '';
		$catblogname = 0;

		if ($bmode != 'all') {
			$where .= ' AND i.iblog = ' . $nowbid;
		} elseif (isset($hide[0]) && $bmode == 'all') {
			foreach ($hide as $val) {
				if (!is_numeric($val)) {
					$val = getBlogIDFromName($val);
				}
				$where .= ' AND i.iblog != ' . intval($val);
			}
		} elseif (isset($show[0]) && $bmode == 'all') {
			foreach ($show as $val) {
				if (!is_numeric($val)) {
					$val = getBlogIDFromName($val);
				}
				$w[] = intval($val);
			}
			$where .= (count($w) > 0) ? ' AND i.iblog in (' . implode(',', $w) . ')' : '';
		}

		if (isset($hideCat[0]) && $catmode == 'all') {
			foreach($hideCat as $val){
				if(is_numeric($val)){
					$where .= ' AND i.icat != ' . intval($val);
				}
			}
		} elseif (isset($showCat[0]) && $catmode == 'all') {
			foreach ($showCat as $val) {
				if (is_numeric($val)) {
					$w[] = intval($val);
				}
			}
			$where .= (count($w) > 0) ? ' AND i.icat in (' . implode(',', $w) . ')' : '';
			$mcats = $w;
		}
		if ($bmode == 'all') {
			$catblogname = 1;
		}
//		echo $bmode;

		if ($skinType == 'item' || $skinType == 'index' || $skinType == 'archive') {
			$catformat = '"' . addslashes($catformat) . '"';
			$nArr      = array(
							   '",c.cname,"',
							   '",b.bname,"',
							   '",c.cdesc,"'
							  );
			$fArr      = array(
							   '/<%category%>/',
							   '/<%blogname%>/',
							   '/<%catdesc%>/'
							  );
			$catformat = preg_replace($fArr, $nArr, $catformat);
			$mtable    = "";
			if ($manager->pluginInstalled('NP_TagEX')) {
				$t_where = $this->_getTagsInum($where, $skinType, $bmode, $amount);
				$where .= $t_where['where'];
			}

			if ($skinType == 'item') {
				$where .= ' and i.inumber != ' . intval($itemid);
			} else {

				$sticCatFlag = (!$catid || (!empty($catStick) && $sticktemplate != ''));
//				if (!$catid && $sticky != '') {
				if ($sticCatFlag && $sticky != '') {
					$stickys = explode('/',  $sticky);
					foreach ($stickys as $stickynumber) {
						$where .= ' AND i.inumber <> ' . intval($stickynumber);
					}
				}

//				$hidden = '';
				$temp = $y = $m = $d = '';
				if ($archive) {
					sscanf($archive, '%d-%d-%d', $y, $m, $d);
					if ($d) {
						$timestamp_start = mktime(0, 0, 0, $m, $d,   $y);
						$timestamp_end   = mktime(0, 0, 0, $m, $d+1, $y);
						$date_str        = 'SUBSTRING(i.itime, 1, 10)';
					} else {
						$timestamp_start = mktime(0, 0, 0, $m,   1, $y);
						$timestamp_end   = mktime(0, 0, 0, $m+1, 1, $y);
						$date_str        = 'SUBSTRING(i.itime,1,7)';
					}
					$where .= ' AND i.itime >= ' . mysqldate($timestamp_start)
							. ' AND i.itime < ' . mysqldate($timestamp_end);
				} elseif (!empty($monthlimit)) {
					$timestamp_end   = mysqldate($b->getCorrectTime());
					sscanf($timestamp_end, '"%d-%d-%d %s"', $y, $m, $d, $temp);
					$timestamp_start = mktime(0, 0, 0, $m-$monthlimit, $d, $y);
					$where .= ' AND i.itime >= ' . mysqldate($timestamp_start)
							. ' AND i.itime <= ' . $timestamp_end;
				} else {
					$where .= ' AND i.itime <= ' . mysqldate($b->getCorrectTime());
				}

				if (!empty($catid)) {
					if ($manager->pluginInstalled('NP_MultipleCategories')) {
						$mcat_query = $this->_getSubcategoriesWhere($catid);
						$mtable     = $mcat_query['m'];
						$where     .= $mcat_query['w'];
					} else {
						$where     .= ' AND i.icat=' . intval($catid);
					}
					$linkparams['catid'] = $todayparams['catid'] = intval($catid);
				}

				if ($type >= 1) {
					$page_switch = $this->PageSwitch($type, $pageamount, $offset, $where, $sort, $mtable);
					if ($typeExp != 9 && $skinType != 'item') {
						echo $page_switch['buf'];
					}
				}
			}

			$sh_query = 'SELECT '
					  . 'i.inumber               as itemid, '
					  . 'i.ititle                as title, '
					  . 'i.ibody                 as body, '
					  . 'm.mname                 as author, '
					  . 'm.mrealname             as authorname, '
					  . 'UNIX_TIMESTAMP(i.itime) as timestamp, '
					  . 'i.itime, '
					  . 'i.imore                 as more, '
					  . 'm.mnumber               as authorid,';
			if (!$catblogname) {
				$sh_query .= ' c.cname as category,';
			} else {
				$sh_query .= ' concat(' . $catformat . ') as category,';
			}
			$sh_query .= ' i.icat    as catid,'
					   . ' i.iclosed as closed'
					   . ' FROM '
					   . sql_table('member') .   ' as m, '
					   . sql_table('category') . ' as c, '
					   . sql_table('item') .     ' as i'
					   . $mtable;
			if ($bmode == 'all') {
				$sh_query .= ', ' . sql_table('blog') . ' as b ';
			}
			$sh_query .= ' WHERE i.iauthor = m.mnumber'
					   . ' AND   i.icat    = c.catid';
			if ($bmode == 'all') {
				$sh_query .= ' AND b.bnumber = c.cblog';
			}

//			if ($page_switch['startpos'] == 0 && !$catid && $sticky != '' && $skinType != 'item' && !$this->tagSelected) {
			$ads         = 0;
			$sticCatFlag = ($page_switch['startpos'] == 0 && (!$catid || (!empty($catStick) && $sticktemplate != '')));
			if ($sticCatFlag && $sticky != '' && $skinType != 'item' && !$this->tagSelected) {
				foreach ($stickys as $stickynumber) {
					$sticky_query = $sh_query;
					$tempblogid   = getBlogIDFromItemID($stickynumber);
					if ($bmode != 'all' && $this->getOption('stickmode') == 1) {
						$sticky_query .= ' AND i.iblog = ' . $nowbid;
					}
					$sticky_query .= ' AND i.inumber = ' . intval($stickynumber)
								   . ' AND i.itime  <= ' . mysqldate($b->getCorrectTime())
								   . ' AND i.idraft  = 0';
					if ($catid) {
						$sticky_query .= ' AND i.icat = ' . intval($catid);
					}
					if ($subcatid) {
						$sticky_query .= ' AND p.subcategories = ' . intval($subcatid);
					}
/*					$sticky_query .= $stickWhere;
					if ($bmode == 'all') {
						$b->showUsingQuery($sticktemplate, $sticky_query, 0, 1, 0); 
					} elseif ($this->getOption('stickmode') == 1 && intval($nowbid) == $tempblogid) {
						$b->showUsingQuery($sticktemplate, $sticky_query, 0, 1, 0); 
					} elseif (!$this->getOption('stickmode')) {
						$b->showUsingQuery($sticktemplate, $sticky_query, 0, 1, 0); 
					}*/

					if (
					    ($bmode == 'all') ||
						($this->getOption('stickmode') == 1 && intval($nowbid) == $tempblogid) ||
						(!$this->getOption('stickmode'))
					   ) {
						$b->showUsingQuery($sticktemplate, $sticky_query, 0, 1, 0); 
					}

					//echo $stickynumber;
					if ($showAdCode > 0 && mysql_num_rows(sql_query($sticky_query))) {
						if ($ads == 0) {
							echo $this->getOption('ads');
						} elseif ($ads == 1) {
							echo $this->getOption('ads2');
						} elseif ($ads >= 2) {
						}
						$ads++;
					}
				}
			}

			$sh_query .= ' AND i.idraft = 0' . $where;

			if ($skinType == 'item') {
				$sh_query .= ' ORDER BY FIND_IN_SET(i.inumber,\'' . @join(',', $t_where['inumsres']) . '\')';
			} else {
				$sh_query .= ' ORDER BY i.itime ' . $sort;
			}

			if ($skinType != 'item') {
				$pStartPos = $page_switch['startpos'];
				if ($offset && $type < 1) {
					$pStartPos += intval($offset);
				}
				$this->_showUsingQuery($template, $sh_query, $pStartPos, $pageamount, $b, $ads);
				if ($type >= 1 && $typeExp != 1) echo $page_switch['buf'];
			} elseif ($skinType == 'item') {
				$sh_query .= ' LIMIT 0, ' . $pageamount;
				$b->showUsingQuery($template, $sh_query, 0, 1, 1); 
			}
		}
	}

	function _showUsingQuery($template, $showQuery, $q_startpos, $q_amount, $b, $ads)
	{
		global $catid;
		$onlyone_query = $showQuery . ' LIMIT ' . intval($q_startpos) .', 1';
		$b->showUsingQuery($template, $onlyone_query, 0, 1, 1);
		if (intval($ads) == 0 && $this->showAdCode > 0) {
			echo $this->getOption('ads');
//		}
//------------SECOND AD CODE-------------
		} elseif (intval($ads) == 1 && $this->showAdCode > 0) {
			echo $this->getOption('ads2');
		}
		$q_startpos++;
		$q_amount--;
		if ($q_amount <= 0) return;
		$onlyone_query = $showQuery . ' LIMIT ' . intval($q_startpos) . ', 1';
		$b->showUsingQuery($template, $onlyone_query, 0, 1, 1); 
		if (mysql_num_rows(sql_query($onlyone_query)) && empty($ads) && $this->showAdCode > 0) {
			echo $this->getOption('ads2');
		}
//------------SECOND AD CODE END-------------
		$q_startpos++;
		$q_amount--;
		if ($q_amount <= 0) return;
		$second_query = $showQuery . ' LIMIT ' . intval($q_startpos) . ',' . intval($q_amount);
		$b->showUsingQuery($template, $second_query, 0, 1, 1);
	}

	function event_InitSkinParse($data)
	{
		global $CONF, $manager;
		$this->skintype = $data['type'];
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		if (serverVar('REQUEST_URI') == '') {
			$uri = (serverVar('QUERY_STRING')) ?
				serverVar('SCRIPT_NAME') . serverVar('QUERY_STRING') : serverVar('SCRIPT_NAME');
		} else { 
			$uri = serverVar('REQUEST_URI');
		}
		$page_str = ($usePathInfo) ? 'page/' : 'page=';
		if ( $manager->pluginInstalled('NP_CustomURL') ||
			 $manager->pluginInstalled('NP_Magical') ||
			 $manager->pluginInstalled('NP_MagicalURL2') ) {
			$page_str = 'page_';
		}
		if (strpos($uri, 'page/')) {
			list($org_uri, $currPage) = explode('page/', $uri, 2);
		} elseif (strpos($uri, 'page_')) {
			list($org_uri, $currPage) = explode('page_', $uri, 2);
		}
//		list($org_uri, $currPage) = explode($page_str, $uri, 2);
		if (getVar('page')) {
			$currPage = intGetVar('page');
		}
		$_GET['page']   = intval($currPage);
		$this->currPage = intval($currPage);
		$this->pagestr  = $page_str;
	}

	function PageSwitch($type, $pageamount, $offset, $where, $sort, $mtable = '')
	{
		global $CONF, $manager, $archive, $catid, $subcatid;

// initialize
		$startpos    = 0;
		$catid       = intval($catid);
		$subcatid    = intval($subcatid);
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		$pageamount  = intval($pageamount);
		$offset      = intval($offset);
		if ($archive) {
			$y = $m = $d = '';
			sscanf($archive, '%d-%d-%d', $y, $m, $d);
			if (!empty($d)) {
				$archive = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} else {
				$archive = sprintf('%04d-%02d', $y, $m);
			}
		}

		$page_str    = $this->pagestr;
		$currentpage = $this->currPage; 

// createBaseURL
		if (!empty($catid)) {
			$catrequest = ($usePathInfo) ? $CONF['CategoryKey'] : 'catid';
			if (!empty($subcatid)) {
				$mplugin =& $manager->getPlugin('NP_MultipleCategories');
				$subrequest = $mplugin->getRequestName(array());
				if (!empty($archive)) {
					$linkParam = array(
									   $catrequest => $catid,
									   $subrequest => $subcatid
									  );
					$pagelink  = createArchiveLink($archive, $linkParam);
				} else {
					$linkParam = array(
									   $subrequest => $subcatid
									  );
					$pagelink  = createCategoryLink($catid, $linkParam);
				}
			} else {
				if (!empty($archive)) {
					$linkParam = array(
									   $catrequest => $catid,
									  );
					$pagelink  = createArchiveLink($archive, $linkParam);
				} else {
					$pagelink  = createCategoryLink($catid);
				}
			}
		} else {
			if (!empty($archive)) {
				$pagelink = createArchiveLink($this->nowbid, $archive);
			} else {
				$pagelink = createBlogidLink($this->nowbid);
			}
		}
		if ($manager->pluginInstalled('NP_TagEX')) {
			$tplugin =& $manager->getPlugin('NP_TagEX');
			$requestTag = $tplugin->getNoDecodeQuery('tag');
			if (!empty($requestTag)) {
				$requestTarray = $tplugin->splitRequestTags($requestTag);
				$tag = array_shift($requestTarray['and']);
				$tag = $tplugin->_rawdecode($tag);
				if (!empty($requestTarray['and'])) {
					$requestT = implode('+', $requestTarray['and']);
				}
				if (!empty($requestTarray['or'])) {
					$requestTor = implode(':', $requestTarray['or']);
				}
				if (!empty($requestT)) {
					if (!empty($requestTor)) {
						$reqtags  = $requestT . ':' . $requestTor;
						$pagelink = $tplugin->creatTagLink($tag, $this->getOption('tagMode'), $reqtags, '+');
					} else {
						$pagelink = $tplugin->creatTagLink($tag, $this->getOption('tagMode'), $requestT, '+');
					}
				} elseif (empty($requestT) && !empty($requestTor)) {
					$pagelink = $tplugin->creatTagLink($tag, $this->getOption('tagMode'), $requestTor, ':');
				} else {
					$pagelink = $tplugin->creatTagLink($tag, $this->getOption('tagMode'));
				}
			}
		}

		$uri = parse_url($pagelink);
		if (!$usePathInfo) {
			if ($pagelink == $CONF['BlogURL']) { // add
				$pagelink .= '?';
			} elseif ($uri['query']) {
				$pagelink .= '&amp;';
			}
			$pagelink = str_replace('&amp;&amp;', '&amp;', $pagelink);
		} elseif ($usePathInfo && substr($pagelink, -1) != '/') {
			if ($uri['query']) {
				$pagelink .= '&amp;';
				$page_str  = 'page=';
			} else {
				$pagelink .= '/';
			}
		}
		if (strstr ($pagelink, '//')) {
			$pagelink = preg_replace("/([^:])\/\//", "$1/", $pagelink);
		}
		if (substr($pagelink, -5) == '.html') {
			$pagelink = substr($pagelink, 0, -5) . '_';
		}

		if ($currentpage > 0) {
			$startpos = ($currentpage - 1) * $pageamount;
		} else {
			$currentpage = 1;
		}

		$totalamount = 0;
		if (is_numeric($where)) {
			$totalamount = $where;
		} elseif (is_array($where)) {
			$totalamount = count($where);
		} else {
			$p_query = 'SELECT COUNT(i.inumber) FROM %s as i%s WHERE i.idraft = 0%s';
			$p_query = sprintf($p_query, sql_table('item'), $mtable, $where);
//			$p_query = 'SELECT COUNT(i.inumber) FROM ' . sql_table('item') . ' as i' . $mtable . ' WHERE i.idraft=0' . $where;
			$entries = sql_query($p_query);
			if ($row = mysql_fetch_row($entries)) {
				$totalamount = $row[0];
			}
		}
		$totalamount = intval($totalamount);
		if (!$archive && !empty($pagelimit) && ($pagelimit * $pageamount < $totalamount)) {
			$totalamount = intval($pagelimit) * $pageamount;
		}
		if ($offset) {
			$startpos += $offset;
			$totalamount -= $offset;
		}
		if ($this->maxamount && $this->maxamount < $totalamount) {
			$totalamount = intval($this->maxamount);
		}
		$totalpages = ceil($totalamount / $pageamount);
		$totalpages = intval($totalpages);
		if ($startpos > $totalamount) {
			$currentpage = $totalpages;
			$startpos    = $totalamount - $pageamount;
		}
		$prevpage      = ($currentpage > 1) ? $currentpage - 1 : 0;
		$nextpage      = $currentpage + 1;
		$firstpagelink = $pagelink . $page_str . '1';
		if ($page_str == 'page_') {
			$firstpagelink .= '.html';
		}
		$lastpagelink = $pagelink . $page_str . $totalpages;
		if ($page_str == 'page_') {
			$lastpagelink .= '.html';
		}
		$nextLinkLabel = $this->getBlogOption($this->nowbid, 'nextLabel') ? $this->getBlogOption($this->nowbid, 'nextLabel') : 'Next&raquo;';
		$prevLinkLabel = $this->getBlogOption($this->nowbid, 'prevLabel') ? $this->getBlogOption($this->nowbid, 'prevLabel') : '&laquo;Prev';

		if ($type >= 1) {
			$buf .= '<div class="pageswitch">' . "\n";
//			$buf .= "<a rel=\"first\" title=\"first page\" href=\"{$firstpagelink}\">&lt;TOP&gt;</a> | \n";
			if (!empty($prevpage)) {
				$prevpagelink = $pagelink . $page_str . $prevpage;
				if ($page_str == 'page_') {
					$prevpagelink .= '.html';
				}
				$buf .= '<a href="' . $prevpagelink . '" title="Previous page" rel="Prev">'
					  . '<span class="npsb_prevlink">' . $prevLinkLabel . '</span></a> |';
			} elseif ($type >= 2) {
				$buf .= $prevLinkLabel . " |";
			}
			if (intval($type) == 1) {
				$buf .= "\n";
			}
			if (intval($type) == 2) {
				$sepstr = '&middot;';
				$buf   .= "|";
				for ($i=1; $i<=$totalpages; $i++) {
					$i_pagelink = $pagelink . $page_str . $i;
					if ($page_str == 'page_') {
						$i_pagelink .= '.html';
					}
					if ($i == $currentpage) {
						$buf .= ' <strong>' . $i . '</strong> |' . "\n";
					} elseif ($totalpages<10 || $i<4 || $i>$totalpages-3) {
						$buf .= ' <a href="' . $i_pagelink . '" title="Page No.' . $i . '">'
							  . $i . '</a> |' . "\n";
					} else {
						if ($i<$currentpage-1 || $i>$currentpage+1) {
							if (($i == 4 && ($currentpage > 5 || $currentpage == 1)) || $i == $currentpage + 2) {
								$buf  = rtrim($buf);
								$buf .= "...|\n";
							}
						} else {
							$buf .= ' <a href="' . $i_pagelink . '" title="Page No.' . $i . '">'
								  . $i . '</a> |' . "\n";
						}
					}
				}
				$buf = rtrim($buf);
			}
			if (intval($type) == 3) {
				$buf .= '|';
				$sepstr = '&middot;';
				for ($i = 1; $i <= $totalpages; $i++) {
					$i_pagelink = $pagelink . $page_str . $i;
					if ($page_str == 'page_') {
						$i_pagelink .= '.html';
					}
					$paging = 5;
					if ($i == $currentpage) {
						$buf .= ' <strong>' . $i . '</strong> ' . $sepstr . "\n";
					} elseif ($totalpages < 10 || ($i < ($currentpage + $paging) && ($currentpage - $paging) < $i)) {
						$buf .= ' <a href="' . $i_pagelink . '" title="Page No.' . $i . '">'
							  . $i . '</a> ' . $sepstr . "\n";
					} elseif ($currentpage - $paging == $i) {
						$buf = rtrim($buf);
						$buf .= ' ...'."\n";
					} elseif ($currentpage + $paging == $i) {
						$buf = rtrim($buf);
						$buf = preg_replace('/$sepstr$/', '', $buf);
						$buf .= "... |\n";
					}
				}
			}
			if ($totalpages >= $nextpage) {
				$nextpagelink = $pagelink . $page_str . $nextpage;
				if ($page_str == 'page_') {
					$nextpagelink .= '.html';
				}
				$buf .= '| <a href="' . $nextpagelink . '" title="Next page" rel="Next">'
					  . '<span class="npsb_nextlink">' . $nextLinkLabel . '</span></a>' . "\n";
			} elseif ($type >= 2) {
				$buf .= "| " . $nextLinkLabel . "\n";
			}
//			$buf .= " | <a rel=\"last\" title=\"Last page\" href=\"{$lastpagelink}\">&lt;LAST&gt;</a>\n";
			$buf .= "</div>\n";
			return array('buf' => $buf, 'startpos' => intval($startpos));
		}
	}

	function _getSubcategoriesWhere($catid)
	{
		global $manager;
		$subcatTable =  sql_table('plug_multiple_categories_sub');
		$mwhere      =  '';
		$mwhere      =  ' AND ((i.inumber = p.item_id'
					 .  ' AND (p.categories REGEXP "(^|,)' . intval($catid) . '(,|$)"'
					 .  ' OR i.icat  = ' . intval($catid) . '))'
					 .  ' OR (i.icat = ' . intval($catid)
					 .  ' AND p.item_id IS NULL))';
		$mtable      =  ' LEFT JOIN ' . sql_table('plug_multiple_categories') . ' as p'
					 .  ' ON i.inumber = p.item_id';
		$mplugin     =& $manager->getPlugin('NP_MultipleCategories');
		if (method_exists($mplugin, 'getRequestName')) {
			$mplugin->event_PreSkinParse(array());
			global $subcatid;
			if ($subcatid) {
				$subcatid = intval($subcatid);

				$mque = 'SELECT * FROM %s WHERE scatid = %d';
				$tres = sql_query(sprintf($mque, $subcatTable, $subcatid));
//				$tres = sql_query('SELECT * FROM ' . sql_table('plug_multiple_categories_sub') .
//								' WHERE scatid = ' . intval($subcatid));
				$ra   = mysql_fetch_array($tres, MYSQL_ASSOC);
				if (array_key_exists('parentid', $ra)) {
					$Children = array();
					$Children = explode('/', $subcatid . $this->getChildren($subcatid));
				}
				if ($Children[1]) {
					for ($i=0;$i<count($Children);$i++) {
						$temp_whr[] = ' p.subcategories REGEXP "(^|,)' . intval($Children[$i]) . '(,|$)" ';
					}
					$mwhere .= ' AND ( ';
					$mwhere .= implode(' OR ', $temp_whr);
					$mwhere .= ' )';
				} else {
					$mwhere .= ' AND p.subcategories REGEXP "(^|,)' . $subcatid . '(,|$)"';
				}
			}
		}
		return array(
					 'w' => $mwhere,
					 'm' => $mtable
					);
	}

	function getParents($subcat_id)
	{
		$subcatTable = sql_table('plug_multiple_categories_sub');
		$que         = 'SELECT scatid, parentid, sname FROM %s WHERE scatid = %d';
		$que         = sprintf($que, $subcatTable, intval($subcat_id));
		$res         = sql_query($que);
		list($sid, $parent, $sname) = mysql_fetch_row($res);
		if ($parent != 0) {
			$r = $this->getParent(intval($parent)) . '/' . intval($sid);
		} else {
			$r = intval($sid);
		}
		return $r;
	}

	function getChildren($subcat_id)
	{
		$subcatTable = sql_table('plug_multiple_categories_sub');
		$que         = 'SELECT scatid, parentid, sname FROM %s WHERE parentid = %d';
		$que         = sprintf($que, $subcatTable, intval($subcat_id));
		$res         = sql_query($que);
		while ($so =  mysql_fetch_object($res)) {
			$r .= $this->getChildren(intval($so->scatid)) . '/' . intval($so->scatid);
		}
		return $r;
	}

	function _getTagsInum($where, $skin_type, $bmode, $p_amount)
	{
		global $manager, $itemid;
		$tagTable   =  sql_table('plug_tagex');
		$tplugin    =& $manager->getPlugin('NP_TagEX');
		$requestTag =  $tplugin->getNoDecodeQuery('tag');
		if (!empty($requestTag) || $skin_type == 'item') {
			$this->tagSelected = TRUE;
			if ($bmode=='all') {
				$allTags = $tplugin->scanExistTags(0);
			} else {
				$allTags = $tplugin->scanExistTags(2);
			}
			$arr = $tplugin->splitRequestTags($requestTag);
			if ($skin_type == 'item') {
				$item =& $manager->getItem(intval($itemid), 0, 0);
				$q    =  'SELECT * FROM %s WHERE inum = %d';
				$res  =  sql_query(sprintf($q, $tagTable, intval($itemid)));
				while ($o = mysql_fetch_object($res)) {
					$temp_tags_array = preg_split('/[\n,]+/', trim($o->itags));
					for ($i=0; $i < count($temp_tags_array); $i++) {
						$arr['or'][] = trim($temp_tags_array[$i]);
					}
				}
			}
			if ($skin_type != 'item') {
				for ($i=0; $i < count($arr['and']); $i++) {
					$deTag = $tplugin->_rawdecode($arr['and'][$i]);
					if ($allTags[$deTag]) {
						if (empty($inumsand)) {
							$inumsand = $allTags[$deTag];
						} else {
							$inumsand = array_intersect($inumsand, $allTags[$deTag]);
						}
					} else {
						$inumsand = array();
					}
					if (empty($inumsand)) {
						break;
					}
				}
				if (!empty($inumsand)) {
					$inumsres = array_values($inumsand);
					unset($inumsand);
				}
			}
			$inumsor = array();
			for ($i=0; $i < count($arr['or']); $i++) {
				if ($skin_type == 'item') {
					$deTag = $arr['or'][$i];
				} else {
					$deTag = $tplugin->_rawdecode($arr['or'][$i]);
				}
				if ($allTags[$deTag]) {
					$inumsor = array_merge($inumsor, $allTags[$deTag]);
				}
			}
			if ($inumsres && $inumsor) {
				$inumsres = array_merge($inumsres, $inumsor);
				$inumsres = array_unique($inumsres);
			} elseif (!$inumsres && $inumsor) {
				$inumsres = array_unique($inumsor);
			}
			if ($inumsres) {
				if ($skin_type == 'item') {
					foreach ($inumsres as $resinum) {
						$iTags = array();
						$q     = 'SELECT itags FROM %s WHERE inum = %d';
						$q     = sprintf($q, $tagTable, intval($resinum));
						$res   = sql_query($q);
						while ($o = mysql_fetch_object($res)) {
							$resTags = preg_split("/[\n,]+/", trim($o->itags));
							for ($i=0; $i < count($resTags); $i++) {
								$iTags[] = trim($resTags[$i]);
							}
						}
							$relatedTags        = array_intersect($arr['or'], $iTags);
							$tagCount[$resinum] = count($relatedTags);
					}
					asort($tagCount);
					$inumsres = array();
					foreach ($tagCount as $resinum => $val) {
						$relatedInums[] = intval($resinum);
					}
					for ($i=0; $i <= $p_amount; $i++) {
						$inumsres[$i] = array_pop($relatedInums);
					}
				}
				$where .= ' and i.inumber IN ('. @join(',', $inumsres) . ')';
			} else {
				$where .= ' and i.inumber=0';
			}
		}
		$retArray = array(
						  'where'    => $where,
						  'inumsres' => $inumsres
						 );
		return $retArray;
	}

	function doIf($key, $val = '')
	{
		if (strpos($key, '|') && strpos($val, '|')) {
			$keys = explode('|', $key);
			$vals = explode('|', $val);
			if (count($keys) <> count($vals)) {
				return;
			}
			$sbArgs = array();
			for ($i = 0; count($keys) > $i; $i++) {
				$sbArgs[$keys[$i]] = $vals[$i];
			}
			$tmplt = $sbArgs['tmplt'] ? $sbArgs['tmplt'] : 'default/index'; // template
			$amont = $sbArgs['amont'] ? $sbArgs['amont'] : 10;              // amount/page
			$bmode = $sbArgs['bmode'] ? $sbArgs['bmode'] : '';              // show or hide Blogs
			$type  = $sbArgs['type']  ? $sbArgs['type']  : 1;               // pagw switch type
			$sort  = $sbArgs['sort']  ? $sbArgs['sort']  : 'DESC';          // item sort mode (DESC or ASC)
			$stick = $sbArgs['stick'] ? $sbArgs['stick'] : '';              // sticky item id
			$stplt = $sbArgs['stplt'] ? $sbArgs['stplt'] : '';              // sticky template
			$cmode = $sbArgs['cmode'] ? $sbArgs['cmode'] : 'all';           // show or hide categories
			$acode = $sbArgs['acode'] ? $sbArgs['acode'] : 1;               // AdCode switch
			$cstik = $sbArgs['cstik'] ? $sbArgs['cstik'] : 0;               // show sticky item when category selected ?
			$this->doSkinVar($this->skintype, $tmplt, $amont, $bmode, $type, $sort, $stick, $stplt, $cmode, $acode, $cstik);
			return TRUE;
		} elseif ($key == 'page') {
			if ($value) {
				if ($this->currPage == intval($value)) {
					return TRUE;
				} else {
					return FALSE;
				}
			} elseif ($this->currPage > 1) {
				return TRUE;
			} else {
				return FALSE;
			}
		}

	}

}

