<?php

	$strRel = '../../../'; 
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	
	// Send out Content-type
	sendContentType('application/xhtml+xml', 'admin-skinswitcher', _CHARSET);	
/*
	if (!($member->isLoggedIn() && $member->isAdmin()))
		doError('You\'re not logged in.');
*/	
	if (!($member->isLoggedIn() && $member->getAdminBlogs()))
		doError('You do not have admin rights for any blogs.');

	$oPluginAdmin = new PluginAdmin('SkinSwitcher');

		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($oPluginAdmin->plugin->getDirectory().'language/'.$language.'.php')) 
			include_once($oPluginAdmin->plugin->getDirectory().'language/'.$language.'.php'); 
		else 
			include_once($oPluginAdmin->plugin->getDirectory().'language/'.'english.php');


// ------------------------------------------------------------------
class NpSkinSwitcher_ADMIN{
	
	function NpSkinSwitcher_ADMIN(){
		global $oPluginAdmin;
		$this->url = $oPluginAdmin->plugin->getAdminURL();
		$this->extrahead = '<link rel="stylesheet" type="text/css" href="'.$this->url.'plus.css" />'."\n";
		session_start();

	}

	function msg(){
		$msg = $_SESSION['msg'];
		if ($msg) echo "<blockquote>"._MESSAGE.": $msg</blockquote>";
		unset($_SESSION['msg']);
	}

	function showSelectList($blogid){
		global $member, $oPluginAdmin;
		if(!$blogid && !($member->isAdmin())) return;

		$global_sdnums = $oPluginAdmin->plugin->getSdnums(0);
		$defskinid = quickQuery('SELECT bdefskin as result FROM '.sql_table('blog').' WHERE bnumber='.intval($blogid));
		if($hkey = array_search($defskinid,$global_sdnums)) unset($global_sdnums[$hkey]);
		$sdnums = $oPluginAdmin->plugin->getSdnums($blogid);
		if($blogid)
			echo '<h4>'._EBLOG_NAME.' : '.getBlogNameFromID($blogid).'</h4>';
		else
			echo '<h4>'._SETTINGS_TITLE.'</h4>';

		$this->msg();
?>
			<form method="post" action="<?php echo $this->url ?>index.php">
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="blogid" value="<?php echo $blogid ?>" />
<?php		
		echo '<table>'."\n";
		echo '<thead><tr><th>'._SKIN_NAME.'</th><th>'._SKIN_TYPE.'</th><th>'._SKIN_DESC.'</th></tr></thead>'."\n";
		echo '<tbody>';
		$query =  'SELECT * FROM '.sql_table('skin_desc');
		$res = sql_query($query);
		
		
		$i=0;
		while($ob = mysql_fetch_object($res)){
			if($blogid && in_array($ob->sdnumber,$global_sdnums)) continue;
			$chtxt = (in_array($ob->sdnumber,$sdnums))? ' checked="checked"': '';
			$extxt = ($ob->sdnumber==$defskinid)? '<b> ('._EBLOG_DEFSKIN.')</b>': '';
			echo '<tr'." onmouseover='focusRow(this);' onmouseout='blurRow(this);'".'><td><input type="checkbox" id="batch'.$i.'" name="sdnum['.$i.']" value="'.$ob->sdnumber.'"'.$chtxt.' /><label for="batch'.$i.'">'.$ob->sdname.$extxt.'</label></td><td>'.$ob->sdtype.'</td><td>'.$ob->sddesc.'</td></tr>'."\n";
			$i++;
		}
		echo '<tr><td colspan="3">
		<a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(1); ">'._BATCH_SELECTALL.'</a>
		 <a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(0); ">'._BATCH_DESELECTALL.'</a>
			<input type="submit" tabindex="10" value="'._SUBMIT.'" /> 
		 </td></tr>'."\n";
		echo '</tbody></table></form>'."\n";
	}

	function bloglistForSS(){
		global $member;
		
		echo '<h4>'._OVERVIEW_YRBLOGS.' ('._BMLET_OPTIONS.')</h4>';
		if ($member->isAdmin()) {
			// Super-Admins have access to all blogs! (no add item support though)
			$query =  'SELECT bnumber, bname, 1 as tadmin, burl, bshortname'
				   . ' FROM ' . sql_table('blog')
				   . ' ORDER BY bname';
		} else {
			$query =  'SELECT bnumber, bname, tadmin, burl, bshortname'
				   . ' FROM ' . sql_table('blog') . ', ' . sql_table('team')
				   . ' WHERE tblog=bnumber and tmember=' . $member->getID() . ' and tadmin=1'
				   . ' ORDER BY bname';
		}
		$res = sql_query($query);
		
		$i=0;
		echo '<table>'."\n";
		echo '<tr><th>'._EBLOG_NAME.'</th><th>'._EBLOG_DESC.'</th><th>'._LISTS_ACTIONS.'</th></tr>'."\n";
		while($ob = mysql_fetch_object($res)){
			echo '<tr'." onmouseover='focusRow(this);' onmouseout='blurRow(this);'".'><td>' . $ob->bname . '</td><td>'.$ob->bdesc.'</td><td><a href="'.$this->url.'index.php?action=blogoverview&amp;blogid='.$ob->bnumber.'">'._PLUG_SKINSWITCHER_BLOGLINK.'</a></td></tr>';
		}
		echo '</table>'."\n";
		
	}

	function action_overview(){
		global $member, $oPluginAdmin;
//		$member->isAdmin() or $this->disallow();
		$member->isLoggedIn() or $this->disallow();

		$oPluginAdmin->start($this->extrahead);
		echo '<h2>SkinSwitcher</h2>';
		echo _PLUG_SKINSWITCHER_HINT;
		$this->showSelectList(0);
		$this->bloglistForSS();

		
		$oPluginAdmin->end();
	}

	function action_blogoverview(){
		global $member, $oPluginAdmin;
		$member->isLoggedIn() or $this->disallow();

		$oPluginAdmin->start($this->extrahead);
		echo '<h2>SkinSwitcher</h2>';
		echo _PLUG_SKINSWITCHER_HINT;
		$blogid = intRequestVar('blogid');
		$this->showSelectList($blogid);

		
		$oPluginAdmin->end();
	}
	

	function action_update(){
		global $member, $oPluginAdmin, $oTemplate;
//		$member->isAdmin() or $this->disallow();
		$member->isLoggedIn() or $this->disallow();

		$blogid = intRequestVar('blogid');
		$sdnums = @join(',',requestVar('sdnum'));
		
		$dq = 'DELETE FROM '.sql_table('plug_skinswitcher').' WHERE sblogid='.$blogid;
		$dres = sql_query($dq);
		
		if($sdnums){
		$iq = "
				INSERT INTO 
					".sql_table('plug_skinswitcher')." 
				SET
					sblogid = ".$blogid.", 
					disskinid = '".$sdnums."'
				";
				$res = @mysql_query($iq);
				if (!$res) {
					$_SESSION['msg'] = $iq.'Could not save data: ' . mysql_error() . $query;
				}else{
					$_SESSION['msg'] = 'Saved.';
				}
		}else{
					$_SESSION['msg'] = 'Saved.';
		}
		
		header('location: '.$this->url);

	}

	
	
	function action($action) {
		$methodName = 'action_' . $action;
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
}
// ------------------------------------------------------------------
$myAdmin = new NpSkinSwitcher_ADMIN();
if (requestVar('action')) {
	$myAdmin->action(requestVar('action'));
} else {
	$myAdmin->action('overview');
}

?>