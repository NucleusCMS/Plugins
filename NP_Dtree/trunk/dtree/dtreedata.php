<?php
global $CONF, $manager;
	$strRel = '../../../';
	include($strRel . 'config.php');

	$usePathInfo = ($CONF['URLMode'] == 'pathinfo');

//  $objectId = requestVar('o');
	$objectId    = 'tree' . preg_replace('|[^0-9a-f]|i', '', substr(requestVar('o'), 4));
	$blogid      = intRequestVar('bid');
	if (empty($blogid)) {
		$blogid = intval($CONF['DefaultBlog']);
	}
	$blogname = getBlogNameFromID($blogid);

	$b        =& $manager->getBlog($blogid);
	$blogurl  =  $b->getURL();
	if (!$blogurl) {
		if($blog) {
			$b_tmp   =& $manager->getBlog($blogid);
			$blogurl =  $b_tmp->getURL();
		}
		if (!$blogurl) {
			$blogurl = $CONF['IndexURL'];
			if (!$usePathInfo) {
				if ($data['type'] == 'pageparser') {
					$blogurl .= 'index.php';
				} else {
					$blogurl  = $CONF['Self'];
				}
			}
		}
	}

	if ($usePathInfo) {
		if (substr($blogurl, -1) == '/') { 
			$blogurl = substr($blogurl, 0, -1);
		}
	}

	$CONF['BlogURL']        = $blogurl;
	$CONF['ItemURL']        = $blogurl;
	$CONF['CategoryURL']    = $blogurl;
	$CONF['ArchiveURL']     = $blogurl;
	$CONF['ArchiveListURL'] = $blogurl;
	$CONF['SearchURL']      = $blogurl;

	$printData = $objectId . "=new dTree('" . $objectId . "');\n"
			   . $objectId . ".add(0,-1,'"
			   . htmlspecialchars($blogname, ENT_QUOTES, _CHARSET)
			   . "');\n";
	echo $printData;
	unset($printData);
	
	$resq = 'SELECT * FROM %s WHERE cblog = %d';
	$res  = sql_query(sprintf($resq, sql_table('category'), $blogid));
	$n    = 1;
	while ($o = mysql_fetch_object($res)) {
		$catid                    = intval($o->catid);
		$nodeArray['cat'][$catid] = $n;
		$url                      = createCategoryLink($catid);
		$printData                = $objectId
								  . ".add"
								  . "("
								  . $n . ","
								  . "0,"
								  . "'" . htmlspecialchars($o->cname, ENT_QUOTES, _CHARSET) . "',"
//								  . "'" . htmlspecialchars($url,      ENT_QUOTES, _CHARSET) . "',"
								  . "'" . $url . "',"
								  . "'" . htmlspecialchars($o->cdesc, ENT_QUOTES, _CHARSET). "'"
								  . ");\n";
		echo $printData;
		$catFilter[] = $catid;
		$n++;
		unset($printData);
	}
	
	if (!$manager->pluginInstalled('NP_MultipleCategories')) {
		echo 'document.write(' . $objectId . ');';
		if ($itemid = intRequestVar('id')) {
			$que       = 'SELECT icat as result FROM %s WHERE inumber = %d';
			$catid     = quickQuery(sprintf($que, sql_table('item'), $itemid));
			$catid     = intval($catid);
			$nodeId    = 's' . $objectId . $nodeArray['cat'][$catid];
			$printData = "document.getElementById('"
					   . htmlspecialchars($nodeId, ENT_QUOTES, _CHARSET)
					   . "').className = 'selectedNode';";
			echo $printData;
			unset($printData);
		}
		return;
	}

/*	if ($catFilter[1]) {
		$catFilter = implode(', ', $catFilter);
		$catFilter = ' IN (' . $catFilter . ')';
	} else {
		$catFilter = ' = ' . $catFilter;
	}	//original*/

	if (count($catFilter) == 1) {
		$catFilter = ' = ' . $catFilter[0];
	} elseif (count($catFilter) > 1) {
		$catFilter = implode(', ', $catFilter);
		$catFilter = ' IN (' . $catFilter . ')';
	} else {
		$catFilter = '';
	}	// test

	$scatTable   =  sql_table('plug_multiple_categories_sub');
	$mcategories =& $manager->getPlugin('NP_MultipleCategories');
	if (method_exists($mcategories, 'getRequestName')) {
		$subrequest = $mcategories->getRequestName();
	} else {
		$subrequest = 'subcatid';
	}
