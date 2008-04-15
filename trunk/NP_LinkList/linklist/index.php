<?php
/*
	NP_LinkList (based on NP_LinksByBlog)
	by Jim Stone
	   fel
	   nakahara21 (http://nakahara21.com)
	   yu (http://nucleus.datoka.jp/)
	
	index.php (admin page)
	----------------------
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
*/

$strRel = '../../../'; 
include($strRel . 'config.php');
include($DIR_LIBS . 'PLUGINADMIN.php');
include('functions.php');

$language = ereg_replace( '[\\|/]', '', getLanguageName());
$langfile = $language.'.php';
if (file_exists($langfile))
	include_once($langfile);
else
	include_once('english.php');

/**
  * Create admin area
  */

$oPluginAdmin = new PluginAdmin('LinkList');
$pluginUrl    = $oPluginAdmin->plugin->getAdminURL();

switch ( $oPluginAdmin->plugin->getOption('sel_edit') ) {
case 'siteadmin':
	$flg_edit = $member->isAdmin();
	break;
case 'blogadmin':
	$flg_edit = $oPluginAdmin->plugin->_isBlogAdmin();
	break;
case 'blogteam':
	$flg_edit = $oPluginAdmin->plugin->_isBlogTeam();
	break;
}


if ( !($member->isLoggedIn() and $flg_edit) )
{
	$oPluginAdmin->start();
	echo '<p>' . _ERROR_DISALLOWED . '</p>';
	$oPluginAdmin->end();
	exit;
}

$oPluginAdmin->plugin->init_grp(true);

$oPluginAdmin->start("
<script type='text/javascript' src='../../javascript/numbercheck.js'></script>
<script type='text/javascript'>
<!--

function confirm_check(message)  {
  if( confirm(message) ){
    sent = true;
    return true;
  }
  else {
    return false;
  }
}

// -->
</script>
<style type='text/css'>
<!--
p.message {
	font-weight: bold;
	color: #c00;
}
form.button {
	display: inline;
}
table.group {
	margin: 5px 0;
}
table.group td {
	background-color: #ddd;
}
table.link {
	margin: 0;
}
table.link th {
	/*background-color: #ddd;*/
}
table.link td.stripe {
	background-color: #eee;
}
-->
</style>
");

echo "<h2>" . _LINKLIST_ADMIN_STR1 . "</h2>";

$action = requestVar('action');
$actions = array (
	'index',
	'detail',
	'add',
	'modify',
	'quickmod',
	'delete',
	'dbupdate',
);

if (in_array($action, $actions)) 
{ 
	if (!$manager->checkTicket())
	{
		echo '<p class="error">Error: ' . _ERROR_BADTICKET . '</p>';
	} 
	else 
	{
		call_user_func('_linklist_' . $action);
	}
} 
else 
{
	if ($member->isAdmin() and !$oPluginAdmin->plugin->_checkColumn()) { //update DB
		_linklist_updateForm();
	}
	else {
		_linklist_index();
	}
}

$oPluginAdmin->end();
exit;


?>
