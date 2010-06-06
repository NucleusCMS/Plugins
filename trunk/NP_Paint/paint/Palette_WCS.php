<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Palette_WCS.php ($Revision: 1.39 $)
  * for WCS DynPalette 2003/06/22
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Palette_WCS.php,v 1.39 2010/06/06 11:44:19 hsur Exp $
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


class Palette_WCS extends PaintPlugin {
	function getName(){
		return 'WCS DynPalette ' . _PAINT_Palette;
	}
	
	function Palette_WCS(){
		$this->id = '$Id: Palette_WCS.php,v 1.39 2010/06/06 11:44:19 hsur Exp $';
		$this->name = 'WCS DynPalette';
		
		$requiredFiles = Array('palette.js');
		$this->enable = $this->isFileInstalled($requiredFiles);
	}

	function getCopyrightsPart(){
		return <<<END
<a href="http://wondercatstudio.com/archive/">WCS DynPalette</a> by <a href="http://wondercatstudio.com/">WonderCatStudio</a>
( /w Palette_WCS Plugin by <a href="http://blog.cles.jp">cles</a> )
<br />
END;
	}
	
	function getBeforeAppletPart(){
		if (strstr($_SERVER['HTTP_USER_AGENT'], "Macintosh")) {
			return '<br /><br />';
		}
		return '';
	}
	
	function getHeaderPart(){
		return <<<END
<script type="text/javascript" charset="shift_jis" src="{$this->appletBaseUrl}palette.js"></script>
END;
	}
	
	function getAfterAppletPart(){
		if( $pidx = requestVar('pidx') ){
			return <<<END
<div style="width: 100px;">
<script type="text/javascript">
<!--
PaletteInit();
d = document
d.Palette.select.selectedIndex = $pidx
setTimeout("setPalette()", 2000)
function paintBBSCallback(value){
if(value=="start"){
	setPalette()
}
}
//-->
</script>
END;
		}
		return <<<END
<div style="width: 100px;">
<script type="text/javascript">
<!--
TableLineColor	= "#222222";
FontColor	= "#222222";
PaletteInit();
//-->
</script>
</div>
END;
	}
	
	function getExtraOption(){
		return $this->template->fetch('extraoption', strtolower(__CLASS__));
	}
}
