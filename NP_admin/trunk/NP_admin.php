<?php 
class NP_admin extends NucleusPlugin { 
	function getName() { return 'NP_admin'; }
	function getMinNucleusVersion() { return 220; }
	function getAuthor()  { return 'Katsumi'; }
	function getVersion() { return '0.1.9'; }
	function getURL() {return 'http://japan.nucleuscms.org/wiki/plugins:authors:katsumi';}
	function getDescription() { return $this->getName().' plugin'; } 
	function supportsFeature($what) { return ($what=='SqlTablePrefix')?1:0; }
	function getPluginDep() { return array('NP_SkinVarManager'); }
	function getEventList() { return array('InitSkinParse','RegisterSkinVars'); }
	var $main=false;
	function selector(){
		require_once(dirname(__FILE__).'/admin/main.php');
		$this->main=new NP_admin_main;
		return $this->main->selector();
	}
	function doSkinVar() {
		if (!$this->main) return;
		$args=func_get_args();
		return call_user_func_array(array(&$this->main,'doSkinVar'),$args);
	}
	function event_InitSkinParse(&$data){
		$skin=&$data['skin'];
		if (!$this->main) {
			// The skins whose names start from "admin" are only used as admin skin.
			if (preg_match('/^admin/i',$skin->getName())) exit('The skin is not valid as normal skin!');
			return;
		}
		// Only the skins whose names start from "admin" are used as admin skin.
		if (!preg_match('/^admin/i',$skin->getName())) exit('The skin is not valid as admin skin!');
		return $this->main->event_InitSkinParse($data);
	}
	function event_RegisterSkinVars(&$data) {
		if (!$this->main) return;
		return $this->main->event_RegisterSkinVars($data);
	}
}
