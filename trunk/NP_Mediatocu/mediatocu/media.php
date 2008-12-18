<?php
/*
 * Nucleus: PHP/MySQL Weblog CMS (http://nucleuscms.org/)
 * Copyright (C) 2002-2007 The Nucleus Group
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 */
/**
 * Media popup window for Nucleus
 *
 * Purpose:
 *   - can be openen from an add-item form or bookmarklet popup
 *   - shows a list of recent files, allowing browsing, search and
 *     upload of new files
 *   - close the popup by selecting a file in the list. The file gets
 *     passed through to the add-item form (linkto, popupimg or inline img)
 *
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2002-2007 The Nucleus Group
 * @version $Id: media.php 1116 2007-02-03 08:24:29Z kimitake $
 * $NucleusJP: media.php,v 1.4 2007/03/27 12:13:47 kimitake Exp $
 *
 */



if (!defined('_MEDIA_PHP_DEFINED')) {
	define('_MEDIA_PHP_DEFINED', 1);
	// mymbmime choice.
}
// add definition end
$CONF = array();

// defines how much media items will be shown per page. You can override this
// in config.php if you like. (changing it in config.php instead of here will
// allow your settings to be kept even after a Nucleus upgrade)


//rem yama 20070928 $CONF['MediaPerPage'] = $media_per_page;

$Prefix_thumb = "thumb_";

// include all classes and config data
require('../../../config.php');

// added yama20070928
$mediatocu            = $manager->getPlugin('NP_Mediatocu');
$media_per_page       = $mediatocu->getOption('media_per_page');
$CONF['MediaPerPage'] = $media_per_page;
// end yama20070928

include($DIR_LIBS . 'MEDIA.php');	// media classes
/*
 * Start append by T.Kosugi
 * Reffering /lib/MEDIA.php (v3.22)
 */
class MEDIADIRS extends MEDIA
{
	function MEDIADIRES()
	{
		//$this->MEDIA();
	}

	function getCollectionList()
	{
		global $member, $DIR_MEDIA, $manager;

		$collections = array();
		/* edit T.Kosugi 2006/09/06
		// add private directory for member
		$collections[$member->getID()] = _MEDIA_PHP_32;
		*/
/*
		$searchDir   = '/';
		$prefix      = $member->getID();
		$collections = array_merge($collections, (array)MEDIADIRS::traceCorrectionDir($searchDir, $prefix, _MEDIA_PHP_32));
*/
		// add global collections
		if (!is_dir($DIR_MEDIA)) {
			return $collections;
		}

		$dirhandle = opendir($DIR_MEDIA);
		while ($dirname = readdir($dirhandle)) {
			// only add non-numeric (numeric=private) dirs
			if (@is_dir($DIR_MEDIA . $dirname) && ($dirname != '.') && ($dirname != '..') && ($dirname != 'CVS') && (!is_numeric($dirname))) {
				$collections[$dirname] = $dirname;
/*
				$searchDir             = '/';
				$prefix                = $dirname;
				$collections           = array_merge($collections, (array)MEDIADIRS::traceCorrectionDir($searchDir, $prefix, $dirname));
*/
			}
		}
		closedir($dirhandle);
		// add T.Kosugi 2006/09/06
		// add private directory for member
//		$collections[$member->getID()] = _MEDIA_PHP_32;
		// add end
//		ksort($collections, SORT_STRING);

		$hiddendir = array();
		$mediatocu = $manager->getPlugin('NP_Mediatocu');
		$hiddendir = explode(',', $mediatocu->getOption('hidden_dir'));
		foreach ($hiddendir as $value)
		{
			$value = trim($value);
			unset($collections["$value"]);
		}
		foreach ($collections as $value)
		{
				$searchDir             = '/';
				$prefix                = $value;
				$collections           = array_merge($collections, (array)MEDIADIRS::traceCorrectionDir($searchDir, $prefix, $value));
		}
		$collections[$member->getID()] = _MEDIA_PHP_32;
		$searchDir   = '/';
		$prefix      = $member->getID();
		$collections = array_merge($collections, (array)MEDIADIRS::traceCorrectionDir($searchDir, $prefix, _MEDIA_PHP_32));
		ksort($collections, SORT_STRING);
		return $collections;

	}

	function getPrivateCollectionList()
	{
		global $member, $DIR_MEDIA;

		$collections = array();
		$prefix      = $member->getID();
		$searchDir   = '/';
		$collections = MEDIADIRS::traceCorrectionDir($searchDir, $prefix, _MEDIA_PHP_32);
		// add private directory for member
		$collections[$prefix] = _MEDIA_PHP_32;
		ksort($collections, SORT_STRING);
		return $collections;
	}

	function traceCorrectionDir($searchDir, $prefix ='', $preName)
	{
		global $DIR_MEDIA;
		$collections = array();		//http://japan.nucleuscms.org/bb/viewtopic.php?p=21230&highlight=#21230
		$dirhandle   = @opendir($DIR_MEDIA . $prefix . $searchDir);
		if (!$dirhandle) {
			return;
		}
		while ($dirname = readdir($dirhandle)) {
			// only add non-numeric (numeric=private) dirs
			if (@is_dir($DIR_MEDIA . $prefix . $searchDir . $dirname) && ($dirname != '.') && ($dirname != '..') && ($dirname != 'CVS'))  {
				$collections[$prefix . $searchDir . $dirname] = $preName . $searchDir . $dirname;
				$collections = array_merge($collections, (array)MEDIADIRS::traceCorrectionDir($searchDir . $dirname . '/', $prefix, $preName));
			}
		}
		closedir($dirhandle);

		return $collections;
	}

	function getMediaListByCollection($collection, $filter = '')
	{
		global $DIR_MEDIA;

		$filelist = array();

		// 1. go through all objects and add them to the filelist

		$mediadir = $DIR_MEDIA . $collection . '/';

		// return if dir does not exist
		if (!is_dir($mediadir)) {
			return $filelist;
		}

		$dirhandle = opendir($mediadir);
		while ($filename = readdir($dirhandle)) {
			// only add files that match the filter
			if (!@is_dir($mediadir . $filename) && MEDIA::checkFilter($filename, $filter)) {
				array_push($filelist, new MEDIAOBJECT($collection, $filename, filemtime($mediadir . $filename)));
			}
		}
		closedir($dirhandle);

		// sort array so newer files are shown first
		usort($filelist, 'sort_media');

		return $filelist;
	}
}

/*
 * End append by T.Kosugi
 */
sendContentType('application/xhtml+xml', 'media');

// user needs to be logged in to use this
if (!$member->isLoggedIn()) {
	media_loginAndPassThrough();
	exit;
}

