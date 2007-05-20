<?php

/**
  * Plugin for Nucleus CMS (http://plugins.nucleuscms.org/)
  * Copyright (C) 2003 The Nucleus Plugins Project
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  *
  * see license.txt for the full license
  */

/**
* Usage:
*		 
*
* Versions:
*
*  TODO
*		- documentation
*		- etc
*/

if (!function_exists('array_key_exists')){
	function array_key_exists($key, $array) {
		return key_exists($key, $array);
	}
}

class NP_MultipleCategories extends NucleusPlugin {

	function getName()	{ return 'Multiple Categories [Custom Edition]'; }
	function getAuthor()	  { return 'Anand + nakahara21 + Taka + sato(na) + shizuki + Katsumi'; }
	function getURL()	 { return 'http://reverb.jp/vivian/download.php?itemid=NP_MultipleCategories'; }
	function getVersion()	 { return '0.5.1j'; }
	function getMinNucleusVersion()	 { return '220'; }
	function getDescription()	{
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) {
			include_once($this->getDirectory().'language/'.$language.'.php'); 
		} else {
			include_once($this->getDirectory().'language/'.'english.php');
		}
		return _NPMC_DESCRIPTION;
	}
	function supportsFeature($what) {
		switch($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function install() {
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) {
			include_once($this->getDirectory().'language/'.$language.'.php'); 
		} else {
			include_once($this->getDirectory().'language/'.'english.php');
		}
		
		$this->createOption('addindex',      _NP_MCOP_ADDINDEX, "yesno",    'yes');
		$this->createOption('addblogid_def', _NP_MCOP_ADBIDDEF, "yesno",    'no');
		$this->createOption('addblogid',     _NP_MCOP_ADBLOGID, "yesno",    'yes');
		$this->createOption("mainsep",       _NP_MCOP_MAINSEP,  "text",     " , ");
		$this->createOption("addsep",        _NP_MCOP_ADDSEP,   "text",     " , ");
		$this->createOption("subformat",     _NP_MCOP_SUBFOMT,  "text",     "<%category%> ( <%subcategory%> )");
		$this->createOption("catheader",     _NP_MCOP_CATHEADR, "textarea", '<ul class="nobullets">' . "\n");
		$this->createOption("catlist",       _NP_MCOP_CATLIST,  "textarea", '<li<%catflag%>><a href="<%catlink%>"><%catname%></a>(<%catamount%>)<%subcategorylist%></li>'."\n");
		$this->createOption("catfooter",     _NP_MCOP_CATFOOTR, "textarea", '</ul>' . "\n");
		$this->createOption("catflag",       _NP_MCOP_CATFLAG,  "textarea", ' class="current"' . "\n");
		$this->createOption("subheader",     _NP_MCOP_SUBHEADR, "textarea", '<ul>' . "\n");
		$this->createOption("sublist",       _NP_MCOP_SUBLIST,  "textarea", '<li<%subflag%>><a href="<%sublink%>"><%subname%></a>(<%subamount%>)</li>'."\n");
		$this->createOption("subfooter",     _NP_MCOP_SUBFOOTR, "textarea", '</ul>' . "\n");
		$this->createOption("subflag",       _NP_MCOP_SUBFLAG,  "textarea", ' class="current"' . "\n");
		$this->createOption("replace",       _NP_MCOP_REPLACE,  'yesno',    'no');
		$this->createOption("replacechar",   _NP_MCOP_REPRCHAR, 'text',     '+');
		$this->createOption("archeader",     _NP_MCOP_ARCHEADR, "textarea", '<ul>' . "\n");
		$this->createOption("arclist",       _NP_MCOP_ARCLIST,  "textarea", '<li><a href="<%archivelink%>">%Y-%m</a></li>'."\n");
		$this->createOption("arcfooter",     _NP_MCOP_ARCFOOTR, "textarea", '</ul>'."\n");
		$this->createOption("locale",        _NP_MCOP_LOCALE,   "text",     'ja_JP');
		$this->createOption("quickmenu",     _NP_MCOP_QICKMENU, "yesno",    "no");
		$this->createOption("del_uninstall", _NP_MCOP_DELTABLE, "yesno",    "no");
/*
		$this->createOption('addindex', '[When URL-Mode is normal] If a blog URL ends with "/", add "index.php" before query strings.', 'yesno', 'yes');
		$this->createOption('addblogid_def', 'Add blogid to default blog\'s category URLs.', 'yesno', 'no');
		$this->createOption('addblogid', 'When a blog URL is different from default blog URL, add blogid to its category URLs.', 'yesno', 'yes');
		$this->createOption("mainsep", "Separate character between a category and additional categories", "text", " , ");
		$this->createOption("addsep", "Separate character between additional categories", "text", " , ");
		$this->createOption("subformat", "Display form of a category name when the item belongs to one or more sub categories.", "text", "<%category%> ( <%subcategory%> )");
		$this->createOption("catheader", "[Category list] Header Template. You can use <%blogid%>, <%blogurl%>, <%self%>", "textarea",'<ul class="nobullets">'."\n");
		$this->createOption("catlist", "[Category list] List item Template. You can use <%catname%>, <%catdesc%>, <%catid%>, <%catlink%>, <%catflag%>, <%catamount%>, <%subcategorylist%>", "textarea",'<li<%catflag%>><a href="<%catlink%>"><%catname%></a>(<%catamount%>)<%subcategorylist%></li>'."\n");
		$this->createOption("catfooter", "[Category list] Footer Template. You can use <%blogid%>, <%blogurl%>, <%self%>", "textarea",'</ul>'."\n");
		$this->createOption("catflag", "[Category list] Flag Template", "textarea",' class="current"'."\n");
		$this->createOption("subheader", "[Category list] Sub-Category Header Template", "textarea",'<ul>'."\n");
		$this->createOption("sublist", "[Category list] Sub-Category List item Template. You can use <%subname%>, <%subdesc%>, <%subcatid%>, <%sublink%>, <%subflag%>, <%subamount%>", "textarea",'<li<%subflag%>><a href="<%sublink%>"><%subname%></a>(<%subamount%>)</li>'."\n");
		$this->createOption("subfooter", "[Category list] Sub-Category Footer Template", "textarea",'</ul>'."\n");
		$this->createOption("subflag", "[Category list] Sub-Category Flag Template", "textarea",' class="current"'."\n");
		$this->createOption("replace", '[Category list] a-1: When a category has sub categories, replace "<%amount%>" of category list template to another character.', 'yesno','no');
		$this->createOption("replacechar", '[Category list] a-2: The character to replace.', 'text','+');
		$this->createOption("archeader", "[Archive list] Header Template. You can use <%blogid%>", "textarea",'<ul>'."\n");
		$this->createOption("arclist", "[Archive list] List item Template. You can use <%archivelink%>,<%blogid%>, month/year/day like \"%B, %Y\"", "textarea",'<li><a href="<%archivelink%>">%Y-%m</a></li>'."\n");
		$this->createOption("arcfooter", "[Archive list] Footer Template. You can use <%blogid%>", "textarea",'</ul>'."\n");
		$this->createOption("locale", "[Archive list] Locale", "text",'ja_JP');
		$this->createOption("quickmenu", "Show in quick menu", "yesno", "no");
		$this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");
*/
/*

ALTER TABLE `000_nucleus_plug_multiple_categories_sub` ADD `parentid` INT( 11 ) DEFAULT '0' NOT NULL AFTER `scatid` ,
ADD `ordid` INT( 11 ) DEFAULT '100' NOT NULL AFTER `parentid` ;


*/
		// create the table that will keep track of notifications
		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_multiple_categories'). '(';	
		$query .= ' item_id int(11) NOT NULL,';
		$query .= ' categories varchar(255) not null,';		
		$query .= ' subcategories varchar(255) not null,';		
		$query .= ' PRIMARY KEY  (item_id)';
		$query .= ') TYPE=MyISAM;';
		sql_query($query);

		$check_column = sql_query('SELECT * FROM '. sql_table('plug_multiple_categories'). ' WHERE 1=0');
		for ($i=0; $i<mysql_num_fields($check_column); $i++) {
			if ($meta = mysql_fetch_field($check_column)) {
				$names[] = $meta->name;
			}
		}
		if (!in_array("subcategories",$names)) {
			sql_query ('ALTER TABLE '.sql_table('plug_multiple_categories').' ADD subcategories varchar(255) not null');
			sql_query('ALTER TABLE ' .sql_table('plug_multiple_categories').' MODIFY categories varchar(255) not null');
		}
		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_multiple_categories_sub'). '('
		. 'scatid int(11) not null auto_increment,'
		. 'catid int(11) not null,'
		. 'sname varchar(40) not null,'
		. 'sdesc varchar(200) not null,'
		. ' PRIMARY KEY (scatid)'
		. ') TYPE=MyISAM;';
		sql_query($query);
		
		//<sato(na)0.5.1j>
		//table Upgrade
		if ($this->checkMSCVersion() == 2){
			$q = "
				ALTER TABLE 
					`".sql_table('plug_multiple_categories_sub')."` 
				ADD `parentid` INT( 11 ) DEFAULT   '0' NOT NULL AFTER `scatid` , 
				ADD `ordid`    INT( 11 ) DEFAULT '100' NOT NULL AFTER `parentid`
			";
		} elseif ($this->version == 3){
			$q = "
				ALTER TABLE 
					`".sql_table('plug_multiple_categories_sub')."` 
				ADD `ordid`    INT( 11 ) DEFAULT '100' NOT NULL AFTER `parentid`
			";
		}
		if ($q) sql_query($q);
		//</sato(na)0.5.1j>
	}

	function unInstall() {
		if ($this->getOption('del_uninstall') == "yes") {
			sql_query('DROP TABLE ' .sql_table('plug_multiple_categories'));
			sql_query('DROP TABLE ' .sql_table('plug_multiple_categories_sub'));
		}
	}

	function getTableList() {
		return array(sql_table('plug_multiple_categories'), sql_table('plug_multiple_categories_sub'));
	}

	function hasAdminArea() { return 1; }

	function getEventList() {
		return array('PreSkinParse','PostAddItem','AddItemFormExtras', 'EditItemFormExtras', 'PreUpdateItem', 'PostDeleteItem', 'PostDeleteCategory','QuickMenu');
	}	

	function event_QuickMenu(&$data) {
		// only show when option enabled
		if ($this->getOption('quickmenu') != 'yes') return;
		global $member;
		// only show to admins
		if (!($member->isLoggedIn() && $member->isAdmin())) return;
		array_push(
			$data['options'],
			array(
				'title' => 'Multiple Categories',
				'url' => $this->getAdminURL(),
				'tooltip' => 'Edit sub categories'
			)
		);
	}
	
	function getRequestName() {
		return "subcatid";
	}
	
	function init() {
		$this->setglobal = 0;
		$this->subOrderArray = $this->_setSubOrder();//<sato(na)t1855 />
	}
	
	function event_PreSkinParse($data) {
		global $catid, $subcatid, $CONF;
		
		if ($this->setglobal == 1) return;

		if ($CONF['URLMode'] == 'pathinfo') {
			if (!$subcatid) {
				$sid = 0;
				$pathdata = explode("/",serverVar('PATH_INFO'));
				for ($i=0;$i<sizeof($pathdata);$i++) {
					switch ($pathdata[$i]) {
						case $this->getRequestName():
							$i++;
							if ($i<sizeof($pathdata)) $sid = $pathdata[$i];
							break 2;
					}
				}
				if ($sid) $subcatid = intval($sid);
			}
		} else {
			$subcatid = intRequestVar($this->getRequestName());
		}
		if ($subcatid && !$catid) {
			$catid = intval($this->_getParentCatID($subcatid));//Intval is not needed. ($subcatid) <sato(na)0.5j />
			if (!$catid) {
				$subcatid = null;
				$catid = null;
			}
		} elseif ($subcatid) {
			$pcatid = intval($this->_getParentCatID($subcatid));//Intval is not needed. ($subcatid) <sato(na)0.5j />
			if ($pcatid != $catid) $subcatid = null;
		}
		
		$this->setglobal = 1;
	}
	
//modify start+++++++++
	function checkMSCVersion(){
				$res = sql_query("SHOW FIELDS from ".sql_table('plug_multiple_categories_sub') );
				$fieldnames = array();
				while ($co = mysql_fetch_assoc($res)) {
					$fieldnames[] = $co['Field'];
				}
				if(in_array('ordid',$fieldnames)) return 4;
				if(in_array('parentid',$fieldnames)) return 3;
				return 2;
	}
//modify end+++++++++

	function _getCategories($id){
		$aResult = array();	
		$query = 'SELECT catid, cname as name, cdesc FROM '.sql_table('category').' WHERE cblog=' . intval($id);
		$res = sql_query($query);	
		while ($a = mysql_fetch_assoc($res)){
			array_push($aResult,$a);
		} 
		return $aResult;	
	}

	function _getDefinedScats($id){
		$aResult = array();	
		$query = 'SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid=' . intval($id);
		$res = sql_query($query);	
		while ($a = mysql_fetch_assoc($res)){
			array_push($aResult,$a);
		} 
		return $aResult;
	}
	
	function _getScatIDs($id){
		$aResult = array();	
		$query = 'SELECT scatid FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid=' . intval($id);
		$res = sql_query($query);	
		while ($row = mysql_fetch_row($res)){
			$aResult[] = intval($row[0]); //<sato(na)0.5j />ultrarich
		} 
		return $aResult;
	}
	
	function _getCatNameFromID($id){
		return quickQuery('SELECT cname as result FROM '.sql_table('category').' WHERE catid='.intval($id));
	}

	function _getScatNameFromID($id) {
		return quickQuery('SELECT sname as result FROM '.sql_table('plug_multiple_categories_sub').' WHERE scatid='.intval($id));
	}

	function _getScatDescFromID($id) {
		return quickQuery('SELECT sdesc as result FROM '.sql_table('plug_multiple_categories_sub').' WHERE scatid='.intval($id));
	}

	function _getScatIDFromName($name) {
		return quickQuery('SELECT scatid as result FROM '.sql_table('plug_multiple_categories_sub').' WHERE sname="'.addslashes($name).'"');
	}

	function _getParentCatID($id) {
		return quickQuery('SELECT catid as result FROM '.sql_table('plug_multiple_categories_sub').' WHERE scatid='.intval($id));
	}
	
	function _getScatMap($numarray) {
		$aResult = array();
		$numstr  = implode(",",array_map("intval",$numarray));
		//<sato(na)t1855>
		$numstr = $this->permuteSubcategories($numstr);
		if (!$numstr) $numstr = 0;//<mod by shizuki>
		//$res = sql_query("SELECT catid, scatid, sname FROM ". sql_table("plug_multiple_categories_sub") ." WHERE scatid in (".$numstr.")");
		$sql_str = "SELECT catid, scatid, sname FROM ". sql_table("plug_multiple_categories_sub").
		" WHERE scatid in (".$numstr.") ORDER BY FIND_IN_SET(scatid,'".$numstr."')";
		$res = sql_query($sql_str);
		//</sato(na)t1855>
		while ($o = mysql_fetch_object($res)) {
			if (!isset($aResult[$o->catid])) $aResult[$o->catid] = array();
			$aResult[$o->catid][$o->scatid] = $o->sname;
		}
		return $aResult;
	}

	function _getMultiCategories($itemid){
		$query = "SELECT categories FROM ".sql_table('plug_multiple_categories')." WHERE item_id=".intval($itemid); 
		$result = sql_query($query); 
		if(mysql_num_rows($result)==0) return;
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	
	function _getSubCategories($itemid){
		$query = "SELECT subcategories FROM ".sql_table('plug_multiple_categories')." WHERE item_id=".intval($itemid); 
		$result = sql_query($query); 
		if(mysql_num_rows($result)==0) return;
		$row = mysql_fetch_row($result);
		return $row[0];
	}
	//<sato(na)t1855>
	function _setSubOrder(){
		$subOrderString = substr($this->_getSubOrder(0), 1);
		return explode(",", $subOrderString);
	}
	function _getSubOrder($pid){
		$sql_str  = 'SELECT scatid FROM '.sql_table('plug_multiple_categories_sub').' WHERE parentid='.intval($pid).' ORDER BY ordid'; //<sato(na)0.5j />
		$qid_scat = mysql_query($sql_str);
		if ($qid_scat === FALSE) return ''; //<sato(na)0.403j />
		$scat_str = '';
		while ($row_scat = mysql_fetch_object($qid_scat)) $scat_str .= ',' . intval($row_scat->scatid) . $this->_getSubOrder($row_scat->scatid); //<sato(na)0.5j />
		return $scat_str;
	}
	function permuteSubcategories($subcategories){
		$itemScats = explode(",", $subcategories);
		$retArray  = array_intersect($this->subOrderArray, $itemScats);
		$ret = implode(",", $retArray);
		return $ret;
	}
	//</sato(na)t1855>
	//<sato(na)0.402j>
	function doAction($type) {
		$catid    = intRequestVar('catid');
		$subcatid = intRequestVar('subcatid');
		echo '
function orderKey(key, sequence) {
	var scatDat = new Array();';
		$query = "SELECT scatid, sname, sdesc FROM ".sql_table('plug_multiple_categories_sub')." WHERE parentid=$subcatid AND catid=$catid";
		$res   = sql_query($query);
		$i     = 0;
		while($row = mysql_fetch_array($res)) {
			//<sato(na)0.5j>
			echo 'scatDat['.($i++).'] = new setScatDat('.
			intval($row['scatid']).
			' , "'.
			htmlspecialchars($row['sname'], ENT_QUOTES).
			'", "'.
			htmlspecialchars($row['sdesc'], ENT_QUOTES).
			'");'."\n";
			//</sato(na)0.5j>
		}
		echo '
	scatDat.sort(eval("sort" + key + sequence));
	scatListRefresh(scatDat);
}';
	}
	//</sato(na)0.402j>
	
	function _getItemObject($id) {
		$res = sql_query("SELECT inumber as itemid, icat as catid FROM ".sql_table('item')." WHERE inumber=".intval($id));
		if ($res) {
			return mysql_fetch_object($res);
		}
	}
	
	function event_AddItemFormExtras($data) {
		$aCategories = $this->_getCategories($data['blog']->blogid);
		if(count($aCategories) > 1) {
			$this->showForm($aCategories,$data['itemid']);
		} elseif (count($aCategories) > 0) {
			$this->showSubForm($aCategories,$data['itemid']);
		}
	}

	function event_EditItemFormExtras($data) {
		$aCategories = $this->_getCategories($data['blog']->blogid);
		if(count($aCategories) > 1) {
			$this->showForm($aCategories,$data['itemid']);
		} elseif (count($aCategories) > 0) {
			$this->showSubForm($aCategories,$data['itemid']);
		}
	}
	
	function showSubForm($aCategories, $itemid) {
		$aDefinedScats = $this->_getDefinedScats($aCategories[0]['catid']);
		if (!count($aDefinedScats)) return;

		$itemScats = array();
		if($subcatlist = $this->_getSubCategories($itemid))//Intval is not needed. ($itemid) <sato(na)0.5j />
			$itemScats = explode(",",$subcatlist);
		
		//<sato(na)>$snum = 0;</sato(na)>
		echo '<h3>Multiple Categories</h3>'; 
		echo "<fieldset><legend>Sub Categories</legend>";
		//<sato(na)>
		$sql_str = 'SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid='.intval($aCategories[0]['catid']).' AND  parentid=0'; //<sato(na)0.5j />
		$qid = sql_query($sql_str);
		while ($aSub = mysql_fetch_assoc($qid)) {
			$schecked = (in_array($aSub['scatid'], $itemScats)) ? " checked=checked" : "";
			echo '<input type="checkbox" id="npmc_scat'.$aSub['scatid'].'" name="npmc_scat['.$aSub['scatid'].']"'.$schecked.' value="'.$aSub['scatid'].'" />'; 
			echo '<label for="npmc_scat'.$aSub['scatid'].'">'.htmlspecialchars($aSub['sname'], ENT_QUOTES).'</label><br />'; //<sato(na)0.5j />
			$this->showFormHierarchical($aSub['scatid'], $itemScats); //<sato(na)0.5j />
		}
		//</sato(na)>
		echo "</fieldset>";
	}
	
	function showForm($aCategories,$itemid) {
		$itemcats = array();
		$itemScats = array();
		if($multicatlist = $this->_getMultiCategories($itemid))//Intval is not needed. ($itemid) <sato(na)0.5j />
			$itemcats = explode(",",$multicatlist);
		if($subcatlist = $this->_getSubCategories($itemid))//Intval is not needed. ($itemid) <sato(na)0.5j />
			$itemScats = explode(",",$subcatlist);

		echo '<h3 style="margin-bottom:0px;">Multiple Categories</h3>'; 
		$num = 0;
		//<sato(na)>
		//$snum = 0;
		echo '<div style="height: 300px;overflow: auto;"><table><tbody>';
		//</sato(na)>
		foreach ($aCategories as $aCategory){
			$checked = "";
			if(in_array($aCategory['catid'],$itemcats)) $checked = " checked=checked";
			echo '<tr><td>';
			echo '<input type="checkbox" id="npmc_cat'.$num.'" name="npmc_cat['.$num.']"'.$checked.' value="'.$aCategory['catid'].'" />'; 
			echo '<label for="npmc_cat'.$num.'">'.htmlspecialchars($aCategory['name'], ENT_QUOTES); //<sato(na)0.5j />
			if ($aCategory['cdesc']) echo "(".htmlspecialchars($aCategory['cdesc'], ENT_QUOTES).")"; //<sato(na)0.5j />
			echo '</label>';
			$num ++;
			//<sato(na)>
			$sql_str = 'SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid='.intval($aCategory['catid']).' AND parentid=0'; //<sato(na)0.5j />
			$qid = sql_query($sql_str);
			if (mysql_num_rows($qid)) {
				echo "<fieldset style=\"margin-left:1.5em;border:none\">";
				while ($aSub = mysql_fetch_assoc($qid)) {
					$schecked = (in_array($aSub['scatid'], $itemScats)) ? " checked=checked" : "";
					echo '<input type="checkbox" id="npmc_scat'.$aSub['scatid'].'" name="npmc_scat['.$aSub['scatid'].']"'.$schecked.' value="'.$aSub['scatid'].'" />'; 
					echo '<label for="npmc_scat'.$aSub['scatid'].'">'.htmlspecialchars($aSub['sname'], ENT_QUOTES).'</label><br />'; //<sato(na)0.5j />
					$this->showFormHierarchical($aSub['scatid'], $itemScats); //<sato(na)0.5j />
				}
				echo "</fieldset>";
			}
			//</sato(na)>
			echo "</td></tr>";
		}
		echo "</tbody></table></div>";//<sato(na) />
	}
	//<sato(na)>
	function showFormHierarchical($parentid, $itemScats) {
		$qid = sql_query('SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE parentid='.intval($parentid));
		if (mysql_num_rows($qid)){
			echo "<div style=\"margin-left:3em;border:none\">";
			while ($aSub = mysql_fetch_assoc($qid)) {
				$schecked = (in_array($aSub['scatid'], $itemScats)) ? " checked=checked" : "";
				echo '<input type="checkbox" id="npmc_scat'.$aSub['scatid'].'" name="npmc_scat['.$aSub['scatid'].']"'.$schecked.' value="'.$aSub['scatid'].'" />'; 
				echo '<label for="npmc_scat'.$aSub['scatid'].'">'.htmlspecialchars($aSub['sname'], ENT_QUOTES).'</label><br />'; //<sato(na)0.5j />
				$this->showFormHierarchical($aSub['scatid'], $itemScats); //<sato(na)0.5j />
			}
			echo "</div>";
		}
	}
	//</sato(na)>

	function event_PostAddItem($data) {
		$selected = requestIntArray('npmc_cat');
		$s_selected = requestIntArray('npmc_scat');
		if (count($selected) == 0 && count($s_selected) == 0) return;	
		
		$pcatid = quickQuery("SELECT icat as result FROM ".sql_table('item')." WHERE inumber=".intval($data['itemid']));
		
		$this->updateData($data['itemid'], $pcatid, $selected, $s_selected);
	}

	function event_PreUpdateItem($data) {
		$selected = requestIntArray('npmc_cat');
		$s_selected = requestIntArray('npmc_scat');

		if(($this->_getMultiCategories($data['itemid']) || $this->_getSubCategories($data['itemid'])) && count($selected) == 0 && count($s_selected) == 0 ){
			sql_query('DELETE FROM ' . sql_table('plug_multiple_categories') . ' WHERE item_id=' . intval($data['itemid']));
			return;
		} elseif (count($selected) == 0 && count($s_selected) == 0) {
			return;
		}
		
		$this->updateData($data['itemid'],$data['catid'],$selected,$s_selected);
	}
	
	function updateData($itemid, $pcatid, $selected, $s_selected) {
		$value = "";
		$aMulti = array();
		$aSub = array();
		if (is_array($selected) && count($selected) > 0) {
			$aMulti = $selected;
		}
		
		if (is_array($s_selected) && count($s_selected) > 0) {
			foreach ($s_selected as $v) {
				$v = intval($v);
				$mycatid = $this->_getParentCatID($v);
				if ($mycatid) {
					$aSub[] = $v;
					if (!in_array($mycatid,$aMulti)) {
						$aMulti[] = $mycatid;
					}
				}
			}
		}
		
		if (count($aMulti) > 0) {
			$aMulti = array_map("intval",$aMulti);
			$cat_string = join(",",$aMulti);
			$value .= ', "'.addslashes($cat_string).'"';
		} else {
			$value .= ', ""';
		}

		if (count($aSub) > 0) {
			$scat_string = join(",",$aSub);
			$value .= ', "'.addslashes($scat_string).'"';
		} else {
			$value .= ', ""';
		}
		
		$query = 'REPLACE INTO '.sql_table('plug_multiple_categories').' (item_id,categories,subcategories) VALUES('.intval($itemid).$value.');'; //$value : addslashes
		sql_query($query);
	}

	function event_PostDeleteItem($data){
		$query = 'DELETE FROM ' . sql_table('plug_multiple_categories') . ' WHERE item_id=' . intval($data['itemid']);
		sql_query($query);
	}

	function event_PostDeleteCategory($data) {
		$catid = intval($data['catid']);
		$subcats = $this->_getScatIDs($catid);
		if (count($subcats > 0)) {
			sql_query("DELETE FROM ". sql_table("plug_multiple_categories_sub") ." WHERE catid=$catid");
			global $manager;
			foreach ($subcats as $val) {
				$manager->notify(
								 'PostDeleteSubcat',
								 array(
									   'subcatid' => $val
									  )
								);
			}
		}
		
		$query = "SELECT categories, subcategories, item_id FROM ". sql_table("plug_multiple_categories") ." WHERE categories REGEXP '(^|,)$catid(,|$)'";
		if (count($subcats > 0)) { 
		 $query .= " or subcategories REGEXP '(^|,)(".implode("|",$subcats).")(,|$)'";
		}
		$res = sql_query($query);
		$del = array();
		$up = array();
		
		while ($o = mysql_fetch_object($res)) {
			$o->categories = preg_replace("/^(?:(.*),)?$catid(?:,(.*))?$/","$1,$2",$o->categories);
			$o->subcategories = preg_replace("/^(?:(.*),)?$catid(?:,(.*))?$/","$1,$2",$o->subcategories);
			if ((!$o->categories || $o->categories == ',') && (!$o->subcategories || $o->subcategories == ',')) {
				$del[] = intval($o->item_id); //<sato(na)0.5j />ultrarich
			} else {
				$o->categories = preg_replace("/(^,+|(?<=,),+|,+$)/","",$o->categories);
				$o->subcategories = preg_replace("/(^,+|(?<=,),+|,+$)/","",$o->subcategories);
				$up[] = "UPDATE ". sql_table("plug_multiple_categories") ." SET categories='".addslashes($o->categories).
					"', subcategories='".addslashes($o->subcategories)."' WHERE item_id=".intval($o->item_id); //<sato(na)0.5j />ultrarich
			}
		}
		
		if (count($del) > 0) {
			sql_query("DELETE FROM ". sql_table("plug_multiple_categories") . " WHERE item_id in (".implode(",",$del).")");
		}
		if (count($up) > 0) {
			foreach ($up as $v) {
				sql_query($v);
			}
		}
	}

	function doSKinVar(){
		global $blog, $catid, $CONF, $manager, $itemid, $subcatid, $startpos, $archive;
		
		$params = func_get_args();
		// item skin
		if ($params[0] == 'item' && $params[1] != "1") {
			if ($itemid) $this->_parseItem($params[1], intval($itemid));//<sato(na)0.5j />
			return;
		}

		if (intval($params[1]) == 1) {
			switch ($params[2]) {
				case 'id':
					if (!$subcatid || !$catid) return;
					echo intval($subcatid);//<sato(na)0.5j />
					return;
					break;
				case 'desc':
					if (!$subcatid || !$catid) return;
					echo htmlspecialchars($this->_getScatDescFromID($subcatid), ENT_QUOTES);//Intval is not needed. ($subcatid) <sato(na)0.5j />
					return;
					break;
				case 'name':
					if (!$subcatid || !$catid) return;
					echo htmlspecialchars($this->_getScatNameFromID($subcatid), ENT_QUOTES);//Intval is not needed. ($subcatid) <sato(na)0.5j />
					return;
					break;
				case 'url':
					if (!$subcatid || !$catid) return;
					if ($blog) {
						$b =& $blog;
					} else {
						$b =& $manager->getBlog($CONF['DefaultBlog']);
					}
					$this->_setCommonData($b->getID());
					$sparams = array_merge($this->param, array($this->getRequestName() => intval($subcatid)));//<sato(na)0.5j />
					$url = createCategoryLink(intval($catid), $sparams);//<sato(na)0.5j />
					if ($CONF['URLMode'] != 'pathinfo') {
						list(,$temp_param) = explode("?",$url);
						$url = $this->url. "?" . $temp_param;
					}
					$url = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $url); //<sato(na)0.5j />
					echo $url; //$sparams escape OK <sato(na)0.5j />
					return;
					break;
				case 'link':
					if ($params[0] != 'item') return;
					$item = $this->_getItemObject(intval($itemid));//<sato(na)0.5j />
					if ($item) {
						$this->doTemplateVar(&$item);
					}
					return;
					break;
				case 'archivelink':
					if ($blog) {
						$b =& $blog;
					} else {
						$b =& $manager->getBlog($CONF['DefaultBlog']);
					}
					$bid = $b->getID();
					$this->_setCommonData($bid);
					$cur_params = array();
					if ($catid) $cur_params['catid'] = intval($catid);//<sato(na)0.5j />
					if ($subcatid) {
						$rname = $this->getRequestName();
						$cur_params[$rname] = intval($subcatid);//<sato(na)0.5j />
					}
					$url = createArchiveListLink($bid, $cur_params);
					if ($CONF['URLMode'] != 'pathinfo') {
						list(,$temp_param) = explode("?",$url);
						$url = $this->url. "?" . $temp_param;
					}
					$url = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $url); //<sato(na)0.5j />
					echo $url; //$cur_params escape OK <sato(na)0.5j />
					return;
					break;
				case 'categorylist':
					$this->showCategoryList();
					return;
					break;
				case 'archivelist':
					$arcmode = 'month';
					if (isset($params[3]) && $params[3] == 'day') {
						$arcmode = 'day';
					}
					$limit = 0;
					if (isset($params[4]) && intval($params[4])) {
						$arclimit = intval($params[4]);
					}
					$this->showArchiveList($arcmode,$arclimit);
					return;
					break;
			}
		}
		
		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		
		$mycatid    = ($catid)    ? intval($catid)    : 0;//<sato(na)0.5j />
		$mysubcatid = ($subcatid) ? intval($subcatid) : 0;//<sato(na)0.5j />
		$templateName = $params[1];
		$amountEntries = 0;
		$offset = 0;
		$startpos = intval($startpos);//<sato(na)0.5j />
		if (isset($params[2])) {
			list($amountEntries, $offset) = sscanf($params[2], '%d(%d)');
			if ($offset) {
				$startpos += $offset;
			}
		}
		if (isset($params[3]) && $params[3])
			$mycatid = getCatIDFromName($params[3]);
		if (isset($params[4]) && $params[4])
			$mysubcatid = $this->_getScatIDFromName($params[4]);
		
		if (!$templateName) $templateName = 'grey/short';
		if (!$amountEntries) $amountEntries = 10;
		
		$mycatid = intval($mycatid);
		$mysubcatid = intval($mysubcatid);
		if (!$mycatid && $mysubcatid) $mysubcatid = 0;
		
		$query =  'SELECT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, UNIX_TIMESTAMP(i.itime) as timestamp, i.itime, i.imore as more, m.mnumber as authorid, c.cname as category, i.icat as catid, i.iclosed as closed' ;
		//<sato(na)0.5j>
		//$query .= ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c';
		$query .= ' FROM '.sql_table('category').' as c, '.sql_table('member').' as m, '.sql_table('item').' as i';
		//</sato(na)0.5j>
		
		if ($mycatid) {
			$query .= ' LEFT JOIN '.sql_table('plug_multiple_categories').' as p ON i.inumber=p.item_id';
		}
		
		$query .=  ' WHERE i.iauthor=m.mnumber' 
			 . ' and i.iblog='.intval($b->getID()) //<sato(na)0.5j />
			 . ' and i.icat=c.catid' 
			 . ' and i.idraft=0';
		if ($params[0] == 'archive' && $archive) {
			sscanf($archive,'%d-%d-%d',$y,$m,$d);
			if ($d) {
				$timestamp_start = mktime(0,0,0,$m,$d,$y);
				$timestamp_end = mktime(0,0,0,$m,$d+1,$y);
			} else {
				$timestamp_start = mktime(0,0,0,$m,1,$y);
				$timestamp_end = mktime(0,0,0,$m+1,1,$y);
			}
			
			$query .= ' and i.itime>=' . mysqldate($timestamp_start)
			       . ' and i.itime<' . mysqldate($timestamp_end);
			
		} else {
			$query .= ' and i.itime<=' . mysqldate($b->getCorrectTime());
		}
		
		if ($mycatid) {
			$query .= ' and ((i.inumber=p.item_id and (p.categories REGEXP "(^|,)'.$mycatid.'(,|$)" or i.icat='.$mycatid.')) or (i.icat='.$mycatid.' and p.item_id IS NULL))';
		}
		if ($mysubcatid) {
			$query .= ' and p.subcategories REGEXP "(^|,)'.$mysubcatid.'(,|$)"';
		}
		
		$query .= ' ORDER BY i.itime DESC'; 

		$query .= ' LIMIT ' . intval($startpos).',' . intval($amountEntries);
		
		$b->showUsingQuery($templateName, $query, 0, 1, 1); 
	}


	function doTemplateCommentsVar(&$item, &$comment, $what='') {
		if ($what == 'itemlink') {
			$this->doTemplateVar(&$item, $what);
		}
	}

	function doTemplateVar(&$item, $what='') {
		global $CONF, $catid, $subcatid;
		
		$bid = getBlogIDFromItemID($item->itemid);
		if (!isset($this->defurl)) $this->_setCommonData($bid);
		if ($bid != $this->bid) $this->_setBlogData($bid);
		
		if ($what == 'itemlink') {
			$sparams = array();
			if ($catid) {
				$sparams['catid'] = intval($catid);//<sato(na)0.5j />
				if ($subcatid) {
					$sparams[$this->getRequestName()] = intval($subcatid);//<sato(na)0.5j />
				}
			}
			$url = createItemLink($item->itemid, $sparams);
			if ($CONF['URLMode'] != 'pathinfo') {
				list(,$temp_param) = explode("?",$url);
				$url = $this->url. "?" . $temp_param;
			}
			$url = preg_replace(array("/</", "/>/"), array("&lt;", "&gt;"), $url); //<sato(na)0.5j />
			echo $url; //$cur_params escape OK <sato(na)0.5j />
			return;
		}
		
		$url = createCategoryLink($item->catid, $this->param);
		if ($CONF['URLMode'] != 'pathinfo') {
			list(,$temp_param) = explode("?",$url);
			$url = $this->url. "?" . $temp_param;
		}
		$mcat_string = '<a href="'.$this->cnvHtmlUrlAttribute($url).'">'.htmlspecialchars($this->_getCatNameFromID($item->catid), ENT_QUOTES).'</a>'; //<sato(na)0.5j />
		
		$itemScats = array();
		if ($itemscatstr = $this->_getSubCategories($item->itemid)) {
			$itemScats = explode(",",$itemscatstr);
			$scatMaps = $this->_getScatMap($itemScats);
		}
		if ($itemscatstr && array_key_exists($item->catid,$scatMaps)) {
			$extra_scat_string = array();
			foreach ($scatMaps[$item->catid] as $id => $name) {
				if ($CONF['URLMode'] == 'pathinfo') {
					$sparams = array_merge($this->param, array($this->getRequestName() => $id));
					$surl = createCategoryLink($item->catid, $sparams);
				} else {
					$surl = addLinkParams($url,array($this->getRequestName() => $id));
				}
				$extra_scat_string[] = '<a href="'.$this->cnvHtmlUrlAttribute($surl).'">'.htmlspecialchars($name, ENT_QUOTES).'</a>'; //<sato(na)0.5j />
			}
			$scat_string = implode($this->ssep,$extra_scat_string);
			$cat_string = str_replace(array("<%category%>","<%subcategory%>"), array($mcat_string,$scat_string), $this->sform);
		} else {
			$cat_string = $mcat_string;
		}
		
		if ($multicatstr = $this->_getMultiCategories($item->itemid)) {
			$itemcats = explode(",",$multicatstr);
			$extra_cat_string = array();
			foreach ($itemcats as $icat){
				if ($icat != $item->catid) {
					$url = createCategoryLink($icat,$this->param);
					if ($CONF['URLMode'] != 'pathinfo') {
						list(,$temp_param) = explode("?",$url);
						$url = $this->url. "?" . $temp_param;
					}
					$mcat_string = '<a href="'.$this->cnvHtmlUrlAttribute($url).'">'.htmlspecialchars($this->_getCatNameFromID($icat), ENT_QUOTES).'</a>'; //<sato(na)0.5j />
					
					if (count($itemScats) > 0 && array_key_exists($icat,$scatMaps)) {
						$extra_scat_string = array();
						foreach ($scatMaps[$icat] as $id => $name) {
							if ($CONF['URLMode'] == 'pathinfo') {
								$sparams = array_merge($this->param, array($this->getRequestName() => $id));
								$surl = createCategoryLink($icat,$sparams);
							} else {
								$surl = addLinkParams($url,array($this->getRequestName() => $id));
							}
							$extra_scat_string[] = '<a href="'.$this->cnvHtmlUrlAttribute($surl).'">'.htmlspecialchars($name, ENT_QUOTES).'</a>'; //<sato(na)0.5j />
						}
						$scat_string = implode($this->ssep,$extra_scat_string);
						$extra_cat_string[] = str_replace(array("<%category%>","<%subcategory%>"), array($mcat_string,$scat_string), $this->sform);
					} else {
						$extra_cat_string[] = $mcat_string;
					}
				}
			}
			if (count($extra_cat_string) > 0) {
				$cat_string .= $this->msep . implode($this->ssep,$extra_cat_string);
			}
		}
		echo $cat_string;//$mcat_string, $scat_string escape OK <sato(na)0.5j />
	}
		//<sato(na)0.5j>
	function cnvHtmlUrlAttribute($forHtmlAtt__str)
	{
		//onEvent
		$forHtmlAtt__str = preg_replace('/[\'"]/', '', $forHtmlAtt__str);
		
		//href="javascript:"
		$forHtmlAtt__str = preg_replace('/javascript/i', '', preg_replace('/[\x00-\x20\x22\x27]/', '', $forHtmlAtt__str));
		
		return $forHtmlAtt__str;
	}
		//</sato(na)0.5j>
	
	function _setCommonData($bid) {
		global $CONF;
		$this->msep = $this->getOption('mainsep');
		$this->ssep = $this->getOption('addsep');
		$this->sform = $this->getOption('subformat');
		$this->addindex = ($this->getOption('addindex') == 'yes');
		$this->addbiddef = ($this->getOption('addblogid_def') == 'yes');
		$this->addbid = ($this->getOption('addblogid') == 'yes');
		$this->defurl = quickQuery("SELECT burl as result from ".sql_table('blog')." WHERE bnumber=".addslashes($CONF['DefaultBlog'])); //<sato(na)0.5j />
		if (!$this->defurl) $this->defurl = $CONF['Self'];
		$this->_setBlogData($bid);
	}
	
	function _setBlogData($bid) {
		global $CONF;
		$this->param = array();
		$this->bid = intval($bid);
		if ($bid != $CONF['DefaultBlog']) {
			$this->url = quickQuery("SELECT burl as result from ".sql_table('blog')." WHERE bnumber=".$this->bid);
			if (!$this->url) $this->url = $this->defurl;
		} else {
			$this->url = $this->defurl;
		}
		if ($CONF['URLMode'] == 'normal' && substr($this->url,-1) == "/" && $this->addindex) {
			$this->url .= "index.php";
		}
		if ($this->bid == $CONF['DefaultBlog'] && $this->addbiddef) {
			$this->param['blogid'] = $this->bid; //$this->bid intval OK
		} elseif ($this->bid != $CONF['DefaultBlog'] && ($this->url == $this->defurl || $this->addbid)){
			$this->param['blogid'] = $this->bid; //$this->bid intval OK
		}
	}
	
	function _parseItem($template, $itemid) {
		global $manager;
		
		$b =& $manager->getBlog(getBlogIDFromItemID($itemid));//Intval is not needed. ($itemid) <sato(na)0.5j />
		
		$query = 'SELECT i.inumber as itemid, i.ititle as title, i.ibody as body, m.mname as author, m.mrealname as authorname, i.itime, i.imore as more, m.mnumber as authorid, m.memail as authormail, m.murl as authorurl, c.cname as category, i.icat as catid, i.iclosed as closed';
		
		//<sato(na)0.5j>
		//$query .= ' FROM '.sql_table('item').' as i, '.sql_table('member').' as m, '.sql_table('category').' as c'
		$query .= ' FROM '.sql_table('category').' as c, '.sql_table('member').' as m, '.sql_table('item').' as i'
		//</sato(na)0.5j>
		       . ' WHERE i.iblog='.intval($b->getID()) //<sato(na)0.5j />
		       . ' and i.iauthor=m.mnumber'
		       . ' and i.icat=c.catid'
		       . ' and i.idraft=0'	// exclude drafts
					// don't show future items
		       . ' and i.itime<=' . mysqldate($b->getCorrectTime())
		       . ' and i.inumber='.intval($itemid)
		       . ' ORDER BY i.itime DESC LIMIT 0,1';
		 
		$b->showUsingQuery($template, $query, 0, 0, 0); 
		
	}
	
	function showCategoryList() {
		global $CONF, $manager, $blog, $catid, $subcatid;
		global $archive, $archivelist;
		
		//<sato(na)0.5j>
		if ($archive) {
			sscanf ($archive,'%d-%d-%d', $y, $m, $d);
			if ($d) {
				$archive = sprintf ('%04d-%02d-%02d', $y, $m, $d);
			} else {
				$archive = sprintf ('%4d-%02d', $y, $m);
			}
		}
		// check archivelist
		if (! is_numeric($archivelist)) $archivelist = getBlogIDFromName($archivelist);
		//</sato(na)0.5j>
		
		if ($blog) {
			 $b =& $blog;
		} else {
			 $b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		$blogid = $b->getID();
		$blogid = (is_numeric($blogid)) ? intval($blogid) : getBlogIDFromName($blogid); //<sato(na)0.5j />
		
		if (!isset($this->defurl)) $this->_setCommonData($blogid);
		
		$linkparams = array();
		if ($archive) {
			$blogurl = createArchiveLink($blogid, $archive, '');
			if ($CONF['URLMode'] != 'pathinfo') {
				list(,$temp_param) = explode("?",$url);
				$blogurl = $this->url. "?" . $temp_param;
			}
			$linkparams['blogid'] = $blogid;
			$linkparams['archive'] = $archive;
		} else if ($archivelist) {
			$blogurl = createArchiveListLink($blogid, '');
			if ($CONF['URLMode'] != 'pathinfo') {
				list(,$temp_param) = explode("?",$blogurl);
				$blogurl = $this->url. "?" . $temp_param;
			}
			$linkparams['archivelist'] = $archivelist;
		} else {
			$blogurl = $this->url;
			$linkparams = $this->param;
		} 
		

		echo TEMPLATE::fill($this->getOption('catheader'),
							array(
								'blogid' => $blogid,
								'blogurl' => $blogurl,
								'self' => $CONF['Self']
							));
		/* begin modification by kat */
		$items=array();
		$catdata=array();
		$scatdata=array();
		$query = 'SELECT inumber,icat FROM '.sql_table('item').
			' WHERE iblog='.(int)$blogid;
		$res = sql_query($query);
		while ($row=mysql_fetch_row($res)) {
			$items[$row[0]]=true;
			$catdata[$row[1]][$row[0]]=true;
			
		}
		$query = 'SELECT item_id, categories, subcategories FROM '.sql_table('plug_multiple_categories');
		$res = sql_query($query);
		while ($row=mysql_fetch_row($res)) {
			if (!$items[$row[0]]) continue;
			foreach(explode(',',$row[1]) as $cat) if ($cat) $catdata[$cat][$row[0]]=true;
			foreach(explode(',',$row[2]) as $scat) if ($scat) $scatdata[$scat][$row[0]]=true;
		}
		/* end modification by kat */

		$query = 'SELECT c.catid, c.cdesc as catdesc, c.cname as catname FROM '.sql_table('category').
			' as c WHERE c.cblog=' . intval($blogid) . ' GROUP BY c.cname ORDER BY c.cname ASC'; //<sato(na)0.5j />
		$res = sql_query($query);

		$tp = array();
		$tp['catlist'] = $this->getOption('catlist');
		$tp['subheader'] = $this->getOption('subheader');
		$tp['sublist'] = $this->getOption('sublist');
		$tp['subfooter'] = $this->getOption('subfooter');
		$replace = ($this->getOption('replace') == 'yes');
		if ($replace) {
			$rchar = $this->getOption('replacechar');
		}
		
		while ($data = mysql_fetch_assoc($res)) {
			$data['catid'] = intval($data['catid']); //<sato(na)0.5j />ultrarich
			$data['blogid'] = $blogid;	
			$data['blogurl'] = $blogurl;
			$data['catlink'] = createCategoryLink($data['catid'], $linkparams);
			if ($CONF['URLMode'] != 'pathinfo') {
				list(,$temp_param) = explode("?",$data['catlink']);
				$data['catlink'] = $this->url. "?" . $temp_param;
			}
			$data['self'] = $CONF['Self'];
			if ($data['catid'] == intval($catid)) { //<sato(na)0.5j />
				$data['catflag'] = $this->getOption('catflag');
			}
			/* begin modification by kat */
			/*
			$cq = 'SELECT count(*) as result FROM '.sql_table('item').' as i';
			$cq .= ' LEFT JOIN '.sql_table('plug_multiple_categories').' as p ON  i.inumber=p.item_id';
			$cq .= ' WHERE ((i.inumber=p.item_id and (p.categories REGEXP "(^|,)'.$data['catid'].'(,|$)" or i.icat='.$data['catid'].')) or (p.item_id IS NULL and i.icat='.$data['catid'].'))';
			$cq .= ' and i.itime<=' . mysqldate($b->getCorrectTime()) . ' and i.idraft=0';
			
			$data['catamount'] = quickQuery($cq);
			*/
			$data['catamount']=count($catdata[$data['catid']]);
			/* end modification by kat */
			if (intval($data['catamount']) < 1) {
				continue;
			}
			
			$query = 'SELECT scatid as subcatid, sname as subname, sdesc as subdesc FROM '.sql_table('plug_multiple_categories_sub').' WHERE catid='.$data['catid']. ' ORDER BY sname ASC';
			$sres = sql_query($query);
			if (mysql_num_rows($sres) > 0) {
				$subliststr = "";
				
				while ($sdata = mysql_fetch_assoc($sres)) {
					$sdata['subcatid'] = intval($sdata['subcatid']); //<sato(na)0.5j />ultrarich
					/* begin modification by kat */
					/*
					$ares = sql_query(
						'SELECT count(i.inumber) FROM '
						. sql_table('item').' as i, '
						. sql_table('plug_multiple_categories').' as p'
						. ' WHERE i.idraft=0 and i.itime<='.mysqldate($b->getCorrectTime())
						. ' and i.inumber=p.item_id'
						. ' and p.subcategories REGEXP "(^|,)'.$sdata['subcatid'].'(,|$)"'
					);
					if ($ares && $row = mysql_fetch_row($ares)) {
					*/
					if ($row[0]=count($scatdata[$sdata['subcatid']])) {
					/* end modification by kat */
						$sdata['subamount'] = $row[0];
						if ($sdata['subamount'] > 0) {
							if ($CONF['URLMode'] == 'pathinfo') {
								$sparams = array_merge($linkparams, array($this->getRequestName() => $sdata['subcatid']));
								$sdata['sublink'] = createCategoryLink($data['catid'],$sparams);
							} else {
								$sdata['sublink'] = addLinkParams($data['catlink'], array($this->getRequestName() => $sdata['subcatid']));
							}
							if ($sdata['subcatid'] == $subcatid) {
								$sdata['subflag']= $this->getOption('subflag');
							}
							$subliststr .= TEMPLATE::fill($tp['sublist'],$sdata);
						}
					}
				}
				if ($subliststr) {
					$data['subcategorylist'] = $tp['subheader'];
					$data['subcategorylist'] .= $subliststr;
					$data['subcategorylist'] .= $tp['subfooter'];
					if ($replace) {
						$data['amount'] = $rchar;
					}
				}
			}
			mysql_free_result($sres);

			echo TEMPLATE::fill($tp['catlist'],$data);

		}
		
		mysql_free_result($res);

		echo TEMPLATE::fill($this->getOption('catfooter'),
							array(
								'blogid' => $blogid,
								'blogurl' => $blogurl,
								'self' => $CONF['Self']
							));
	}
	
	function showArchiveList($mode = 'month', $limit = 0) {
		global $CONF, $manager, $blog, $catid, $subcatid;

		if ($blog) {
			 $b =& $blog;
		} else {
			 $b =& $manager->getBlog($CONF['DefaultBlog']);
		}
		
		if ($catid) $linkparams = array('catid' => intval($catid)); //<sato(na)0.5j />
		if ($subcatid) $linkparams['subcatid'] = intval($subcatid); //<sato(na)0.5j />
		if ($lc = $this->getOption('locale')) {
			setlocale(LC_TIME,$lc);
		}
		
		$template['header'] = $this->getOption('archeader');
		$template['list'] = $this->getOption('arclist');
		$template['footer'] = $this->getOption('arcfooter');

		echo TEMPLATE::fill($template['header'],array('blogid'=>$b->getID()));

		$query = 'SELECT i.itime, SUBSTRING(i.itime,1,4) AS Year, SUBSTRING(i.itime,6,2) AS Month, SUBSTRING(i.itime,9,2) as Day FROM '.sql_table('item').' as i';
		if ($catid) {
			$query .= ' LEFT JOIN '.sql_table('plug_multiple_categories').' as p ON i.inumber=p.item_id';
		}
		$query .= ' WHERE i.iblog=' . intval($b->getID()) //<sato(na)0.5j />
		. ' and i.itime <=' . mysqldate($b->getCorrectTime())	// don't show future items!
		. ' and i.idraft=0'; // don't show draft items
		
		if ($catid) {
			$query .= ' and ((i.inumber=p.item_id and (p.categories REGEXP "(^|,)'.intval($catid).'(,|$)" or i.icat='.intval($catid).')) or (i.icat='.intval($catid).' and p.item_id IS NULL))';
		}
		if ($subcatid) {
			$query .= ' and p.subcategories REGEXP "(^|,)'.intval($subcatid).'(,|$)"';
		}
		
		$query .= ' GROUP BY Year, Month';
		if ($mode == 'day')
			$query .= ', Day';
		
		$query .= ' ORDER BY i.itime DESC';
		
		if ($limit > 0) 
			$query .= ' LIMIT ' . intval($limit);
		
		$res = sql_query($query);

		while ($current = mysql_fetch_object($res)) {
			$current->itime = strtotime($current->itime);	// string time -> unix timestamp
			$data = array('blogid'=>$b->getID());
		
			if ($mode == 'day') {
				$archivedate = date('Y-m-d',$current->itime);
				$data['day'] = date('d',$current->itime);
			} else {
				$archivedate = date('Y-m',$current->itime);
			}
			$data['month'] = date('m',$current->itime);
			$data['year'] = date('Y',$current->itime);
			$data['archivelink'] = createArchiveLink($b->getID(),$archivedate,$linkparams);
			if ($CONF['URLMode'] != 'pathinfo') {
				list(,$temp_param) = explode("?",$data['archivelink']);
				$data['archivelink'] = $this->url. "?" . $temp_param;
			}

			$temp = TEMPLATE::fill($template['list'], $data);
			echo strftime($temp,$current->itime);
		}
		mysql_free_result($res);

		echo TEMPLATE::fill($template['footer'],array('blogid'=>$b->getID()));
	}
	
	//<sato(na)0.5.1j>
	function doIf($name='', $value = '')
	{
		global $subcatid;

		if ($name == 'subcategory' || ($value == ''))
			return $this->isValidSubCategory($subcatid);

		if ($name == 'subcatname') {
			//Even as for "subcategory" with same "parent", the name is not unique either.
			$scatname = _getScatNameFromID($subcatid);
			if ($value == $scatname)
				return $this->isValidSubCategory($subcatid);
		}

		if (($name == 'subcatid') && ($value == $subcatid))
			return $this->isValidSubCategory($subcatid);

		return false;
	}
	function isValidSubCategory($subcatid) {
//		global $blog;
		global $manager;
//		$catid = quickQuery('SELECT catid AS result FROM '.sql_table('plug_multiple_categories_sub').' WHERE scatid=' . intval($subcatid));
// <mod by shizuki>
		$catid = $this->_getParentCatID($subcatid);
		$bid   = getBlogIDFromCatID($catid);
		$b     = $manager->getBlog($bid);
//		return $blog->isValidCategory($catid);
		return $b->isValidCategory($catid);
// </ mod by shizuki>
	}
	//</sato(na)0.5.1j>

}
?>
