<?php
class NP_tDiarySkin extends NucleusPlugin
{ 
	function getName()
	{
		return 'tDiarySkin specified';
	}

	function getAuthor()
	{
		return 'shizuki';
	}

	function getURL()
	{
		return 'http://shizuki.kinezumi.net/NusleusCMS/Skins/tDiary.html';
	}

	function getVersion()
	{
		return '1.0';
	}

	function getDescription()
	{
		$deac = 'tDiarySkin 表示用プラグイン<br />';
		return $deac;
	}

	function supportsFeature($what)
	{
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		return array('InitSkinParse');
	}

	function install()
	{
		$this->createOption('monthlimit', 'カテゴリ一覧で表示する期間(初期値)',
							'select', '2', '一月|1|四半期|2|半年|3|1年|4');
	}

	function _dataCleaning($data)
	{
		$y = $m = $d = '';
		sscanf($data, '%d-%d-%d', $y, $m, $d);
		if (!empty($d)) {
			$data = sprintf('%04d-%02d-%02d', $y, $m, $d);
		} elseif (!empty($m)) {
			$data = sprintf('%04d-%02d', $y, $m);
		} else {
			$data = sprintf('%04d', $y);
		}
		return $data;
	}

	function _createDiaryLink($linkDate, $blogid)
	{
		sscanf($linkDate, '%d-%d-%d', $y, $m, $d);
		$linkDate = sprintf('%02d-%02d', $m, $d);
		$link     = createBlogidLink(intval($blogid), array('tdiarydate' => $linkDate));
		return $link;
	}

	function _createItemLinkByDate($itemdate, $mtable = '')
	{
		$inumber = 'SELECT min(i.inumber) as result'
				 . ' FROM ' . sql_table('item') . ' as i'
				 . $mtable
				 . ' WHERE i.idraft = 0'
				 . $this->getDateQuery($itemdate)
				 . ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC,'
				 . ' i.itime DESC';
		$itemID  = intval(quickQuery($inumber));
		$data    = array('link' => createItemLink($itemID),
						 'id'   => $itemID);
		return $data;
	}

	function _createCategoryIDLink($catid, $skinType, $subrequest = '')
	{
		global $archive;
		if (!empty($archive)) {
			$archive = $this->_dataCleaning($archive);
		}
		if (intGetVar('monthlimit') > 1) {
			$linkParams['monthlimit'] = intGetVar('monthlimit');
		}
//		$linkParams['catid'] = $catid;
		if ($subrequest) {
			$temp = explode('/', $subrequest);
			$linkParams[$temp[0]] = intval($temp[1]);
		}
		if ($skinType == 'archive') {
			$linkParams['archive'] = $archive;
			$catlink               = createCategoryLink($catid, $linkParams);
//			$catlink = createArchiveLink($this->nowbid, $archive, $linkParams);
		} elseif ($skinType == 'archivelist') {
			$linkParams['archives'] = $this->nowbid;
			$catlink                = createCategoryLink($catid, $linkParams);
//			$catlink = createArchiveListLink($this->nowbid, $linkParams);
		} else {
			if ($subrequest) {
				$catlink = createCategoryLink($catid, array($temp[0] => $temp[1]));
			} else {
				$catlink = createCategoryLink($catid);
			}
		}
		return $catlink;
	}

	function _createBasePageLink($catid, $subcatid, $archive)
	{
		global $CONF, $manager;
		$catid       = intval($catid);
		$subcatid    = intval($subcatid);
		$page_str    = $this->pagestr;
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		if (!empty($archive)) {
			$archive = $this->_dataCleaning($archive);
		}
		if (!empty($catid)) {
			$linkParam['catid'] = $catid;
			if (!empty($subcatid)) {
				$mcategories =& $manager->getPlugin('NP_MultipleCategories');
				if (method_exists($mcategories, 'getRequestName')) {
					$subrequest = $mcategories->getRequestName();
				} else {
					$subrequest = 'subcatid';
				}
				$linkParam[$subrequest] = $subcatid;
			}
			if (!empty($archive)) {
				$pagelink = createArchiveLink($this->nowbid, $archive, $linkParam);
			} else {
				$pagelink = createCategoryLink($catid, $linkParam);
			}
		} elseif (!empty($archive)) {
			$pagelink = createArchiveLink($this->nowbid, $archive);
		} else {
			$pagelink = createBlogidLink($this->nowbid);
		}
		$uri  = parse_url($pagelink);
		if (!$usePathInfo) {
			if ($pagelink == $CONF['BlogURL']) {
				$pSeparator = '?';
			} elseif ($uri['query']) {
				$pSeparator = '&amp;';
			}
			$pagelink = str_replace('&amp;&amp;', '&amp;', $pagelink);
		} elseif ($usePathInfo && substr($pagelink, -1) != '/') {
			if ($uri['query']) {
				$pSeparator  = '&amp;';
				$page_str    = 'page=';
			} else {
				$pagelink  .= '/';
				$pSeparator = '';
			}
		}
		if (strstr ($pagelink, '//')) {
			$pagelink = preg_replace("/([^:])\/\//", "$1/", $pagelink);
		}
		return array('pagelink'   => $pagelink,
						'separator'  => $pSeparator,
						'pageString' => $page_str);
	}

	function _createDatePageLink($datedata, $catid, $subcatid, $archive)
	{
		$y = $m = $d = '';
		sscanf($datedata, '%d-%d-%d', $y, $m, $d);
		if (!empty($d)) {
			$datedata = sprintf('%04d%02d%02d', $y, $m, $d);
		} else {
			$datedata = sprintf('%04d%02d', $y, $m);
		}
		if (!empty($archive)) {
			$archive = $this->_dataCleaning($archive);
		}
		$pLink         = $this->_createBasePageLink($catid, $subcatid, $archive);
		$pagelink      = $pLink['pagelink'];
		$pSeparator    = $pLink['separator'];
		$pagestr       = $pLink['pageString'];
		$this->pagestr = $pagestr;
		return $pagelink . $pSeparator . $pagestr . $datedata;
	}

	function _createArchivePageLink($linkdate, $catid = 0)
	{
		if ($catid) {
			$linkParams['catid'] = intval($catid);
		}
		if (intGetVar('categorylist') > 0) {
			$linkParams['categorylist'] = intGetVar('categorylist');
		}
		if (intGetVar('monthlimit') > 1) {
			$linkParams['monthlimit'] = intGetVar('monthlimit');
		}
		return createArchiveLink($this->nowbid, $linkdate, $linkParams);
	}