// check if member is on at least one teamlist
$query = 'SELECT * FROM ' . sql_table('team'). ' WHERE tmember=' . $member->getID();
//$teams = mysql_query($query);
$teams = sql_query($query);
if (mysql_num_rows($teams) == 0) {
	media_doError(_ERROR_DISALLOWEDUPLOAD);
}

// get action
$action = requestVar('action');
if ($action == '') {
	$action = 'selectmedia';
}

// check ticket
$aActionsNotToCheck = array('selectmedia', _MEDIA_PHP_30, _MEDIA_COLLECTION_SELECT);
if (!in_array($action, $aActionsNotToCheck)) {
	if (!$manager->checkTicket()) {
		media_doError(_ERROR_BADTICKET);
	}
}

		/*-------kei edit & append 2005.5.26-------*/
// <080213 fix $_POST to postVar by shizuki>
//if ($_POST[targetthumb]) {//}
if (postVar('targetthumb')) {
	// Check if the collection is valid.
	if (!MEDIA::isValidCollection(postVar('currentCollection'))) media_doError(_ERROR_DISALLOWED);
//	$mediapath = $DIR_MEDIA . $_POST[currentCollection] . "/";
	$mediapath = $DIR_MEDIA . postVar('currentCollection') . "/";
//	switch ($_POST[myaction]) {//}
	switch (postVar('myaction')) {
		case _MEDIA_PHP_1:
//			$msg1 = unlink($mediapath . $_POST[targetfile]);
			$msg1 = media_unlink($mediapath, postVar('targetfile'));
			if (!$msg1) {
				print htmlspecialchars(postVar('targetfile') . _MEDIA_PHP_2);
			}
//			$exist = file_exists($mediapath . $_POST[targetthumb]);
			$exist = file_exists($mediapath . postVar('targetthumb'));
			if ($exist) {
//				$msg2 = unlink($mediapath.$_POST[targetthumb]);
				$msg2 = media_unlink($mediapath, postVar('targetthumb'));
				if (!$msg2) {
//					print $_POST[targetthumb] . _MEDIA_PHP_2;
					print htmlspecialchars(postVar('targetthumb') . _MEDIA_PHP_2);
				}
			}
			break;
		case _MEDIA_PHP_3:
			//chmod($mediapath.$targetfile, 706);
			/*
			T.Kosugi edit 2006.8.22
			*/
			// check file type against allowed types
//			$newfilename = $_POST[newname];
			$newfilename = postVar('newname');
			// T.Kosugi add 2006.9.1
			if (stristr($newfilename, '%00')) {
				media_doError(_MEDIA_PHP_38);
			}
			// T.Kosugi add end
			$ok = 0;
			$allowedtypes = explode (',', $CONF['AllowedTypes']);
			foreach ($allowedtypes as $type) {
				if (eregi("\." . $type . "$", $newfilename)) {
					$ok = 1;
				}
			}
//TODO:allow only the allowed media files
			if (eregi("\.php$", $newfilename)) {
				$ok = 0;
			}
			if (!$ok) {
				media_doError(_ERROR_BADFILETYPE);
			}
			/*
			T.Kosugi edit End
			*/
//			$msg1 = rename($mediapath . $_POST[targetfile], $mediapath . htmlspecialchars($_POST[newname]) );
			$msg1 = media_rename($mediapath, postVar('targetfile'), htmlspecialchars(postVar('newname')) );
			if (!$msg1) {
				print htmlspecialchars(postVar('targetfile') . _MEDIA_PHP_10);
			}
//			$exist = file_exists($mediapath . $_POST[targetthumb]);
			$exist = file_exists($mediapath . postVar('targetthumb'));

			//print "targetthumb=$mediapath$_POST[targetthumb]<BR />";
			if ($exist) {
//				$thumbnewname = $Prefix_thumb . $_POST[newname];
				$thumbnewname = $Prefix_thumb . postVar('newname');
//				$msg2         = rename($mediapath . $_POST[targetthumb], $mediapath . $thumbnewname);
				$msg2         = media_rename($mediapath, postVar('targetthumb'), $thumbnewname);
				if (!$msg2) {
					print htmlspecialchars(postVar('targetthumb') . _MEDIA_PHP_10);
				}
			}
			break;
	}
}
// </080213 fix $_POST to postVar by shizuki>
switch($action) {
	case 'chooseupload':
	case _MEDIA_UPLOAD_TO:
	case _MEDIA_UPLOAD_NEW:
		media_choose();
		break;
	case 'uploadfile':
		media_upload();
		break;
	case _MEDIA_PHP_30:
	case 'selectmedia':
	case _MEDIA_COLLECTION_SELECT:
	default:
		media_select();
		break;
	/*
		added forder action by T.Kosugi  2006/08/27
	*/
	case _MEDIA_PHP_ACTION_DIR:
	case _MEDIA_PHP_ACTION_MKDIR:
	case _MEDIA_PHP_ACTION_RMDIR:
	case 'rmdir':
	case 'mkdir':
		media_mkdir($action);
		break;
	/*
		END added forder action by T.Kosugi  2006/08/27
	*/
}

