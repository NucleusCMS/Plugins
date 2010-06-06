<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Applet_PaintBBS ($Revision: 1.46 $)
  * for PaintBBSv2.22_8
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Applet_PaintBBS.php,v 1.46 2010/06/06 11:44:19 hsur Exp $
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

class Applet_PaintBBS extends PaintPlugin {
	function getName(){
		return _PAINT_Applet_PaintBBS_name . ' ' . _PAINT_Applet;
	}
		
	function Applet_PaintBBS(){
		$this->id = '$Id: Applet_PaintBBS.php,v 1.46 2010/06/06 11:44:19 hsur Exp $';
		$this->name = _PAINT_Applet_PaintBBS_name;
		$this->parser = 'paintbbs';
		
		$this->magic = 'P';
		$this->animeExt = Array(
			'.pch',
		);

		$requiredFiles = Array(
			'PaintBBS.jar'
		);
		$this->enable = $this->isFileInstalled($requiredFiles);
		
		$this->templatevars['id'] = $this->id;
		$this->templatevars['name'] = $this->name;
		$this->templatevars['parser'] = $this->parser;
		$this->templatevars['magic'] = $this->magic;
	}
	
	function getCopyrightsPart(){
		$tpl = $this->template->fetch('copyrights', strtolower(__CLASS__));
		return $this->template->fill($tpl, $this->templatevars, false);
	}
	
	function getPageTemplate($vars = null){
		$tpl = $this->template->fetch('pagetemplate', strtolower(__CLASS__));

		global $CONF;
		$this->templatevars['ActionURL'] = $CONF['ActionURL'];
		$this->templatevars['PluginURL'] = $CONF['PluginURL'];
		$this->templatevars['appletBaseUrl'] = $this->appletBaseUrl;

		$vars = array_merge((array)$vars, $this->templatevars);		
		return $this->template->fill($tpl, $vars, false);
	}
}
