<?php
// vim: tabstop=2:shiftwidth=2

/**
  * rd.php ($Revision: 1.1 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: upload.php,v 1.1 2010/04/24 19:15:52 hsur Exp $
*/

/*
  * Copyright (C) 2010 CLES. All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

$strRel = '../../../';
require($strRel.'config.php');
require($DIR_LIBS.'PLUGINADMIN.php');

// create the admin area page
$oPluginAdmin = new PluginAdmin('DragAndDropUploader');
$action = requestVar('action');
if( !$action ) $action = '';
$oPluginAdmin->plugin->doAction($action);
