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
	    return 'http://shizuki.kinezumi.net/NucleusCMS/Plugins/SearchResultsEX/';
	}

	function getVersion()
	{
	    return '0.03';
	}

	function getDescription()
	{
		return 'This plugin replace &lt;%searchresults()%&gt; with page switch<br />
		Usage: &lt;%SearchResultsEX(default/index,15,,2,500)%&gt;<br />
		Requered NP_ExtensibleSearch'; 
	} 

	function getEventList()
	{
	    return array('PreSearchResults');
	}

	function getPluginDep()
	{
	    return array('NP_ExtensibleSearch');
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

	function install()
	{
		$this->createOption("srex_ads1",
		                    "[Ads code] code displayed under first and second item of the page",
		                    "textarea",
		                    "");
		$this->createOption("srex_ads2",
		                    "[Ads code] code displayed under second and third item of the page",
		                    "textarea",
		                    "");
	}

	function event_PreSearchResults(&$data)
	{	// Orign NP_CommentSearch by Andy
		global $blog, $manager;
		$blogs       = $data['blogs'];
		$query       = $data['query'];
		$items       = & $data['items'];
		$searchclass =& new SEARCH($query);

		$sqlquery  = 'SELECT i.inumber as itemid FROM ';
		$tables    = sql_table('item') . ' as i ';
		$where_str = 'xxx.cm.cbody';
//	if ($manager->pluginInstalled('NP_TagEX')) {
//		$tables    .= ' left join ' . sql_table('plug_tagex') . ' as tag on i.inumber = tag.inum';
//		$where_str .= ',xxx.tag.itags';
//	}
		if ($manager->pluginInstalled('NP_TrackBack')) {
			$tables    .= ' left join '.sql_table('plugin_tb').' as t on i.inumber = t.tb_id';
			$where_str .= ',xxx.t.title,xxx.t.excerpt';
		}
		$sqlquery .= $tables . ' left join ' . sql_table('comment')
		           . ' as cm on i.inumber = cm.citem ';
		$where     = $searchclass->boolean_sql_where($where_str);
		$where     = strtr($where, array('i.xxx.'=> ''));
		$sqlquery .= ' WHERE i.idraft = 0 and i.itime <= ' . mysqldate($blog -> getCorrectTime())
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
		global $manager, $CONF, $blog, $query, $amount, $startpos;
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

//        if (!$manager->pluginInstalled('NP_ExtensibleSearch') && getNucleusVersion() < ???) {
//            return;	// Future
//        }
		$type = floatval($type);
		$typeExp = intval(($type - floor($type))*10); //0 or 1 or 9
		list($pageamount, $offset) = sscanf($p_amount, '%d(%d)');
		if (!$pageamount) $pageamount = 10;
		if (preg_match("/^(<>)?([0-9\/]+)$/",$bmode,$matches)) {
			if ($matches[1]) {
				$hide = explode("/",$matches[2]);
				$show = array();
			} else {
				$hide = array();
				$show = explode("/",$matches[2]);
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
			echo '<form method="get" action="' . createBlogidLink($nowbid) . '">' . "\n";
			echo "\t" . '<div class="searchform">' . "\n";
			echo "\t\t";
			echo '<input name="query" class="formfield" size="10" maxlength="60"';
			echo ' accesskey="4" value="' . $q . '" />' . "\n";
			echo "\t\t" . '<br />' . "\n";
			echo "\t\t" . '<input type="submit" value="' . _SEARCHFORM_SUBMIT . '" ';
			echo 'class="formbutton" />' . "\n";
			echo "\t" . '</div>' . "\n";
			echo '</form>' . "\n";
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
			$s_blogs .= ' and i.iblog in ('.implode(",",$w).')';
		}
// Origin NP_ExtensibleSearch by Andy
		$highlight = '';
//		if ($manager->pluginInstalled('NP_ExtensibleSearch')) {
			$explugin  =& $manager->getPlugin('NP_ExtensibleSearch');
			$sqlquery  = $explugin->getSqlQuery(htmlspecialchars($query), $amount, $highlight);
//		} elseif (getNucleusVersion() >= ???) {
//			$sqlquery  = getSqlQuery($query, $amount, $highlight);
//		}
		$que_arr   = explode(' ORDER BY', $sqlquery, 2);
		$sqlquery  = implode($s_blogs.' ORDER BY', $que_arr);
		if (!$sqlquery) {
			// no query -> show everything
			$extraquery = '';
			$amountfound = $b->readLogAmount($template, $maxresults, $extraQuery, $query, 1, 1);
		} else {
//			if ($manager->pluginInstalled('NP_ExtensibleSearch')) {
				$entries = $explugin->getArray($sqlquery);
//			} elseif (getNucleusVersion() >= ???) {
//				$entries  = $???????->getArray($sqlquery);
//			}
			if (count($entries) > 0) {
				$page_switch = $this->PageSwitch($type, $pageamount, $offset, $entries, $b);
				if ($typeExp != 9) echo $page_switch;
				$this->_showUsingQuery($template, $sqlquery, $highlight, $startpos, $pageamount, $b); 
				if ($type >= 1 && $typeExp != 1) echo $page_switch;
			} else {
				$template =& $manager->getTemplate($template);
				$vars = array(
				'query'      => htmlspecialchars($query),
				'blogid'   => $nowbid
				);
				echo TEMPLATE::fill($template['SEARCH_NOTHINGFOUND'],$vars);
			}
		}
	}

	function _showUsingQuery($template, $showQuery, $highlight, $q_startpos, $q_amount, $b)
	{
		$onlyone_query = $showQuery . ' LIMIT ' . intval($q_startpos) .', 1';
		$b->showUsingQuery($template, $onlyone_query, intval($highlight), 1, 1); 
		echo $this->getOption('srex_ads1');

		$q_startpos++;
		$q_amount--;
		if ($q_amount < 0) {
		    return;
		}
		$onlyone_query = $showQuery . ' LIMIT ' . intval($q_startpos) .', 1';
		$b->showUsingQuery($template, $onlyone_query, intval($highlight), 1, 1); 
		if (mysql_num_rows(sql_query($onlyone_query))) {
			echo $this->getOption('srex_ads2');
		}

		$q_startpos++;
		$q_amount--;
		if ($q_amount < 0) {
		    return;
		}		$second_query = $showQuery . ' LIMIT ' . intval($q_startpos) . ',' . intval($q_amount);
		$b->showUsingQuery($template, $second_query, intval($highlight), 1, 1);
	}

	function PageSwitch($type, $pageamount, $offset, $entries, $b)
	{	// Orign NP_ShowBlogs by Taka + nakahara21
		global $CONF, $manager, $startpos, $query;
		$startpos = intval($startpos);
		$pageamount = intval($pageamount);
		$offset = intval($offset);
		$usePathInfo = ($CONF['URLMode'] == 'pathinfo');
		if (serverVar('REQUEST_URI') == '') {
			$uri = (serverVar('QUERY_STRING')) ?
				serverVar('SCRIPT_NAME') . serverVar('QUERY_STRING') : serverVar('SCRIPT_NAME');
		} else { 
			$uri = serverVar('REQUEST_URI');
		}
		$page_str = ($usePathInfo) ? 'page/' : 'page=';
		$blogID = intval($b->getID());
		$installedCustomURL = ($manager->pluginInstalled('NP_CustomURL'));
		$installedMagical   = ($manager->pluginInstalled('NP_Magical') || $manager->pluginInstalled('NP_MagicalURL2'));
		if ($installedCustomURL) {
		    $plugCustomURL = $tplugin =& $manager->getPlugin('NP_CustomURL');
		    $customFlag = ($plugCustomURL->getBlogOption(intval($blogID), 'use_customurl') == 'yes');
			$redirectFlag = ($plugCustomURL->getBlogOption(intval($blogID), 'redirect_normal') == 'yes');
		    $redirectSFlag = ($plugCustomURL->getBlogOption(intval($blogID), 'redirect_search') == 'yes');
		    $page_str = 'page_';
		}
		if ($installedMagical) {
			$page_str = 'page_';
		}
		list($pagelink, $currentpage) = explode($page_str, $uri);
		if (getVar('page')) $currentpage = intGetVar('page');
		$currentpage = intval($currentpage);
		$pagelink = createBlogidLink($blogID);
		if ($installedCustomURL && $customFlag && $redirectSFlag) {
			$que_str = $query;
			$que_str = htmlspecialchars($que_str);
			$que_str = mb_eregi_replace('/', 'ssslllaaassshhh', $que_str);
			$que_str = mb_eregi_replace("'", 'qqquuuooottt', $que_str);
			$que_str = mb_eregi_replace('&', 'aaammmppp', $que_str);
			$que_str = urlencode($que_str);
			$pagelink .= '/search/' . $que_str . '/';
		} else {
		    $pagelink .= '?query=' . $query;
		    if (is_numeric(getVar('amount')) && intGetVar('amount') >= 0) {
		        $pagelink .= '&amp;amount=' . intGetVar('amount');
		    }
		    $pagelink .= '&amp;blogid=' . $blogID;
		}
		if ($installedMagical) {
			if (substr($pagelink, -5) == '.html') {
				$pagelink = substr($pagelink, 0, -5) . '_';
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
				$page_str = 'page=';
			} else {
				$pagelink .= '/';
				if (strstr ($pagelink, '//')) $link = preg_replace("/([^:])\/\//", "$1/", $pagelink);
			}
		}

		if ($currentpage > 0) {
			$startpos = ($currentpage - 1) * $pageamount;
		} else {
			$currentpage = 1;
		}

		$totalamount = 0;
		if  (is_array($entries)) {
			$totalamount = count($entries);
		}
		$totalamount = intval($totalamount);

		if (!empty($this->maxamount) && $this->maxamount < $totalamount) $totalamount = intval($this->maxamount);
		$totalpages = ceil($totalamount/$pageamount);
		$totalpages = intval($totalpages);
		if ($startpos > $totalamount) {
			$currentpage = $totalpages;
			$startpos = $totalamount-$pageamount;
		}
		if ($offset) {
			$startpos += $offset;
			$totalamount -= $offset;
		}
		$totalpages = ceil($totalamount/$pageamount);
		if ($startpos > $totalamount) {
			$currentpage = $totalpages;
			$startpos = $totalamount-$pageamount;
		}
		$prevpage = ($currentpage > 1) ? $currentpage - 1 : 0;
		$nextpage = $currentpage + 1;
		$firstpagelink = $pagelink . $page_str . '1';
		if ($page_str = 'page_') {
			$firstpagelink .= '.html';
		}
		$lastpagelink = $pagelink . $page_str . $totalpages;
		if ($page_str = 'page_') {
			$firstpagelink .= '.html';
		}

		if ($type >= 1) {
			$buf .= '<div class="pageswitch">'."\n";
			if ($prevpage) {
				$buf .= "<link rel=\"first\" title=\"first page\" href=\"{$firstpagelink}\" />\n";
				$prevpagelink = $pagelink . $page_str . $prevpage;
			if ($page_str = 'page_') {
				$firstpagelink .= '.html';
			}
				$buf .= "\n<a href=\"{$prevpagelink}\" title=\"Previous page\" rel=\"Prev\">&laquo;Prev</a> |";
			} elseif ($type >= 2) {
				$buf .= "\n&laquo;Prev |";
			}
			if (intval($type) == 2) {
				$sepstr = '&middot;';
				$buf .= "|";
				for ($i=1; $i<=$totalpages; $i++) {
					$i_pagelink = $pagelink . $page_str . $i;
					if ($page_str = 'page_') {
						$firstpagelink .= '.html';
					}
					if ($i == $currentpage) {
						$buf .= " <strong>{$currentpage}</strong> |\n";
					} elseif ($totalpages<10 || $i<4 || $i>$totalpages-3) {
						$buf .= " <a href=\"{$i_pagelink}\" title=\"Page No.{$i}\">{$i}</a> |\n";
					} else {
						if ($i<$currentpage-1 || $i>$currentpage+1) {
							if (($i==4 && ($currentpage>5 || $currentpage==1)) || $i==$currentpage+2) {
								$buf = rtrim($buf);
								$buf .= "...|\n";
							}
						} else {
							$buf .= " <a href=\"{$i_pagelink}\" title=\"Page No.{$i}\">{$i}</a> |";
						}
					}
				}
				$buf = rtrim($buf);
			}
			if (intval($type) == 3) {
				$buf .= "|";
				$sepstr = '&middot;';
				for ($i=1; $i<=$totalpages; $i++) {
					$i_pagelink = $pagelink . $page_str . $i;
					$i_pagelink .= '.html';
					$paging = 5;
					if ($i == $currentpage) {
						$buf .= " <strong>{$i}</strong> {$sepstr}\n";
					} elseif ($totalpages < 10 || (($i < ($currentpage + $paging)) && (($currentpage - $paging) < $i))) {
						$buf .= " <a href=\"{$i_pagelink}\" title=\"Page No.{$i}\">{$i}</a> {$sepstr}\n";
					} elseif ($currentpage - $paging == $i) {
						$buf = rtrim($buf);
						$buf .= ' ...'."\n";
					} elseif ($currentpage + $paging == $i) {
						$buf = rtrim($buf);
						$buf = preg_replace("/$sepstr$/","",$buf);
						$buf .= "... |\n";
					}
				}
			}
			if ($totalpages >= $nextpage) {
				$nextpagelink = $pagelink . $page_str . $nextpage;
				if ($page_str = 'page_') {
					$firstpagelink .= '.html';
				}
				$buf .= "| <a href=\"{$nextpagelink}\" title=\"Next page\" rel=\"Next\">Next&raquo;</a>\n";
				$buf .= "<link rel=\"last\" title=\"Last page\" href=\"{$lastpagelink}\" />\n";
			} elseif ($type >= 2) {
				$buf .= "| Next&raquo;\n";
			}
			$buf .= "</div>\n";
			return $buf;
		}
	}

	function doTemplateVar(&$item, $maxLength = 250, $addHighlight = 1)
	{	// Orign NP_ChoppedDisc.php by nakahara21
		global $CONF, $manager, $member, $catid;

		$item_id = intval($item->itemid);
		$que = 'SELECT %s as result FROM %s WHERE %s = %d';
		$Searched['Item'] = strip_tags($item->body).strip_tags($item->more);
		$res = sql_query(sprintf($que, cbody, sql_table('comment'), citem, $item_id));
		while ($cm = mysql_fetch_object($res)) {
			$Searched['comment'] .= strip_tags($cm->result);
		}
//		$res = quickQuery(sprintf($que, title, sql_table('plugin_tb'), tb_id, $item_id));
//		$Searched['Trackback_title'] = strip_tags($res);
		$res = sql_query(sprintf($que, excerpt, sql_table('plugin_tb'), tb_id, $item_id));
		while ($tb = mysql_fetch_object($res)) {
			$Searched['Trackback'] .= strip_tags($tb->result);
		}
		$que_arr = $this->getQueryStrings();
		foreach($Searched as $sKey => $sValue) {
			$i = 1;
			foreach($que_arr as $qValue) {
				if (!(mb_substr_count($sValue, htmlspecialchars($qValue)))) $i++;
			}
			if ($i > count($que_arr)) {
				$sValue = '';
			}
			if ($sValue) {
				if ($addHighlight) {
					$i = 0;
					foreach($que_arr as $qValue) {
						$pattern = "<span class='highlight_{$i}'>{$qValue}</span>";
						$sValue = mb_eregi_replace(htmlspecialchars($qValue), $pattern, $sValue);
						$i++;
						if ($i == 10) $i = 0;
					}
					$str_array = mb_split('</span>', $sValue);
					$num = count($str_array);
					$lastKey = $num +(-1);
					foreach($str_array as $key => $value) {
						$tmpStr = mb_split("<span class='highlight", $value);
						$tmpStr[0] = mb_eregi_replace('&lt;', '<', $tmpStr[0]);
						$tmpStr[0] = mb_eregi_replace('&gt;', '>', $tmpStr[0]);
						$tmpStr[0] = mb_eregi_replace('&amp;', '&', $tmpStr[0]);
						//$tmpStr[0] = mb_eregi_replace('&nbsp;', ' ', $tmpStr[0]);
						$lastp = mb_strwidth($tmpStr[0], _CHARSET);
						if ($key == 0) {
							if ($lastp > 20) {
								$temp_s = '...'.mb_substr($tmpStr[0], -20, 20, _CHARSET);
							} else {
								$temp_s = $tmpStr[0];
							}
						} elseif ($key > 0 && $key < $lastKey) {
							if ($lastp > 30) {
								$temp_s = mb_substr($tmpStr[0], 0, 10, _CHARSET).'...'.mb_substr($tmpStr[0], -10, 10, _CHARSET);
							} else {
								$temp_s = $tmpStr[0];
							}
						} elseif ($key == $lastKey) {
							if ($lastp > 20) {
								$temp_s = mb_substr($tmpStr[0], 0, 20, _CHARSET).'...';
							} else {
								$temp_s = $tmpStr[0];
							}
						}
						if ($key != $lastKey) {
							$str_array[$key] = htmlspecialchars($temp_s, ENT_QUOTES) . "<span class='highlight" . $tmpStr[1];
						} else {
							$str_array[$key] = htmlspecialchars($temp_s, ENT_QUOTES);
						}
					}
					$sValue = '<span class="queryPosition">in ' . htmlspecialchars($sKey);
					$sValue .= '</span><div class="queryResults">' . @join('</span>', $str_array) . '</div>';
				} else {
					$sValue = '<span class="queryPosition">in ' . htmlspecialchars($sKey) . '</span><div class="queryResults">';
					$sValue .= htmlspecialchars(shorten($sValue, $maxLength, '...'), ENT_QUOTES) . '</div>';
				}
				echo $sValue;
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
} 

