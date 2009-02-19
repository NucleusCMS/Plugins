<?php

class NP_admin_global {
	var $admin;
	function NP_admin_global(&$admin){
		// Import information
		$this->admin=&$admin;
		// Check the rights
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if (!$member->isAdmin()) exit(_ERROR_DISALLOWED);
	}
}