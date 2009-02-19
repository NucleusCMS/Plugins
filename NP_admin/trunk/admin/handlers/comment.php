<?php

class NP_admin_comment {
	var $admin;
	var $cid,$blogid,$itemid;
	function NP_admin_comment(&$admin,$cid){
		// Import information
		$this->admin=&$admin;
		$this->cid=$cid=(int)$cid;
		$query =  'SELECT cblog, citem, iauthor'
			   . ' FROM ' . sql_table('comment') . ', ' . sql_table('item')
			   . ' WHERE cnumber=' . $cid
			   . ' AND inumber=citem';
		$row=mysql_fetch_assoc(sql_query($query));
		if (!$row) exit(_ERROR_DISALLOWED);
		$this->blogid=$blogid=(int)$row['cblog'];
		$this->itemid=$itemid=(int)$row['citem'];
		$mid=(int)$row['iauthor'];
		// Check the rights
		if (!$blogid || !$itemid) exit(_ERROR_DISALLOWED);
		global $member;
		if (!$member->isLoggedIn()) exit(_ERROR_DISALLOWED);
		if ($member->blogAdminRights($blogid)) return; // Is this right way?
		if (!$member->teamRights($blogid)) exit(_ERROR_DISALLOWED);
		if ($mid!=$member->getID()) exit(_ERROR_DISALLOWED);
	}
}