<?php

class NP_admin_item {
	var $admin;
	var $itemid,$catid,$blogid;
	function NP_admin_item(&$admin,$itemid){
		// Import information
		$this->admin=&$admin;
		$this->itemid=$itemid=(int)$itemid;
		$query =  'SELECT iblog, icat'
			   . ' FROM ' . sql_table('item')
			   . ' WHERE inumber=' . $itemid;
		$row=mysql_fetch_assoc(sql_query($query));
		if (!$row) exit(_ERROR_DISALLOWED);
		$this->blogid=$blogid=(int)$row['iblog'];
		$this->catid=$catid=(int)$row['icat'];
		// Check the rights
		if (!$blogid || !$catid) exit(_ERROR_DISALLOWED);
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if ($member->blogAdminRights($blogid)) return; // Is this right way?
		if (!$member->teamRights($blogid)) exit(_ERROR_DISALLOWED);
	}
}