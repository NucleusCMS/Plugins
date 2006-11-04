<?php

class NP_SkinSwitcher extends NucleusPlugin {

 function getNAME() { return 'Skin Switcher';  }
 function getAuthor()  { return 'Andy + nakahara21 et al.';  }
 function getURL() {  return ''; }
 function getVersion() { return '0.7.2'; }
 function getDescription() { 
  return 'Skin selector. &lt;%SkinSwitcher()%&gt; makes a drop down menu. you can define unselectable skin on each blog, and all blogs.';
 }
 
 function install() {
		$this->createOption("del_uninstall", "Delete tables on uninstall?", "yesno", "no");

		$query =  'CREATE TABLE IF NOT EXISTS '. sql_table('plug_skinswitcher'). '('
		. 'ssid int(11) not null auto_increment,'
		. 'sblogid int(11) NOT NULL,'
		. 'disskinid TEXT NOT NULL,'
		. ' PRIMARY KEY (ssid)'
		. ') TYPE=MyISAM;';
		sql_query($query);
 }
 
 function unInstall() { 
		if ($this->getOption('del_uninstall') == "yes") {
			sql_query('DROP TABLE ' .sql_table('plug_skinswitcher'));
		}
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
	function getTableList() {
		return array(sql_table('plug_skinswitcher'));
	}
	function hasAdminArea() { return 1; }
	function event_QuickMenu(&$data) {
		global $member;
		// only show to blogAdmins
		if (!($member->isLoggedIn() && $member->getAdminBlogs())) return;
		array_push(
			$data['options'],
			array(
				'title' => 'SkinSwitcher',
				'url' => $this->getAdminURL(),
				'tooltip' => 'Edit SkinSwitcher'
			)
		);
	}

	function getEventList()   { 		
		return array('QuickMenu','InitSkinParse');
	}

	function event_InitSkinParse(&$data) {
		global $CONF, $blogid;
		$cookieName = $CONF['CookiePrefix'] .'nuc_skinswitch';

		if (cookieVar($cookieName)) {
			$skinID = cookieVar($cookieName);
			$sdnums = $this->getSdnums($blogid);
			if(in_array($skinID,$sdnums)){
				setcookie($cookieName,'',(time() - 3600),$CONF['CookiePath'],$CONF['CookieDomain'],$CONF['CookieSecure']);
			}else{
				if ($data['skin']->existsID($skinID)) {
//					$data['skin']->SKIN($skinID);
					$data['skin'] = new SKIN($skinID);
				}
			}
		}
	}

	function doSkinVar($skinType) {
		global $blog, $currentSkinName, $CONF,$manager;

		$b =& $blog;
		$defskinid = $b->getDefaultSkin();
		$defskinName = SKIN::getNameFromId($defskinid);
		$currentSkinID = SKIN::getIdFromName($currentSkinName);
		$blogid = $b->getID();
		$cookieContent = "document.cookie='".$CONF['CookiePrefix']."nuc_skinswitch=' + this.value + ';'";
		$cookieContentExtra = '';
		if($CONF['CookiePath']) $cookieContentExtra .= "path=".$CONF['CookiePath'].";";
		if($CONF['CookieDomain']) $cookieContentExtra .= "domain=".$CONF['CookieDomain'].";";
		if($CONF['CookieSecure']) $cookieContentExtra .= "secure=".$CONF['CookieSecure'].";";
		if($cookieContentExtra) $cookieContent .= " + '".$cookieContentExtra."'";

		echo '<form action="">';		
//		echo '<select name="skinselector" onchange="document.cookie=\''.$CONF['CookiePrefix'].'nuc_skinswitch=\' + this.value;">';
		echo '<select name="skinselector" onchange="'.$cookieContent.'">';
		echo '<optgroup label="Blog default" style="color:red;">';
			$exstr = ($defskinid==$currentSkinID)? ' selected="selected"': '';
			echo '<option value="' . (int)$defskinid . '"'.$exstr.'>';
			echo htmlspecialchars($defskinName).'</option>';
		echo '</optgroup>';

		echo '<optgroup label="oters">';
		$global_sdnums = $this->getSdnums(0);
		$sdnums = $this->getSdnums($blogid);
		$res = sql_query('SELECT * FROM '.sql_table('skin_desc').' WHERE sdnumber<>'.(int)$defskinid);
		while ($skinObj = mysql_fetch_object($res)) {
			if(in_array($skinObj->sdnumber,$global_sdnums) || in_array($skinObj->sdnumber,$sdnums)) continue;
			$exstr = ($skinObj->sdnumber==$currentSkinID)? ' selected': '';
			echo '<option value="' . (int)$skinObj->sdnumber . '"'.$exstr.'>';
			echo htmlspecialchars($skinObj->sdname).'</option>';
		}
		echo '</optgroup>';
		echo '</select>';
		echo '<input type="submit" value="select" onclick="window.location.reload();return false;" />';
		echo '</form>';

		if($currentSkinID != $defskinid && $this->canChange($blogid)){
			echo '<div id="np_skinswitcher_skindef"><a href="javascript:np_skinswitcher_setDefSkin('."'".(int)$currentSkinID."','".(int)$blogid."'".');">set default skin to "'.htmlspecialchars($currentSkinName).'"</a></div>';
		}
if($this->canChange($blogid)){
	$ticket=$manager->addTicketToUrl('');
	$ticket=substr($ticket,strpos($ticket,'ticket=')+7);
?>
	<script type="text/javascript">
	var np_skinswitcher_xmlhttp = false;
	var scAction = "<?php echo $CONF['ActionURL'];?>";
		function np_skinswitcher_setDefSkin(skinid, blogid){
			try 
			{
				np_skinswitcher_xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			} 
			catch (e) 
			{
				try 
				{
					np_skinswitcher_xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				} 
				catch (e) 
				{
					np_skinswitcher_xmlhttp = false;
				}
			}

			if (!np_skinswitcher_xmlhttp && typeof XMLHttpRequest!='undefined'){
				np_skinswitcher_xmlhttp = new XMLHttpRequest();
			}
			
			if (np_skinswitcher_xmlhttp){
				var url = scAction + '?action=plugin&name=SkinSwitcher&type=change' +
					'&s=' + skinid + '&b=' + blogid +
					'&ticket=<?php echo $ticket; ?>';
		
				np_skinswitcher_xmlhttp.onreadystatechange=np_skinswitcher_xmlhttpChange
				np_skinswitcher_xmlhttp.open("GET",url,true)
				np_skinswitcher_xmlhttp.send('')
			}
		}
	function np_skinswitcher_xmlhttpChange()
	{
		if (np_skinswitcher_xmlhttp.readyState == 4 && np_skinswitcher_xmlhttp.status == 200) 
		{
			var deff = document.getElementById("np_skinswitcher_skindef");
			deff.innerHTML = np_skinswitcher_xmlhttp.responseText;
		}
	}
	
	</script>

<?php
}

	}

	function getSdnums($blogid=0) {
		$pq = 'SELECT disskinid FROM '.sql_table('plug_skinswitcher').' WHERE sblogid='.(int)$blogid;
		$pres = sql_query($pq);
		if (mysql_num_rows($pres) == 0) return array();
		$sdnums = mysql_result($pres,0,0);
		$sdnums = explode(',',$sdnums);
		return $sdnums;
	}

	function canChange($blogid) {
		global $member;
		if(!$member->isLoggedIn()) return 0;
		return $member->isBlogAdmin($blogid);
	}

	function doAction($type){
		global $CONF, $manager;
		if (!$manager->checkTicket()) {
			echo '<b style="color:red;">'._ERROR_BADTICKET.'</b>';
			return;
		}
		switch ($type) {
			case 'change':
				if(!($blogid = intGetVar('b'))) return;
				if(!$this->canChange($blogid)) return;

				if(!($skinid = intGetVar('s'))) return;
				$query =  'UPDATE '.sql_table('blog')
				       . " SET bdefskin=" . $skinid
				       . " WHERE bnumber=" . $blogid;
				$res = @mysql_query($query);
				if($res){
					echo '<b style="color:red;">Done! Please reload.</b>';
				}else{
					echo 'Could not update: ' . htmlspecialchars( mysql_error().$query );
				}		
				break;
			default:
				break;
		}
	}

}
?>