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

class MEDIA_CONTROL extends MEDIA_VARIABLES {
 public function __construct(&$PluginObject) {
  global $CONF, $manager, $member;
  
  parent::__construct(&$PluginObject);
  self::setMode();
  
  // login routine from globalfunctions.php
  // this should be checked and changes as soon as the core program is updated
  if(self::getMode() == 'login') {
   if (!isset($CONF['secureCookieKey']))
    $CONF['secureCookieKey'] = 24;
   switch($CONF['secureCookieKey']){
    case 8:
     $CONF['secureCookieKeyIP'] = preg_replace('#\.[0-9]+\.[0-9]+\.[0-9]+$#', '', serverVar('REMOTE_ADDR'));
     break;
    case 16:
     $CONF['secureCookieKeyIP'] = preg_replace('#\.[0-9]+\.[0-9]+$#', '', serverVar('REMOTE_ADDR'));
     break;
    case 24:
     $CONF['secureCookieKeyIP'] = preg_replace('#\.[0-9]+$#', '', serverVar('REMOTE_ADDR'));
     break;
    case 32:
     $CONF['secureCookieKeyIP'] = serverVar('REMOTE_ADDR');
     break;
    default:
     $CONF['secureCookieKeyIP'] = '';
   }
   
   $login = postVar('login');
   $pw = substr(postVar('password'),0,40);
   $shared = intPostVar('shared');
   
   if($member->login($login, $pw)) {
    $member->newCookieKey();
    $member->setCookies($shared);
    
    if ($CONF['secureCookieKey'] !=='none') {
     $member->setCookieKey(md5($member->getCookieKey() . $CONF['secureCookieKeyIP']));
     $member->write();
    }
    
    $manager->notify('LoginSuccess', array('member' => &$member));
    ACTIONLOG::add(INFO, "Login successful for $login (sharedpc=$shared)");
   } else {
    $manager->notify('LoginFailed', array('username' => $login));
    ACTIONLOG::add(INFO, "Login failed for $login");
   }
  }
  
  self::setAlttext();
  self::setAstool();
  self::setCollection();
  self::setSubDir();
  self::setFilename();
  self::setNewFilename();
  self::setOffset();
  self::setWay();
  self::setFilter();
  
  if(!$member->isLoggedIn()) {
   self::media_loginAndPassThrough();
   return;
  }
  
  if(!$PluginObject->getBlogid()) {
   self::media_doError(_IM_FORBIDDEN_ACCESS);
   return;
  }
  
  if(!$member->isAdmin() && !$member->isTeamMember(self::getBlogid())) {
   self::media_doError(_ERROR_NOTONTEAM);
   return;
  }
  
  if(!in_array(self::getMode(), parent::$modesNotToCheck) && !$manager->checkTicket()) {
   self::media_doError(_ERROR_BADTICKET);
   return;
  }
  
  if(!is_dir(self::getDirMedia()) || !is_writable(self::getDirMedia())) {
   self::media_doError(_IM_REMIND_MEDIADIR);
   return;
  }
  
  if(self::getCollections() === 'error') {
   self::media_doError(_IM_REMIND_DIRECTORY);
   return;
  }
  
  if(self::getCollection() === 'error') {
   self::media_doError(_IM_MISSING_DIRECTORY);
   return;
  }
  
  if(self::getSubdirs() === 'error') {
   self::media_doError(_IM_COLLECTION_FAILED_READ);
   return;
  }
  
  if(self::getFilename() === 'error') {
   self::media_doError(_IM_MISSING_FILE);
   return;
  }
  
  switch(self::getMode()) {
   case 'rename_confirm';
    self::media_rename_confirm();
   return;
   case 'rename';
    self::media_rename();
    return;
   case 'erase_confirm':
    self::media_erase_confirm();
    return;
   case 'erase';
    self::media_erase();
    return;
   case 'upload_select':
   case _IM_UPLOAD_NEW:
    self::media_upload_select();
    return;
   case 'upload':
    self::media_upload();
    return;
   case 'embed_confirm':
    self::media_embed_confirm();
    return;
   case 'embed':
    self::media_embed();
    return;
   case 'directory_remove_confirm':
    self::media_directory_remove_confirm();
    return;
   case 'directory_remove':
    self::media_directory_remove();
    return;
   case _IM_CREATE_SUBDIR_CONFIRM:
    self::media_directory_create_confirm();
    return;
   case 'directory_create':
    self::media_directory_create();
    return;
   case 'directory_rename_confirm':
    self::media_directory_rename_confirm();
    return;
   case 'directory_rename':
    self::media_directory_rename();
    return;
   case _IM_DISPLAY_SUBDIR:
   case _IM_DISPLAY_SUBDIR_SELECT:
   case 'directory_display':
    self::media_directory_display();
    return;
   case _IM_COLLECTION_SELECT:
   case _IM_FILTER_APPLY:
   case 'display':
   default:
    self::media_display();
    return;
  }
  return;
 }
 
