<?php
/*
	Quick Menu
	by yu (http://nucleus.datoka.jp)
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
*/


class NP_QuickMenu extends NucleusPlugin {

	function getName() { return 'QuickMenu'; }
	function getAuthor()  { return 'yu'; }
	function getURL() { return 'http://nucleus.datoka.jp/'; }
	function getVersion() { return '0.1'; }
	function getMinNucleusVersion() { return '250'; }
	function getEventList() { return array('QuickMenu'); }	
	function getTableList() { return array(); }

	function getDescription() { 
		return "You can make links in Quick Menu. ";
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function init() {
		$this->max = 5;
	}
	
	function install() {
		//set initial value
		$title[1]   = "NucleusCMS(JP)";
		$url[1]     = "http://japan.nucleuscms.org/";
		$tooltip[1] = "Nucleus CMS Japanese Official";
		for ($i = 1; $i <= $this->max; $i++) {
			$this->createOption("title{$i}",   "Title {$i}",   "text", $title[$i]);
			$this->createOption("url{$i}",     "URL {$i}",     "text", $url[$i]);
			$this->createOption("tooltip{$i}", "ToolTip {$i}", "text", $tooltip[$i]);
		}
	}

	function uninstall() {
	}

	function event_QuickMenu(&$data) {
		global $member;
		
		// only show to admins
		if (!($member->isLoggedIn() && $member->isAdmin())) return;
		
		for ($i = 1; $i <= $this->max; $i++) {
			if ( $this->getOption("title{$i}") and $this->getOption("url{$i}") ) {
				array_push(
					$data['options'], 
					array(
						'title'   => $this->getOption("title{$i}"),
						'url'     => $this->getOption("url{$i}"),
						'tooltip' => $this->getOption("tooltip{$i}")
					)
				);
			}
		}
	}

}
?>
