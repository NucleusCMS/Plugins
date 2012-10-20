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

class Medium  {
	public $root = '';
	public $path = '';
	public $name = '';
	public $update = '';
	public $prefix = '';
	public $suffix = '';
	public $filename = '';
	public $size = 0;
	public $mime = '';
	public $width = 0;
	public $height = 0;
	public $resampledwidth = 0;
	public $resampledheight = 0;
	
/**
 * Return MEDIUM Object
 * @access	Public	$this->__construnct
 * @param	String	$root
 * @param	String	$relativepath
 * @param	Boolean	$prefix
 * @return	Object/FALSE
 */
	public function __construct ($root, $relativepath, $prefix) {
		static $fullpath;
		static $info;
		
		if ($root == '' || $relativepath == '') {
			return FALSE;
		}
		
		$root = preg_replace('#/*$#', '', $root);
		if ($root == '' || $relativepath == ''
		 || !file_exists($root)
		 || FALSE === ($fullpath = realpath(rtrim($root . '/' . ltrim($relativepath, '/'), '/')))
		 || strpos($fullpath, $root) !== 0
		 || !file_exists($fullpath)) {
			return FALSE;
		}
		
		$this->root = $root;
		$this->name = basename($fullpath);
		$this->path = str_replace(array($this->root.'/', '/'.$this->name), '', $fullpath);
		
		if ($this->path === $this->name) {
			$this->path = ''; 
		}
		
		if (FALSE === ($info = @getimagesize ($fullpath))) {
			$this->mime = 'application/octet-stream';
			$this->width = 0;
			$this->height = 0;
		} else {
			$this->mime = $info['mime'];
			$this->width = $info[0];
			$this->height = $info[1];
		}
		
		set_time_limit(ini_get('max_execution_time'));
		if (defined('FILEINFO_MIME_TYPE')
		 && function_exists ('finfo_open')
		 && (FALSE !== ($info = finfo_open(FILEINFO_MIME_TYPE)))) {
			$this->mime = finfo_file($info, $fullpath);
		}
		
		$this->update = date("Y/m/d", @filemtime($fullpath));
		$this->size = ceil(filesize($fullpath) / 1000);
		
		if (preg_match('#^(.*)\.([a-zA-Z0-9]{2,})$#', $this->name, $info) === 1) {
			$this->filename = $info[1];
			$this->suffix = $info[2];
			if ($prefix && preg_match('#^([0-9]{8})\-(.*)$#', $this->filename, $info) == 1 ) {
				$this->prefix = preg_replace('#^([0-9]{4})([0-9]{2})([0-9]{2})$#', '$1/$2/$3', $info[1]);
				$this->filename = $info[2];
			}
		}
		
		return $this;
	}
	
	public function __destruct () {
		return;
	}
	
/**
 * Set resampled size
 * @access	Public	$this->setResampledSize
 * @param	Integer	$maxwidth
 * @param	Integer	$maxheight
 * @return	Boolean
 */
	public function setResampledSize($maxwidth=0, $maxheight=0) {
		if (($maxwidth == 0) && ($maxheight == 0)) {
			return FALSE;
		} else if ($this->width == 0 || $this->height  == 0) {
			return FALSE;
		} else if ($this->width < $maxwidth && $this->height < $maxheight) {
			$this->resampledwidth = $this->width;
			$this->resampledheight = $this->height;
		} else if ($maxheight == 0 || $this->width > $this->height) {
			$this->resampledheight = intval ($this->height * $maxwidth / $this->width);
			$this->resampledwidth = $maxwidth;
		} else if ($maxwidth == 0 || $this->width <= $this->height) {
			$this->resampledwidth = intval ($this->width * $maxheight / $this->height);
			$this->resampledheight = $maxheight;
		}
		return TRUE;
	}
	
/**
 * Return resampled image binary
 * @access	Public	$this->getResampledSize
 * @param	Integer	$maxwidth
 * @param	Integer	$maxheight
 * @return	Boolean
 */
	public function getResampledBinary ($image_mime) {
		static $gdinfo;
		static $original;
		static $resampledimage;
		
		$gdinfo = gd_info();
		
		if ($this->path !== '') {
			$fullpath = "{$this->root}/{$this->path}/{$this->name}";
		} else {
			$fullpath = "{$this->root}/{$this->name}";
		}
		if (!file_exists($fullpath)) {
			return FALSE;
		}
		
		if (!array_key_exists($this->mime, $image_mime)
		 || $this->width == 0
		 || $this->height == 0
		 || $this->resampledwidth == 0
		 || $this->resampledheight == 0) {
		 	return FALSE;
		}
		
		// check current available memory
		$memorymax = trim(ini_get("memory_limit"));
		switch (strtolower ($memorymax[strlen($memorymax)-1])) {
		 case 'g':
		 $memorymax *= 1024;
		 case 'm':
		 $memorymax *= 1024;
		 case 'k':
		 $memorymax *= 1024;
		}
		
		// this code is based on analyze if gd.c in php source code
		// if you can read C/C++, please check these elements and notify us if you have some ideas
		if ((memory_get_usage() + ($this->resampledwidth * $this->resampledheight * 5 + $this->resampledheight * 24 + 10000) + ($this->width * $this->height * 5 + $this->height * 24 + 10000)) > $memorymax) {
			return FALSE;
		}
		
		switch ($this->mime) {
			case 'image/gif':
				if (!$gdinfo['GIF Read Support'] && !$gdinfo['GIF Create Support']) {
					return FALSE;
				}
				$original = imagecreatefromgif ($fullpath);
				break;
			case 'image/jpeg':
				if ((array_key_exists('JPEG Support', $gdinfo) && !$gdinfo['JPEG Support']) && (array_key_exists('JPG Support', $gdinfo) && $gdinfo['JPG Support'])) {
					return FALSE;
				}
				$original = imagecreatefromjpeg ($fullpath);
				break;
			case 'image/png':
				if (!$gdinfo['PNG Support']) {
					return FALSE;
				}
				$original = imagecreatefrompng ($fullpath);
				break;
			default:
				return FALSE;
		}
		
		$resampledimage = imagecreatetruecolor ($this->resampledwidth, $this->resampledheight);
		
		if (!$resampledimage) {
			return FALSE;
		}
		
		set_time_limit(ini_get('max_execution_time'));
		if (!ImageCopyResampled ($resampledimage, $original, 0, 0, 0, 0, $this->resampledwidth, $this->resampledheight, $this->width, $this->height)) {
			return FALSE;
		}
		
		imagedestroy ($original);
		
		ob_start();
		
		switch ($this->mime) {
		 case 'image/gif':
		  imagegif ($resampledimage);
		  break;
		 case 'image/jpeg':
		  imagejpeg ($resampledimage);
		  break;
		 case 'image/png':
		  imagepng ($resampledimage);
		  break;
		 default:
		}
		
		imagedestroy ($resampledimage);
		
		return ob_get_clean();
	}
	
	public function getHashedName($algorism) {
		return (string) hash($algorism, "{$this->path}/{$this->name}", FALSE);
	}
}
