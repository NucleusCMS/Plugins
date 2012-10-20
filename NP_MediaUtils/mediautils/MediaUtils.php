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

class MediaUtils
{
	static public $lib_path = '';
	
	static public $cookiename = 'blogid';
	static public $blogid = 0;
	static public $blogs = array();
	static public $bshortname = '';
	
	static public $prefix = TRUE;
	static public $maxsize = 2097152;
	static public $suffixes = array();
	
	static public $algorism = 'md5';
	static public $image_mime = array(
		'image/jpeg'	=> '.jpeg',
		'image/png'		=> '.png',
		'image/gif'		=> '.gif');
	
	/**
	 * error and exit
	 * @access	Public	MediaUtils::error
	 * @param	String	$message	Error message
	 * @exit	
	 */
	static public function error($message)
	{
		$message = (string) $message;
		header("HTTP/1.0 404 Not Found");
		exit($message);
	}
	
	/**
	 * send resampled image via HTTP
	 * @access	Public	MediaUtils::responseResampledImage
	 * @param	Object	$medium		Medium Object
	 * @exit	
	 */
	static public function responseResampledImage($medium)
	{
		if ( !class_exists('Medium', FALSE) )
		{
			include(self::$lib_path . '/Medium.php');
		}
		
		if ( 'Medium' !== get_class($medium) )
		{
			self::error('NP_MediaUtils: Fail to generate resampled image');
			return;
		}
		
		if ( FALSE === ($resampledimage = $medium->getResampledBinary(self::$image_mime)) )
		{
			unset($resampledimage);
			self::error('NP_MediaUtils: Fail to generate resampled image');
			return;
		}
		
		header('Content-type: ' . $medium->mime);
		echo $resampledimage;
		unset($resampledimage);
		exit;
	}
	
	/**
	 * Store resampled image binary to filesystem as file
	 * @access	Public	MediaUtils::storeResampledImage
	 * @param	String	$root			root directory for media
	 * @param	String	$path			Relative path from root to destination
	 * @param	Object	$medium		Medium Object
	 * @return	Boolean	TRUE/FALSE
	 */
	static public function storeResampledImage($root, $target, $medium)
	{
		if ( !class_exists('Medium', FALSE) )
		{
			include(self::$lib_path . '/Medium.php');
		}
		
		if ( 'Medium' !== get_class($medium) )
		{
			return FALSE;
		}
		
		if ( FALSE === ($resampledimage = $medium->getResampledBinary(self::$image_mime)) )
		{
			unset($resampledimage);
			return FALSE;
		}
		
		if ( FALSE === ($handle = @fopen("{$root}/{$target}", 'w')) )
		{
			unset($resampledimage);
			return FALSE;
		}
		
		if ( @fwrite($handle, $resampledimage) === FALSE )
		{
			unset($resampledimage);
			@unlink("{$root}/{$target}");
			return FALSE;
		}
		
		unset($resampledimage);
		fclose($handle);
		
		if ( @chmod("{$root}/{$target}", 0744) === FALSE )
		{
			@unlink("{$root}/{$target}");
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Check the path as directory and writable
	 * @access	Public	MediaUtils::checkDir
	 * @param	String	Fullpath
	 * @return	Boolean	TRUE/FALSE
	 */
	static public function checkDir($fullpath)
	{
		$fullpath = (string) $fullpath;
		
		if ( file_exists($fullpath) )
		{
			if ( is_dir($fullpath) && is_writable($fullpath) )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if ( !@mkdir($fullpath, 0777) )
			{
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}
	}
	
	/**
	 * Return file data list
	 * @access	Public	MediaUtils::getMediaList
	 * @param	String	$root		path to media root directory
	 * @param	Boolean	$hidden	show hidden files (.*) or not
	 * @param	Boolean	$recursive	follow recursively or not
	 * @param	Boolean	$prefix	analyze its prefix or not
	 * @param	String	$suffix	limit by suffix
	 * @param	String	$path		relative path from root to destination
	 * @return	Array	Array(Medium)
	 */
	static public function getMediaList($root, $hidden=TRUE, $recursive=TRUE, $suffix='', $path='', $media=array())
	{
		$root		= (string)	$root;
		$hidden		= (boolean) $hidden;
		$recursive	= (boolean) $recursive;
		$suffix		= (string) $suffix;
		$path		= (string) $path;
		$media		= (array) $media;
		
		if ( !class_exists('Medium', FALSE) )
		{
			include(self::$lib_path . '/Medium.php');
		}
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) )
		{
			return FALSE;
		}
		
		$fullpath = $root;
		if ( $path )
		{
			$path = trim($path, '/');
			$fullpath = "{$root}/{$path}";
		}
		
		if ( FALSE === ($handle = @opendir($fullpath)) )
		{
			return FALSE;
		}
		while ( FALSE !== ($filename = readdir($handle)) )
		{
			if ( $filename !== '.' && $filename !== '..' )
			{
				if ( ($hidden && preg_match("#^\.#", $filename))
				   || ($suffix && !preg_match("#\.$suffix$#", $filename)) )
				{
					continue;
				}
				
				if ( $recursive && filetype("{$fullpath}/{$filename}") === "dir" )
				{
					$media = self::getMediaList($root, $hidden, $recursive, $suffix, trim("{$path}/{$filename}", '/'), $media);
				}
				else if ( $path !== '' && filetype("{$fullpath}/{$filename}") === "file" )
				{
					$media[] = new Medium($root, trim("{$path}/{$filename}", '/'), self::$prefix);
					continue;
				}
			}
		}
		closedir($handle);
		return $media;
	}
	
	/**
	 * Purge directory
	 * @access	Public	MediaUtils::purgeDir
	 * @param	String	$root		path to media root directory
	 * @param	String	$path		relative path from root to destination
	 * @return	Mixed	$logs
	 */
	static public function purgeDir($root, $path='', $logs=array())
	{
		$root	= (string) $root;
		$path	= (string) $path;
		$logs	= (array) $logs;
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) )
		{
			return FALSE;
		}
		
