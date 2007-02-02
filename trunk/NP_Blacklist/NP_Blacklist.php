<?php

/**
  * NP_Blacklist(JP) ($Revision: 1.6 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_Blacklist.php,v 1.6 2007-02-02 16:48:25 hsur Exp $
  *
  * Based on NP_Blacklist 0.98
  * by xiffy
  * http://forum.nucleuscms.org/viewtopic.php?t=5300
*/

/*
  * Copyright (C) 2005-2007 cles All rights reserved.
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
*/

include_once(dirname(__FILE__)."/blacklist/blacklist_lib.php");

class NP_Blacklist extends NucleusPlugin {
	function getName() {
		return 'Blacklist(JP)';
	}
	function getAuthor() {
		return 'xiffy + hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/11';
	}
	function getVersion() {
		return '1.0.0';
	}
	function getDescription() {
		return '[$Revision: 1.6 $]<br />'.NP_BLACKLIST_description;
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
				return 1;
			default :
				return 0;
		}
	}

	function install() {
		// create some options
		$this->createOption('enabled', NP_BLACKLIST_enabled, 'yesno', 'yes');
		$this->createOption('redirect', NP_BLACKLIST_redirect, 'text', '');
		$this->createOption('ipblock', NP_BLACKLIST_ipblock, 'yesno', 'yes');
		$this->createOption('ipthreshold', NP_BLACKLIST_ipthreshold, 'text', '10');
		$this->createOption('BulkfeedsKey', NP_BLACKLIST_BulkfeedsKey, 'text', '');
		$this->createOption('SkipNameResolve', NP_BLACKLIST_SkipNameResolve, 'yesno', 'yes');

		$this->_initSettings();
	}

	function unInstall() {
	}

	function getPluginOption($name) {
		return $this->getOption($name);
	}

	function getEventList() {
		$this->_initSettings();
		return array ('QuickMenu', 'SpamCheck');
	}

	function hasAdminArea() {
		return 1;
	}

	function init() {
		// include language file for this plugin 
		$language = ereg_replace('[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().'language/'.$language.'.php'))
			@ include_once ($this->getDirectory().'language/'.$language.'.php');
		else
			@ include_once ($this->getDirectory().'language/english.php');
		$this->resultCache = false;
	}

	function event_QuickMenu(& $data) {
		global $member, $nucleus, $blogid;
		// only show to admins
		if (preg_match("/MD$/", $nucleus['version'])) {
			$isblogadmin = $member->isBlogAdmin(-1);
		} else {
			$isblogadmin = $member->isBlogAdmin($blogid);
		}
		if (!($member->isLoggedIn() && ($member->isAdmin() | $isblogadmin)))
			return;
		array_push($data['options'], array ('title' => NP_BLACKLIST_name, 'url' => $this->getAdminURL(), 'tooltip' => NP_BLACKLIST_nameTips,));
	}

	// handle SpamCheck event
	function event_SpamCheck(& $data) {
		global $DIR_PLUGINS;
		if (isset ($data['spamcheck']['result']) && $data['spamcheck']['result'] == true) {
			// Already checked... and is spam
			return;
		}

		if (!isset ($data['spamcheck']['return'])) {
			$data['spamcheck']['return'] = true;
		}

		// for SpamCheck API 2.0 compatibility
		if (!$data['spamcheck']['data']) {
			switch (strtolower($data['spamcheck']['type'])) {
				case 'comment' :
					$data['spamcheck']['data'] = $data['spamcheck']['body']."\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['author']."\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['url']."\n";
					break;
				case 'trackback' :
					$data['spamcheck']['data'] = $data['spamcheck']['title']."\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['excerpt']."\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['blogname']."\n";
					$data['spamcheck']['data'] .= $data['spamcheck']['url'];
					break;
				case 'referer' :
					$data['spamcheck']['data'] = $data['spamcheck']['url'];
					break;
			}
		}
		$ipblock = ($data['spamcheck']['ipblock']) || ($data['spamcheck']['live']);

		// Check for spam
		$result = $this->blacklist($data['spamcheck']['type'], $data['spamcheck']['data'], $ipblock);

		if ($result) {
			// Spam found
			// logging !
			pbl_logspammer($data['spamcheck']['type'].': '.$result);
			if (isset ($data['spamcheck']['return']) && $data['spamcheck']['return'] == true) {
				// Return to caller
				$data['spamcheck']['result'] = true;
				$data['spamcheck']['plugin'] = $this->getName();
				$data['spamcheck']['message'] = 'Marked as spam by NP_Blacklist';
				return;
			} else {
				$this->_redirect($this->getOption('redirect'));
			}
		}
	}

	// Obsolete
	function event_PreAddComment(& $data) {
		$comment = $data['comment'];
		$result = $this->blacklist('comment', postVar('body')."\n".$comment['host']."\n".$comment['user']."\n".$comment['userid']);
		if ($result) {
			pbl_logspammer('comment: '.$result);
			$this->_redirect($this->getOption('redirect'));
		}
	}

	// Obsolete
	function event_ValidateForm(& $data) {
		if ($data['type'] == 'comment') {
			$comment = $data['comment'];
			$result = $this->blacklist('comment', postVar('body')."\n".$comment['host']."\n".$comment['user']."\n".$comment['userid']);
			if ($result) {
				pbl_logspammer('comment: '.$result);
				$this->_redirect($this->getOption('redirect'));
			}
		} else {
			if ($data['type'] == 'membermail') {
				$result = $this->blacklist('membermail', postVar('frommail')."\n".postVar('message'));
				if ($result) {
					pbl_logspammer('membermail: '.$result);
					$this->_redirect($this->getOption('redirect'));
				}
			}
		}
	}

	// Obsolete
	function event_PreSkinParse(& $data) {
		$result = $this->blacklist('PreSkinParse', '');
		if ($result) {
			pbl_logspammer('PreSkinParse: '.$result);
			$this->_redirect($this->getOption('redirect'));
		}
	}

	function blacklist($type, $testString, $ipblock = true) {
		global $DIR_PLUGINS, $member;
		if ($this->resultCache)
			return $this->resultCache.'[Cached]';

		if ($member->isLoggedIn()) {
			return '';
		}

		if ($this->getOption('enabled') == 'yes') {
			// update the blacklist first file
			//pbl_updateblacklist($this->getOption('update'),false);
			if ($ipblock) {
				$ipblock = ($this->getOption('ipblock') == 'yes') ? true : false;
			}

			$result = '';
			if ($ipblock || $testString != '') {
				$result = pbl_checkforspam($testString, $ipblock, $this->getOption('ipthreshold'), true);
			}

			if ($result) {
				$this->resultCache = $result;
			}

			return $result;
		}
	}

	function submitSpamToBulkfeeds($url) {
		if (is_array($url))
			$url = implode("\n", $url);

		$postData['apikey'] = $this->getOption('BulkfeedsKey');
		if (!$postData['apikey'])
			return "BulkfeedsKey not found. see http://bulkfeeds.net/app/register_api.html";
		$postData['url'] = $url;

		$data = $this->_http('http://bulkfeeds.net:80/app/submit_spam.xml', 'POST', '', $postData);
		return $data;
	}

	function _http($url, $method = "GET", $headers = "", $post = array ("")) {
		$URL = parse_url($url);

		if (isset ($URL['query'])) {
			$URL['query'] = "?".$URL['query'];
		} else {
			$URL['query'] = "";
		}

		if (!isset ($URL['port']))
			$URL['port'] = 80;

		$request = $method." ".$URL['path'].$URL['query']." HTTP/1.0\r\n";

		$request .= "Host: ".$URL['host']."\r\n";
		$request .= "User-Agent: NP_Blacklist/".phpversion()."\r\n";

		if (isset ($URL['user']) && isset ($URL['pass'])) {
			$request .= "Authorization: Basic ".base64_encode($URL['user'].":".$URL['pass'])."\r\n";
		}

		$request .= $headers;

		if (strtoupper($method) == "POST") {
			while (list ($name, $value) = each($post)) {
				$POST[] = $name."=".urlencode($value);
			}
			$postdata = implode("&", $POST);
			$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$request .= "Content-Length: ".strlen($postdata)."\r\n";
			$request .= "\r\n";
			$request .= $postdata;
		} else {
			$request .= "\r\n";
		}

		$fp = fsockopen($URL['host'], $URL['port'], $errno, $errstr, 20);

		if ($fp) {
			socket_set_timeout($fp, 20);
			fputs($fp, $request);
			$response = "";
			while (!feof($fp)) {
				$response .= fgets($fp, 4096);
			}
			fclose($fp);
			$DATA = split("\r\n\r\n", $response, 2);
			return $DATA[1];
		} else {
			$host = $URL['host'];
			$port = $URL['port'];
			ACTIONLOG :: add(WARNING, $this->getName().':'."[$errno]($host:$port) $errstr");
			return "";
		}
	}

	function _redirect($url) {
		if (!$url) {
			header("HTTP/1.0 403 Forbidden");
			header("Status: 403 Forbidden");

			include (dirname(__FILE__).'/blacklist/blocked.txt');
		} else {
			$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $url);
			header('Location: '.$url);
		}
		exit;
	}

	function _initSettings() {
		$settingsDir = dirname(__FILE__).'/blacklist/settings/';
		$settings = array ('blacklist.log', 'blockip.pbl', 'matched.pbl', 'blacklist.pbl', 'blacklist.txt', 'suspects.pbl',);
		$personalBlacklist = $settingsDir.'personal_blacklist.pbl';
		$personalBlacklistDist = $settingsDir.'personal_blacklist.pbl.dist';

		// setup settings
		if ($this->_is_writable($settingsDir)) {
			foreach ($settings as $setting) {
				touch($settingsDir.$setting);
			}
			// setup personal blacklist
			if (!file_exists($personalBlacklist)) {
				if (copy($personalBlacklistDist, $personalBlacklist)) {
					$this->_warn("'$personalBlacklist' ".NP_BLACKLIST_isCreated);
				} else {
					$this->_warn("'$personalBlacklist' ".NP_BLACKLIST_canNotCreate);
				}
			}
		}

		// check settings	
		foreach ($settings as $setting) {
			$this->_is_writable($settingsDir.$setting);
		}
		$this->_is_writable($personalBlacklist);

		// setup and check cache dir
		$cacheDir = NP_BLACKLIST_CACHE_DIR;
		$this->_is_writable($cacheDir);
	}

	function _is_writable($file) {
		$ret = is_writable($file);
		if (!$ret) {
			$this->_warn("'$file' ".NP_BLACKLIST_isNotWritable);
		}
		return $ret;
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'Blacklist: '.$msg);
	}

}
