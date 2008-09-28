<?php
/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)

	History
	-------
	v0.3   [2008/08/23] Improve javascript code.
	v0.21  [2007/06/07] 
*/


class NP_ItemFormat extends NucleusPlugin {

	function getName() { return 'Item Format'; }
	function getAuthor()  { return 'yu'; }
	function getURL() { return 'http://nucleus.datoka.jp/'; }
	function getVersion() { return '0.3'; }
	function getMinNucleusVersion() { return 250; }
	function getEventList() { return array('AdminPrePageHead','AdminPrePageFoot'); }

	function getDescription() { 
		return "Prepare item format. Fill form in category option.";
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createCategoryOption("title", "title", "text", "");
		$this->createCategoryOption("body", "body", "textarea", "");
		$this->createCategoryOption("more", "more", "textarea", "");
	}

	/*function event_PreAddItemForm(&$data) {
		$contents =& $data['contents'];
		
		if (! $contents['hasBeenSet']) {
			$contents['title'] = $this->getOption('title');
			$contents['body']  = $this->getOption('body');
			$contents['more']  = $this->getOption('more');
			$contents['hasBeenSet'] = 1;
		}
	}*/

	function doAction($type) {
		global $member;
		if (! $member->isLoggedIn()) return;
		
		switch ($type) {
		case 'get':
			$cid = intGetVar('cid');
			if ($cid < 1) return;
			
			$data = array();
			$data[]= $this->getCategoryOption($cid, 'title');
			$data[]= $this->getCategoryOption($cid, 'body');
			$data[]= $this->getCategoryOption($cid, 'more');
			echo @join("[[[ itemformat_splitter ]]]", $data);
			break;
		default:
			break;
		}
	}

	function event_AdminPrePageHead(&$data){
		global $CONF;
		$path = $CONF['PluginURL'];
		
		switch ($data['action']) {
		case 'createitem':
			$data['extrahead'] .= <<< EOS
<script type="text/javascript" src="{$path}itemformat/itemformat_js.php"></script>

EOS;
			break;
		default:
			return;
		}
		
		$this->ob_ok = ob_start();
	}

	function event_AdminPrePageFoot(){
		if (!$this->ob_ok) return;
		
		$html = ob_get_contents();
		ob_end_clean();
		
		// add event
		$target  = '<td><select name="catid"';
		$replace = '<td><select name="catid" onchange="plug_itemf_change(this)"';
		echo str_replace($target, $replace, $html);
	}

}
?>