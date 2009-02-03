<?php
/*
	NP_ArchiveIndex by yu(http://nucleus.datoka.jp/)
	Based on NP_ArchiveListEX ver0.6 by nakahara21(http://xx.nakahara21.net/)
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	Usage
	-----
	<%ArchiveIndex%>
	<%ArchiveIndex(5)%>			//set item amount of each category on archive index
	<%ArchiveIndex(5,10,desc)%>	//set page amount, show category description
	<%ArchiveIndex(5,10,none)%>	//set page amount, don't show category description
	
	History
	-------
	2008/04/15 Ver0.81:
		[FIX] small fixes on 'Template: List (begin)' and 'Template: List element'.
	2004/11/30 Ver0.8:
		[ADD] Works with NP_UpdateTime and NP_ContentsList.
		[ADD] Blog option.
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_ArchiveIndex extends NucleusPlugin {

	function getName() { return 'Archive Index'; }
	function getAuthor()  { return 'nakahara21 + yu'; }
	function getURL() { return 'http://works.datoka.jp/index.php?itemid=167'; }
	function getVersion() { return '0.81'; }
	function getMinNucleusVersion() { return 220; }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return "Show all item title on archive list. [Optional] amount:  limit the amount of links shown (e.g. if you only want to show 5 links on each list by category index). page amount: the amount of links in a page. show_catdesc: show category description. 'desc' or 'none'. Usage: &lt;%ArchiveIndex%&gt;, &lt;%ArchiveIndex(5)%&gt;, &lt;%ArchiveIndex(5,10)%&gt;, &lt;%ArchiveIndex(5,10,desc)%&gt;";
	}

	function install() {
		
		// global option
		$this->createOption('itemOrder', 'Order of title (index)', 'select', 'time,DESC', 'title,DESC|title,DESC|title,ASC|title,ASC|time,DESC|time,DESC|time,ASC|time,ASC');
		$this->createOption('itemOrder2', 'Order of title (when a category is selected)', 'select', 'time,DESC', 'title,DESC|title,DESC|title,ASC|title,ASC|time,DESC|time,DESC|time,ASC|time,ASC');
		$this->createOption('catDesc','Show category description.','yesno','yes');
		$this->createOption('flg_showupd','Show update information (NP_UpdateTime).','yesno','no');
		$this->createOption('flg_sortupd','Sort with update information (NP_UpdateTime).','yesno','no');
		$this->createOption('idateFormat','Date format for item list','text','Y-m-d H:i');
		
		$this->createOption('tempListBegin','Template: List (begin)','text','<table><thead title="%catdesc%"><tr><th colspan="2"><a href="%caturl%">%catname%</a></th></tr></thead><tbody>');
		$this->createOption('tempListElement','Template: List element','text','<tr%bg%><td><span class="title%up%">%titlelink%</span></td><td class="detail">%detail%</td></tr>');
		$this->createOption('tempListMore','Template: List element (morelink)','text','<tr class="more"><td>%pagenavi%</td><td class="detail">%morelink%</td></tr>');
		$this->createOption('tempListEnd','Template: List (end)','text','</tbody></table>');
		
		// blog option
		$this->createBlogOption('switch','Use blog option?','yesno','no');
		$this->createBlogOption('itemOrder', 'Order of title (index)', 'select', 'time,DESC', 'title,DESC|title,DESC|title,ASC|title,ASC|time,DESC|time,DESC|time,ASC|time,ASC');
		$this->createBlogOption('itemOrder2', 'Order of title (when a category is selected)', 'select', 'time,DESC', 'title,DESC|title,DESC|title,ASC|title,ASC|time,DESC|time,DESC|time,ASC|time,ASC');
		$this->createBlogOption('catDesc','Show category description.','yesno','yes');
		$this->createBlogOption('flg_showupd','Show update information (NP_UpdateTime).','yesno','no');
		$this->createBlogOption('flg_sortupd','Sort with update information (NP_UpdateTime).','yesno','no');
		$this->createBlogOption('idateFormat','Date format for item list','text','Y-m-d H:i');
		
		$this->createBlogOption('tempListBegin','Template: List (begin)','text','<table><thead title="%catdesc%"><th><a href="%caturl%">%catname%</a></th><th></th></thead><tbody>');
		$this->createBlogOption('tempListElement','Template: List element','text','<tr %bg%><td><span class="title%up%">%titlelink%</span></td><td class="detail">%detail%</td></tr>');
		$this->createBlogOption('tempListMore','Template: List element (morelink)','text','<tr class="more"><td>%pagenavi%</td><td class="detail">%morelink%</td></tr>');
		$this->createBlogOption('tempListEnd','Template: List (end)','text','</tbody></table>');
	}
	
	function unInstall() {
		//nothing to do
	}

	function doSkinVar($skinType, $amount, $pamount=10, $show_catdesc='') { 
		global $manager, $member, $blog, $CONF, $catid, $itemid; 
		
		if ($blog) $b =& $blog; 
		else $b =& $manager->getBlog($CONF['DefaultBlog']);
		$bid = $b->getID();
		
		// get global option
		$op_itemOrder =        $this->getOption('itemOrder');
		$op_itemOrder2 =       $this->getOption('itemOrder2');
		$op_catDesc =          $this->getOption('catDesc');
		$op_flg_showupd =      $this->getOption('flg_showupd');
		$op_flg_sortupd =      $this->getOption('flg_sortupd');
		$op_idateFormat =      $this->getOption('idateFormat');
		$op_tempListBegin =    $this->getOption('tempListBegin');
		$op_tempListElement =  $this->getOption('tempListElement');
		$op_tempListMore =     $this->getOption('tempListMore');
		$op_tempListEnd =      $this->getOption('tempListEnd');
		
		// get blog option
		if ($this->getBlogOption($bid, 'switch') == 'yes') {
			$op_itemOrder =        $this->getBlogOption($bid, 'itemOrder');
			$op_itemOrder2 =       $this->getBlogOption($bid, 'itemOrder2');
			$op_catDesc =          $this->getBlogOption($bid, 'catDesc');
			$op_flg_showupd =      $this->getBlogOption($bid, 'flg_showupd');
			$op_flg_sortupd =      $this->getBlogOption($bid, 'flg_sortupd');
			$op_idateFormat =      $this->getBlogOption($bid, 'idateFormat');
			$op_tempListBegin =    $this->getBlogOption($bid, 'tempListBegin');
			$op_tempListElement =  $this->getBlogOption($bid, 'tempListElement');
			$op_tempListMore =     $this->getBlogOption($bid, 'tempListMore');
			$op_tempListEnd =      $this->getBlogOption($bid, 'tempListEnd');
		}
		
		if (!is_numeric($amount)) $amount = 5;
		if (!is_numeric($pamount)) $pamount = 10;
		
		if ($show_catdesc == '') {
			if ($op_catDesc == 'yes') $show_catdesc = 'desc';
			else $show_catdesc = 'none';
		}
		
		if ($blog) { 
			$b =& $blog; 
		} else { 
			$b =& $manager->getBlog($CONF['DefaultBlog']); 
		} 
		$blogid = $b->getID();

		if ($catid) {
			$this->linkparams = array('catid' => $catid);
		}

		//get value from request
		$page = intRequestVar('ap');
		if ($catid) {
			$item_order = requestVar('ao');
			//check value (also used to make flip link)
			list($itarget, $iorder) = @split(' ',$item_order);
			if(!($itarget=='time' or $itarget=='title' or $iorder =='desc'  or $iorder =='asc')) {
				$item_order = $op_itemOrder2;
				list($itarget, $iorder) = @split(',',$item_order);
			}
		}
		else {
			$item_order = $op_itemOrder;
			list($itarget, $iorder) = @split(',',$item_order);
		}
		$iorder = strtolower($iorder);
		
		$now = time();
		$idateformat = $op_idateFormat;
		$archivedate = date('Y-m');	
		
		//get item data
		if ($manager->pluginInstalled('NP_UpdateTime')) {
			$flg_showupdate = ($op_flg_showupd == 'yes');
			$flg_sortupdate = ($op_flg_sortupd == 'yes');
		}
		if ($flg_showupdate or $flg_sortupdate) {
			//get update info
			$query = 'SELECT r.up_id as itemid, UNIX_TIMESTAMP(r.updatetime) as utime, i.itime as itime FROM '.sql_table('plugin_rectime') . ' as r, '.sql_table('item') .' as i WHERE r.up_id=i.inumber and r.updatetime>i.itime'
				. ' ORDER BY itemid ASC';
			$ut_res = sql_query($query);
			while ($utinfo = mysql_fetch_object($ut_res)) {
				if ($catid and 
					$catid != $this->_getCategoryIDFromItemID($utinfo->itemid)) continue;
				$up_ids[] = $utinfo->itemid;
				$up_tstamps[] = $utinfo->utime;
				if ($flg_sortupdate) $up_datetimes[$utinfo->itemid] = $utinfo->itime; // for title prop.
				else $up_datetimes[$utinfo->itemid] = date($idateformat, $utinfo->utime);
			}
			mysql_free_result($ut_res);
		}
		
		if ($flg_sortupdate and count($up_ids)) {
			$str_up_ids = join(',', $up_ids);
			$str_up_tstamps = join(',', $up_tstamps);
			
			//select
			$query = 'SELECT inumber, ititle, icat, IF(inumber IN('.$str_up_ids.'), ELT(INTERVAL(inumber,'.$str_up_ids.'), '.$str_up_tstamps.'), UNIX_TIMESTAMP(itime)) as itime, iauthor FROM '.sql_table('item');
		}
		else {
			$query = 'SELECT inumber, ititle, icat, UNIX_TIMESTAMP(itime) as itime, iauthor FROM '.sql_table('item');
		}
		
		$query .= ' WHERE iblog=' . $blogid
			.' and UNIX_TIMESTAMP(itime)<='. $now .' and idraft=0';
		
		if ($catid) {
			$query .= ' and icat=' . intval($catid);
			
			//item count in the category
			$resnum = sql_query($query);
			$itemnum = mysql_num_rows($resnum);
			mysql_free_result($resnum);
			$pagemax = ceil($itemnum / $pamount);
		}
		
		if ($itarget == 'title') $query .= ' ORDER BY ititle';
		else $query .= ' ORDER BY itime';
		if ($iorder == 'asc') $query .= ' ASC';
		else $query .= ' DESC';
		
		if ($catid) {
			if ($page < 1) $page = 1;
			$poffset = ($page-1) * $pamount;
			$query .= " LIMIT $poffset,$pamount";
		}
		$res = sql_query($query);
		
		//set data by category
		$list_cat  = array();
		$list_item = array();
		$cnt_item = array();
		while ($current = mysql_fetch_object($res)) {
			$cnt_item[$current->icat]++;
			if (!$catid and $cnt_item[$current->icat] > $amount) { //check the amount
				continue;
			}
			
			$inumber = $current->inumber; //itemid
			$idetail = date($idateformat, $current->itime); //itemdate
			
			$list_cat[$current->icat][] = $inumber;
			$list_item[$inumber]['ititle']   = htmlspecialchars(strip_tags($current->ititle));
			$list_item[$inumber]['itemlink'] = createItemLink($inumber,$this->linkparams);
			$list_item[$inumber]['idetail']  = $idetail;
		}
		mysql_free_result($res);
		
		//prepare list by category
		$arr_out = array();
		foreach ($list_cat as $icat => $arr_icat) {
			// buffer category name
			$icatName = $b->getCategoryName($icat);
			if (!$catid) $icatDesc = $b->getCategoryDesc($icat);
			else $icatDesc = '&raquo; Back to index';
			
			if (!$catid) $extra = array('catid' => $icat);
			else $extra = array();
			$arclist_link = createArchiveListLink($blogid, $extra);
			
			$temp_list_b = $op_tempListBegin;
			$rep_from = array('/%caturl%/','/%catname%/','/%catdesc%/');
			$rep_to   = array($arclist_link, $icatName, $icatDesc);
			$temp_list_b = preg_replace($rep_from, $rep_to, $temp_list_b);
			$temp_list_e = $op_tempListEnd;
			
			$icnt = 0;
			$arr_title = array();
			foreach ($arr_icat as $inumber) {
				//buffer item title
				$ititle   = $list_item[$inumber]['ititle'];
				if (empty($ititle)) $ititle = '(no title)'; 
				$itemlink = $list_item[$inumber]['itemlink'];
				$idetail  = $list_item[$inumber]['idetail'];
				$bg = '';
				$upstr = '';
				$titledesc = '';
				
				if ($icnt % 2 == 1) $bg = " class='stripe'";
				if (count($up_ids) and in_array($inumber, $up_ids)) {
					if($flg_showupdate) $upstr = '-up ';
					if($flg_sortupdate) $titledesc = " title='Posted on ". $up_datetimes[$inumber] ."'";
					else $titledesc = " title='Updated on ". $up_datetimes[$inumber] ."'";
				}
				$titlelink = "<a href='$itemlink' $titledesc>$ititle</a>";
				
				$temp_list_el = $op_tempListElement;
				$rep_from = array('/%bg%/','/%titlelink%/','/%detail%/','/%up%/');
				$rep_to   = array($bg, $titlelink, $idetail, $upstr);
				$temp_list_el = preg_replace($rep_from, $rep_to, $temp_list_el);
				$arr_title[]  = $temp_list_el."\n";
				
				$icnt++;
				if (!$catid and $icnt >= $amount) break;
			}
			
			// last list (navigation)
			if (!$catid) {
				if ($cnt_item[$icat] <= $amount) { // category description
					$arclist_link = '<span class="nomore">&raquo; More</span>';
					if ($show_catdesc == 'desc') $icatDescStr = '<span class="catdesc">'.$icatDesc.'</span>';
					else $icatDescStr = '';
					
					$temp_list_more = $op_tempListMore;
					$rep_from = array('/%pagenavi%/','/%morelink%/');
					$rep_to   = array($icatDescStr, $arclist_link);
					$temp_list_more = preg_replace($rep_from, $rep_to, $temp_list_more);
					$arr_title[] = "$temp_list_more\n"; 
				}
				else { // category description with more link
					$extra = array('catid' => $icat);
					$arclist_link = '<a href="'. createArchiveListLink($blogid, $extra) .'">&raquo; More</a>';
					if ($show_catdesc == 'desc') $icatDescStr = '<span class="catdesc">'.$icatDesc.'</span>';
					else $icatDescStr = '';
					
					$temp_list_more = $op_tempListMore;
					$rep_from = array('/%pagenavi%/','/%morelink%/');
					$rep_to   = array($icatDescStr, $arclist_link);
					$temp_list_more = preg_replace($rep_from, $rep_to, $temp_list_more);
					$arr_title[] = "$temp_list_more\n"; 
				}
			}
			else { // sort order, page navi and all category link
				//make 'order switch'
				$itarget_flip = ($itarget=='title') ? 'time' : 'title'; //mode change
				$iorder_flip  = ($iorder =='desc')  ? 'asc'  : 'desc';  //order change
				$itarget_flip_order = ($itarget_flip =='title') ? 'asc' : 'desc'; //ini-order on mode change
				
				if ($itarget == 'title') {
					$iorder_str      = ($iorder == 'desc') ? 'Z-A' : 'A-Z';
					$iorder_flip_str = ($iorder == 'desc') ? 'A-Z' : 'Z-A';
				}
				else { //time
					$iorder_str      = ($iorder == 'desc') ? 'New' : 'Old';
					$iorder_flip_str = ($iorder == 'desc') ? 'Old' : 'New';
				}
				
				$orderURL = serverVar('REQUEST_URI');
				$orderURL = preg_replace('/[?&]ap=[0-9]*/','',$orderURL); //delete to add 'ap' param to last
				$orderURL = preg_replace('/[?&]ao=[^&0-9]*/','',$orderURL);
				
				if (strpos($orderURL,'?')===false) $orderURL .= "?ao=";
				else $orderURL .= "&ao=";
				
				$orderURL1 = $orderURL . "$itarget_flip+$itarget_flip_order";
				$orderURL2 = $orderURL . "$itarget+$iorder_flip";
				$pagenavi = "Sort: <strong>$itarget</strong>/<a href='$orderURL1'>$itarget_flip</a>, ";
				$pagenavi.= "<strong>$iorder_str</strong>/<a href='$orderURL2'>$iorder_flip_str</a>";
				
				//make 'page navi link'
				$pagenavi.= "&nbsp;&nbsp; Page:";
				for ($i=1; $i<=$pagemax; $i++) {
					if ($i==$page) $pagenavi.= "<strong>&nbsp;$i&nbsp;</strong>";
					else {
						$pageURL = serverVar('REQUEST_URI');
						$pageURL = preg_replace('/([?&]ap=)[0-9]*/','',$pageURL);
						if (strpos($pageURL,'?')===false) $pageURL .= "?ap=$i";
						else $pageURL .= "&ap=$i";
						$pagenavi.= "<a href='$pageURL'>&nbsp;$i&nbsp;</a>";
					}
				}
				//and make 'all category link'
				$arclist_link = '<a href="'. createArchiveListLink($blogid) .'">&raquo; Back to index</a>';
				
				$temp_list_more = $op_tempListMore;
				$rep_from = array('/%pagenavi%/','/%morelink%/');
				$rep_to   = array($pagenavi, $arclist_link);
				$temp_list_more = preg_replace($rep_from, $rep_to, $temp_list_more);
				$arr_title[] = "$temp_list_more\n";
			}
			
			$arr_out[$icatName] .= 
				$temp_list_b ."\n". join('',$arr_title) . $temp_list_e ."\n";
		} //end of foreach
		
		//sort by category
		if ($manager->pluginInstalled('NP_ContentsList')) {
			$plugin =& $manager->getPlugin('NP_ContentsList');
			if ($plugin) {
				$query = 'SELECT rid as catid FROM '. sql_table('plug_contentslist_rank') .' WHERE blog=0 AND rank<20 ORDER BY rank ASC'; // you can delete 'AND rank<20'
				$cl_res = sql_query($query); 
				
				$arr_out2 = $arr_out;
				$arr_out = array();
				while ($clrank = mysql_fetch_object($cl_res)) {
					$icatName = $b->getCategoryName($clrank->catid);
					$arr_out[] = $arr_out2[$icatName];
				}
				$arr_out2 = '';
			}
		}
		else {
			ksort($arr_out);
		}
		
		//flush the buffer
		foreach ($arr_out as $value) {
			echo $value;
		}
		
	} //end of function doSkinVar


	// helper function
	function _getCategoryIDFromItemID($itemid) {
		return quickQuery('SELECT icat as result FROM '.sql_table('item').' WHERE inumber='.intval($itemid));
	}

} //end of class
?>