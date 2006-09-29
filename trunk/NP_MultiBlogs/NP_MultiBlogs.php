<?php
/*
 * Nucleus MultiBlogs plugin
 * Copyright (C) 2004-2006 Jun
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * @license http://nucleuscms.org/license.txt GNU General Public License
 */
/*
 * Changes:
 *  v2.622	Jun			- security fix
 *  v3.00	kimitake	- security fix
 */

if(!function_exists('sql_table')) {
	function sql_table($name) {	return 'nucleus_'.$name; }
}

class NP_MultiBlogs extends NucleusPlugin {

	function getName() { return 'MultiBlogs'; }
	function getAuthor() { return 'kimitake'; }
	function getURL() { return 'http://japan.nucleuscms.org/bb/viewtopic.php?t=515'; }
	function getVersion() { return '3.00'; }

	function getDescription() { return 'It can replace &lt;%blog%&gt;, '.
		'&lt;%item%&gt;, or &lt;%searchresults%&gt;. It is possible to link '.
		'to other pages. You can specify blogs or categories to show. You can '.
		'sort items by date, title(ascending or descending), or at random.';	}
	
		function supportsFeature($what) {
		switch($what)	{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption('blog_except',
			'BlogID to except. (example) 1/4','text','');
		$this->createOption('item_except',
			'ItemID to except for NP_View. (example) 3/15/120','text','');
		$this->createOption('group_num',
			'Group mode: Do you display the number of the items?','yesno','yes');
		$this->createOption('group_link',
			'Group mode: Do you display the title link?','yesno','no');
		$this->createOption('group_more',
			"Group mode: String to display 'more' link.",'text','more');
		$this->createOption('group_other',
			"Group mode: Do you display 'Other items'?",'text','');
		$this->createOption('group_tagt',
			'Group mode: CSS title tag. (*ex1. div)(*ex2. ol)','text','div');
		$this->createOption('group_tag',
			'Group mode: CSS body tag. (*ex1. div)(*ex2. ol)','text','div');
		$this->createOption('category_except',
			'Group mode: CategoryID to except. (example) 2/3','text','');
		$this->createOption('subcategory_except',
			'Group mode: SubategoryID to except. (example) 1/3','text','');
	}

	function unInstall() {
		$this->deleteOption('blog_except');
		$this->deleteOption('item_except');
		$this->deleteOption('group_num');
		$this->deleteOption('group_link');
		$this->deleteOption('group_more');
		$this->deleteOption('group_other');
		$this->deleteOption('group_tagt');
		$this->deleteOption('group_tag');
		$this->deleteOption('category_except');
		$this->deleteOption('subcategory_except');
	}

	function doTemplateCommentsVar(&$item, &$comment, $mode = '') {
		if (!$mode || $mode=='i') {
			$this->doTemplateVar(&$item, $mode);
		}
	}

	function doTemplateVar(&$item, $mode = '') {

		global $CONF, $manager;

		$itemid = intval($item->itemid);

		// support MultipleCategories plugin
		$mact = $manager->pluginInstalled('NP_MultipleCategories');

		$q0 = 'SELECT iblog, icat ';
		if ($mact) {
			$q0 .= ',subcategories ,categories ';
		}
		$q0 .= 'FROM '.sql_table('blog').', '.sql_table('category')
			.', '.sql_table('item');
		if ($mact) {
			$q0 .= ' left join '.sql_table('plug_multiple_categories')
				.' on item_id = inumber';
		}
		$q0 .= ' WHERE bnumber = iblog and catid = icat and inumber = '.$itemid;

		$q1 = mysql_query($q0);
		while($row = mysql_fetch_assoc($q1)) {
			$blink = quickQuery('SELECT burl as result FROM '.sql_table('blog')
				.' WHERE bnumber = '.$row['iblog']);
			$clink = createBlogidLink($row['iblog'], array('catid' => $row['icat']));

			if ($mact && $row['subcategories'] && $row['categories']==$row['icat']) {
				$scat = explode(',' ,$row['subcategories']);
				$ilink = createItemLink($itemid, array('subcatid' => $scat[0]));
			} else {
				$ilink = createItemLink($itemid, array('catid' => $row['icat']));
			}
		}
		if (!$mode || $mode=='i') {
			echo $blink.$ilink;
		}
		if ($mode=='b') {
			echo $blink;
		}
		if ($mode=='c') {
			echo $clink;
		}
	}

	function doSkinVar($skinType, $template = 'grey/short', $items = 10,
				$bmode = '', $bpage = '', $msort = '', $mcat = '', $mform = '',
				$templates = '') {

		global $blog, $blogid, $catid, $archive, $itemid, $member;
		global $memberid, $CONF, $manager, $archivelist;

		$blogid = intval($blogid);
		$catid = intval($catid);
		$itemid = intval($itemid);
		$memberid = intval($memberid);
		$meid = intval($member->getID());
		$temple = explode('/', $template, 4);

		// basic query
		$q1 = ' i.inumber as itemid, i.iblog as blog, i.ibody as body, '
			. 'm.mname as author, m.mrealname as authorname, '
			. 'UNIX_TIMESTAMP(i.itime) as timestamp, i.itime, i.imore as more, '
			. 'm.mnumber as authorid, c.cname as category, i.icat as catid, '
			. 'i.iclosed as closed, m.memail as authormail, m.murl as authorurl,';
		$q2 = ' i.ititle as title';
		$q3 = ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '
			. sql_table('category').' as c';
		$q4 = ' WHERE i.iauthor = m.mnumber and i.icat = c.catid and i.idraft = 0';

		if (substr($templates, 0, 6) != 'future') {
			$q4 .= ' and i.itime <= '.mysqldate($blog -> getCorrectTime());
		}

		// MultiBlogs
		switch(TRUE) {
		case (!($bmode=='fix' || $bmode=='fixall' || $temple[0]=='multitag')):
			$items = explode('/', $items, 4);
			$mform = explode('/', $mform, 4);
	
			$uri1 = "";
			switch (TRUE) {
				case ($_GET['query']):
				$uri1 = $_GET['query'];
				break;
			case ($_GET['que']):
				$uri1 = $_GET['que'];
				break;
			}
			if (get_magic_quotes_gpc()) {
				$uri1 = stripslashes($uri1);
			}
			$uri1 = addslashes($uri1);

			// for NP_StickyIT (* Thanks for Fujisaki, 2004.08.09)
			if ($manager->pluginInstalled('NP_StickyIT')) {
				$q5 = ' left join '.sql_table('plug_stickyit').' on snumber = i.inumber';
				$q6 = ' and snumber is null';
			}

			// for NP_View
			$sort = intval($_GET['sort']);
			if ($manager->pluginInstalled('NP_View') && 
				(($msort >= '11' && $msort <= '6') ||
				 ($mform[0] && $sort >= 6 && $sort <= 11))) {

				$vtime = date("Y-m-d H:i:s");
				sscanf($vtime, '%d-%d-%d', $y, $m, $d);
				$t0 = mktime(0, 0, 0, $m, $d, $y);
				$t0m = date("m", $t0);
				$vday = 'v.week'.strftime("%w", $t0);
				$vmonth = 'v.month'.$t0m;

				switch (TRUE) {
				case($mform[0] && $sort == 8) :
					$q200 = ', '.$vday.' as vday';
					break;
				case($mform[0] && $sort == 9) :
					$q200 = ', v.week0+v.week1+v.week2+v.week3+'
						  . 'v.week4+v.week5+v.week6 as vweek';
					break;
				case($mform[0] && $sort == 10) :
					$q200 = ', '.$vmonth.' as vmonth';
					break;
				case($mform[0] && $sort == 11) :
					$q200 = ', v.month01+v.month02+v.month03+'
						  . 'v.month04+v.month05+v.month06+'
						  . 'v.month07+v.month08+v.month09+'
						  . 'v.month10+v.month11+v.month12 as vyear';
					break;
				}
				$q20 = ', '.sql_table('plugin_view').' as v';
				$q21 = ' and v.id = i.inumber';
			}

			// for Comment
			if ($items[0] == 'c') {
				$q22 = ', '.sql_table('comment').' as co';
				$q23 = ' and co.citem = i.inumber';
			}
			// for NP_TrackBack
			if ($manager->pluginInstalled('NP_TrackBack') && $items[0] == 'tb') {
				$q24 = ', '.sql_table('plugin_tb').' as tb';
				$q25 = ' and tb.tb_id = i.inumber';
			}
// for Search
			if ($items[0] == 's' || ($_GET['que'] && $mform[0])) {
				$q26 = ' left join '.sql_table('comment').' as cm on i.inumber = cm.citem';
				if ($manager -> pluginInstalled('NP_TrackBack')) {
					$q26 .= ' left join '.sql_table('plugin_tb').' as t on i.inumber = t.tb_id';
					$s0 = array('i.ititle', '" or i.ibody', '" or i.imore', '" or m.mname', '" or cm.cbody', '" or cm.cuser', '" or t.title', '" or t.excerpt');
				}
			}
// for NP_MultipleCategories (* Thanks for Taka, 2005.01.20)
			$mact = $manager->pluginInstalled('NP_MultipleCategories');
			switch(TRUE) {
			case(!$mact) :
				$q31 = ($mcat[0] == 'blog' || $items[0] == 'item' || !$catid) ? 'i.iblog = '.$blogid : 'i.icat = '.$catid;
				break;
			default:
				global $subcatid;
				$subcatid = intval($subcatid);
				$mplugin =& $manager -> getPlugin('NP_MultipleCategories');
				$q30 = ' left join '.sql_table('plug_multiple_categories').' as p on p.item_id = i.inumber';
				$mcat = explode('/', $mcat, 2);
				switch(TRUE) {
				case (!($mcat[0] == 'scat' || $mcat[0] == 'blog' || $items[0] == 'item' || !$catid)):
					$q31 = '(p.categories REGEXP "(^|,)'.$catid.'(,|$)" or ';
					$q31 .= ($mcat[0] == 'blog') ? 'i.iblog = '.$blogid.')' : 'i.icat = '.$catid.')';
					break;
				case ($mcat[0] == 'blog' || $items[0] == 'item' || !$catid):
					$qq = sql_query('SELECT DISTINCT icat FROM '.sql_table('item').' WHERE iblog = '.$blogid);
					$q31 = '(';
					while($row = mysql_fetch_assoc($qq)) {
						$q31 .= 'p.categories REGEXP "(^|,)'.$row['icat'].'(,|$)" or ';
					}
					$q31 .= 'i.iblog = '.$blogid.')';
					break;
				default:
					$q30 .= ' left join '.sql_table('plug_multiple_categories_sub').' as ps on ps.catid = i.icat';
					if ($subcatid) {
						$scat = quickQuery('SELECT scatid as result FROM '.sql_table('plug_multiple_categories_sub').', '.sql_table('item').' WHERE icat = catid and inumber = '.$itemid.' LIMIT 1');
					}

					$mcats = explode('/', $mcat[1]);
					switch(TRUE) {
					case($mcat[1]) :
						foreach($mcats as $mitems){
							$q31 .= ' p.subcategories REGEXP "(^|,)'.$mitems.'(,|$)" or';
						}
						$q31 = substr($q31 ,0, -3);
						$q31 = '('.$q31.')';
						break;
					case($scat) :
						$q31 = ' p.subcategories > 0 and ps.scatid = '.$scat;
						break;
					default:
						$q31 = ' c.catid = '.$catid;
					}
				}
				switch(TRUE) {
				case($subcatid) :
					$sname = quickQuery('SELECT sname as result FROM '.sql_table('plug_multiple_categories_sub').' WHERE scatid = '.$subcatid);
					$subcatlink = createBlogidLink($blogid, array('subcatid' => $subcatid));
					if (method_exists($mplugin, "getRequestName")) {
						$q31 .= ' and (p.subcategories REGEXP "(^|,)'.$subcatid.'(,|$)"';
					}
					$q31 .= ($mcat[0] == 'blog') ? ' or i.iblog = '.$blogid.')' : ')';
					$q32 = ($itemid) ? '<a href="'.$subcatlink.'">'.$sname."</a> &raquo; " : $sname." &raquo; ";
				}
				$mcat = implode('/', $mcat);
			}
// for NP_ItemFlag
			if ($manager->pluginInstalled('NP_ItemFlag') && $mform[0]) {
				if ($_GET['rank'] || $_GET['sort'] == 2 || $_GET['sort'] == 5 || $msort == '9' || $msort == '10') {
					$q33 = ' ,ifg.iflagflag';
					$q34 = ' left join '.sql_table('plugin_itemflag').' as ifg on ifg.iflagid = i.inumber';
				}
			}
// blog mode
			$item =& $manager -> getItem($itemid, 0, 0);
			$q0 = ($items[0] == 'c' || $items[0] == 'tb') ? 'SELECT ' : 'SELECT DISTINCT ';
			$query = $q0.$q1.$q2.$q200.$q33.$q3.$q5.$q26.$q30.$q20.$q22.$q24.', '.sql_table('blog').' as b'.$q34.$q4.$q6.$q21.$q23.$q25;
			$q7 = ' and b.bnumber = c.cblog';
			$ihide = $this -> getOption('item_except');
			$bhide = $this -> getOption('blog_except');
			$ihide = explode('/', $ihide);
			$bhide = explode('/', $bhide);
			if ($items[0] != 'item') $bpage = explode('/', $bpage);
			$uri = sprintf('%s%s%s', 'http://', $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
			$s1 = ($s0) ? $s0 : array('i.ititle', '" or i.ibody', '" or i.imore', '" or m.mname', '" or cm.cbody', '" or cm.cuser');
// rank
			$rank = intval($_GET['rank']);
			if ($q33 && $_GET['rank'] != 'a' && $_GET['rank']) $query .= ' and ifg.iflagflag = '.$rank;
// search
			$post = urldecode($uri1);
			if($items[0] == 's' || ($_GET['que'] && $mform[0])) $query .= $this->SearchQuery($post, $s1, $s0);
// switch form
			switch(TRUE) {
			case($mform[0] || $template == 'switch') :
				$p_sform = '<form method="get" action="';
				$p_sform .= ($template == 'switch' || $mform[0] == 'd') ? createBlogidLink($blogid) : $_SERVER['PHP_SELF'];
				$p_sform .= '">';
				if ($CONF['URLMode'] != 'pathinfo' && $mform[1]) {
					switch(TRUE) {
					case ($catid):
						$p_sform .= '<input name="catid" value="'.$catid.'" type="hidden">';
						break;
					case ($blogid):
						$p_sform .= '<input name="blogid" value="'.$blogid.'" type="hidden">';
						break;
					case ($archivelist):
						$p_sform .= '<input name="archivelist" value="'.$blogid.'" type="hidden">';
						break;
					case ($archive):
						$p_sform .= '<input name="archive" value="'.$archive.'" type="hidden">';
					}
				}
				$p_time0 = array(
					''		=> 'Today',
					1		=> '1day',
					3		=> '3days',
					7		=> '1week',
					30		=> '1month',
					365		=> '1year',
				);
				$p_time = array(
					9999	=> 'All period',
					1		=> 'In 1day',
					3		=> 'In 3days',
					7		=> 'In 1week',
					30		=> 'In 1month',
					365		=> 'In 1year',
				);
				$p_sort = array(
					99		=> 'New',
					1		=> 'Old',
					4		=> 'A-&gt;Z',
					3		=> 'Z-&gt;A',
				);
/* add $p_sort
					2		=> 'Update',
					5		=> 'Rank',
					7		=> 'ViewTime',
					6		=> 'MostView',
					8		=> 'MostView(day)',
					9		=> 'MostView(week)',
					10		=> 'MostView(month)',
					11		=> 'MostView(year)',
*/
				$p_view = array(
					10		=> '10 results',
					20		=> '20 results',
					30		=> '30 results',
					40		=> '40 results',
					50		=> '50 results',
				);
				$p_blog = array(
					1		=> 'Category',
					2		=> 'All page',
				);
				$p_rank = array(
					a		=> 'Rank',
					1		=> '*',
					2		=> '**',
					3		=> '***',
					4		=> '****',
					5		=> '*****',
				);
				switch(TRUE) {
				case (preg_match('/b/', $mform[1])):
					$b_sform = ' <select name="time0">';
					foreach($p_time0 as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['time0']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['time0'] && $_GET['sbmt']) $p_sf = '['.$p_s3.']';
						$b_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				case (preg_match('/c/', $mform[1])):
					$c_sform = ' <select name="time">';
					foreach($p_time as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['time']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['time'] && $_GET['sbmt']) $p_sf .= '['.$p_s3.']';
						$c_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				case (preg_match('/d/', $mform[1])):
					$d_sform = ' <select name="sort">';
					foreach($p_sort as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['sort']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['sort'] && $_GET['sbmt']) $p_sf .= '['.$p_s3.' sort]';
						$d_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				case (preg_match('/e/', $mform[1])):
					$e_sform = ' <select name="view">';
					foreach($p_view as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['view']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['view'] && $_GET['sbmt']) $p_sf .= '['.$p_s3.']';
						$e_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				case (preg_match('/f/', $mform[1])):
					$f_sform = ' <select name="blog">';
					foreach($p_blog as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['blog']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['blog'] && $_GET['sbmt']) $p_sf .= '['.$p_s3.']';
						$f_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				case (preg_match('/g/', $mform[1])):
					$g_sform = ' <select name="rank">';
					foreach($p_rank as $p_s2 => $p_s3) {
						$p_flag = ($p_s2 == $_GET['rank']) ? ' selected="selected"' : '';
						if ($p_s2 == $_GET['rank'] && $_GET['sbmt']) $p_sf .= '['.$p_s3.']';
						$g_sform .= '<option value="'.$p_s2.'"'.$p_flag.'>'.$p_s3.'</option>';
					}
				}
				list($plink, $mpage) = explode('?', $uri, 2);
// switch form sort
				switch(TRUE) {
				case (preg_match('/a/', $mform[1])):
					$p_sform .= ' <input type="text" name="que" size="12" maxlength="60" value="'.addslashes($_GET['que']).'" />';
				case (preg_match('/s/', $mform[1])):
					$p_sform .= ' <input type="submit" name="sbmt" value="Push" />';
				case (preg_match('/b/', $mform[1])):
					$p_sform .= $b_sform.'</select> -&gt;';
				case (preg_match('/c/', $mform[1])):
					$p_sform .= $c_sform.'</select>';
				case (preg_match('/d/', $mform[1])):
					$p_sform .= $d_sform.'</select>';
				case (preg_match('/e/', $mform[1])):
					$p_sform .= $e_sform.'</select>';
				case (preg_match('/f/', $mform[1])):
					$p_sform .= $f_sform.'</select>';
				case (preg_match('/g/', $mform[1])):
					$p_sform .= $g_sform.'</select>';
				case (preg_match('/r/', $mform[1])):
					if ($_GET['sbmt']) $p_sform .= ' [<a href="'.$plink.'">Reset</a>]';
				}
				$p_sform .= '</form>';
				if ($template == 'switch' && $mform[1]) {
					echo '<div class="switchform">'.$p_sform.'</div>';
					return;
				}
			}
// category
			$mcats = explode('/', $mcat, 2);
			switch(TRUE) {
			case($mcat && $items[0] != 'item') :
				$mcat = explode('/', $mcat);
				switch($mcat[0]) {
				case 'c':
					$q8 = ' or i.imore LIKE "%[-'.$catid.'-]%" )';
					break;
				case 'b':
					$q8 = ' or i.imore LIKE "%[-b'.$blogid.'-]%" )';
					break;
				case 'm':
					$query .= ' and BINARY i.imore LIKE "%[-'.$mcat[1].'-]%"';
					break;
				case 's':
					$s2 = quickQuery('SELECT ititle as result FROM '.sql_table('item').' WHERE inumber = '.$itemid);
					$s3 = explode(' ', $s2);
					$s4 = ' LIKE "%'.addslashes($s3[1]).'%';
					$query .= ($s3[1]) ? ' and ( '.$s1[0].$s4.$s1[1].$s4.$s1[2].$s4.'" )' : ' and i.inumber = '.$itemid;
					break;
				case 'mem':
					$query .= ($mcat[1]) ? ' and i.iauthor = '.$mcat[1] : ' and i.iauthor = '.$memberid;
					break;
				case 'mem2':
					$query .= ' and i.iauthor = '.$memid;
					break;
				case 'a':
					$query .= ' and c.catid = '.$item['catid'];
					break;
				case 'all':
					$mcat = explode('/', $mcats[1]);
					foreach($mcat as $mcats) $query .= ' and i.icat != '.$mcats;
					break;
				default:
					if($mcat[0] != 'blog' && $mcat[0] != 'archive' && $mcat[0] != 'scat') {
						$mcat = implode(', ', $mcat);
						$query .= ' and i.icat in ('.$mcat.')';
					}
				}
			}
// blog
			$bmodes = explode('/', $bmode, 2);
			if ($bmodes[0] == 'all' && $bmodes[1]) $bhide = explode('/', $bmodes[1]);
			switch(TRUE) {
			case ($bmode == 'all' || $bmodes[0] == 'all' || ($_GET['blog'] == '2' && $mform[0])):
				$query .= $q7;
				if ($bhide[0]) foreach($bhide as $hides) {
					$query .= ' and i.iblog != '.$hides;
				}
				break;
			case ($bmode == '' || $bmode == 0):
				switch(TRUE) {
				case ($mcat[0] == 'c' && $catid):
					$query .= $q7.' and ( i.icat = '.$catid.$q8;
					break;
				case ($mcat[0] == 'b' && $blogid):
					$query .= $q7.' and ( i.iblog = '.$blogid.$q8;
					break;
				default:
					$query .= $q7.' and '.$q31;
				}
				break;
			default:
				$bmode = str_replace('/', ', ', $bmode);
				$query .= ' and i.iblog in ('.$bmode.')';
			}
// archive
			switch(TRUE) {
			case ($_GET['time0'] && $mform[0]):
				$time0 = intval($_GET['time0']);
				$query .= ' and i.itime <= '.mysqldate($blog -> getCorrectTime() - 86400 * $time0);
				break;
			case ($_GET['time'] && $mform[0]):
				$time1 = intval($_GET['time']);
				$query .= ' and i.itime >= '.mysqldate($blog -> getCorrectTime() - 86400 * $time1);
				break;
			case ($archive && !($mcat[0] == 'archive' || $mcat[0] == 'blog')):
				sscanf($archive, '%d-%d-%d', $y , $m, $d);
				switch(TRUE) {
				case($d) :
					$time_s = mktime(0, 0, 0, $m, $d, $y);
					$time_e = mktime(0, 0, 0, $m, $d + 1, $y);
					break;
				case($m) :
					$time_s = mktime(0, 0, 0, $m, 1, $y);
					$time_e = mktime(0, 0, 0, $m + 1, 1, $y);
					break;
				default:
					$time_s = mktime(0, 0, 0, 1, 1, $y);
					$time_e = mktime(0, 0, 0, 12, 31, $y);
				}
				$query .= $q7.' and i.itime >= '.mysqldate($time_s)
							.' and i.itime < '.mysqldate($time_e);
			}
//group
			if (substr($templates, 0, 5) == 'group') $q101 = $query;
// item
			if ($ihide[0] && $result2 && ($msort == '5' || $msort == '6')) foreach($ihide as $ihides){
				$query .= ' and i.inumber != '.$ihides;
			}
			switch(TRUE) {
			case($items[0] != 'item' || $_GET['que']) :
// sort
				$que1 = ' ORDER BY ';
				switch(TRUE) {
				case ($_GET['sort'] == 1 && $mform[0]):
					$que1 .= 'i.itime ASC ';
					break;
				case ($_GET['sort'] == 2 && $mform[0]):
					$que1 .= 'ifg.iflagtime DESC, i.itime DESC ';
					break;
				case ($_GET['sort'] == 3 && $mform[0]):
					$que1 .= 'i.ititle DESC ';
					break;
				case ($_GET['sort'] == 4 && $mform[0]):
					$que1 .= 'i.ititle ASC ';
					break;
				case ($_GET['sort'] == 5 && $mform[0]):
					$que1 .= 'ifg.iflagflag DESC ';
					break;
				case ($_GET['sort'] == 6 && $mform[0]):
					$que1 .= 'v.view DESC ';
					break;
				case ($_GET['sort'] == 7 && $mform[0]):
					$que1 .= 'v.vtime DESC ';
					break;
				case ($_GET['sort'] == 8 && $mform[0]):
					$que1 .= 'vday DESC ';
					break;
				case ($_GET['sort'] == 9 && $mform[0]):
					$que1 .= 'vweek DESC ';
					break;
				case ($_GET['sort'] == 10 && $mform[0]):
					$que1 .= 'vmonth DESC ';
					break;
				case ($_GET['sort'] == 11 && $mform[0]):
					$que1 .= 'vyear DESC ';
					break;
				case ($_GET['sort'] == 99 && $mform[0]):
					$que1 .= 'i.itime DESC ';
					break;
				case ($msort == 1):
					$que1 .= 'i.itime ASC ';
					break;
				case ($msort == 2):
					$que1 .= 'RAND() ';
					break;
				case ($msort == 3):
					$que1 .= 'i.ititle DESC ';
					break;
				case ($msort == 4):
					$que1 .= 'i.ititle ASC ';
					break;
				case ($msort == 7):
					$que1 .= 'co.ctime DESC ';
					break;
				case ($result3 && $msort == 8):
					$que1 .= 'tb.timestamp DESC ';
					break;
				case ($q33 && $msort == 9):
					$que1 .= 'ifg.iflagflag DESC ';
					break;
				case ($q33 && $msort == 10):
					$que1 .= 'ifg.iflagtime DESC, i.itime DESC ';
					break;
				case ($result2 && $msort == 11):
					$que1 .= 'v.view DESC ';
					break;
				case ($result2 && $msort == 12):
					$que1 .= 'v.vtime DESC ';
					break;
				default:
					$que1 .= 'i.itime DESC ';
				}
				$query .= $que1;
// non pageswitch
				$bitem = mysql_num_rows(mysql_query($query));
				switch(TRUE) {
				case($items[0] == 's' || $items[0] == 'c' || $items[0] == 'tb') :
					$ite = $items[1];
					break;
				default:
					$ite = $items[0];
					if ($items[1] > 0) $ite0 = $items[1].', ';
				}

				if ($_GET['sbmt'] && $mform[3]) $template = $mform[3];
				if ($_GET['sbmt'] && $mform[2]) $ite = $mform[2];
				if ($_GET['view'] && $mform[0]) $ite = $_GET['view'];

				switch(TRUE) {
				case(($bpage[0] == '' || $bpage[0] == 0) && !$mform[0]) :
					$query .= 'LIMIT '.$ite0.$ite;
					break;
// pageswitch
				default:
					if ($bitem > $ite || $items[0] == 's' || ($mform[0] && $bitem && $_GET['sbmt'])) $psh = '<div class="pageswitch">';
					switch(TRUE) {
					case($bitem > $ite && $bpage[0]) :
						list($plink, $mpage) = explode('page=', $uri);
						$max = ($items[2] == 'b') ? ceil(($bitem - $items[1]) / $ite + 1) : ceil($bitem / $ite);
						$plink = htmlspecialchars($plink);
						switch(TRUE) {
						case(intval($mpage) > 0) :
							$mpages = (intval($mpage) - 1) * $max;
							break;

						default:
							$mpage = 1;
							$uri = parse_url($plink);
							$plink .= ($uri['query']) ? '&' : '?';
							$plink = str_replace('&&', '&', $plink);
						}

						switch(TRUE) {
						case($items[2] == 'b' && $mpages == 0) :
							$query .= 'LIMIT 0,'.$items[1];
							$max2 = $items[1];
							if ($items[3] && !$_GET['sbmt']) $template = $items[3];
							break;

						default:
							$mpages = $mpage * $ite - $ite;
							if ($items[2] == 'b') $mpages = $mpages - $ite + $items[1];
							$query .= 'LIMIT '.$mpages.','.$ite;
							$max2 = ($max == $mpage) ? $bitem : $mpages + $ite;
						}
// pageswitch output
						$plk = '<a href="'.$plink.'page=';
						$psh .= ($mpage != 1) ? $plk.($mpage - 1).'">Prev</a>' : 'Prev';
						$psh .= ' [P.'.$mpage.'/'.$max.'] ';
						$psh .= ($mpage != $max) ? $plk.($mpage + 1).'">Next</a>' : 'Next';
						break;
					default:
						$max2 = $bitem;
					}

					if ((($bitem > $ite || $items[0] == 's') && $bpage[0] == '2') || ($mform[0] && $bitem && $_GET['sbmt'])) {
						$psh .= ' [*';
						if (!($bitem > $ite && $bpage[0] == '2')) $psh .= 'Results ';
						if($items[0] == 's' || ($_GET['que'] && $mform[0])) $psh .= 'for "<strong>'.htmlspecialchars($post).'</strong>": ';
						if ($bitem) $psh .= 'No.'.($mpages + 1).'-'.$max2.' of '.$bitem;
						$psh .= '] ';
					}

					if ($bitem > $ite && $bpage[0] == '2') {
						$psh .= ($mpage != 1) ? $plk.'1">First</a> / ' : 'First / ';
						$psh .= ($mpage != $max) ? $plk.$max.'">Last</a>' : 'Last';
					}

					if ($bitem > $ite || $items[0] == 's' || ($mform[0] && $bitem && $_GET['sbmt'])) $psh .= '</div>';
					if (substr($templates, 0, 5) == 'group' && !$subcatid) $psh = '';
				}
				break;

			default:
				if ($mcat == 'a') $query .= ' and c.catid = '.$item['catid'];
				$qnex = $query.' and i.itime > "'.$item['itime'].'" ORDER BY i.itime ASC LIMIT '.$items[1];
				if ($items[1] && $items[1] != 1) sql_query("CREATE TEMPORARY TABLE tnext as ".$qnex);
				$qnext = ($items[1] != 1) ? 'SELECT itemid, blog, body, author, authorname, timestamp, itime, more, authorid, category, catid, closed, authormail, authorurl, title FROM tnext ORDER BY itime DESC' : $query.' and i.itime > "'.$item['itime'].'" ORDER BY i.itime ASC LIMIT 1';
				$qprev = $query.' and i.itime < "'.$item['itime'].'" ORDER BY i.itime DESC LIMIT '.$items[1];
				$query .= ' and i.inumber = '.$itemid.' LIMIT 1';
			}
// Prev or Next

			if ($items[0] == 'item' && $items[1] && !$_GET['que']) {
				$bp4 = explode('/', $bpage, 4);
				$ms4 = explode('/', $msort, 4);
				echo "<div class=\"linkswitch\">\n";
//				echo "[Prev or Next items]<br />\n";
				switch(TRUE) {
				case(($items[3] == 'a' || $items[3] == 'd') && mysql_num_rows(mysql_query($qprev))) :
					echo "&laquo; Prev : ";
					switch(TRUE) {
					case($bp4[0] == 'multiblogs') :
						$this -> MultiTitle($bp4, $qprev);
						break;
					default :
						$blog -> showUsingQuery($bpage, $qprev, 1, 1, 1);
					}
				case(mysql_num_rows(mysql_query($qnext))) :
					if ($items[3] == 'a' || $items[3] == 'd') echo "&raquo; Next : ";
					switch(TRUE) {
					case($itemid && $bp4[0] == 'multiblogs') :
						$this -> MultiTitle($bp4, $qnext);
						break;
					case($itemid) :
						$blog -> showUsingQuery($bpage, $qnext, 1, 1, 1);
					}
				}
				if ($items[3] == '' || $items[3] == 'c') {
					switch(TRUE) {
					case($ms4[0] == 'multiblogs') :
						$this -> MultiTitle($ms4, $query, $temple[0]);
						break;
					default :
						$blog -> showUsingQuery($msort, $query, 1, 1, 1);
					}
				}
				if (!($items[3] == 'a' || $items[3] == 'd') && mysql_num_rows(mysql_query($qprev)))	{
					switch(TRUE) {
					case($bp4[0] == 'multiblogs') :
						$this -> MultiTitle($bp4, $qprev);
						break;
					default :
						$blog -> showUsingQuery($bpage, $qprev, 1, 1, 1);
					}
				}
				echo "</div>\n";
			}
// pagenavi
			if ($items[3]) {
				$cid = ($itemid) ? $item['catid'] : $catid;
				$catname = $blog -> getCategoryName($cid);
				$catlink = createBlogidLink($blogid, array('catid' => $cid));
				$blogname = getBlogNameFromID($blogid);
//				$bloglink = createBlogidLink($blogid);
				$bloglink = quickQuery('SELECT burl as result FROM '.sql_table('blog').' WHERE bnumber ='.$blogid);
				$navi .= "<div class=\"pagenavi\">\n<a href=\"".$CONF['IndexURL']."\">HOME</a> &raquo; ";
				if ($blogid != $CONF['DefaultBlog']) $navi .= (!$cid) ? $blogname." &raquo; " : "<a href=\"".$bloglink."\">".$blogname."</a> &raquo; ";
				if ($cid) $navi .= ($item['catid'] || $archive || $archivelist || $subcatid) ? "<a href=\"".$catlink."\">".$catname."</a> &raquo; " : $catname." &raquo; ";
				$navi .= $q32.$item['title'];
				if($archive) $navi .= 'Archive:'.htmlspecialchars($archive);
				if ($archivelist) $navi .= 'ArchiveList';
				$navi .= "</div>\n";
				if ($items[3] == 'a' || $items[3] == 'b') echo $navi;
			}
// printout
			if ($_GET['sbmt'] && preg_match('/z/', $mform[1])) {
				$p_sg = '*Results ';
				if ($post) $p_sg .= 'for "'.$post.'" ';
				$p_sg .= ' ('.$bitem.') : <span class="iteminfo">'.$p_sf.'</span>';
			}
			if (($mform[0] == 'a' || $mform[0] == 'c' || $mform[0] == 'd') && substr($templates, 0, 5) != 'group') echo '<div class="switchform">'.$p_sform.$p_sg.'</div>';
			if ($bpage[1] != 'a' && !($bpage[2] && !$_GET['sbmt'])) echo $psh;
			if ($s3[1]) echo 'Search Word > '.$s3[1]."<br />\n";
// loggedin change
			$templates = explode('/', $templates, 2);
			if ($memid) $tblog = quickQuery('SELECT tblog as result FROM '.sql_table('team').' WHERE tmember = '.$memid);
			if (!$member -> isLoggedIn() && $templates[0] == 1) $template = $templates[1];
// category template change
			if (substr($templates[0], 0, 3) == 'cat' && $catid) {
				$ccat = explode(':', $templates[0]);
				foreach($ccat as $ccat2){
					if ($ccat2 != 'cat' && $catid == $ccat2) $template = $templates[1];
				}
			}
			switch(TRUE) {
// main print
			case($templates[0] != 'group' || $subcatid) :
				switch(TRUE) {
				case($temple[0] == 'multiblogs') :
					$this -> MultiTitle($temple, $query);
					break;
				case($items[2] != 'a') :
					$blog -> showUsingQuery($template, $query, 1, 1, 1);
				}
				break;
			default:
// group
				switch(TRUE) {
				case($mact && $catid) :
					$sj = $this -> getOption('subcategory_except');
					$shide = explode('/', $sj);
					$q111 = 'SELECT s.catid, s.scatid, s.sname, s.sdesc FROM '.sql_table('category').' as c, '.sql_table('plug_multiple_categories_sub').' as s WHERE s.catid = c.catid and c.catid = '.$catid;
					if ($sj) foreach($shide as $shide2) $q111 .= ' and s.scatid != '.$shide2;
					$q111 .= ' GROUP BY s.scatid ORDER BY ';
					switch(TRUE) {
					case($templates[1] == 1) :
						$q111 .= 'sname';
						break;
					case($templates[1] == 2) :
						$q111 .= 'sdesc';
						break;
					default:
						$q111 .= 'scatid';
					}
					break;
				default:
					if ($catid) $q110 = ' and catid = '.$catid;
					$cj = $this -> getOption('category_except');
					$chide = explode('/', $cj);
					$q111 = 'SELECT catid, cname, cdesc FROM '.sql_table('item').', '.sql_table('category').' WHERE catid = icat and cblog = '.$blogid.$q110;
					if ($cj) foreach($chide as $chide2) $q111 .= ' and catid != '.$chide2;
					$q111 .= ' GROUP BY catid ORDER BY ';
					switch(TRUE) {
					case($templates[1] == 1) :
						$q111 .= 'cname';
						break;
					case($templates[1] == 2) :
						$q111 .= 'cdesc';
						break;
					default:
						$q111 .= 'catid';
					}
				}
				$gcat = mysql_query($q111);
				while($rcat = mysql_fetch_assoc($gcat)) {
					switch(TRUE) {
					case($mact) :
						$q102 = ' and (p.categories REGEXP "(^|,)'.$rcat['catid'].'(,|$)" or i.icat = '.$rcat['catid'].')';
						if ($catid) $q102a = ' and (p.subcategories REGEXP "(^|,)'.$rcat['scatid'].'(,|$)")';
						$q102e = ' and (';
						$q102g = ' (categories REGEXP "(^|,)'.$rcat['catid'].'(,|$)" or icat = '.$rcat['catid'].') and subcategories is null)';
						break;
					default:
						$q102 = ' and i.icat = '.$rcat['catid'];
					}
 					$gcount = mysql_num_rows(mysql_query($q101.$q102.$q102a));
					$q103 = $q101.$q102.$q102a.$que1.' LIMIT '.$items[0];
					switch(TRUE) {
					case($catid && $mact) :
						$gid = $rcat['scatid'];
						$gname = $rcat['sname'];
						$gdesc = $rcat['sdesc'];
						break;
					default:
						$gid = $rcat['catid'];
						$gname = $rcat['cname'];
						$gdesc = $rcat['cdesc'];
					}
					$par = ($catid && $mact) ? array('subcatid' => $gid) : array('catid' => $gid);
					if ($mact) $gnum = 'SELECT COUNT(ititle) as result FROM '.sql_table('item').' left join '.sql_table('plug_multiple_categories').' on inumber = item_id WHERE (categories REGEXP "(^|,)'.$rcat['catid'].'(,|$)" or icat = '.$rcat['catid'].')';
					if ($catid) $gnum .= ' and (subcategories REGEXP "(^|,)'.$rcat['scatid'].'(,|$)")';
					$gnum = quickQuery($gnum);
					$clink = createBlogidLink($blogid, $par);
					echo '<'.$this -> getOption('group_tagt').' class="grouptitle">';
					if ($this -> getOption('group_link') == 'yes') { echo '<a href="'.$clink.'">'.$gname.'</a>'; }else { echo $gname;}
					if ($this -> getOption('group_num') == 'yes') echo ' ('.$gnum.')';
					if ($gcount > $items[0]) echo ' [<a href="'.$clink.'">'.$this -> getOption('group_more').'</a>]';
					echo "</".$this -> getOption('group_tagt').">\n<".$this -> getOption('group_tag');
					if ($this -> getOption('group_tag') != "") echo ' class="groupbody"';
					echo ">";
					switch(TRUE) {
					case($temple[0] == 'multiblogs') :
						$this -> MultiTitle($temple, $q103);
						break;
					default :
						$blog -> showUsingQuery($template, $q103, 1, 1, 1);
					}
					echo '</'.$this -> getOption('group_tag').'>';
				}
				mysql_free_result($gcat);
				$q103 = $q101.$q102e.$q102g.$que1.' LIMIT '.$items[0];

				if ($catid && !$subcatid && $mact && $this -> getOption('group_other') && mysql_num_rows(mysql_query($q103))) {
					echo "<".$this -> getOption('group_tagt')." class=\"grouptitle\">".$this -> getOption('group_other')."</".$this -> getOption('group_tagt').">\n<".$this -> getOption('group_tag');
					if ($this -> getOption('group_tag') != "") echo ' class="groupbody"';
					echo ">";
					switch(TRUE) {
					case($temple[0] == 'multiblogs') :
						$this -> MultiTitle($temple, $q103);
						break;
					default :
						$blog -> showUsingQuery($template, $q103, 1, 1, 1);
					}
					echo '</'.$this -> getOption('group_tag').'>';
				}
			}
			if ($bpage[1] != 'b' && !($bpage[2] && !$_GET['sbmt'])) echo $psh;
			if (($mform[0] == 'b' || $mform[0] == 'c') && $templates[0] != 'group') echo '<div class="switchform">'.$p_sform.$p_sg.'</div>';
			if ($items[3] == 'b' || $items[3] == 'c') echo $navi;
//print_r($query);
			return;
// fixed items
		default:
			if ($page = getVar('page') && $page > 1 && $bpage == '') return;
			if($catid && $msort != '1') return;
			$items = explode('/', $items);
			foreach($items as $mitem){
				$query = 'SELECT DISTINCT'.$q1.$q2.$q3.$q4.' and i.inumber = '.$mitem;
				if($bmode == 'fix') $query .= ' and i.iblog = '.$blogid;
				if($mcat == 'cat') $query .= ' and i.icat = '.$catid;
				$query .= ' LIMIT 1';
				$bitem = mysql_num_rows(mysql_query($query));
				switch(TRUE) {
				case($temple[0] == 'multiblogs' && $bitem) :
					$this -> MultiTitle($temple, $query);
					break;
				case($bitem) :
					$blog -> showUsingQuery($template, $query, 1, 1, 1);
				}
			}
		}
	}

	function SearchQuery($post = '', $s1 = '', $s0 = '') {
		$order = (_CHARSET == 'EUC-JP') ? 'EUC-JP, UTF-8,' : 'UTF-8, EUC-JP,';
		$post = mb_convert_encoding($post, _CHARSET, $order.' JIS, SJIS, ASCII');
		$post = mb_convert_kana($post, 's');
		$post = ereg_replace('[\\]+', '\\', $post);
		$post = ereg_replace('[_%]+', '\\\0', $post);
		$post = ereg_replace('[[:space:]]+', ' ', $post);
		$post = explode(' ', trim($post));
		foreach ($post as $s2) {
			$s2 = ' LIKE "%'.addslashes($s2).'%';
			$query .= ' and ( ';
			$query .= $s1[0].$s2.$s1[1].$s2.$s1[2].$s2.$s1[3].$s2.$s1[4].$s2.$s1[5].$s2;
			if($s0) {
				$query .= $s1[6].$s2.$s1[7].$s2;
			}
			$query .= '" )';
		}
		return $query;
	}

	function MultiTitle($temple = '', $query = '', $jd = '') {
		switch(TRUE) {
		case ($temple[1] == 'tr'):
			$m1 = 'tr';
			$m1a = 'td';
			break;
		case (!$temple[1]):
			$m1 = 'div';
			$m1a = 'span';
			break;
		default:
			$m1 = $temple[1];
			$m1a = 'span';
		}

		$tj = intval(substr($temple[2], 2, 1));
		$tp = sql_query($query);
		while ($row = mysql_fetch_assoc($tp)) {
			echo '<'.$m1.' class="multiblogs_top">';
			switch(TRUE) {
			case($temple[3]) :
				$date = date($temple[3], $row['timestamp']);
				echo '<'.$m1a.' class="multiblogs_date">'.$date.'</'.$m1a.'>';
			}
			switch (TRUE) {
			case ($jd == 1) :
				echo '<'.$m1a.' class="multiblogs_title">'.$row['title'].'</'.$m1a.'>';
				break;
			default :
				echo '<'.$m1a.' class="multiblogs_title"><a href="'
					. createItemLink($row['itemid']).'">'
					. $row['title'].'</a></'.$m1a.'>';
			}
			if ($temple[2]) {
				echo '<'.$m1a.' class="multiblogs_cat">'
					. substr($temple[2], 0, 1)
					. $row['category']
					. substr($temple[2], 1, 1).'</'.$m1a.'>';
			}
			echo '</'.$m1.'>';

			switch(TRUE) {
			case($tj == 1) :
				echo '<'.$m1.' class="multiblogs_body">'
					. shorten(strip_tags($row['body'].$row['more']), ($tj*100), '...').'</'.$m1.'>
';
				break;
			}
		}
		return;
	}
}
?>
