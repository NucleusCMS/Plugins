<?php
/**
 * ImprovedMedia plugin for Nucleus CMS
 * Version 3.0.1
 * Written By Mocchi, Feb.28, 2010
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

define('_IM_DESCRIPTION',	'Provide media function for each weblog on its own directory with erasing and renaming functions');
define('_IM_OPTION_PRIVATE',	'Would you like to use your private collection directory in media directory?');
define('_IM_OPTION_ITEMDISPLAY',	'How many files do you want to display in a list? (5-50 is preferrable)');
define('_IM_OPTION_GREYBOX',	'Would you like to use GreBox utility to open Media Control window?');
define('_IM_OPTION_EACHBLOGDIR',	'Would you like to use the media directory belonged to its own weblog directory?');

define('_IM_HEADER_TEXT',	' - ImprovedMedia plugin for Nucleus CMS');
define('_IM_HEADER_RENAME_CONFIRM',	'File Rename (step 1/1)');
define('_IM_HEADER_ERASE_CONFIRM',	'File Erase (step 1/1)');
define('_IM_HEADER_UPLOAD_SELECT',	'File Upload (step 1/1)');
define('_IM_HEADER_EMBED_CONFIRM',	'File Embed (step 1/2)');
define('_IM_HEADER_EMBED',	'File Embed (step 2/2)');
define('_IM_HEADER_SUBDIR_CREATE_CONFIRM',	'Sub Directory Create (step1/1)');
define('_IM_HEADER_SUBDIR_REMOVE_CONFIRM',	'Sub Directory Remove (step1/1)');
define('_IM_HEADER_SUBDIR_RENAME_CONFIRM',	'Sub Directory Rename (step1/1)');

define('_IM_ANCHOR_TEXT',	'Media Control');
define('_IM_VIEW_TT',	': View this (Open new window)');
define('_IM_FILTER',	'Filter: ');
define('_IM_FILTER_APPLY',	'Apply Filter');
define('_IM_FILTER_LABEL',	'Filter (case-insentive): ');
define('_IM_UPLOAD_TO',	'Upload to...');
define('_IM_UPLOAD_NEW',	'Upload new file...');
define('_IM_UPLOADLINK',	'Upload a new file');
define('_IM_COLLECTION_SELECT',	'Select');
define('_IM_COLLECTION_TT',	'Switch to this collection directory');
define('_IM_COLLECTION_LABEL',	'Current collection directory: ');
define('_IM_MODIFIED',	'Modified');
define('_IM_FILENAME',	'Filename');
define('_IM_DIMENSIONS',	'Dimensions');
define('_IM_WEBLOG_LABEL',	'Weblog');

define('_IM_FORBIDDEN_ACCESS',	'This access is done with forbidden way.');
define('_IM_ALT_TOOLONG',	'Description about this file is restricted within 40 letters.');
define('_IM_ERASE_FAILED',	'Fail to erase the file.');
define('_IM_MISSING_FILE',	'The request file is missing.');
define('_IM_MISSING_DIRECTORY',	'The collection directory is missing.');
define('_IM_REMIND_DIRECTORY',	'Please make some directories at the directory for uploading files.');
define('_IM_REMIND_MEDIADIR',	'The directory for media files is not appropriate. Confirm the media directory and the permissions.');
define('_IM_RENAME_FORBIDDEN',	'Forbidden to access the file. Please check your right of access.');
define('_IM_RENAME_FILEEXIST',	'The name you want to rename to already exists.');
define('_IM_RENAME_TOOLONG',	'The file name is restricted within 30 letters.');
define('_IM_RENAME_WRONG',	'The new name includes some disallowed charactors!');
define('_IM_NOTICE',	'NOTICE!');
define('_IM_FUNCTION',	'function');
define('_IM_RENAME_FAILED',	'Fail to rename the file.');
define('_IM_RENAME_BLANK',	'The file name you want to rename to is blank.');
define('_IM_RENAME',	'Rename');
define('_IM_RENAME_DESCRIPTION',	'Please enter the name you want to rename to, without file prefix. You can use only Alphabets, numbers, underscores, hyphen-minus and plus (Maximum 30 letters).');
define('_IM_RENAME_AFTER',	'Name you want to rename to');
define('_IM_RENAME_FILENAME',	'Selected file');
define('_IM_FILENAME_CLICK',	'File name(Click to open)');
define('_IM_FILTER_NONE',	'No Filter');
define('_IM_ACTION',	'Action');
define('_IM_RETURN',	'Go to Overview');
define('_IM_COLLECTION',	'Collection Directory');
define('_IM_COLLECTION_DESC',	'Please choose the directory where you will upload.');
define('_IM_SHORTNAME',	'Short Name of the website');
define('_IM_UPDATE',	'Update');
define('_IM_TYPE',	'Type');
define('_IM_ERASE',	'Erase');
define('_IM_ERASE_CONFIRM',	'Are you sure to erase the file below?');
define('_IM_ERASE_DONE',	'Do Erase');
define('_IM_INCLUDE',	'Insert this');
define('_IM_INCLUDE_DESC',	'Are you sure to insert the file below?');
define('_IM_INCLUDE_ALT',	'Description of this file');
define('_IM_INCLUDE_ALT_DESC',	'Enter the description of this file. Required!');
define('_IM_INCLUDE_MODIFIED',	'Modify');
define('_IM_INCLUDE_FILE_SELECTED',	'The file you selected: ');
define('_IM_INCLUDE_WAY',	'How to show this file');
define('_IM_INCLUDE_WAY_POPUP',	'include this image with the alternative text in the item and popup clicking');
define('_IM_INCLUDE_WAY_INLINE',	'Include this image with the original size in the item');
define('_IM_INCLUDE_WAY_OTHER',	'The description about this file is shown and the users can display to click it.');
define('_IM_INCLUDE_CODE',	'Code which indicates this');
define('_IM_INCLUDE_CODE_DESC',	'Following code is embedded in the input screen. Do not reedit this code.');
define('_IM_INCLUDE_WAY_DECIDE',	'Decide');
define('_IM_UPLOAD_USED_ASCII',	'File name should be in English');
define('_IM_UPLOAD_USED_ASCII_DESC1',	'Please name the file in English. <br />It will be the garbled characters and you can\'t access the file if you upload it with Japanese name.');
define('_IM_UPLOAD_USED_ASCII_DESC2',	'Then please use the erase function on this plugin.<br />After, rename it and re-upload it.');
define('_IM_UPLOAD_USED_FILETYPE',	'Available file types');
define('_IM_UPLOAD_CONPLETE',	'The file is uploaded.');
define('_IM_COLLECTION_AMOUNT',	'Num. of Files: ');
define('_IM_COLLECTION_BRANK',	'Nothing exists at collection directory: ');
define('_IM_REQUIREMENT',	'Enter description about the selected file');
define('_IM_ITEMDISPLAY_WRONG',	'Too much or too less number of files in a list!');

define('_IM_SUBDIR',	'Sub Directory');
define('_IM_COLLECTION_FAILED_READ',	'Fail to get the list in this collection directory. COnfirm your permissions to access this directory.');
define('_IM_SUBDIR_LABEL',	'Sub Collection Directory: ');
define('_IM_SUBDIR_SELECT',	'Select');
define('_IM_SUBDIR_NONE',	'Nothing');
define('_IM_SUBDIR_TT',	'Switch to this sub collection directory');
define('_IM_SUBDIR_DESC',	'Please choose the sub directory where you will upload. if there are no sub directories, the file will be uploaded to the collection directory.');
define('_IM_DISPLAY_FILES',	'Display files');
define('_IM_SUBDIR_REMOVE',	'Remove directory');
define('_IM_DISPLAY_SUBDIR',	'Manage Sub Directory');
define('_IM_DISPLAY_SUBDIR_TT',	'Move ');
define('_IM_DISPLAY_SUBDIR_SELECT',	'Choice');
define('_IM_CREATE_SUBDIR_CONFIRM',	'Create Sub Directory');
define('_IM_CREATE_SUBDIR_COLLECTION_LABEL',	'Choose collection directory');
define('_IM_CREATE_SUBDIR_COLLECTION',	'Please chose the collection directory where you would like to create sub directory.');
define('_IM_CREATE_SUBDIR_INPUT_NAME',	'Name of sub directory');
define('_IM_CREATE_SUBDIR_CHARS',	'the name of sub directory should be ');
define('_IM_CREATE_SUBDIR_CHARS_DESC',	'For name of directories, the charactors is limited within alphabeds, numbers, underscores, hyphenminus and plus. Maximum 20 letters.');
define('_IM_RENAME_SUBDIR_BLANK',	'');
define('_IM_RENAME_SUBDIR_TOOLONG',	'The sub directory name should be restricted within 20 letters.');
define('_IM_RENAME_SUBDIR_WRONG',	'the name for sub directory includes disallowed charactors.');
define('_IM_RENAME_SUBDIR_DUPLICATE',	'Directory with the same name is already exists.');
define('_IM_CREATE_SUBDIR_WRONG',	'Creating sub directory failed. Please ask the manager of your server.');
define('_IM_RENAME_SUBDIR_COLLECTION',	'Sub directory which you want to rename');
define('_IM_SUBDIR_NUM_FILES',	'the number of files');
define('_IM_DISPLAY_SUBDIR_LABEL1',	'the number of directories: ');
define('_IM_DISPLAY_SUBDIR_LABEL2',	', the number of files: ');
define('_IM_DISPLAY_SUBDIR_RETURN',	'File list');
define('_IM_REMOVE_SUBIDR',	'Remove sub directory');
define('_IM_REMOVE_SUBIDR_CONFIRM',	'Confirm to remove sub directory');
define('_IM_REMOVE_SUBIDR_REMIND',	'When you remove sub directory, all of files in the sub directory is erased automatically. But the code indicating the file in certain item is not modified automatically. You are not allowd to remove collection directories.');
define('_IM_REMOVE_SUBDIR_FAILED', 	'Fail to remove this sub directory. Confirm your permissions to this sub directory or included files.');
define('_IM_DISPLAY_SUBDIR_CAPTION',	'Sub directory list');
define('_IM_DISPLAY_SUBDIR_NOTHING',	'No sub directories');
define('_IM_SUBDIR_REMOVE_FAILED',	'Fail to remove this sub directory. Confirm your permission to this sub directory or included files.');
define('_IM_SUBDIR_FAILED_READ',	'Fail to get the list of this sub directory. Confirm your permission to access this sub directory.');
?>
