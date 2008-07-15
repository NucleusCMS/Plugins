<?php
/*
	NP_SimpleURL admin area
*/

$strRel = '../../../'; 
include($strRel . 'config.php');
include($DIR_LIBS . 'PLUGINADMIN.php');

$oPluginAdmin = new PluginAdmin('SimpleURL');
$oPluginAdmin->plugin->_makeAdminArea(&$oPluginAdmin);

?>
