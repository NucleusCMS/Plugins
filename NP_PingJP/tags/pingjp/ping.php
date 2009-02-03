<?php
require(dirname(__FILE__).'/../../../config.php');

include($DIR_LIBS . 'PLUGINADMIN.php');

// create a object of the plugin via Plugin Admin
$oPluginAdmin = new PluginAdmin('Ping');

$blogid = intval($argv[1]);
if ($blogid > 0) {
	$oPluginAdmin->plugin->sendPings($blogid, 2);
} else {
	ACTIONLOG::add(WARNING, 'NP_Ping: invalid blogid, background ping abort');
}