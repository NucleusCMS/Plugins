<?php

class NP_admin_category {
	var $admin;
	var $catid,$blogid;
	function NP_admin_category(&$admin,$catid){
		// Import information
		$this->admin=&$admin;
		$this->catid=$catid=(int)$catid;
		$query =  'SELECT cblog as result'
			   . ' FROM ' . sql_table('category')
			   . ' WHERE catid=' . $catid;
		$this->blogid=$blogid=(int)quickQuery($query);
		// Check the rights
		if (!$blogid) exit(_ERROR_DISALLOWED);
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if (!$member->blogAdminRights($blogid)) exit(_ERROR_DISALLOWED);
	}
}