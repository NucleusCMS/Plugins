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

class NP_ImprovedMedia extends NucleusPlugin {
 // private
 private $baseUrl = ''; //string
 // private
 private $blog = FALSE; //object
 // private
 private $blogid = 1; //integer
 
 // public
 public function __construct() {
  global $CONF;
  $this->baseUrl = str_replace($CONF['AdminURL'], '', $this->getAdminURL());
  self::setBlogObject();
  return;
 }
 
 // public
 public function getName() { return 'ImprovedMedia'; }
 // public
 public function getAuthor() { return 'Mocchi'; }
 // public
 public function getURL() { return 'http://japan.nucleuscms.org/wiki/plugins:improvedmedia'; }
 // public
 public function getVersion() { return '3.0.1 for PHP5'; }
 // public
 public function getMinNucleusVersion() { return 223; }
 // public
 public function getDescription() { return _IM_DESCRIPTION; }
 
 // public
 public function supportsFeature($what) {
  global $CONF;
  switch($what) {
  case 'HelpPage':
  case 'SqlTablePrefix':
   return 1;
  default:
   return 0;
  }
 }
 
 // public
 public function init() {
  $language = preg_replace( '#[/|\\\\]#', '', getLanguageName());
  if(file_exists($this->getDirectory() .'lang/' .  $language.'.php'))
   include_once($this->getDirectory() . 'lang/' . $language.'.php');
  else
   include_once($this->getDirectory() . 'lang/english.php');
  
  if($this->getOption('IM_EACHBLOGDIR') == 'yes')
   self::setDirectories();
  
  return;
 }
 
 // public
 public function install() {
  $this->createOption('IM_PRIVATE', _IM_OPTION_PRIVATE , 'yesno', 'yes');
  $this->createOption('IM_ITEMDISPLAY', _IM_OPTION_ITEMDISPLAY , 'text', '10', 'datatype=numerical' );
  $this->createOption('IM_GREYBOX', _IM_OPTION_GREYBOX , 'yesno', 'no');
  $this->createOption('IM_EACHBLOGDIR', _IM_OPTION_EACHBLOGDIR , 'yesno', 'no');
  return;
 }
 
 // public
 public function unInstall() {
  $this->deleteOption('IM_PRIVATE');
  $this->deleteOption('IM_ITEMDISPLAY');
  $this->deleteOption('IM_GREYBOX');
  $this->deleteOption('IM_EACHBLOGDIR');
  return;
 }
 
 // public
 public function getEventList() {
  return array('InitSkinParse', 'AdminPrePageHead', 'BookmarkletExtraHead');
 }
 
 // public
 public function doAction($type) {
  global $DIR_LIBS;
  if(!class_exists('MEDIA', FALSE))
   include($DIR_LIBS . 'MEDIA.php');
  if(!class_exists('MEDIA_VARIABLES', FALSE))
   include($this->getDirectory() . 'MEDIA_VARIABLES.php');
  if(!class_exists('MEDIA_CONTROL', FALSE))
   include($this->getDirectory() . 'MEDIA_CONTROL.php');
  
  new MEDIA_CONTROL($this);
  exit;
 }
 
 // public
 public function doSkinVar($data, $place) {
  global $CONF, $member;
  if($member->isLoggedIn()) {
   if($this->getOption('IM_GREYBOX') == 'yes') {
    switch($place) {
    case 'head':
     echo '<script type="text/javascript">' . "\n" .
      '<!--' . "\n" . 
      ' var GB_ROOT_DIR = "' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/";' . "\n" .
      '// -->' . "\n" .
      '</script>' . "\n" .
      '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/AJS.js"></script>' . "\n" .
      '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/AJS_fx.js"></script>' . "\n" .
      '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/gb_scripts.js"></script>' . "\n".
      '<link href="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/gb_styles.css" rel="stylesheet" type="text/css" />' . "\n";
     break;
    case 'anchor':
     echo '<a href="' . $CONF['ActionURL'] . '?action=plugin&amp;name=ImprovedMedia&amp;astool=1&amp;blogid=' . $this->blogid . '" onclick="GB_showCenter(\'Media Control' . _IM_HEADER_TEXT . '\',this.href,540,600); return false;">' . _IM_ANCHOR_TEXT . '</a>';
     break;
    default:
     break;
    }
   } else {
    if($place == 'anchor') {
     echo '<a href="' . $CONF['ActionURL'] . '?action=plugin&amp;name=ImprovedMedia&amp;astool=1&amp;blogid=' . $this->blogid . '" onclick="window.open(this.href , \'MediaControl\' , \'width=600 , height=600, scrollbars=1\'); return false;">' . _IM_ANCHOR_TEXT . '</a>';
    }
   }
  }
  return;
 }
 
 // public
 public function event_InitSkinParse(&$data) {
  return;
 }
 
 // public
 public function event_BookmarkletExtraHead(&$data) {
  self::addHeader($data['extrahead']);
  return;
 }
 
 // public
 public function event_AdminPrePageHead(&$data) {
  if(($data['action'] == 'createitem') || ($data['action'] == 'itemedit'))
   self::addHeader($data['extrahead']);
  return;
 }
 
