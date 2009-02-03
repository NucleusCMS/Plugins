<?php

class UpdatePingServer_Management extends PlugManagement
{
	var $tpmanager;
	var $poupurl;
	
	function init() {
		global $CONF;

		// include language file
		$langfile = $this->plugin->getDirectory() . 'language/'.$CONF['Language'].'.php';
		if (!file_exists($langfile)) {
			$langfile = $this->plugin->getDirectory() . 'language/english.php';
		}
		include_once($langfile);

		// assign plugin data to view object
		$helpurl = $this->plugin->getAdminURL() .'index.php?action=pluginhelp';
		$plug_data = array(
			'name' => $this->plugin->getName(),
			'id' => $this->plugin->getID(),
			'url' => $this->plugin->getAdminURL(),
			'helpurl' => $helpurl
			);
		$this->view->assign('plugin', $plug_data);
		$this->view->assign('message', '');
		
		// popup help URL
		$popup_helpfile = $this->plugin->getDirectory() .'i18nhelp/'.$CONF['Language']. '.popuphelp.html';
		if (file_exists($popup_helpfile)) {
			$this->popupurl = $this->plugin->getAdminURL() .'i18nhelp/'.$CONF['Language']. '.popuphelp.html';
		} else {
			$this->popupurl = $this->plugin->getAdminURL() . 'popuphelp.html';
		}
	}
	
	function &getTpManager() {
		if (!$this->tpmanager) {
			$this->tpmanager =& new PlugTemplate(sql_table('plug_blogmenu_template'), 'tid', 'tname', 'tdesc');
		}
		return $this->tpmanager;
	}
	
	function createPopup($anchor) {
		return $this->createPopupHelpLink($this->popupurl, $anchor);
	}
	
	function getHelpPath() {
		global $CONF;
		
		$helpfile = $this->plugin->getDirectory() .'i18nhelp/'.$CONF['Language']. '.help.html';
		if (!file_exists($helpfile)) {
			$helpfile = $this->plugin->getDirectory() . 'help.html';
		}
		return $helpfile;
	}
	
}

?>