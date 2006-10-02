<?php

/**
  * Plugin for Nucleus CMS (http://plugins.nucleuscms.org/)
  * Copyright (C) 2003 The Nucleus Plugins Project
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  *
  * see license.txt for the full license
  */

/**
 * Usage:
 * 		
 *
 * Versions:
 *  0.9c	2005-09-28  eph
 *		- upgrade to TinyMCE 2RC3
 *		- lots of path fixes
 *		- experimental GZip option. Should load quicker if it works
 *  0.9b	2005-09-24  eph
 *		- upgrade to TinyMCE 2RC2
 *		- addition of File Manager
 *		- bugfixes and cleaner uninstall
 *	0.9		2005-07-16	roel
 *		- initial implementation, mostly copied over from NP_EditControls by karma - http://demuynck.org 
 */

class NP_TinyMCE2j extends NucleusPlugin {

	function NP_TinyMCE2j() {
		// $this->baseUrl = $this->getAdminURL();
		// hardcoded relative path to avoid domain security issues (IE6 'Access is denied' error) 
		global $CONF;
		$this->baseUrl = str_replace ($CONF['AdminURL'],'',$this->getAdminURL()); 
		
	}

	function getName() 		{ return 'TinyMCE 2RC3'; }
	function getAuthor()  	{ return 'karma | roel | eph'; }
	function getURL()  		{ return 'http://demuynck.org/'; }
	function getVersion() 	{ return '0.9c'; }
	function getMinNucleusVersion() { return 300; }
	function getDescription()
	{
		return 'WYSIWYG XHTML 1.0 editor. Mozilla, MSIE and FireFox (Safari experimental).';
	}
		


	/**
	 * Make sure plugin still works when a database table prefix is activated for 
	 * the Nucleus installation. (Nucleus refuses to install plugins which do not
	 * support SqlTablePrefix when a database prefix is active)
	 */

	function supportsFeature($what) {
   	switch($what) {
         	case 'HelpPage':
	        		return 0;
         			break;
			case 'SqlTablePrefix':
					return 1;
					break;
        	default:
           			return 0;
     	}
  	}

	function install() {
		// create plugin options (per-blog options)
		$this->createBlogOption('use_tinymce', 'Use TinyMCE editor', 'yesno', 'yes');

		// create plugin options (admin)
		$this->createOption('use_tgzip', 'Use TinyMCE GZip compression (experimental)', 'yesno', 'no');
		
		// disable the default javascript editbar that comes with nucleus
		mysql_query("UPDATE ".sql_table('config')." SET value='1' WHERE name='DisableJSTools'");
		
		// disable auto-linebreak conversions
		$this->setLinebreakConversion(0);
		
	}
	
	function setLinebreakConversion($mode) {
		// modify auto-linebreak on the fly. Thx to alfmiti :)
		mysql_query("UPDATE ".sql_table('blog')." SET bconvertbreaks=".$mode);

	}

	function unInstall() {
		// restore to standard settings
		$this->setLinebreakConversion(1);
		mysql_query("UPDATE ".sql_table('config')." SET value='2' WHERE name='DisableJSTools'");
	}

	/**
	 * List of events we want to subscribe to
	 */
	function getEventList() {
		return array(
			'AdminPrePageHead', 			// include javascript on admin add/edit pages
			'BookmarkletExtraHead',			// include javascript on bookmarklet pages
			'PreSendContentType' 			// we need to force text/html instead of application/xhtml+xml
		);
	}
	
	/**
	 * Hook into the <head> section of bookmarkler area pages.
	 * Insert extra script/css includes there.
	 */
	function event_BookmarkletExtraHead(&$data)
	{
		$this->_getExtraHead($data['extrahead']);	
	}

	/**
	 * Hook into the <head> section of admin area pages. When the requested page is an "add item" or
	 * "edit item" form, include the extra code.
	 */
	function event_AdminPrePageHead(&$data) 
	{
		$action = $data['action'];
		if (($action != 'createitem') && ($action != 'itemedit'))
			return;
		
		$this->_getExtraHead($data['extrahead']);
		
	}	
	
	/**
	 * Returns the extra code that needs to be inserted in the <head>...</head> section of pages that
	 * use tinyMCE
	 */
	function _getExtraHead(&$extrahead)
	{
		global $CONF, $manager;

		// Found no function to detect blogid in this time -> do the long way
		if (is_array($manager->blogs)) {
			$tmp_keys = array_keys($manager->blogs);
			$blogid = $tmp_keys[0];
		}      
		
		// get the options for the current blog
		$bUseEditor	= ($this->getBlogOption($blogid, 'use_tinymce') == 'yes');

		// add code for html editor
		if ($bUseEditor)
		{
			// To avoid conflicts if a other user use only textmode we must set this on all calls
			$CONF['DisableJsTools'] = 1; // overrule simple global settings
			$this->setLinebreakConversion(0);
			
			// GZip compression?
			if ($this->getOption('use_tgzip') == 'yes')
				$editorCode = '<script type="text/javascript" src="'.$this->baseUrl.'tiny_mce_gzip.php"></script>';
			else 
				$editorCode = '<script type="text/javascript" src="'.$this->baseUrl.'tiny_mce.js"></script>';

		if(_CHARSET == 'UTF-8'){
			$lang_jp = 'ja_utf8';
		}elseif(_CHARSET == 'EUC-JP'){
			$lang_jp = 'ja_euc';
		}else{
			$lang_jp = 'en';
		}
			$editorCode .= <<<EOD
<script type="text/javascript">
 tinyMCE.init({ 
	language : "{$lang_jp}",
	mode : "textareas",
	theme : "advanced",
	document_base_url : "{$CONF['IndexURL']}",
	plugins : "paste,ibrowser,filemanager,emotions,searchreplace,table",
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,forecolor,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,link,unlink",
	theme_advanced_buttons1_add : "ibrowser,filemanager,emotions,separator,code",
	theme_advanced_buttons2 : "undo,redo,separator,tablecontrols,separator,pastetext,pasteword,search,replace",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_path_location : "bottom",
	extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],span[class|align|style]",
	paste_create_paragraphs : true,
	paste_use_dialog : true,
	paste_auto_cleanup_on_paste : true,
	theme_advanced_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
	debug : false

});
</script>
EOD;

			$extrahead .= $editorCode;
		} else {
			$CONF['DisableJsTools'] = 2;
			$linebreak_conversion = $this->getOption('enable_br') == 'yes' ? 1 : 0;
			$this->setLinebreakConversion($linebreak_conversion);
		}

	}
	
	/**
	 * Nucleus sends its admin area pages as application/xhtml+xml to browsers that can handle this.
	 *
	 * Unfortunately, this causes javascripts that alter page contents through non-DOM methods
	 * to stop working correctly. As the jscalendar and htmlarea both need this, we're forcing
	 * the content-type to text/html for add/edit item forms.
	 */
	function event_PreSendContentType(&$data)
	{
		$pageType = $data['pageType'];
		if ($pageType == 'skin')
			return;
		if (	($pageType != 'bookmarklet-add')
			&&	($pageType != 'bookmarklet-edit')
			&&	($pageType != 'admin-createitem')
			&& 	($pageType != 'admin-itemedit')
			)
			return;
		
		if ($data['contentType'] == 'application/xhtml+xml')
			$data['contentType'] = 'text/html';
	}
	
}
?>