 // private
 private function addHeader(&$extrahead) {
  global $CONF;
  
  if($this->getOption('IM_GREYBOX') == 'yes') {
   $extrahead .= '<script type="text/javascript">' ."\n" .
   '<!--' ."\n".
   'function addMedia() {' ."\n".
   ' GB_showCenter(\'Media Control' . _IM_HEADER_TEXT . '\',\'' . $CONF['ActionURL'] . '?action=plugin&name=ImprovedMedia&mode=upload_select&astool=0&blogid=' . $this->blogid . '\',540,600);' . "\n" .
   '}' . "\n";
  } else {
   $extrahead .= '<script type="text/javascript">' ."\n" .
   '<!--' ."\n".
   'function addMedia() {' ."\n".
   ' window.open(\'' . $CONF['ActionURL'] . '?action=plugin&name=ImprovedMedia&mode=upload_select&astool=0&blogid=' . $this->blogid . '\' , \'MediaControl\' , \'width=600 , height=600, scrollbars=1\');' . "\n" .
   '}' . "\n";
  }
  $extrahead .= 'function includeImage(collection, filename, type, width, height, text) {' . "\n" .
  ' var fullName;' . "\n" .
  ' if(isNaN(collection) || (nucleusAuthorId != collection)) {' . "\n" .
  '  fullName = collection + \'/\' + filename;' . "\n" .
  ' } else {' . "\n" .
  '  fullName = filename;' . "\n" .
  ' }' . "\n" .
  ' var replaceBy;' . "\n" .
  ' switch(type) {' . "\n" .
  ' case \'popup\':' . "\n" .
  '  replaceBy = \'<%popup(\' +  fullName + \'|\'+width+\'|\'+height+\'|\' + text +\')%>\';' . "\n" .
  '  break;' . "\n" .
  ' case \'inline\':' . "\n" .
  ' default:' . "\n" .
  '  replaceBy = \'<%image(\' +  fullName + \'|\'+width+\'|\'+height+\'|\' + text +\')%>\';' . "\n" .
  ' }' . "\n" .
  ' insertAtCaret(replaceBy);' . "\n" .
  ' updAllPreviews();' . "\n" .
  '}' . "\n" .
  '' . "\n" .
  'function includeOtherMedia(collection, filename, text) {' . "\n" .
  ' var fullName;' . "\n" .
  ' if(isNaN(collection) || (nucleusAuthorId != collection)) {' . "\n" .
  '  fullName = collection + \'/\' + filename;' . "\n" .
  ' } else {' . "\n" .
  '  fullName = filename;' . "\n" .
  ' }' . "\n" .
  ' var replaceBy = \'<%media(\' +  fullName + \'|\' + text +\')%>\';' . "\n" .
  ' insertAtCaret(replaceBy);' . "\n" .
  ' updAllPreviews();' . "\n" .
  '}' .
  '// -->' ."\n".
  '</script>'."\n";
  
  if($this->getOption('IM_GREYBOX') == 'yes') {
   $extrahead .= '<script type="text/javascript">' . "\n" .
   '<!--' . "\n" .
   ' var GB_ROOT_DIR = "' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/";' . "\n" .
   '// -->' . "\n" .
   '</script>' . "\n" .
   '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/AJS.js"></script>' . "\n" .
   '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/AJS_fx.js"></script>' . "\n" .
   '<script type="text/javascript" src="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/gb_scripts.js"></script>' . "\n" .
   '<link href="' . $CONF['AdminURL'] . $this->baseUrl . 'greybox/gb_styles.css" rel="stylesheet" type="text/css" />' . "\n";
  }
  return;
 }
 
 // private
 private function setBlogObject() {
  global $manager, $blog;
  
  $blogid = intRequestVar('blogid');
  $itemid = intRequestVar('itemid');
  
  if($blog) {
   $this->blog =& $blog;
   $this->blogid =& $blog->getID();
   return;
  }
  
  if($blogid && $manager->existsBlogID($blogid)) {
   $this->blog =& $manager->getBlog($blogid);
   $this->blogid =& $this->blog->getID();
   return;
  }
  
  if($itemid && $manager->existsItem($itemid, 1, 1)) {
   $this->blog =& $manager->getBlog(getBlogIDFromItemID($itemid));
   $this->blogid =& $this->blog->getID();
   return;
  }
 }
 
 // private
 private function setDirectories() {
  global $DIR_MEDIA, $CONF;
  
  if(!$this->blog)
   return;
  
  $blog =& $this->blog;
  $bshortname =& $blog->getShortName();
  
  if($this->blogid !== 1) {
   $DIR_MEDIA = preg_replace('#(.+)/(.+)?/$#',"$1/$bshortname/$2/",$DIR_MEDIA);
   $CONF['MediaURL'] = preg_replace( '#(.+)/(.+)?/$#' , "$1/$bshortname/$2/" , $CONF['MediaURL'] );
  }
  return;
 }
 
 // public
 public function & getBlog() {
  return $this->blog;
 }
 
 // public
 public function & getBlogid() {
  return $this->blogid;
 }
 
 // public
 public function getBaseURL() {
  return $this->baseUrl;
 }
}
?>