	function _createPageSwitch($where, $mtable = '', $amount, $skinType = 'index', $b)
	{
		global $CONF, $manager, $itemid, $catid, $subcatid;
		global $archive, $archiveprev, $archivenext;
// initialize
		$nextstr = $prevstr = '';
		$query = 'SELECT SUBSTRING(i.itime, 1, 10) as Date'
				. ' FROM ' . sql_table('item') . ' as i' . $mtable
				. ' WHERE i.idraft = 0';
		if (!empty($archive)) {
			$monthlimit = (intGetVar('monthlimit') > 0) ? intGetVar('monthlimit') : 1;
			if ($monthlimit > 1) {
				sscanf($archive, '%d-%d-%d', $y, $m, $d);
				$prevmonth   = mktime(0, 0, 0, $m-intGetVar('monthlimit'), 1, $y);
				$prevarchive = strftime('%Y-%m', $prevmonth);
				$nextmonth   = mktime(0, 0, 0, $m+intGetVar('monthlimit'), 1, $y);
				$nextarchive = strftime('%Y-%m', $nextmonth);
			} else {
				$prevarchive = $archiveprev;
				$nextarchive = $archivenext;
			}
			$prevarchive = substr($prevarchive, 0, strlen($archive));
			$nextarchive = substr($nextarchive, 0, strlen($archive));
			$nextArcQ  = $query . $this->getDateQuery($nextarchive, $monthlimit);
			$nextManth = mysql_num_rows(sql_query($nextArcQ));
			$prevArcQ  = $query . $this->getDateQuery($prevarchive, $monthlimit);
			$prevManth = mysql_num_rows(sql_query($prevArcQ));
		}
		$startpos    = 0;
		$pLink    = $this->_createBasePageLink($catid, $subcatid, $archive);
		$pagelink = $pLink['pagelink'];

		$dresq = $query . $where
				. ' GROUP BY Date'
				. ' ORDER BY i.itime DESC'; 
		$res = sql_query($dresq);
		while ($dres = mysql_fetch_row($res)) {
			$daylist[] = $dres[0];
		}
		$currentpage = 0;
		$totalamount = 0;
		if (!empty($itemid)) {
			$dateq			= $query
							. ' AND i.inumber = ' . intval($itemid);
			$temp			= sql_query($dateq);
			$itemdate		= mysql_fetch_object($temp);
			$this->currPage = $itemdate->Date;
		}
		if (isset($daylist)) {
			$temp        = array_search($this->currPage, $daylist);
			$totalamount = count($daylist);
			if (!empty($temp)) {
				$currentpage = $temp / $amount;
			} elseif ($skinType == 'item' && $temp !== FALSE) {
				$currentpage = $temp;
			}
		}
		$startpos = ($currentpage > 0) ? $currentpage * $amount : 0;
		$totalamount = intval($totalamount);
		$totalpages  = ceil($totalamount / $amount);
		if ($startpos > $totalamount) {
			$currentpage = $totalpages;
			$startpos    = $totalamount - $amount;
		}
		$nextpos  = $startpos - $amount;
		$prevpos  = $startpos + $amount;
		$nextpage = $currentpage - 1;
		$prevpage = $currentpage + 1;

		$buf .= '<div class="adminmenu">' . "\n";
		if (($totalpages >= $prevpage || $prevManth > 0) && $skinType != 'archivelist') {
			if ($skinType == 'item') {
				$templink = $this->_createItemLinkByDate($daylist[$prevpos], $mtable);
				$prevlink = $templink['link'];
				$prevstr  = '前日の日記';
			} elseif ($skinType == 'archive' && !$catid && !intGetVar('categorylist')) {
				$prevlink = $this->_createArchivePageLink($prevarchive, $catid);
				if ($monthlimit > 1) {
					$prevstr = $this->getArchivePageString($prevarchive, $monthlimit);
				} elseif (strlen($archive) == 4) {
					$nextstr = '前の年';
				} else {
					$prevstr = '前の月';
				}
			} elseif ($skinType == 'index') {
				$prevlink = $this->_createDatePageLink($daylist[$prevpos], $catid, $subcatid, $archive);
				$prevstr  = '前'. $amount . '日分';
				if ($this->pagestr == 'page_') {
					$prevlink .= '.html';
				}
			}
			if (!empty($prevstr)) {
				$buf .= '<span class="adminmenu">'
						. '<a href="' . $prevlink . '" title="Previous page" rel="Prev">'
						. '&laquo;' . $prevstr
						. '</a></span>' . "\n";
			}
		}
		$buf .= '<span class="adminmenu">'
				. ' <a href="'
				. createBlogidLink($this->nowbid)
				. '" title="new" rel="Start">最新</a></span>'
				. "\n";
		if (($nextpage >= 0 || $nextManth > 0) && $skinType != 'archivelist') {
			if ($skinType == 'item') {
				$templink = $this->_createItemLinkByDate($daylist[$nextpage], $mtable);
				$nextlink = $templink['link'];
				$nextstr  = '翌日の日記';
			} elseif ($skinType == 'archive' && !$catid && !intGetVar('categorylist')) {
				$nextlink = $this->_createArchivePageLink($nextarchive, $catid);
				if ($monthlimit > 1) {
					$nextstr = $this->getArchivePageString($nextarchive, $monthlimit);
				} elseif (strlen($archive) == 4) {
					$nextstr = '次の年';
				} else {
					$nextstr = '次の月';
				}
			} elseif ($skinType == 'index') {
				$nextlink = $this->_createDatePageLink($daylist[$nextpos], $catid, $subcatid, $archive);
				$nextstr  = '次'. $amount . '日分';
				if ($this->pagestr == 'page_') {
					$nextlink .= '.html';
				}
			}
			if (!empty($nextstr)) {
				$buf .= '<span class="adminmenu">'
						. '<a href="' . $nextlink . '" title="Next page" rel="Next">'
						. $nextstr . '&raquo;</a>'
						. '</span>'
						. "\n";
			}
		}
		$buf .= "</div>\n";

		$catlist = ($skinType == 'archive' && ($catid || intGetVar('categorylist')));
		if ($skinType == 'archivelist' || $catlist) {
			$catID   = ($catid) ? $catid : 0;
			$idData  = array('catid'  => $catID,
							 'bid'    => $this->nowbid);
			$datearr = array('pdate'  => $prevarchive,
							 'ndate'  => $nextarchive,
							 'pmonth' => $prevManth,
							 'nmonth' => $nextManth);
			if ($skinType == 'archive') {
				$datearr['cdate'] = $archive;
			} else {
				$datearr['cdate'] = substr(mysqldate($b->getCorrectTime()), 1, 7);
			}
			$buf    .= $this->generateArchivePageSwitch($skinType, $mtable, $datearr, $idData);
		}

		$pageParams = array('pageLink' => $buf,
							'startpos' => intval($startpos),
							'daysList' => $daylist);
		return $pageParams;
	}

