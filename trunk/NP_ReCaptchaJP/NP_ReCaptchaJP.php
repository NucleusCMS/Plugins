<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_ReCaptchaJP ($Revision: 1.1 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_ReCaptchaJP.php,v 1.1 2008-05-03 09:39:48 hsur Exp $
  *
*/

/*
  * Copyright (C) 2007-2008 CLES. All rights reserved.
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

// reCAPTCHA Theme
define('NP_RECAPTCHAJP_THEME', 'clean');

global $recaptcha_api_server, $recaptcha_api_secure_server, $recaptcha_verify_server;
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('recaptchalib.php');

class NP_ReCaptchaJP extends NucleusPlugin {

	function getName() {
		return 'reCAPTCHAJP';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/18';
	}
	function getVersion() {
		return '1.1.0';
	}
	function getMinNucleusVersion() {
		return 320;
	}
	function getMinNucleusPatchLevel() {
		return 0;
	}
	function getEventList() {
		return array ('FormExtra', 'ValidateForm', );
	}
	function getDescription() {
		return _RECAPTCHAJP_DESC;
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
		$this->createOption('publicKey', 'reCAPTCHA Public Key', 'text', '');
		$this->createOption('privateKey', 'reCAPTCHA Private Key', 'text', '');
		$this->createOption('debug', 'Debug mode ?', 'yesno', 'no');
	}
	
	function init() {
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
			@include_once($this->getDirectory().'language/'.$language.'.php');
	}

	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'ReCaptchaJP: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'ReCaptchaJP: '.$msg);
	}

	function event_FormExtra(&$data) {
		global $manager, $member;
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
	
		$publicKey = $this->getOption('publicKey');
		if( ! $publicKey ){
			$this->_warn('reCAPTCHA Public Key is not set.');
			echo 'reCAPTCHA Public Key is not set.';
			return;
		}
		
		switch ($data['type']) {
			case 'membermailform-notloggedin' :
			case 'commentform-notloggedin' :			
				echo '<style>.recaptchatable td img { margin-top:0px; }</style>';
				echo "<script type=\"text/javascript\">\nvar RecaptchaOptions = { theme : '".NP_RECAPTCHAJP_THEME."' };\n</script>";
				echo _RECAPTCHAJP_header;
				echo recaptcha_get_html($publicKey, $this->error);
				break;
		}
	}

	function event_ValidateForm(&$data) {
		global $manager, $member;
		if ($member->isLoggedIn())
			return;
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		
		$privateKey = $this->getOption('privateKey');
		
		if ($_POST["recaptcha_response_field"]) {
			$resp = recaptcha_check_answer ($privateKey,
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]);
			
			if ($resp->is_valid) {
				// OK
			} else {
				$data['error'] = _RECAPTCHAJP_failedMessage . '(' . $resp->error . ')';
				$this->_info(_RECAPTCHAJP_failedMessage . ' (' . $resp->error . ')' );
			}
		} else {
			$data['error'] = _RECAPTCHAJP_nullMessage;
		}
	}
}