<?php
// vim: tabstop=2:shiftwidth=2

/**
  * PaintPlugin.php ($Revision: 1.20 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: PaintPlugin.php,v 1.20 2010/06/06 11:44:19 hsur Exp $
*/

/*
  * Copyright (C) 2005-2010 CLES. All rights reserved.
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

class PaintPlugin {
	var $appletBaseUrl = '';
	var $type = 'unknown';
	var $enable = false;
	var $template = null;
	
	function _info($msg) {
		global $manager;
		
		$plugin = $manager->getPlugin('NP_Paint');
		if( $plugin )
			$plugin->_info($msg);
	}

	function isFileInstalled($files){
		global $DIR_PLUGINS;
		
		// if $files is array
		if( is_array($files) ){
			foreach( $files as $file ){
				$filePath = $DIR_PLUGINS . 'paint/applet/' . $file;
				if( ! file_exists($filePath) ){
					$this->_info($filePath . " is required. [{$this->name}]");
					return false;
				}
			}
			return true;
		}
		
		// if $files is string
		return file_exists($DIR_PLUGINS . 'paint/applet' . $files);
	}
		
	function getPageTemplate($vars = null){
		return '';
	}
	
	function getHeaderPart(){
		return '';
	}
	
	function getParamPart(){
		return '';
	}
	
	function getCopyrightsPart(){
		return '';
	}

	function getBeforeAppletPart(){
		return '';	
	}
	
	function getAfterAppletPart(){
		return '';
	}
	
	function getExtraOption(){
		return '';	
	}
}