	function getArchivePageString($datedata, $limit)
	{
		sscanf($datedata, '%d-%d-%d', $y, $m, $d);
		if ($limit == 6) {
			if ($m == 1) {
				$buf = $y . '年の上半期';
			} else {
				$buf = $y . '年の下半期';
			}
		} elseif ($limit == 3) {
			if ($m == 1) {
				$buf = $y . '年の第一四半期';
			} elseif ($m == 4) {
				$buf = $y . '年の第二四半期';
			} elseif ($m == 7) {
				$buf = $y . '年の第三四半期';
			} else {
				$buf = $y . '年の第四四半期';
			}
		}
		return $buf;
	}

	function generateArchivePageSwitch($skinType, $mtable, $datearr, $id)
	{
// initialize
		$catID       = $id['catid'];
		$bid         = $id['bid'];
		$cdate       = $datearr['cdate'];
		$prevarchive = $datearr['pdate'];
		$nextarchive = $datearr['ndate'];
		$prevManth   = $datearr['pmonth'];
		$nextManth   = $datearr['nmonth'];
		$monthlimit = (intGetVar('monthlimit') > 0) ? intGetVar('monthlimit') : 1;
		$data    = '<div class="adminmenu">' . "\n"
				 . "<p>\n";
		if ($skinType == 'archive') {
			if ($catID > 0) {
				$param['catid'] = $catID;
			}
			if ($prevManth > 0) {
				$prevlink = $this->_createArchivePageLink($prevarchive, $catID);
				if ($monthlimit > 1) {
					$prevstr = $this->getArchivePageString($prevarchive, $monthlimit);
				} elseif (strlen($cdate) == 4) {
					$prevstr = '前の年';
				} else {
					$prevstr = '前の月';
				}
				$data .= '<span class="adminmenu">'
					   . '<a href="' . $prevlink . '" title="Previous page" rel="Prev">'
					   . '&laquo;' . $prevstr
					   . '</a></span>' . "\n";
			}
			if ($nextManth > 0) {
				$nextlink = $this->_createArchivePageLink($nextarchive, $catID);
				if ($monthlimit > 1) {
					$nextstr = $this->getArchivePageString($nextarchive, $monthlimit);
				} elseif (strlen($cdate) == 4) {
					$nextstr = '次の年';
				} else {
					$nextstr = '次の月';
				}
				$data .= '<span class="adminmenu">'
					   . '<a href="' . $nextlink . '" title="Next page" rel="Next">'
					   . $nextstr . '&raquo;</a>'
					   . '</span>'
					   . "\n";
			}
			unset($param);
		}
		if ($skinType == 'archive') {
			if ($catID > 0) {
				$param['catid'] = $catID;
			}
			$data  .= '<span class="adminmenu"><a href="'
					. createArchiveListLink($bid, $param)
					. '">全期間</a></span>' . "\n"
					. '<span class="adminmenu"><a href="'
					. createArchiveListLink($bid)
					. '">全期間/全カテゴリ</a></span>' . "\n";
			unset($param);
		} elseif ($skinType == 'archivelist') {
			if ($catID > 0) {
				$param['catid'] = $catID;
			}
			$param['categorylist'] = 1;
			$data .= '<span class="adminmenu"><a href="'
				   . createArchiveLink($bid, substr($cdate, 0, 4), $param)
				   . '">年</a></span>' . "\n";
			$param['monthlimit'] = 6;
			$data .= '<span class="adminmenu"><a href="'
				   . createArchiveLink($bid, $cdate, $param)
				   . '">半年</a></span>' . "\n";
			$param['monthlimit'] = 3;
			$data .= '<span class="adminmenu"><a href="'
				   . createArchiveLink($bid, $cdate, $param)
				   . '">四半期</a></span>' . "\n";
			unset($param['monthlimit']);
			$data .= '<span class="adminmenu"><a href="'
				   . createArchiveLink($bid, $cdate, $param)
				   . '">月</a></span>' . "\n";
			unset($param);
		}
		if ($catID > 0) {
			$data .= '<span class="adminmenu"><a href="';
			if ($skinType == 'archive') {
				$param = array('categorylist' => 1,
							   'monthlimit'   => $monthlimit);
				$data .= createArchiveLink($wbid, $cdate, $param);
			} elseif ($skinType == 'archivelist') {
				$data .= createArchiveListLink($bid);
			}
			$data .= '">全カテゴリ</a></span>' . "\n";
			unset($param);
		}
		$data .= "</p>\n</div>\n";
		return $data;
	}

	function _getTrackBackURL($id)
	{
		global $manager;
		$id = intval($id);
		if ($manager->pluginInstalled('NP_CustomURL')) {
			$query     =  'SELECT inumber as itemid FROM %s WHERE inumber = %d';
			$query     =  sprintf($query, sql_table('item'), $id);
			$res       =  sql_query($query);
			$iData     =  mysql_fetch_object($res);
			$customURL =& $manager->getPlugin('NP_CustomURL');
			ob_start();
			$customURL->doTemplateVar($iData, 'trackback');
			$tbURL     = ob_get_contents();
			ob_end_clean();
		} else {
			$tbPlugin  =& $manager->getPlugin('NP_TrackBack');
			$tbURL     =  $tbPlugin->getTrackBackUrl($id);
		}
		return $tbURL;
	}

	function getChildren($subcat_id)
	{
		$que = 'SELECT scatid, parentid, sname FROM %s WHERE parentid = %d';
		$res = sql_query(sprintf($que, sql_table('plug_multiple_categories_sub'), intval($subcat_id)));
		while ($so =  mysql_fetch_object($res)) {
			$r .= $this->getChildren(intval($so->scatid)) . '/' . intval($so->scatid);
		}
		return $r;
	}

	function getSearchQuery()
	{
		global $query, $blog, $CONF, $manager;
		$hlight = '';
		$exsearch = $manager->getPlugin('NP_ExtensibleSearch');
		if ($exsearch) {
			$sqlquery = $exsearch->getSqlQuery($query, $months, $hlight);
		} else {
			if ($blog) {
				$b =& $blog;
			} else {
				$b =& $manager->getBlog($CONF['DefaultBlog']);
			}
			$sqlquery = $b->getSqlQuery($query, $months, $hlight);
		}
		$retQuery = explode(' ORDER BY', $sqlquery, 2);
		return $retQuery[0];
	}

	function getDateQuery($datedata, $limit = '1')
	{
		$datedata = $this->_dataCleaning($datedata);
		sscanf($datedata, '%d-%d-%d', $y, $m, $d);
		if (!empty($d)) {
			$timestamp_start = mktime(0, 0, 0, $m,        $d,   $y  );
			$timestamp_end   = mktime(0, 0, 0, $m,        $d+1, $y  );
		} elseif (!empty($m)) {
			$timestamp_start = mktime(0, 0, 0, $m,        1,    $y  );
			$timestamp_end   = mktime(0, 0, 0, $m+$limit, 1,    $y  );
		} else {
			$timestamp_start = mktime(0, 0, 0, 1,         1,    $y  );
			$timestamp_end   = mktime(0, 0, 0, 1,         1,    $y+1);
		}
		$where = ' and i.itime >= ' . mysqldate($timestamp_start)
				. ' and i.itime < '  . mysqldate($timestamp_end);
		return $where;
	}