// select a file
function media_select()
{
	global $member, $CONF, $DIR_MEDIA, $manager;
//added yama 20071013
	$mediatocu = $manager->getPlugin('NP_Mediatocu');
	if ($mediatocu->getOption('paste_mode_checked')=="yes") {
		$paste_mode_popup_checked = 'checked="checked"';
	} else {
		$paste_mode_normal_checked = 'checked="checked"';
	}
//end yama

	// show 10 files + navigation buttons
	// show msg when no files
	// show upload form
	// files sorted according to last modification date

	// currently selected collection
	$currentCollection = requestVar('collection');
        /*2005.8.31  kei append*/
// <080213 fix $_POST to postVar by shizuki>
//	if ($_POST[currentCollection]) {//}
	if (postVar('currentCollection')) {
//		$currentCollection = $_POST[currentCollection];
		$currentCollection = postVar('currentCollection');
	}
// </ 080213 fix $_POST to postVar by shizuki>
	if (!$currentCollection || !@is_dir($DIR_MEDIA . $currentCollection)) {
		$currentCollection = $member->getID();
	}

	// avoid directory travarsal and accessing invalid directory
	if (!MEDIA::isValidCollection($currentCollection)) media_doError(_ERROR_DISALLOWED);

	media_head();

	// get collection list
	// start modify by T.Kosugi 2006/08/26
	//$collections = MEDIA::getCollectionList();
	$collections = MEDIADIRS::getCollectionList();
	// modify end
	// modify start T.Kosugi 2006/09/01
	// if (sizeof($collections) > 1) {
	if (sizeof($collections) > 0) {
	// modify end T.Kosugi 2006/09/01
?>
		<form method="post" action="media.php" style="margin:5px 0;"><div>
			<label for="media_collection"><?php echo htmlspecialchars(_MEDIA_COLLECTION_LABEL)?></label>
			<select name="collection" id="media_collection" onchange="return form.submit()">
				<?php
					foreach ($collections as $dirname => $description) {
						echo '<option value="',htmlspecialchars($dirname),'"';
						if ($dirname == $currentCollection) {
							echo ' selected="selected"';
						}
						echo '>',htmlspecialchars($description),'</option>';
					}
				?>
			</select>
			<!--<input type="submit" name="action" value="<?php echo htmlspecialchars(_MEDIA_COLLECTION_SELECT) ?>" title="<?php echo htmlspecialchars(_MEDIA_COLLECTION_TT)?>" />-->
			<input type="submit" name="action" value="<?php echo htmlspecialchars(_MEDIA_UPLOAD_NEW) ?>" title="<?php echo htmlspecialchars(_MEDIA_UPLOADLINK) ?>" />
<?php // add button start by T.Kosugi 2006/08/26 ?>
			<input type="submit" name="action" value="<?php echo htmlspecialchars(_MEDIA_PHP_ACTION_DIR) ?>" title="<?php echo htmlspecialchars(_MEDIA_PHP_ACTION_DIR_TT) ?>" />
<?php // add bottun end by T.Kosugi 2006/08/26 ?>
			<?php $manager->addTicketHidden() ?>
		</div></form>
<?php
	} else {
?>
		<form method="post" action="media.php" style="float:right"><div>
			<input type="hidden" name="collection" value="<?php echo htmlspecialchars($currentCollection)?>" />
			<input type="submit" name="action" value="<?php echo htmlspecialchars(_MEDIA_UPLOAD_NEW) ?>" title="<?php echo htmlspecialchars(_MEDIA_UPLOADLINK) ?>" />
			<?php $manager->addTicketHidden() ?>
		</div></form>
<?php
	} // if sizeof

	$filter = requestVar('filter');
	$offset = intRequestVar('offset');

	// start modify by T.Kosugi 2006/08/26
	//$arr = MEDIA::getMediaListByCollection($currentCollection, $filter);
	$arr = MEDIADIRS::getMediaListByCollection($currentCollection, $filter);
	// modify end
?>
		<form method="post" action="media.php" style="margin:5px 0;"><div>
			<label for="media_filter"><?php echo htmlspecialchars(_MEDIA_PHP_31)?></label>
			<input id="media_filter" type="text" name="filter" value="<?php echo htmlspecialchars($filter)?>" />
			<input type="submit" name="action" value="<?php echo htmlspecialchars(_MEDIA_PHP_30) ?>" />
			<input type="hidden" name="collection" value="<?php echo htmlspecialchars($currentCollection)?>" />
			<input type="hidden" name="offset" value="<?php echo intval($offset)?>" />
		</div></form>

<?php
	if (sizeof($arr)>0) {
		$contents = array();
		/*The numbers of contents except the thumbnail image are requested. */
		for ($i=0;$i<sizeof($arr);$i++) {
			$obj = $arr[$i];
			if (ereg("thumb", $obj->filename)) {
				continue;
			}
			$contents[] = $obj;
		}
		$conts_count = sizeof($contents);
		//print "conts_count=$conts_count<br />";
		if ($conts_count < $CONF['MediaPerPage']) {
			$maxpage = 1;
		} else {
			$maxpage = ceil($conts_count/$CONF['MediaPerPage']);
		}

		if ($offset==0) {
			$offset=1;
		}
		$idxStart = $offset;
		$idxEnd   = $idxStart * $CONF['MediaPerPage'];
		if ($idxEnd > $conts_count) {
			$idxEnd = $conts_count;
		}
		if ($idxEnd < 1) {
			$idxEnd = $CONF['MediaPerPage'];
		}
		$idxNext = ($idxStart-1) * $CONF['MediaPerPage'];
		if ($idxNext < 0) {
			$idxNext = 0;
		}
	}
?>

		<p><?php echo htmlspecialchars(_MEDIA_COLLECTION_LABEL . $collections[$currentCollection] . _MEDIA_PHP_6 . $conts_count) . " " . intVal($idxNext+1) . " - " . htmlspecialchars($idxEnd . _MEDIA_PHP_7); ?></p>
		<p>
<?php
	if ($idxStart >0 && $idxNext >0) {
		$page = ($idxStart-1);
		echo "<a href='media.php?offset=$page&amp;collection=" . urlencode($currentCollection) . "' title='$page'>" . htmlspecialchars(_MEDIA_PHP_29) . "</a> ";
	}
	if ($idxStart < $maxpage) {
		$page = ($idxStart+1);
		echo "<a href='media.php?offset=$page&amp;collection=" . urlencode($currentCollection) . "' title='$page'>" .  htmlspecialchars(_MEDIA_PHP_28) . "</a> ";
	}
?>
		</p>
<form name="top" action="media.php" style="margin:5px;">
	<div>
		<?php echo htmlspecialchars(_MEDIA_PHP_11) ?>
		<input id="typeradio0" type="radio" class="radio" name="typeradio" onclick="setType(0);document.bottom.typeradio[0].checked=true;" onkeypress="setType(0);document.bottom.typeradio[0].checked=true;" <?php echo $paste_mode_normal_checked; ?> />
		<label for="typeradio0"><?php echo htmlspecialchars(_MEDIA_INLINE);?></label>
		<input <?php echo $paste_mode_popup_checked; ?> id="typeradio1" type="radio" class="radio" name="typeradio" onclick="setType(1);document.bottom.typeradio[1].checked=true;" onkeypress="setType(1);document.bottom.typeradio[1].checked=true;" />
		<label for="typeradio1"><?php echo htmlspecialchars(_MEDIA_POPUP); ?></label>
	</div>
</form>

<!-- rem yama 20070928		  <th><?php echo _MEDIA_MODIFIED; ?></th> -->
<!--
			<th><?php echo htmlspecialchars(_MEDIA_PHP_12); ?></th>
			<th><?php echo htmlspecialchars(_MEDIA_PHP_25); ?></th>
			<th><?php echo htmlspecialchars(_MEDIA_PHP_20); ?></th>
-->
<?php
	if (sizeof($arr)>0) {
		/*-------kei edit & append 2005.5.26-------*/
		global $myaction, $targetfile, $targetthumb, $Prefix_thumb;
		global $newname, $thumbnewname;

		if ($msg1) {
			$targetfile = $newname;
		}
		if ($msg2) {
			$thumb_targetfile = $thumbnewname;
			echo "<script type='text/javascript'>\n\tlocation.replace('media.php');\n</script>";
		}

//		print"idxNext=$idxNext<BR />";
//		print"idxEnd=$idxEnd<BR />";
//		print"<BR />";
		for ($i=$idxNext;$i<$idxEnd;$i++) {
			$filename = $DIR_MEDIA . $currentCollection . '/' . $contents[$i]->filename;
//			if(!$msg1)$targetfile = $contents[$i]->filename;
			$targetfile = $contents[$i]->filename;
			$old_level  = error_reporting(0);
			$size       = @GetImageSize($filename);
			error_reporting($old_level);
			$intWidth      = intval($size[0]);
			$intHeight     = intval($size[1]);
			$filetype   = $size[2];

			echo "<div class='box'>\n";
// rem yama			echo "<td>". date("Y-m-d",$contents[$i]->timestamp) ."</td>\n";

			// strings for javascript
			$jsCurrentCollection = str_replace("'", "\\'", $currentCollection);
			$jsFileName          = str_replace("'", "\\'", $contents[$i]->filename);
			$targetfile          = str_replace($Prefix_thumb, "", $jsFileName);
			/*-------kei append 2005.5.26-------*/
			$mediapath           = $DIR_MEDIA . $currentCollection."/";
			$thumb_file          = $Prefix_thumb . $targetfile;
			if (!$msg2) {
				$thumb_targetfile = $thumb_file;
			}
			$thumb_exist = file_exists($mediapath . $thumb_file);
			/*Thumbnail*/
// <080213 shizuki add>
			$hscJsCC = htmlspecialchars($jsCurrentCollection);
			$hscTGTF = htmlspecialchars($targetfile);
			$hscTTGT = htmlspecialchars($thumb_targetfile);
			$hscJsFN = htmlspecialchars($jsFileName);
			$hscCCol = htmlspecialchars($currentCollection);
			$hscThFN = htmlspecialchars($thumb_file);
			$hscMEDA = htmlspecialchars($CONF['MediaURL']);
//	2008-02-21 cacher
//			$hscMVEW = htmlspecialchars(_MEDIA_VIEW_TT);
			$hscMVEW = htmlspecialchars(_MEDIA_VIEW);
			$hscMVTT = htmlspecialchars(_MEDIA_VIEW_TT);
//	/2008-02-21 cacher
			$hscMedia26 = htmlspecialchars(_MEDIA_PHP_26);
// </080213 shizuki add>
			if ($filetype != 0 || $thumb_exist) {
				// image (gif/jpg/png/swf)
				$selectfile = $mediapath . $contents[$i]->filename;
//				print "selectfile=$selectfile<BR />";
				if (function_exists("ImageCreateFromGif")) {
				$pattern = array( "/.wmv/" );
				} else {
				$pattern = array( "/.gif/", "/.wmv/" );
				}
				if (!$thumb_exist) {
//					$thumbfile2    = preg_replace($pattern,  ".png", $targetfile); //Extension conversion
//					$thumb_file    = $Prefix_thumb . $thumbfile2;
					$thumb_file    = $Prefix_thumb . $targetfile . ".png";
					$thumb_exist   = file_exists($mediapath . $thumb_file);
					$notmake_thumb = 0;
					/*Making is tried if there is no thumbnail.  */
					if (!$thumb_exist) {
						$notmake_thumb = make_thumbnail($DIR_MEDIA, $currentCollection, $selectfile, $contents[$i]->filename);
					}
				}
				if ($msg2) {
					$thumb_file = $Prefix_thumb . $contents[$i]->filename;
				}

// <080213 shizuki add>
				$hscThFN = htmlspecialchars($thumb_file);
// </080213 shizuki add>
//	2008-11-01 cacher
				}
				if (file_exists($mediapath . $thumb_file)) {
//	/2008-11-01 cacher
// <080213 mod by shizuki>
//			echo "<div class=\"tmb\">
//				<a href=\"media.php\" onclick=\"chooseImage('", htmlspecialchars($jsCurrentCollection), "','", htmlspecialchars($targetfile), "',"
//		     . "'", htmlspecialchars($intWidth), "','" , htmlspecialchars($intHeight), "'"
//				   . ")\" onkeypress=\"chooseImage('", htmlspecialchars($jsCurrentCollection), "','", htmlspecialchars($targetfile), "',"
//		     . "'", htmlspecialchars($intWidth), "','" , htmlspecialchars($intHeight), "'"
//				   . ")\" title=\"" . htmlspecialchars($targetfile). "\">
//				<img src=\"../../../media/$currentCollection/$thumb_file\" alt=\"$targetfile\" /></a></div>\n";
				echo <<<_DIVTHUMB_
	<div class="tmb">
		<a href="media.php" onclick="chooseImage('{$hscJsCC}', '{$hscTGTF}', '{$intWidth}', '{$intHeight}')" onkeypress="chooseImage('{$hscJsCC}', '{$hscTGTF}', '{$intWidth}', '{$intHeight}')" title="{$hscTGTF}">
			<img src="{$hscMEDA}{$hscCCol}/{$hscThFN}" alt="{$hscTGTF}" /></a></div>

_DIVTHUMB_;
//2008-02-21 Cacher
// </ 080213 mod by shizuki>
			} else {
				// When you do not make the thumbnail with mpg and wmv, etc.
//	2008-11-01 cacher
				$revname=strrev($filename);
				$file_ext=strtoupper(strrev(substr($revname,0,strpos($revname,"."))));
// 2008-11-08 yama
//				echo "\t<div class=\"tmb\">$file_ext</div>\n";
				echo "\t<div class=\"media\">".htmlspecialchars($file_ext)."</div>\n";
// /2008-11-08 yama
//	/2008-11-01 cacher
			}
//	2008-11-01 cacher
			echo "\t";
			if ($intWidth||$intHeight){
				echo $intWidth . ' x ' . $intHeight;
			}
//			echo "<br />\n\t(" . intval(filesize($filename)) . ")<br />\n\t"	//2008-11-06 cacher
			echo "<br />\n\t" . number_format(filesize($filename)/1024, 1)." KB<br />\n\t"
				. date("Y-m-d", $contents[$i]->timestamp) . "<br class=\"clear\" />\n";
//	/2008-11-01 cacher
			/*File name and size*/
			//print "targetfile=$targetfile<BR />";
			if ($filetype != 0) {
				// image (gif/jpg/png/swf)
// <080213 mod by shizuki>
//				echo "<a href=\"media.php\" onclick=\"chooseImage('", htmlspecialchars($jsCurrentCollection), "','", htmlspecialchars($targetfile), "',"
//					. "'", htmlspecialchars($intWidth), "','" , htmlspecialchars($intHeight), "'"
//					. ")\" onkeypress=\"chooseImage('", htmlspecialchars($jsCurrentCollection), "','", htmlspecialchars($targetfile), "',"
//					. "'", htmlspecialchars($intWidth), "','" , htmlspecialchars($intHeight), "'"
//					. ")\" title=\"" . htmlspecialchars($targetfile). "\">"
// rem yama 20070928					   . htmlspecialchars(shorten($targetfile,25,'...'))
//					   . _MEDIA_PHP_26 //added yama 20070928
//					   ."</a>";
//			   echo ' (<a href="', htmlspecialchars($CONF['MediaURL'] . $currentCollection . '/' . $targetfile), '" onclick="window.open(this.href); return false;" onkeypress="window.open(this.href); return false;" title="',htmlspecialchars(_MEDIA_VIEW_TT),'">',_MEDIA_VIEW,'</a>)';
			echo <<<_MEDIAPREVIEW_
	<a href="media.php" onclick="chooseImage('{$hscJsCC}', '{$hscTGTF}', '{$intWidth}', '{$intHeight}')" onkeypress="chooseImage('{$hscJsCC}', '{$hscTGTF}', '{$intWidth}', '{$intHeight}')" title="{$hscTGTF}">
		{$hscMedia26}
	</a>
	(<a href="{$hscMEDA}{$hscCCol}/{$hscTGTF}" onclick="window.open(this.href); return false;" onkeypress="window.open(this.href); return false;" title="{$hscMVTT}">{$hscMVEW}</a>)

_MEDIAPREVIEW_;
//2008-02-21 cacher
// </ 080213 mod by shizuki>
			} else {
			// not image (e.g. mpg)
// <080213 mod by shizuki>
//			echo "<a href=\"media.php\" onclick=\"chooseOther('" , htmlspecialchars($jsCurrentCollection), "','", htmlspecialchars($targetfile), "'"
//			    . ")\" title=\"" . htmlspecialchars($targetfile). "\">"
//			    . htmlspecialchars(shorten($targetfile,30,'...'))
//			    ."</a>";
//				$shortFN = htmlspecialchars(shorten($targetfile, 30, '...'));
				echo <<<_MEDIAFILE_
	<a href="media.php" onclick="chooseOther('{$hscJsCC}', '{$hscTGTF}')" onkeypress="chooseOther('{$hscJsCC}', '{$hscTGTF}')" title="{$hscTGTF}">
		{$hscMedia26}
	</a>
	(<a href="{$hscMEDA}{$hscCCol}/{$hscTGTF}" onclick="window.open(this.href); return false;" onkeypress="window.open(this.href); return false;" title="{$hscMVTT}">{$hscMVEW}</a>)

_MEDIAFILE_;
// </080213 mod by shizuki>
			}
// <080213 mod by shizuki>
//		echo"<form method='post' action='media.php' style=\"margin:5px 0 2px;padding:0;\">\n
//			<div>
//			<input type='hidden' name='currentCollection' value='$currentCollection' />
//			<input type='hidden' name='offset' value=\"$offset\" />
//			<input type='hidden' name='targetfile' value=\"$targetfile\" />
//			<input type ='hidden' name='targetthumb' value=\"$thumb_targetfile\" />
//			<input type='text' name='newname' value=\"$targetfile\" size=\"24\" /><br />
//			<input type='submit' name='myaction' value='"._MEDIA_PHP_3."' title='"._MEDIA_PHP_4."' onclick='return kakunin(this.value)' onkeypress='return kakunin(this.value)' style=\"margin-left:5px;\" />\n
//			<input type='submit' name='myaction' value='"._MEDIA_PHP_1."' onclick='return kakunin(this.value)' onkeypress='return kakunin(this.value)' />\n
//			</div>
//			</form></div>\n";
			$hscMedia01 = htmlspecialchars(_MEDIA_PHP_1);
			$hscMedia03 = htmlspecialchars(_MEDIA_PHP_3);
			$hscMedia04 = htmlspecialchars(_MEDIA_PHP_4);
			echo <<<_FORMBLOCK_
	<form method="post" action="media.php" style="margin:5px 0 2px; padding:0;">
		<div>
			<input type="hidden" name="currentCollection" value="{$hscCCol}" />
			<input type="hidden" name="offset" value="{$offset}" />
			<input type="hidden" name="targetfile" value="{$hscTGTF}" />
			<input type="hidden" name="targetthumb" value="{$hscTTGT}" />
			<input type="text"   name="newname" value="{$hscTGTF}" size="24" /><br />
			<input type="submit" name="myaction" value="{$hscMedia03}" title="{$hscMedia04}" onclick="return kakunin(this.value)" onkeypress="return kakunin(this.value)" style="margin-left:5px;" />
			<input type="submit" name="myaction" value="{$hscMedia01}" onclick="return kakunin(this.value)" onkeypress="return kakunin(this.value)" />
		</div>
	</form>
</div>

_FORMBLOCK_;
// </080213 mod by shizuki>
		}
	} // if (sizeof($arr)>0) }
	echo '<p class="clear">' . "\n";
	if ($idxStart > 0 && $idxNext > 0) {
		echo "<a href='media.php?offset=" . intVal($idxStart-1) . "&amp;collection=" . urlencode($currentCollection) . "'>" . htmlspecialchars(_MEDIA_PHP_29) . "</a> ";
	}
	if ($idxStart < $maxpage) {
		echo "<a href='media.php?offset=" . intVal($idxStart+1) . "&amp;collection=" . urlencode($currentCollection) . "'>" . htmlspecialchars(_MEDIA_PHP_28) . "</a> ";
	}
?>
	</p><form name="bottom" action="media.php" style="margin:5px;"><div>
		<?php echo htmlspecialchars(_MEDIA_PHP_11); ?> <input id="typeradio0b" type="radio" class="radio" name="typeradio" onclick="setType(0);document.top.typeradio[0].checked=true;" onkeypress="setType(0);document.top.typeradio[0].checked=true;" <?php echo $paste_mode_normal_checked; ?> /><label for="typeradio0b"><?php echo htmlspecialchars(_MEDIA_INLINE); ?></label>
		<input <?php echo $paste_mode_popup_checked; ?> id="typeradio1b" type="radio" class="radio" name="typeradio" onclick="setType(1);document.top.typeradio[0].checked=true;" onkeypress="setType(1);document.top.typeradio[0].checked=true;" /><label for="typeradio1b"><?php echo htmlspecialchars(_MEDIA_POPUP); ?></label>
	</div></form>

<?php
	media_foot();


}

