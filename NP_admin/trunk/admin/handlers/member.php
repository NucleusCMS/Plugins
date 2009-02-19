<?php

class NP_admin_member {
	var $admin;
	var $mid;
	function NP_admin_member(&$admin,$mid){
		// Import information
		$this->admin=&$admin;
		$this->mid=(int)$mid;
		// Check the rights
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if ($mid==$member->getID()) return;
		if (!$member->isAdmin()) exit(_ERROR_DISALLOWED);
	}
}