	function getMulticategoriesQuery($catid, $subcatid = 0)
	{
		global $manager;
		$mwhere = '';
		$mtable = '';
		$mwhere = ' AND ((i.inumber = p.item_id'
				. ' AND (p.categories REGEXP "(^|,)' . intval($catid) . '(,|$)"'
				. ' OR i.icat = ' . intval($catid) . '))'
				. ' OR (i.icat = ' . intval($catid)
				. ' AND p.item_id IS NULL))';
		$mtable = ' LEFT JOIN ' . sql_table('plug_multiple_categories') . ' as p'
				. ' ON i.inumber = p.item_id';
		$mplugin =& $manager->getPlugin('NP_MultipleCategories');
		if (method_exists($mplugin, 'getRequestName')) {
			$mplugin->event_PreSkinParse(array());
			if ($subcatid) {

				$mque = 'SELECT * FROM %s WHERE scatid = %d';
				$tres = sql_query(sprintf($mque, sql_table('plug_multiple_categories_sub'), intval($subcatid)));
				$ra = mysql_fetch_array($tres, MYSQL_ASSOC);
				if (array_key_exists('parentid', $ra)) {
					$Children = array();
					$Children = explode('/', intval($subcatid) . $this->getChildren(intval($subcatid)));
				}
				if ($Children[1]) {
					for ($i=0;$i<count($Children);$i++) {
						$temp_whr[] = ' p.subcategories REGEXP "(^|,)' . intval($Children[$i]) . '(,|$)" ';
					}
					$mwhere .= ' AND ( ';
					$mwhere .= join(' OR ', $temp_whr);
					$mwhere .= ' )';
				} else {
					$mwhere .= ' AND p.subcategories REGEXP "(^|,)' . intval($subcatid) . '(,|$)"';
				}
			}
		}
		return array('mtable' => $mtable,
					 'where'  => $mwhere);
	}

	function showComment($itemid, $itemuri, $skinType)
	{
		$q = 'SELECT COUNT(*) as result'
			. ' FROM ' . sql_table('comment') . ' as c'
			. ' WHERE c.citem = ' . intval($itemid);
		$postnum = quickQuery($q);
		if ($postnum > 5) {
			$startnum = $postnum - 5;
		} else {
			$startnum = 0;
		}
		$query = 'SELECT c.cnumber, c.cbody, c.cuser, c.cmember,'
				. ' UNIX_TIMESTAMP(c.ctime) as ctimestamp'
				. ' FROM ' . sql_table('comment') . ' as c'
				. ' WHERE c.citem = ' . intval($itemid)
				. ' ORDER BY c.ctime ASC'
				. ' LIMIT ' . intval($startnum) . ', 5';

		$comments    = sql_query($query);
		$viewnum     = mysql_num_rows($comments);
		$youbi       = array('日', '月', '火', '水', '木', '金', '土');
		$commentData = '<div class="comment">' . "\n"
						. '<div class="caption">';
		if ($postnum) {
			$commentData .= '本日のツッコミ(全' . $postnum . '件)';
		}
		$commentData .= '[<a href="' . $itemuri . '#c">ツッコミを入れる</a>]</div>' . "\n";
		if ($postnum) {
			if ($skinType == 'index') {
				$commentData .= '<div class="commentshort">' . "\n";
			} else {
				$commentData .= '<div class="commentbody">' . "\n";
			}
			while ($row = mysql_fetch_object($comments)) {
				$uri  = $itemuri . '#c' . $row->cnumber;
				if (!($myname = $row->cuser)) {
					$mem = new MEMBER;
					$mem->readFromID($row->cmember);
					$myname = $mem->getRealName();
				}
				if ($skinType != 'item') {
					$body = strip_tags($row->cbody);
					$body = str_replace("\r\n", "\r", $body); 
					$body = str_replace("\r", "\n", $body); 
					$body = str_replace("\n",' ',$body);
					$body = shorten($body, 180, "...");
					$body = htmlspecialchars($body);
					$commentData .= '<p><a href="' .$uri. '"><span class="canchor">_</span></a>' . "\n"
									. '<span class="commentator">' . htmlspecialchars($myname) . '</span>'
									. '&nbsp;[' . $body . ']</p>' . "\n";
				} elseif ($skinType == 'item') {
					$body = $row->cbody;
					$commentData .= '<div class="commentator">'
									. "\t" . '<a name="c' . $row->cnumber . '" id="c' . $row->cnumber . '" '
									. 'href="' .$uri. '"><span class="canchor">_</span></a>' . "\n\t"
									. '<span class="commentator">' . htmlspecialchars($myname) . '</span>' . "\n\t"
									. '<span class="commenttime">(' . strftime('%Y年%m月%d日', $row->ctimestamp)
									. '(' . $youbi[strftime('%w', $row->ctimestamp)] . ')'
									. strftime('%H:%M', $row->ctimestamp) . ')'
									. "</span>\n</div>"
									. '<p>' . $body . "</p>\n";
				}
			}
			$commentData .= '</div>' . "\n";
			mysql_free_result($comments);
		}
		$commentData .= '</div>' . "\n";
		return $commentData;
	}

	function showForm($itemid, $itemuri)
	{
		global $manager, $CONF, $member;
		$actionphp  = $CONF['ActionURL'];
		$membername = $member->getRealName();

		if ($membername) {
			$nameArea = 'お名前: ' . $membername . ' (<a href="'
					  . createItemLink($itemid)
					  . '/?action=logout">ログアウト</a>)';
			$mailArea = ' ';
			$checkBox = '';
			$type     = 'commentform-loggedin';
		} else {
			if (cookieVar('comment_user')) {
				$username = htmlspecialchars(cookieVar('comment_user'));
			} else {
				$username = '';
			}
			if (cookieVar('comment_userid')) {
				$userid = htmlspecialchars(cookieVar('comment_userid'));
			} else {
				$userid = '';
			}
			cookieVar('comment_user') ? $check = 'checked="checked" ' : $check = '';
			$nameArea = 'お名前: <input name="user" value="' . $username . '" class="field" />';
			$mailArea = 'E-Mail: <input name="userid" value="' . $userid . '" class="field" />';
//			$checkBox = '<input type="checkbox" value="1" name="remember" ' . $check . '/>情報を記憶しておく ';
			$type     = 'commentform-notloggedin';
		}
echo <<<___COMMENTFORM__
<div class="form">
	<div class="caption"><a name="c" id="c">ツッコミ・コメントがあればどうぞ! </a></div>
	<form method="post" action="{$actionphp}">
		<div>
			<input type="hidden" name="action" value="addcomment" />
			<input type="hidden" name="url" value="{$itemuri}" />
			<input type="hidden" name="itemid" value="{$itemid}" />
			<div class="field name">
				{$nameArea}
			</div>
			<div class="field mail">
				{$mailArea}
			</div>
			<div class="textarea">
				コメント: <textarea name="body" cols="60" rows="5" class="formfield"></textarea><br />
			</div>
___COMMENTFORM__;
		$manager->notify('FormExtra', array('type' => $type));
echo <<<___COMMENTFORM__
			<div class="button">
				<input type="submit" value="投稿" class="formbutton" />
			</div>
		</div>
	</form>
</div>
___COMMENTFORM__;
	}

