<?php
/**
 * Thumbnail plugin for Nucleus CMS
 * Version 4.0.0 for PHP5
 * Written By Mocchi, Apr. 06, 2011
 * Original code was written by jirochou, May 23, 2004 and maintained by nakahara21
 * This plugin depends on NP_MediaUtils
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

class NP_Thumbnail extends NucleusPlugin
{
	private static $thumbdir	= '.thumb';
	private static $max_sync	= 10;
	private static $authorid	= 0;
	private static $buffering	= FALSE;
	private static $table_name	= 'plugin_thumbnail'; // not implemented yet
	
	public function getName()			{ return 'Thumbnail'; }
	public function getAuthor()		{ return 'Mocchi, nakahara21, jirochou'; }
	public function getURL()			{ return 'http://japan.nucleuscms.org/wiki/plugins:thumbnail'; }
	public function getVersion()		{ return '4.0.0'; }
	public function getDescription()	{ return _NP_THUMBNAIL_01; }
	public function getPluginDep()	{ return array('NP_MediaUtils'); }
	public function getMinNucleusVersion() 	{ return 340; }
	public function supportsFeature($feature) { return in_array ($feature, array('SqlTablePrefix', 'SqlApi'));}
	public function getEventList()	{ return array('QuickMenu', 'PrePluginOptionsEdit', 'PostAuthentication', 'PreItem', 'PostMediaUpload'); }
	
	public function install ()
	{
		$this->createOption('maxwidth', '_NP_THUMBNAIL_02', 'text', '100', 'datatype=numerical');
		$this->createOption('maxheight', '_NP_THUMBNAIL_03', 'text', '100', 'datatype=numerical');
		$this->createOption('save_thumb', '_NP_THUMBNAIL_04', 'select', 'filesystem', '_NP_THUMBNAIL_05|no|_NP_THUMBNAIL_06|filesystem');
		$this->createBlogOption('force_thumb', '_NP_THUMBNAIL_07', 'yesno', 'yes');
		$this->createBlogOption('thumb_template', '_NP_THUMBNAIL_08', 'textarea', '<a href="<%rawpopuplink%>" title="<%popuptext%>" onclick="<%popupcode%>"><img src="<%thumb_url%>" width="<%thumb_width%>" height="<%thumb_height%>" alt="<%popuptext%>" /></a>');
		return;
	}
	
	/*
	 * plugin options are purged automatically when uninstalled.
	 */
	public function uninstall()
	{
		global $DIR_MEDIA;
		MediaUtils::purgeDir($DIR_MEDIA . self::$thumbdir);
		return;
	}
	
	public function init()
	{
		global $manager;
		
		$locale = '';
		
		if ( !class_exists('Medium', FALSE) )
		{
			$manager->getPlugin('NP_Thumbnail');
		}
		
		
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
	public function event_PrePluginOptionsEdit($data)
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
	 * for translation
	 */
	static private function t($text, $array=array())
	{
		if ( is_array($array) )
		{
			$search = array();
			$replace = array();
			
			foreach ( $array as $key => $value )
			{
				if ( is_array($value) )
				{
					continue;
				}
				$search[] = '<%'.preg_replace('/[^a-zA-Z0-9_]+/','',$key).'%>';
				$replace[] = $value;
			}
		}
		return htmlspecialchars(str_replace($search, $replace, $text), ENT_QUOTES, _CHARSET);
	}
	
	public function event_QuickMenu(&$data)
	{
		global $CONF;
		
		if ( $this->getOption('save_thumb') !== 'no' )
		{
			$option = array (
				'title'		=> 'NP_Thumbnail',
				'url'		=> "{$CONF['ActionURL']}?action=plugin&name={$this->getname()}&type=admin",
				'tooltip'	=> _NP_THUMBNAIL_09);
			array_push($data['options'], $option);
		}
		return;
	}
	
	public function event_PostAuthentication(&$data)
	{
		if ( array_key_exists('action', $_REQUEST)
		 && array_key_exists('name', $_REQUEST)
		 && array_key_exists('type', $_REQUEST)
		 && array_key_exists('width', $_REQUEST)
		 && array_key_exists('height', $_REQUEST)
		 && $_REQUEST['action'] === 'plugin'
		 && $_REQUEST['name'] === $this->getName()
		 && $_REQUEST['type'] !== '' )
		{
			self::$buffering = ob_start;
		}
		return;
	}
	
	public function doAction($type)
	{
		global $DIR_MEDIA, $member;
		
		$type = (string) $type;
		
		$path = '';
		$maxwidth = '';
		$maxheight = '';
		
		if ( array_key_exists('path', $_GET) )
		{
			$path = (string)  $_GET['path'];
		}
		if ( array_key_exists('width', $_GET) )
		{
			$maxwidth = (integer) $_GET['width'];
		}
		if ( array_key_exists('height', $_GET) )
		{
			$maxheight = (integer) $_GET['height'];
		}
		
		if ( self::$buffering )
		{
			ob_end_clean();
		}
		
		if ( in_array($type, array('admin', 'clear', 'sync')) && $member->isAdmin() )
		{
			$this->showAdmin($type);
			exit;
		}
		
		if ( $maxwidth <= 0 || $maxwidth > 1000 || $maxheight <= 0 || $maxheight > 1000 )
		{
			MediaUtils::error($this->t(_NP_THUMBNAIL_10, array($maxwidth, $maxheight)));
			return;
		}
		
		if ( FALSE === ($medium = new Medium($DIR_MEDIA, $path, MediaUtils::$prefix)) )
		{
			MediaUtils::error($this->t(_NP_THUMBNAIL_11, array($path)));
			return;
		}
		
		if ( FALSE === $medium->setResampledSize($maxwidth, $maxheight) )
		{
			MediaUtils::error($this->t(_NP_THUMBNAIL_10, array($maxwidth, $maxheight)));
			return;
		}
		
		MediaUtils::responseResampledImage($medium);
		return;
	}
	
	public function doSkinVar($skinType)
	{
		$path = '';
		$maxwidth = 0;
		$maxheight = 0;
		$alt = '';
		
		/* retrieve arguments. This is for preventing E_STRICT */
		$args = func_get_args();
		if ( array_key_exists(0, $args) )
		{
			$path = (string)  $args[0];
		}
		if ( array_key_exists(1, $args) )
		{
			$maxwidth = (integer) $args[1];
		}
		if ( array_key_exists(2, $args) )
		{
			$maxheight = (integer) $args[2];
		}
		if ( array_key_exists(3, $args) )
		{
			$alt = (string) $args[3];
		}
		
		if ( $this->getBlogOption(MediaUtils::$blogid, 'force_thumb') == 'yes' )
		{
			echo $this->getParsedTag(array('', '', $path, 0, 0, $alt), $maxwidth, $maxheight);
		}
		
		return;
	}
	
	public function event_PreItem(&$data)
	{
		$item =& $data["item"];
		self::$authorid = $item->authorid;
		$item->body = preg_replace_callback("#<\%(Thumbnail)\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'getParsedTag'), $item->body);
		$item->more = preg_replace_callback("#<\%(Thumbnail)\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'getParsedTag'), $item->more);
		
		if ( $this->getBlogOption(MediaUtils::$blogid, 'force_thumb') == 'yes' )
		{
			$item->body = preg_replace_callback("#<\%(popup)\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'getParsedTag'), $item->body);
			$item->more = preg_replace_callback("#<\%(popup)\((.*?)\|(.*?)\|(.*?)\|(.*?)\)%\>#", array(&$this, 'getParsedTag'), $item->more);
		}
		return;
	}
	
	public function event_PostMediaUpload(&$data)
	{
		global $DIR_MEDIA;
		
		if ( $this->getOption('save_thumb') == 'no' )
		{
			return;
		}
		
		$root = rtrim($DIR_MEDIA, '/');
		$path = trim($data['collection'], '/');
		$filename = trim($data['filename'], '/');
		$maxwidth  = $this->getOption('maxwidth');
		$maxheight = $this->getOption('maxheight');
		
		if ( !MediaUtils::checkDir ($root . '/' . self::$thumbdir) )
		{
			return;
		}
		
		if ( FALSE === ($medium = new Medium($root, "{$path}/{$filename}", MediaUtils::$prefix)) )
		{
			return;
		}
		
		if ( !array_key_exists($medium->mime, MediaUtils::$image_mime) )
		{
			return;
		}
		
		if ( FALSE === $medium->setResampledSize($maxwidth, $maxheight) )
		{
			return;
		}
		
		$target = self::getThumbPath($medium);
		
		if ( $this->getOption('save_thumb') == 'filesystem' )
		{
			if ( !file_exists("{$root}/{$target}")
			   && !MediaUtils::storeResampledImage ($DIR_MEDIA, $target, $medium) )
			{
				return;
			}
		}
		
		return;
	}
	
	public function getParsedTag($match, $maxwidth=0, $maxheight=0)
	{
		global $DIR_MEDIA, $member;
		
		list($code, $tag, $path, $width, $height, $alt) = $match;
		
		if ( !preg_match("#^.+?/.+$#", $path) && self::$authorid )
		{
			$path = self::$authorid . '/' . $path;
		}
		
		if ( FALSE === ($medium = new Medium($DIR_MEDIA, $path, MediaUtils::$prefix)) )
		{
			return $this->t('NP_Thumbnail: 指定したメディアファイルを読み込めませんでした。', array($path));
		}
		
		if ( !array_key_exists($medium->mime, MediaUtils::$image_mime) )
		{
			return $this->t(_NP_THUMBNAIL_12, array($path));
		}
		
		if ( $tag == 'Thumbnail' )
		{
			$maxwidth  = (integer) $width;
			$maxheight = (integer) $height;
		}
		
		if ( ($maxwidth == 0) && ($maxheight == 0) )
		{
			$maxwidth  = (integer) $this->getOption('maxwidth');
			$maxheight = (integer) $this->getOption('maxheight');
		}
		
		if ( $maxwidth < 0 || $maxwidth > 1000 || $maxheight < 0 || $maxheight > 1000 )
		{
			return $this->t(_NP_THUMBNAIL_10, array($path));
		}
		
		if ( FALSE === $medium->setResampledSize($maxwidth, $maxheight) )
		{
			return $this->t('NP_Thumbnail: サムネイルのサイズが不正です。', array($path));
		}
		
		if ( !$alt )
		{
			$alt =& $path;
		}
		
		return $this->generateTag($this->getBlogOption(MediaUtils::$blogid, 'thumb_template'), $medium, $alt);
	}
	
	public function generateTag($template, $medium, $alt)
	{
		global $DIR_LIBS;
		
		if ( !class_exists('BODYACTIONS', FALSE) )
		{
			include($DIR_LIBS . 'BODYACTIONS.php');
		}
		$action = new BODYACTIONS;
		
		if ( array_key_exists($medium->mime, MediaUtils::$image_mime)
		 && $this->getOption('save_thumb') == 'filesystem' )
		 {
			if ( !MediaUtils::checkDir ($medium->root . '/' . self::$thumbdir) )
			{
				return $this->t(_NP_THUMBNAIL_13, array(self::$thumbdir));
			}
			if ( !file_exists($medium->root . '/' . self::getThumbPath($medium)) )
			{
				MediaUtils::storeResampledImage ($medium->root, self::getThumbPath($medium), $medium);
			}
		}
		
		ob_start();
		if ( array_key_exists($medium->mime, MediaUtils::$image_mime) && $this->getThumbURL($medium) )
		{
			$action->template['POPUP_CODE'] = $template;
			$replacements = array(
				'<%thumb_width%>'	=> $medium->resampledwidth,
				'<%thumb_height%>'	=> $medium->resampledheight,
				'<%thumb_url%>'		=> $this->getThumbURL($medium)
			);
			foreach ( $replacements as $target => $replacement )
			{
				$action->template['POPUP_CODE'] = str_replace ($target, $replacement, $action->template['POPUP_CODE']);
			}
			$action->createPopupCode("{$medium->path}/{$medium->name}", $medium->width, $medium->height, $alt);
		}
		else
		{
			$action->template['MEDIA_CODE'] = $template;
			$action->createMediaCode("{$medium->path}/{$medium->name}", $alt);
		}
		$tag = ob_get_contents();
		ob_get_clean();
		
		return preg_replace('#href="(.*?)imagetext(.*?)"#', 'href="$1imagetext$2&amp;blogid='.MediaUtils::$blogid . '"', $tag);
	}
	
	private function showAdmin($type)
	{
		global $CONF, $DIR_LIBS, $DIR_MEDIA, $manager;
		
		$type = (string) $type;
		
		if ( !class_exists ('PLUGINADMIN', FALSE) )
		{
			include ($DIR_LIBS . 'PLUGINADMIN.php');
		}
		
		$oPluginAdmin = new PluginAdmin('Thumbnail');
		$oPluginAdmin->start();
		
		echo "<h2>NP_Thumbnail</h2>\n";
		
		if ( $this->getOption('save_thumb') === 'no' )
		{
			echo '<p>' . $this->t(_NP_THUMBNAIL_14) . "</p>\n";
			$oPluginAdmin->end();
			return;
		}
		
		$logs = array ();
		if ( $type == 'clear' )
		{
			if ( $this->getOption('save_thumb') == 'filesystem' )
			{
				$logs = MediaUtils::purgeDir($DIR_MEDIA, self::$thumbdir . '/');
			}
		}
		
		echo "<p>" . $this->t(_NP_THUMBNAIL_15, array(self::$thumbdir)) . "<br />\n";
		echo $this->t(_NP_THUMBNAIL_16, array(self::$max_sync)) . "<br />\n";
		echo $this->t(_NP_THUMBNAIL_17) . "</p>\n";
		
		if ( $type == 'sync' )
		{
			$maxwidth = $this->getOption('maxwidth');
			$maxheight = $this->getOption('maxheight');
			if ( $this->getOption('save_thumb') == 'filesystem' )
			{
				echo "<h3>" . $this->t(_NP_THUMBNAIL_22) . "</h3>\n";
				if ( self::syncFilesystem ($DIR_MEDIA, self::$thumbdir, $maxwidth, $maxheight) )
				{
					echo "<p>何かのエラーメッセージ</p>\n";
				}
			}
		}
		
		$media = MediaUtils::getMediaList($DIR_MEDIA);
		$elected = array();
		$rejected = array();
		
		foreach ( $media as $medium )
		{
			if ( !array_key_exists($medium->mime, MediaUtils::$image_mime) )
			{
				continue;
			}
			if ( file_exists($DIR_MEDIA . self::getThumbPath($medium)) )
			{
				$rejected[] =& $medium;
				continue;
			}
			else
			{
				$elected[] =& $medium;
				continue;
			}
		}
		
		$total_media = count ($media);
		$total_elected = count ($elected);
		$total_rejected = count ($rejected);
		$total_images = count ($rejected) + $total_elected;
		
		/*
		 * NOTICE: NP_Improvededia with eachblogdir option rewrite
		 * global variables of "DIR_MEDIA" and "$CONF['MediaURL']"
		 * in its initializing process.
		 * (I realized it a bad behavior but there is no other way...)
		 * Here are based on its rewriting system.
		 *  (Apr. 06, 2011)
		 */
		if ( $manager->pluginInstalled('NP_ImprovedMedia') )
		{
			$NP_ImprovedMedia =& $manager->getPlugin('NP_ImprovedMedia');
			if ( $NP_ImprovedMedia->getOption('IM_EACHBLOGDIR') == 'yes' )
			{
				echo "<form method=\"post\" action=\"{$CONF['ActionURL']}?action=plugin&name=Thumbnail\" enctype=\"application/x-www-form-urlencoded\">\n";
				echo "<p>\n";
				echo "<label for=\"blogid\">" . $this->t(_NP_THUMBNAIL_18) . "</label>\n";
				echo "<select name=\"blogid\" id=\"blogid\"onchange=\"return form.submit()\">\n";
				foreach ( MediaUtils::$blogs as $blogid => $bshortname )
				{
					if ( $blogid == MediaUtils::$blogid )
					{
						echo "<option value=\"{$blogid}\" selected=\"selected\">{$bshortname}</option>\n";
					}
					else
					{
						echo "<option value=\"{$blogid}\">{$bshortname}</option>\n";
					}
				}
				echo "</select>\n";
				echo "<input type=\"hidden\" id=\"admin\" name=\"type\" value=\"admin\">\n";
				echo "</p>\n";
				echo "</form>\n";
			}
		}
		
		echo "<form method=\"post\" action=\"{$CONF['ActionURL']}?action=plugin&name=Thumbnail\" enctype=\"application/x-www-form-urlencoded\">\n";
		echo "<ul>\n";
		echo "<li>" . $this->t(_NP_THUMBNAIL_19, array($total_media)) . "</li>\n";
		echo "<li>" . $this->t(_NP_THUMBNAIL_20, array($total_images)) . "</li>\n";
		echo "<li>" . $this->t(_NP_THUMBNAIL_21, array($total_rejected)) . "</li>\n";
		echo "</ul>\n";
		echo "<p>\n";
		echo '<input type="hidden" name="blogid" value="' . MediaUtils::$blogid . '">' . "\n";
		echo "<input type=\"submit\" name=\"type\" value=\"sync\">\n";
		echo "<input type=\"submit\" name=\"type\" value=\"clear\">\n";
		echo "</p>\n";
		
		if ( $logs )
		{
			echo "<h3>" . $this->t(_NP_THUMBNAIL_22) . "</h3>\n";
			echo "<ul>\n";
			
			foreach ( $logs as $log )
			{
				echo "<li>{$log}</li>\n";
			}
			echo "</ul>\n";
		}
		echo "</form>\n";
		
		$oPluginAdmin->end();
		return;
	}
	
	public static function syncFilesystem ($root, $dest, $maxwidth, $maxheight)
	{
		$logs = array ();
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) )
		{
			return FALSE;
		}
		
		if ( !MediaUtils::checkDir(rtrim($root, '/') . '/' . trim($dest, '/')) )
		{
			return FALSE;
		}
		
		echo "<ul>\n";
		
		$media = MediaUtils::getMediaList($root);
		$targets = array();
		$count = 1;
		
		foreach ( $media as $medium )
		{
			ob_flush();
			flush();
			
			if ( $count > self::$max_sync )
			{
				break;
			}
			
			if ( FALSE === ($destination = self::getThumbPath($medium))
			   || file_exists (rtrim($root, '/') . '/' . $destination) )
			{
				continue;
			}
			
			if ( FALSE === $medium->setResampledSize($maxwidth, $maxheight)
			   || !MediaUtils::storeResampledImage ($root, $destination, $medium) )
			{
				echo "<li>Fail: {$medium->name}</li>\n";
			}
			else
			{
				echo "<li>Success: {$medium->name}</li>\n";
				$count++;
			}
		}
		
		echo "</ul>\n";
		flush();
		return TRUE;
	}
	
	public static function getThumbPath($medium)
	{
		if ( !array_key_exists ($medium->mime, MediaUtils::$image_mime) )
		{
			return FALSE;
		}
		return self::$thumbdir . '/' . $medium->getHashedName(MediaUtils::$algorism) . MediaUtils::$image_mime[$medium->mime];
	}
	
	public function getThumbURL($medium)
	{
		global $CONF, $DIR_MEDIA;
		
		if ( ($medium->width < $medium->resampledwidth && $medium->height < $medium->resampledheight)
		 || ($medium->width <= $this->getOption('maxwidth') && $medium->height <= $this->getOption('maxheight')) )
		 {
			$url = "{$CONF['MediaURL']}{$medium->path}/{$medium->name}";
		}
		else if ( $medium->resampledwidth > $this->getOption('maxwidth') && $medium->resampledheight > $this->getOption('maxheight') )
		{
			$url = "{$CONF['ActionURL']}?action=plugin&amp;name={$this->getName()}&amp;path={$medium->path}/{$medium->name}&amp;width={$medium->resampledwidth}&amp;height={$medium->resampledheight}&amp;blogid=" . MediaUtils::$blogid;
		}
		else if (file_exists($DIR_MEDIA . self::getThumbPath($medium)) )
		{
			$url = $CONF['MediaURL'] . self::getThumbPath($medium);
		}
		else
		{
			$url = FALSE;
		}
		return $url;
	}
}
