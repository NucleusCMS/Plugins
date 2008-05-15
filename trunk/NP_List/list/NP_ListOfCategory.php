<?php
/**
 * Category sub-plugin for NP_List
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.2
 * 
 * History
 * v0.2   2008-05-06  Modified for NP_List v0.3. (yu)
 * v0.1   2008-05-04  First release. (yu)
 */
class NP_ListOfCategory extends NP_ListOfSubPlugin 
{
    /**
     * echo a list
     * 
     * @access public
     * @param  void
     * @return void
     */
    function main()
    {
        global $CONF, $manager, $blogid, $catid, $archive, $archivelist;
        
        //get params
        $p =& $this->params;
        $b =& $manager->getBlog($blogid);
        $filter = ($p['cfilter']) ? $p['cfilter'] : $p['filter'];
        $bid = $p['blogid']; //it may not same of global $blogid.
        $archivestr = ($archive) ? '['. $archive .']' : '';
        
        //get template
        $template =& $this->_getTemplate();
        
        //get flags
        $flg =& $this->flg;
        
        //prepare linkparams
        $linkparams = array();
        if ($archive) {
            $blogurl = createArchiveLink($bid, $archive, '');
            $linkparams['blogid'] = $bid;
            $linkparams['archive'] = $archive;
        } else if ($archivelist) {
            $blogurl = createArchiveListLink($bid, '');
            $linkparams['archivelist'] = $archivelist;
        } else {
            $blogurl = createBlogidLink($bid, '');
            $linkparams['blogid'] = $bid;
        }
        
        //prepare header/footer vars.
        $vars = array(
            'blogid'  => $bid,
            'blogurl' => $blogurl,
            'self'    => $CONF['Self'],
            'archivedate' =>$archivestr,
            );
        
        //get data
        $order = ($p['corder']) ? $p['corder'] : (($p['order']) ? $p['order'] : 'catname ASC');
        $query = 'SELECT catid, cdesc AS catdesc, cname AS catname, cblog AS blogid'
            .' FROM '.sql_table('category')
            .' WHERE cblog='. $bid;
        if ($filter) {
            //it can't use aliases in WHERE clause.
            $aliasmap['search'] = array('blogid'); //no need to set catid (same name, no alias).
            $aliasmap['replace'] = array('cblog');
            $current = array(
                'blogid' => $bid, //$bid, not global $blogid.
                'catid'  => $catid,
                );
            
            $fstr = $this->_getFilter($filter, $aliasmap, $current);
            if ($fstr) $query .= ' AND ' . $fstr;
        }
        $query .= ' ORDER BY '. $order;
        $res = sql_query($query);
        
        //echo
        $stripe = false;
        if (mysql_num_rows($res)) {
            //item amount on blog
            if ($this->_scan($template['CATLIST_HEADER'], '<%amount%>')) {
                $vars['amount'] = $this->_getItemAmountOnBlog($bid, $filter);
            }
            if ($this->_scan($template['CATLIST_HEADER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['CATLIST_HEADER'], $vars);
            
            //item amount on category
            $flg['amount'] = $this->_scan($template['CATLIST_LISTITEM'], '<%amount%>');
            if ($flg['amount']) {
                $amounts = $this->_getItemAmountOnCategory($bid, $filter);
            }
            
            $flg['class']  = $this->_scan($template['CATLIST_LISTITEM'], '<%class%>');
            while ($data = mysql_fetch_assoc($res)) {
                if (strpos($data['catdesc'], '[!]') === 0) continue; //skip
                
                $data['blogid'] = $bid;
                $data['blogurl'] = $blogurl;
                $data['catlink'] = createLink(
                    'category',
                    array(
                        'catid' => $data['catid'],
                        'name'  => $data['catname'],
                        'extra' => $linkparams,
                        ));
                $data['self'] = $CONF['Self'];
                $data['archivedate'] = $archivestr;
                if ($flg['class']) {
                    $classes = array(
                        'current' => ($catid == $data['catid']),
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $data['class'] = $this->_makeClassProperty($classes);
                }
                if ($flg['amount']) {
                    $data['amount'] = (int)$amounts[ $data['catid'] ];
                    if ($flg['hidenoamount'] and !$data['amount']) continue; //skip if no amount in the category
                }
                
                echo $this->_fill($template['CATLIST_LISTITEM'], $data);
                $stripe = ($stripe) ? false : true; //toggle
            }
            mysql_free_result($res);
            
            if ($this->_scan($template['CATLIST_FOOTER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['CATLIST_FOOTER'], $vars);
        }
    }
    
    /**
     * get item amount on blog
     * 
     * @access private
     * @param  $bid     blogid
     * @param  $filter  filter string
     * @return integer
     */
    function _getItemAmountOnBlog($bid, $filter) 
    {
        global $manager, $blogid, $catid, $archive;
        
        $b =& $manager->getBlog($blogid);
        
        $query = 'SELECT COUNT(*) AS result FROM '. sql_table('item')
            .' WHERE iblog='. $bid;
        if ($filter) {
            //it can't use aliases in WHERE clause.
            $aliasmap['search'] = array('blogid', 'catid', 'arcdate');
            $aliasmap['replace'] = array('iblog', 'icat', 'SUBSTRING(itime,1,7)');
            $current = array(
                'blogid'  => $bid, //$bid, not global $blogid.
                'catid'   => $catid,
                'arcdate' => $archive, //'XXXX-XX' style
                );
            
            $fstr = $this->_getFilter($filter, $aliasmap, $current);
            if ($fstr) $query .= ' AND '. $fstr;
        }
        $query .= ' AND itime<='. mysqldate($b->getCorrectTime()) .' AND idraft=0';
        
        return quickQuery($query);
    }
    
    /**
     * get item amount on each category
     * 
     * @access private
     * @param  $bid     blogid
     * @param  $filter  filter string
     * @return array
     */
    function _getItemAmountOnCategory($bid, $filter) 
    {
        global $manager, $blogid, $catid, $archive;
        
        $b =& $manager->getBlog($blogid);
        $amounts = array();
        
        $query = 'SELECT COUNT(*) AS amount, icat AS catid FROM '. sql_table('item')
            .' WHERE iblog='. $bid;
        if ($filter) {
            //it can't use aliases in WHERE clause.
            $aliasmap['search'] = array('blogid', 'catid','arcdate');
            $aliasmap['replace'] = array('iblog', 'icat', 'SUBSTRING(itime,1,7)');
            $current = array(
                'blogid'  => $bid, //$bid, not global $blogid.
                'catid'   => $catid,
                'arcdate' => $archive, //'XXXX-XX' style
                );
            
            $fstr = $this->_getFilter($filter, $aliasmap, $current);
            if ($fstr) $query .= ' AND '. $fstr;
        }
        $query .= ' AND itime<='. mysqldate($b->getCorrectTime()) .' AND idraft=0';
        $query .= ' GROUP BY icat';
        $res = sql_query($query);
        
        while ($data = mysql_fetch_assoc($res)) {
            $amounts[ $data['catid'] ] = $data['amount'];
        }
        
        return $amounts;
    }
}
?>