	function showCategoryList($skinType, $query, $mtable = '', $where, $catwhere = '', $catID, $b)
	{
		global $manager, $archive, $subcatid;
		$minstalled = $manager->pluginInstalled('NP_MultipleCategories');
		if ($minstalled) {
			$subcatTable =  sql_table('plug_multiple_categories_sub');
			$mplugin     =& $manager->getPlugin('NP_MultipleCategories');
			if (method_exists($mplugin, 'getRequestName')) {
				$subrequest = $mplugin->getRequestName();
			} else {
				$subrequest = 'subcatid';
			}
		}
		$sType      = $skinType;
		$monthlimit = (intGetVar('monthlimit') > 0) ? intGetVar('monthlimit') : 1;
		$bid = intval($this->nowbid);
		$catque = 'SELECT c.catid as catid,'
				. ' c.cname as catname'
				. ' FROM ' . sql_table('category') . ' as c'
				. ' WHERE c.cblog = ' . $bid;
		$catres = sql_query($catque);
		echo '<p>Categories |';
		while ($catdata = mysql_fetch_object($catres)) {
			$cid     = intval($catdata->catid);
			$catlink = $this->_createCategoryIDLink($cid, $sType);
			echo 'cat<a href="' . $catlink . '">' . $catdata->catname . "</a> | \n";
			if ($minstalled) {
				$subcats =  $mplugin->_getScatIDs($cid);
				if (!empty($subcats)) {
					foreach ($subcats as $subcat) {
						$scatque  = 'SELECT sname  as result'
								  . ' FROM ' . $subcatTable
								  . ' WHERE scatid = ' . intval($subcat);
						$subname  = quickQuery($scatque);
						$sbprm    = $subrequest . '/' . intval($subcat);
						$sblnk    = $this->_createCategoryIDLink($cid, $sType, $sbprm);
						echo 'scat<a href="' . $sblnk . '">' . $subname . "</a> | \n";
					}
				}
			}
		}
		echo '</p>';
		echo '<hr class="sep" />' . "\n";
		if (!empty($catID)) {
			$catque .= ' and catid = ' . intval($catID);
		}
		$catres  = sql_query($catque);
		while ($catdata = mysql_fetch_object($catres)) {
			$cid = intval($catdata->catid);
			if ($minstalled && !$mtable) {
				$tempq    = $this->getMulticategoriesQuery($cid);
				$shQuery = $query . $tempq['mtable'] . $where . $tempq['where'];
			} else {
				$shQuery = $query . $mtable . $where . $catwhere;
			}

			if ($skinType == 'archive') {
				$shQuery .= $this->getDateQuery($archive, $monthlimit);
			}
			$shQuery .= ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC, i.itime DESC';
			if (mysql_num_rows(sql_query($shQuery)) && !$subcatid) {
				$catlink  = $this->_createCategoryIDLink($cid, $sType);
				$headData = '<div class="conf day">' . "\n"
						  . '<h2><span class="title"><a href="' . $catlink . '">'
						  . $catdata->catname . "</a></span></h2>\n"
						  . '<div class="body">' . "\n"
						  . "<p>\n";
				echo $headData;
				$b->showUsingQuery('tDiary/archive', $shQuery, 0, 1, 1);
				echo "</p>\n</div>\n</div>\n";
			}

			if ($minstalled && !$subcatid && $catID) {
				$subcats =  $mplugin->_getScatIDs($cid);
				if (!empty($subcats)) {
					sort($subcats);
					foreach ($subcats as $subcat) {
						$tempq   = $this->getMulticategoriesQuery($cid, $subcat);
						$sbQuery = $query . $tempq['mtable'] . $where . $tempq['where'];
						if ($skinType == 'archive') {
							$sbQuery .= $this->getDateQuery($archive, $monthlimit);
						}
						$sbQuery .= ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC,'
								  . ' i.itime DESC';
						$this->showSubcatList($cid, $subcat, $sType, $sbQuery, $subrequest);
					}
				}
			} elseif ($subcatid) {
				$tempq   = $this->getMulticategoriesQuery($cid, $subcatid);
				$sbQuery = $query . $tempq['mtable'] . $where . $tempq['where'];
				if ($skinType == 'archive') {
					$sbQuery .= $this->getDateQuery($archive, $monthlimit);
				}
				$sbQuery .= ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC, i.itime DESC';
				$this->showSubcatList($cid, $subcatid, $sType, $sbQuery, $subrequest);
			}
		}
	}

	function showSubcatList($cid, $sid, $skinType, $sbQuery, $subrequest)
	{
		global $manager;
		$bid      =  getBlogIDFromCatID($cid);
		$b        =& $manager->getBlog($bid);
		$scatque  =  'SELECT sname  as result'
				  .  ' FROM ' . sql_table('plug_multiple_categories_sub')
				  . ' WHERE scatid = ' . $sid;
		$subname  = quickQuery($scatque);
		$subparam = $subrequest . '/' . $sid;
		if (mysql_num_rows(sql_query($sbQuery))) {
			$sublink  = $this->_createCategoryIDLink($cid, $skinType, $subparam);
			$headData = '<div class="conf day">' . "\n"
						. '<h2><span class="title"><a href="' . $sublink . '">'
						. $subname . "</a></span></h2>\n"
						. '<div class="body">' . "\n"
						. "<p>\n";
			echo $headData;
			$b->showUsingQuery('tDiary/archive', $sbQuery, 0, 1, 1);
			echo "</p>\n</div>\n</div>\n";
		}
	}

