<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Viewer_Continue ($Revision: 1.32 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Viewer_Continue.php,v 1.32 2010/06/06 11:44:19 hsur Exp $
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


class Viewer_Continue extends PaintPlugin {

	function Viewer_Continue(){
		$this->id = '$Id: Viewer_Continue.php,v 1.32 2010/06/06 11:44:19 hsur Exp $';
		$this->name = 'paint continue';
		$this->animeExt = Array();
		$this->enable = true;
		
		$this->templatevars['id'] = $this->id;
		$this->templatevars['name'] = $this->name;
	}
			
	function getPageTemplate($vars = null){
		global $CONF, $manager;
		$plugin = $manager->getPlugin('NP_Paint');
		
		list($animeFile, $appletName) = $plugin->findAppletByImg($vars['file']);
		$fileInfo = $plugin->getFileInfo($animeFile);
		
		$vars['charset'] = _CHARSET;
		$vars['mediaurl'] = $CONF['MediaURL'];
		$vars['actionurl'] = $CONF['ActionURL'];
		$vars['quality'] = $fileInfo['quality'];
		
		$vars['appletTag'] = $plugin->_getAppletSelect($appletName);
		$vars['paletteTag'] = $plugin->_getPaletteSelect();
		$vars['paletteExtraTag'] = $plugin->_getPaletteSelectExtra();
		$vars['animation'] = ($plugin->getOption('defaultAnimation') == 'yes') ? 'checked' : '';
		
		if( $fileInfo ){
			foreach ($fileInfo as $key => $value){
				$animeInfo .= "&nbsp;&nbsp;* $key: $value<br />";
			}
		} else {
			$animeInfo = '<em>'. _PAINT_Viewer_infoNotFond .'</em>';
		}
		$vars['animeInfo'] = $animeInfo;
		
		$tpl = $this->template->fetch('pagetemplate', strtolower(__CLASS__));		
		$vars = array_merge((array)$vars, $this->templatevars);		
		
		return $this->template->fill($tpl, $vars, false);
	}
}
