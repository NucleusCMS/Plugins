<?php
    //
    // Nucleus Admin section;
    // Created by Xiffy
    //
 	$strRel = '../../../';
	include($strRel . 'config.php');

	include($DIR_LIBS . 'PLUGINADMIN.php');
	require_once($DIR_PLUGINS . 'sharedlibs/sharedlibs.php');
	require_once('cles/Template.php');
	require_once('cles/Feedback.php');

	if ($blogid) {$isblogadmin = $member->isBlogAdmin($blogid);}
	else $isblogadmin = 0;

	if (!($member->isAdmin() || $isblogadmin)) {
		$oPluginAdmin = new PluginAdmin('Blacklist');
		$pbl_config = array();
		$oPluginAdmin->start();
		echo "<p>"._ERROR_DISALLOWED."</p>";
		$oPluginAdmin->end();
		exit;
	}
	
	$action = requestVar('action');
	$aActionsNotToCheck = array(
		'',
	);
	if (!in_array($action, $aActionsNotToCheck)) {
		if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
	}

	// Okay; we are allowed. let's go
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('Blacklist');
	$oPluginAdmin->start();
	$fb =& new cles_Feedback($oPluginAdmin);
	
	// get the plugin options; stored in the DB
    $pbl_config['enabled']       = $oPluginAdmin->plugin->getOption('enabled');
    $pbl_config['redirect']      = $oPluginAdmin->plugin->getOption('redirect');
    $pbl_config['ipblock']       = $oPluginAdmin->plugin->getOption('ipblock');
    $pbl_config['ipthreshold']   = $oPluginAdmin->plugin->getOption('ipthreshold');
    $pbl_config['BulkfeedsKey']   = $oPluginAdmin->plugin->getOption('BulkfeedsKey');
    $pbl_config['SkipNameResolve']   = $oPluginAdmin->plugin->getOption('SkipNameResolve');

	function getPluginOption($name) {
	    global $pbl_config;
	    return $pbl_config[$name];
	}
	function getPlugid() {
	    global $oPluginAdmin;
	    return $oPluginAdmin->plugin->plugid;
	}
	
	$templateEngine =& new cles_Template(dirname(__FILE__).'/template');
	$templateEngine->defaultLang = 'english';
	define('NP_BLACKLIST_TEMPLATEDIR_INDEX', 'index');
	$tplVars = array(
		'indexurl' => serverVar('PHP_SELF'),
		'itemperpage' => '20',
		'optionurl' => $CONF['AdminURL'] . 'index.php?action=pluginoptions&amp;plugid=' . $oPluginAdmin->plugin->getid(),
		'actionurl' => $CONF['ActionURL'],
		'ticket' => $manager->_generateTicket(),
		'plugindirurl' => $oPluginAdmin->plugin->getAdminURL(),
		'message' => '',
	);
	
	// show menu
	$menu = $templateEngine->fetch('menu', NP_BLACKLIST_TEMPLATEDIR_INDEX);
	echo $templateEngine->fill($menu, $tplVars, false);
	
	// do aciton
	switch($action){
		case 'addpersonal':
			$tplVars['message'] = pbl_addpersonal();
			$action = 'blacklist';
			break;
		case 'deleteexpression':
			$tplVars['message'] = pbl_deleteexpression();
			$action = 'blacklist';
			break;

		case 'resetlog':
			$tplVars['message'] = pbl_resetfile('log');
			$action = 'log';
			break;
		
		case 'test':
			$tplVars['message'] = pbl_test();	
			$action = 'testpage';
			break;
			
		case 'addipblock':
			$tplVars['message'] = pbl_addIp();
			$action = 'showipblock';
			break;
			
		case 'deleteipblock':
			$tplVars['message'] = pbl_deleteIp();
			$action = 'showipblock';
			break;

		case 'addipwhite':
			$tplVars['message'] = pbl_addIp('white');
			$action = 'showipwhite';
			break;
		
		case 'deleteipwhite':
			$tplVars['message'] = pbl_deleteIp('white');
			$action = 'showipwhite';
			break;
		
		case 'htaccess':
			$type = '';
			if (isset ($_POST["type"])) {
				$type = (strpos(postVar("type"), "ip") !== false) ? 'ip' : 'rules'; 
			}
			if (strpos(postVar("type"), "reset") !== false ) {
				$tplVars['message'] = pbl_resetfile($type);
			}
			$tplVars['snippet'] = pbl_htaccess($type);
			break;

		default:
			break;
	}
	
	global $pblmessage;
	if($pblmessage)
		$tplVars['message'] .= '<div class="pblmessage">'.$pblmessage.'</div>';
		// show content
	$content = '';
	switch($action){
		case 'blacklist':
			$content = $templateEngine->fetch('blacklisteditor_header', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, false);
			pbl_blacklisteditor();
			$content = $templateEngine->fetch('blacklisteditor_footer', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;
		
		case 'log':
			$files = pbl_getlogfiles();
			$no = intPostVar("no");
			if( !$no ) $no = 0;

			if( $files[($no + 1)] )  {
				$tplVars['prev_submit'] = 'submit';
				$tplVars['prev_no'] = $no + 1;
			} else {
				$tplVars['prev_submit'] = 'hidden';
			}
			
			if( $files[($no - 1)] )  {
				$tplVars['next_submit'] = 'submit';
				$tplVars['next_no'] = $no - 1;
			} else {
				$tplVars['next_submit'] = 'hidden';
			}
			
			$content = $templateEngine->fetch('logtable_header', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, null);
			pbl_logtable($no);
			$content = $templateEngine->fetch('logtable_footer', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;
		
		case 'testpage':
			$content = $templateEngine->fetch('testpage', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;
		
		case 'showipblock':
			$content = $templateEngine->fetch('ipblock_header', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, null);
			pbl_showIp();
			$content = $templateEngine->fetch('ipblock_footer', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;
			
		case 'showipwhite':
			$content = $templateEngine->fetch('ipwhite_header', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			echo $templateEngine->fill($content, $tplVars, null);
			pbl_showIp('white');
			$content = $templateEngine->fetch('ipwhite_footer', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;

		case 'htaccess':
			$content = $templateEngine->fetch('htaccess', NP_BLACKLIST_TEMPLATEDIR_INDEX);
			break;
			
		case 'report':
			$content = '';
			$fb->printForm();
			break;
			
		default:
			break;
	}
	echo $templateEngine->fill($content, $tplVars, null);

	// show footer
	$footer = $templateEngine->fetch('footer', NP_BLACKLIST_TEMPLATEDIR_INDEX);
	echo $templateEngine->fill($footer, $tplVars, false);
	
	$oPluginAdmin->end();
