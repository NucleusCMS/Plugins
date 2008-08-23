<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_AuthSister ($Revision$)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id$
  *
*/

/*
  * Copyright (C) 2008 CLES. All rights reserved.
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

//Font Settings
define('_AuthSister_font', dirname(__FILE__).'/sharedlibs/auth_sister/ipagp.ttf');
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('auth_sister/core.php');
global $authSister;
$authSister = new AuthSister();
$authSister->session_start();

class NP_AuthSister extends NucleusPlugin {

	function getName() {
		return 'AuthSister';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/23';
	}
	function getVersion() {
		return '1.0.0';
	}
	function getMinNucleusVersion() {
		return 330;
	}
	function getMinNucleusPatchLevel() {
		return 0;
	}
	function getEventList() {
		return array ('FormExtra', 'ValidateForm', 'InitSkinParse');
	}
	function getDescription() {
		return _AuthSister_DESC;
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function hasAdminArea() {
		return 1;
	}
	
	function install() {
		$this->createOption('load', 'load', 'text', 'reiya');
	}
	
	function init() {
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
			@include_once($this->getDirectory().'language/'.$language.'.php');
					
		global $authSister, $CONF;
		
		$authSister->load = $this->getOption('load');
		$authSister->mes_a = _AuthSister_mes_a;
		$authSister->mes_b = _AuthSister_mes_b;
		$authSister->method = _AuthSister_method;
		$authSister->len_min = _AuthSister_len_min;
		$authSister->len_max = _AuthSister_len_max;
		$authSister->outlen = _AuthSister_outlen;
		$authSister->font = _AuthSister_font;
		$authSister->basedir = $this->getAdminURL().'../sharedlibs/auth_sister';
		$authSister->imageurl = $CONF['ActionURL'] . '?action=plugin&name=AuthSister&type=img';
	}

	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'AuthSister: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'AuthSister: '.$msg);
	}
	
	function event_InitSkinParse(&$data){
	}
	
	function doSkinVar($skinType, $type = '') {
		global $authSister;
		switch ($type) {
			case '':
			case 'header':
				$authSister->header();
				break;
			default:
				return 'Unexisting type: ' . $type;
		}
	}
	
	function doAction($type){
		global $CONF,$manager;
		global $authSister;
		$aActionsNotToCheck = array(
			'',
			'img',
		);
		if (!in_array($type, $aActionsNotToCheck)) {
			if (!$manager->checkTicket()) return _ERROR_BADTICKET;
		}

		switch ($type) {
			// When no action type is given, assume it's a ping
			case '':
			case 'img':
				$authSister->show_image('img','png');
				exit;
				break;
		}
	}
		
	function event_FormExtra(&$data) {
		global $manager, $member;
		global $authSister;
		if ($member->isLoggedIn())
			return;
		
		switch ($data['type']) {
			case 'commentform-notloggedin' :
			case 'membermailform-notloggedin': 
				break;
			default :
				return;
		}
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
			
		switch ($data['type']) {
			case 'membermailform-notloggedin' :
			case 'commentform-notloggedin' :
				echo $authSister->load();
				echo $authSister->insert();							
				break;
		}
	}

	function event_ValidateForm(&$data) {
		global $manager, $member;
		global $authSister;
		if ($member->isLoggedIn())
			return;
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		
		if ($authSister->auth()) {
			//echo $authSister->res();
			session_destroy();
		} else {
			$data['error'] = $authSister->res();
		}
	}
}
