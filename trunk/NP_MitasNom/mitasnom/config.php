<?php
/*
 * NP_MitasNom
 * This library is GPL
 */

$strRel = '../../../'; 
require($strRel . 'config.php');
if (!$member->isLoggedIn()) exit('Not logged in.');
np_mitasnom_config();

function np_mitasnom_config(){
	global $CONF,$manager;

	$CustomConfig = "";

	// create the plugin object
	$p=&$manager->getPlugin('NP_MitasNom');
	
	// Setting media dialog stuff
	$MediaURL=$CONF['MediaURL'];
	$NucleusMediaDirValue=$MediaURL;
	$NucleusMediaWindowWidth=trim($p->this_getOption('dialogwidth'));
	$NucleusMediaWindowHeight=trim($p->this_getOption('dialogheight'));
	if ($p->this_getOption('useimagemanager')=='yes') {
		$NucleusMediaWindowURL=$CONF['AdminURL'].'plugins/imagemanager/manager.php';
		$NucleusUseImageManagerValue = 'true';
	
	} else {
		$NucleusMediaWindowURL=$CONF['AdminURL'].'media.php';
		$NucleusUseImageManagerValue = 'false';
	}


	if ($p->this_getOption('usep')=='yes') {
		$EnterModeValue='p';
		$ShiftEnterModeValue='br';
	} else {
		$EnterModeValue='br';
		$ShiftEnterModeValue='p';
	}
	
	
	
	// ProtectedSource
	$ProtectedSources="/\\x0A/g\n";
	$ProtectedSources.=$p->getOption('protectedsource')."\n";
	$ProtectedSources.=$p->this_getOption('additionalpsource');
	$ProtectedSources=str_replace("\n","\x0A",$ProtectedSources);
	$ProtectedSources=str_replace("\x0D","\x0A",$ProtectedSources);
	foreach ( explode ("\x0A",$ProtectedSources) as $value) 
		if ($value) $ProtectedSourceAddValue = $value;
	// Custom toolbar
	$toolbar=$p->this_getOption('toolbar');
	switch (strtolower($toolbar)) {
	case 'default':
	case 'full':
	case 'basic':
		break;
	default:
		$ToolbarSets = 'FCKConfig.ToolbarSets["'.$toolbar.'"] = ['.
			$p->getOption('toolbar_'.strtolower($toolbar))."];\n";
		break;
	}



	$CustomConfig .= <<< CONFIG_END

	var NucleusMediaWindowWidth=500;
	var NucleusMediaWindowHeight=450;
	var NucleusMediaWindowURL="../../../../media.php";
	var NucleusMediaDir="media/";

	FCKConfig.NucleusMediaDir='$NucleusMediaDirValue';
	FCKConfig.NucleusUseImageManager = $NucleusUseImageManagerValue;
	NucleusMediaWindowWidth=$NucleusMediaWindowWidth;
	NucleusMediaWindowHeight=$NucleusMediaWindowHeight;
	NucleusMediaWindowURL='$NucleusMediaWindowURL';
	FCKConfig.EnterMode = '$EnterModeValue' ;
	FCKConfig.ShiftEnterMode = '$ShiftEnterModeValue' ;
	FCKConfig.ProtectedSource.Add($ProtectedSourceAddValue);
	FCKConfig.LinkBrowserWindowWidth='$NucleusMediaWindowWidth';
	FCKConfig.LinkBrowserWindowHeight='$NucleusMediaWindowHeight';
	FCKConfig.LinkBrowserURL='$NucleusMediaWindowURL';
	FCKConfig.ImageBrowserWindowWidth='$NucleusMediaWindowWidth';
	FCKConfig.ImageBrowserWindowHeight='$NucleusMediaWindowHeight';
	FCKConfig.ImageBrowserURL='$NucleusMediaWindowURL';
	FCKConfig.FlashBrowserWindowWidth='$NucleusMediaWindowWidth';
	FCKConfig.FlashBrowserWindowHeight='$NucleusMediaWindowHeight';
	FCKConfig.FlashBrowserURL='$NucleusMediaWindowURL';
	FCKConfig.LinkUpload = false ;
	FCKConfig.ImageUpload = false ;
	FCKConfig.FlashUpload = false ;
	$ToolbarSets;
//	FCKConfig.Plugins.Add('nucleus');

	FCKConfig.ToolbarSets["Default"] = [
	['Source','Save','Preview'],
	['Cut','Copy','Paste'],
	['Undo','Redo','Find','Replace','RemoveFormat'],
//	['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Table','Smiley','SpecialChar'],
	'/',
	['Style','FontFormat'],['FontName','FontSize'],
	['TextColor'],
	['FitWindow']
];

	FCKConfig.ToolbarSets["Full"] = [
	['Source','DocProps','-','Save','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
//	'/',
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak'],
	'/',
	['Style','FontFormat','FontName','FontSize'],
	['TextColor','BGColor'],
	['FitWindow','ShowBlocks','-','About']
] ;

FCKConfig.ToolbarSets["Basic"] = [
	['Source','Save','Preview','-','RemoveFormat','-','Bold','Italic','-','Link','Unlink','Image','FitWindow']
] ;

FCKConfig.ContextMenu = ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','TableCell','Table','Form'] ;

FCKConfig.FontFormats	= 'p;div;pre;address;h2;h3;h4;h5;h6' ;

FCKConfig.NucleusMediaDir="$NucleusMediaDirValue";


CONFIG_END;

	echo $CustomConfig;
}
?>