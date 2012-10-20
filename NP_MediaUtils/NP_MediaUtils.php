<?php
/**
 * MediaUtils plugin for Nucleus CMS
 * Version 1.0.0 for PHP5
 * Written By Mocchi, Oct. 20, 2011
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

class NP_MediaUtils extends NucleusPlugin
{
	public function getName()			{ return 'MediaUtils'; }
	public function getAuthor()		{ return 'Mocchi'; }
	public function getURL()			{ return 'http://japan.nucleuscms.org/wiki/plugins:mediautils'; }
	public function getVersion()		{ return '1.0.0'; }
	public function getDescription()	{ return 'Load MediaUtils, static function set for media management. Another function of this plugin is keeping Cookies for identifying weblog id.'; }
	
	public function getMinNucleusVersion() 	{ return 340; }
	public function supportsFeature($feature) { return in_array ($feature, array ('SqlTablePrefix', 'SqlApi')); }
	public function getEventList()	{ return array('PostAuthentication', 'InitSkinParse', 'PreSendContentType'); }
	
	public function init()
	{
		if ( !class_exists('MediaUtils', FALSE) )
		{
			include ($this->getDirectory() . 'MediaUtils.php');
		}
		
		return;
	}
	
	public function event_PostAuthentication(&$data)
	{
		global $CONF;
		static $blogid = 0;
		static $blogs = array();
		
		MediaUtils::$lib_path = preg_replace('#/*$#', '', $this->getDirectory());
		MediaUtils::$prefix = (boolean) $CONF['MediaPrefix'];
		MediaUtils::$maxsize = (integer) $CONF['MaxUploadSize'];
		
		$suffixes = explode(',', $CONF['AllowedTypes']);
		foreach ( $suffixes as $suffix )
		{
			$suffix = trim($suffix);
			if(!in_array($suffix, MediaUtils::$suffixes)) {
				MediaUtils::$suffixes[] = strtolower($suffix);
			}
		}
		
		$result = sql_query('SELECT bnumber, bshortname FROM ' . sql_table('blog') . ';');
		while ( FALSE !== ($row = sql_fetch_assoc($result)) )
		{
			$blogs[$row['bnumber']] = $row['bshortname'];
		}
		MediaUtils::$blogs =& $blogs;
		
		if ( array_key_exists('blogid', $_GET) )
		{
			$blogid = (integer) $_GET['blogid'];
		}
		else if ( array_key_exists('blogid', $_POST) )
		{
			$blogid = (integer) $_POST['blogid'];
		}
		else if ( array_key_exists('itemid', $_GET) && function_exists('getBlogIDFromItemID') )
		{
			$blogid = (integer) getBlogIDFromItemID((integer) $_GET['itemid']);
		}
		else if ( array_key_exists('itemid', $_POST) && function_exists('getBlogIDFromItemID') )
		{
			$blogid = (integer) getBlogIDFromItemID((integer) $_POST['itemid']);
		}
		else if ( array_key_exists(MediaUtils::$cookiename, $_COOKIE) )
		{
			$blogid = (integer) $_COOKIE['blogid'];
		}
		else
		{
			return;
		}
		
		MediaUtils::$blogid =& $blogid;
		MediaUtils::$bshortname =& MediaUtils::$blogs[MediaUtils::$blogid];
		
		return;
	}
	
	/*
	 * When index.php is directly called, there is no $blogid at first.
	 * $blogid is set when selector() is called after globalfunctions is included (selectBlog() is optional).
	 * In this case, plugin can finally get correct $blogid in InitSkinParse event.
	 * (Nucleus CMS 3.64, Apr. 06, 2011)
	 */
	public function event_InitSkinParse(&$data)
	{
		global $blogid;
		if ( MediaUtils::$blogid == 0 )
		{
			MediaUtils::$blogid = $blogid;
			MediaUtils::$bshortname = MediaUtils::$blogs[MediaUtils::$blogid];
		}
		return;
	}
	
	public function event_PreSendContentType($data)
	{
		global $CONF;
		
		/* delete my cookie */
		if ( MediaUtils::$blogid == 0 )
		{
			setcookie($CONF['CookiePrefix'] . MediaUtils::$cookiename, MediaUtils::$blogid, -1, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		}
		/* set my cookie */
		else
		{
			setcookie($CONF['CookiePrefix'] . MediaUtils::$cookiename, MediaUtils::$blogid, time()+180, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		}	
		return;
	}
}
