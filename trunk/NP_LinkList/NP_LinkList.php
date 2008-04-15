<?php
/*
	NP_LinkList (based on NP_LinksByBlog)
	by Jim Stone
	   fel
	   nakahara21 (http://nakahara21.com)
	   yu (http://nucleus.datoka.jp/)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	USAGE
	-----
	<%LinkList%>
	<%LinkList(menu1)%> --- symbol
	<%LinkList(menu1,ul)%>     --- symbol, tag-template
	<%LinkList(menu1,ul,[,])%> --- and prefix, postfix 

	HISTORY
	-------
	Ver 0.62: [Fix] Bug fix of list output in BlogAdmin / BlogTeam mode. (2007/02/01)
	Ver 0.61: [Fix] Edit control bug fix. (2006/11/29)
	Ver 0.6 : [Chg] Move edit page to admin area. (2006/11/19)
	          [Add] Edit control option.
	          [Add] 'imgsrc' column added.
	          [Chg] bid:0 = shows on all blogs. Rank 'z' = hidden group.
	          [Chg] 'default' template set changed to be suitable for default skin.
	Ver 0.53: [Fix] Security fix. (2006/09/30)
	Ver 0.52: [Chg] Delete css-class parameter and div output. (2004/02/17)
	Ver 0.51: [Fix] Bug fix and show dialog when delete link/group. (2004/02/06)
	Ver 0.5 : First release as NP_LinkList - based on NP_LinksByBlog ver0.46.
	          [Add] Parameter "Symbol", "Prefix" and "Postfix".
*/

// quote variable to make safe
if(!function_exists('quote_smart')) {
	function quote_smart($value) {
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		if (!is_numeric($value)) {
			//$value = "'". mysql_real_escape_string($value) ."'";
			$value = "'". mysql_escape_string($value) ."'";
		}
		return $value;
	}
}

