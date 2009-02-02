<?php
class NP_SearchResultsEX extends NucleusPlugin
{
	function getName()
	{
	    return 'SearchResults EX';
	} 

	function getAuthor()
	{
	    return 'Taka + nakahara21 + Andy + shizuki';
	}

	function getURL()
	{
	    $url = 'http://shizuki.kinezumi.net/'
	    	 . 'NucleusCMS/Plugins/SearchResultsEX/';
	    return $url;
	}

	function getVersion()
	{
	    return '0.08';
	}

	function getDescription()
	{
		$desc = 'This plugin replace &lt;%searchresults()%&gt; '
			  . 'with page switch<br />'
			  . 'Usage: &lt;%SearchResultsEX(Template,15,,2,500)%&gt;<br />'
			  . 'Requered NP_ExtensibleSearch for Extensible searchresults.';
		return $desc;
	} 

	function getEventList()
	{
	    global $manager;
	    $event_arr = array(
	    					'InitSkinParse'
	    				   );
	    if ($manager->pluginInstalled('NP_ExtensibleSearch')) {
	    	$event_arr[] = 'PreSearchResults';
	    }
	    return $event_arr;
	}

//	function getPluginDep()
//	{
//	    return array('NP_ExtensibleSearch');
//	}

	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install()
	{
		$this->createOption("commentsearch",
							"Comments are included in a search target",
//						  . "(Need 'NP_ExtensibleSearch').",
							"yesno",
							"yes");
		$this->createOption("trackbacksearch",
							"TrackBacks are included in a search target",
//						  . "(Need 'NP_ExtensibleSearch').",
							"yesno",
							"yes");
		$this->createOption("tagsearch",
							"Tags are included in a search target",
//						  . "(Need 'NP_ExtensibleSearch').",
							"yesno",
							"no");
		$this->createOption("srex_ads1",
		                    "[Ads code 1] "
		                  . "Ads code where it's caught between "
		                  . "1st and 2nd entry and shown",
		                    "textarea",
		                    "");
		$this->createOption("srex_ads2",
		                    "[Ads code 1] "
		                  . "Ads code where it's caught between "
		                  . "2nd and 3rd entry and shown",
		                    "textarea",
		                    "");
	}

	function event_PreSearchResults(&$data)
	{	// Orign NP_CommentSearch by Andy
		global $blog, $manager;
		$blogs       =  $data['blogs'];
		$query       =  $data['query'];
		$items       =& $data['items'];
		$searchclass =& new SEARCH($query);

		$sqlquery  = 'SELECT i.inumber as itemid FROM ';
		$tables    = sql_table('item') . ' as i ';
		$where_str = '';
		if ($this->getOption('commentsearch') == 'yes') {
		    	$tables    .= ' left join ' . sql_table('comment') . ' as cm'
		    			 	. ' on i.inumber = cm.citem ';
		    	$where_str .= 'xxx.cm.cbody';
		}
		
		if ($this->getOption('tagsearch') == 'yes' &&
			$manager->pluginInstalled('NP_TagEX')) {
				$tables    .= ' left join ' . sql_table('plug_tagex') . ' as tag'
							. ' on i.inumber = tag.inum';
				$where_str .= ',xxx.tag.itags';
		}
		if ($this->getOption('trackbacksearch') == 'yes' &&
			$manager->pluginInstalled('NP_TrackBack')) {
				$tables    .= ' left join ' . sql_table('plugin_tb') . ' as t'
							. ' on i.inumber = t.tb_id';
				$where_str .= ',xxx.t.title,xxx.t.excerpt';
		}
		$sqlquery .= $tables;
		$where     = $searchclass->boolean_sql_where($where_str);
		$where     = strtr($where, array('i.xxx.' => ''));
		$sqlquery .= ' WHERE i.idraft = 0'
				   . ' and i.itime <= ' . mysqldate($blog -> getCorrectTime())
				   . ' and i.iblog in (' . implode(',', $blogs) . ') '
				   . ' and ' . $where;
		$res       = sql_query($sqlquery);
		$array     = array();
		while ($itemid = mysql_fetch_row($res)) {
			array_push($array, $itemid[0]);
		}
		$data['items'] = array_unique(array_merge($items,$array));
	}

