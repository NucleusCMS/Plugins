<?php
/**
 * ImageLimitSize plugin for Nucleus CMS
 * Version 0.9.6 (1.0 RC2) for PHP5
 * Written By Mocchi, Apr. 04, 2011
 * Original code was written by Kai Greve and maintained by shizuki and yamamoto
 * This plugin depends on NP_MediaUtils
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

class NP_ImageLimitSize extends NucleusPlugin {
	public function getName()			{return 'ImageLimitSize';}
	public function getAuthor()		{return 'Mocchi, shizuki, yamamoto, Kai Greve';}
	public function getURL()			{return 'http://japan.nucleuscms.org/wiki/plugins:imagelimitsize';}
	public function getVersion()		{return '0.9.6 (1.0 RC2)';}
	public function getDescription()	{return _NP_IMAGELIMITSIZE_01;}
	public function getPluginDep()	{return array('NP_MediaUtils');}
	public function getMinNucleusVersion()		{return 360;}
	public function supportsFeature($feature)	{ return in_array ($feature, array ('SqlTablePrefix', 'SqlApi'));}
	public function getEventList()	{return array('PrePluginOptionsEdit', 'PreMediaUpload', 'MediaUploadFormExtras');}
	
	public function install() {
		$this->createOption('maxwidth', '_NP_IMAGELIMITSIZE_02', 'text', '550', 'datatype=numerical');
		$this->createOption('maxheight', '_NP_IMAGELIMITSIZE_03', 'text', '0', 'datatype=numerical');
		$this->createBlogOption('status', '_NP_IMAGELIMITSIZE_04', 'yesno', 'yes');
		$this->createBlogOption('blog_maxwidth', '_NP_IMAGELIMITSIZE_05', 'text', '0', 'datatype=numerical');
		$this->createBlogOption('blog_maxheight', '_NP_IMAGELIMITSIZE_06', 'text', '0', 'datatype=numerical');
		return;
	}
	
	public function uninstall() {
		// plugin options are purged automatically when uninstalled.
		return;
	}
	
	public function init() {
		if((string)$_REQUEST['action'] == 'pluginlist' && !defined('_NP_IMAGELIMITSIZE_01')) {
			$language = preg_replace('#[/|\\\\]#', '', getLanguageName());
			if (file_exists($this->getDirectory() . $language.'.php')) {
				include($this->getDirectory() . $language.'.php');
			} else {
				include($this->getDirectory() . 'english.php');
			}
		}
		return;
	}
	
/*
 * for translation
 */
	public function event_PrePluginOptionsEdit ($data) {
		if(!defined('_NP_IMAGELIMITSIZE_01')) {
			$language = preg_replace('#[/|\\\\]#', '', getLanguageName());
			if (file_exists($this->getDirectory() . $language.'.php')) {
				include($this->getDirectory() . $language.'.php');
			} else {
				include($this->getDirectory() . 'english.php');
			}
		}
		if ($data['context'] != 'global') {
			foreach($data['options'] as $key => $option) {
				if ($option['pid'] == $this->getID()) {
					if (defined($option['description'])) {
						$data['options'][$key]['description'] = constant($option['description']);
					}
					if ($option['type'] == 'select') {
						foreach (explode('|', $option['typeinfo']) as $option) {
							if (defined($option)) {
								$data['options'][$key]['typeinfo'] = str_replace($option, constant($option), $data['options'][$key]['typeinfo']);
							}
						}
					}
				}
			}
		} else if ($data['plugid'] == $this->getID()) {
			foreach($data['options'] as $key => $option){
				if (defined($option['description'])) {
					$data['options'][$key]['description'] = constant($option['description']);
				}
				if ($option['type'] == 'select') {
						foreach (explode('|', $option['typeinfo']) as $option) {
							if (defined($option)) {
								$data['options'][$key]['typeinfo'] = str_replace($option, constant($option), $data['options'][$key]['typeinfo']);
							}
						}
				}
			}
		}
		return;
	}
	
/*
 * for translation
 */
	static private function t($text,$array=array()) {
		if (is_array($array)) {
			$search = array();
			$replace = array();
			
			foreach ($array as $key => $value){
				if (is_array($value)) {
					continue;
				}
				$search[] = '<%'.preg_replace('/[^a-zA-Z0-9_]+/','',$key).'%>';
				$replace[] = $value;
			}
		}
		return htmlspecialchars (str_replace($search, $replace, $text), ENT_QUOTES, _CHARSET);
	}
	
	public function event_PreMediaUpload($data) {
		global $CONF, $manager;
		
		if (!class_exists('MediaUtils', FALSE)) {
			return;
		}
		
		if (MediaUtils::$blogid == 0) {
			return;
		}
		
		if ($this->getBlogOption(MediaUtils::$blogid, 'status') == 'no') {
			return;
		}
		
		if (0 == ($maxwidth = $this->getBlogOption(MediaUtils::$blogid, 'blog_maxwidth'))) {
			$maxwidth = $this->getOption('maxwidth');
		}
		
		if (0 == ($maxheight = $this->getBlogOption(MediaUtils::$blogid, 'blog_maxheight'))) {
			$maxheight = $this->getOption('maxheight');
		}
		
		$path = basename($data['uploadfile']);
		$root = str_replace('/' . $path, '', $data['uploadfile']);
		
		if (FALSE === ($medium = new MEDIUM($root, $path, MediaUtils::$prefix))) {
			return;
		}
		
		if (!array_key_exists($medium->mime, MediaUtils::$image_mime)
		 || ($maxwidth >= $medium->width && $maxheight >= $medium->height)) {
			return;
		}
		
		if (FALSE === $medium->setResampledSize($maxwidth, $maxheight)) {
			return;
		}
		
		if (!MediaUtils::storeResampledImage($root, $path, $medium)) {
			return;
		}
		return;
	}
	
	public function event_MediaUploadFormExtras() {
		echo '<input type="hidden" name="blogid" value="' . MediaUtils::$blogid . '" />' . "\n";
		return;
	}
}
