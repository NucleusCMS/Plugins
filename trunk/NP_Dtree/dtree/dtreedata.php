<?php
//	$strRel = '../../../';
//	include($strRel . 'config.php');

	$usePathInfo = ($CONF['URLMode'] == 'pathinfo');

	if ($usePathInfo) {
		if (empty($CONF['ItemKey'])) {
			$CONF['ItemKey'] = 'item';
		}
		if (empty($CONF['ArchiveKey'])) {
			$CONF['ArchiveKey'] = 'archive';
		}
		if (empty($CONF['ArchivesKey'])) {
			$CONF['ArchivesKey'] = 'archives';
		}
		if (empty($CONF['MemberKey'])) {
			$CONF['MemberKey'] = 'member';
		}
		if (empty($CONF['BlogKey'])) {
			$CONF['BlogKey'] = 'blog';
		}
		if (empty($CONF['CategoryKey'])) {
			$CONF['CategoryKey'] = 'category';
		}
	}

	$CategoryKey = ($usePathInfo) ? $CONF['CategoryKey'] : 'catid';

	$objectId = requestVar('o');
	$blogid = intval(requestVar('bid'));
	$blogname = getBlogNameFromID($blogid);

		$b =& $manager->getBlog($blogid);
		$blogurl = $b->getURL();
		if (!$blogurl) {
			if($blog) {
				$b_tmp =& $manager->getBlog($CONF['DefaultBlog']);
				$blogurl = $b_tmp->getURL();
			}
			if (!$blogurl) {
				$blogurl = $CONF['IndexURL'];
				if (!$usePathInfo){
					if ($data['type'] == 'pageparser') {
						$blogurl .= 'index.php';
					} else {
						$blogurl = $CONF['Self'];
					}
				}
			}
		}
		if ($usePathInfo) {
			if (substr($blogurl, -1) == '/') { 
				$blogurl = substr($blogurl, 0, -1);
			}
		}

		$CONF['BlogURL'] = $blogurl;
		$CONF['ItemURL'] = $blogurl;
		$CONF['CategoryURL'] = $blogurl;
		$CONF['ArchiveURL'] = $blogurl;
		$CONF['ArchiveListURL'] = $blogurl;
		$CONF['SearchURL'] = $blogurl;

	echo $objectId . " = new dTree('" . htmlspecialchars($objectId) . "');\n";
	
	echo $objectId . ".add(0,-1,'" . htmlspecialchars($blogname) . "');\n";
	
	$res = sql_query("SELECT * FROM " . sql_table('category') . " WHERE cblog = " . $blogid);
	$n = 1;
	while ($o = mysql_fetch_object($res)) {
		$catid = intval($o->catid);
		$nodeArray[cat][$catid] = $n;
//		$url = createBlogidLink($blogid, array('catid'=>$catid));
		$url = createCategoryLink($catid);
//		$url = createBlogidLink($blogid, array("$CategoryKey"=>$catid));
		echo $objectId . ".add(" . $n . ",0,'" . htmlspecialchars($o->cname) . "','" . htmlspecialchars($url) . "');\n";
		$catFilter[] = $catid;
		$n++;
	}
	
	global $manager; 
	if (!$manager->pluginInstalled('NP_MultipleCategories')) {
		$mPlugin =& $manager->getPlugin('NP_MultipleCategories');
		$subrequest = $mPlugin->getRequestName();
		echo 'document.write(' . $objectId . ');';
		if ($itemid = intval(requestVar('id'))) {
			$catid = quickQuery('SELECT icat as result FROM ' . sql_table('item') . ' WHERE inumber = ' . $itemid);
			$nodeId = 's' . $objectId . $nodeArray[cat][$catid];
			echo "document.getElementById('" . htmlspecialchars($nodeId) . "').className = 'selectedNode';";
		}
		return;
	}

	if ($catFilter[1]) {
		$catFilter = @join(',', $catFilter);
		$catFilter = ' IN (' . $catFilter . ')';
	} else {
		$catFilter = '=' . $catFilter;
	}
	
	$query = "SELECT * FROM " . sql_table('plug_multiple_categories_sub') . " WHERE catid" . $catFilter;
	$res = sql_query($query);
	while ($o = mysql_fetch_object($res)) {
		$scatid = intval($o->scatid);
		$nodeArray[subcat][$scatid] = $n;
		$n++;
	}
	$query = "SELECT * FROM " . sql_table('plug_multiple_categories_sub') . " WHERE catid" . $catFilter;
	$res = sql_query($query);
	while ($u = mysql_fetch_object($res)) {
		$scatid = intval($u->scatid);
//		$url = createBlogidLink($blogid, array('catid'=>$u->catid, 'subcatid'=>$scatid));
		$url = createCategoryLink($u->catid, array($subrequest => $scatid));
//		$url = createBlogidLink($blogid, array("$CategoryKey"=>$u->catid, 'subcatid'=>$scatid));
		$pnode = ($u->parentid) ? $nodeArray[subcat][$u->parentid] : $nodeArray[cat][$u->catid];
		echo $objectId . ".add(" . $nodeArray[subcat][$u->scatid] . "," . $pnode . ",'" . htmlspecialchars($u->sname) . "','" . $url . "');\n";
	}

	echo "document.write(".$objectId.");\n";


	if (requestVar('sid')) {
		$sid = intval(requestVar('sid'));
		$nodeId = 's' . $objectId . $nodeArray[subcat][$sid];
		echo "document.getElementById('" . $nodeId . "').className = 'urlselected';\n";
		echo $objectId . ".openTo(" . $nodeArray[subcat][$sid] . ", true);\n";
	} elseif(requestVar('cid')) {
		$cid = intval(requestVar('cid'));
		$nodeId = 's' . $objectId . $nodeArray[cat][$cid];
		echo "document.getElementById('" . $nodeId . "').className = 'urlselected';\n";
		echo $objectId . ".openTo(" . $nodeArray[cat][$cid] . ", true);\n";
	}



	if ($itemid = requestVar('id')) {
		$catid = quickQuery('SELECT icat as result FROM ' . sql_table('item') . ' WHERE inumber = ' . intval($itemid));
		$nodeId = 's' . $objectId.$nodeArray[cat][$catid];
		echo "document.getElementById('" . $nodeId . "').className = 'selectedNode';\n";
		
		//multi catid
		$que = 'SELECT categories as result FROM %s WHERE item_id = %d';
		$catids = quickQuery(sprintf($que, sql_table('plug_multiple_categories'), intval($itemid)));
		if ($catids) {
			$catids = explode(',', $catids);
			for ($i=0;$i<count($catids);$i++) {
				$catidTemp = intval($catids[$i]);
				if ($catidTemp != $catid) {
					$nodeId = 's' . $objectId . $nodeArray[cat][$catidTemp];
					echo "document.getElementById('" . $nodeId . "').className = 'selectedCatNode';\n";
				}
			}
		}
		
		//(multi) subcatid
		$que = 'SELECT subcategories as result FROM %s WHERE item_id = %d';
		$scatids = quickQuery(sprintf($que, sql_table('plug_multiple_categories'), intval($itemid)));
		if ($scatids) {
			$scatids = explode(',', $scatids);
			for ($i=0;$i<count($scatids);$i++) {
				$scatid = intval($scatids[$i]);
				$nodeId = 's' . $objectId . $nodeArray[subcat][$scatid];
				echo "document.getElementById('" . $nodeId . "').className = 'selectedScatNode';\n";
				echo $objectId . ".openTo(" . $nodeArray[subcat][$scatid] . ", true);\n";
			}
		}
	}


?>