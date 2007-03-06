<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_CommentSpamCheck ($Revision: 1.2 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_AddSpamCheckEvent.php,v 1.2 2007-03-06 20:54:37 hsur Exp $
*/

/*
  * Copyright (C) 2007 cles All rights reserved.
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
  * In addition, as a special exception, mamio and cles gives
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

class NP_AddSpamCheckEvent extends NucleusPlugin {
	function getName() {
		return 'AddSpamCheckEvent';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/';
	}
	function getVersion() {
		return '1.2.0';
	}
	function getDescription() {
		return 'Add SpamCheck event';
	}
	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
				return 1;
			default :
				return 0;
		}
	}

	function getEventList() {
		return array ('ValidateForm');
	}

	function event_ValidateForm(& $data) {
		global $manager, $member;
		if ($member->isLoggedIn())
			return;
			
		$spamcheck = array();
		switch( $data['type'] ){
			case 'membermail':
				$spamcheck = array (
					'type' => 'membermail', 
					'data' => postVar('frommail')."\n".postVar('message'), 
					'live' => true, 
					'return' => true,
				);
				break;
			case 'comment':
				$spamcheck = array (
					'type' => 'comment', 
					'body' => postVar('body'), 
					'author' => $data['comment']['user'], 
					'url' => $data['comment']['userid'], 
					'id' => intval($data['comment']['itemid']), 
					'live' => true, 
					'return' => true,
					//SpamCheck API1 Compat
					'data' => postVar('body')."\n".$data['comment']['user']."\n".$data['comment']['userid'],
				);
				break;
			default:
				return;
		}

		$manager->notify('SpamCheck', array ('spamcheck' => & $spamcheck));
		if (isset($spamcheck['result']) && $spamcheck['result'] == true) {
			if ($manager->pluginInstalled('NP_Blacklist')) {
				$plugin = & $manager->getPlugin('NP_Blacklist');
				$plugin->_redirect($plugin->getOption('redirect'));
			} else {
				$this->_showForbiddenMessage($spamcheck['message']);
			}
		}
	}

	function _showForbiddenMessage($message = '') {
		header("HTTP/1.0 403 Forbidden");
		header("Status: 403 Forbidden");
		echo '<html><header><title>403 Forbidden</title></header><body><h1>403 Forbidden</h1><p>'.$message.'</p></body></html>';
		exit;
	}
}
