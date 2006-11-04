<?php
/**
 *
 * DATA creation script for dTree
 *
 */

global $CONF, $manager;
    $strRel = '../../../';
    include($strRel . 'config.php');

    $usePathInfo = ($CONF['URLMode'] == 'pathinfo');

//  $objectId = requestVar('o');
    $objectId = 'tree' . preg_replace('|[^0-9a-f]|i', '', substr(requestVar('o'), 4));
    $blogid   = intRequestVar('bid');
    if (empty($blogid)) $blogid = intval($CONF['DefaultBlog']);
    $blogname = getBlogNameFromID($blogid);

    $b =& $manager->getBlog($blogid);
    $blogurl = $b->getURL();
    if (!$blogurl) {
        if($blog) {
            $b_tmp =& $manager->getBlog($blogid);
            $blogurl = $b_tmp->getURL();
        }
        if (!$blogurl) {
            $blogurl = $CONF['IndexURL'];
            if (!$usePathInfo) {
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

    $CONF['BlogURL']        = $blogurl;
    $CONF['ItemURL']        = $blogurl;
    $CONF['CategoryURL']    = $blogurl;
    $CONF['ArchiveURL']     = $blogurl;
    $CONF['ArchiveListURL'] = $blogurl;
    $CONF['SearchURL']      = $blogurl;

    echo $objectId . "=new dTree('" . htmlspecialchars($objectId) . "');\n";
    
    echo $objectId . ".add(0,-1,'" . htmlspecialchars($blogname) . "');\n";
    
    $resq = 'SELECT * FROM %s WHERE cblog = %d';
    $res = sql_query(sprintf($resq, sql_table('category'), $blogid));
    $n = 1;
    while ($o = mysql_fetch_object($res)) {
        $catid = intval($o->catid);
        $nodeArray['cat'][$catid] = $n;
//      $url = createBlogidLink($blogid, array('catid'=>$catid));
        $url = createCategoryLink($catid);
//      $url = createBlogidLink($blogid, array("$CategoryKey"=>$catid));
        $printData = $objectId
                   . ".add(" . $n
                   . ",0,'"
                   . htmlspecialchars($o->cname) . "','"
                   . htmlspecialchars($url) . "','"
                   . htmlspecialchars($o->cdesc) . "');\n";
        echo $printData;
        $catFilter[] = $catid;
        $n++;
    }
    
    if (!$manager->pluginInstalled('NP_MultipleCategories')) {
        echo 'document.write(' . $objectId . ');';
        if ($itemid = intRequestVar('id')) {
            $que = 'SELECT icat as result FROM %s WHERE inumber = %d';
            $catid = quickQuery(sprintf($que, sql_table('item'), $itemid));
            $catid = intval($catid);
            $nodeId = 's' . $objectId . $nodeArray['cat'][$catid];
            echo "document.getElementById('" . htmlspecialchars($nodeId) . "').className = 'selectedNode';";
        }
        return;
    }

    if ($catFilter[1]) {
        $catFilter = @join(', ', $catFilter);
        $catFilter = ' IN (' . $catFilter . ')';
    } else {
        $catFilter = '=' . $catFilter;
    }
    
    $query = 'SELECT * FROM %s WHERE catid %s ORDER BY scatid';
    $res = sql_query(sprintf($query, sql_table('plug_multiple_categories_sub'), $catFilter));
    while ($o = mysql_fetch_object($res)) {
        $scatid = intval($o->scatid);
        $nodeArray['subcat'][$scatid] = $n;
        $n++;
    }
    $query = 'SELECT * FROM %s WHERE catid %s ORDER BY scatid';
    $res = sql_query(sprintf($query, sql_table('plug_multiple_categories_sub'), $catFilter));
    $mcategories =& $manager->getPlugin('NP_MultipleCategories');
    if (method_exists($mcategories, "getRequestName")) {
        $subrequest = $mcategories->getRequestName();
    } else {
        $subrequest = 'subcatid';
    }

    while ($u = mysql_fetch_object($res)) {
        $scatid = intval($u->scatid);
        $parent_id = intval($u->parentid);
        $cat_id = intval($u->catid);
//      $url = createBlogidLink($blogid, array('catid'=>$u->catid, 'subcatid'=>$scatid));
        $url = createCategoryLink($cat_id, array($subrequest => $scatid));
//      $url = createBlogidLink($blogid, array("$CategoryKey"=>$u->catid, 'subcatid'=>$scatid));

        $pnode = (!empty($parent_id)) ? $nodeArray['subcat'][$parent_id] : $nodeArray['cat'][$cat_id];
        $printData = $objectId . ".add(" . $nodeArray['subcat'][$scatid] . ","
                   . $pnode . ",'"
                   . htmlspecialchars($u->sname) . "','"
                   . htmlspecialchars($url) . "','"
                   . htmlspecialchars($u->sdesc) . "');\n";
        echo $printData;
    }

    echo "document.write(" . $objectId . ");\n";


    if ($sid = intRequestVar('sid')) {
//      $sid = intRequestVar('sid');
        $nodeId = 's' . $objectId . $nodeArray['subcat'][$sid];
        echo "document.getElementById('" . $nodeId . "').className='urlselected';\n";
        echo $objectId . ".openTo(" . $nodeArray['subcat'][$sid] . ",true);\n";
    } elseif ($cid = intRequestVar('cid')) {
//      $cid = intRequestVar('cid');
        $nodeId = 's' . $objectId . $nodeArray['cat'][$cid];
        echo "document.getElementById('" . $nodeId . "').className='urlselected';\n";
        echo $objectId . ".openTo(" . $nodeArray['cat'][$cid] . ",true);\n";
    }



    if ($itemid = intRequestVar('id')) {
        $que = 'SELECT icat as result FROM %s WHERE inumber = %d';
        $catid = quickQuery($que, sql_table('item'), $itemid);
        $catid = intva($catid);
//      $catid = quickQuery('SELECT icat as result FROM ' . sql_table('item') . ' WHERE inumber = ' . $itemid);
        $nodeId = 's' . $objectId.$nodeArray['cat'][$catid];
        echo "document.getElementById('" . $nodeId . "').className='selectedNode';\n";
        
        //multi catid
        $que = 'SELECT categories as result FROM %s WHERE item_id = %d';
        $catids = quickQuery(sprintf($que, sql_table('plug_multiple_categories'), $itemid));
        if ($catids) {
            $catids = explode(',', $catids);
            for ($i=0;$i<count($catids);$i++) {
                $catidTemp = intval($catids[$i]);
                if ($catidTemp != $catid) {
                    $nodeId = 's' . $objectId . $nodeArray['cat'][$catidTemp];
                    echo "document.getElementById('" . $nodeId . "').className='selectedCatNode';\n";
                }
            }
        }
        
        //(multi) subcatid
        $que = 'SELECT subcategories as result FROM %s WHERE item_id = %d';
        $scatids = quickQuery(sprintf($que, sql_table('plug_multiple_categories'), $itemid));
        if ($scatids) {
            $scatids = explode(',', $scatids);
            for ($i=0;$i<count($scatids);$i++) {
                $scatid = intval($scatids[$i]);
                $nodeId = 's' . $objectId . $nodeArray['subcat'][$scatid];
                echo "document.getElementById('" . $nodeId . "').className='selectedScatNode';\n";
                echo $objectId . ".openTo(" . $nodeArray['subcat'][$scatid] . ",true);\n";
            }
        }
    }
?>