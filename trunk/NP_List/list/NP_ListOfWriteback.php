<?php
/**
 * Writeback sub-plugin for NP_List
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.3
 * 
 * History
 * v0.3   2008-05-15  Fix making link with catid. Add flag "catlink". (yu)
 * v0.2   2008-05-06  Modified for NP_List v0.3. (yu)
 * v0.1   2008-05-02  First release. (yu)
 */
class NP_ListOfWriteback extends NP_ListOfSubPlugin 
{
    /**
     * get default template
     * 
     * @access private
     * @param  void
     * @return array
     */
    function _getDefaultTemplate() 
    {
        $template['DATE']        = '[%m/%d]';
        $template['HEADER']      = '';
        $template['LISTHEADER']  = '<dt><a href="<%itemlink%>"><%itemtitle%></a>'."\n";
        $template['LISTCOMMENT'] = '<dd<%class%>><a href="<%itemlink%>#c<%commentid%>" title="<%body%>"><span class="date"><%date%></span><%user%></a></dd>'."\n";
        $template['LISTTB']      = '<dd<%class%>><a href="<%itemlink%>#tb<%tbid%>" title="<%body%>"><span class="date"><%date%></span><%user%></a></dd>'."\n";
        $template['LISTNONE']    = '<dd<%class%>>ありません</dd>'."\n";
        $template['LISTFOOTER']  = '';
        $template['FOOTER']      = '';
        
        return $template;
    }
    