 private function showMediaList() {
  global $manager, $member;
  
  $collections = self::getCollections();
  
  if(!self::getSubdir()) $combinedcollection = self::getCollection();
  else $combinedcollection = self::getCollection() . '/' . self::getSubdir();
  
  $arr = MEDIA::getMediaListByCollection($combinedcollection, self::getFilter());
  $list = array();
  
  foreach($arr as $obj) {
   if(!is_dir(self::getDirMedia() . $obj->collection . '/' . $obj->filename)
    && preg_match('#^(.+)\.([a-zA-Z0-9]{2,})$#', $obj->filename))
    array_push($list, $obj);
  }
  
  if(self::getMediaprefix())
   usort($list, array("MEDIA_CONTROL", "sort_media_by_filename"));
  
  $fileamount = count($list);
  
  if($fileamount > 0) {
   $idxStart = ceil(self::getOffset() / self::getItemdisplay()) * self::getItemdisplay();
   $idxEnd = $idxStart + self::getItemdisplay();
   if($idxEnd > $fileamount)
    $idxEnd = $fileamount;
   $idxPrev = $idxStart - self::getItemdisplay();
   if($idxPrev < 0)
    $idxPrev = 0;
   $idxNext = $idxStart + self::getItemdisplay();
   
   echo '<table frame="void" rules="none" summary="FILE LIST" width="100%">' . "\n";
   echo '<caption>' . _IM_COLLECTION_AMOUNT . $fileamount . ' (';
   if(!self::getFilter())
    echo _IM_FILTER_NONE;
   else
    echo _IM_FILTER . self::getFilter();
   echo ') &gt;&gt; ';
   if(self::getSubdir())
    echo self::getSubdir() . ' &gt;&gt; ';
   echo $collections[self::getCollection()];
   if(self::getBlogShortName() && self::getEachblogdir())
    echo ' &gt;&gt; ' . self::getBlogShortName();
   echo "</caption>\n";
   echo "<thead>\n";
   echo "<tr>\n";
   
   if(self::getMediaprefix())
    echo '<th>' . _IM_UPDATE . "</th>\n";
   else
    echo '<th>' . _MEDIA_MODIFIED . "</th>\n";
   
   echo '<th>' . _IM_FILENAME_CLICK . "</th>\n";
   echo '<th>' . _IM_TYPE . "</th>\n";
   
   if(!self::getAstool())
    echo '<th>' . _IM_ACTION . "</th>\n";
   
   echo '<th>' . _IM_DIMENSIONS . "</th>\n";
   echo '<th colspan="2">' . _IM_FUNCTION . "</th>\n";
   echo "</tr>\n";
   echo "</thead>\n";
   echo "<tfoot>";
   echo "<tr>\n";
   echo '<td colspan="2">'. "\n";
   if($idxStart > 0)
    echo '<a href="' . self::getAccessURL() . '&amp;collection=' . urlencode(self::getCollection()) . '&amp;subdir=' . urlencode(self::getSubdir()) . '&amp;filter=' . urlencode(self::getFilter()) . '&amp;astool=' . urlencode(self::getAstool()) . '&amp;offset=' . urlencode($idxPrev) . '&amp;blogid=' . urlencode(self::getBlogid()) . '">' . _LISTS_PREV . ' &lt;</a> ';
   if(($idxStart + 1) != $idxEnd)
    echo ($idxStart + 1) . ' to ' . $idxEnd . ' / ' . $fileamount . ' ';
   if($idxEnd < $fileamount)
    echo '<a href="' . self::getAccessURL() . '&amp;collection=' . urlencode(self::getCollection()) . '&amp;subdir=' . urlencode(self::getSubdir()) . '&amp;filter=' . urlencode(self::getFilter()) . '&amp;astool=' . urlencode(self::getAstool()) . '&amp;offset=' . urlencode($idxNext) . '&amp;blogid=' . urlencode(self::getBlogid()) . '">&lt; ' . _LISTS_NEXT . '</a> ';
   echo "</td>\n";
   if(!self::getAstool())
    echo '<td colspan="5" class="right">'. "\n";
   else
    echo '<td colspan="4" class="right">'. "\n";
   if(self::getBlogName() && self::getEachblogdir())
    echo _IM_WEBLOG_LABEL . ': ' . self::getBlogName();
   echo "</td>\n";
   echo "</tr>\n";
   echo "</tfoot>\n";
   echo "<tbody>\n";
   
   for($i = $idxStart; $i < $idxEnd; $i++) {
    $obj = $list[$i];
    
    list($width, $height, $filetype, $filesize) = self::getFileData($combinedcollection, $obj->filename);
    list($update, $onlyfilename, $onlyprefix) = self::getSplitFilename($combinedcollection, $obj->filename);
    
    echo "<tr>\n";
    echo "<td>" . $update . "</td>\n";
    echo "<td>\n";
    if($filetype != 0 && !self::getSubdir())
     echo '<a href="'. self::getMediaURL() . urlencode(self::getCollection()) . '/' . urlencode($obj->filename) . '" onclick="window.open(this.href,\'PopupWindow\',\'width=400, height=400,location=yes,status=yes,scrollbars=yes,resizable=yes\'); return false;" title="' . $obj->filename . _IM_VIEW_TT . '">' . "\n";
    elseif($filetype == 0 && !self::getSubdir())
     echo '<a href="'. self::getMediaURL() . urlencode(self::getCollection()) . '/' . urlencode($obj->filename) . '" onclick="window.open(this.href,\'PopupWindow\',\'width=' . $width . ', height=' . $height . ',location=yes,status=yes,scrollbars=yes,resizable=yes\'); return false;" title="' . $obj->filename . _IM_VIEW_TT .'">'."\n";
    elseif($filetype != 0 && self::getSubdir())
     echo '<a href="'. self::getMediaURL() . urlencode(self::getCollection()) . '/' . urlencode(self::getSubdir()) . '/' . urlencode($obj->filename) . '" onclick="window.open(this.href,\'PopupWindow\',\'width=400, height=400,location=yes,status=yes,scrollbars=yes,resizable=yes\'); return false;" title="' . $obj->filename . _IM_VIEW_TT . '">' . "\n";
    else
     echo '<a href="'. self::getMediaURL() . urlencode(self::getCollection()) . '/' . urlencode(self::getSubdir()) . '/' . urlencode($obj->filename) . '" onclick="window.open(this.href,\'PopupWindow\',\'width=' . $width . ', height=' . $height . ',location=yes,status=yes,scrollbars=yes,resizable=yes\'); return false;" title="' . $obj->filename . _IM_VIEW_TT .'">'."\n";
    
    if(!self::getAstool())
     echo shorten($onlyfilename . '.' . $onlyprefix, 15, '...') . "\n";
    else
     echo shorten($onlyfilename . '.' . $onlyprefix, 30, '...') . "\n";
    
    echo "</a>\n";
    echo "</td>\n";
    echo '<td>' . $onlyprefix . "</td>\n";
    if(!self::getAstool()) {
     echo "<td>\n";
     echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
     echo "<p>\n";
     echo '<input type="hidden" name="mode" value="embed_confirm" />' . "\n";
     echo $manager->addTicketHidden() . "\n";
     echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
     echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
     echo '<input type="hidden" name="filename" value="' . $obj->filename . '" />' . "\n";
     echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
     echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
     echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
     echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
     if($filetype != 0)
      echo '<input type="hidden" name="type" value="image" />' . "\n";
     else
      echo '<input type="hidden" name="type" value="other" />' . "\n";
     echo '<input type="submit" name="button" value="' . _IM_INCLUDE .'" class="formbutton" />' . "\n";
     echo "</p>\n";
     echo '</form>' . "\n";
     echo '</td>' . "\n";
    }
    if($filetype != 0)
     echo '<td>' . $width . '&nbsp;x&nbsp;' . $height . "</td>\n";
    else
     echo '<td>' . $filesize . "&nbsp;KB</td>\n";
    echo '<td>' . "\n";
    echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
    echo "<p>\n";
    echo '<input type="hidden" name="mode" value="erase_confirm" />' . "\n";
    echo $manager->addTicketHidden() . "\n";
    echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
    echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
    echo '<input type="hidden" name="filename" value="' . $obj->filename . '" />' . "\n";
    echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
    echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
    echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
    echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
    echo '<input type="submit" name="button" value="' . _IM_ERASE . '" class="formbutton" />' . "\n";
    echo "</p>\n";
    echo '</form>' . "\n";
    echo '</td>' . "\n";
    echo '<td>' . "\n";
    echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
    echo "<p>\n";
    echo '<input type="hidden" name="mode" value="rename_confirm" />' . "\n";
    echo $manager->addTicketHidden() . "\n";
    echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
    echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
    echo '<input type="hidden" name="filename" value="' . $obj->filename . '" />' . "\n";
    echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
    echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
    echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
    echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
    echo '<input type="submit" name="button" value="' . _IM_RENAME . '" class="formbutton" />' . "\n";
    echo "</p>\n";
    echo '</form>' . "\n";
    echo '</td>' . "\n";
    echo '</tr>' . "\n";
   }
   echo "</tbody>\n";
   echo "</table>\n";
  } else {
   echo '<p>' . _IM_COLLECTION_BRANK . ' (';
   if(!self::getFilter())
    echo _IM_FILTER_NONE;
   else
    echo _IM_FILTER . self::getFilter();
   echo ') ' . ' &gt;&gt; ';
   if(self::getSubdir())
    echo self::getSubdir() . ' &gt;&gt; ';
   echo $collections[self::getCollection()];
   if(self::getBlogShortName() && self::getEachblogdir())
    echo ' &gt;&gt; ' . self::getBlogShortName();
   echo "</p>\n";
  }
  return;
 }
 