	function showDiary($skinType, $query, $startpos, $daylist, $amount, $b)
	{
		global $manager;
		if ($skinType == 'item') {
			$template = 'tDiary/item';
		} else {
			$template = 'tDiary/index';
		}
		if (getVar('tdiarydate')) {
			sscanf(getVar('tdiarydate'), '%d-%d', $m, $d);
			$linkDate = sprintf('%02d-%02d', $m, $d);
			$amount   = count($daylist);
		} elseif ($skinType == 'archive') {
			$amount   = count($daylist);
		} elseif ($skinType == 'search') {
			$tmpQue   = $query
					  . ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC,'
					  . ' i.itime DESC';
			$res      = sql_query($tmpQue);
			$amount   = count($daylist);
//			$amount   = mysql_num_rows($res);
		}
		$youbiArray   = array('日', '月', '火', '水', '木', '金', '土');
		for ($i = 1; $i <= $amount; $i++) {
			$dateData = $daylist[$startpos];
			$itemData = $this->_createItemLinkByDate($dateData, $mtable);
			$itemLink = $itemData['link'];
			$itemID   = $itemData['id'];
			$youbi    = $youbiArray[strftime("%w", strtotime($dateData))];
			$headData = '<hr class="sep" />' . "\n"
					  . '<div class="day">' . "\n"
					  . "<h2>\n" . '<span class="date">'
					  . '<a href="' . $itemLink . '" title="' . $dateData . '">'
					  . $dateData . '(' . $youbi . ')'
					  . "</a></span>\n"
					  . '<span class="title"></span>' . "\n";
			if (!isset($linkDate)) {
				$headData .= '<span class="nyear">[<a href="'
						. $this->_createDiaryLink($dateData, $this->nowbid)
						. '" title="長年日記">長年日記</a>]</span>'
						. "\n";
			}
			$headData .= "</h2>\n";
			$shQuery   = $query;
//			if ($skinType != 'search') {
				$shQuery .= $this->getDateQuery($dateData);
//			}
			$shQuery .= ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC,'
					  . ' i.itime DESC';
//			echo mysql_num_rows(sql_query($shQuery));
//			if (mysql_num_rows(sql_query($shQuery)) > 0) {
				if (isset($linkDate)) {
					if (substr($dateData, 5) == $linkDate) {
						echo $headData;
						$b->showUsingQuery($template, $shQuery, 0, 1, 1);
						echo $this->showComment($itemID, $itemLink, $skinType);
						$this->showTBandReferer($skinType, $itemID, $itemLink);
						echo '</div>' . "\n";
					}
				} else {
					echo $headData;
					$b->showUsingQuery($template, $shQuery, 0, 1, 1);
					echo $this->showComment($itemID, $itemLink, $skinType);
					if ($skinType == 'item') {
						$this->showForm($itemID, $itemLink);
					}
					$this->showTBandReferer($skinType, $itemID, $itemLink);
					echo '</div>' . "\n";
				}
				$startpos++;
//			}
		}
	}

	function showTBandReferer($skinType, $itemID, $itemLink)
	{
		global $manager;
		$refInstalled = $manager->pluginInstalled('NP_Referer3');
		$tbInstalled  = $manager->pluginInstalled('NP_TrackBack');
		if ($refInstalled || $tbInstalled) {
			if ($skinType == 'item') {
				if ($tbInstalled) {
					$tbPlugin  =& $manager->getPlugin('NP_TrackBack');
					$tbCount   =  $tbPlugin->getTrackBackCount($itemID);
					$tbURL     = $this->_getTrackBackURL($itemID);
					$printData = "\n\t" . '<div class="comment trackbacks">'
							   . "\n\t\t" . '<div class="caption">';
					if ($tbCount > 0) {
						$printData .= "\n\t\t\t"
								    . '本日のTrackBacks(全' . $tbCount . '件)';
					}
					$printData     .= "\n\t\t\t"
									. '[TrackBack URL: <a href="' . $tbURL . '" name="tb" id="tb">'
									. $tbURL . '</a>]'
									. "\n\t\t</div>";
					echo $printData;
					$this->showTrackBackList($itemID);
					echo "\n\t</div>";
				}
				if ($refInstalled) {
					$refTemplate = "\n\t\t"
								 . '<li><a href="<%send%>" rel="nofollow">'
								 . '<%extra%></a> × <%senCount%></li>';
					$receive     = 'id/item/' . intval($itemID);
					$refQuery    = 'SELECT send,'
								 . '       extra,'
								 . '       receive,'
								 . ' COUNT(send) AS senCount'
								 . ' FROM ' . sql_table('plug_referer3')
			            		 . ' WHERE receive = "' . $receive . '"'
								 . ' GROUP BY send'
							     . ' ORDER BY senCount DESC';
					$results  = sql_query($refQuery);
					if (mysql_num_rows($results) > 0) {
						$printData = '<div class="refererlist">'
								   . "\n\t" . '<div class="caption">本日のリンク元</div>'
								   . "\n\t" . '<ul>';
						echo $printData;
						while ($res = mysql_fetch_object($results)) {
							$printData = (array)$res;
							$printData = array_map('htmlspecialchars', $printData);
							echo TEMPLATE::fill($refTemplate, $printData);
						}
						echo "\n\t</ul>\n</div>";
					}
				}
			} else {
				echo '<div class="referer">' . "\n";
				if ($tbInstalled) {
					$tbPlugin  =& $manager->getPlugin('NP_TrackBack');
					$tbCount   = $tbPlugin->getTrackBackCount($itemID);
					$printData = '<a href="' . $itemLink . '#tb">'
							   . 'TrackBack(' . $tbCount . ')'
							   . '</a>'
							   . '<br />';
					echo $printData;
				}
				if ($refInstalled) {
					$refTemplate = '<a href="<%send%>" title="<%extra%>">'
								 . '<%senCount%></a> | ' . "\n";
					$receive     = 'id/item/' . intval($itemID);
					$refQuery    = 'SELECT send,'
								 . '       extra,'
								 . '       receive,'
								 . ' COUNT(send) AS senCount'
								 . ' FROM ' . sql_table('plug_referer3')
			            		 . ' WHERE receive = "' . $receive . '"'
								 . ' GROUP BY send'
							     . ' ORDER BY senCount DESC';
					$results  = sql_query($refQuery);
					if (mysql_num_rows($results) > 0) {
						echo '本日のリンク元 | ';
						while ($res = mysql_fetch_object($results)) {
							$printData = (array)$res;
							$printData = array_map('htmlspecialchars', $printData);
							echo TEMPLATE::fill($refTemplate, $printData);
						}
					}
				echo '</div>';
				}
			}
		}
	}

