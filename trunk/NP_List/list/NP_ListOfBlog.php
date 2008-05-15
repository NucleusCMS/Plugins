<?php
/**
 * Blog sub-plugin for NP_List
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.3
 * 
 * History
 * v0.3   2008-05-14  Modified for NP_List v0.4. (yu)
 * v0.2   2008-05-06  Modified for NP_List v0.3. (yu)
 * v0.1   2008-05-02  First release. (yu)
 */
class NP_ListOfBlog extends NP_ListOfSubPlugin 
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
        global $CONF, $manager, $blogid, $archive;
        
        //get params
        $p =& $this->params;
        $b =& $manager->getBlog($blogid);
        $archivestr = ($archive) ? '['. $archive .']' : '';
        
        //get template
        $template =& $this->_getTemplate();
        
        //get flags
        $flg =& $this->flg;
        
        //prepare header/footer vars.
        $vars = array(
            'sitename' => $CONF['SiteName'],
            'siteurl'  => $CONF['IndexURL'],
            'archivedate' =>$archivestr,
            );
        
        //get data
        $order = ($p['order']) ? $p['order'] : 'blogid ASC';
        $query = 'SELECT bnumber AS blogid, bname AS blogname, bshortname, bdesc AS blogdesc, burl'
            .' FROM '.sql_table('blog');
        if ($p['filter']) {
            $aliasmap['search'] = array('blogid');
            $aliasmap['replace'] = array('bnumber');
            
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= ' WHERE ' . $fstr;
        }
        $query .= ' ORDER BY '. $order;
        $res = sql_query($query);
        
        //echo
        $stripe = false;
        if (mysql_num_rows($res)) {
            if ($this->_scan($template['BLOGLIST_HEADER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['BLOGLIST_HEADER'], $vars);
            
            $flg['class']   = $this->_scan($template['BLOGLIST_LISTITEM'], '<%class%>');
            $flg['amount']  = $this->_scan($template['BLOGLIST_LISTITEM'], '<%amount%>');
            $flg['catlist'] = $this->_scan($template['BLOGLIST_LISTITEM'], '<%catlist%>');
            while ($data = mysql_fetch_assoc($res)) {
                if (strpos($data['blogdesc'], '[!]') === 0) continue; //skip
                
                $data['bloglink'] = createBlogidLink($data['blogid']);
                $data['archivedate'] = $archivestr;
                if ($flg['class']) {
                    $classes = array(
                        'current' => ($blogid == $data['blogid']),
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $data['class'] = $this->_makeClassProperty($classes);
                }
                if ($flg['amount']) {
                    $data['amount'] = quickQuery('SELECT COUNT(*) AS result FROM '. sql_table('item')
                        .' WHERE iblog='. $data['blogid'] 
                        .' AND itime<='. mysqldate($b->getCorrectTime()) .' AND idraft=0');
                }
                if ($flg['catlist']) {
                    $temp = array();
                    $temp['blogid'] = $p['blogid'];
                    $p['blogid'] = $data['blogid']; //set blogid
                    
                    ob_start();
                    $sub =& $this->caller->callSubPlugin('category');
                    if (is_object($sub)) $sub->main();
                    $data['catlist'] = ob_get_contents();
                    ob_end_clean();
                    
                    //restore params
                    $p['blogid'] = $temp['blogid'];
                }
                
                echo $this->_fill($template['BLOGLIST_LISTITEM'], $data);
                $stripe = ($stripe) ? false : true; //toggle
            }
            mysql_free_result($res);
            
            if ($this->_scan($template['BLOGLIST_FOOTER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['BLOGLIST_FOOTER'], $vars);
        }
    }
}
?>