//	$query = 'SELECT * FROM %s WHERE catid%s';
	$query = 'SELECT * FROM %s WHERE catid%s ORDER BY parentid, catid, ordid';
	$query = sprintf($query, $scatTable, $catFilter);
	$res   = sql_query($query);
	while ($o = mysql_fetch_object($res)) {
		$scatid                       = intval($o->scatid);
		$nodeArray['subcat'][$scatid] = $n;
		$n++;
//	}

//	$query = 'SELECT * FROM %s WHERE catid%s';
//	$query = sprintf($query, $scatTable, $catFilter);
//	$res = sql_query($query);
//	while ($u = mysql_fetch_object($res)) {
//$u = $o;
//		$scatid    = intval($o->scatid);
		$parent_id = intval($o->parentid);
		$cat_id    = intval($o->catid);
		$linkParam = array(
						   $subrequest => $scatid
						  );
		$url       = createCategoryLink($cat_id, $linkParam);

		if (!empty($parent_id)) {
			$pnode = intval($nodeArray['subcat'][$parent_id]);
		} else {
			$pnode = intval($nodeArray['cat'][$cat_id]);
		}
		$printData =  $objectId
				   . ".add"
				   . "("
				   . intval($nodeArray['subcat'][$scatid]) . ","
				   . $pnode . ","
				   . "'" . htmlspecialchars($o->sname, ENT_QUOTES, _CHARSET) . "',"
//				   . "'" . htmlspecialchars($url,      ENT_QUOTES, _CHARSET) . "',"
				   . "'" . $url . "',"
				   . "'" . htmlspecialchars($o->sdesc, ENT_QUOTES, _CHARSET) . "'"
				   . ");\n";
		echo $printData;
		unset($printData);
	}

	echo "document.write(" . $objectId . ");\n";

	if ($sid = intRequestVar('sid')) {
		$nodeId    = 's' . $objectId . intval($nodeArray['subcat'][$sid]);
		$printData = "document.getElementById('" . $nodeId . "')"
				   . ".className='urlselected';\n"
				   . $objectId
				   . ".openTo(" . intval($nodeArray['subcat'][$sid]) . ",true);\n";
		echo $printData;
		unset($printData);
	} elseif ($cid = intRequestVar('cid')) {
		$nodeId    = 's' . $objectId . intval($nodeArray['cat'][$cid]);
		$printData = "document.getElementById('" . $nodeId . "')."
				   . "className='urlselected';\n"
				   . $objectId
				   . ".openTo(" . intval($nodeArray['cat'][$cid]) . ",true);\n";
	}

	if ($itemid = intRequestVar('id')) {
		$que       = 'SELECT icat as result FROM %s WHERE inumber = %d';
		$catid     = quickQuery($que, sql_table('item'), $itemid);
		$catid     = intval($catid);
		$nodeId    = 's' . $objectId . intval($nodeArray['cat'][$catid]);
		$printData = "document.getElementById('" . $nodeId . "')"
				   . ".className='selectedNode';\n";
		echo $printData;
		unset($printData);
		
		//multi catid
		$que    = 'SELECT categories as result FROM %s WHERE item_id = %d';
		$que    = sprintf($que, sql_table('plug_multiple_categories'), $itemid);
		$catids = quickQuery($que);
		if ($catids) {
			$catids = explode(',', $catids);
			$cCount = count($catids);
			for ($i=0; $i < $cCount; $i++) {
				$catidTemp = intval($catids[$i]);
				if ($catidTemp != $catid) {
					$nodeId   = 's' . $objectId . intval($nodeArray['cat'][$catidTemp]);
					$prntData = "document.getElementById('" . $nodeId . "')"
							  . ".className='selectedCatNode';\n";
					echo $printData;
					unset($printData);
				}
			}
		}

		//(multi) subcatid
		$que     = 'SELECT subcategories as result FROM %s WHERE item_id = %d';
		$que     = sprintf($que, sql_table('plug_multiple_categories'), $itemid);
		$scatids = quickQuery($que);
		if ($scatids) {
			$scatids = explode(',', $scatids);
			$scatCnt = count($scatids);
			for ($i=0; $i < $scatCnt; $i++) {
				$scatid    = intval($scatids[$i]);
				$nodeId    = 's' . $objectId . intval($nodeArray['subcat'][$scatid]);
				$printData = "document.getElementById('" . $nodeId . "')"
						   . ".className='selectedScatNode';\n"
						   . $objectId
						   . ".openTo(" . intval($nodeArray['subcat'][$scatid]) . ",true);\n";
				echo $printData;
				unset($printData);
			}
		}
	}
