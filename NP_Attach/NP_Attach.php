<?php
/**
 * Attach plugin for Nucleus CMS
 * Version 1.0.0 for PHP5
 * Written By Mocchi, Oct. 20, 2012
 * Original code was written by NKJG and yamamoto, May 02, 2009
 * This plugin depends on NP_Thumbnail and NP_MediaUtils
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

// TODO (hard) If itemOption is set as "hidden", PrePluginOptionsUpdate event is not generated, related Nucleus CMS library problem.
// TODO (hard) There are no ways to show the error message even if upload processing is failed.
// TODO (medium) all of the plugin options are purged when uninstalling plugins.

class NP_Attach extends NucleusPlugin
{
	static private $buffer = false;
	static private $blogid = 0;
	static private $itemid = 0;
	static private $amount = 0;
	static private $data = array();
	
	public function getName()			{ return 'Attach'; }
	public function getAuthor()		{ return 'Mocchi, yamamoto, NKJG'; }
	public function getURL()			{ return 'http://japan.nucleuscms.org/wiki/plugins:attach'; }
	public function getVersion()		{ return '1.0.0'; }
	public function getDescription()	{ return _NP_ATTACH_01; }
	public function getPluginDep()	{ return array('NP_MediaUtils', 'NP_Thumbnail');}
	public function getMinNucleusVersion()	{return 340;}
	public function supportsFeature($feature)	{ return in_array($feature, array('SqlTablePrefix', 'SqlApi'));}
	public function getEventList()
	{
		return array(
			'PrePluginOptionsEdit', 
			'AdminPrePageHead',
			'BookmarkletExtraHead',
			'PreItem',
			'PreAddItem',
			'PreUpdateItem',
			'PostAddItem',
			'PostUpdateItem',
			'PrePluginOptionsUpdate',
			'AddItemFormExtras',
			'EditItemFormExtras');
	}
	
	public function install()
	{
		$this->createOption('maxwidth', '_NP_ATTACH_02', 'text', '90',  'datatype=numerical');
		$this->createOption('maxheight', '_NP_ATTACH_03', 'text', '90',  'datatype=numerical');
		$this->createOption('admin_popup_template', '_NP_ATTACH_04', 'textarea', '<a href="<%rawpopuplink%>" title="<%popuptext%>" onclick="<%popupcode%>"><img src="<%thumb_url%>" width="<%thumb_width%>" height="<%thumb_height%>" alt="<%popuptext%>" /></a>');
		$this->createOption('admin_media_template', '_NP_ATTACH_05', 'textarea', '<a href="<%link%>" title="<%text%>"><%text%></a>');
		$this->createBlogOption('amount', '_NP_ATTACH_06', 'text', '3');
		$this->createBlogOption('blog_image_template', '_NP_ATTACH_07', 'textarea', '<img src="<%link%>" width="<%width%>" height="<%height%>" alt="<%text%>" />');
		$this->createBlogOption('blog_media_template', '_NP_ATTACH_08', 'textarea', '<a href="<%link%>" title="<%text%>"><%text%></a>');
		$this->createItemOption('media', '_NP_ATTACH_09', 'text', '');
		return;
	}
	
	public function uninstall()
	{
		return;
	}
	
	public function init()
	{
		$locale = '';
		
		/* new API */
		if ( class_exists('i18n', FALSE) )
		{
			$locale = i18n::get_current_locale() . '.' . i18n::get_current_charset() . '.php';
		}
		/* old API */
		else
		{
			$language = preg_replace('#[/|\\\\]#', '', getLanguageName());
			if ( $language == 'japanese-euc' )
			{
				$locale = 'ja_Jpan_JP.EUC-JP.php';
			}
			else if ( $language = 'japanese-utf8' )
			{
				$locale = 'ja_Jpan_JP.UTF-8.php';
			}
		}
		
		if ( !$locale || !file_exists($this->getDirectory() . $locale) )
		{
			include($this->getDirectory() . 'en_Latn_US.ISO-8859-1.php');
		}
		else
		{
			include($this->getDirectory() . $locale);
		}
		
		return;
	}
	
	/*
	 * for translation
	 */
	public function event_PrePluginOptionsEdit(&$data)
	{
		/* Old version do not support natively */
		if ( getNucleusVersion() < 400  )
		{
			if ( $data['context'] != 'global' )
			{
				foreach ( $data['options'] as $key => $option )
				{
					if ( $option['pid'] == $this->getID() )
					{
						if ( defined($option['description']) )
						{
							$data['options'][$key]['description'] = constant($option['description']);
						}
						if ( $option['type'] == 'select' )
						{
							foreach ( explode('|', $option['typeinfo']) as $option )
							{
								if ( defined($option) )
								{
									$data['options'][$key]['typeinfo'] = str_replace($option, constant($option), $data['options'][$key]['typeinfo']);
								}
							}
						}
					}
				}
			}
			else if ($data['plugid'] == $this->getID() )
			{
				foreach ( $data['options'] as $key => $option )
				{
					if ( defined($option['description']) )
					{
						$data['options'][$key]['description'] = constant($option['description']);
					}
					if ( $option['type'] == 'select' )
					{
							foreach ( explode('|', $option['typeinfo']) as $option )
							{
								if ( defined($option) )
								{
									$data['options'][$key]['typeinfo'] = str_replace($option, constant($option), $data['options'][$key]['typeinfo']);
								}
							}
					}
				}
			}
		}
		
		return;
	}
	
	/*
	 * Change enctype attribute of form element as 'multipart/form-data'
	 * Insert interface
	 */
	public function event_AdminPrePageHead(&$data)
	{
		if ( !in_array ($data['action'], array('createitem', 'itemedit')) )
		{
			self::prepareEnctype();
		}
		return;
	}
	
	public function event_BookmarkletExtraHead(&$data)
	{
		self::prepareEnctype();
		return;
	}
	
	public function event_AddItemFormExtras(&$data)
	{
		global $DIR_MEDIA;
		$blog = $data['blog'];
		self::$blogid = $blog->blogid;
		self::$itemid = 0;
		self::setEnctype();
		$this->setData($DIR_MEDIA);
		$this->showInterface();
		return;
	}
	
	public function event_EditItemFormExtras(&$data)
	{
		global $DIR_MEDIA;
		$blog = $data['blog'];
		self::$blogid = $blog->blogid;
		self::$itemid = $data['itemid'];
		self::setEnctype();
		$this->setData($DIR_MEDIA);
		$this->showInterface();
		return;
	}
	
	/*
	 * Process upload and update plugin item option
	 */
	public function event_PreAddItem(&$data)
	{
		self::$blogid = $data['blog']->blogid;
		return;
	}
	
	public function event_PreUpdateItem(&$data)
	{
		self::$blogid = $data['blog']->blogid;
		return;
	}
	
	public function event_PostAddItem(&$data)
	{
		self::$itemid = $data['itemid'];
		return;
	}
	
	public function event_PostUpdateItem(&$data)
	{
		self::$itemid = $data['itemid'];
		return;
	}
	
	public function event_PrePluginOptionsUpdate(&$data)
	{
		global $DIR_MEDIA, $member;
		
		if ( $data['plugid'] != $this->getID() || $data['optionname'] != 'media' )
		{
			return;
		}
		
		$this->setData($DIR_MEDIA);
		self::batchRequest($DIR_MEDIA, $member->getID());
		
		$value = '';
		foreach ( self::$data as $datum )
		{
			if ( !$datum['path'] )
			{
				continue;
			}
			$value .= "{$datum['path']}:{$datum['alt']}:{$datum['way']};";
		}
		
		$data['value'] = $value;
		return;
	}
	
	/*
	 * Show media in item
	 */
	public function event_PreItem(&$data)
	{
		global $DIR_MEDIA;
		
		self::$blogid =& $data['blog']->blogid;
		self::$itemid =& $data["item"]->itemid;
		$this->setData($DIR_MEDIA);
		
		$data["item"]->body = preg_replace_callback("#<\%Attach\((.+?)(,.+?)?(,.+?)?\)%\>#", array($this, 'getParsedTag'), $data["item"]->body);
		$data["item"]->more = preg_replace_callback("#<\%Attach\((.+?)(,.+?)?(,.+?)?\)%\>#", array($this, 'getParsedTag'), $data["item"]->more);
		return;
	}
	
	public function doTemplateVar(&$item)
	{
		global $DIR_MEDIA, $blogid;
		
		/* receive an argument, with prevention of E_STRICT*/
		$args = func_get_args();
		if ( !array_key_exists(0, $args) )
		{
			return;
		}
		else
		{
			$mediumid = $args[0];
		}
		
		if ( !self::$itemid || !self::$blogid )
		{
			self::$blogid = $blogid;
			self::$itemid = $item->itemid;
			$this->setData($DIR_MEDIA);
		}
		
		$params = array('item', $mediumid);
		$this->getParsedTag($params);
		return;
	}
	
	private function getParsedTag($match)
	{
		global $DIR_MEDIA, $manager;
		
		/* receice maximum dimension */
		$maxwidth = 0;
		$maxheight = 0;
		if ( array_key_exists(3, $match) )
		{
			$maxheight = (integer) trim($match[3], ',');
		}
		if ( array_key_exists(2, $match) )
		{
			$maxwidth = (integer) trim($match[2], ',');
		}
		
		/* ID in tag starts 1 but ID in program starts 0 */
		$mediumid = $match[1];
		$mediumid--;
		
		/* filled tag or blank if failed */
		$tag = $this->generateParsedTag($maxwidth, $maxheight);
		
		return $tag;
	}
	
	private function generateParsedTag($maxwidth, $maxheight)
	{
		global $manager, $DIR_MEDIA;
		
		/* check medium has path */
		$data = &self::$data;
		if ( !array_key_exists($mediumid, $data) || !$data[$mediumid]['path'] )
		{
			return;
		}
		
		/* receive an instance of NP_Thumbnail class */
		$NP_Thumbnail =& $manager->getPlugin('NP_Thumbnail');
		
		/* confirm to have maximum dimension */
		if ( $maxwidth == 0 && $maxheight == 0 )
		{
			$maxwidth = $NP_Thumbnail->getOption('maxwidth');
			$maxheight = $NP_Thumbnail->getOption('maxheight');
		}
		
		/* receive an instance of MEDIUM class */
		if ( FALSE === ($medium = new MEDIUM($DIR_MEDIA, $data[$mediumid]['path'], MediaUtils::$prefix)) )
		{
			return;
		}
		
		/* take the instance having correct resampled dimension */
		if ( array_key_exists($medium->mime, MediaUtils::$image_mime)
		   && !$medium->setResampledSize($maxwidth, $maxheight) )
		 {
			return;
		}
		
		/* receive template */
		if ( !array_key_exists($medium->mime, MediaUtils::$image_mime)
		   || $data[$mediumid]['way'] == 'anchor' )
		 {
			$template = $this->getBlogOption(self::$blogid, 'blog_media_template');
		}
		else if ( $data[$mediumid]['way'] == 'original' )
		{
			$template = $this->getBlogOption(self::$blogid, 'blog_image_template');
		}
		else
		{
			$template = $NP_Thumbnail->getBlogOption(self::$blogid, 'thumb_template');
		}
		
		/* return filled template */
		return $NP_Thumbnail->generateTag($template, $medium, $data[$mediumid]['alt']);
	}
	
	private function showInterface()
	{
		global $CONF, $DIR_LIBS, $DIR_MEDIA, $manager;
		
		if ( !class_exists('BODYACTIONS', FALSE) )
		{
			include($DIR_LIBS . 'BODYACTIONS.php');
		}
		$action = new BODYACTIONS;
		
		if ( !$manager->pluginInstalled('NP_Thumbnail') )
		{
			return;
		}
		$NP_Thumbnail = &$manager->getPlugin('NP_Thumbnail');
		$maxwidth = $NP_Thumbnail->getOption('maxwidth');
		$maxheight = $NP_Thumbnail->getOption('maxheight');
			
		if ( !self::$amount )
		{
			return;
		}
		
		$data = self::$data;
		
		if ( empty($data) )
		{
			return;
		}
		
		echo "<table frame=\"box\" rules=\"all\" summary=\"Attached Media\">\n";
		echo "<thead>\n";
		echo "<tr>\n";
		echo "<th>" . _NP_ATTACH_10 . "</th>\n";
		echo "<th>" . _NP_ATTACH_11 . "</th>\n";
		echo "<th>" . _NP_ATTACH_12 . "</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";
		echo "<tbody>\n";
		
		for ( $count = 0; $count < self::$amount; $count++ )
		{
			$id = $count + 1;
			
			echo "<tr>\n";
			echo "<td>\n";
			echo "<label for=\"alt{$count}\"><%Attach({$id})%></label>\n";
			
			if ( $data[$count]['path'] )
			{
				echo "<br />\n";
				echo "<input type=\"checkbox\" id=\"delete{$count}\" name=\"delete[{$count}]\" value=\"1\" />\n";
				echo "<label for=\"delete{$count}\">" . _NP_ATTACH_13 . "</label>\n";
			}
			
			echo "</td>\n";
			echo "<td>\n";
			
			if ( !$data[$count]['path'] )
			{
				echo "<input type=\"file\" id=\"medium{$count}\" name=\"medium[{$count}]\" size=\"1\" /><br />\n";
			}
			else
			{
				if ( FALSE === ($medium = new MEDIUM($DIR_MEDIA, $data[$count]['path'], MediaUtils::$prefix)) )
				{
					return;
				}
				
				if ( array_key_exists($medium->mime, MediaUtils::$image_mime)
				   && !$medium->setResampledSize($maxwidth, $maxheight) )
				 {
					return;
				}
				
				if ( array_key_exists($medium->mime, MediaUtils::$image_mime) )
				{
					$template = $this->getOption('admin_popup_template');
				}
				else
				{
					$template = $this->getOption('admin_media_template');
				}
				
				echo $NP_Thumbnail->generateTag($template, $medium, $data[$count]['alt']) . "<br />\n";
				echo "<input type=\"hidden\" id=\"path{$count}\" name=\"path[{$count}]\" value=\"{$data[$count]['path']}\" /><br />\n";
			}
			echo "<input type=\"text\" id=\"alt{$count}\" name=\"alt[{$count}]\" value=\"{$data[$count]['alt']}\" size=\"12\" />\n";
			echo "</td>\n";
			echo "<td>\n";
			
			if ( $data[$count]['path'] && !array_key_exists($data[$count]['mime'], MediaUtils::$image_mime) )
			{
				echo "<input type=\"hidden\" name=\"way[{$count}]\" value=\"anchor\" />\n";
				echo _NP_ATTACH_14 . "\n";
			}
			else
			{
				$thumbnail_checked = "";
				$original_checked = "";
				$anchor_checked = "";
				
				if ( $data[$count]['way'] == 'original' )
				{
					$original_checked = 'checked="checked"';
				}
				else if ($data[$count]['way'] == 'anchor' )
				{
					$anchor_checked = 'checked="checked"';
				}
				else
				{
					$thumbnail_checked = 'checked="checked"';
				}
				
				echo "<input type=\"radio\" id=\"way{$count}-anchor\" name=\"way[{$count}]\" value=\"anchor\" {$anchor_checked} />\n";
				echo "<label for=\"way{$count}-anchor\">" . _NP_ATTACH_15 . "<label><br />\n";
				echo "<input type=\"radio\" id=\"way{$count}-small\" name=\"way[{$count}]\" value=\"thumbnail\" {$thumbnail_checked} />\n";
				echo "<label for=\"way{$count}-small\">" . _NP_ATTACH_16 . "<label><br />\n";
				echo "<input type=\"radio\" id=\"way{$count}-original\" name=\"way[{$count}]\" value=\"original\" {$original_checked} />\n";
				echo "<label for=\"way{$count}-original\">" . _NP_ATTACH_17 . "<label>\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
		}
		
		echo "</tbody>\n";
		echo "</table>\n";
		return;
	}
	
	private function setData($root)
	{
		if ( self::$blogid === 0 || !self::$itemid === 0 )
		{
		 return;
		}
		
		self::$amount = $this->getBlogOption(self::$blogid, 'amount');
		$media = explode(';', $this->getitemOption(self::$itemid, 'media'));
		$data = array();
		
		for ( $count = 0; $count < self::$amount; $count++ )
		{
			if ( (self::$itemid != 0) && array_key_exists($count, $media) && !empty($media[$count]) )
			{
				$medium = explode(':', $media[$count]);
				$data[$count]['path'] = $medium[0];
				$data[$count]['alt'] = $medium[1];
				$data[$count]['way'] = $medium[2];
				
				if ( FALSE === ($file = new Medium($root, $data[$count]['path'], MediaUtils::$prefix)) )
				{
					$data[$count] = array('path' => "", 'alt' => "", 'way' => "");
				}
				
				$data[$count]['mime'] = $file->mime;
				$data[$count]['width'] = $file->width;
				$data[$count]['height'] = $file->height;
			}
			else
			{
				$data[$count] = array('path' => "", 'alt' => "", 'way' => "");
			}
		}
		
		self::$data = $data;
		return;
	}
	
	static private function batchRequest($root, $collection)
	{
		global $CONF, $manager;
		$media = array();
		$paths = array();
		$alts = array();
		$ways = array();
		$deletes = array();
		
		if ( array_key_exists('medium', $_FILES) )
		{
			$media = $_FILES['medium'];
		}
		
		if ( array_key_exists('path', $_POST) )
		{
			$paths = $_POST['path'];
		}
		
		if ( array_key_exists('alt', $_POST) )
		{
			$alts = $_POST['alt'];
		}
		
		if ( array_key_exists('way', $_POST) )
		{
			$ways = $_POST['way'];
		}
		
		if ( array_key_exists('delete', $_POST) )
		{
			$deletes = $_POST['delete'];
		}
		
		$data = &self::$data;
		
		if ( !$manager->pluginInstalled('NP_Thumbnail') )
		{
			return;
		}
		
		foreach ( $data as $key => $value )
		{
			if ( $media && array_key_exists($key, $media['name']) && !empty($media['name'][$key]) )
			{
				$medium = array();
				if ( preg_match ("#(\\\\|/|\\n)#", $media['name'][$key]) )
				{
					$data[$key] = array('path' => "", 'alt' => "", 'way' => "");
					continue;
				}
				
				$medium['name'] = &$media['name'][$key];
				$medium['size'] = $media['size'][$key];
				$medium['tmp_name'] = $media['tmp_name'][$key];
				$medium['error'] = $media['error'][$key];
				
				$result = MediaUtils::uploadMedium($root, $collection, $medium, '', $manager);
				
				if ( $result )
				{
					$data[$key]['path'] = "";
					$data[$key]['alt']  = "{$result} ({$medium['name']})";
					$data[$key]['way']  = "";
					continue;
				}
				else
				{
					$data[$key]['path'] = "{$collection}/{$medium['name']}";
				}
			}
			else if ( array_key_exists($key, $paths) && !empty($paths[$key]) )
			{
				if ( FALSE === ($medium = new MEDIUM($root, $paths[$key], MediaUtils::$prefix)) )
				{
					$data[$key] = array('path' => "", 'alt' => "", 'way' => "");
					continue;
				}
				if ( $deletes && array_key_exists($key, $deletes) && $deletes[$key] == 1 )
				{
					if ( @unlink($root . $paths[$key]) )
					{
						if ( array_key_exists($medium->mime, MediaUtils::$image_mime) )
						{
							@ unlink($root . NP_Thumbnail::getThumbPath($medium));
						}
						$data[$key] = array('path' => "", 'alt' => "", 'way' => "");
						continue;
					}
				}
			}
			
			if ( array_key_exists($key, $alts) && $alts[$key] != $value['alt'] )
			{
				$data[$key]['alt'] = htmlspecialchars($alts[$key], ENT_QUOTES, _CHARSET);
			}
			
			if ( array_key_exists($key, $ways) && $ways[$key] != $value['way'] )
			{
				if ( !in_array($ways[$key], array('thumbnail', 'original', 'anchor')) )
				{
					$data[$key]['way'] = 'thumbnail';
				}
				else
				{
					$data[$key]['way'] = $ways[$key];
				}
			}
		}
		return;
	}
	
	static private function prepareEnctype()
	{
		self::$buffer = ob_start();
		return;
	}
	
	static private function setEnctype()
	{
		if ( !self::$buffer )
		{
			return;
		}
		
		$strings = ob_get_contents();
		ob_end_clean();
		$strings = preg_replace('#action="(index.php|bookmarklet.php)"#', '$0 enctype="multipart/form-data"', $strings);
		echo $strings;
		return;
	}
}
