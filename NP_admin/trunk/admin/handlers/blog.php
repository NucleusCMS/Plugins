<?php

class NP_admin_blog {
	var $admin;
	var $blogid;
	function NP_admin_blog(&$admin,$blogid){
		// Import information
		$this->admin=&$admin;
		$this->blogid=$blogid=(int)$blogid;
		// Check the rights
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if (!$member->blogAdminRights($blogid)) exit(_ERROR_DISALLOWED);
	}
}