 private function media_display() {
  global $manager, $member;
  
  $collections = self::getCollections();
  $subdirs = self::getSubdirs();
  
  array_shift($subdirs);
  
  if(self::getItemdisplay() != strval(intval(self::getItemdisplay())) || (self::getItemdisplay() < 5) || (self::getItemdisplay() > 50 )) {
   self::media_doError(_IM_ITEMDISPLAY_WRONG);
   return;
  }
  
  self::media_head();
  
  echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  
  if(count($collections) > 1) {
   echo '<label for="media_collection">' . _IM_COLLECTION_LABEL . '</label>' . "\n";
   echo '<select name="collection" id="media_collection">' . "\n";
   foreach($collections as $dirname => $description) {
    if((string)$dirname == (string)self::getCollection())
     echo '<option value="' . $dirname . '" selected="selected">' . $description . "</option>\n";
    else
     echo '<option value="' . $dirname . '">' . $description . "</option>\n";
   }
   echo '</select>' . "\n";
   echo '<input type="submit" name="mode" value="' . _IM_COLLECTION_SELECT . '" title="' . _IM_COLLECTION_TT . '" class="formbutton" />' . "\n";
  } else {
   echo _IM_COLLECTION . ': ' . $collections[self::getCollection()] . '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  }
  echo '<input type="submit" name="mode" value="' . _IM_UPLOAD_NEW . '" title="' . _IM_UPLOADLINK . '" class="formbutton" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  if(count($subdirs) > 0) {
   echo '<label for="media_subdir">' . _IM_SUBDIR_LABEL . '</label>' . "\n";
   echo '<select name="subdir" id="media_subdir">' . "\n";
   if(self::getSubdir() == '')
    echo '<option value="" selected="selected">' . _IM_SUBDIR_NONE . "</option>\n";
   else
    echo '<option value="">' . _IM_SUBDIR_NONE . "</option>\n";
   foreach($subdirs as $key => $options) {
    if($options['subdirname'] == self::getSubdir())
     echo '<option value="' . $options['subdirname'] . '" selected="selected">' . $options['subdirname'] . "</option>\n";
    else
     echo '<option value="' . $options['subdirname'] . '">' . $options['subdirname'] . "</option>\n";
   }
   echo '</select>' . "\n";
   echo '<input type="submit" name="mode" value="' . _IM_SUBDIR_SELECT . '" title="' . _IM_SUBDIR_TT . '" class="formbutton" />' . "\n";
  } elseif(self::getSubdir() == '') {
   echo _IM_SUBDIR_LABEL . _IM_SUBDIR_NONE . '<input type="hidden" name="subdir" value="" />' . "\n";
  }
  echo '<input type="submit" name="mode" value="' . _IM_DISPLAY_SUBDIR . '" title="' . _IM_DISPLAY_SUBDIR_TT. '" class="formbutton" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  echo '<label for="media_filter">' . _IM_FILTER_LABEL . '</label>' . "\n";
  echo '<select name="filter" id="media_filter">' . "\n";
  if(self::getFilter() == '')
   echo '<option value="" selected="selected">' . _IM_FILTER_NONE . "</option>\n";
  else
   echo '<option value="">' . _IM_FILTER_NONE . "</option>\n";
  if(is_array(self::getAllowedtypes())) {
   foreach(self::getAllowedtypes() as $allowedtype) {
    if($allowedtype == self::getFilter())
     echo '<option value="' . $allowedtype . '" selected="selected">' . $allowedtype . "</option>\n";
    else
     echo '<option value="' . $allowedtype . '">' . $allowedtype . "</option>\n";
   }
  }
  echo '</select>' . "\n";
  echo '<input type="submit" name="mode" value="' . _IM_FILTER_APPLY . '" class="formbutton" />' . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  echo '<hr />' . "\n";
  
  self::showMediaList();
  self::media_foot();
  return;
 }
 
 private function media_erase() {
  global $manager;
  
  if(!self::getSubdir()) $combinedfilename = self::getFilename();
  else $combinedfilename = self::getSubdir() . '/' . self::getFilename();
  
  $manager->notify('PreMediaErase',array('collection' => self::getCollection(), 'filename' => $combinedfilename));
  
  if(!@unlink(self::getDirMedia() . self::getCollection() . '/' . $combinedfilename)) {
   self::media_doError(_IM_ERASE_FAILED . ': ' . self::getCollection() . '/' . $combinedfilename);
   return;
  }
  $manager->notify('PostMediaErase',array('collection' => self::getCollection(), 'filename' => $combinedfilename));
  
  self::resetFilter();
  self::resetOffset();
  self::media_display();
  return;
 }
 
 private function media_rename() {
  global $manager;
  
  $newfilename = self::getNewfilename();
  
  if($newfilename == '') {
   self::media_rename_confirm(_IM_RENAME_BLANK);
   return;
  } elseif(mb_strlen($newfilename, _CHARSET) > 30) {
   self::media_rename_confirm(_IM_RENAME_TOOLONG);
   return;
  } elseif(!preg_match('#^[a-zA-Z0-9 \_\-\+]+$#', $newfilename)) {
   self::media_rename_confirm(_IM_RENAME_WRONG);
   return;
  } elseif(stristr($newfilename, '%00')) {
   self::media_doError(_IM_RENAME_FORBIDDEN);
   return;
  }
  
  if(!self::getSubdir()) $combinedfilename = self::getFilename();
  else $combinedfilename = self::getSubdir() . '/' . self::getFilename();
  
  list($update, $onlyfilename, $onlyprefix) = self::getSplitFilename(self::getCollection(), $combinedfilename);
  $update = preg_replace('#/|\\\\#', '', $update);
  
  if(self::getMediaprefix())
   $newfilename = $update . '-' . $newfilename . '.' . $onlyprefix;
  else
   $newfilename = $newfilename . '.' . $onlyprefix;
  
  if(!self::getSubdir()) $combinednewfilename = $newfilename;
  else $combinednewfilename = self::getSubdir() . '/' . $newfilename;
  
  $manager->notify('PreMediaRename',array('collection' => self::getCollection(), 'oldfilename' => $combinedfilename, 'newfilename' => $combinednewfilename));
  
  if(!@rename(self::getDirMedia() . self::getCollection() . '/' . $combinedfilename, self::getDirMedia() . self::getCollection() . '/' . $combinednewfilename)) {
   self::media_doError(_IM_RENAME_FAILED . ': ' . self::getCollection() . '/' . $combinedfilename);
   return;
  }
  
  $manager->notify('PostMediaRename',array('collection' => self::getCollection(), 'oldfilename' => $combinedfilename, 'newfilename' => $combinednewfilename));
  
  self::resetFilter();
  self::resetOffset();
  self::media_display();
  return;
 }
 
 private function media_upload() {
  global $CONF;
  if(!$CONF['AllowUpload']) {
   self::media_doError(_ERROR_DISALLOWED);
   return;
  }
  
  $uploadInfo = postFileInfo('uploadfile');
  $filename = $uploadInfo['name'];
  $filetype = $uploadInfo['type'];
  $filesize = $uploadInfo['size'];
  $filetempname = $uploadInfo['tmp_name'];
  $fileerror = intval($uploadInfo['error']);
  
  // include error code for debugging
  // (see http://www.php.net/manual/en/features.file-upload.errors.php)
  switch($fileerror) {
   case 0: // = UPLOAD_ERR_OK
   break;
   case 1: // = UPLOAD_ERR_INI_SIZE
   case 2: // = UPLOAD_ERR_FORM_SIZE
    self::media_doError(_ERROR_FILE_TOO_BIG);
    return;
   case 3: // = UPLOAD_ERR_PARTIAL
   case 4: // = UPLOAD_ERR_NO_FILE
   case 6: // = UPLOAD_ERR_NO_TMP_DIR
   case 7: // = UPLOAD_ERR_CANT_WRITE
   default:
   self::media_doError(_ERROR_BADREQUEST . ' (' . $fileerror . ')');
   return;
  }
  
  if($filesize > self::getMaxuploadsize()) {
   self::media_doError(_ERROR_FILE_TOO_BIG);
   return;
  }
  
  $ok = 0;
  foreach(self::getAllowedtypes() as $allowedtype) {
   if(eregi("\." . $allowedtype . "$",$filename))
    $ok = 1;
  }
  
  if(!$ok) {
   self::media_doError(_ERROR_BADFILETYPE);
   return;
  }
  
  if(!is_uploaded_file($filetempname)) {
   self::media_doError(_ERROR_BADREQUEST);
   return;
  }
  
  // prefix filename with current date (YYYY-MM-DD-) to avoid nameclashes
  if(self::getMediaprefix())
   $filename = strftime("%Y%m%d-", time()) . $filename;
  
  if(!self::getSubdir()) $combinedfilename = $filename;
  else $combinedfilename = self::getSubdir() . '/' . $filename;
  
  $message = MEDIA::addMediaObject(self::getCollection(), $filetempname, $combinedfilename);
  
  if($message != '') {
   self::media_doError($message);
   return;
  }
  
  self::resetFilter();
  self::resetOffset();
  
  if(!self::getAstool()) {
   $tempfilename =& self::getFilename();
   $tempfilename = $filename;
   self::media_embed_confirm(_IM_UPLOAD_CONPLETE);
   return;
  } else {
   self::media_display();
  }
 }
 
