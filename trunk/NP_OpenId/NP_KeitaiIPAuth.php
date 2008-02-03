<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_KeitaiIPAuth ($Revision: 1.1 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_KeitaiIPAuth.php,v 1.1 2008-02-03 13:11:23 hsur Exp $
  *
*/

/*
  * Copyright (C) 2005-2006 CLES. All rights reserved.
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

class NP_KeitaiIPAuth extends NucleusPlugin {

	function getName() {
		return 'KeitaiIPAuth';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles';
	}
	function getVersion() {
		return '1.1';
	}
	function getMinNucleusVersion() {
		return 320;
	}
	function getMinNucleusPatchLevel() {
		return 0;
	}
	function getEventList() {
		return array ('ExternalAuth');
	}
	function getDescription() {
		return 'KeitaiIPAuth';
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	
	function init() {
	}

	function install() {
	}

	function unInstall() {
	}
	
	// 2007/7/16
	var $keitaiNetworks = array(
		// Docomo
		// http://www.nttdocomo.co.jp/service/imode/make/content/ip/
		'210.153.84.0/24',
		'210.136.161.0/24',
		'210.153.86.0/24',
		'210.153.87.0/24',
				// AU
		// http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html
		'210.169.40.0/24',
		'210.196.3.192/26',
		'210.196.5.192/26',
		'210.230.128.0/24',
		'210.230.141.192/26',
		'210.234.105.32/29',
		'210.234.108.64/26',
		'210.251.1.192/26',
		'210.251.2.0/27',
		'211.5.1.0/24',
		'211.5.2.128/25',
		'211.5.7.0/24',
		'218.222.1.0/24',
		'61.117.0.0/24',
		'61.117.1.0/24',
		'61.117.2.0/26',
		'61.202.3.0/24',
		'219.108.158.0/26',
		'219.125.148.0/24',
		'222.5.63.0/24',
		'222.7.56.0/24',
		'222.5.62.128/25',
		'222.7.57.0/24',
		'59.135.38.128/25',
		'219.108.157.0/25',
		'219.125.151.128/25',
		'219.125.145.0/25',
		// SoftBank
		// http://developers.softbankmobile.co.jp/dp/tech_svc/web/ip.php
		'202.179.204.0/24',
		'202.253.96.248/29',
		'210.146.7.192/26',
		'210.146.60.192/26',
		'210.151.9.128/26',
		'210.169.130.112/29',
		'210.169.130.120/29',
		'210.169.176.0/24',
		'210.175.1.128/25',
		'210.228.189.0/24',
		'211.8.159.128/25',
		// WILLCOM
		// http://www.willcom-inc.com/ja/service/contents_service/club_air_edge/for_phone/ip/
		'125.28.0.0/24',
		'125.28.1.0/24',
		'125.28.11.0/24',
		'125.28.12.0/24',
		'125.28.13.0/24',
		'125.28.14.0/24',
		'125.28.15.0/24',
		'125.28.16.0/24',
		'125.28.17.0/24',
		'125.28.2.0/24',
		'125.28.3.0/24',
		'125.28.4.0/24',
		'125.28.5.0/24',
		'125.28.6.0/24',
		'125.28.7.0/24',
		'125.28.8.0/24',
		'210.168.246.0/24',
		'210.168.247.0/24',
		'211.18.232.0/24',
		'211.18.233.0/24',
		'211.18.234.0/24',
		'211.18.235.0/24',
		'211.18.236.0/24',
		'211.18.237.0/24',
		'211.18.238.0/24',
		'211.18.239.0/24',
		'219.108.0.0/24',
		'219.108.1.0/24',
		'219.108.10.0/24',
		'219.108.14.0/24',
		'219.108.2.0/24',
		'219.108.3.0/24',
		'219.108.4.0/24',
		'219.108.5.0/24',
		'219.108.6.0/24',
		'219.108.7.0/24',
		'219.108.8.0/24',
		'219.108.9.0/24',
		'221.119.0.0/24',
		'221.119.1.0/24',
		'221.119.2.0/24',
		'221.119.3.0/24',
		'221.119.4.0/24',
		'221.119.5.0/24',
		'221.119.6.0/24',
		'221.119.7.0/24',
		'221.119.8.0/24',
		'221.119.9.0/24',
		'61.198.129.0/24',
		'61.198.138.100/32',
		'61.198.138.101/32',
		'61.198.138.102/32',
		'61.198.140.0/24',
		'61.198.141.0/24',
		'61.198.142.0/24',
		'61.198.161.0/24',
		'61.198.165.0/24',
		'61.198.166.0/24',
		'61.198.168.0/24',
		'61.198.169.0/24',
		'61.198.170.0/24',
		'61.198.248.0/24',
		'61.198.249.0/24',
		'61.198.250.0/24',
		'61.198.253.0/24',
		'61.198.254.0/24',
		'61.198.255.0/24',
		'61.204.0.0/24',
		'61.204.2.0/24',
		'61.204.3.0/25',
		'61.204.4.0/24',
		'61.204.5.0/24',
		'61.204.6.0/25',
	);

	function event_ExternalAuth(&$data){
		static $result = null;
		
        if( isset($data['externalauth']['result']) && $data['externalauth']['result'] == true ){
            return;
        }
		
		if( $result === null ){
			foreach( $this->keitaiNetworks as $network ){
				if( $this->netMatch($network, serverVar('REMOTE_ADDR')) ){
					$result = true;
					break;
				}
			}
		}
		
		if( $result ){
			$data['externalauth']['result'] = true;
			$data['externalauth']['plugin'] = $this->getName();
		} else {
			$result = false;
		}
	}
	
	function netMatch($network, $ip) {
		list($network, $mask) = explode('/', $network);
		$network = ip2long($network);
		$mask = 0xffffffff << (32 - $mask);
		$ip = ip2long($ip);
		
		return ($ip & $mask) == ($network & $mask);
	}
	
}