class NP_LinkList extends NucleusPlugin {

function getName() { return 'LinkList'; }
function getAuthor() { return 'Jim Stone + Fel + nakahara21 + yu'; }
function getURL() { return 'http://works.datoka.jp/index.php?itemid=176'; }
function getVersion() { return '0.62'; }
function getMinNucleusVersion() { return '320'; }
function getEventList() { return array('QuickMenu'); }	
function getTableList() { return array( sql_table('plug_linklist'), sql_table('plug_linklist_grp') ); }

function getDescription () {
	return _LINKLIST_DESCRIPTION;
}
function supportsFeature($what) {
	switch($what){
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
	}
}
function hasAdminArea() {
	return 1;
}

function init() {
	// include language file for this plugin
	$language = ereg_replace( '[\\|/]', '', getLanguageName());
	if (file_exists($this->getDirectory().$language.'.php'))
		include_once($this->getDirectory().$language.'.php');
	else
		include_once($this->getDirectory().'english.php');
	
	//sortkey array (called from admin area)
	$this->arr_skey = array();
	foreach (range('a','z') as $key) {
		$this->arr_skey[ $key ] = strtoupper($key);
	}
}

//group array (called from admin area, too)
function init_grp($flg_edit=false) {
	global $member;
	
	$this->arr_grp = array();
	
	// pick up group data
	$query = "SHOW TABLES LIKE '". sql_table('plug_linklist_grp') ."'";
	$grptable = sql_query ($query);
	if (!mysql_num_rows($grptable)) return;
	
	$query = "SELECT * FROM ". sql_table('plug_linklist_grp') 
		. " ORDER BY sortkey,title";
	$grps = sql_query ($query);
	if (mysql_num_rows($grps) > 0){
		while ($grp = mysql_fetch_object($grps)){
			//check
			if ( $flg_edit and !$this->_checkBlogList($grp->bid) ) continue;
			
			//prepare
			$grp->title = stripslashes($grp->title);
			$grp->description = stripslashes($grp->description);
			$grp->symbol = stripslashes($grp->symbol);
			
			$this->arr_grp[] = $grp;
		}
	}
}

function install() {
	global $CONF;
	
	sql_query ("CREATE TABLE IF NOT EXISTS ". sql_table('plug_linklist') ." (
		id			INT unsigned not null auto_increment,
		title		VARCHAR(255) not null default '',
		description	VARCHAR(255) not null default '',
		url			VARCHAR(255) not null default '',
		imgsrc		VARCHAR(255) not null default '',
		gid			INT unsigned not null default 1,
		sortkey		TINYINT unsigned not null default '20',
		primary key (id))");
		/* imgsrc added (v0.6) */
	
	sql_query ("CREATE TABLE IF NOT EXISTS ". sql_table('plug_linklist_grp') ." (
		id			INT unsigned not null auto_increment,
		title		VARCHAR(255) not null default '',
		description	VARCHAR(255) not null default '',
		symbol		VARCHAR(255) not null default '',
		bid			VARCHAR(255) not null default '1',
		sortkey		CHAR(1) not null default 'm',
		primary key (id))");
		/* bid is a (comma-joined) string value */
	
	$check_query = "SELECT * FROM ". sql_table('plug_linklist_grp');
	$check_rows = sql_query($check_query);
	if (mysql_num_rows($check_rows) < 1) { //if no rows in grp table, set default group
		$query = "INSERT INTO ". sql_table('plug_linklist_grp') ." SET title='Links',bid='0',sortkey='a'";
		sql_query($query);
	}

	$this->createOption('sel_edit',     _LINKLIST_OPTION_EDIT, 'select', 'siteadmin', 'Site Admin|siteadmin|Blog Admin|blogadmin|Blog Team|blogteam');
	$this->createOption('def_tplname',  _LINKLIST_OPTION_TPLNAME, 'text', 'default');
	$this->createOption('path_image',   _LINKLIST_OPTION_PATH_IMAGE, 'text', $CONF['MediaURL'].'banner/');
	$this->createOption('flg_extlink',  _LINKLIST_OPTION_EXTLINK, 'yesno', 'no');
	$this->createOption('flg_qmenu',    _LINKLIST_OPTION_QMENU, 'yesno', 'yes');
	$this->createOption('flg_erase',    _LINKLIST_OPTION_ERACE, 'yesno', 'no');
}

function unInstall() {
	if ($this->getOption('flg_erase') == 'yes') {
		sql_query ('DROP TABLE '. sql_table('plug_linklist') );
		sql_query ('DROP TABLE '. sql_table('plug_linklist_grp') );
	}
}

function update() {
	//add a new column 'imgsrc'
	sql_query("ALTER TABLE ". sql_table('plug_linklist') ." ADD imgsrc VARCHAR(255) not null default ''");
	
	//modify column 'sortkey'
	sql_query("ALTER TABLE ". sql_table('plug_linklist') ." MODIFY sortkey TINYINT unsigned not null default '20'");
	
	//convert hidden group settings
	sql_query("UPDATE ". sql_table('plug_linklist_grp') ." SET sortkey='z' WHERE bid='0'");
	
	//convert banner settings (image url)
	$res = sql_query("SELECT id,title,description FROM ". sql_table('plug_linklist'));
	while ($row = mysql_fetch_assoc($res)) {
		if ( preg_match('/\.(gif|jpg|jpeg|png)$/', $row['title']) ) {
			$row['title'] = stripslashes($row['title']);
			$row['description'] = stripslashes($row['description']);
			$query = sprintf("UPDATE %s SET title=%s, imgsrc=%s WHERE id=%d", 
				sql_table('plug_linklist'),
				quote_smart( $row['description'] ), //description -> title
				quote_smart( $row['title'] ), //title -> imgsrc
				quote_smart( $row['id'] ) );
			sql_query($query);
		}
	}

}

function _checkColumn(){
	$res = sql_query("SHOW FIELDS from ".sql_table('plug_linklist') );
	$fieldnames = array();
	while ($co = mysql_fetch_assoc($res)) {
		$fieldnames[] = $co['Field'];
	}
	return in_array('imgsrc',$fieldnames);
}

function event_QuickMenu(&$data) {
	global $member;
	
	if ($this->getOption('flg_qmenu') == 'yes') {
		$edit = $this->getOption('sel_edit');
		if (($edit == 'siteadmin' and !$member->isAdmin()) or
			($edit == 'blogadmin'  and !$this->_isBlogAdmin()) or
			($edit == 'blogteam'   and !$this->_isBlogTeam())
			) { 
			return;
		}
		array_push(
			$data['options'], 
			array(
				'title' => _LINKLIST_QMENU_TITLE,
				'url' => $this->getAdminURL(),
				'tooltip' => _LINKLIST_QMENU_TOOLTIP
			)
		);
	}
}

function _isBlogAdmin() {
	global $member;
	
	$query = 'SELECT * FROM '.sql_table('team').' WHERE'
	       . ' tmember='. $member->getID() .' and tadmin=1';
	return (mysql_num_rows(sql_query($query)) != 0);
}

function _isBlogTeam() {
	global $member;
	
	$query = 'SELECT * FROM '.sql_table('team').' WHERE'
	       . ' tmember='. $member->getID();
	return (mysql_num_rows(sql_query($query)) != 0);
}

function _checkGroupList($gid) {
	global $member;
	
	foreach ($this->arr_grp as $grp) {
		if ($grp->id == $gid) return true;
	}
	
	return false;
}

function _checkBlogList($chkstr) {
	global $member;
	
	$arr_chk = explode(',', $chkstr);
	$arr_chk = (array) $arr_chk;
	$edit = $this->getOption('sel_edit');
	
	//exception
	if ($arr_chk[0] == 0) { //(hidden)
		if ($edit == 'siteadmin') return true;
		else return false;
	}
	
	switch ($edit) {
	case 'siteadmin':
		return true;
		break;
	case 'blogadmin':
		$query = 'SELECT tblog FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID() .' and tadmin=1';
		break;
	case 'blogteam':
		$query = 'SELECT tblog FROM '.sql_table('team').' WHERE'
		       . ' tmember='. $member->getID();
		break;
	}
	$res = sql_query($query);
	
	$arr_bid = array();
	while ($row = mysql_fetch_row($res)) {
		$arr_bid[] = $row[0];
	}
	foreach ($arr_chk as $chk) {
		if ( !in_array($chk, $arr_bid) ) return false; // all $chk must have blog rights
	}
	return true;
}

/*function _checkBlogsRights($arr_bid) {
	global $member;
	
	$edit = $this->getOption('sel_edit');
	$arr_bid = (array) $arr_bid;
	foreach ($arr_bid as $bid) {
		if ($edit == 'blogadmin' and $member->blogAdminRights($bid)) continue;
		else if ($edit == 'blogteam' and $member->teamRights($bid)) continue;
		return false;
	}
	
	return true;
}*/

function _canEdit($bid) {
	global $member;
	
	if (!$member->isLoggedIn()) return 0;
	
	switch ( $this->getOption('sel_edit') ) {
	case 'siteadmin':
		return $member->isAdmin();
		break;
	case 'blogadmin':
		return $member->blogAdminRights($bid);
		break;
	case 'blogteam':
		return $member->teamRights($bid);
		break;
	}
}

function doSkinVar($skinType, $symbol, $tplname='', $pre='', $post='') {
	global $CONF, $manager, $blog;
	
	$actionURL = $CONF['ActionURL'];
	if ($blog) $b =& $blog; 
	else $b =& $manager->getBlog($CONF['DefaultBlog']);
	$bid = $b->getID();
	if (empty($tplname)) $tplname = $this->getOption('def_tplname');
	
	/* --- LIST TEMPLATE --- */
	//'default' template set changed to be suitable for default skin. [v0.6]
	//'banner' part added. [v0.6]
	//'head' part became necessary in each pattern. [v0.6]
	
	// default
	$tpl['default']['head']   = '<dl class="sidebardl"><dt>$title</dt>';
	$tpl['default']['begin']  = '';
	$tpl['default']['link']   = '<dd>$pre<a href="$href" title="$desc" $onclick>$title</a>$post</dd>';
	$tpl['default']['banner'] = '<dd>$pre<a href="$href" title="$title : $desc" $onclick><img src="$src" alt="$title" /></a>$post</dd>';
	$tpl['default']['nolink'] = '<dd>$pre$title$post <span>$desc</span></dd>';
	$tpl['default']['end']    = '</dl>';
	
	// ul pattern
	$tpl['ul']['head']   = '<h2 title="$desc">$title</h2>';
	$tpl['ul']['begin']  = '<ul>';
	$tpl['ul']['link']   = '<li>$pre<a href="$href" title="$desc" $onclick>$title</a>$post</li>';
	$tpl['ul']['banner'] = '<li>$pre<a href="$href" title="$title : $desc" $onclick><img src="$src" alt="$title" /></a>$post</li>';
	$tpl['ul']['nolink'] = '<li>$pre$title$post <span>$desc</span></li>';
	$tpl['ul']['end']    = '</ul>';
	
	// ol pattern
	$tpl['ol']['head']   = '<h2 title="$desc">$title</h2>';
	$tpl['ol']['begin']  = '<ol>';
	$tpl['ol']['link']   = '<li>$pre<a href="$href" title="$desc" $onclick>$title</a>$post</li>';
	$tpl['ol']['banner'] = '<li>$pre<a href="$href" title="$title : $desc" $onclick><img src="$src" alt="$title" /></a>$post</li>';
	$tpl['ol']['nolink'] = '<li>$pre$title$post <span>$desc</span></li>';
	$tpl['ol']['end']    = '</ol>';
	
	// dl pattern
	$tpl['dl']['head']   = '<h2 title="$desc">$title</h2>';
	$tpl['dl']['begin']  = '<dl>';
	$tpl['dl']['link']   = '<dt>$pre<a href="$href" $onclick>$title</a>$post</dt><dd>$desc</dd>';
	$tpl['dl']['banner'] = '<dt>$pre<a href="$href" title="$title" $onclick><img src="$src" alt="$title" /></a>$post</dt><dd>$desc</dd>';
	$tpl['dl']['nolink'] = '<dt>$pre$title$post</dt><dd>$desc</dd>';
	$tpl['dl']['end']    = '</dl>';
	
	// plain pattern
	$tpl['plain']['head']   = '<h2 title="$desc">$title</h2>';
	$tpl['plain']['begin']  = '';
	$tpl['plain']['link']   = '$pre<a href="$href" $onclick>$title</a>$post <span>$desc</span><br />';
	$tpl['plain']['banner'] = '$pre<a href="$href" title="$title" $onclick><img src="$src" alt="$title" /></a>$post <span>$desc</span><br />';
	$tpl['plain']['nolink'] = '$pre$title$post <span>$desc</span><br />';
	$tpl['plain']['end']    = '';
	
	// pcp pattern (for Pure CSS Popups)
	$tpl['pcp']['head']   = '<h2><a href="#">$title<span>$desc</span></a></h2>'; //custom header
	$tpl['pcp']['begin']  = '<ul>';
	$tpl['pcp']['link']   = '<li>$pre<a href="$href" $onclick>$title<span>$desc</span></a>$post</li>';
	$tpl['pcp']['banner'] = '<li>$pre<a href="$href" title="$title" $onclick><img src="$src" alt="$title" /><span>$desc</span></a>$post</li>';
	$tpl['pcp']['nolink'] = '<li>$pre$title$post <span>$desc</span></li>';
	$tpl['pcp']['end']    = '</ul>';
	
	// new pattern --- write here if you want to add
	
	/* --- END OF LIST TEMPLATE --- */
	
	// get data
	if (!count($this->arr_grp)) $this->init_grp();
	
	// group loop
	foreach ($this->arr_grp as $grp) {
		// list matching
		$ary_bid = split(',', $grp->bid);
		if ($symbol == '') {
			if ( $grp->sortkey == 'z' or ($grp->bid != 0 and !in_array($bid, $ary_bid)) ) {
				continue;
			}
		}
		else if ($symbol != $grp->symbol) continue;
		
		// tag-template vars for group
		$grp_target = array('$title','$desc');
		$grp_replace = array(
			htmlspecialchars($grp->title, ENT_QUOTES),
			htmlspecialchars($grp->description, ENT_QUOTES),
			);
		
		$query = "SELECT id,title,url,imgsrc,description,sortkey FROM ". sql_table('plug_linklist')
			." WHERE gid = $grp->id"
			." ORDER BY sortkey,title";
		$links = sql_query ($query);
		
		if (mysql_num_rows($links) > 0){ //if link group have links actually
			if ( !empty($grp->title) ) {
				// print header
				echo str_replace($grp_target, $grp_replace, $tpl[$tplname]['head']);
			}
			elseif ( empty($grp->title) and $grp->sortkey != 'a') {
				echo "<br />";
			}
			
			echo $tpl[$tplname]['begin']."\n";
			//link loop
			while ($link = mysql_fetch_object($links)){
				//prepare
				$link->title = stripslashes($link->title);
				$link->description = stripslashes($link->description);
				$link->url = stripslashes($link->url);
				$link->imgsrc = stripslashes($link->imgsrc);
				
				// template type
				if ( empty($link->url) or $link->url == 'http://' ) {
					$tag_sel = 'nolink';
				}
				else if ( !empty($link->imgsrc) ) {
					$tag_sel = 'banner';
					if (! preg_match('{^http://}', $link->imgsrc) ) //add image path
						$link->imgsrc = $this->getOption('path_image') . $link->imgsrc;
				}
				else {
					$tag_sel = 'link';
				}
				
				// open new window (external link)
				if ($this->getOption('flg_extlink') == 'yes' and 
					preg_match("{^.+://}", $link->url) ) // protocol(http, https, ftp, etc.)
					$onclick = 'onclick="window.open(this.href);return false;"';
				else
					$onclick = '';
				
				// tag-template vars for link
				$l_target = array('$title','$desc','$href','$src','$onclick','$pre','$post');
				$l_replace = array(
					htmlspecialchars($link->title, ENT_QUOTES),
					htmlspecialchars($link->description, ENT_QUOTES),
					htmlspecialchars($link->url, ENT_QUOTES),
					htmlspecialchars($link->imgsrc, ENT_QUOTES),
					$onclick,
					$pre,
					$post,
					);
				
				// print element
				echo str_replace($l_target, $l_replace, $tpl[$tplname][$tag_sel])."\n";
				
			} //end of link loop
			echo $tpl[$tplname]['end']."\n";
		}
	} //end of group loop
	
	return;
} //end of function doSkinVar()

} //end of class
?>