		$fullpath = $root;
		if ( $path )
		{
			$path = trim($path, '/');
			$fullpath = "{$root}/{$path}";
		}
		
		if ( FALSE === ($handle = @opendir($fullpath)) )
		{
			return FALSE;
		}
		while ( FALSE !== ($filename = readdir($handle)) )
		{
			if ( $filename !== '.' && $filename !== '..' )
			{
				if ( filetype("{$fullpath}/{$filename}") === "dir" )
				{
					$logs = self::purgeDir($root, "{$path}/{$filename}", $logs);
				}
				else
				{
					if ( !unlink("{$root}/{$path}/{$filename}") )
					{
						$logs[] = "Exists: {$path}/{$filename}";
					}
					else
					{
						$logs[] = "Removed: {$path}/{$filename}";
					}
					continue;
				}
			}
		}
		if ( !rmdir("{$root}/{$path}") )
		{
			$logs[] = "Exists: {$path}";
		}
		else
		{
			$logs[] = "Removed: {$path}";
		}
		return $logs;
	}
	
	/**
	 * Return path list under root
	 * @access	Public	MediaUtils::getPathList
	 * @param	String	$root		full path to root directory
	 * @param	String	$path		certain path to search
	 * @param	Boolean	$private	use private directory or not
	 * @param	Boolean	$recursize	search recursively or not
	 * @param	Boolean	$hidden		do not list up the directory started with piriod or not
	 * @return	String	$name	
	 */
	static public function getPathList($root, $path='', $private=FALSE, $hidden=TRUE, $recursive=TRUE)
	{
		$root		= (string) $root;
		$path		= (string) $path;
		$hidden		= (boolean) $hidden;
		$recursive	= (boolean) $recursive;
		
		$paths=array();
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) )
		{
			return FALSE;
		}
		
		$fullpath = $root;
		if ( $path )
		{
			$path = trim($path, '/');
			$fullpath = "{$root}/{$path}";
		}
		
		if ( FALSE === ($handle = @opendir($fullpath)) )
		{
			return FALSE;
		}
		while ( FALSE !== ($filename = readdir($handle)) )
		{
			if ( in_array($filename, array('.', '..', 'CVS')) )
			{
				continue;
			}
			else if ( is_file("{$fullpath}/{$filename}") )
			{
				continue;
			}
			else if ($hidden && preg_match('#^\.#', $filename) )
			{
				continue;
			}
			else if ( $private && is_numeric($filename) && $path==''&& $private != $filename )
			{
				continue;
			}
			
			if ( !$path )
			{
				$relpath = $filename;
			}
			else
			{
				$relpath = "{$path}/{$filename}";
			}
			
			$paths = self::getPathData($root, $relpath, $private, $hidden, $recursive, $paths);
		}
		closedir($handle);
		
		if ( $path=='' && $private )
		{
			if ( !array_key_exists($private, $paths) )
			{
				$paths[$private] = array('root'=>$root , 'path'=>$private, 'files'=>0, 'dirs'=>0);
			}
			$paths[$private]['label'] = 'PRIVATE';
		}
		
		ksort($paths, SORT_STRING);
		return $paths;
	}
	
	/**
	 * Return path data
	 * @access	Public	MediaUtils::getPathData
	 * @param	String	$root		full path to root directory
	 * @param	String	$path		relative path from root to target directory
	 * @param	Boolean	$private	use private directory or not
	 * @param	Boolean	$hidden		do not list up the directory started with piriod or not
	 * @param	Boolean	$recursive	search recursively or not
	 * @return	Array	Array('root', 'parent', 'name', 'files', 'dirs', 'label')
	 */
	static public function getPathData($root, $path, $private=FALSE, $hidden=TRUE, $recursive=FALSE, $paths=array())
	{
		$root		= (string)	$root;
		$path		= (string)	$path;
		$private	= (boolean) $private;
		$hidden		= (boolean) $hidden;
		$recursive	= (boolean) $recursive;
		
		$cnt_files = 0;
		$cnt_dirs = 0;
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) )
		{
			return FALSE;
		}
		
		$fullpath = $root;
		if ( $path )
		{
			$path = trim($path, '/');
			$fullpath = "{$root}/{$path}";
		}
		
		if ( FALSE === ($handle = @opendir($fullpath)) )
		{
			return FALSE;
		}
		while ( FALSE !== ($filename = readdir($handle)) )
		{
			if ( in_array($filename, array('.', '..', 'CVS')) )
			{
				continue;
			}
			else if ( !is_dir("{$fullpath}/{$filename}") )
			{
				if ( !$hidden || !preg_match('#^\.#', $filename) )
				{
					$cnt_files++;
				}
				continue;
			}
			else if ( $hidden && preg_match('#^\.#', $filename) )
			{
				continue;
			}
			
			$cnt_dirs++;
			
			if ( !$path )
			{
				$relpath = $filename;
			}
			else
			{
				$relpath = "{$path}/{$filename}";
			}
			
			if ( $recursive )
			{
				$paths = self::getPathData($root, $relpath, $private, $recursive, $hidden, $paths);
			}
		}
		closedir($handle);
		
		$paths[$path]['root'] = $root;
		$paths[$path]['parent'] = trim(str_replace(basename($fullpath), '', $path), '/');
		$paths[$path]['name'] = basename($fullpath);
		$paths[$path]['files'] = $cnt_files;
		$paths[$path]['dirs'] = $cnt_dirs;
		if ( $private )
		{
			$paths[$path]['label'] = preg_replace("#^$private#", 'PRIVATE', $path);
		}
		else
		{
			$paths[$path]['label'] = $path;
		}
		
		return $paths;
	}
	
	/**
	 * Store uploaded binary to filesystem
	 * @access	Public	MediaUtils::uploadMedium
	 * @param	String	$root		path to edia root directory
	 * @param	String	$path		relative path from root to target directory
	 * @param	Array	$medium		uploaded binary data.
	 * @param	Mixed	$overwrite	overwrite or not if the file already exists
	 * @param	Object	$manager	Nucleus Manager Object
	 * @return	String	$log		Return '' if success, others is error messages
	 */
	static public function uploadMedium($root, $path, &$temp, $overwrite='', &$manager='')
	{
		global $manager;
		
		$root		= (string) $root;
		$path		= (string) $path;
		$temp		= (array) $temp;
		$overwrite	= (string) $overwrite;
		$manager	= (object) $manager;
		
		/**
		 * $temp should be derived from $_FILE
		 */
		foreach ( array('name', 'tmp_name','size', 'error') as $key )
		{
			if ( !array_key_exists($key, $temp) )
			{
				return 'NP_MediaUtils: Miss uploaded file.';
			}
		}
		
		$root = rtrim($root, '/');
		if ( !$root || !file_exists($root) || !$path )
		{
			return 'NP_MediaUtils: Destination is invalid.';
		}
		$path = trim($path, '/');
		
		/**
		 * see http://www.php.net/manual/en/features.file-upload.errors.php
		 */
		switch ( $temp['error'] )
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'NP_MediaUtils: Binary is too big.';
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION :
			default:
				return 'NP_MediaUtils: Request rejected';
		}
		
		if ( preg_match ("#(\\\\|/|\\n)#", $temp['name']) )
		{
			return 'NP_MediaUtils: invalid filename';
		}
		
		if ( $temp['size'] > self::$maxsize )
		{
			return 'NP_MediaUtils: Binary is too big';
		}
		
		if ( !empty(self::$suffixes) && is_array(self::$suffixes) )
		{
			preg_match("#\.(.+)$#", $temp['name'], $match);
			$suffix = strtolower($match[1]);
			if ( !in_array($suffix, self::$suffixes) )
			{
				return 'NP_MediaUtils: Forbidden file suffix';
			}
		}
		
		if ( !self::checkDir("{$root}/{$path}") )
		{
			return 'NP_MediaUtils: Invalid target directory';
		}
		
		if ( $overwrite )
		{
			if ( !preg_match("#\.($suffix)$#", strtolower($overwrite), $match) )
			{
				return 'NP_MediaUtils: suffix is not the same.';
			}
			$temp['name'] = $overwrite;
		}
		else if ( self::$prefix )
		{
			$temp['name'] = strftime("%Y%m%d-", time()) . $temp['name'];
		}
		
		if ( !$overwrite && file_exists("{$root}/{$path}/{$temp['name']}") )
		{
			return 'NP_MediaUtils: The same filename already exists in this target directory.';
		}
		
		if ( $manager )
		{
			$params = array(
				'collection'	=> &$path,
				'uploadfile'	=>  $temp['tmp_name'],
				'filename'		=>  $temp['name']
			);
			$manager->notify('PreMediaUpload', $params);
		}
		
		if ( is_uploaded_file($temp['tmp_name']) )
		{
			if ( !@move_uploaded_file($temp['tmp_name'], "{$root}/{$path}/{$temp['name']}") )
			{
					return 'NP_MediaUtils: Fail to move uploaded binary to file sytem.';
			}
		}
		else if ( !copy($temp['tmp_name'], "{$root}/{$path}/{$temp['name']}") )
		{
				return 'NP_MediaUtils: Fail to copy uploaded binary to file sytem.';
		}
		
		$oldumask = umask(0000);
		@chmod("{$root}/{$path}/{$temp['name']}", 0644);
		umask($oldumask);
		
		if ( $manager )
		{
			$manager->notify('PostMediaUpload',array('collection' => $path, 'mediadir' => $root, 'filename' => $temp['name']));
		}
		
		return '';
	}
}
