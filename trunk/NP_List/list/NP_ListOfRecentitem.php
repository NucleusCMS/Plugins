<?php
/**
 * RecentItem sub-plugin for NP_List
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.3
 * 
 * History
 * v0.3   2008-05-15  Fix making link with catid. Add flag "catlink". (yu)
 * v0.22  2008-05-13  Support current memberid in filter. (yu)
 *                    Add template 'NONE' part. (yu)
 * v0.21  2008-05-08  Change filename (RecentItem ->Recentitem). (yu)
 * v0.2   2008-05-06  Modified for NP_List v0.3. (yu)
 * v0.1   2008-05-02  First release. (yu)
 */
class NP_ListOfRecentItem extends NP_ListOfSubPlugin 
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
        $template['DATE']   = '[%m/%d]';
        $template['HEADER'] = '';
        $template['BODY']   = '<dd<%class%>><a href="<%itemlink%>"><span class="date"><%date%></span><%itemtitle%></a></dd>' ."\n";
        $template['NONE']   = '<dd<%class%>>ありません</dd>'."\n";
        $template['FOOTER'] = '';
        
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
        global $manager, $blogid, $catid, $itemid;
        
        //get params
        $p =& $this->params;
        $b =& $manager->getBlog($blogid);
        
        //get template
        $template =& $this->_getTemplate();
        
        //get flags
        $flg =& $this->flg;
        
        //get item data
        $query = 'SELECT ititle AS itemtitle, inumber AS itemid, icat AS catid, iblog AS blogid,'
            .' UNIX_TIMESTAMP(itime) AS unixt'
            .' FROM '. sql_table('item');
        $query .= ' WHERE ';
        if ($p['filter']) {
            $flg_fixcatid = preg_match('/catid=[0-9]+(?!\|)/', $p['filter']); //check catid filter (single value)
            $flg_curcatid = preg_match('/catid=@/', $p['filter']); //check catid filter (@ pattern)
            
            $aliasmap['search'] = array('blogid', 'catid', 'memberid');
            $aliasmap['replace'] = array('iblog', 'icat', 'iauthor');
            
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= $fstr .' AND ';
        }
        $query .= 'itime<='. mysqldate($b->getCorrectTime()) .' AND idraft=0';
        $query .= ' ORDER BY itime DESC LIMIT 0, ' . $p['amount'];
        $res = sql_query($query);
        
        //echo
        $vars = array();
        $stripe = false;
        if ($this->_scan($template['HEADER'], '<%class%>')) {
            $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
            $stripe = ($stripe) ? false : true; //toggle
        }
        echo $this->_fill($template['HEADER'], $vars);
            
        if (mysql_num_rows($res)) {
            if ($flg_curcatid and $catid) $linkparams = array('catid' => $catid); //use current catid
            
            $flg['class'] = ($this->_scan($template['BODY'], '<%class%>'));
            while ($data = mysql_fetch_assoc($res)) {
                if ($flg_fixcatid or $flg['catlink']) $linkparams = array('catid' => $data['catid']); //use catid in data
                
                $data['date'] = strftime($template['DATE'], $data['unixt']);
                $data['itemtitle'] = str_replace("\r\n", ' ', strip_tags($data['itemtitle']));
                $data['itemtitle'] = shorten($data['itemtitle'], $p['length'] - strlen($data['date']), $p['trimmarker']);
                $data['itemlink'] = createItemLink($data['itemid'], $linkparams);
                if ($flg['class']) {
                    $classes = array(
                        'current' => ($itemid == $data['itemid']),
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $data['class'] = $this->_makeClassProperty($classes);
                }
                
                echo $this->_fill($template['BODY'], $data);
                $stripe = ($stripe) ? false : true; //toggle
            }
        }
        else {
            if ($this->_scan($template['NONE'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['NONE'], $vars);
        }
        
        if ($this->_scan($template['FOOTER'], '<%class%>')) {
            $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
            $stripe = ($stripe) ? false : true; //toggle
        }
        echo $this->_fill($template['FOOTER'], $vars);
    }
}
?>
