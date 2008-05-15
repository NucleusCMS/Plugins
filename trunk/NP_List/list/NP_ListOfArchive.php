<?php
/**
 * Archive sub-plugin for NP_List
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.2
 * 
 * History
 * v0.2   2008-05-15  Fix making link with catid. (yu)
 * v0.1   2008-05-13  First release. (yu)
 */
class NP_ListOfArchive extends NP_ListOfSubPlugin 
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
        global $CONF, $manager, $blogid, $catid, $archive;
        
        //get params
        $p =& $this->params;
        $b =& $manager->getBlog($blogid);
        $currentTime = $b->getCorrectTime();
        
        //get template
        $template =& $this->_getTemplate();
        
        //get flags
        $flg =& $this->flg;
        if ($flg['ylimit']) $flg['nolimit'] = true;
        
        //get data
        $now = time();
        $yorder = ($p['yorder']) ? $p['yorder'] : 'DESC';
        $morder = ($p['morder']) ? $p['morder'] : 'DESC';
        $query = 'SELECT itime, SUBSTRING(itime,1,4) AS year, SUBSTRING(itime,6,2) AS month, iblog AS blogid, icat AS catid, '
            .' COUNT(*) AS amount'
            .' FROM '. sql_table('item') .' WHERE ';
        if ($p['filter']) {
            $flg_fixcatid = preg_match('/catid=[0-9]+(?!\|)/', $p['filter']); //check catid filter (single value)
            $flg_curcatid = preg_match('/catid=@/', $p['filter']); //check catid filter (@ pattern)
            
            $aliasmap['search'] = array('blogid', 'catid');
            $aliasmap['replace'] = array('iblog', 'icat');
            
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= $fstr .' AND ';
        }
        else {
            $query .= 'iblog=' . $blogid . ' AND ';
        }
        if ($archive and $flg['ylimit']) {
            $archiveY = substr($archive, 0, 4);
            $query .= 'SUBSTRING(itime,1,4)="' . $archiveY . '" AND ';
        }
        $query .= 'itime<='. mysqldate($currentTime) .' AND idraft=0';
        $query .= ' GROUP BY year, month';
        $query .= ' ORDER BY year '.$yorder.', month '.$morder;
        $res = sql_query($query);
        
        $aLinks = array();
        $aAmount = array();
        $aTime = array();
        $aYear = array();
        for ($i=1; $i<=12; $i++) $aMonth[] = sprintf('%02d', $i);
        
        $pastYear = null;
        if ($morder == 'DESC') krsort($aMonth);
        if (mysql_num_rows($res)) {
            if ($flg_curcatid and $catid) $linkparams = array('catid' => $catid); //use current catid
            
            while ($data = mysql_fetch_assoc($res)) {
                if ($flg_fixcatid) $linkparams = array('catid' => $data['catid']); //use catid in data ($flg["catlink"] is not available here)
                
                if ($data['year'] != $pastYear) $aYear[] = $data['year'];
                $arcdate = $data['year'] .'-'. $data['month'];
                $aLinks[ $data['year'] ][ $data['month'] ] = createArchiveLink($data['blogid'], $arcdate, $linkparams);
                $aAmount[ $data['year'] ][ $data['month'] ] = $data['amount'];
                $aTime[ $data['year'] ][ $data['month'] ] = strtotime($data['itime']);
                $pastYear = $data['year'];
            }
        }
        mysql_free_result($res);
        
        //echo
        $cnt = 0;
        $stripe = false;
        $flg['class'] = $this->_scan($template['ARCHIVELIST_LISTITEM'], '<%class%>');
        foreach ($aYear as $year) {
            if (!$flg['nolimit'] and $cnt >= $p['amount']) break;
            
            $vars = array('year'=>$year);
            if ($this->_scan($template['ARCHIVELIST_HEADER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['ARCHIVELIST_HEADER'], $vars);
            
            foreach ($aMonth as $month) {
                //skip this month if future
                if (!$flg['allmonth'] and (strtotime($year.'-'.$month.'-'.'01 00:00:00') > $currentTime)) continue;
                
                $data = array();
                $data['year'] = $year;
                $data['month'] = $month;
                if ($flg['class']) {
                    $classes = array(
                        'current' => ($archive == $year.'-'.$month),
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $data['class'] = $this->_makeClassProperty($classes);
                }
                $data['archivelink'] = $aLinks[$year][$month];
                $data['amount'] = (int)$aAmount[$year][$month];
                if (isset($aLinks[$year][$month])) {
                    $tempstr = $this->_fill($template['ARCHIVELIST_LISTITEM'], $data);
                    echo strftime($tempstr, $aTime[$year][$month]);
                    $cnt++;
                    $stripe = ($stripe) ? false : true; //toggle
                }
                else if ($flg['shownolink']) {
                    $tempstr = $this->_fill($template['ARCHIVELIST_LISTITEM'], $data);
                    $tempstr = preg_replace('{<a([^>]+?)>|</a>}', '', $tempstr); //strip link
                    echo strftime($tempstr, strtotime($year.'-'.$month));
                    $cnt++;
                    $stripe = ($stripe) ? false : true; //toggle
                }
                if (!$flg['nolimit'] and $cnt >= $p['amount']) break;
            }
            
            if ($this->_scan($template['ARCHIVELIST_FOOTER'], '<%class%>')) {
                $vars['class'] = ($flg['stripe'] and $stripe) ? ' class="stripe"' : '';
                $stripe = ($stripe) ? false : true; //toggle
            }
            echo $this->_fill($template['ARCHIVELIST_FOOTER'], $vars);
        }
    }
}
?>