	function showTrackBackList($tb_id)
	{
		global $manager, $blog, $CONF, $member;
		$tbplgin       =& $manager->getPlugin('NP_TrackBack');
		$enableHideurl =  true;
		$UserAgent     =  serverVar('HTTP_USER_AGENT');
		if(strstr($UserAgent, 'Hatena Diary Track Forward Agent')
		|| strstr($UserAgent, 'NP_TrackBack')
		|| strstr($UserAgent, 'TBPingLinkLookup')
		|| strstr($UserAgent, 'MT::Plugin::BanNoReferTb')
		|| strstr($UserAgent, 'livedoorBlog')) {
			$enableHideurl = false;
			$amount        = '-1';
		}
		$tmpHead = "\n\t\t" . '<div class="commentbody trackbackbody">';
		$tmpItem = "\n\t\t\t" . '<div class="commentator trackback">'
				 . "\n\t\t\t\t" . '<span class="canchor">#</span>'
				 . "\n\t\t\t\t" . '<span class="commentator trackbackblog">'
				 . '<a href="<%url%>" name="tb<%id%>" id="tb<%id%>"><%name%> : '
				 . '<%title%></a></span>'
				 . "\n\t\t\t\t" . '<span class="commenttime trackbacktime">'
				 . '<%date%></span>'
				 . "\n\t\t\t</div>"
				 . "\n\t\t\t<p><%excerpt%></p>";
		$tmpEmpt = '';
		$tmpFoot = "\n\t\t</div>";

		$query = 'SELECT'
			   . ' id,'
			   . ' url,'
			   . ' md5(url) AS urlHash,'
			   . ' blog_name,'
			   . ' excerpt,'
			   . ' title,'
			   . ' UNIX_TIMESTAMP(timestamp) AS timestamp'
			   . ' FROM %s'
			   . ' WHERE'
			   . '     tb_id = %d'
			   . ' AND block = 0'
			   . ' ORDER BY timestamp DESC';
		if ($amount == '-1') {
			$query .= ' LIMIT 9999999';
		}
		$query   = sprintf($query, sql_table('plugin_tb'), $tb_id);
		$res     = sql_query($query);

		$gVars = array(
					   'action' => $this->_getTrackBackUrl($tb_id),
					  );
		
		if ($member->isLoggedIn() && $member->isAdmin()){
			$gVars['admin']    = '<a href="'
							   . $CONF['PluginURL']
							   . 'trackback/index.php?action=list&amp;id='
							   . $tb_id . '" target="_blank">[admin]</a>';
			$gVars['pingform'] = '<a href="'
							   . $CONF['PluginURL']
							   . 'trackback/index.php?action=ping&amp;id='
							   . $tb_id . '" target="_blank">[pingform]</a>';
		}
		echo TEMPLATE::fill($tmpHead, $gVars);
		while ($row = mysql_fetch_array($res)) {
			$row['blog_name'] = htmlspecialchars($row['blog_name'], ENT_QUOTES);
			$row['title']     = htmlspecialchars($row['title'],     ENT_QUOTES);
			$row['excerpt']   = htmlspecialchars($row['excerpt'],   ENT_QUOTES);
			if (_CHARSET != 'UTF-8') {
				$row['blog_name'] = $tbplgin->_restore_to_utf8($row['blog_name']);
				$row['title']     = $tbplgin->_restore_to_utf8($row['title']);
				$row['excerpt']   = $tbplgin->_restore_to_utf8($row['excerpt']);
				$row['blog_name'] = mb_convert_encoding($row['blog_name'], _CHARSET, 'UTF-8');
				$row['title']     = mb_convert_encoding($row['title'],     _CHARSET, 'UTF-8');
				$row['excerpt']   = mb_convert_encoding($row['excerpt'],   _CHARSET, 'UTF-8');
			}
			$tbDate = strftime($tbplgin->getOption('dateFormat'), $row['timestamp']);
			$iVars = array(
                           'action'  => $this->_getTrackBackUrl($tb_id),
                           'name'    => $row['blog_name'],
                           'title'   => $row['title'],
                           'excerpt' => $tbplgin->_cut_string($row['excerpt'], 400),
                           'url'     => htmlspecialchars($row['url'], ENT_QUOTES),
                           'date'    => htmlspecialchars($tbDate,     ENT_QUOTES),
                           'id'      => intval($row['id'])
						  );
			if ($enableHideurl && $tbplgin->getOption('HideUrl') == 'yes') {
				$iVars['url'] = $CONF['ActionURL']
							  . '?action=plugin&amp;name=TrackBack&amp;type=redirect&amp;tb_id='
							  . $tb_id . '&amp;urlHash=' . $row['urlHash'];
			} else {
				$iVars['url'] = $row['url'];
			}
				echo TEMPLATE::fill($tmpItem, $iVars);
		}
		if (mysql_num_rows($res) == 0) {
			echo TEMPLATE::fill($tmpEmpt, $gVars);
		}
		mysql_free_result($res);
		echo TEMPLATE::fill($tmpFoot, $gVars);
	}

