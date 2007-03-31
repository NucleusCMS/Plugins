<?php

/**
  * REJECT 'ONLY ASCII' COMMENT AND TRACKBACK PLUGIN FOR NucleusCMS
  * PHP versions 4 and 5
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
  * 
  * 
  * @author     shizuki
  * @copyright  2006-2007 shizuki
  * @license    http://www.gnu.org/licenses/gpl.txt
  *             GNU GENERAL PUBLIC LICENSE Version 2, June 1991
  * @version    0.3
  * @link       http://shizuki.kinezumi.net
  *
  * 0.3  Release version
  * 0.2  supports SpamCheck API 2
  *      supports NP_TrackBack
  * 0.1  initial
  *
  **/

class NP_RejectASCIIComment extends NucleusPlugin
{

	// name of plugin
	function getName()
	{
		$retData = 'Reject ASCII Comment and TrackBack';
		return $retData;
	}

	// author of plugin
	function getAuthor()
	{
		$retData = 'shizuki';
		return $retData;
	}

	// an URL to the plugin website
	function getURL()
	{
		$retData = 'http://shizuki.kinezumi.net';
		return $retData;
	}

	// version of the plugin
	function getVersion()
	{
		$retData = '0.3';
		return $retData;
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
		$retData = _REJECT_ASCII_DESCRIPTION;
		if (_CHARSET != 'UTF-8') {
			mb_convert_encoding($retData, _CHARSET, 'UTF-8');
		}
		return $retData;
	}

	function getEventList()
	{
		return array(
					 'SpamCheck',
					 'ValidateForm'
					);
	}

	function install()
	{
		$optionData = _REJECT_ASCII_HOOK;
		if (_CHARSET != 'UTF-8') {
			mb_convert_encoding($optionData, _CHARSET, 'UTF-8');
		}
		$this->createBlogOption('hook',  $optionData, 'yesno', 'no');
		$optionData = _REJECT_ASCII_DELTB;
		if (_CHARSET != 'UTF-8') {
			mb_convert_encoding($optionData, _CHARSET, 'UTF-8');
		}
		$this->createBlogOption('deltb', $optionData, 'yesno', 'no');
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function init()
	{
		global $admin;
		$langFile = $this->getDirectory() . 'japanese.php';
		if (file_exists($langFile)) {
			include_once($langFile);
		}
	}

	function event_SpamCheck(&$data)
	{
		global $DIR_PLUGINS, $member;

		$spamCheck = $data['spamcheck'];
		if ($spamCheck['result'] == TRUE) {
			return FALSE;
		}

		if ($member->isLoggedIn()) {
			return FALSE;
		}

		$type     = (!empty($spamCheck['type'])) ? $spamCheck['type'] : 'post';
		$encoding = $this->_detect_encoding($excerpt);
		switch ($type) {
			case 'comment':
				$checkData = $spamCheck['body'];
				$item_id   = intval($spamCheck['id']);
				break;
			case 'trackback':
				$checkData = $spamCheck['excerpt'] . $spamCheck['title'] . $spamCheck['blogname'];
				$item_id   = intval($spamCheck['id']);
				break;
			default:
		}
		$bid       = intval(getBlogIDFromItemID($item_id));
		$bname     = getBlogNameFromID($bid);
		$inque     = 'SELECT ititle as result FROM %s WHERE inumber = %d';
		$inque     = sprintf($inque, sql_table('item'), $item_id);
		$iname     = quickQuery($inque);
		$checkData = str_replace($bname, '', $checkData);
		$checkData = str_replace($iname, '', $checkData);
		$checkData = ereg_replace("\r|\n","",$checkData);
		$checkData = mb_convert_encoding($checkData, 'UTF-8', $encoding);
		if ($checkData && !preg_match('/[\x80-\xff]/', $checkData)) {
			$checkType = array(
							   'comment'   => _REJECT_ASCII_COMMENT,
							   'trackback' => _REJECT_ASCII_TRACKBACK,
							  );
			$info      = _REJECT_ASCII_INFOHEAD . $item_id . $checkType[$type];
			if (_CHARSET != 'UTF-8') {
				mb_convert_encoding($info, _CHARSET, 'UTF-8');
			}
//			ACTIONLOG :: add(INFO, 'RejectASCII: ' . $info . shorten(strip_tags($checkData), 50, '...') . ')');
			$data['spamcheck']['result'] = TRUE;
			if ($type == 'trackback' && $this->getBlogOption($bid, 'deltb') == 'yes') {
				header("Location: " . createItemLink($item_id));
				exit();
			}
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function event_ValidateForm(&$data) {
		global $DIR_PLUGINS, $member;
		if (getNucleusVersion() >= '330' || $data['type'] != 'comment') {
			return TRUE;
		}
		$item_id = intval($data['comment']['itemid']);
		$blog_id = intval(getBlogIDFromItemID($item_id));

		if ($this->getBlogOption($blog_id, 'hook') == 'no') {
			return TRUE;
		}

		if ($member->isLoggedIn()) {
			return TRUE;
		}

		$spamcheck = array(
						   'type' => 'comment',
						   'id'   => $item_id,
						   'user' => $data['comment']['user'],
						   'body' => $data['comment']['body'],
						  );

		$param     = array(
						   'spamcheck' => &$spamcheck
						  );
		$this->event_SpamCheck($param);

		if (isset($spamcheck['result']) && $spamcheck['result'] == TRUE) {
			$data['error'] = -1;
			header("Location: " . createItemLink($item_id));
			exit();
		}

		return true;
	}

	function _detect_encoding($string)
	{
		if (preg_match("/;\s*charset=([^\n]+)/is", serverVar("CONTENT_TYPE"), $regs)) {
			$encoding =  strtoupper(trim($regs[1]));
		} else {
			$encoding = '';
		}
		$mbstrInput = strtolower(ini_get("mbstring.http_input"));
		if (!empty($encoding) && (mb_http_input('P') == '' || $mbstrInput == 'pass')) {
			return $encoding;
		} else {
			if (_CHARSET == 'UTF-8') {
				$encChars = 'UTF-8,EUC-JP,SJIS,ISO-8859-1,ASCII,JIS';
			} else {
				$encChars = 'EUC-JP,UTF-8,SJIS,ISO-8859-1,ASCII,JIS';
			}
			$encoding = mb_detect_encoding($string, $encChars);
		}
		return ($encoding) ? $encoding : 'ISO-8859-1';
	}

}

