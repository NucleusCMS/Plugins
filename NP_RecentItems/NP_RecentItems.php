<?php
/**
  *
  * SHOW RECENT ITEMS PLUG-IN FOR NucleusCMS
  * PHP versions 4 and 5
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
  *
  * @author        Original Author nakahara21
  * @copyright    2005-2006 nakahara21
  * @license        http://www.gnu.org/licenses/gpl.txt  GNU GENERAL PUBLIC LICENSE Version 2, June 1991
  * @version       0.51
  * @link          http://nakahara21.com
  *
  */
/**
  * HISTORY
  * 0.51 add BLOGID mode 
  */
class NP_RecentItems extends NucleusPlugin
{
    function getName()
    {
        return 'RecentItems';
    }

    function getAuthor()
    {
        return 'nakahara21';
    }

    function getURL()
    {
        return 'http://nakahara21.com';
    }

    function getVersion()
    {
        return '0.51';
    }

    function getDescription()
	{
        return 'Display Recent Items. Usage: &lt;%RecentItems(blogname,templatename,5)%&gt;';
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
    function doSkinVar($skinType, $blogName = '', $templateName = '', $amountEntries = 5)
    { 
        global $manager;

        if (is_numeric($blogName)) {
            $query = 'SELECT bshortname as result FROM %s WHERE bnumber = %d';
            $blogname = quickQuery(sprintf($query, sql_table('blog'), intval($blogName)));
        }
        if (!BLOG::exists($blogName)) {
            return;
        }
        if (!TEMPLATE::exists($templateName)) {
            return;
        }
        if ($amountEntries=='') {
            $amountEntries = 5;
        }

        $tempBid =  getBlogIDFromName($blogName);
        $b       =& $manager->getBlog($tempBid); 
        $query   =  $this->_getsqlquery($b, $amountEntries, '');
        $b->showUsingQuery($templateName, $query, 0, 1, 0);
    }

    function _getsqlquery($blogObj, $amountEntries, $extraQuery)
    {
        $query = 'SELECT'
               . ' i.inumber as itemid,'
               . ' i.ititle as title,'
               . ' i.ibody as body,'
               . ' m.mname as author,'
               . ' m.mrealname as authorname,'
               . ' i.itime,'
               . ' i.imore as more,'
               . ' m.mnumber as authorid,'
               . ' m.memail as authormail,'
               . ' m.murl as authorurl,'
               . ' c.cname as category,'
               . ' i.icat as catid,'
               . ' i.iclosed as closed'
               . ' FROM '                        // <mod by shizuki corresponds MySQL 5.0.x or later />
               . sql_table('member') . ' as m, '
               . sql_table('category') . ' as c,'
               . sql_table('item') . ' as i'
               . ' WHERE i.iblog = ' . intval($blogObj->getID())
               . ' AND i.iauthor = m.mnumber'
               . ' AND i.icat = c.catid'
               . ' AND i.idraft = 0'             // exclude drafts
                // don't show future items
               . ' AND i.itime <= ' . mysqldate($blogObj->getCorrectTime());

//        if ($blogObj->getSelectedCategory())
//            $query .= ' and i.icat=' . $blogObj->getSelectedCategory() . ' ';

        $query .= $extraQuery
                . ' ORDER BY i.itime DESC'
                . ' LIMIT ' . intval($amountEntries);
        return $query;
    }
}