    /**
     * echo a list
     * 
     * @access public
     * @param  void
     * @return void
     */
    function main()
    {
        //get params
        $p =& $this->params;
        
        //get template
        $template =& $this->_getTemplate();
        
        //get flags
        $flg =& $this->flg;
        
        //get data
        $cdata = $this->_getCommentData(&$params);
        $tdata = $this->_getTrackbackData(&$params);
        
        //merge and sort
        $stocks = $cdata + $tdata;
        krsort($stocks); //sort by timestamp(unix-time)
        
        //group by item 
        $elems = array();
        $items = array();
        $cnt = 0;
        $itemids = array();
        foreach ($stocks as $unixt => $data) {
            $elems[ $data['itemid'] ][] = $data['output'];
            
            if (++$cnt > $p['amount']) break;
            
            if ( in_array($data['itemid'], $itemids) ) continue;
            $itemids[] = $data['itemid'];
            $items[ $unixt ] = array(
                'itemid' => $data['itemid'],
                'itemlink' => $data['itemlink'],
                'itemtitle' => $data['itemtitle'],
                );
        }
        krsort($items);
        
        //echo
        $vars = array();
        $stripe = false;
        if ($this->_scan($template['HEADER'], '<%class%>')) {
            $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
            $stripe = ($stripe) ? false : true; //toggle
        }
        echo $this->_fill($template['HEADER'], $vars);
        
        if (count($items)) {
            foreach ($items as $itemdata) {
                if ($flg['resetstripe']) $stripe = false; //reset stripe per item
                if ($this->_scan($template['LISTHEADER'], '<%class%>')) {
                    $classes = array(
                        'item' => true,
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $itemdata['class'] = $this->_makeClassProperty($classes);
                    $stripe = ($stripe) ? false : true; //toggle
                }
                echo $this->_fill($template['LISTHEADER'], $itemdata);
                
                foreach ($elems[ $itemdata['itemid'] ] as $output) {
                    if ($this->_scan($output, '[[CMCLASS]]')) { //search placeholder
                        $classes = array(
                            'comment' => true,
                            'stripe'  => ($flg['stripe'] and $stripe),
                            );
                        $list_class = $this->_makeClassProperty($classes);
                        $stripe = ($stripe) ? false : true; //toggle
                        echo str_replace('[[CMCLASS]]', $list_class, $output);
                    }
                    elseif ($this->_scan($output, '[[TBCLASS]]')) { //search placeholder
                        $classes = array(
                            'trackback' => true,
                            'stripe'  => ($flg['stripe'] and $stripe),
                            );
                        $list_class = $this->_makeClassProperty($classes);
                        $stripe = ($stripe) ? false : true; //toggle
                        echo str_replace('[[TBCLASS]]', $list_class, $output);
                    }
                    else {
                        echo $output;
                    }
                }
                
                if ($this->_scan($template['LISTFOOTER'], '<%class%>')) {
                    $classes = array(
                        'item' => true,
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $itemdata['class'] = $this->_makeClassProperty($classes);
                    $stripe = ($stripe) ? false : true; //toggle
                }
                echo $this->_fill($template['LISTFOOTER'], $itemdata);
            }
        }
        else {
            if ($this->_scan($template['LISTNONE'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['LISTNONE'], $vars);
        }
        
        if ($this->_scan($template['FOOTER'], '<%class%>')) {
            $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
            $stripe = ($stripe) ? false : true; //toggle
        }
        echo $this->_fill($template['FOOTER'], $vars);
    }
    
    /**
     * get comment data from DB
     * 
     * @access private
     * @param  &$params  parameters
     * @return array
     */
    function _getCommentData(&$params) 
    {
        global $catid;
        
        $p =& $this->params;
        $flg =& $this->flg;
        $template =& $this->_getTemplate();
        
        $query = 'SELECT cnumber AS commentid, cuser AS user, cbody AS body, citem AS itemid, cmember AS memberid,'
            . ' UNIX_TIMESTAMP(ctime) AS unixt, cblog AS blogid, icat AS catid, ititle AS itemtitle'
            . ' FROM ' . sql_table('comment') .' JOIN '. sql_table('item') .' ON citem=inumber';
        if ($p['filter']) {
            $flg_fixcatid = preg_match('/catid=[0-9]+(?!\|)/', $p['filter']); //check catid filter (single value)
            $flg_curcatid = preg_match('/catid=@/', $p['filter']); //check catid filter (@ pattern)
            
            $aliasmap['search'] = array('blogid', 'catid', 'arcdate');
            $aliasmap['replace'] = array('cblog', 'icat', 'SUBSTRING(ctime,1,7)');
            
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= ' WHERE ' . $fstr;
        }
        $query .= ' ORDER BY ctime DESC LIMIT 0, ' . $p['amount'];
        $res = sql_query($query);
        
        $stocks = array();
        if (mysql_num_rows($res)) {
            if ($flg_curcatid and $catid) $linkparams = array('catid' => $catid); //use current catid
            
            while ($data = mysql_fetch_assoc($res)) {
                if ($flg_fixcatid or $flg['catlink']) $linkparams = array('catid' => $data['catid']); //use catid in data
                
                $data['date'] = strftime($template['DATE'], $data['unixt']);
                if (!empty($data['memberid'])) {
                    $data['user'] = $this->_getRealName($data['memberid']);
                }
                $data['user'] = shorten($data['user'], $p['length'] - strlen($data['date']), $p['trimmarker']);
                $data['body'] = str_replace("\r\n", ' ', strip_tags($data['body']));
                $data['body'] = shorten($data['body'], $p['length'], $p['trimmarker']);
                $data['itemlink'] = createItemLink($data['itemid'], $linkparams);
                $data['itemtitle'] = str_replace("\r\n", ' ', strip_tags($data['itemtitle']));
                $data['itemtitle'] = shorten($data['itemtitle'], $p['length'], $p['trimmarker']);
                $data['class'] = '[[CMCLASS]]'; //placeholder - making class is done on main().
                
                $stocks[ $data['unixt'] ] = array(
                    'itemid'   => $data['itemid'],
                    'itemlink' => $data['itemlink'],
                    'itemtitle'=> $data['itemtitle'],
                    'output'   => $this->_fill($template['LISTCOMMENT'], $data),
                    );
            }
        }
        mysql_free_result($res);
        
        return $stocks;
    }
    
    /**
     * get real name of the member
     * 
     * @access private
     * @param  $id  memberid
     * @return string
     */
    function _getRealName($id)
    {
        static $names;
        
        if (empty($names[$id])) {
            $mem = new MEMBER;
            $mem->readFromID(intval($id));
            $names[$id] = $mem->getRealName();
        }
        
        return $names[$id];
    }
    
    /**
     * get trackback data from DB
     * 
     * @access private
     * @param  &$params  parameters
     * @return array
     */
    function _getTrackbackData(&$params) 
    {
        global $manager, $catid;
        
        if (!$manager->pluginInstalled('NP_TrackBack')) return array();
        
        $p =& $this->params;
        $flg =& $this->flg;
        $template =& $this->_getTemplate();
        
        $query = 'SELECT id AS tbid, title AS body, blog_name AS user, tb_id AS itemid,'
            .' UNIX_TIMESTAMP(timestamp) AS unixt, iblog AS blogid, icat AS catid, ititle AS itemtitle'
            .' FROM ' . sql_table('plugin_tb') .' JOIN '. sql_table('item') .' ON tb_id=inumber';
        $query .= ' WHERE ';
        if ($p['filter']) {
            $flg_fixcatid = preg_match('/catid=[0-9]+(?!\|)/', $p['filter']); //check catid filter (single value)
            $flg_curcatid = preg_match('/catid=@/', $p['filter']); //check catid filter (@ pattern)
            
            $aliasmap['search'] = array('blogid', 'catid', 'arcdate');
            $aliasmap['replace'] = array('iblog', 'icat', 'SUBSTRING(itime,1,7)');
            
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= $fstr . ' and ';
        }
        $query .= 'block=0';
        $query .= ' ORDER BY timestamp DESC LIMIT 0, ' . $p['amount'];
        $res = sql_query($query);

        $stocks = array();
        if (mysql_num_rows($res)) {
            if ($flg_curcatid and $catid) $linkparams = array('catid' => $catid); //use current catid
            
            while ($data = mysql_fetch_assoc($res)) {
                if ($flg_fixcatid or $flg['catlink']) $linkparams = array('catid' => $data['catid']); //use catid in data
                
                $data['date'] = strftime($template['DATE'], $data['unixt']);
                $data['user'] = shorten($data['user'], $p['length'] - strlen($data['date']), $p['trimmarker']);
                $data['body'] = str_replace("\r\n", ' ', strip_tags($data['body']));
                $data['body'] = shorten($data['body'], $p['length'], $p['trimmarker']);
                $data['itemlink'] = createItemLink($data['itemid'], $linkparams);
                $data['itemtitle'] = str_replace("\r\n", ' ', strip_tags($data['itemtitle']));
                $data['itemtitle'] = shorten($data['itemtitle'], $p['length'], $p['trimmarker']);
                $data['class'] = '[[TBCLASS]]'; //placeholder - making class is done on main().
                
                $stocks[ $data['unixt'] ] = array(
                    'itemid'   => $data['itemid'],
                    'itemlink' => $data['itemlink'],
                    'itemtitle'=> $data['itemtitle'],
                    'output'   => $this->_fill($template['LISTTB'], $data),
                    );
            }
        }
        mysql_free_result($res);
        
        return $stocks;
    }
}
?>