/**
  * Shows a screen where you can select the file to upload
  */
function media_choose()
{
	global $CONF, $member, $manager;

	$currentCollection = requestVar('collection');

	// start modify by T.Kosugi 2006/08/26
//	$collections = MEDIA::getCollectionList();
	$collections = MEDIADIRS::getCollectionList();
	// modify end

	media_head();
?>
	<h1><?php echo htmlspecialchars(_UPLOAD_TITLE); ?></h1>

	<p><?php echo htmlspecialchars(_UPLOAD_MSG); ?></p>
<!--//added yama 20070928-->
	<?php echo _MEDIA_PHP_21; ?>
<!--//end yama 20070928-->

	<form method="post" enctype="multipart/form-data" action="media.php">
	<div>
 	  <input type="hidden" name="action" value="uploadfile" />
 	  <?php $manager->addTicketHidden() ?>
	  <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo intVal($CONF['MaxUploadSize']); ?>" />
	  File:
	  <br />
	  <input name="uploadfile" type="file" size="40" />
	<?php		if (sizeof($collections) > 1) {
?>
		<br /><br /><label for="upload_collection">Collection:</label>
		<br /><select name="collection" id="upload_collection">
			<?php
					foreach ($collections as $dirname => $description) {
						echo '<option value="' . htmlspecialchars($dirname) . '"';
						if ($dirname == $currentCollection) {
							echo ' selected="selected"';
						}
						echo '>' . htmlspecialchars($description) . '</option>';
					}
			?>
		</select>
	<?php		} else {
	?>
	  	<input name="collection" type="hidden" value="<?php echo htmlspecialchars(requestVar('collection'))?>" />
	<?php		} // if sizeof
	?>
	  <br /><br />
	  <input type="submit" value="<?php echo htmlspecialchars(_UPLOAD_BUTTON); ?>" />
	</div>
	</form>
<p><a href="javascript:history.back()"><?php echo htmlspecialchars(_BACK); ?></a></p>
	<?php
	media_foot();
}