 private function media_embed() {
  global $manager, $member;
  
  $collections = self::getCollections();
  
  if(!self::getSubdir()) $combinedfilename = self::getFilename();
  else $combinedfilename = self::getSubdir() . '/' . self::getFilename();
  
  $jsCollection = str_replace("'","\\'", self::getCollection());
  $jsFileName = str_replace("'","\\'", $combinedfilename);
  
  list($width, $height, $filetype, $filesize) = self::getFileData(self::getCollection(), $combinedfilename);
  
  if(self::getAlttext() == '') {
   self::media_embed_confirm(_IM_REQUIREMENT);
   return;
  } elseif(mb_strlen(self::getAlttext(), _CHARSET) > 40 ) {
   self::media_embed_confirm(_IM_ALT_TOOLONG);
   return;
  }
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_EMBED  . "</h2>\n";
  echo '<h3>' . _IM_INCLUDE_DESC . '</h3>' . "\n";
  echo '<div class="filedetail">' . "\n";
  echo '<dl>' . "\n";
  echo '<dt>' . _IM_FILENAME . '</dt>' . "\n";
  echo '<dd>' . self::getFilename() . '</dd>' . "\n";
  echo '<dt>' . _IM_INCLUDE_ALT . '</dt>' . "\n";
  echo '<dd>' . self::getAlttext() . '</dd>' . "\n";
  
  if(self::getSubdir()) {
   echo '<dt>' . _IM_SUBDIR  . "</dt>\n";
   echo '<dd>' . self::getSubdir()  . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION . '</dt>' . "\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
  
  if(self::getWay() == 'popup') {
   echo '<dt>' . _IM_DIMENSIONS . '</dt>' . "\n";
   echo '<dd>' . $width . ' x ' . $height . ' (' . $filesize . 'KB)</dd>' . "\n";
   echo '<dt>' . _IM_INCLUDE_WAY . '</dt>' . "\n";
   echo '<dd>' . _IM_INCLUDE_WAY_POPUP . '</dd>' . "\n";
  } elseif(self::getWay()  == 'inline') {
   echo '<dt>' . _IM_DIMENSIONS . '</dt>' . "\n";
   echo '<dd>' . $width . ' x ' . $height . ' (' . $filesize . 'KB)</dd>' . "\n";
   echo '<dt>' . _IM_INCLUDE_WAY . '</dt>' . "\n";
   echo '<dd>' . _IM_INCLUDE_WAY_INLINE . '</dd>';
  } elseif(self::getWay() == 'other') {
   echo '<dt>' . _IM_DIMENSIONS . '</dt>' . "\n";
   echo '<dd>' . $filesize . 'KB' . '</dd>' . "\n";
   echo '<dt>' . _IM_INCLUDE_WAY . '</dt>' . "\n";
   echo '<dd>' . _IM_INCLUDE_WAY_OTHER . '</dd>';
  }
  
  echo '</dl>' . "\n";
  echo '</div>' . "\n";
  
  echo '<h3>' . _IM_INCLUDE_CODE . '</h3>' . "\n";
  echo '<p>' . _IM_INCLUDE_CODE_DESC . '</p>' . "\n";
  
  if(self::getWay() == 'popup') {
   echo '<p>&lt;%popup(' . self::getCollection() . '/' . $combinedfilename . '|' . $width . '|' . $height . '|' . self::getAlttext() . ')%&gt;</p>' . "\n";
   echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded" onclick="chooseImage(\'' . $jsCollection . '\',\'' . $jsFileName . '\',\'popup\',\'' . $width . '\',\'' . $height . '\',\'' . self::getAlttext() . '\'' . ')" >' . "\n";
  } elseif(self::getWay() == 'inline') {
   echo '<p>&lt;%image(' . self::getCollection() . '/' . $combinedfilename . '|' . $width . '|' . $height . '|' . self::getAlttext() . ')%&gt;</p>' . "\n";
   echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded" onclick="chooseImage(\'' . $jsCollection . '\',\'' . $jsFileName . '\',\'inline\',\'' . $width . '\',\'' . $height . '\',\'' . self::getAlttext() . '\'' . ')" >' . "\n";
  } else {
   echo '<p>&lt;%media(' . self::getCollection() . '/' . $combinedfilename . '|' . self::getAlttext() . ')%&gt;</p>' . "\n";
   echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded" onclick="chooseOther(\'' . $jsCollection . '\',\'' . $jsFileName . '\',\'' . self::getAlttext() . '\'' . ')" >' . "\n";
  }
  
  echo '<p class="left">' . "\n";
  echo '<input type="submit" value="' . _IM_INCLUDE . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo '<input type="hidden" name="mode" value="embed_confirm" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filename" value="' . self::getFilename() . '" />' . "\n";
  echo '<input type="hidden" name="alttext" value="' . self::getAlttext() . '" />' . "\n";
  echo '<input type="hidden" name="way" value="' . self::getWay() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_INCLUDE_MODIFIED . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_erase_confirm() {
  global $manager, $member;
  
  $collections = self::getCollections();
  
  if(!self::getSubdir()) $combinedcollection = self::getCollection();
  else $combinedcollection = self::getCollection() . '/' . self::getSubdir();
  
  list($width, $height, $filetype, $filesize) = self::getFileData($combinedcollection, self::getFilename());
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_ERASE_CONFIRM  . "</h2>\n";
  echo '<h3>' . _IM_ERASE_CONFIRM . '</h3>' . "\n";
  echo '<div class="filedetail">' . "\n";
  echo '<dl>' . "\n";
  echo '<dt>' . _IM_FILENAME . '</dt>' . "\n";
  echo '<dd>' . self::getFilename() . '</dd>' . "\n";
  
  if(self::getSubdir()) {
   echo '<dt>' . _IM_SUBDIR  . "</dt>\n";
   echo '<dd>' . self::getSubdir()  . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION . '</dt>' . "\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
   
  echo '<dt>' . _IM_DIMENSIONS . '</dt>' . "\n";
  echo '<dd>' . $filesize . 'KB' . '</dd>' . "\n";
  echo '</dl>' . "\n";
  echo '</div>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="erase" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filename" value="' . self::getFilename() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_ERASE_DONE . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="'. self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_rename_confirm($notice = '') {
  global $manager, $member;
  
  $collections = self::getCollections();
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_ERASE_CONFIRM  . "</h2>\n";
  
  if($notice) {
   echo '<h3>' . _IM_NOTICE . "</h3>\n";
   echo '<p class="notice">' .  $notice . "</p>\n";
  }
  
  if(!self::getSubdir()) $combinedcollection = self::getCollection();
  else $combinedcollection = self::getCollection() . '/' . self::getSubdir();
  
  list($update, $onlyfilename, $onlyprefix) = self::getSplitFilename($combinedcollection, self::getFilename());
  
  echo '<h3>' . _IM_RENAME_FILENAME . '</h3>' . "\n";
  echo '<div class="filedetail">'."\n";
  echo "<dl>\n";
  echo '<dt>' . _IM_FILENAME . '</dt>' . "\n";
  echo '<dd>' . $onlyfilename . '</dd>' . "\n";
  echo '<dt>' . _IM_TYPE . '</dt>' . "\n";
  echo '<dd>' . $onlyprefix . '</dd>' . "\n";
  
  if(self::getSubdir()) {
   echo '<dt>' . _IM_SUBDIR  . "</dt>\n";
   echo '<dd>' . self::getSubdir()  . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION . '</dt>' . "\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
   
   if(self::getMediaprefix())
   echo '<dt>'. _IM_UPDATE . "</dt>\n";
  else
   echo '<dt>'. _MEDIA_MODIFIED . "</dt>\n";  
  
  echo '<dd>' . $update . '</dd>' . "\n";
  echo '</dl>' . "\n";
  echo '</div>' . "\n";
  echo '<h3>' ._IM_RENAME_AFTER . '</h3>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' ._IM_RENAME_DESCRIPTION . '</p>' . "\n";
  echo '<p>' . "\n";
  
  if(self::getNewfilename())
   echo '<input type="text" name="newfilename" value="' . self::getNewfilename() . '" size="40" />' . "\n";
  else
   echo '<input type="text" name="newfilename" value="' . $onlyfilename . '" size="40" />' . "\n";
  
  echo '</p>' . "\n";
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="rename" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filename" value="' . self::getFilename() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_RENAME . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_upload_select() {
  global $CONF, $manager, $member;
  
  if(!$CONF['AllowUpload']) {
   self::media_doError(_ERROR_DISALLOWED);
   return;
  }
  
  $collections = self::getCollections();
  $subdirs = self::getSubdirs();
  
  if($subdirs)
   array_shift($subdirs);
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_UPLOAD_SELECT  . "</h2>\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<h3>' . _IM_WEBLOG_LABEL . "</h3>\n";
   echo '<p>' . self::getBlogName() . "</p>\n";
  }
  
  echo '<h3><label for="upload_collection">' . _IM_COLLECTION . "</label></h3>\n";
  echo "<p>\n";
  
  if(count($collections) > 1) {
   echo _IM_COLLECTION_DESC . "<br />\n";
   echo '<select name="collection" id="upload_collection">' . "\n";
   foreach($collections as $dirname => $description) {
    if((string)$dirname == (string)self::getCollection())
     echo '<option value="' . $dirname . '" selected="selected">' . $description . "</option>\n";
    else
     echo '<option value="' . $dirname . '">' . $description . "</option>\n";
   }
   echo '</select>' . "\n";
   echo '<input type="submit" name="button" value="' . _IM_COLLECTION_SELECT . '" title="' . _IM_COLLECTION_TT . '" class="formbutton" />' . "\n";
  } else {
   echo _IM_COLLECTION . ': ' . $collections[self::getCollection()] . '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  }
  echo '<input type="hidden" name="mode" value="upload_select" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="subdir" value="" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form  mode="' . self::getAccessURL() . '" method="post" enctype="multipart/form-data">' . "\n";
  echo '<h3><label for="media_subdir">' . _IM_SUBDIR . "</label></h3>\n";
  echo "<p>\n";
  
  if(count($subdirs) > 0) {
   echo _IM_SUBDIR_DESC . '<br />';
   echo '<select name="subdir" id="media_subdir">' . "\n";
   if(self::getSubdir() == '')
    echo '<option value="" selected="selected">' . _IM_SUBDIR_NONE . "</option>\n";
   else
    echo '<option value="">' . _IM_SUBDIR_NONE . "</option>\n";
   foreach($subdirs as $key => $options) {
    if($options['subdirname'] == self::getSubdir())
     echo '<option value="' . $options['subdirname'] . '" selected="selected">' . $options['subdirname'] . "</option>\n";
    else
     echo '<option value="' . $options['subdirname'] . '">' . $options['subdirname'] . "</option>\n";
   }
   echo '</select>' . "\n";
  } elseif(self::getSubdir() == '') {
   echo _IM_SUBDIR_LABEL . _IM_SUBDIR_NONE . '<input type="hidden" name="subdir" value="" />' . "\n";
  }
  echo "</p>\n";
  
  echo '<h3>' . _IM_UPLOAD_USED_FILETYPE . "</h3>\n";
  echo '<p>' . implode(', ', self::getAllowedtypes() ) . "</p>\n";
  echo '<h3><label for="uploadfile">' . _UPLOAD_TITLE . "</label></h3>\n";
  echo '<p><input type="file" name="uploadfile" id="uploadfile" size="30" />' . "</p>\n";
  echo '<h3>' . _IM_UPLOAD_USED_ASCII . "</h3>\n";
  echo '<p>' . _IM_UPLOAD_USED_ASCII_DESC1 . "</p>\n";
  echo '<p>' . _IM_UPLOAD_USED_ASCII_DESC2 . "</p>\n";
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="upload" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="MAX_FILE_SIZE" value="' . self::getMaxuploadsize() . ' " />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _UPLOAD_BUTTON . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo $manager->addTicketHidden . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_embed_confirm($notice = '') {
  global $manager, $member;
  
  $collections = self::getCollections();
  
  if(!self::getSubdir()) $combinedcollection = self::getCollection();
  else $combinedcollection = self::getCollection() . '/' . self::getSubdir();
  
  list($width, $height, $filetype, $filesize) = self::getFileData($combinedcollection, self::getFilename());
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_EMBED_CONFIRM  . "</h2>\n";
  
  if($notice) {
   echo '<h3>' . _IM_NOTICE . "</h3>\n";
   echo '<p class="notice">' . $notice . "</p>\n";
  }
  
  echo '<h3>'. _IM_INCLUDE_FILE_SELECTED . '</h3>' . "\n";
  echo '<div class="filedetail">' . "\n";
  echo '<dl>' . "\n";
  echo '<dt>' . _IM_FILENAME . '</dt>' . "\n";
  echo '<dd>' . self::getFilename() . '</dd>' . "\n";
  
  if(self::getSubdir()) {
   echo '<dt>' . _IM_SUBDIR  . "</dt>\n";
   echo '<dd>' . self::getSubdir()  . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION . '</dt>' . "\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
  
  echo '<dt>' . _IM_DIMENSIONS . '</dt>' . "\n";
  
  if($filetype !== 0)
   echo '<dd>' . $width . ' x ' . $height . ' (' . $filesize . 'KB)</dd>' . "\n";
  else
   echo '<dd>' . $filesize . 'KB' . '</dd>' . "\n";
  
  echo '</dl>' . "\n";
  echo '</div>' . "\n";
  echo '<h3>'. _IM_INCLUDE_ALT . '</h3>' . "\n";
  echo '<p><label for="alt">'. _IM_INCLUDE_ALT_DESC . "</label></p>\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  echo '<input type="text" name="alttext" value="'. self::getAlttext() . '" size="40" id="alt" />' . "\n";
  echo '</p>'. "\n";
  echo '<h3>'. _IM_INCLUDE_WAY . "</h3>\n";
  
  if($filetype !== 0) {
   echo '<p>'. "\n";
   if(self::getWay() == 'inline') {
    echo '<input type="radio" name="way" value="popup" id="popup" />'. "\n";
    echo '<label for="popup">'. _IM_INCLUDE_WAY_POPUP . '</label><br />'. "\n";
    echo '<input type="radio" name="way" value="inline" id="inline" checked="checked" />'. "\n";
    echo '<label for="inline">'. _IM_INCLUDE_WAY_INLINE . '</label>'. "\n";
   } else {
    echo '<input type="radio" name="way" value="popup" id="popup" checked="checked" />'. "\n";
    echo '<label for="popup">'. _IM_INCLUDE_WAY_POPUP . '</label><br />'. "\n";
    echo '<input type="radio" name="way" value="inline" id="inline" />'. "\n";
    echo '<label for="inline">'. _IM_INCLUDE_WAY_INLINE . '</label>'. "\n";
   }
   echo '</p>'. "\n";
  } else {
   echo '<p>'. _IM_INCLUDE_WAY_OTHER . "\n";
   echo '<input type="hidden" name="way" value="other" /></p>'. "\n";
  }
  
  echo '<p class="left">'. "\n";
  echo '<input type="hidden" name="mode" value="embed" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="'. self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="'. self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filename" value="'. self::getFilename() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="'. self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="'. self::getAstool() . '" />'. "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="'. _IM_INCLUDE_WAY_DECIDE . '" />'. "\n";
  echo '</p>'. "\n";
  echo '</form>'. "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">'. "\n";
  echo '<p class="right">'. "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="'. self::getCollection() . '" />'. "\n";
  echo '<input type="hidden" name="subdir" value="'. self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filter" value="' . self::getFilter() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="'. self::getOffset() . '" />'. "\n";
  echo '<input type="hidden" name="astool" value="'. self::getAstool() . '" />'. "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="'. _IM_RETURN . '" />'. "\n";
  echo '</p>'. "\n";
  echo '</form>'. "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_directory_remove_confirm($notice = '') {
  global $manager, $member;
  
  $collections = self::getCollections();
  $subdirs = self::getSubdirs();
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_SUBDIR_REMOVE_CONFIRM  . "</h2>\n";
  
  if($notice) {
   echo '<h3>' . _IM_NOTICE . "</h3>\n";
   echo '<p class="notice">' .  $notice . "</p>\n";
  }
  
  echo '<h3>' . _IM_REMOVE_SUBIDR . "</h3>\n";
  echo '<div class="filedetail">'."\n";
  echo "<dl>\n";
  echo '<dt>' . _IM_SUBDIR . "</dt>\n";
  echo '<dd>' . self::getSubdir() . '</dd>' . "\n";
  echo '<dt>' . _IM_SUBDIR_NUM_FILES . "</dt>\n";
  
  foreach($subdirs as $key => $options) {
   if($options['subdirname'] == self::getSubdir())
    echo '<dd>' . $options['number'] . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION .  "</dt>\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
   
  echo "</dl>\n";
  echo "</div>\n";
  
  echo '<h3>' . _IM_REMOVE_SUBIDR_CONFIRM . "</h3>\n";
  echo '<p>' . _IM_REMOVE_SUBIDR_REMIND . "</p>\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="directory_remove" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="filename" value="' . self::getFilename() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_ERASE_DONE . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="mode" value="directory_display" />' . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_directory_remove() {
  global $manager;
  
  if(self::getSubdir() == '') {
   self::media_doError(_IM_SUBDIR_FAILED_READ . ' (' . self::getCollection() . '/' . self::getSubdir() . ')');
   return;
  }
  
  $manager->notify('PreSubdirRemove', array('collection' => self::getCollection(), 'subdir' => self::getSubdir()));
  
  $log = self::removeSubdir(self::getCollection(), self::getSubdir());
  
  if($log) {
   self::media_doError($log);
   return;
  }
  
  $manager->notify('PostSubdirRemove',array('collection' => self::getCollection(), 'subdir' => self::getSubdir()));
  
  self::resetOffset();
  self::media_directory_display();
  return;
 }
 
 private function media_directory_create_confirm($notice = '') {
  global $manager;
  
  $collections = self::getCollections();
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_SUBDIR_CREATE_CONFIRM  . "</h2>\n";
  
  if($notice) {
   echo '<h3>' . _IM_NOTICE . "</h3>\n";
   echo '<p class="notice">' .  $notice . "</p>\n";
  }
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<h3>' . _IM_WEBLOG_LABEL . "</h3>\n";
   echo '<p>' . self::getBlogName() . "</p>\n";
  }
  
  echo "<h3>" . _IM_CREATE_SUBDIR_COLLECTION_LABEL . "</h3>\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo "<p>\n";
  echo '<label for="media_collection">' . _IM_CREATE_SUBDIR_COLLECTION . "</label><br />\n";
  echo '<select name="collection" id="media_collection">' . "\n";
  foreach ($collections as $dirname => $description){
   if((string)$dirname == (string)self::getCollection())
    echo '<option value="' . $dirname . '" selected="selected">' . $description . "</option>\n";
   else
    echo '<option value="' . $dirname . '">' . $description . "</option>\n";
  }
  echo '</select>' . "\n";
  echo "</p>\n";
  
  echo '<h3>' . _IM_CREATE_SUBDIR_INPUT_NAME . "</h3>\n";
  echo "<p>" . _IM_CREATE_SUBDIR_CHARS_DESC . "</p>\n";
  echo '<p>' . "\n";
  echo $manager->addTicketHidden() . "\n";
  if(self::getNewfilename() != '')
   echo '<input type="text" name="newfilename" value="' . self::getNewfilename() . '" size="20" />' . "\n";
  else
   echo '<input type="text" name="newfilename" value="" size="20" />' . "\n";
  echo '</p>' . "\n";
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="directory_create" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_CREATE_SUBDIR_CONFIRM . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo '<input type="hidden" name="mode" value="directory_display" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_directory_create() {
  global $manager;
  $newdirname = self::getNewfilename();
  
  if($newdirname == '') {
   self::media_directory_create_confirm(_IM_RENAME_SUBDIR_BLANK);
    return;
  } elseif(mb_strlen($newdirname,_CHARSET) > 20) {
   self::media_directory_create_confirm(_IM_RENAME_SUBDIR_TOOLONG);
    return;
  } elseif(!preg_match('#^[a-zA-Z0-9 \_\-\+]+$#', $newdirname)) {
   self::media_directory_create_confirm(_IM_RENAME_SUBDIR_WRONG);
    return;
  } elseif(stristr($newdirname, '%00')) {
   self::media_doError(_IM_RENAME_FORBIDDEN);
    return;
  } elseif(is_dir(self::getDirMedia() . self::getCollection() . '/' . $newdirname)) {
   self::media_directory_create_confirm(_IM_RENAME_SUBDIR_DUPLICATE);
    return;
  } elseif(!@is_dir(self::getDirMedia() . self::getCollection()) && is_numeric(self::getCollection())) {
   $oldumask = umask(0000);
   if(!@mkdir(self::getDirMedia() . self::getCollection(), 0777)) {
    self::media_doError(_ERROR_BADPERMISSIONS);
    return;
   }
   umask($oldumask);
  }
  
  $manager->notify('PreSubdirCreate',array('collection' => self::getCollection(), 'subdir' => $newdirname));
  
  $oldumask = umask(0000);
  if(!@mkdir(self::getDirMedia() . self::getCollection() . '/' . $newdirname))
   self::media_directory_create_confirm(_IM_CREATE_SUBDIR_WRONG . '(' . $collection . '/' . $newdirname . ')');
  umask($oldumask);
  @chmod(self::getDirMedia() . self::getCollection() . '/' . $newdirname, 0777);
  
  $manager->notify('PostSubdirCreate',array('collection' => self::getCollection(), 'subdir' => $newdirname));
  
  self::resetOffset();
  self::media_directory_display();
  return;
 }
 
 private function media_directory_rename_confirm($notice = '') {
  global $manager, $member;
  
  $collections = self::getCollections();
  $newdirname = self::getNewfilename();
  $subdirs = self::getSubdirs();
  
  self::media_head();
  echo '<h2>' . _IM_HEADER_SUBDIR_RENAME_CONFIRM  . "</h2>\n";
  
  if($notice) {
   echo '<h3>' . _IM_NOTICE . "</h3>\n";
   echo '<p class="notice">' .  $notice . "</p>\n";
  }
  
  echo '<h3>' . _IM_RENAME_SUBDIR_COLLECTION . "</h3>\n";
  echo '<div class="filedetail">'."\n";
  echo "<dl>\n";
  echo '<dt>' . _IM_SUBDIR . "</dt>\n";
  echo '<dd>' . self::getSubdir() . '</dd>' . "\n";
  echo '<dt>' . _IM_SUBDIR_NUM_FILES . "</dt>\n";
  
  foreach($subdirs as $key => $options) {
   if($options['subdirname'] == self::getSubdir())
    echo '<dd>' . $options['number'] . "</dd>\n";
  }
  
  echo '<dt>' . _IM_COLLECTION . "</dt>\n";
  echo '<dd>' . $collections[self::getCollection()] . '</dd>' . "\n";
  
  if(self::getBlogName() && self::getEachblogdir()) {
   echo '<dt>' . _IM_WEBLOG_LABEL . "</dt>\n";
   echo '<dd>' . self::getBlogName() . "</dd>\n";
  }
  
  echo '</dl>' . "\n";
  echo '</div>' . "\n";
  echo '<h3>' ._IM_RENAME_AFTER . '</h3>' . "\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' ._IM_RENAME_DESCRIPTION . '</p>' . "\n";
  echo '<p>' . "\n";
  if($newdirname != '')
   echo '<input type="text" name="newfilename" value="' . $newdirname . '" size="40" />' . "\n";
  else
   echo '<input type="text" name="newfilename" value="' . self::getSubdir() . '" size="40" />' . "\n";
  echo '</p>' . "\n";
  
  echo '<p class="left">' . "\n";
  echo '<input type="hidden" name="mode" value="directory_rename" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="subdir" value="' . self::getSubdir() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _IM_RENAME . '"  class="formbutton" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="mode" value="directory_display" />' . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_directory_rename() {
  global $manager;
  
  $newdirname = self::getNewfilename();
  
  if(self::getSubdir() == '') {
   self::media_doError(_IM_SUBDIR_FAILED_READ);
  }
  
  if($newdirname == '') {
   self::media_directory_rename_confirm(_IM_RENAME_SUBDIR_BLANK);
   return;
  } elseif(mb_strlen($newdirname,_CHARSET) > 20) {
   self::media_directory_rename_confirm(_IM_RENAME_SUBDIR_TOOLONG);
   return;
  } elseif(!preg_match('#^[a-zA-Z0-9 \_\-\+]+$#',$newdirname)) {
   self::media_directory_rename_confirm(_IM_RENAME_SUBDIR_WRONG);
   return;
  } elseif(stristr($newdirname, '%00')) {
   self::media_doError(_IM_RENAME_FORBIDDEN);
   return;
  } elseif(!is_dir(self::getDirMedia() . self::getCollection() . '/' . self::getSubdir())) {
   self::media_doError(_IM_MISSING_DIRECTORY);
   return;
  } elseif(is_dir(self::getDirMedia() . self::getCollection() . '/' . $newdirname)) {
   self::media_directory_rename_confirm(_IM_RENAME_SUBDIR_DUPLICATE);
   return;
  }
    
  $manager->notify('PreSubdirRename',array('collection' => self::getCollection(), 'olddirname' => self::getSubdir(), 'newdirname' => $newdirname));
  
  if(!@rename(self::getDirMedia() . self::getCollection() . '/' . self::getSubdir(), self::getDirMedia() . self::getCollection() . '/' . $newdirname)) {
   self::media_doError(_IM_RENAME_FAILED . ': ' . $collection . '/' . self::getSubdir() . '/' . $filename);
   return;
  }
    
  $manager->notify('PostSubdirRename',array('collection' => self::getCollection(), 'olddirname' => self::getSubdir(), 'newdirname' => $newdirname));
  
  self::resetOffset();
  self::media_directory_display();
  return;
 }
 
 private function media_directory_display() {
  global $manager, $member;
  
  $collections = self::getCollections();
  $subdirs = self::getSubdirs();
  $fileamount = count($subdirs) -1;
  
  self::media_head();
  
  echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  
  echo '<label for="media_collection">' . _IM_COLLECTION_LABEL . '</label>' . "\n";
  echo '<select name="collection" id="media_collection">' . "\n";
  foreach ($collections as $dirname => $description){
   if ((string)$dirname == (string)self::getCollection())
    echo '<option value="' . $dirname . '" selected="selected">' . $description . "</option>\n";
   else
    echo '<option value="' . $dirname . '">' . $description . "</option>\n";
  }
  echo '</select>' . "\n";
  echo '<input type="submit" name="mode" value="' . _IM_DISPLAY_SUBDIR_SELECT . '" class="formbutton" />' . "\n";
  echo '<input type="submit" name="mode" value="' . _IM_CREATE_SUBDIR_CONFIRM . '" class="formbutton" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . _IM_DISPLAY_SUBDIR_LABEL1 . $fileamount . _IM_DISPLAY_SUBDIR_LABEL2 . $subdirs[0]['number'] .  "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
  echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_DISPLAY_SUBDIR_RETURN . '"  class="formbutton" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  echo "<hr />\n";
  
  
  if($fileamount > 0) {
   $idxStart = ceil(self::getOffset() / self::getItemdisplay()) * self::getItemdisplay();
   $idxEnd = $idxStart + self::getItemdisplay();
   if($idxEnd > $fileamount)
    $idxEnd = $fileamount;
   $idxPrev = $idxStart - self::getItemdisplay();
   if($idxPrev < 0)
    $idxPrev =0;
   $idxNext = $idxStart + self::getItemdisplay();
   
   array_shift($subdirs);
   
   echo '<table frame="void" rules="none" summary="Directory List" width="100%">' . "\n";
   echo '<caption>' . _IM_DISPLAY_SUBDIR_CAPTION . ' &gt;&gt; ' . $collections[self::getCollection()];
   if(self::getBlogShortName() && self::getEachblogdir())
    echo ' &gt;&gt; ' . self::getBlogShortName();
   echo "</caption>\n";
   echo '<thead>' . "\n";
   echo '<tr>' . "\n";
   echo '<th>' . _IM_SUBDIR . "</th>\n";
   echo '<th>' . _IM_SUBDIR_NUM_FILES . "</th>\n";
   echo '<th colspan="3">' . _IM_ACTION . "</th>\n";
   echo '</tr>' . "\n";
   echo '</thead>' . "\n";
   echo '<tfoot>' . "\n";
   echo '<tr>' . "\n";
   echo '<td colspan="2">' . "\n";
   if ($idxStart > 0)
    echo '<a href="' . self::getAccessURL() . '&amp;mode=directory_display&amp;collection=' . urlencode(self::getCollection()) . '&amp;offset=' . urlencode($idxPrev) . '&amp;astool=' . urlencode(self::getAstool()) . '&amp;blogid=' . urlencode(self::getBlogid()) . '">' . _LISTS_PREV . ' &lt;</a> ';
   if(($idxStart + 1) !=  $idxEnd)
    echo ($idxStart + 1) . ' to ' . $idxEnd . '&nbsp;';
   if ($idxEnd < $fileamount )
    echo '<a href="' . self::getAccessURL() . '&amp;mode=directory_display&amp;collection=' . urlencode(self::getCollection()) . '&amp;offset=' . urlencode($idxNext) . '&amp;astool=' . urlencode(self::getAstool()) . '&amp;blogid=' . urlencode(self::getBlogid()) . '">' . '&gt; ' . _LISTS_NEXT . '</a>';
   echo "</td>\n";
   echo '<td colspan="3" class="right">'. "\n";
   if(self::getBlogName() && self::getEachblogdir())
    echo _IM_WEBLOG_LABEL . ': ' . self::getBlogName();
   echo "</td>\n";
   echo "</tr>\n";
   echo '</tfoot>' . "\n";
   echo '<tbody>' . "\n";
   
   for($i = $idxStart; $i < $idxEnd; $i++) {
    $options = $subdirs[$i];
    echo '<tr>' . "\n";
    if($options['subdirname'] == ".")
     echo '<td>' . _IM_SUBDIR_NONE . '</td>' . "\n";
    else
     echo '<td>' . $options['subdirname'] . '</td>' . "\n";
    echo '<td>' . $options['number'] . '</td>' . "\n";
    echo '<td>' . "\n";
    echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
    echo "<p>\n";
    echo '<input type="hidden" name="mode" value="display" />' . "\n";
    echo $manager->addTicketHidden() . "\n";
    echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
    echo '<input type="hidden" name="subdir" value="' . $options['subdirname'] . '" />' . "\n";
    echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
    echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
    echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
    echo '<input type="submit" name="button" value="' . _IM_DISPLAY_FILES . '" class="formbutton" />' . "\n";
    echo "</p>\n";
    echo '</form>' . "\n";
    echo '</td>' . "\n";
    
    if($options['subdirname'] == ".") {
     echo "<td></td>\n";
     echo "<td></td>\n";
     echo "</tr>\n";
    } else {
     echo '<td>' . "\n";
     echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
     echo "<p>\n";
     echo '<input type="hidden" name="mode" value="directory_remove_confirm" />' . "\n";
     echo $manager->addTicketHidden() . "\n";
     echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
     echo '<input type="hidden" name="subdir" value="' . $options['subdirname'] . '" />' . "\n";
     echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
     echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
     echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
     echo '<input type="submit" name="button" value="' . _IM_SUBDIR_REMOVE . '" class="formbutton" />' . "\n";
     echo "</p>\n";
     echo '</form>' . "\n";
     echo '</td>' . "\n";
     echo '<td>' . "\n";
     echo '<form action="' . self::getAccessURL() . '" method="post">' . "\n";
     echo "<p>\n";
     echo '<input type="hidden" name="mode" value="directory_rename_confirm" />' . "\n";
     echo $manager->addTicketHidden() . "\n";
     echo '<input type="hidden" name="collection" value="' . self::getCollection() . '" />' . "\n";
     echo '<input type="hidden" name="subdir" value="' . $options['subdirname'] . '" />' . "\n";
     echo '<input type="hidden" name="offset" value="' . self::getOffset() . '" />' . "\n";
     echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
     echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
     echo '<input type="submit" name="button" value="' . _IM_RENAME . '" class="formbutton" />' . "\n";
     echo "</p>\n";
     echo '</form>' . "\n";
     echo '</td>' . "\n";
     echo '</tr>' . "\n";
    }
   }
   echo '</tbody>' . "\n";
   echo '</table>' . "\n";
  } else {
   if(self::getBlogShortName() && self::getEachblogdir())
    echo "<p>" . _IM_DISPLAY_SUBDIR_NOTHING . ' &gt;&gt; ' . $collections[self::getCollection()] . ' &gt;&gt; ' . self::getBlogShortName() . "</p>\n";
   else
    echo "<p>" . _IM_DISPLAY_SUBDIR_NOTHING . ' &gt;&gt; ' . $collections[self::getCollection()] . "</p>\n";
  }
  
  self::media_foot();
  return;
 }
 
 private function media_loginAndPassThrough() {
  self::media_head();
  
  echo '<h2>' . _LOGIN_PLEASE . "</h2>\n";
  echo '<form method="post" action="' . self::getAccessURL() . '" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p>' . "\n";
  echo _LOGINFORM_NAME . '<br />' . "\n";
  echo '<input name="login"  size="20" maxlength="40" value="" /><br />' . "\n";
  echo _LOGINFORM_PWD . '<br />' . "\n";
  echo '<input name="password" type="password" size="20" maxlength="40" /><br />' . "\n";
  echo '<input type="checkbox" value="1" name="shared" id="nucleus_lf_shared" checked="checked" />' . "\n";
  echo '<label for="nucleus_lf_shared">' . _LOGINFORM_SHARED . "</label>\n";
  echo "</p>\n";
  echo "<p>\n";
  echo '<input name="mode" value="login" type="hidden" />' . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" value="' . _LOGIN . '" />' . "\n";
  echo "</p>\n";
  echo "</form>\n";
  
  self::media_foot();
  return;
 }
 
 private function media_doError($message) {
  global $manager;
  
  self::media_head();
  
  echo '<h2>' . _ERROR . "</h2>\n";
  echo '<p>' . $message . "</p>\n";
  echo '<form action="' . self::getAccessURL() . '" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
  echo '<p class="right">' . "\n";
  echo '<input type="hidden" name="mode" value="display" />' . "\n";
  echo $manager->addTicketHidden() . "\n";
  echo '<input type="hidden" name="astool" value="' . self::getAstool() . '" />' . "\n";
  echo '<input type="hidden" name="blogid" value="' . self::getBlogid() . '" />' . "\n";
  echo '<input type="submit" name="button" value="' . _IM_RETURN . '" />' . "\n";
  echo '</p>' . "\n";
  echo '</form>' . "\n";
  
  self::media_foot();
  return;
 }
 
 private function media_head() {
  sendContentType('application/xhtml+xml', 'media');
  echo '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n";
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
  echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">' . "\n";
  echo '<head>' . "\n";
  echo '<title>Media Control' . _IM_HEADER_TEXT . '</title>' . "\n";
  echo '<meta http-equiv="content-style-type" content="text/css" />' . "\n";
  echo '<link rel="stylesheet" type="text/css" href="' . self::getPluginDir() . 'popups.css" />' . "\n";
  echo '<meta http-equiv="content-script-type" content="text/javascript" />' . "\n";
  echo '<script type="text/javascript">' . "\n";
  
  if(self::getGreybox()) {
   echo 'function chooseImage(collection, filename, type, width, height, text) {' . "\n";
   echo ' top.window.focus();' . "\n";
   echo ' top.window.includeImage(collection, filename, type, width, height, text);' . "\n";
   echo ' top.window.GB_hide();' . "\n";
   echo '}' . "\n";
   echo 'function chooseOther(collection, filename, text) {' . "\n";
   echo ' top.window.focus();' . "\n";
   echo ' top.window.includeOtherMedia(collection, filename, text);' . "\n";
   echo ' top.window.GB_hide();' . "\n";
   echo '}' . "\n";
  } else {
   echo 'function chooseImage(collection, filename, type, width, height, text) {' . "\n";
   echo ' top.opener.focus();' . "\n";
   echo ' top.opener.includeImage(collection, filename, type, width, height, text);' . "\n";
   echo ' window.close();' . "\n";
   echo '}' . "\n";
   echo 'function chooseOther(collection, filename, text) {' . "\n";
   echo ' top.opener.focus();' . "\n";
   echo ' top.opener.includeOtherMedia(collection, filename, text);' . "\n";
   echo ' window.close();' . "\n";
   echo '}' . "\n";
  }
  
  echo '</script>' . "\n";
  echo '</head>' . "\n";
  echo '<body>' . "\n";
  
  if(!self::getGreybox()) {
   echo '<h1>Media Control<span class="header">' . _IM_HEADER_TEXT . '</span></h1>' . "\n";
   echo '<hr />' . "\n";
  }
  return;
 }
 
 private function media_foot() {
  echo "</body>\n";
  echo "</html>";
  exit;
 }
 
 private function getSplitFilename(&$collection, &$filename) {
  if(self::getMediaprefix()) {
   if(preg_match('#^([0-9]{8})\-(.*)\.([a-zA-Z0-9]{2,})$#', $filename, $filealt) == 1 ) {
    $update = preg_replace('#^([0-9]{4})([0-9]{2})([0-9]{2})$#', '$1/$2/$3', $filealt[1]);
    $onlyfilename = $filealt[2];
    $onlyprefix = $filealt[3];
   } else {
    preg_match('#^(.*)\.([a-zA-Z0-9]{2,})$#', $filename, $filealt);  
    $update = date("Y/m/d", @filemtime( self::getDirMedia() . $collection . '/' . $filename));
    $onlyfilename = $filealt[1];
    $onlyprefix = $filealt[2];
   } 
  } else {
   preg_match('#^(.*)\.([a-zA-Z0-9]{2,})$#', $filename, $filealt);
   $update = date("Y/m/d", @filemtime( self::getDirMedia() . $collection . '/' . $filename));
   $onlyfilename = $filealt[1];
   $onlyprefix = $filealt[2];
  }
  return array((string)$update, (string)$onlyfilename, (string)$onlyprefix);
 }
 
 private function getFileData(&$collection, &$filename) {
  $old_level = error_reporting(0);
  $size = @GetImageSize(self::getDirMedia() . $collection . '/' .$filename);
  error_reporting($old_level);
  $width = $size[0];
  $height = $size[1];
  $filetype = $size[2];
  $filesize = number_format(ceil((@filesize(self::getDirMedia() . $collection . '/' . $filename) / 1000)));
  
  return array((int)$width, (int)$height, (int)$filetype, (int)$filesize);
 }
 
 private function sort_media_by_filename($a, $b) {
  if($a->filename == $b->filename) return 0;
  elseif($a->filename > $b->filename) return -1;
  else return 1;
 }
 
 private function removeSubdir($collection, $subdir, $log = '') {
  $dirname = self::getDirMedia() . $collection . '/' . $subdir;
  
  if(!is_dir($dirname)) {
   $log .= _IM_SUBDIR_FAILED_READ . ' (' . $collection . '/' . $subdir . ')<br />';
   return $log;
  }
  
  if(($dir = @opendir($dirname)) == FALSE) {
   $log .= _IM_SUBDIR_FAILED_READ . ' (' . $collection . '/' . $subdir . ')<br />';
   return $log;
  } else {
   while(($file = readdir($dir)) !== false) {
    if($file != "." && $file != "..") {
     if(filetype($dirname . "/" . $file) == "dir")
      $log .= self::removeSubdir($collection, $subdir . "/" . $file, $log);
     else
      if(@unlink($dirname . "/" . $file) == false)
       $log .= _IM_SUBDIR_REMOVE_FAILED . ' (' . $collection . '/' . $subdir . '/' . $file . ")<br />";
    }
   }
  }
  closedir($dir);
  
  if($log)
   return $log;
  
  if(@rmdir($dirname) == false) {
   $log .= _IM_SUBDIR_REMOVE_FAILED  . ' (' . $collection . '/' . $subdir . ')<br />';
   return $log;
  }
 }
}
?>
