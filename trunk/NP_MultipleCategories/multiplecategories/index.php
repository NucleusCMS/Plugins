<?php
/**
  * NP_MultipleCategories Admin Page Script 
  *     Taka ( http://reverb.jp/vivian/) 2004-12-01
  *   + nakahara21 ( http://nakahara21.com/) 2005-07-20
  *   + sato(na) ( http://wa.otesei.com/) 2006-05-01
  */

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';

	include($strRel . 'config.php');
	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');

	include($DIR_LIBS . 'PLUGINADMIN.php');

	// create the admin area page
	$oPluginAdmin = new PluginAdmin('MultipleCategories');
	
// ------------------------------------------------------------------

class NpMCategories_ADMIN {

	function NpMCategories_ADMIN() {
		global $oPluginAdmin;
		
		$this->plug =& $oPluginAdmin->plugin;
		$this->plugname = $this->plug->getName();
		$this->url = $this->plug->getAdminURL();
		$this->table = sql_table('plug_multiple_categories_sub');

//modify start+++++++++
		$this->version = $this->plug->checkMSCVersion();

		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->plug->getDirectory().'language/'.$language.'.php')) 
			include_once($this->plug->getDirectory().'language/'.$language.'.php'); 
		else 
			include_once($this->plug->getDirectory().'language/'.'english.php');
//modify end+++++++++

	}

//-------------------

	function action_overview($msg='') {
		global $member, $oPluginAdmin;
		global $manager; //<sato(na)0.5j />
		
		$member->isAdmin() or $this->disallow();

		$oPluginAdmin->start();
		
		echo '<p><a href="index.php?action=pluginlist">('._PLUGS_BACK.')</a></p>';
		echo '<h2>' .$this->plugname. '</h2>'."\n";
		if ($msg) echo "<p>"._MESSAGE.": $msg</p>";
		echo '<p>[<a href="index.php?action=pluginoptions&amp;plugid='.$this->plug->getID().'">'._MC_EDIT_PLUGIN_OPTIONS.'</a>]</p>';
		
//modify start+++++++++
		if($this->version == 2){
			echo '<blockquote style="color: red;border:1px solid red;padding:0.5em;"><b>Upgarde Information:</b><br />';
			echo _MC_SCAT_TABLE_UPDATE_INFO;
?>
			<form method="post" action="<?php echo $this->url ?>index.php"><div>
				<input type="hidden" name="action" value="tableUpgrade" />
<?php
	//<sato(na)0.5j>
	$manager->addTicketHidden();
	//</sato(na)0.5j>
?>
				<input type="submit" tabindex="10" value="upgrade table" />
			</div></form>
<?php
			echo '</blockquote>';
		}
		
		$res = sql_query('SELECT bnumber, bname FROM '.sql_table('blog'));
		while ($o = mysql_fetch_object($res)) {
			echo '<h3 style="padding-left: 0px">' . htmlspecialchars($o->bname) . '</h3>';
?>
<table>
	<thead>
		<tr><th><?php echo _LISTS_NAME ?></th><th><?php echo _LISTS_DESC ?></th><th><?php echo _MC_SUB_CATEGORIES ?></th><th><?php echo _LISTS_ACTIONS ?></th></tr>
	</thead>
	<tbody>
<?php
			$cats = $this->plug->_getCategories($o->bnumber);
			foreach ($cats as $cat) {
				$snum = quickQuery("SELECT count(*) as result FROM ".$this->table." WHERE catid=".intval($cat['catid']));//<sato(na)0.5j />
				$snum = intval($snum);
?>
		<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>
			<td>
				<?php echo htmlspecialchars($cat['name']) ?></td>
			<td><?php echo htmlspecialchars($cat['cdesc']) ?></td>
			<td><?php echo $snum ?></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatoverview&amp;catid=<?php echo intval($cat['catid']) ?>" tabindex="50"><?php echo _MC_EDIT_SUB_CATEGORIES ?></a></td>
		</tr>
<?php
			}
?>
	</tbody>
</table>
<?php
		}
		
		$oPluginAdmin->end();
	
	}