/**
  * accepts a file for upload
  */
function media_upload()
{
	global $DIR_MEDIA, $member, $CONF, $manager;

	$uploadInfo   = postFileInfo('uploadfile');

	$filename     = $uploadInfo['name'];
	$filetype     = $uploadInfo['type'];
	$filesize     = $uploadInfo['size'];
	$filetempname = $uploadInfo['tmp_name'];
	$fileerror    = intval($uploadInfo['error']);
	$mediatocu    = $manager->getPlugin('NP_Mediatocu');
// added yama 20080131
	if ($mediatocu->getOption('filename_rule') == "ascii") {
		$path_parts = pathinfo($filename);
		$filename   = time() . "." . $path_parts['extension'];
	}
// end
	
	switch ($fileerror) {
		case 0: // = UPLOAD_ERR_OK
			break;
		case 1: // = UPLOAD_ERR_INI_SIZE
		case 2: // = UPLOAD_ERR_FORM_SIZE
			media_doError(_ERROR_FILE_TOO_BIG);
			break;
		case 3: // = UPLOAD_ERR_PARTIAL
		case 4: // = UPLOAD_ERR_NO_FILE
		case 6: // = UPLOAD_ERR_NO_TMP_DIR
		case 7: // = UPLOAD_ERR_CANT_WRITE
		default:
			// include error code for debugging
			// (see http://www.php.net/manual/en/features.file-upload.errors.php)
			media_doError(_ERROR_BADREQUEST . ' (' . $fileerror . ')');
			break;
	}

	// T.Kosugi add 2006.9.1
	if (stristr($filename, '%00')) {
		media_doError(_MEDIA_PHP_38);
	}
	// T.Kosugi add end
	if ($filesize > $CONF['MaxUploadSize']) {
		media_doError(_ERROR_FILE_TOO_BIG);
	}

	// check file type against allowed types
	$ok           = 0;
	$allowedtypes = explode (',', $CONF['AllowedTypes']);
	foreach ( $allowedtypes as $type ) {
		if (eregi("\." .$type. "$",$filename)) {
			$ok = 1;
		}
	}
	if (!$ok) {
		media_doError(_ERROR_BADFILETYPE);
	}

	if (!is_uploaded_file($filetempname)) {
		media_doError(_ERROR_BADREQUEST);
	}

	// prefix filename with current date (YYYY-MM-DD-)
	// this to avoid nameclashes
	if ($CONF['MediaPrefix']) {
		$filename = strftime("%Y%m%d-", time()) . $filename;
	}

	$collection = requestVar('collection');
	$res        = MEDIA::addMediaObject($collection, $filetempname, $filename);

	if ($res != '') {
		media_doError($res);
	}
	$uppath = $DIR_MEDIA.$collection . "/";
	$upfile = $DIR_MEDIA.$collection . "/" . $filename;

	$res    = move_uploaded_file($filetempname, $upfile);
	if ($res != '') {
	  media_doError($res);
	}

	make_thumbnail($DIR_MEDIA, $collection, $upfile, $filename);

	// shows updated list afterwards
	media_select();
}
/**
  * accepts a dirname for mkdir
  * added by T.Kosugi 2006/08/27
  *
  */
