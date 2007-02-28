<?php

class NP_TestPostAddCategory extends NucleusPlugin
{
   /* ==================================================================================
	* Nucleus TestPostAddCategory Plugin
	*
	* Copyright 2007 by Kimitake
	*
	* ==================================================================================
	* This program is free software and open source software; you can redistribute
	* it and/or modify it under the terms of the GNU General Public License as
	* published by the Free Software Foundation; either version 2 of the License,
	* or (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful, but WITHOUT
	* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
	* FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
	* more details.
	*
	* You should have received a copy of the GNU General Public License along
	* with this program; if not, write to the Free Software Foundation, Inc.,
	* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA  or visit
	* http://www.gnu.org/licenses/gpl.html
	* ==================================================================================
	
   /*
	* Changes:
	* v0.1 kimitake		- initial version
	*/


	function getName() { return 'TestPostAddCategory';    }
	function getAuthor() { return 'kimitake';    }
	function getURL() { return 'http://kimitake.blogdns.net'; }
	function getVersion() { return '0.1'; }
	function getDescription() { return 'TestPostAddCategory'; }

	function supportsFeature($what)
	{
		switch($what)
		{
		case 'SqlTablePrefix':
			return 1;
		default:
			return 0;
		}
	}

	function install()
	{
	}

	function unintall()
	{
	}

	function getEventList() { return array('PostAddCategory'); }

	function event_PostAddCategory($data)
	{
		define('__WEBLOG_ROOT', dirname(dirname(realpath(__FILE__))));
		$handle = fopen(__WEBLOG_ROOT."/plugins/TestPostAddCategory.log", "w");

		if ($handle)
		{
			$str = print_r($data, TRUE);
			fputs($handle, $str);
			fclose($handle);
		}
	}
}
?>