//-----

	function action_scatoverview($msg = '') {
		global $member, $oPluginAdmin;
		global $manager; //<sato(na)0.5j />
		
		$member->isAdmin() or $this->disallow();
		
		$catid = intRequestVar('catid');
		$catname = $this->plug->_getCatNameFromID($catid);
		
		$oPluginAdmin->start();

?>
<p><a href="<?php echo $this->url ?>index.php?action=overview">(Go Back)</a></p>

<h2><?php 
		echo _MC_EDIT_SUB_CATEGORIES_OF." '".htmlspecialchars($catname)."'</h2>\n";

		if ($msg) echo "<p>"._MESSAGE.": $msg</p>";

		$defines = $this->plug->_getDefinedScats($catid);
		if (count($defines) > 0) {

//modify start+++++++++
			if($this->version > 2){
				echo $this->listupSubcategories($catid, $subcatid);
				echo $this->showOrderMenu($catid, $subcatid);
			}else{
//modify end+++++++++

?>

	<table>
	<thead>
		<tr><th><?php echo _LISTS_NAME ?></th><th><?php echo _LISTS_DESC ?></th><th colspan='2'><?php echo _LISTS_ACTIONS ?></th></tr>
	</thead>
	<tbody>
<?php
			foreach ($defines as $scat) {
?>
		<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>
			<td><?php echo htmlspecialchars($scat['sname']) ?></td>
			<td><?php echo htmlspecialchars($scat['sdesc']) ?></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatedit&amp;catid=<?php echo $catid ?>&amp;scatid=<?php echo $scat['scatid'] ?>" tabindex="50"><?php echo _LISTS_EDIT ?></a></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatdelete&amp;catid=<?php echo $catid ?>&amp;scatid=<?php echo $scat['scatid'] ?>" tabindex="50"><?php echo _LISTS_DELETE ?></a></td>
		</tr>
<?php
			}
?>
	</tbody>
	</table>
<?php
//modify start+++++++++
			}
//modify end+++++++++
		} //end of if(count($defines) > 0)
		
		echo "\n\n".'<h3>'._MC_CREATE_NEW_SUB_CATEGORY.'</h3>'."\n\n";
		
?>
	<form method="post" action="<?php echo $this->url ?>index.php"><div>
	
		<input name="action" value="scatnew" type="hidden" />
<?php
	//<sato(na)0.5j>
	$manager->addTicketHidden();
	//</sato(na)0.5j>
?>
		<input name="catid" value="<?php echo $catid ?>" type="hidden" />
		<table><tr>
			<td><?php echo _MC_SCAT_NAME ?></td>
			<td><input name="sname" tabindex="10010" maxlength="20" size="20" /></td>
		</tr><tr>
			<td><?php echo _MC_SCAT_DESC ?></td>
			<td><input name="sdesc" tabindex="10020" size="60" maxlength="200" /></td>
		</tr><tr>
<?php
			if($this->version > 2){
?>
			<td><?php echo _MC_SCAT_PARENT_NAME ?></td>
			<td>
<?php
				$subcategoryList = $this->getCategoryList($catid);
				echo $this->printCategoryList($catid, $subcategoryList, 1, 0);
?>
	</td>
		</tr><tr>
<?php
			}
?>
			<td><?php echo _MC_SCAT_CREATE ?></td>
			<td><input type="submit" tabindex="10030" value="<?php echo _MC_SCAT_CREATE ?>" onclick="return checkSubmit();" /></td>
		</tr></table>
		
	</div></form>
<?php
		
		$oPluginAdmin->end();
	
	}
	
	function action_scatedit($msg = '') {//-----
		global $member, $oPluginAdmin;
		global $manager; //<sato(na)0.5j />
		
		$member->isAdmin() or $this->disallow();

		$scatid = intRequestVar('scatid');
		$catid = intRequestVar('catid');
		
		$res = sql_query("SELECT * FROM ".$this->table." WHERE scatid=$scatid and catid=$catid");
		if ($o = mysql_fetch_object($res)) {

			$oPluginAdmin->start();

?>
<p><a href="<?php echo $this->url ?>index.php?action=scatoverview&amp;catid=<?php echo $catid ?>">(Go Back to OVERVIEW)</a></p>

<h2><?php 
			echo _MC_SCAT_EDIT;
			echo  " '".htmlspecialchars($o->sname)."'</h2>\n";

			if ($msg) echo "<p>"._MESSAGE.": $msg</p>";

?>
<script language=javascript src=<?php echo $this->url ?>orderlist.js></script>
<form method="post" action="<?php echo $this->url ?>index.php?action=scatedit&catid=<?php echo $catid; ?>&scatid=<?php echo $scatid; ?>">
	<input type="hidden" name="action" value="scatupdate" />
<?php
	//<sato(na)0.5j>
	$manager->addTicketHidden();
	//</sato(na)0.5j>
?>
	<input type="hidden" name="scatid" value="<?php echo $scatid; ?>" />
	<input type="hidden" name="catid" value="<?php echo $catid; ?>" />

	<div>
		
	<table><tr>
		<td><?php echo _MC_SCAT_NAME ?></td>
		<td><input name="sname" tabindex="10010" maxlength="20" size="20" value="<?php echo htmlspecialchars($o->sname) ?>" /></td>
	</tr><tr>
		<td><?php echo _MC_SCAT_DESC ?></td>
		<td><input name="sdesc" tabindex="10020" size="60" maxlength="200" value="<?php echo htmlspecialchars($o->sdesc) ?>" /></td>
	</tr><tr>
<?php
			if($this->version > 2){
?>
			<td><?php echo _MC_SCAT_PARENT_NAME . '<br />' . _MC_SCAT_PARENT_NAME_DESC ?></td><!-- <sato(na)0.402j /> -->
			<td>
<?php
				$pid = quickQuery('SELECT parentid as result FROM '.$this->table.' WHERE scatid='.$scatid);
				//<sato(na)0.402j>
				//$subcategoryList = $this->getCategoryList($catid, $pid);
				//echo $this->printCategoryList($catid, $subcategoryList, 1);
				echo $this->printCategoryListUD($catid, $pid);
				//</sato(na)0.402j>
?>
	</td>
		</tr><tr>
<?php
			}
?>
		<td><?php echo _MC_SCAT_UPDATE ?></td>
		<td><input type="submit" tabindex="10030" value="<?php echo _MC_SCAT_UPDATE ?>" onclick="return checkSubmit();" /></td>
	</tr></table>
		
	</div>
</form>
<?php
			if($this->version > 2) echo $this->showOrderMenu($catid, $scatid);

			$oPluginAdmin->end();

		} else $this->error(_MC_SCAT_MISSING);
	}//-----

	function action_scatnew() {
		global $member;
		
		$member->isAdmin() or $this->disallow();
		
		$sname = postVar('sname');
		if (!trim($sname)){
			$this->action_scatoverview(_MC_SCAT_ERROR_NAME);
		} else{
			$newid = $this->createSubcat($sname);

			$array = array(
				'catid'=>postVar('catid'),
				'sdesc'=>postVar('sdesc')
			);
			if($this->version > 2)
				$array['parentid'] = postVar('parentid');
			
			$this->updateSubcat($newid,$array);

			$this->action_scatoverview();
		}
	}
	
	function action_scatupdate() {
		global $member;
		
		$scatid = intRequestVar('scatid');

		$member->isAdmin() or $this->disallow();
		
		$sname = postVar('sname');
		if (!trim($sname)) {
			$this->action_scatoverview("Error! Input a name.");
		} else {
		
			$this->addToScat($scatid);
		
			$this->action_scatedit(_MC_SCAT_DATA_UPDATE);
		}
	
	}	