function media_mkdir($action)
{
	global $DIR_MEDIA, $member, $CONF, $manager;
	if ($action == _MEDIA_PHP_ACTION_MKDIR || $action =='mkdir' ) {
		$current   = requestVar('mkdir_collection');
		$mkdirname = postVar('mkdirname');
		if (!($mkdirname && $current)) {
			media_select();
			return;
		}
		// Create member's directory if not exists.
		if (is_numeric($current) && $current==$member->getID() && !is_dir($DIR_MEDIA . '/' . $current)) {
			$oldumask = umask(0000);
			if (!@mkdir($DIR_MEDIA. '/' . $current, 0777)) {
				return _ERROR_BADPERMISSIONS;
			}
			umask($oldumask);
		}
		// Check if valid directory.
		$path      = $current . '/' . $mkdirname ;
		$path      = str_replace('\\','/',$path); // Avoid using "\" in Windows.
		$pathArray = explode('/', $path);
		if ($pathArray[0] !== $member->getID()) {
			media_doError(_MEDIA_PHP_39 . $pathArray[0] . ':' . $member->getID());
		}
		if (in_array('..', $pathArray)) {
			media_doError(_MEDIA_PHP_40);
		}
		// OK. Let's go.
		if (is_dir($DIR_MEDIA . '/' . $current)) {
			$res = @mkdir($DIR_MEDIA . '/' . $current . '/' . $mkdirname);
			$res .= @chmod($DIR_MEDIA . '/' . $current . '/' . $mkdirname , 0777);
		}
		if (!$res) {
			media_doError(_MEDIA_PHP_41 . $res );
		}
		// shows updated list afterwards
		media_select();
	} elseif($action == _MEDIA_PHP_ACTION_RMDIR ||
			 $action == 'rmdir') {
		$rmdir_collection = postVar('rmdir_collection');
		$rmdir_collection = str_replace('\\','/',$rmdir_collection); // Avoid using "\" in Windows.
		$pathArray        = explode('/', $rmdir_collection);
		if ($pathArray[0] !== $member->getID()) {
			media_doError(_MEDIA_PHP_39 . $pathArray[0] . ':' . $member->getID());
		}
		if (in_array('..', $pathArray)) {
			media_doError(_MEDIA_PHP_40);
		}
		$res   = @media_rmdir($DIR_MEDIA,$rmdir_collection);
		if ($res) {
			media_select();
		} else {
			media_doError(_MEDIA_PHP_42);
		}
	} else {
		$current     = requestVar('collection');
		$collections = MEDIADIRS::getPrivateCollectionList();

		media_head();
		?>
		<h1><?php echo htmlspecialchars(_MEDIA_MKDIR_TITLE); ?></h1>

		<p><?php echo htmlspecialchars(_MEDIA_MKDIR_MSG); ?></p>

		<form method="post" action="media.php">
		<div>
	 	  <input type="hidden" name="action" value="<?php echo htmlspecialchars(_MEDIA_PHP_ACTION_MKDIR); ?>" />
	 	  <?php $manager->addTicketHidden() ?>
		  FolderName:
		  <br />
		  <input name="mkdirname" type="text" size="40" />
		<?php		if (sizeof($collections) > 0) {
		?>
			<br /><br /><label for="mkdir_collection">Collection:</label>
			<br /><select name="mkdir_collection" id="mkdir_collection">
				<?php
						foreach ($collections as $dirname => $description) {
							echo '<option value="',htmlspecialchars($dirname),'"';
							if ($dirname == $current) {
								echo ' selected="selected"';
							}
							echo '>' . htmlspecialchars($description) . '</option>';
						}
				?>
			</select>
		<?php		} elseif (sizeof($collections) == 1) {
						$flipCollections = array_flip($collections);
						$collection = array_pop($flipCollections);
		?>
		  	<input name="collection" type="hidden" value="<?php echo htmlspecialchars($collection);?>" />
		<?php		} else {
						media_foot();
						return;
					}// if sizeof
		?>
		  <br /><br />
		  <input type="submit" value="<?php echo htmlspecialchars(_MEDIA_MKDIR_BUTTON); ?>" />
		</div>
		</form>
		<?php		if (sizeof($collections) > 0) {?>
			<br /><br /><h1><?php echo htmlspecialchars(_MEDIA_RMDIR_TITLE); ?></h1>

		<p><?php echo htmlspecialchars(_MEDIA_RMDIR_MSG); ?></p>

		<form method="post" action="media.php">
		<div>
	 	  <input type="hidden" name="action" value="<?php echo htmlspecialchars(_MEDIA_PHP_ACTION_RMDIR); ?>" />

	<label for="rmdir_collection">Collection:</label>
			<br /><select name="rmdir_collection" id="rmdir_collection">
				<?php
					foreach ($collections as $dirname => $description) {
						if (is_numeric($dirname)) continue;
						echo '<option value="',htmlspecialchars($dirname),'"';
						if ($dirname == $current) {
							echo ' selected="selected"';
						}
						echo '>',htmlspecialchars($description),'</option>';
					}
				?>
			</select>
		<?php		} else {
						media_foot();
						return;
					}// if sizeof
		?>
		  <br /><br />
		  <?php $manager->addTicketHidden() ?>
		  <input type="submit" value="<?php echo htmlspecialchars(_MEDIA_RMDIR_BUTTON); ?>" />
		</div>
		</form>
		<p><a href="javascript:history.back()"><?php echo htmlspecialchars(_BACK); ?></a></p>
		<?php
		media_foot();
	}
}

