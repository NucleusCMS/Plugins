<?php
// vim: tabstop=2:shiftwidth=2

/**
  * index.php ($Revision: 1.99 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: index.php,v 1.99 2010/06/06 11:44:19 hsur Exp $
*/

/*
  * Copyright (C) 2005-2010 CLES. All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

define('PAINT_INI_URL',	'http://blog.cles.jp/support/paint.ini');
define('PAINT_PLUGIN_DIR',	dirname(__FILE__)."/applet");

$strRel = '../../../';
include ($strRel.'config.php');
include ($DIR_LIBS.'PLUGINADMIN.php');

require_once($DIR_PLUGINS . 'sharedlibs/sharedlibs.php');
require_once('cles/Feedback.php');
require_once('cles/Template.php');
require_once('pclzip.lib.php');

if ($blogid) {
	$isblogadmin = $member->isBlogAdmin($blogid);
} else
	$isblogadmin = 0;

if (!$member->isLoggedIn()) {
	$oPluginAdmin = new PluginAdmin('Paint');
	$oPluginAdmin->start();
	echo '<p>' . _PAINT_NeedLogin . '</p>';
	$oPluginAdmin->end();
	exit;
}

if (!($member->isAdmin() || $isblogadmin)) {
	$oPluginAdmin = new PluginAdmin('Paint');
	$oPluginAdmin->start();
	echo "<p>"._ERROR_DISALLOWED."</p>";
	$oPluginAdmin->end();
	exit;
}

$action = requestVar('action');
$aActionsNotToCheck = array(
	'',
	'report',
);
if (!in_array($action, $aActionsNotToCheck)) {
	if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
}

// create the admin area page
$oPluginAdmin = new PluginAdmin('Paint');
$oPluginAdmin->start();
$fb =& new cles_Feedback($oPluginAdmin);

$templateEngine =& new cles_Template(dirname(__FILE__).'/template');
define('NP_PAINT_TEMPLATEDIR_INDEX', 'index');
$tplVars = array(
	'indexurl' => serverVar('PHP_SELF'),
	'optionurl' => $CONF['AdminURL'] . 'index.php?action=pluginoptions&amp;plugid=' . $oPluginAdmin->plugin->getid(),
	'paintiniurl' => PAINT_INI_URL,
	'paintplugindir' => PAINT_PLUGIN_DIR,
	'ticket' => $manager->_generateTicket(),
);

// get the plugin options; stored in the DB
//$pbl_config['enabled']       = $oPluginAdmin->plugin->getOption('enabled');

// menu
$menu = $templateEngine->fetch('menu', NP_PAINT_TEMPLATEDIR_INDEX);
echo $templateEngine->fill($menu, $tplVars, false);

//action
switch ($action) {
	case 'help' :
		echo $templateEngine->fetch('help', NP_PAINT_TEMPLATEDIR_INDEX);
		break;
		
	case 'install' :
		if( requestVar('func') == 'ini' ){
			$err = paint_plugin_download( PAINT_INI_URL, PAINT_PLUGIN_DIR);
			if($err){
				echo '<h3>Error: ' . _PAINT_canNotReadFile . ':' .PAINT_INI_URL . '</h3>';
				echo '<h4>Reason: '.$err.'</h4>';
				break;
			}
		}
		
		echo "<h2>"._PAINT_iniDownload."</h2>";
		echo "<ul>\n";
		echo "<li><a href=\"".htmlspecialchars($manager->addTicketToUrl(serverVar('PHP_SELF')."?action=install&func=ini"),ENT_QUOTES)."\">"._PAINT_doDownload."</a></li>\n";
		echo "</ul>\n";

		if( ! is_readable(PAINT_PLUGIN_DIR . '/paint.ini') )
			break;
		
		$settings = parse_ini_file(PAINT_PLUGIN_DIR . '/paint.ini', true);
		if( ! $settings ){
			echo '<h3>Error: ' . _PAINT_canNotReadFile . ': ' .PAINT_INI_URL . '</h3>';
			break;
		}
		
		echo '<h2>' . _PAINT_appletinstall. "( {$settings['paint']['version']}[{$settings['paint']['updated']}] )</h2>";

		if ($pluginName = getVar('plugin')) {
			$oPluginAdmin->plugin->_loadPlugin();
			$plugin = $oPluginAdmin->plugin->disabledPlugin[$pluginName];

			if ($plugin) {
				$zipurl = $settings[$pluginName]['zip'];
				if($zipurl)
					echo paint_plugin_install($zipurl);
				else
					echo '<h3>Error:' ._PAINT_canNotAutoInstall . '</h3>';

				} else {
				echo '<h3>Error:'. _PAINT_noSuchPlugin . '</h3>';
			}
		}

		$oPluginAdmin->plugin->_loadPlugin(false, true);
		ksort($oPluginAdmin->plugin->disabledPlugin);

		echo '<h3>'._PAINT_autoInstall.'</h3>';
		echo "<ul>\n";
		foreach ($oPluginAdmin->plugin->disabledPlugin as $key => $plugin) {
			if ( $settings[$key]['zip'] )
				echo "<li><a href=\"".htmlspecialchars($manager->addTicketToUrl(serverVar('PHP_SELF')."?action=install&plugin=".$key),ENT_QUOTES)."\">".$plugin->getName()._PAINT_installSuffix."</a></li>\n";
			elseif ( $settings[$key]['file'] )
				echo "<li><em>".$plugin->getName(). _PAINT_canNotAutoInstall ."<br /><a href=\"{$settings[$key]['file']}\">{$settings[$key]['file']}</a>"._PAINT_downloadSuffix."</em></li>\n";
			else
				echo "<li><em>".$plugin->getName(). _PAINT_canNotAutoInstall . "</em></li>\n";
		}
		echo "</ul>\n";

		break;

	case 'report' :
		$oPluginAdmin->plugin->_loadPlugin();
		$extra = '';
		foreach ($oPluginAdmin->plugin->parsers as $key => $name) {
			$extra .= 'parser_'.$key.', ';
		}
		foreach ($oPluginAdmin->plugin->applets as $key => $name) {
			$extra .= 'applet_'.$key.', ';
		}
		foreach ($oPluginAdmin->plugin->palettes as $key => $name) {
			$extra .= 'palette_'.$key.', ';
		}
		foreach ($oPluginAdmin->plugin->viewers as $key => $name) {
			$extra .= 'viewer_'.$key.', ';
		}
		$fb->printForm($extra);

		break;

	default :
		break;
}

echo "<br />";

$oPluginAdmin->end();

function paint_plugin_install($url) {
	$err = paint_plugin_download($url, PAINT_PLUGIN_DIR);
	if ($err)
		return $err;

	$file = PAINT_PLUGIN_DIR . '/' . basename($url);
	$err = paint_plugin_extract($file, PAINT_PLUGIN_DIR);

	return $err;
}

function paint_plugin_extract($file, $dir) {
	$archive = new PclZip($file);
	if ($archive->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_SET_CHMOD, 0777) == 0) {
		return $archive->errorInfo(true);
	}
	return '';
}

function paint_plugin_download($url, $dir) {
	if (!@ is_writable($dir) || !@ is_dir($dir)) {
		return _Paint_directoryNotWriteable . $dir;
	}

	$remote = fopen($url, "rb");
	socket_set_timeout($remote, 10);
	if (!$remote)
		return _PAINT_fileOpen_failure . ': '.$url;

	$file = $dir.'/'.basename($url);
	$local = fopen($file, "wb");
	socket_set_timeout($local, 5);
	if (!$local)
		return _PAINT_fileOpen_failure . ': '.$file;

	while (!feof($remote)) {
		$buffer = fgets($remote, 4096);
		fputs($local, $buffer);
	}

	fclose($remote);
	fclose($local);

	return '';
}