	function doSkinVar($skinType,
	                   $template   = 'default/index',    // display template
	                   $p_amount   = 10,                 // amount par page
	                   $type       = 1,                  // page switch type
	                   $bmode      = 'all',              // blog mode
	                   $maxresults = '')                 // max results
	{
		global $manager, $CONF, $blog, $query, $amount;
		if (!$template) {
		    $template   = 'default/index';
		}
		if (!$p_amount) {
		    $p_amount   = 10;
		}
		if (!$type) {
		    $type   = 1;
		}
		if (!$bmode) {
		    $bmode   = 'all';
		}
		$this->maxamount = ($maxresults) ? $maxresults : 0;
		$type    = floatval($type);
		$typeExp = intval(($type - floor($type))*10); //0 or 1 or 9
		list($pageamount, $offset) = sscanf($p_amount, '%d(%d)');
		if (!$pageamount) $pageamount = 10;
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

		if ($blog) {
			$b =& $blog; 
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$nowbid = intval($b->getID());

		if ($template == 'form') {
			$q = getVar('query');
			$search_form = '<form '
						 .     'method="get" '
						 .     'action="' . createBlogidLink($nowbid) . '" '
						 . ">\n"
						 . "\t" . '<div class="searchform">' . "\n"
						 . "\t\t<input "
						 .         'name="query" '
						 .         'class="formfield" '
						 .         'size="10" '
						 .         'maxlength="60" '
						 .         'accesskey="4" '
						 .         'value="' . $q . '" '
						 .      "/>\n\t\t<br />\n"
						 . "\t\t<input "
						 .         'type="submit" '
						 .         'value="' . _SEARCHFORM_SUBMIT . '" '
						 .         'class="formbutton" '
						 .      "/>\n"
						 . "\t</div>\n"
						 . "</form>\n";
			echo $search_form;
			return;
		}

		$s_blogs = '';
		if ($bmode != 'all') {
			$s_blogs .= ' and i.iblog = ' . $nowbid;
		} elseif ($hide[0] && $bmode=='all') {
			foreach ($hide as $val) {
				if (intval($val) < 1) {
					$val = getBlogIDFromName($val);
				}
				$s_blogs .= ' and i.iblog != ' . intval($val);
			}
		} elseif ($show[0] && $bmode=='all') {
			foreach ($show as $val) {
				if(intval($val) < 1){
					$val = getBlogIDFromName($val);
				}
				$w[] = intval($val);
			}
			$s_blogs .= ' and i.iblog in (' . implode(",", $w) . ')';
		}
		$manager->notify('PreBlogContent',array('blog' => &$b, 'type' => 'searchresults'));
// Origin NP_ExtensibleSearch by Andy
		$highlight = '';
		$query     = $this->_hsc($query);
		if ($manager->pluginInstalled('NP_ExtensibleSearch')) {
			$explugin =& $manager->getPlugin('NP_ExtensibleSearch');
			$sqlquery =  $explugin->getSqlQuery($query, $amount, $highlight);
		} else {
//			$sqlquery = $b->getSqlSearch($query, $amount, $highlight);
			$sqlquery = $this->getSqlQuery($b, $query, $amount, $highlight);
		}
		$que_arr  = explode(' ORDER BY', $sqlquery, 2);
		$sqlquery = implode($s_blogs . ' ORDER BY', $que_arr);
		if (!$sqlquery) {
			// no query -> show everything
			$exQuery = '';
			$amfound = $b->readLogAmount($template, $maxresults, $exQuery, $query, 1, 1);
		} else {
			$entries   = $this->getArray($sqlquery);
			$allAmount = count($entries);
			if ($allAmount > 0) {
			    $switchParam = array (
			    					  $type,
			    					  $pageamount,
			    					  $offset,
			    					  $entries,
			    					  $b
			    					 );
				$page_switch = $this->PageSwitch($switchParam);
				if ($typeExp != 9) {
				    echo $page_switch['buf'];
//				    print_r($page_switch);
				}
			    $showParams = array (
			    					 $template,
			    					 $sqlquery,
			    					 $highlight,
			    					 $page_switch['startpos'],
			    					 $pageamount,
			    					 $b
			    					);
				$this->_showUsingQuery($showParams); 
				if ($type >= 1 && $typeExp != 1) {
				    echo $page_switch['buf'];
				}
			} else {
				$template =& $manager->getTemplate($template);
				$vars = array(
							  'query'    => $query,
							  'blogid'   => $nowbid
							 );
				echo TEMPLATE::fill($template['SEARCH_NOTHINGFOUND'], $vars);
			}
		}
		$manager->notify('PostBlogContent',array('blog' => &$b, 'type' => 'searchresults'));
	}

//*
	function getSqlQuery($b, $query, $amountMonths = 0, &$highlight, $mode = '')
	{
		$searchclass =& new SEARCH($query);
		$highlight   = $searchclass->inclusive;
		if ($searchclass->inclusive == '') {
			return '';
		}
		$select  = $searchclass->boolean_sql_select('ititle,ibody,imore');
		$blogs   = $searchclass->blogs;
		$blogs[] = $b->getID();
		$blogs   = array_unique($blogs);

		$sqlquery = $b->getSqlSearch($query, $amount, $highlight);
		if (preg_match('/^(SELECT COUNT\(\*\) as result)/', $sqlquery)) {
			$mode = 1;
		}
		$items    = $this->getArray($sqlquery);
		$sqldata  = array(
						  'blogs' => &$blogs,
						  'items' => &$items,
						  'query' => $query
						 );
		$this->event_PreSearchResults($sqldata);

		if ($mode == '') {
			$sqlquery = 'SELECT '
					  . '      i.inumber   as itemid, '
					  . '      i.ititle    as title, '
					  . '      i.ibody     as body, '
					  . '      m.mname     as author, '
					  . '      m.mrealname as authorname, '
					  . '      i.itime, '
					  . '      i.imore     as more, '
					  . '      m.mnumber   as authorid, '
					  . '      m.memail    as authormail, '
					  . '      m.murl      as authorurl, '
					  . '      c.cname     as category, '
					  . '      i.icat      as catid, '
					  . '      i.iclosed   as closed '
					  . 'FROM '
					  .        sql_table('item')   .   ' as i, '
					  .        sql_table('member')  .  ' as m, '
					  .        sql_table('category') . ' as c '
					  . 'WHERE '
					  . '      i.iauthor = m.mnumber '
					  . ' and  i.icat    = c.catid';
			if ($items) {
				$sqlquery .= ' and i.inumber in (' . implode(',', $items) . ')';
			} else {
				$sqlquery .= ' and 1=2 ';
			}
			if ($select) {
				$sqlquery .= ' ORDER BY score DESC';
			} else {
				$sqlquery .= ' ORDER BY i.itime DESC ';
			}
		} else {
				$sqlquery = 'SELECT COUNT(*) FROM ' . sql_table('item') . ' as i WHERE ';
			if ($items) {
				$sqlquery .= ' and i.inumber in (' . implode(',', $items) . ')';
			} else {
				$sqlquery .= ' and 1=2 ';
			}
		}
		return $sqlquery;
	}
//*/

	function getArray($query) {
		$res = sql_query($query);
		$array = array();
		while ($itemid = mysql_fetch_row($res)) {
			array_push($array, $itemid[0]);
		}
		return $array;
	}

	function _showUsingQuery($showParams)
	{
		$template      = $showParams[0];
		$showQuery     = $showParams[1];
		$highlight     = $showParams[2];
		$q_startpos    = $showParams[3];
		$q_amount      = $showParams[4];
		$b             = $showParams[5];
		$onlyone_query = $showQuery
					   . ' LIMIT '
					   . intval($q_startpos)
					   . ', 1';
		$b->showUsingQuery($template, $onlyone_query, intval($highlight), 1, 1); 
		echo $this->getOption('srex_ads1');

		$q_startpos++;
		$q_amount--;
		if ($q_amount <= 0) {
		    return;
		}
		$onlyone_query = $showQuery
					   . ' LIMIT '
					   . intval($q_startpos)
					   . ', 1';
		$b->showUsingQuery($template, $onlyone_query, intval($highlight), 1, 1); 
		if (mysql_num_rows(sql_query($onlyone_query))) {
			echo $this->getOption('srex_ads2');
		}

		$q_startpos++;
		$q_amount--;
		if ($q_amount < 0) {
		    return;
		}
		$second_query = $showQuery
					  . ' LIMIT '
					  . intval($q_startpos) . ','
					  . intval($q_amount);
		$b->showUsingQuery($template, $second_query, intval($highlight), 1, 1);
	}

	function event_InitSkinParse($data)
	{
		global $CONF, $manager;
		$this->skinType = $data['type'];
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		if (serverVar('REQUEST_URI') == '') {
			$uri = (serverVar('QUERY_STRING')) ?
				serverVar('SCRIPT_NAME') . serverVar('QUERY_STRING') :
				serverVar('SCRIPT_NAME');
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

	function PageSwitch($switchParam)
	{
		global $CONF, $manager, $query;
// initialize
		extract($switchParam);
		$type          = intval($switchParam[0]);
		$pageamount    = intval($switchParam[1]);
		$offset        = intval($switchParam[2]);
		$entries       = $switchParam[3];
		$b             = $switchParam[4];
		$startpos      = 0;
		$nowblogid     = $b->getID();
		$usePathInfo   = ($CONF['URLMode'] == 'pathinfo');
		$page_str      = $this->pagestr;
		$currentpage   = $this->currPage; 
		$useCustomURL  = ($manager->pluginInstalled('NP_CustomURL'));
		$useMagicalURL = ($manager->pluginInstalled('NP_Magical') || $manager->pluginInstalled('NP_MagicalURL2'));
		if ($useCustomURL) {
			$plugCustomURL =& $manager->getPlugin('NP_CustomURL');
			$customFlag    = ($plugCustomURL->getBlogOption(intval($nowblogid), 'use_customurl') == 'yes');
			$redirectSFlag = ($plugCustomURL->getBlogOption(intval($nowblogid), 'redirect_search') == 'yes');
		}

// createBaseURL
		$pagelink = createBlogidLink($nowblogid);
		if ($useCustomURL && $customFlag && $redirectSFlag) {
			$que_str    = $query;
			$que_str    = $this->_hsc($que_str);
			$que_str    = mb_eregi_replace('/', 'ssslllaaassshhh', $que_str);
			$que_str    = mb_eregi_replace("'", 'qqquuuooottt', $que_str);
			$que_str    = mb_eregi_replace('&', 'aaammmppp', $que_str);
			$que_str    = urlencode($que_str);
			$search_str = 'search/' . $que_str . '/';
		} else {
			if ($useMagicalURL && substr($pagelink, -5) == '.html') {
				$pagelink   = substr($pagelink, 0, -5) . '_';
			}
			$search_str = '?query=' . $query;
			if (is_numeric(getVar('amount')) && intGetVar('amount') >= 0) {
				$search_str .= '&amp;amount=' . intGetVar('amount');
			}
			if (strpos($pagelink, 'blogid=' . $nowblogid) === FALSE) {
				$search_str .= '&amp;blogid=' . $nowblogid;
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
				if ($useMagicalURL && substr($pagelink, -1) == '_') {
					$pagelink = $pagelink;
				} else {
					$pagelink .= '/';
				}
			}
		}
		if (strstr ($pagelink, '//')) {
			$pagelink = preg_replace("/([^:])\/\//", "$1/", $pagelink);
		}
		if (strpos($pagelink, '?')) {
			$search_str = str_replace('?', '&amp;', $search_str);
		}

// Process pages
		if ($currentpage > 0) {
			$startpos = ($currentpage - 1) * $pageamount;
		} else {
			$currentpage = 1;
		}
		$totalamount = 0;
		if (is_array($entries)) {
			$totalamount = count($entries);
		}
		if (!empty($this->maxamount) && $this->maxamount < $totalamount) {
			$totalamount = intval($this->maxamount);
		}
		$totalamount = intval($totalamount);
		if ($offset) {
			$startpos    += $offset;
			$totalamount -= $offset;
		}
		$totalpages  = ceil($totalamount / $pageamount);
		$totalpages  = intval($totalpages);
		if ($startpos > $totalamount) {
			$currentpage = $totalpages;
			$startpos    = $totalamount - $pageamount;
		}

// Create pageswitch
		$prevpage = ($currentpage > 1) ? intval($currentpage) - 1 : 0;
		$nextpage = intval($currentpage) + 1;
		if ($useCustomURL && $customFlag && $redirectSFlag) {
			$lastpagelink = $pagelink . $search_str . $page_str . '1.html';
		} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
			$lastpagelink = $pagelink . $page_str . '1.html' . $search_str;
		} else {
			$lastpagelink = $pagelink . $page_str . '1' . $search_str;
		}
		if ($useCustomURL && $customFlag && $redirectSFlag) {
			$lastpagelink = $pagelink . $search_str . $page_str . $totalpages . '.html';
		} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
			$lastpagelink = $pagelink . $page_str . $totalpages . '.html' . $search_str;
		} else {
			$lastpagelink = $pagelink . $page_str . $totalpages . $search_str;
		}

		if ($type >= 1) {
			$buf .= '<div class="pageswitch">' . "\n";
//			$buf .= "<a rel=\"first\" title=\"first page\" href=\"{$firstpagelink}\">&lt;TOP&gt;</a> | \n";
			if (!empty($prevpage)) {
				if ($useCustomURL && $customFlag && $redirectSFlag) {
					$prevpagelink = $pagelink . $search_str . $page_str . $prevpage . '.html';
				} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
					$prevpagelink = $pagelink . $page_str . $prevpage . '.html' . $search_str;
				} else {
					$prevpagelink = $pagelink . $page_str . $prevpage . $search_str;
				}
				$buf .= '<a href="' . $prevpagelink . '" title="Previous page" rel="Prev">&laquo;Prev</a> |';
			} elseif ($type >= 2) {
				$buf .= "&laquo;Prev |";
			}
			if (intval($type) == 1) {
				$buf .= "\n";
			}
			if (intval($type) == 2) {
				$sepstr = '&middot;';
				$buf   .= "|";
				for ($i=1; $i<=$totalpages; $i++) {
					if ($useCustomURL && $customFlag && $redirectSFlag) {
						$i_pagelink = $pagelink . $search_str . $page_str . $i . '.html';
					} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
						$i_pagelink = $pagelink . $page_str . $i . '.html' . $search_str;
					} else {
						$i_pagelink = $pagelink . $page_str . $i . $search_str;
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
					if ($useCustomURL && $customFlag && $redirectSFlag) {
						$i_pagelink = $pagelink . $search_str . $page_str . $i . '.html';
					} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
						$i_pagelink = $pagelink . $page_str . $i . '.html' . $search_str;
					} else {
						$i_pagelink = $pagelink . $page_str . $i . $search_str;
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
				if ($useCustomURL && $customFlag && $redirectSFlag) {
					$nextpagelink = $pagelink . $search_str . $page_str . $nextpage . '.html';
				} elseif (($useMagicalURL && substr($pagelink, -1) == '_') || $useCustomURL) {
					$nextpagelink = $pagelink . $page_str . $nextpage . '.html' . $search_str;
				} else {
					$nextpagelink = $pagelink . $page_str . $nextpage . $search_str;
				}
				$buf .= '| <a href="' . $nextpagelink . '" title="Next page" rel="Next">Next&raquo;</a>' . "\n";

			} elseif ($type >= 2) {
				$buf .= "| Next&raquo;\n";
			}
//			$buf .= " | <a rel=\"last\" title=\"Last page\" href=\"{$lastpagelink}\">&lt;LAST&gt;</a>\n";
			$buf .= "</div>\n";
			return array('buf' => $buf, 'startpos' => intval($startpos));
		}
	}

	function doTemplateVar(&$item, $maxLength = 250, $addHighlight = 1)
	{	// Orign NP_ChoppedDisc.php by nakahara21
		global $CONF, $manager, $member, $catid;

// Paese setting
		$item_id     = intval($item->itemid);
		$resultQuery = 'SELECT '
					 . '      %s as result '
					 . 'FROM '
					 . '      %s '
					 . 'WHERE '
					 . '      %s = ' . $item_id;

// Parse item
		$results['Item'] = strip_tags($item->body).strip_tags($item->more);

// Parse commets
		if ($this->getOption("commentsearch")) {
			$cmntQuery = sprintf($resultQuery, cbody, sql_table('comment'), 'citem');
			$response  = sql_query($cmntQuery);
			while ($cmnt = mysql_fetch_object($response)) {
				$results['comment'] .= strip_tags($cmnt->result);
			}
		}

// Parse trackback
		if ($this->getOption("trackbacksearch") &&
			$manager->pluginInstalled('NP_TrackBack')) {
//			$titlQuery = sprintf($resultQuery, title, sql_table('plugin_tb'), 'tb_id');
//			$response  = quickQuery($titlQuery);
//			$results['Trackback_title'] = strip_tags($response);
			
			$trbkQuery =sprintf($resultQuery, excerpt, sql_table('plugin_tb'), 'tb_id');
			$response  = sql_query($trbkQuery);
			while ($tb = mysql_fetch_object($response)) {
				$results['Trackback'] .= strip_tags($tb->result);
			}
		}
		$queryStrings = $this->getQueryStrings();
		foreach($results as $resKey => $resValue) {
			$strCount = 1;
			foreach($queryStrings as $queryValue) {
				if (!(mb_substr_count($resValue, $this->_hsc($queryValue)))) {
					$strCount++;
				}
			}
			if ($strCount > count($queryStrings)) {
				$resValue = '';
			}
			if ($resValue) {
				if ($addHighlight) {
					$i = 0;
					foreach($queryStrings as $queryValue) {
						mb_regex_encoding(_CHARSET);
						$pattern  = "<span class='highlight_{$i}'>{$queryValue}</span>";
						$resValue = mb_eregi_replace($this->_hsc($queryValue), $pattern, $resValue);
						$i++;
						if ($i == 10) {
							$i = 0;
						}
					}
					$str_array = mb_split('</span>', $resValue);
					$num       = count($str_array);
					$lastKey   = $num +(-1);
					$resWidth  = 0;
					$check     = FALSE;
					foreach($str_array as $key => $value) {
						$tmpStr    = mb_split("<span class='highlight", $value);
						$tmpStr[0] = mb_eregi_replace('&lt;', '<', $tmpStr[0]);
						$tmpStr[0] = mb_eregi_replace('&gt;', '>', $tmpStr[0]);
						$tmpStr[0] = mb_eregi_replace('&amp;', '&', $tmpStr[0]);
//						$tmpStr[0] = mb_eregi_replace('&nbsp;', ' ', $tmpStr[0]);
						$lastp     = mb_strwidth($tmpStr[0], _CHARSET);
						if ($key == 0) {
							if ($lastp > 20) {
								$temp_s = '...'
										. mb_substr($tmpStr[0], -20, 20, _CHARSET);
							} else {
								$temp_s = $tmpStr[0];
							}
							$resWidth += 20;
						} elseif ($key > 0 && $key < $lastKey) {
							if ($lastp > 30) {
								$temp_s = mb_substr($tmpStr[0], 0, 10, _CHARSET)
										. '...'
										. mb_substr($tmpStr[0], -10, 10, _CHARSET);
							} else {
								$temp_s = $tmpStr[0];
							}
							$resWidth += 30;
						} elseif ($key == $lastKey) {
							if ($lastp > 20) {
								$temp_s = mb_substr($tmpStr[0], 0, 20, _CHARSET)
										. '...';
							} else {
								$temp_s = $tmpStr[0];
							}
							$resWidth += 20;
						}
						if ($key != $lastKey) {
							$str_array[$key] = $this->_hsc($temp_s)
											 . "<span class='highlight"
											 . $tmpStr[1];
						} else {
							$str_array[$key] = $this->_hsc($temp_s);
						}
						if ($maxLength < $resWidth && !$check) {
							$strKey = $key;
							$check  = TRUE;
						}
					}
					if ($strKey > 0) {
						$str_array = array_slice($str_array, 0, $strKey);
						$str_array[$strKey] = $str_array[$strKey] . '...';
					}
					$resValue = '<span class="queryPosition">in '
							  . $this->_hsc($resKey)
							  . '</span>'
							  . '<div class="queryResults">'
							  . @implode('</span>', $str_array)
							  . '</div>';
				} else {
					$tmpValue = '<span class="queryPosition">in '
							  . $this->_hsc($resKey)
							  . '</span>'
							  . '<div class="queryResults">'
							  . $this->_hsc(shorten($resValue, $maxLength, '...'))
							  . '</div>';
					$resValue = $tmpValue;
				}
				echo $resValue;
			}
		}
	}

	function getQueryStrings()
	{
		global $query;
//		if (requestVar('query')) {
			$q = 'query';
//			$urlq = serverVar("QUERY_STRING");
			if(_CHERSET != 'UTF-8') {
				$query = mb_convert_encoding($query, "UTF-8", _CHARSET);
			}
			$urlq = urlencode($query);
			$urlq = preg_replace('|[^a-zA-Z0-9-~+_.#=&;,:@%]|i', '', $urlq);
//			$urlq = str_replace('?', '', $urlq);
//			$urlq = str_replace('or+', '', $urlq);
			$SQ = str_replace('or+', '', $urlq);
			/*$urlq = explode('&', $urlq);
			foreach ($urlq as $v) {
				$tmpq = explode('=', $v);
				if ($tmpq[0] == $q) $SQ = $tmpq[1];
			}*/
			$SQarray = explode('+', $SQ);
			return array_map(array(&$this, '_rawdecode'), $SQarray);
//		}
	}

	function _rawdecode($str)
	{
		$str = rawurldecode($str);
		if(_CHERSET != 'UTF-8') {
			$str = mb_convert_encoding($str, _CHARSET, "UTF-8");
		}
		return $str;
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

} 

