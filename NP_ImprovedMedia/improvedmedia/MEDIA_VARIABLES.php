<?php
/**
 * ImprovedMedia plugin for Nucleus CMS
 * Version 3.0.1 for PHP5
 * Written By Mocchi, Feb.28, 2010
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

class MEDIA_VARIABLES {
 private $accessurl = 'media.php'; //string
 // privete
 private $plugindir = ''; //string
 private $private = 1; //boolean
 private $greybox = 0; //boolean
 private $itemdisplay = 10; //integer
 private $eachblogdir = 0; //boolean
 
 private $allowedtypes = array(); //array
 private $mediaprefix = 0; //boolean
 private $maxuploadsize = 0; //integer
 
 private $mediaurl = ''; //string
 private $dirmedia = ''; //string
 
 private $blog = FALSE; //object
 private $blogid = 1; //object
 
 private $mode = 'display'; //string
 private $alttext = ''; //string
 private $astool = 0; //boolean
 private $collection = ''; //string
 private $subdir = ''; //string
 private $filename= ''; //string
 private $newfilename = ''; //string
 private $offset = 0; //integer
 private $way = 'popup'; //string
 private $filter = ''; //string
 
 private static $modes = array('rename_confirm', 'rename', 'erase_confirm', 'erase',
  'upload_select', _IM_UPLOAD_NEW, 'upload', 'embed_confirm', 'embed',
   _IM_COLLECTION_SELECT, _IM_FILTER_APPLY, 'display', 'login',
   'directory_remove_confirm', 'directory_remove', _IM_CREATE_SUBDIR_CONFIRM, 'directory_create',
    'directory_rename_confirm', 'directory_rename',
    _IM_DISPLAY_SUBDIR, _IM_DISPLAY_SUBDIR_SELECT, 'directory_display');
 // protected
 protected static $modesNotToCheck
  = array('display', _IM_FILTER_APPLY, _IM_COLLECTION_SELECT, 'login', 'upload_select',
   _IM_DISPLAY_SUBDIR, _IM_DISPLAY_SUBDIR_SELECT, 'directory_display');
    
 public function __construct(&$PluginObject) {
  global $CONF;
  $this->blog =& $PluginObject->getBlog();
  $this->blogid =& $PluginObject->getBlogid();
  $this->accessurl = $CONF['ActionURL'] . '?action=plugin&amp;name=ImprovedMedia';
  $this->plugindir = $CONF['AdminURL'] . $PluginObject->getBaseURL();
  self::setOptions($PluginObject);
  
  self::setAllowedTypes();
  self::setMediaPrefix();
  self::setMaxUploadSize();
  
  self::setMediaURL();
  self::setDIR_MEDIA();
  
  return;
 }
 
 public function __destruct() {return;}
 
 protected function setMode() {
  $mode = htmlspecialchars(requestVar('mode'), ENT_QUOTES, _CHARSET);
  if(in_array($mode, self::$modes))
   $this->mode = (string)$mode;
  return;
 }
 
 protected function setAlttext() {
  $alttext = htmlspecialchars(requestVar('alttext'), ENT_QUOTES, _CHARSET);
  trim($alttext);
  $this->alttext = (string)$alttext;
  return;
 }
 
 protected function setAstool() {
  $astool = intRequestVar('astool');
  if($astool)
   $this->astool = (int)1;
  return;
 }
 
 public function getCollections() {
  $collections = array();
  $collections = MEDIA::getCollectionList();
  
  if(!$this->private) {
   if(count($collections) < 2)
    return (string)'error';
   else
    array_shift($collections);
  }
  ksort($collections, SORT_STRING);
  return (array)$collections;
 }
 
 protected function setCollection() {
  global $member;
  $collection = htmlspecialchars(requestVar('collection'), ENT_QUOTES, _CHARSET);
  $collections = $this->getCollections();
  
  if($collections == 'error') {
   $this->collection = (string)'error';
   return;
  }
  
  if(!$this->private) {
   if($collection == '') {
    $keys = array_keys($collections);
    $this->collection = (string)$collections[$keys[0]];
    return;
   }
   if(!array_key_exists($collection, $collections)) {
    $this->collection = (string)'error';
    return;
   }
   $this->collection = (string)$collection;
   return;
  } else {
   if($collection == '') {
    $this->collection = (string)$member->getID();
   	return;
   }
   if(!array_key_exists($collection, $collections)) {
    $this->collection = (string)'error';
   	return;
   }
   $this->collection = (string)$collection;
   return;
  }
 }
 
 public function getSubdirs() {
  $subdirs = array();
  $temp = array();
  $number = 0;
  
  if($this->collection == 'error') {
   return (string)'error';
  }
  
  if(!is_dir($this->dirmedia . $this->collection)) {
   $subdirs[] = array('subdirname' => '.', 'number' => 0, 'collection' => $this->collection);
   return $subdirs;
  }
  
  $dirhandle = @opendir($this->dirmedia . $this->collection);
  
  if(!$dirhandle) return (string)'error';
  
  while(($subdirname = readdir($dirhandle)) !== FALSE) {
   if(is_dir($this->dirmedia . $this->collection . '/' . $subdirname) && $subdirname != "..") {
    $number = 0;
     if($subdirhandle = @opendir($this->dirmedia . $this->collection . '/' . $subdirname)) {
     if(!$subdirhandle) {
      return (string)'error';
     }
     while(($file = readdir($subdirhandle)) !== FALSE)
      if($file != '..' && $file != '.' && !is_dir($this->dirmedia . $this->collection . '/' . $subdirname . '/' . $file))
       ++$number;
     closedir($subdirhandle);
    }
    $temp[$subdirname] = array('number' => $number, 'collection' => $this->collection);
   }
  }
  closedir($dirhandle);
  ksort($temp);
  foreach($temp as $subdirname => $options) {
   $subdirs[] = array('subdirname' => $subdirname, 'number' => $options['number'], 'collection' => $options['collection']);
  }
  return (array)$subdirs;
 }
 
 protected function setSubDir() {
  $subdir = htmlspecialchars(requestVar('subdir'), ENT_QUOTES, _CHARSET);
  $subdirs = self::getSubdirs();
  $list = array();
  
  if($subdir == '') {
   $this->subdir = (string)'';
  }
    
  if($subdirs == 'error') {
   $this->subdir = (string)'error';
   return;
  }
  
  if(count($subdirs) < 2) {
   $this->subdir = (string)'';
   return;
  }
  
  array_shift($subdirs);
  
  foreach($subdirs as $key => $options) {
   if((string)$options['subdirname'] == (string)$subdir) {
    $this->subdir = (string)$subdir;
    break;
   }
  }
 }
 
 protected function setFilename() {
  $filename = htmlspecialchars(requestVar('filename'), ENT_QUOTES, _CHARSET);
  
  if($filename == '') {
   $this->filename = (string)'';
   return;
  }
  
  if($this->collection === 'error') {
   $this->filename = (string)'error';
   return;
  }
  
  if($this->subdir === 'error') {
   $this->filename = (string)'error';
   return;
  }
    
  if(!$this->subdir)
   $fileobjectlist = MEDIA::getMediaListByCollection($this->collection);
  else
   $fileobjectlist = MEDIA::getMediaListByCollection($this->collection . '/' . $this->subdir);
  
  foreach($fileobjectlist as $fileobject) {
   if((string)$fileobject->filename == (string)$filename) {
    $this->filename = $fileobject->filename;
    break;
   }
  }
  return;
 }
 
 protected function setNewFilename() {
  $newfilename = htmlspecialchars(requestVar('newfilename'), ENT_QUOTES, _CHARSET);
  trim($newfilename);
  $this->newfilename = (string)$newfilename;
  return;
 }
 
 protected function setOffset() {
  $offset = intRequestVar('offset');
  if($offset >= 0)
   $this->offset = (int)$offset;
  return;
 }
 
 protected function setWay() {
  $way = htmlspecialchars(requestVar('way'), ENT_QUOTES, _CHARSET);
  $ways = array('popup','inline','other');
  if(in_array($way, $ways))
   $this->way = (string)$way;
  return;
 }
 
 protected function setFilter() {
  $filter = htmlspecialchars(requestVar('filter'), ENT_QUOTES, _CHARSET);
  if($this->allowedtypes === array()) {
   $this->filter = (string)'';
   return;
  }
  if(in_array($filter, $this->allowedtypes))
   $this->filter = (string)$filter;
  return;
 }
 
 private function setAllowedTypes() {
  global $CONF;
  if($CONF['AllowedTypes'] == '')
   return array();
  $allowedtypes = explode(',', $CONF['AllowedTypes']);
  if(is_array($allowedtypes)) {
   sort($allowedtypes);
   $this->allowedtypes = (array)$allowedtypes;
  }
  return;
 }
 
 private function setMediaPrefix() {
  global $CONF;
  $this->mediaprefix = (string)$CONF['MediaPrefix'];
  return;
 }
 
 private function setMediaURL() {
  global $CONF;
  $this->mediaurl = (string)$CONF['MediaURL'];
  return;
 }
 
 private function setMaxUploadSize() {
  global $CONF;
  $this->maxuploadsize = (int)$CONF['MaxUploadSize'];
  return;
 }
 
 private function setDIR_MEDIA() {
  global $DIR_MEDIA;
  $this->dirmedia = (string)$DIR_MEDIA;
  return;
 }
 
 private function setOptions(&$PluginObject) {
  if($PluginObject->getOption('IM_PRIVATE') == 'no')
   $this->private = (int)0;
  
  if($PluginObject->getOption('IM_GREYBOX') == 'yes')
   $this->greybox = (int)1;
  
  $this->itemdisplay =& $PluginObject->getOption('IM_ITEMDISPLAY');
  
  if($PluginObject->getOption('IM_EACHBLOGDIR') == 'yes')
   $this->eachblogdir = (int)1;
  
  return;
 }
 
 public function getAccessURL() {
  return $this->accessurl;
 }
 
 public function getPluginDir() {
  return $this->plugindir;
 }
 
 public function & getBlog() {
  return $this->blog;
 }
 
 public function & getBlogid() {
  return $this->blogid; 
 }
 
 public function & getBlogName() {
  $blog =& $this->blog;
  if(!$blog)
   return '';
  else
   return $blog->getName();
 }
 
 public function & getBlogShortName() {
  $blog =& $this->blog;
  if(!$blog)
   return '';
  else
   return $blog->getShortName();
 }
 
  public function getPrivate() {
  return $this->private;
 }
 
 public function getGreybox() {
  return $this->greybox;
 }
 
 public function getEachblogdir() {
  return $this->eachblogdir;
 }
 
 public function getItemdisplay() {
  return $this->itemdisplay;
 }
 
 public function getAllowedtypes() {
  return $this->allowedtypes;
 }
 
 public function getMediaprefix() {
  return $this->mediaprefix;
 }
 
 public function getMaxuploadsize() {
  return $this->maxuploadsize;
 }
 
 public function getMediaURL() {
  return $this->mediaurl;
 }
 
 public function getDirMedia() {
  return $this->dirmedia;
 }
 
 public function getMode() {
  return $this->mode;
 }
 
 public function getAlttext() {
  return $this->alttext;
 }
 
 public function getAstool() {
  return $this->astool;
 }
 
 public function getCollection() {
  return $this->collection;
 }
 
 public function getSubdir() {
  return $this->subdir;
 }
 
 public function & getFilename() {
  return $this->filename;
 }
 
 public function getNewfilename() {
  return $this->newfilename;
 }
 
 public function & getOffset() {
  return $this->offset;
 }
 
 public function getWay() {
  return $this->way;
 }
 
 public function getFilter() {
  return $this->filter;
 }
 
 public function resetOffset() {
  $this->offset = 0;
 }
 
 public function resetFilter() {
  $this->filter = '';
 }
}
?>