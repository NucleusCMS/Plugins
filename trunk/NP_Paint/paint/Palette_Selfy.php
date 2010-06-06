<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Palette_Selfy.php ($Revision: 1.17 $)
  * for palette_selfy.js 2004/04/11
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Palette_Selfy.php,v 1.17 2010/06/06 11:44:19 hsur Exp $
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


class Palette_Selfy extends PaintPlugin {
	function getName(){
		return 'palette-selfy ' . _PAINT_Palette;
	}
	
	function Palette_Selfy(){
		$this->id = '$Id: Palette_Selfy.php,v 1.17 2010/06/06 11:44:19 hsur Exp $';
		$this->name = 'Palette-Selfy';
		
		$requiredFiles = Array('palette_selfy.js');
		$this->enable = $this->isFileInstalled($requiredFiles);
	}

	function getCopyrightsPart(){
	return <<<END
<a href="http://useyan.x0.com/s/cgi/relm/palette_selfy.html">Palette-Selfy</a> by <a href="http://roo.to/materia/">Snow Materia</a>
( /w Palette_Selfy Plugin by <a href="http://blog.cles.jp">cles</a> )
<br />
END;
	}
	
	function getHeaderPart(){
	return <<<END
<script type="text/javascript" src="{$this->appletBaseUrl}palette_selfy.js" charset="shift_jis"></script>
END;
	}
	
	function getAfterAppletPart(){
	return <<<END
<div style="width: 100px;">
<script type="text/javascript" charset="shift_jis">
<!--
palette_selfy();
//-->
</script>
</div>
END;
	}
}
