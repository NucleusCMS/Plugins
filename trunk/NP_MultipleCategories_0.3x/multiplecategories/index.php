<?php
/**
  * NP_MultipleCategories Admin Page Script 
  *     Taka ( http://reverb.jp/vivian/) 2004-12-01
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

	}

//-------------------

	function action_overview($msg='') {
		global $member, $oPluginAdmin;
		
		$member->isAdmin() or $this->disallow();

		$oPluginAdmin->start();
		
		echo '<p><a href="index.php?action=pluginlist">('._PLUGS_BACK.')</a></p>';
		echo '<h2>' .$this->plugname. '</h2>'."\n";
		if ($msg) echo "<p>"._MESSAGE.": $msg</p>";
		echo '<p>[<a href="index.php?action=pluginoptions&amp;plugid='.$this->plug->getID().'">Edit Plugin Options</a>]</p>';
?>

<?php
		$res = sql_query('SELECT bnumber, bname FROM '.sql_table('blog'));
		while ($o = mysql_fetch_object($res)) {
?>
<?php
		echo '<h3 style="padding-left: 0px">' . htmlspecialchars($o->bname, ENT_QUOTES) . '</h3>'; //<sato(na)0.38j />
?>
<table>
	<thead>
		<tr><th><?php echo _LISTS_NAME ?></th><th><?php echo _LISTS_DESC ?></th><th>Sub Categories</th><th><?php echo _LISTS_ACTIONS ?></th></tr>
	</thead>
	<tbody>
<?php
			$cats = $this->plug->_getCategories($o->bnumber);
			foreach ($cats as $cat) {
				$snum = quickQuery("SELECT count(*) as result FROM ".$this->table." WHERE catid=".$cat['catid']);
				$snum = intval($snum);
?>
		<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>
			<td>
				<?php echo htmlspecialchars($cat['name'], ENT_QUOTES) ?></td>
			<td><?php echo htmlspecialchars($cat['cdesc'], ENT_QUOTES) ?></td>
			<td><?php echo $snum ?></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatoverview&amp;catid=<?php echo intval($cat['catid']); ?>" tabindex="50">Edit sub categories</a></td>
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
		global $member, $manager, $oPluginAdmin;
		
		$member->isAdmin() or $this->disallow();
		
		$catid = intRequestVar('catid');
		$catname = $this->plug->_getCatNameFromID($catid);
		
		$oPluginAdmin->start();

?>
<p><a href="<?php echo $this->url ?>index.php?action=overview">(Go Back)</a></p>

<h2><?php 
		echo " Edit Sub Categories of '".htmlspecialchars($catname, ENT_QUOTES)."'</h2>\n";

		if ($msg) echo "<p>"._MESSAGE.": $msg</p>";
?>

	<table>
	<thead>
		<tr><th><?php echo _LISTS_NAME ?></th><th><?php echo _LISTS_DESC ?></th><th colspan='2'><?php echo _LISTS_ACTIONS ?></th></tr>
	</thead>
	<tbody>
<?php
		$defines = $this->plug->_getDefinedScats($catid);
		if (count($defines) > 0) {
			foreach ($defines as $scat) {
?>
		<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>
			<td><?php echo htmlspecialchars($scat['sname'], ENT_QUOTES) ?></td>
			<td><?php echo htmlspecialchars($scat['sdesc'], ENT_QUOTES) ?></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatedit&amp;catid=<?php echo intval($catid); ?>&amp;scatid=<?php echo intval($scat['scatid']); ?>" tabindex="50"><?php echo _LISTS_EDIT ?></a></td>
			<td><a href="<?php echo $this->url ?>index.php?action=scatdelete&amp;catid=<?php echo intval($catid); ?>&amp;scatid=<?php echo intval($scat['scatid']); ?>" tabindex="50"><?php echo _LISTS_DELETE ?></a></td>
		</tr>
<?php
			}
		}
?>
	</tbody>
	</table>
<?php
		
		echo "\n\n".'<h3>Create New Sub Category</h3>'."\n\n";
		
?>
	<form method="post" action="<?php echo $this->url ?>index.php"><div>
	
		<?php $manager->addTicketHidden(); ?>
		<input name="action" value="scatnew" type="hidden" />
		<input name="catid" value="<?php echo intval($catid); ?>" type="hidden" />
		<table><tr>
			<td>Name</td>
			<td><input name="sname" tabindex="10010" maxlength="20" size="20" /></td>
		</tr><tr>
			<td>Description</td>
			<td><input name="sdesc" tabindex="10020" size="60" maxlength="200" /></td>
		</tr><tr>
			<td>Create</td>
			<td><input type="submit" tabindex="10030" value="Create Sub Category" onclick="return checkSubmit();" /></td>
		</tr></table>
		
	</div></form>
<?php
		
		$oPluginAdmin->end();
	
	}
	
	function action_scatedit($msg = '') {
		global $member, $manager, $oPluginAdmin;
		
		$member->isAdmin() or $this->disallow();
		
		$scatid = intRequestVar('scatid');
		$catid = intRequestVar('catid');
		
		$res = sql_query("SELECT * FROM ".$this->table." WHERE scatid=$scatid and catid=$catid");
		if ($o = mysql_fetch_object($res)) {

		$oPluginAdmin->start();

?>
<p><a href="<?php echo $this->url ?>index.php?action=scatoverview&amp;catid=<?php echo intval($catid); ?>">(Go Back)</a></p>

<h2><?php 
			echo ' Edit';
			echo  " '".htmlspecialchars($o->sname, ENT_QUOTES)."'</h2>\n";

			if ($msg) echo "<p>"._MESSAGE.": $msg</p>";
		
?>

<form method="post" action="<?php echo $this->url ?>index.php">
	<div>
		
	<?php $manager->addTicketHidden(); ?>
	<input type="hidden" name="action" value="scatupdate" />
	<input type="hidden" name="scatid" value="<?php echo intval($scatid); ?>" />
	<input type="hidden" name="catid" value="<?php echo intval($catid); ?>" />
	<table><tr>
		<td>Name</td>
		<td><input name="sname" tabindex="10010" maxlength="20" size="20" value="<?php echo htmlspecialchars($o->sname, ENT_QUOTES); ?>" /></td>
	</tr><tr>
		<td>Description</td>
		<td><input name="sdesc" tabindex="10020" size="60" maxlength="200" value="<?php echo htmlspecialchars($o->sdesc, ENT_QUOTES); ?>" /></td>
	</tr><tr>
		<td>Create</td>
		<td><input type="submit" tabindex="10030" value="Create Sub Category" onclick="return checkSubmit();" /></td>
	</tr></table>
		
	</div>
</form>
		
<?php
			$oPluginAdmin->end();

		} else {
			$this->error("Sub category is missing...");
		}
	}

	function action_scatnew() {
		global $member;
		
		$member->isAdmin() or $this->disallow();
		
		$sname = postVar('sname');
		if (!trim($sname)) $this->action_scatoverview("Error! Input a name.");
		
		$newid = $this->createSubcat($sname);

		$array = array(
			'catid'=>postVar('catid'),
			'sdesc'=>postVar('sdesc')
		);
		$this->updateSubcat($newid,$array);

		$this->action_scatoverview();
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
		
			$this->action_scatoverview("Sub category data has been saved.");
		}
	
	}	

	function action_scatdelete() {
		global $member, $manager, $oPluginAdmin;
		
		$member->isAdmin() or $this->disallow();
		
		$scatid = intRequestVar('scatid');
		$catid = intRequestVar('catid');
		$sname = requestVar('sname');
		
		$oPluginAdmin->start();
		
		?>
			<h2><?php echo _DELETE_CONFIRM?></h2>
			
			<p>You're about to delete the sub category <b><?php echo htmlspecialchars($sname, ENT_QUOTES); ?></b></p>
			
			<form method="post" action="<?php echo $this->url ?>index.php"><div>
				<?php $manager->addTicketHidden(); ?>
				<input type="hidden" name="action" value="scatdeleteconfirm" />
				<input type="hidden" name="scatid" value="<?php echo intval($scatid); ?>" />
				<input type="hidden" name="catid" value="<?php echo intval($catid); ?>" />
				<input type="submit" tabindex="10" value="<?php echo _DELETE_CONFIRM_BTN ?>" />
			</div></form>
		<?php
		
		$oPluginAdmin->end();
	}	
	
	function action_scatdeleteconfirm() {
		global $member, $manager;
		
		$scatid = intRequestVar('scatid');
		$catid = intRequestVar('catid');
		
		$member->isAdmin() or $this->disallow();
		
		$this->deleteSubcat($scatid);
		
		$this->action_scatoverview("Sub category has been deleted.");
	}
	
	function addToScat($nowid) {
		$datanames = array('catid','sname','sdesc');
		foreach ($datanames as $val) {
			$scat[$val] = postVar($val);
		}
		$this->updateSubcat($nowid,$scat);
	}

	function createSubcat($name) {
		sql_query('INSERT INTO '.$this->table.' SET sname="'. addslashes($name) .'"');
		$newid = mysql_insert_id();
		global $manager;
		$manager->notify(
						 'PostAddSubcat',
						 array(
							   'subcatid' => $newid
							  )
						);
		return $newid;
	}

	function updateSubcat($id, $scat) {
		$query = 'UPDATE '.$this->table.' SET ';
		foreach ($scat as $k => $v) {
			$query .= $k.'="'.addslashes($v).'",';
		}
		$query = substr($query,0,-1);
		$query .= ' WHERE scatid='.$id;
		sql_query($query);
	}
	
	function deleteSubcat($id) {
		$id = intval($id); //<sato(na)0.38j />
		sql_query('DELETE FROM '.$this->table.' WHERE scatid=' . $id);
		global $manager;
		$manager->notify(
						 'PostDeleteSubcat',
						 array(
							   'subcatid' => $id
							  )
						);

		$res = sql_query("SELECT categories, subcategories, item_id FROM ". sql_table("plug_multiple_categories") ." WHERE subcategories REGEXP '(^|,)$id(,|$)'");
		$dell = array();
		$up = array();

		while ($o = mysql_fetch_object($res)) {
			$o->subcategories = preg_replace("/^(?:(.*),)?$catid(?:,(.*))?$/","$1,$2",$o->subcategories);
			if (!$o->categories && (!$o->subcategories || $o->subcategories == ',')) {
				$del[] = intval($o->item_id); //<sato(na)0.38j />ultrarich
			} else {
				$o->subcategories = preg_replace("/(^,+|(?<=,),+|,+$)/","",$o->subcategories);
				$up[] = "UPDATE ". sql_table("plug_multiple_categories") ." SET categories='".addslashes($o->categories)."', subcategories='".addslashes($o->subcategories)."' WHERE item_id=".intval($o->item_id); //<sato(na)0.38j />ultrarich
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
		//<sato(na)0.38j />
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
		//<sato(na)0.38j />
		
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

} // NpMCategories_ADMIN end
	
// ------------------------------------------------------------------

$myAdmin = new NpMCategories_ADMIN();
if (requestVar('action')) {
	$myAdmin->action(requestVar('action'));
} else {
	$myAdmin->action('overview');
}

?>