function make_thumbnail($DIR_MEDIA, $collection, $upfile, $filename)
{

    global $Prefix_thumb;

    // Avoid directory traversal
    media_checkFile($DIR_MEDIA,$collection);
    // Thumbnail filename should not contain '/' or '\'.
    if (preg_match('#(/|\\\\)#',$Prefix_thumb.$filename)) media_doError(_ERROR_DISALLOWED);

    /*
    print "DIR_MEDIA=$DIR_MEDIA<BR />";
    print "collection=$collection<BR />";
    print "upfile=$upfile<BR />";
    */
    //print "filename=$filename<BR />\n";
    // Thumbnail image size specification

//mod yama
	global $manager;
	$mediatocu  = $manager->getPlugin('NP_Mediatocu');
	$thumb_w    = intVal($mediatocu->getOption('thumb_width'));
	$thumb_h    = intVal($mediatocu->getOption('thumb_height'));
	$quality    = intVal($mediatocu->getOption('thumb_quality'));
//end yama
    $size       = getimagesize($upfile);
    $thumb_file = "{$DIR_MEDIA}{$collection}/{$Prefix_thumb}{$filename}";
    // Resize rate
    $moto_w = $size[0];
    $moto_h = $size[1];
    if ($moto_w > $thumb_w || $moto_h > $thumb_h) {
      $ritu_w = $thumb_w /$moto_w;
      $ritu_h = $thumb_h /$moto_h;
      ($ritu_w < $ritu_h) ? $cv_ritu = $ritu_w : $cv_ritu = $ritu_h;
      $w = ceil($moto_w * $cv_ritu);
      $h = ceil($moto_h * $cv_ritu);
    }

    if ($w && $h) {
      // Making preservation of thumbnail image
      thumb_gd($upfile, $thumb_file, $w, $h, $size, $quality); //GD version
    } else {
      //There is no necessity about the resize. 
      thumb_gd($upfile, $thumb_file, $moto_w, $moto_h, $size, $quality); //GD version
    }
}


