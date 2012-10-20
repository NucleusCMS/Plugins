<?php
/**
 * MediaUtils plugin for Nucleus CMS
 * Version 0.9.6 (1.0 RC2) for PHP5
 * Written By Mocchi, Apr. 04, 2011
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

class NP_MediaUtils extends NucleusPlugin {
	public function getName()			{ return 'MediaUtils'; }
	public function getAuthor()		{ return 'Mocchi'; }
	public function getURL()			{ return 'http://japan.nucleuscms.org/wiki/plugins:mediautils'; }
	public function getVersion()		{ return '0.9.6 (1.0 RC2)'; }
	public function getDescription()	{ return 'Load MediaUtils, static function set for media management. Another function of this plugin is keeping Cookies for identifying weblog id.'; }
	
	public function getMinNucleusVersion() 	{ return 340; }
	public function supportsFeature($feature) { return in_array ($feature, array ('SqlTablePrefix', 'SqlApi')); }
	public function getEventList()	{ return array('PostAuthentication', 'InitSkinParse', 'PreSendContentType'); }
	
	/*
	 * NOTICE: Event drivened method can get a correct blogid in initSkinParse.
	 */
	public function event_PostAuthentication($data) {
		global $CONF;
		static $blogid = 0;
		static $blogs = array();
		
		if (!class_exists('MediaUtils', FALSE)) {
			include ($this->getDirectory() . 'MediaUtils.php');
		}
		
		MediaUtils::$lib_path = preg_replace('#/*$#', '', $this->getDirectory());
		MediaUtils::$prefix = (boolean) $CONF['MediaPrefix'];
		MediaUtils::$maxsize = (integer) $CONF['MaxUploadSize'];
		
		$suffixes = explode(',', $CONF['AllowedTypes']);
		foreach ($suffixes as $suffix) {
			$suffix = trim($suffix);
			if(!in_array($suffix, MediaUtils::$suffixes)) {
				MediaUtils::$suffixes[] = strtolower($suffix);
			}
		}
		
		$result = sql_query('SELECT bnumber, bshortname FROM ' . sql_table('blog') . ';');
		while(FALSE !== ($row = sql_fetch_assoc($result))) {
			$blogs[$row['bnumber']] = $row['bshortname'];
		}
		MediaUtils::$blogs = $blogs;
		
		if (array_key_exists('blogid', $_GET)) {
			$blogid = (integer) $_GET['blogid'];
		} else if (array_key_exists('blogid', $_POST)) {
			$blogid = (integer) $_POST['blogid'];
		} else if (array_key_exists('itemid', $_GET)) {
			$blogid = (integer) getBlogIDFromItemID((integer) $_GET['itemid']);
		} else if (array_key_exists('itemid', $_POST)) {
			$blogid = (integer) getBlogIDFromItemID((integer) $_POST['itemid']);
		} else if (array_key_exists(MediaUtils::$cookiename, $_COOKIE)) {
			$blogid = (integer) $_COOKIE['blogid'];
		}
		
		if (!$blogid || !array_key_exists($blogid, $blogs)) {
			self::setCookie(-1);
			return;
		}
		
		MediaUtils::$blogid = (integer) $blogid;
		MediaUtils::$bshortname = (string) $blogs[$blogid];
		self::setCookie(1);
		
		return;
	}
	
	public function event_PreSendContentType($data) {
		global $blog, $blogid;
		if (MediaUtils::$blogid) {
			return;
		}
		
		if (!$blogid && !$blog) {
			self::setCookie(-1);
			return;
		}
		
		if (!$blogid) {
			MediaUtils::$blogid = $blog->getID();
		} else {
			MediaUtils::$blogid = $blogid;
		}
		
		if (!$blog) {
			MediaUtils::$bshortname = $manager->getBlog(MediaUtils::$blogid)->getShortName();
		} else {
			MediaUtils::$bshortname = $blog->getShortName();
		}
		
		self::setCookie(1);
		return;
	}
	
	public function event_InitSkinParse($data) {
		global $blogid;
		if (MediaUtils::$blogid != $blogid) {
			MediaUtils::$blogid = $blogid;
			MediaUtils::$bshortname = MediaUtils::$blogs[MediaUtils::$blogid];
			self::setCookie(1);
		}
		return;
	}
	
	private function setCookie($factor) {
		global $CONF;
		$factor = (integer) $factor;
		
		setcookie($CONF['CookiePrefix'] . MediaUtils::$cookiename, MediaUtils::$blogid, time()+180*$factor, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		return;
	}
}