//modify start+++++++++
	function action_tableUpgrade() {
		if($this->version == 2){
			$q = "ALTER TABLE `".$this->table."` ADD `parentid` INT( 11 ) DEFAULT '0' NOT NULL AFTER `scatid` , ADD `ordid` INT( 11 ) DEFAULT '100' NOT NULL AFTER `parentid` ;";
		}elseif($this->version == 3){
			$q = "ALTER TABLE `".$this->table."` ADD `ordid` INT( 11 ) DEFAULT '100' NOT NULL AFTER `parentid` ;";
		}
		if($q)	sql_query($q);
		
		$this->version = 4;

		$this->action_overview('<blockquote style="color: red;border:1px solid red;padding:0.5em;"><b>'._MC_SCAT_TABLE_UPDATE.'</b></blockquote>');
	}

	function action_scatOrder() {
		global $member;
		
		$scatid = intRequestVar('scatid');

		$member->isAdmin() or $this->disallow();
		
		$order = array();
		$order = explode(",", requestVar('orderList'));

		$x=1;
		foreach($order as $o){
			$o = trim(rtrim($o));
			$query = 'UPDATE '.$this->table.' SET ordid='.intval($x).' WHERE scatid='.intval($o); //<sato(na)0.5j />ultrarich
			sql_query($query);
			$x++;
		}

		if(requestVar('redirect') == 'scatoverview'){
			$this->action_scatoverview(_MC_SCAT_ORDER_UPDATE);
		}else{
			$this->action_scatedit(_MC_SCAT_ORDER_UPDATE);
		}
	
	}	
