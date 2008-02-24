<?php
/*
 * NP_MitasNom
 * Written by Katsumi
 * This library is GPL
 */

	$strRel = '../../../'; 
	require($strRel . 'config.php');

	// create the admin area page
	if (!$member->isLoggedIn()) exit('Not logged in.');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	$oPluginAdmin = new PluginAdmin('MitasNom');
	$p=&$oPluginAdmin->plugin;
	
	// Setting media dialog stuff
	$MediaURL=$CONF['MediaURL'];
	echo "FCKConfig.NucleusMediaDir='$MediaURL';\n";
	$NucleusMediaWindowWidth=trim($p->this_getOption('dialogwidth'));
	$NucleusMediaWindowHeight=trim($p->this_getOption('dialogheight'));
	if ($p->this_getOption('useimagemanager')=='yes') {
		$NucleusMediaWindowURL=$CONF['AdminURL'].'plugins/imagemanager/manager.php';
		echo "FCKConfig.NucleusUseImageManager=true;\n";
	
	} else {
		$NucleusMediaWindowURL=$CONF['AdminURL'].'media.php';
		echo "FCKConfig.NucleusUseImageManager=false;\n";
	}
	if ($p->this_getOption('usep')=='yes') echo "FCKConfig.UseBROnCarriageReturn=false;\n";
	
	echo "FCKConfig.LinkBrowserWindowWidth='$NucleusMediaWindowWidth';\n";
	echo "FCKConfig.LinkBrowserWindowHeight='$NucleusMediaWindowHeight';\n";
	echo "FCKConfig.LinkBrowserURL='$NucleusMediaWindowURL';\n";
	echo "FCKConfig.ImageBrowserWindowWidth='$NucleusMediaWindowWidth';\n";
	echo "FCKConfig.ImageBrowserWindowHeight='$NucleusMediaWindowHeight';\n";
	echo "FCKConfig.ImageBrowserURL='$NucleusMediaWindowURL';\n";
	echo "FCKConfig.FlashBrowserWindowWidth='$NucleusMediaWindowWidth';\n";
	echo "FCKConfig.FlashBrowserWindowHeight='$NucleusMediaWindowHeight';\n";
	echo "FCKConfig.FlashBrowserURL='$NucleusMediaWindowURL';\n";
	
	// ProtectedSource
	$ProtectedSources="/\\x0A/g\n";
	$ProtectedSources.=$p->getOption('protectedsource')."\n";
	$ProtectedSources.=$p->this_getOption('additionalpsource');
	$ProtectedSources=str_replace("\n","\x0A",$ProtectedSources);
	$ProtectedSources=str_replace("\x0D","\x0A",$ProtectedSources);
	foreach ( explode ("\x0A",$ProtectedSources) as $value) 
		if ($value) echo "FCKConfig.ProtectedSource.Add($value);\n";
		
	// Custom toolbar
	$toolbar=$p->this_getOption('toolbar');
	switch (strtolower($toolbar)) {
	case 'default':
	case 'full':
	case 'basic':
		break;
	default:
		echo 'FCKConfig.ToolbarSets["'.$toolbar.'"] = ['.
			$p->getOption('toolbar_'.strtolower($toolbar))."];\n";
		break;
	}
	
?>