//Thumbnail making(GD)
function thumb_gd($fname, $thumbfile, $out_w, $out_h, $size, $quality)
{
	switch ($size[2]) {
		case 1 ://.gif(or .png)
			if (function_exists("ImageCreateFromGif")) {
				$img_in = @ImageCreateFromGIF($fname);
			} else {
				$fname  = str_replace( ".gif", ".png", $fname); //Extension conversion
				$img_in = @ImageCreateFromPng($fname);
			}
			break;
		case 2 ://.jpg
			$img_in = @ImageCreateFromJPEG($fname);
			break;
		case 3 ://.png
			$img_in = @ImageCreateFromPng($fname);
			break;
		default :
			return;
	}
	if (!$img_in) {
		return;
	}
	//print "im_in=$img_in<BR />";
	$img_out = ImageCreateTrueColor($out_w, $out_h);
	//Former image is copied and the thumbnail is made.
	ImageCopyResampled($img_out, $img_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
	//Preservation of thumbnail image
	switch ($size[2]) {
		case 1 ://.gif
			ImageGif($img_out, $thumbfile);
			break;
		case 2 ://.jpg
			ImageJpeg($img_out, $thumbfile, $quality);
			break;
		case 3 ://.png
			ImagePng($img_out, $thumbfile);
			break;
	}
	//The memory that maintains the making image is liberated. 
	imagedestroy($img_in);
	imagedestroy($img_out);
}

function media_loginAndPassThrough()
{
	media_head();
	?>
		<h1><?php echo _LOGIN_PLEASE?></h1>

		<form method="post" action="media.php">
		<div>
			<input name="action" value="login" type="hidden" />
			<input name="collection" value="<?php echo htmlspecialchars(requestVar('collection')); ?>" type="hidden" />
			<?php echo htmlspecialchars(_LOGINFORM_NAME); ?>: <input name="login" />
			<br /><?php echo htmlspecialchars(_LOGINFORM_PWD); ?>: <input name="password" type="password" />
			<br /><input type="submit" value="<?php echo htmlspecialchars(_LOGIN); ?>" />
		</div>
		</form>
		<p><a href="media.php" onclick="window.close();"><?php echo htmlspecialchars(_POPUP_CLOSE); ?></a></p>
	<?php	media_foot();
	exit;
}

function media_doError($msg)
{
	if (!headers_sent()) media_head();
	?>
	<h1><?php echo htmlspecialchars(_ERROR); ?></h1>
	<p><?php echo htmlspecialchars($msg); ?></p>
	<p><a href="javascript:history.back()"><?php echo htmlspecialchars(_BACK); ?></a></p>
	<?php	media_foot();
	exit;
}


function media_head()
{
	global $manager, $CONF;
	$mediatocu = $manager->getPlugin('NP_Mediatocu');
	$thumb_w   = intVal($mediatocu->getOption('thumb_width'));
	$thumb_h   = intVal($mediatocu->getOption('thumb_height'));
	if ($mediatocu->getOption('paste_mode_checked') == "yes") {
		$setType = "1";
	} else {
		$setType = "0";
	}
	$GreyBox   = $mediatocu->getOption('use_gray_box');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo htmlspecialchars(_CHARSET); ?>" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<title>Mediatocu</title>
		<link rel="stylesheet" type="text/css" href="popups.css" />
<?php
		if ($manager->pluginInstalled('NP_TinyMCE')) {
			$tinyMCE = $manager->getPlugin('NP_TinyMCE');
?>
		<script language="javascript" type="text/javascript" src="<?php echo $tinyMCE->getAdminURL(); ?>jscripts/tiny_mce/tiny_mce_popup.js"></script>
<?php
		}
?>
		<script type="text/javascript">
			var type = <?php echo intVal($setType); ?>;
			function setType(val) { type = val; }

			function chooseImage(collection, filename, width, height) {
<?php
		if ($manager->pluginInstalled('NP_TinyMCE')) {
?>
				var win = tinyMCEPopup.getWindowArg("w_n");
				var file_path = "<?php echo $CONF['MediaURL']; ?>" + collection + "/" + filename;
				win.document.getElementById(tinyMCEPopup.getWindowArg("f_n")).value = file_path;
				if (tinyMCEPopup.getWindowArg("file_type") == "image") {
					if (win.ImageDialog.getImageData) win.ImageDialog.getImageData();
					if (win.ImageDialog.showPreviewImage) win.ImageDialog.showPreviewImage(file_path);
				}
				tinyMCEPopup.close();
<?php
		} elseif ($GreyBox == 'yes') {
?>
				top.window.focus();
				top.window.includeImage(collection,
										   filename,
										   type == 0 ? 'inline' : 'popup',
										   width,
										   height
										   );
				top.window.GB_hide();
<?php
		} else {
?>
				top.opener.focus();
				top.opener.includeImage(collection,
										   filename,
										   type == 0 ? 'inline' : 'popup',
										   width,
										   height
										   );
				window.close();
<?php
		}
?>
			}

			function chooseOther(collection, filename) {
<?php
		if ($manager->pluginInstalled('NP_TinyMCE')) {
?>
				var win = tinyMCEPopup.getWindowArg("w_n");
				var file_path = "<?php echo $CONF['MediaURL']; ?>" + collection + "/" + filename;
				win.document.getElementById(tinyMCEPopup.getWindowArg("f_n")).value = file_path;
				tinyMCEPopup.close();
<?php
		} elseif ($GreyBox == 'yes') {
?>
				top.window.focus();
				top.window.includeOtherMedia(collection, filename);
				top.window.GB_hide();
<?php
		} else {
?>
				top.opener.focus();
				top.opener.includeOtherMedia(collection, filename);
				window.close();
<?php
		}
?>
			}

			function kakunin(value){
				res=confirm('<?php echo htmlspecialchars(_MEDIA_PHP_8); ?>'+value+'<?php echo htmlspecialchars(_MEDIA_PHP_9); ?>');
				return res;
			}
		</script>

	<style type="text/css">
/* 2008-11-08 yama*/
		div.tmb, div.media {
/* /2008-11-08 yama*/
			margin : 0px;
			padding : 0px;
			width : <?php echo $thumb_w ?>px;
			height : <?php echo $thumb_h ?>px;
			line-height : <?php echo $thumb_h ?>px;
			float : left;
			display : inline;
/* 2008-11-08 yama*/
			border : 1px solid #999;
/* /2008-11-08 yama*/
			text-align : center;
		}
/* 2008-11-08 yama*/
		div.tmb {
			background-image: url("bg.gif");
		}
		div.media {
			background-color: #fff;
		}
		div.tmb a, div.media a {
/* /2008-11-08 yama*/
			width : <?php echo $thumb_w ?>px;
			height : <?php echo $thumb_h ?>px;
			display : block;
		}
	</style>
	
	<base target="_self" />
		
</head>
<body>
<?php
}

function media_foot()
{
?>
</body>
</html>
<?php
}


function media_checkFile($dir,$file,$return=false){
	// Anti direcory-traversal rountine.
	global $DIR_MEDIA,$member;
	// member's directory is OK even if not exists.
	if ($dir==$DIR_MEDIA && is_numeric($file)) return $file==$member->getID();
	// The check fails if file does not exists
	$file=realpath($dir.$file);
	$dir=realpath($dir);
	if (strpos($file,$dir)===0) return true;
	if ($return) return false;
	media_doError(_ERROR_DISALLOWED);
	exit;
}

function media_unlink($dir,$file){
	media_checkFile($dir,$file);
	return unlink($dir.$file);
}

function media_rmdir($dir,$file){
	media_checkFile($dir,$file);
	return rmdir($dir.$file);
}

function media_rename($dir,$file,$newfile){
	media_checkFile($dir,$file);
	if (preg_match('#(/|\\\\)#',$newfile)) media_doError(_ERROR_DISALLOWED);
	return rename($dir.$file, $dir.$newfile);
}

?>