//modify end+++++++++

	function action_scatdelete() {
		global $member, $oPluginAdmin;
		global $manager; //<sato(na)0.5j />
		
		$member->isAdmin() or $this->disallow();
		
		$scatid = intRequestVar('scatid');
		$catid = intRequestVar('catid');
//modify start+++++++++
		if($this->version > 2){
			$sname = $this->plug->_getScatNameFromID($scatid);
			$sdesc = $this->plug->_getScatDescFromID($scatid);
			if($sdesc) $sdesc = ' ('.$sdesc.')';

		
			$modChildren = $this->_getDefinedScatsFromScat($scatid);
			$pid = quickQuery('SELECT parentid as result FROM '.$this->table.' WHERE scatid='.intval($scatid)); //<sato(na)0.5j />ultrarich
			$pid = intval($pid); //<sato(na)0.5j />ultrarich
			$pname = ($pid)? 
				$this->plug->_getScatNameFromID($pid).' ('._MC_SUB_CATEGORIES.')':
				quickQuery('SELECT cname as result FROM '.sql_table('category').' WHERE catid='.intval($catid)).' (' . _EBLOG_CAT_TITLE . ')';
		}
//modify end+++++++++
		
		$oPluginAdmin->start();
		?>
			<h2><?php echo _DELETE_CONFIRM?></h2>
			
			<p><?php echo _MC_CONFIRMTXT_SCAT ?><b>
<?php
			echo htmlspecialchars($sname.$sdesc, ENT_QUOTES); //<sato(na)0.5j />
?>
			</b></p>
			
<?php
		if($modChildren){
			$modList = $pid.'-';
			echo '<blockquote style="color: red;border:1px solid red;padding:1em;"><b>Note:</b><br />';
			echo _MC_SCAT_DELETE_NOTE_LIST;
			echo '<ul>';
			for($i=0;$i<count($modChildren);$i++){
				echo '<li>'.htmlspecialchars($modChildren[$i]['sname'], ENT_QUOTES)."</li>\n"; //<sato(na)0.5j />
				$modList .= intval($modChildren[$i]['scatid']).'/'; //<sato(na)0.5j />ultrarich
			}
			echo '</ul>';
			echo _MC_SCAT_DELETE_NOTE_PARENT.'<ul><li>'.htmlspecialchars($pname, ENT_QUOTES).'</li></ul>'; //<sato(na)0.5j />
			echo '</blockquote>';
			$extraInput = '<input type="hidden" name="modlist" value="'.substr($modList, 0, -1).'" />'."\n";
		}
?>
	
			
			<form method="post" action="<?php echo $this->url ?>index.php"><div>
				<input type="hidden" name="action" value="scatdeleteconfirm" />
<?php
	//<sato(na)0.5j>
	$manager->addTicketHidden();
	//</sato(na)0.5j>
?>
				<input type="hidden" name="scatid" value="<?php echo $scatid ?>" />
				<input type="hidden" name="catid" value="<?php echo $catid ?>" />
				<?php echo $extraInput ?>
				<input type="submit" tabindex="10" value="<?php echo _DELETE_CONFIRM_BTN ?>" />
			</div></form>
		<?php
		
		$oPluginAdmin->end();
	}	
	
	function action_scatdeleteconfirm() {
		global $member, $manager;
		
		$scatid = intRequestVar('scatid');
		$catid  = intRequestVar('catid');
		
		$member->isAdmin() or $this->disallow();
		
//modify start+++++++++
		$modList = requestVar('modlist');
		if($modList){
			list($parent, $children) = explode("-", $modList);
			$children = explode("/", $children);
			for($i=0;$i<count($children);$i++){
				$c = trim(rtrim($children[$i]));
				$query = 'UPDATE '.$this->table.' SET parentid='.intval($parent).' WHERE scatid='.intval($c); //<sato(na)0.5j />ultrarich
				sql_query($query);
			}
		}
//modify end+++++++++

		$this->deleteSubcat($scatid);
		
		$this->action_scatoverview("Sub category has been deleted.");
	}
	
	function addToScat($nowid) {
		if($this->version >= 3)
			$datanames = array('catid','sname','sdesc','parentid');
		else
			$datanames = array('catid','sname','sdesc');
		foreach ($datanames as $val) {
			$scat[$val] = postVar($val);
		}
		$this->updateSubcat($nowid,$scat);
	}

	function createSubcat($name) {
		sql_query('INSERT INTO '.$this->table.' SET sname="'. addslashes($name) .'"');
		$newid = mysql_insert_id();
		return $newid;
	}

	//<sato(na)0.402j>
	//printCategoryList for update
	function printCategoryListUD($catid, $parentid) {
		$catName = quickQuery('SELECT cname as result FROM '.sql_table('category').' WHERE catid='.intval($catid));
		$catName = htmlspecialchars($catName, ENT_QUOTES); //<sato(na)0.5j />
		$text .= "<select size='10' name='parentid'>\n";
		$text    .= "<option value='0'>&nbsp;$catName&nbsp;</option>\n";
		foreach ($this->plug->_setSubOrder() as $val) { //$val : _getSubOrder intval($row_scat->scatid)
			$query = 'SELECT * FROM '.$this->table.' WHERE scatid=' . intval($val);
			$res   = sql_query($query);
			$row   = mysql_fetch_array($res);
			if ($row['catid'] == $catid){
				$levelstr = '';
				for ($i=0; $i<$this->getDepth($val, 0); $i++) $levelstr .= "&hellip;&hellip;";
				$selected = ($parentid == $val) ? " selected='selected'" : '';
				$text    .= "<option value='".$val."'".$selected.">&nbsp;".$levelstr."&hellip;&nbsp;".
				            //<sato(na)0.5j>
				            htmlspecialchars($row['sname'], ENT_QUOTES).
				            " <sup>".
				            htmlspecialchars($row['sdesc'], ENT_QUOTES).
				            "</sup>&nbsp;</option>\n";
				            //</sato(na)0.5j>
			}
		}
		$text .= "</select>\n";
		return $text;
	}
	function getDepth($scatid, $level) {
		$parentid = quickQuery('SELECT parentid as result FROM '.$this->table.' WHERE scatid='.$scatid);
		return ($parentid == 0) ? $level : $this->getDepth($parentid, $level + 1);
	}
	function descendantCheck($parentid, $checkid){
		$res = sql_query("SELECT scatid FROM ".$this->table." WHERE parentid=".intval($parentid)); //<sato(na)0.5j />
		while ($o = mysql_fetch_object($res)) {
			if ($o->scatid == $checkid) {
				return TRUE;
			} else {
				if ($this->descendantCheck($o->scatid, $checkid)) return TRUE;
			}
		}
		return FALSE;
	}
	//</sato(na)0.402j>

	function updateSubcat($id, $scat) {
		
		//<sato(na)0.402j>
		$old_parentid = quickQuery('SELECT parentid as result FROM '.$this->table.' WHERE scatid='.$id);
		if ($id == $scat['parentid']) $branch = 'Self'; else $branch = ($this->descendantCheck($id, $scat['parentid']) ? 'Descendant' : 'Other');
		
		switch ($branch){
			case 'Self':
				//cancel
				$scat['parentid'] = $old_parentid;
				//echo '----------------------------------------Self';
			break;
			case 'Descendant':
				//Succession
				//echo '----------------------------------------Descendant';
				$query = 'UPDATE '.$this->table.' SET parentid='.intval($old_parentid).' WHERE parentid='.intval($id); //<sato(na)0.5j />
				sql_query($query);
			break;
			case 'Other':
				//Nothing is done.
				//echo '----------------------------------------Other';
		}
		//</sato(na)0.402j>
		
		$query = 'UPDATE '.$this->table.' SET ';
		foreach ($scat as $k => $v) {
			$query .= $k.'="'.addslashes($v).'",';
		}
		$query = substr($query,0,-1);
		$query .= ' WHERE scatid='.intval($id); //<sato(na)0.5j />
		sql_query($query);
	}
	
	function deleteSubcat($id) {
		$id = intval($id); //<sato(na)0.5j />
		sql_query('DELETE FROM '.$this->table.' WHERE scatid=' . $id);

		$res = sql_query("SELECT categories, subcategories, item_id FROM ". sql_table("plug_multiple_categories") ." WHERE subcategories REGEXP '(^|,)$id(,|$)'");
		$dell = array();
		$up = array();

		while ($o = mysql_fetch_object($res)) {
			$o->subcategories = preg_replace("/^(?:(.*),)?$catid(?:,(.*))?$/","$1,$2",$o->subcategories);
			if (!$o->categories && (!$o->subcategories || $o->subcategories == ',')) {
				$del[] = intval($o->item_id); //<sato(na)0.5j />ultrarich
			} else {
				$o->subcategories = preg_replace("/(^,+|(?<=,),+|,+$)/","",$o->subcategories);
				$up[] = "UPDATE ". sql_table("plug_multiple_categories") ." SET categories='".addslashes($o->categories)."', subcategories='".addslashes($o->subcategories)."' WHERE item_id=".$o->item_id;
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
	

	function action($action) {
		//<sato(na)0.5j>
		global $manager;
		$methodName         = 'action_' . $action;
		$this->action       = strtolower($action);
		$aActionsNotToCheck = array(
			'overview',
			'scatoverview',
			'scatedit',
			'scatdelete',
		);
		if (!in_array($this->action, $aActionsNotToCheck)) {
			if (!$manager->checkTicket()) $this->error(_ERROR_BADTICKET);
		}
		//</sato(na)0.5j>
		
		if (method_exists($this, $methodName)) {
			call_user_func(array(&$this, $methodName));
		} else {
			$this->error(_BADACTION . " ($action)");
		}
	}

	function disallow() {
		global $HTTP_SERVER_VARS;
		ACTIONLOG::add(WARNING, _ACTIONLOG_DISALLOWED . $HTTP_SERVER_VARS['REQUEST_URI']);
		$this->error(_ERROR_DISALLOWED);
	}

	function error($msg) {
		global $oPluginAdmin;
		
		$oPluginAdmin->start();
		$dir=$oPluginAdmin->plugin->getAdminURL();
		?>
		<h2>Error!</h2>
		<?php		echo $msg;
		echo "<br />";
		echo "<a href='".$dir."index.php' onclick='history.back()'>"._BACK."</a>";
		
		$oPluginAdmin->end();
		exit;
	}

//modify start+++++++++
    /* START OF UNLIMITED DEPTH SUBCATEGORY HELPER FUNCTIONS */
    
    function listupSubcategories($catid, $subcatid){
        $cat .= "<table style='width:auto;'><tr><td>\n";
        $cat .= "<table border='0' cellpadding='0' cellspacing='0' style='width:auto;'>\n";
        $subcategoryList = $this->getCategoryList($catid);
//print_r($subcategoryList);
        $subcategories   = $this->printCategoryList($catid, $subcategoryList, 2, 0);
        if ($subcategories == "") {
            $cat .= "No subcategories currently exist in this category.\n";
        } else {
            $cat .= $subcategories;
        }
        $cat .= "</table></td></tr></table>\n";
        $cat .= "<br />\n";
        return $cat;

    }
    
	function showOrderMenu($catid, $subcatid=0){//<sato(na)0.402j />
		global $manager; //<sato(na)0.5j />
		$text = "<h3>"._MC_MODIFY_CHILDREN_ORDER."</h3>\n";
		if ($sorder = $this->subcatOrd($catid, $subcatid)){
			$text .= "<table style='width:auto;'>\n";
			$text .= "<script language=javascript src={$this->url}orderlist.js></script>\n";
			$text .= "<form method='post' name='ordform' onsubmit=\"submitCatOrder();\">\n";
			$text .= "<input type='hidden' name='action' value='scatOrder'>\n";
			$text .= "<input type='hidden' name='ticket' value='".$manager->_generateTicket()."'>\n"; //<sato(na)0.5j />
			$text .= "<input type='hidden' name='redirect' value='".getVar('action')."'>\n";
			$text .= "<input type=hidden name=orderList value=''>\n";
			//<sato(na)0.402j>
			global $CONF;
			$actionUrl = htmlspecialchars($CONF['ActionURL'], ENT_QUOTES).
				'?action=plugin&amp;name=MultipleCategories&amp;catid='.intval($catid).'&amp;subcatid='.intval($subcatid); //<sato(na)0.5j />
			echo '<script language="javascript" src="'.$actionUrl.'"></script>';
			$text .= '
<tr><td class="main" style="border:0px;padding:0px;">
<table style="width:auto; margin:0; padding:0;">
	<tr><td style="border:0px;padding:0px;" colspan="6">'._MC_SHOW_ORDER_MENU_KEY.'</td>
	<tr><td style="border:0px;padding:0px;">
		<table>
			<tr>
				<td style="border:0px; padding:0px 3px;">ID</td>
				<td style="border:0px; padding:0px 3px;">
					<img src=plugins/multiplecategories/images/up.gif alt="Move Up" onClick="orderKey(\'id\', \'ASC\')"><br />
					<img src=plugins/multiplecategories/images/down.gif alt="Move Down" onClick="orderKey(\'id\', \'DESC\')">
				</td>
				<td style="border:0px; padding:0px 3px;">[ '._MC_SHOW_ORDER_MENU_SNAME.' ]</td>
				<td style="border:0px; padding:0px 3px;">
					<img src=plugins/multiplecategories/images/up.gif alt="Move Up" onClick="orderKey(\'sname\', \'ASC\')"><br />
					<img src=plugins/multiplecategories/images/down.gif alt="Move Down" onClick="orderKey(\'sname\', \'DESC\')">
				</td>
				<td style="border:0px; padding:0px 3px;">'._MC_SHOW_ORDER_MENU_SDESC.'</td>
				<td style="border:0px; padding:0px 3px;">
					<img src=plugins/multiplecategories/images/up.gif alt="Move Up" onClick="orderKey(\'sdesc\', \'ASC\')"><br />
					<img src=plugins/multiplecategories/images/down.gif alt="Move Down" onClick="orderKey(\'sdesc\', \'DESC\')">
				</td>
			</tr>
		</table>
	</td></tr>
</table>
</td></tr>';
			//</sato(na)0.402j>
			$text .= $sorder;
			$text .= "<tr><td class='main' style='border:0px;padding:0px;'><input type='submit' value='"._MC_SUBMIT_CHILDREN_ORDER."'></td></form></tr>\n";
			$text .= "</table>\n";
		} else {
			$text .= _MC_NO_CHILDREN_ORDER;
		}
		return $text;
	}
    function getCategoryList($catid, $selected = 0) {//$selected : parentid
        /** Returns a list of the gallery categories **/
        $queryString  = "SELECT * FROM ".$this->table;
        $queryString .= " WHERE catid=".intval($catid); //<sato(na)0.5j />
        $queryString .= ($this->version >= 4)? " ORDER BY parentid ASC, ordid ASC, scatid ASC" : " ORDER BY scatid ASC";
        $resultSet    = sql_query($queryString);

        $categoryArray = array();
        $flatList      = array();
        if (!mysql_num_rows($resultSet)) return $categoryArray;
        
        while ($row = mysql_fetch_array($resultSet)) {
            $isSelected = ($row["scatid"] == $selected)?1:0;
            $flatList[] = array(
                            //<sato(na)0.5j>
                            "id"          => intval($row["scatid"]),
                            "title"       => htmlspecialchars($row['sname'], ENT_QUOTES),
                            "description" => htmlspecialchars($row['sdesc'], ENT_QUOTES),
                            "children"    => array(),
                            "parent"      => intval($row["parentid"]),
                            //</sato(na)0.5j>
                            "selected"    => $isSelected
                        );
        }

        while (count($flatList)) {
            // Check the first one in the list
            $temp = array_shift($flatList);
            // Is it a top level category?
            if ($temp["parent"] == 0 || $temp["id"] == $selected) {
                // No parent, so just push it to the end of the big array
                $categoryArray[] = $temp;
            } else {
                // Has a parent, so search for parent, and push it to the end of the parent's child array
                if (!$this->appendChildCategory($categoryArray, $temp)) {
                    // Failed, so push category back onto the end of the array
                    $flatList[] = $temp;
                }
            }
        }
        return $categoryArray;
    }

    function appendChildCategory(&$categoryArray, $child) {
        if (empty($categoryArray)) return false;
        
        for($i=0; $i<count($categoryArray); $i++) {
            $category =& $categoryArray[$i];
            if ($category["id"] == $child["parent"]) {
                // Found it, push to category's child list
                $category["children"][] = $child;
                return true;
            } else {
                if ($this->appendChildCategory($category["children"], $child)) {
                    return true;
                }
            }
        }
        return false;
    }

    function printCategoryList($catid, $categoryList, $type=1, $selectedCategory=0, $def='') {
        $text = "";
        $catName = quickQuery('SELECT cname as result FROM '.sql_table('category').' WHERE catid='.intval($catid));
        $catName = htmlspecialchars($catName, ENT_QUOTES); //<sato(na)0.5j />
        if ($type == 1) {
            // Select box
            $text .= "<select size='10' name='parentid'>\n";
            if ($def != 1){
                $selected = ($selectedCategory == 0) ? " selected='selected'" : '';
                $text    .= "<option value='0'$selected>&nbsp;$catName&nbsp;</option>\n";
            }
            $text .= $this->walkCategoryList($categoryList, $catid, $type, $selectedCategory);
            $text .= "</select>\n";
        } elseif ($type == 2) {
            // Table: with action cell
            $text .= "<tr><td class='mainbold' style='border:0px;padding:0px;'><img src='./plugins/multiplecategories/images/folderopen.gif' alt='' /> $catName </td><td class='mainbold' style='border:0px;padding:0px;'>"._LISTS_ACTIONS."</td></tr>\n";
            $text .= $this->walkCategoryList($categoryList, $catid, $type, $selectedCategory);
        } elseif ($type == 3) {
            //
            $selected = ($selectedCategory == 0) ? " selected='selected'" : '';
            $text    .= "<option value='0'$selected> $catName </option>\n";
            $text    .= $this->walkCategoryList($categoryList, $catid, $type, $selectedCategory);
        }
        return $text;
    }

    function walkCategoryList(&$categoryList, $catid, $type = 1, $selectedCategory = 0, $level = 0, $childrenLevel = array()) {
        $text = "";
        if ($type == 1) {
            // Select box
            foreach ($categoryList as $category) {
                if ($category["selected"] == 1) {
                    $text .= "<option value='{$category['id']}' selected='selected'>&nbsp;\n";
                } else {
                    $text .= "<option value='{$category['id']}'>&nbsp;\n";
                }
                for ($i=0;$i<$level;$i++) {
                    $text .= "&hellip;&hellip;";
                }
                $text .= "&hellip;&nbsp;".htmlspecialchars($category["title"], ENT_QUOTES).
                	" <sup>".htmlspecialchars($category["description"], ENT_QUOTES)."</sup>&nbsp;</option>\n"; //<sato(na)0.5j />
                $text .= $this->walkCategoryList($category["children"], $catid, $type, $selectedCategory, $level+1);
            }
        } elseif ($type == 2) {
            // Table
            for ($j=0; $j < count($categoryList); $j++) {
                $category              = $categoryList[$j];
                $hasSiblings           = (($j < (count($categoryList)-1))?1:0); // are there more categories on the same level as this category?
                $childrenLevel[$level] = $hasSiblings;
                $children = $this->walkCategoryList($category["children"], $catid, $type, $selectedCategory, $level+1, $childrenLevel);

                if ($selectedCategory == $category["id"]) {
                    $text .= "<tr bgcolor=\"#B5C1DC\">\n";
                } else {
                    $text .= "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);' style='border:0px;padding:0px;'>\n";
                }
                $text .= "<td class='hover' style='border:0px;padding:0px;'>\n";
                if ($level == 0) {
                    if ($hasSiblings) {
                        $text .= "<img src='./plugins/multiplecategories/images/minusbottom.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                    } else {
                        $text .= "<img src='./plugins/multiplecategories/images/minus.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                    }
                } else {
                    for ($i=0;$i<$level;$i++) {
                        if ($childrenLevel[$i]) {
                            $text .= "<img src='./plugins/multiplecategories/images/line.gif' alt='' /> \n";
                        } else {
                            $text .= "<img src='./plugins/multiplecategories/images/empty.gif' alt='' />";
                        }
                    }
                    if ($hasSiblings) {
                        if ($children != "") {
                            $text .= "<img src='./plugins/multiplecategories/images/minusbottom.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                        } else {
                            $text .= "<img src='./plugins/multiplecategories/images/joinbottom.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                        }
                    } else {
                        if ($children != "") {
                            $text .= "<img src='./plugins/multiplecategories/images/minus.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                        } else {
                            $text .= "<img src='./plugins/multiplecategories/images/join.gif' alt='' /><img src='./plugins/multiplecategories/images/folder.gif' alt='' /> \n";
                        }
                    }
                }
                if ($row[group] != 0){ $group = "(Private)"; }
                else { $group = ''; }
                if ($selectedCategory == $category["id"]) {
                    $text .= "<strong>".htmlspecialchars($category["title"], ENT_QUOTES).
                    	" <sup>".htmlspecialchars($category["description"], ENT_QUOTES)."</sup>"."</strong> $group</td>\n";
                } else {
                    $text .= htmlspecialchars($category["title"], ENT_QUOTES).
                    	" <sup>".htmlspecialchars($category["description"], ENT_QUOTES)."</sup>"." $group</td>\n";
                }
                $subcatid = $category["id"];
                if ($type == 2) {
                    // ALL ACTIONS //
                    $text .= "<td class='hover' align='center' style='border:0px;padding:0px 0px 0px 15px;'><a href={$this->url}index.php?action=scatedit&catid=$catid&amp;scatid=$subcatid><img src=plugins/multiplecategories/images/bedit.gif alt='"._LISTS_EDIT."' border=0></a> \n<a href={$this->url}index.php?action=scatdelete&catid=$catid&amp;scatid=$subcatid><img src=plugins/multiplecategories/images/bdelete.gif alt='"._LISTS_DELETE."' border=0></a></td>\n";
                }
                $text .= "</tr>\n";
                $text .= $children;
            }
        } elseif ($type == 3) {
            // Select box
            foreach ($categoryList as $category) {
                if ($category["id"] == $selectedCategory) {
                    $text .= "<option value='{$category['id']}' selected='selected'>\n";
                } else {
                    $text .= "<option value='{$category['id']}'>\n";
                }
                for ($i=0;$i<$level;$i++) {
                    $text .= "&nbsp;&nbsp;";
                }
                $text .= htmlspecialchars($category["title"], ENT_QUOTES)."</option>\n";
                $text .= $this->walkCategoryList($category["children"], $catid, $type, $selectedCategory, $level+1);
            }
        }
        return $text;
    }


    function subcatOrd($catid, $subcatid=0){
         //<sato(na)0.5j>
         $catid    = intval($catid);
         $subcatid = intval($subcatid);
         //</sato(na)0.5j>
        $q = "select scatid, sname, sdesc from ".$this->table." where parentid = '$subcatid' and catid=$catid";//<sato(na)0.402j />
        $q .= ($this->version >= 4) ? ' order by ordid, scatid ASC': ' order by scatid ASC';
        $result = sql_query($q);
        if(mysql_num_rows($result) < 2){
        	return FALSE;
        }
        $text .= "<tr><td class=main style='border:0px;padding:0px;'><select name=order multiple size=15>";
        //<sato(na)0.402j>
        while($row = mysql_fetch_array($result)){
            //<sato(na)0.5j>
            $text .= "<option value=".$row['scatid'].">".$row['scatid']."&nbsp;[&nbsp;".
                     htmlspecialchars($row['sname'], ENT_QUOTES).
                     "&nbsp;]&nbsp;".
                     htmlspecialchars($row['sdesc'], ENT_QUOTES).
                     "</option>";
            //</sato(na)0.5j>
        }
        $text .= "</select>"._MC_SHOW_ORDER_MENU_INDIVIDUAL."&nbsp;";
        //</sato(na)0.402j>
        $text .= "<img src=plugins/multiplecategories/images/up.gif alt='Move Up' onClick=\"moveOptionUp(document.ordform.order)\">&nbsp;";
        $text .= "<img src=plugins/multiplecategories/images/down.gif alt='Move Down' onClick=\"moveOptionDown(document.ordform.order)\"></td></tr>";
        return $text;
    }

	function _getDefinedScatsFromScat($id){
		$aResult = array();	
		$query = 'SELECT * FROM '.sql_table('plug_multiple_categories_sub').' WHERE parentid=' . intval($id);
		$res = sql_query($query);	
		while ($a = mysql_fetch_assoc($res)){
			array_push($aResult,$a);
		} 
		return $aResult;
	}
//modify end+++++++++


} // NpMCategories_ADMIN end
	
// ------------------------------------------------------------------

$myAdmin = new NpMCategories_ADMIN();
if (requestVar('action')) {
	$myAdmin->action(requestVar('action'));
} else {
	$myAdmin->action('overview');
}

?>

