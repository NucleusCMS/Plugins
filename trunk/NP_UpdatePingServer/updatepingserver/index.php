<?php
/**
  * NP_UpdatePingServer Management Page
  *     Taka ( http://spectra.reverb.jp/ ) 2006-08-23
  */

	// if your 'plugin' directory is not in the default location,
	// edit this variable to point to your site directory
	// (where config.php is)
	$strRel = '../../../';

	require($strRel . 'config.php');

	if (!$member->isLoggedIn())
		doError('You\'re not logged in.');

	require($DIR_LIBS . 'PLUGINADMIN.php');
	require('./admin/PlugView.php');
	require('./admin/PlugTemplate.php');
	require('./admin/PlugManagement.php');
	require('./admin/PlugController.php');
	require('./admin/UpdatePingServer_Management.php');

	$MyTagsController = new PlugController('UpdatePingServer');
	$MyTagsController->forward(requestVar('action'));
