<?php
// vim: tabstop=2:shiftwidth=2

/**
 * AsyncHTTP.php ($Revision: 1.1 $)
 *
 * by hsur ( http://blog.cles.jp/np_cles )
 * $Id: RawPost.php,v 1.1 2007-10-28 15:57:41 shizuki Exp $
 */

/*
 * Copyright (C) 2007 CLES. All rights reserved.
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

require_once 'cles/AsyncHTTP.php';

class cles_AsyncHTTP_RawPost extends cles_AsyncHTTP {
	var $userAgent = 'CLES AsyncHTTP(RawPost) Lib';
	
	function cles_AsyncHTTP_RawPost(){
		$this->init();
	}
	
	function _makePayload($id){
		$params = $this->_requests[$id];
		$url = $params[0];
		$method =  'POST';
		$headers = $params[2];
		$rawpost = $params[3];
		
		$url = parse_url($url);
		if (isset ($url['query'])) {
			$url['query'] = "?".$url['query'];
		} else {
			$url['query'] = "";
		}

		if (!isset ($url['port']))
			$url['port'] = 80;

		$request = $method.' '.$url['path'].$url['query']." HTTP/1.1\r\n";
		$request .= ( $url['port'] == 80 )?
			"Host: " . $url['host'] . "\r\n" :
			"Host: " . $url['host'] . ':' . $url['port'] . "\r\n";
		$request .= "Connection: Close\r\n";
		$request .= 'User-Agent: '.$this->userAgent."\r\n";
		if (isset ($url['user']) && isset ($url['pass'])) {
			$request .= "Authorization: Basic ".base64_encode($url['user'].":".$url['pass'])."\r\n";
		}
		$request .= $headers;

		$request .= "Content-Length: ".strlen($rawpost)."\r\n";
		$request .= "\r\n";
		$request .= $rawpost;
		
		return $request;
	}
}