	function doSkinVar($skinType, $mode = 'show', $amount = '5')
	{ 
		global $manager, $blog, $CONF, $catid, $blogid, $archive, $subcatid;
		if ($archive) {
			$archive = $this->_dataCleaning($archive);
		}
		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$this->nowbid = intval($b->getID());
		$where  = ' and i.iblog=' . $this->nowbid;
		$dwhere = $where;

		if (!empty($catid)) {
			if ($manager->pluginInstalled('NP_MultipleCategories')) {
				$tempq    = $this->getMulticategoriesQuery($catid);
				$mtable   = $tempq['mtable'];
				$catwhere = $tempq['where'];
			} else {
				$where .= ' AND i.icat=' . intval($catid);
			}
		}

		if ($archive) {
			$monthlimit = (intGetVar('monthlimit') > 0) ? intGetVar('monthlimit') : 1;
			if ($monthlimit > 1) {
				sscanf($archive, '%d-%d-%d', $y, $m, $d);
				if ($monthlimit == 6) {
					if (($m / $monthlimit) > 1) {
						$m = 7;
					} else {
						$m = 1;
					}
				} elseif ($monthlimit == 3) {
					if (($m / $monthlimit) >  3) {
						$m = 10;
					} elseif (($m / $monthlimit) >  2) {
						$m = 7;
					} elseif (($m / $monthlimit) >  1) {
						$m = 4;
					} else {
						$m = 1;
					}
				}
				$archive = sprintf('%4d-%2d', $y, $m);
			}
			$where .= $this->getDateQuery($archive, $monthlimit);
		} else {
			$where .= ' and i.itime <= ' . mysqldate($b->getCorrectTime());
		}
		$pageswitch = $this->_createPageSwitch($where, $mtable, $amount, $skinType, $b);
		$startpos   = $pageswitch['startpos'];
		$daylist    = $pageswitch['daysList'];
		$query = 'SELECT'
			   . ' i.inumber as itemid,'
			   . ' i.ititle as title,'
			   . ' i.ibody as body,'
			   . ' i.itime,'
			   . ' i.imore as more,'
			   . ' i.iclosed as closed,'
			   . ' i.icat as catid,'
			   . ' c.cname as category,'
			   . ' UNIX_TIMESTAMP(i.itime) as timestamp,'
			   . ' m.mrealname as authorname,'
			   . ' m.mname as author,'
			   . ' m.mnumber as authorid'
			   . ' FROM '
			   . sql_table('member') . ' as m, '
			   . sql_table('category') . ' as c, '
			   . sql_table('item') . ' as i';
		$base  = ' WHERE i.iauthor = m.mnumber'
			   . ' and i.icat = c.catid'
			   . ' and i.idraft = 0';
		switch ($mode) {
			case 'pagelink':
				echo $pageswitch['pageLink'];
				return;
				break;
			case 'author':
				$authorquery = 'SELECT mrealname as result FROM %s WHERE mnumber = %d';
				$author      = quickQuery(sprintf($authorquery, sql_table('member'), $amount));
				echo $author;
				break;
			case 'authormail':
				$authorquery = 'SELECT memail as result FROM %s WHERE mnumber = %d';
				$authormail  = quickQuery(sprintf($authorquery, sql_table('member'), $amount));
				echo $authormail;
				break;
			case 'recent':
				echo '<div id="recent_list"><ul class="recent-list">' . "\n";
				for ($i = 1; $i <= $amount; $i++) {
					$dateData = $daylist[$startpos];
					$itemData = $this->_createItemLinkByDate($dateData, $mtable);
					$itemLink = $itemData['link'];
					echo '<li>' . '<a href="' . $itemLink . '">' . $dateData . "</a>\n"
					   . "\t" . '<ul class="recent-list-item">' . "\n";
					$shQuery = $query . $base . $dwhere
							. $this->getDateQuery($dateData)
							. ' ORDER BY SUBSTRING(i.itime, 1, 10) DESC, i.itime DESC';
					$b->showUsingQuery('tDiary/recent', $shQuery, 0, 1, 1);
					echo "\t</ul>\n</li>\n";
					$startpos++;
				}
				echo "</ul>\n</div>\n";
				break;
			case 'show':
				switch ($skinType) {
					case 'index':
					case 'item':
						$showquery = $query . $base . $dwhere;
						$this->showDiary($skinType, $showquery, $startpos, $daylist, $amount, $b);
						break;
					case 'archive':
						if (!$catid && intGetVar('categorylist') > 0) {
							$catID = 0;
						} else {
							$catID = intval($catid);
						}
						if ($catID || intGetVar('categorylist') > 0) {
							$where = $base . $dwhere;
							$this->showCategoryList($skinType, $query, $mtable, $where, $catwhere, $catID, $b);
						} else {
							$showquery = $query . $base . $dwhere;
							$this->showDiary($skinType, $showquery, $startpos, $daylist, $amount, $b);
						}
						break;
					case 'archivelist':
						$where = $base . $dwhere;
						$catID = ($catid) ? intval($catid) : 0;
						$this->showCategoryList($skinType, $query, $mtable, $where, $catwhere, $catID, $b);
						break;
					case 'search':
						unset($daylist);
						$showquery = $this->getSearchQuery()
								   . ' AND i.iblog = ' . $this->nowbid;
						$dayQuery  = $showquery
								   . ' ORDER BY i.itime DESC';
						$daysRes   = sql_query($dayQuery);
						while($days = mysql_fetch_object($daysRes)){
							$daylist[] = substr($days->itime, 0, 10);
						}
//						print_r($daylist);
//						exit;
						$this->showDiary($skinType, $showquery, $startpos, $daylist, $amount, $b);
						break;
					default:
						break;
				}
				break;
			case 'catlist':
				$monthlimit = (intGetVar('monthlimit') > 0) ? intGetVar('monthlimit') : 1;
				$bid = intval($this->nowbid);
				$catque = 'SELECT c.catid as catid,'
						. ' c.cname as catname'
						. ' FROM ' . sql_table('category') . ' as c'
						. ' WHERE c.cblog = ' . $bid;
				$catres = sql_query($catque);
				echo '<p>Categories |';
				while ($catdata = mysql_fetch_object($catres)) {
					$catlink = $this->_createCategoryIDLink($catdata->catid, $skinType);
					echo '<a href="' . $catlink . '">' . $catdata->catname . "</a> | \n";
				}
				echo '</p>';
				break;
		}
	}

	function doTemplateVar(&$item, $mode = 'itemlink')
	{
		global $manager, $CONF;
		$blogID   = intval(getBlogidFromItemID($item->itemid));
		$itemdate = substr($item->itime, 0, 10);
		switch ($mode) {
			case 'itemlink':
				$linkdata = $this->_createItemLinkByDate($itemdate);
				echo $linkdata['link'] . '#' . $item->itemid;
				break;
			case 'catlink':
				sscanf($itemdate, '%d-%d-%d', $y, $m, $d);
				$monthlimit = $this->getOption('monthlimit');
				$catdate    = sprintf('%04d-%02d', $y, $m);
				$catparam['catid'] = $item->catid;
				switch ($monthlimit) {
//					case 1:
//						$catparam = array('catid' => $item->catid);
//						$catlink  = createArchiveLink($blogID, $catdate, $catparam);
//						break;
					case 2:
//						$catparam = array('catid'      => $item->catid,
//										  'monthlimit' => 3);
						$catparam['monthlimit'] = 3;
//						$catlink  = createArchiveLink($blogID, $catdate, $catparam);
						break;
					case 3:
//						$catparam = array('catid'      => $item->catid,
//										  'monthlimit' => 6);
						$catparam['monthlimit'] = 6;
//						$catlink  = createArchiveLink($blogID, $catdate, $catparam);
						break;
					case 4:
//						$catparam = array('catid' => $item->catid);
						$catdate = substr($item->itime, 0, 4);
//						$catlink  = createArchiveLink($blogID, $catdate, $catparam);
						break;
				}
				$catlink  = createArchiveLink($blogID, $catdate, $catparam);
				echo $catlink;
				break;
			default: 
				break;
		}
	}

	function event_InitSkinParse($data)
	{
		global $CONF, $manager;
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		if (serverVar('REQUEST_URI') == '') {
			$uri = (serverVar('QUERY_STRING')) ?
				serverVar('SCRIPT_NAME') . serverVar('QUERY_STRING') : serverVar('SCRIPT_NAME');
		} else { 
			$uri = serverVar('REQUEST_URI');
		}
		$page_str = ($usePathInfo) ? 'page/' : 'page=';
		if ($manager->pluginInstalled('NP_CustomURL') || $manager->pluginInstalled('NP_Magical')) {
			$page_str = 'page_';
		}
		list($org_uri, $currPage) = explode($page_str, $uri, 2);
		if (getVar('page')) {
			$y = $m = $d = '';
			sscanf(getVar('page'), '%4d%2d%2d%s', $y, $m, $d, $trush);
			if (!empty($d)) {
				$currPage = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} else {
				$currPage = sprintf('%04d-%02d', $y, $m);
			}
		} else {
			sscanf($currPage, '%4d%2d%2d%s', $y, $m, $d, $trush);
			if (!empty($d)) {
				$currPage = sprintf('%04d-%02d-%02d', $y, $m, $d);
			} else {
				$currPage = sprintf('%04d-%02d', $y, $m);
			}
		}
		$_GET['page']   = $currPage;
		$this->currPage = $currPage;
		$this->pagestr  = $page_str;
	}


}








