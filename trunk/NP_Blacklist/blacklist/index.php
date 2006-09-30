<?php
    require_once("blacklist_lib.php");

    //
    // Nucleus Admin section;
    // Created by Xiffy
    //
 	$strRel = '../../../';
	include($strRel . 'config.php');

	include($DIR_LIBS . 'PLUGINADMIN.php');

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


	if (isset($_GET['page'])) {$action = $_GET['page'];}
	if (isset($_POST['page'])) {$action = $_POST['page'];}

	// Okay; we are allowed. let's go
	// create the admin area page
	$oPluginAdmin = new PluginAdmin('Blacklist');
	$oPluginAdmin->start();
	// get the plugin options; stored in the DB
    $pbl_config['enabled']       = $oPluginAdmin->plugin->getOption('enabled');
    $pbl_config['redirect']      = $oPluginAdmin->plugin->getOption('redirect');
    //$pbl_config['update']        = $oPluginAdmin->plugin->getOption('update');
    $pbl_config['referrerblock'] = $oPluginAdmin->plugin->getOption('referrerblock');
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

	pbl_nucmenu();
	if ($action == 'blacklist') {
	    pbl_blacklisteditor();
    	echo "</div>";
//	} elseif ($action == 'getblacklist') {
//       if (pbl_updateblacklist($pbl_config['update'],true))  {
//	    	$pblmessage = "Blacklist succesfully updated!";
//    	    pbl_blacklisteditor();
//        	echo "</div>";
//    	}
	} elseif ($action == 'addpersonal') {
    	pbl_addpersonal();
    	pbl_blacklisteditor();
    	echo "</div>";
	} elseif ($action == 'deleteexpression') {
    	pbl_deleteexpression();
	    echo "<div class=\"pblmessage\">Expression deleted from personal blacklist.</div>\n";
    	pbl_blacklisteditor();
    } elseif ($action == 'log') {
    	echo "<h2 style=\"text-align:left\"><span style=\"margin-left:10px;\">Blacklist: Blacklist Log</span></h2>";
	    echo "<div class=\"pbldescription\">This is your Blacklist logviewer. Each blocked spam attempt will end up in this overview.If you wish you can reset the log below.</div>\n";
    	pbl_logtable();
    } elseif ($action == 'resetlog') {
    	pbl_resetfile('log');
    	echo "<h2> logfile has been reset</h2>";
    	echo "<h2 style=\"text-align:left\"><span style=\"margin-left:10px;\">Blacklist: Blacklist Log</span></h2>";
	    echo "<div class=\"pbldescription\">This is your Blacklist logviewer. Each blocked spam attempt will end up in this overview.If you wish you can reset the log below.</div>\n";
    	pbl_logtable();
    } elseif ($action == 'testpage') {
    	echo "<h2>Test if an expression is considered spam</h2>";
        pbl_testpage();
    } elseif ($action == 'test') {
    	echo "<h2>Test if an expression is considered spam</h2>";
        pbl_test();
        pbl_testpage();
    } elseif ($action == 'showipblock') {
        echo "<h2>These ip-addresses are blocked</h2>";
        pbl_showipblock();
    } elseif ($action == 'addip') {
        pbl_addipblock();
        echo "<h2>These ip-addresses are blocked</h2>";
        pbl_showipblock();
    } elseif  ($action == 'deleteipblock') {
    	pbl_deleteipblock();
	    echo "<div class=\"pblmessage\">Block deleted</div>\n";
        echo "<h2>These ip-addresses are blocked</h2>";
        pbl_showipblock();
    } elseif ($action == 'htaccess') {
        echo "<h2>Here you can generate .htaccess snippets</h2>";
        pbl_htaccesspage();
    } elseif ($action == 'spamsubmission') {
		if( $_REQUEST['action'] == 'send' && !empty($_REQUEST['url']) ){
			$result = $oPluginAdmin->plugin->submitSpamToBulkfeeds($_REQUEST['url']);

			echo "<h2>Spam submission</h2>";
			echo "<h3>result</h3>";
			echo "<pre>" . htmlspecialchars($result) . "</pre>";
						
		} else {
			echo "<h2>Spam submission</h2>";
			pbl_spamsubmission_form();
		}
    }
    echo "<br />";
	echo "Based on pivot blacklist: <a style=\"border:0px; padding:0px; margin:10px;\" href=\"http://www.i-marco.nl/pivot-blacklist/\"><img style=\"border:0px\" src=\"".dirname($_SERVER['PHP_SELF'])."/pblbutton.png\" alt=\"Pivot Blacklist\"/></a><br/>";

	$oPluginAdmin->end();

?>
