<?php
// vim: tabstop=2:shiftwidth=2

/**
 * AsyncHTTP.php ($Revision: 1.2 $)
 *
 * by hsur ( http://blog.cles.jp/np_cles )
 * $Id: AsyncHTTP.php,v 1.2 2007-09-30 13:39:44 hsur Exp $
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

/* Examples
 * 
 * $ahttp = new cles_AsyncHTTP();
 * 
 * $reqestId[] = $http->setRequest('http://example.com/url1','GET');
 * $reqestId[] = $http->setRequest('http://example.com/url2','GET');
 * 
 * $response = $http->getResponses();
 */

define('CLES_ASYNCHTTP_GETBYTES', 8192);
define('CLES_ASYNCHTTP_INPROGRESS', 115);
define('CLES_ASYNCHTTP_TIMEOUT', 110);

class cles_AsyncHTTP {
	var $asyncMode;
	var $timeout = 120;
	var $userAgent = 'CLES AsyncHTTP Lib';

	var $_requests;
	var $_sockets;
	var $_responses;
	var $_errornos;
	var $_errorstrs;
	
	var $_debug = false;
	var $_debugMsg = '';
	
	function _getTimeStamp(){
		list($usec, $sec) = explode(" ", microtime());
		return $timestamp = date('Y/m/d H:i:s').substr($usec,1);	
	}
	
	function _log($msg){
		$m = $this->_getTimeStamp().':'.$msg."\n";
		$this->_debugMsg .= $m;
		//echo $msg;
	}
	
	function cles_AsyncHTTP(){
		$this->init();
	}
	
	function init() {
		$this->_requests = array();
		$this->_sockets = array();
		$this->_responses = array();
		$this->_errornos = array();
		$this->_errorstrs = array();
		
		$this->asyncMode = function_exists('socket_create') ? true : false;
	}

	function setRequest($url, $method = "", $headers = "", $post = array ("")){
		$this->_requests[] = func_get_args();
		return count($this->_requests) - 1;
	}
	
	function getResponses(){
		$this->_startedTimestamp = $this->_getTimeStamp();

		if( $this->asyncMode )
			$this->_sendAsync();
		else
			$this->_sendSync();
		
		foreach( $this->_responses as $id => $response){
			if( $this->_errornos[$id] !== 0 ){
				unset($this->_responses[$id]);
				continue;
			}
			
			list($header, $body) = split("\r\n\r\n", $response, 2);
			preg_match("/HTTP\/1\.[0-1] ([0-9]+) ([^\r\n]*)\r?\n/", $header, $httpresp);
			$respCd = $httpresp[1];
			$respMsg = $httpresp[2];
	
			if( $respCd != '200' ){
				$this->_errornos[$id] = -1;
				$this->_errorstrs[$id] = 'HTTP Error: '."[$respCd] (".$this->_requests[$id][0].") $respMsg";
				unset($this->_responses[$id]);
			} else {
				$this->_responses[$id] = $body;
			}
		}

		$this->_finishedTimestamp = $this->_getTimeStamp();
		return $this->_responses;
	}
	
	function isError($id){
		if( $this->_errornos[$id] === 0 )
			return false;
		else
			return true;
	}
	
	function getErrorNo($id){
		return $this->_errornos[$id];
	}
	
	function getError($id){
		return $this->_errorstrs[$id];
	}
	
	function _sendAsync(){
		$this->_debug && $this->_log('Using async mode.');
		$expired = time() + $this->timeout;

		// connect
		foreach ($this->_requests as $id => $request) {
			$url = parse_url($request[0]);
			$port = ($url['port'] ? $url['port'] : 80);

			$this->_debug && $this->_log('Open async connection (id:'.$id.')');
			$s = $this->_async_connect($url['host'], $port, $this->_errornos[$id], $this->_errorstrs[$id], $this->timeout);
			if ($s) {
				$this->_sockets[$id] = $s;
				$this->_responses[$id] = '';
			} else {
				$this->_errornos[$id] = -1;
				$this->_errorstrs[$id] = 'Connection Failed '.__LINE__;
			}
		}
		
		// send and recieve
		while (count($this->_sockets)) {
			$read = $write = $this->_sockets;
			$e = null;

			$timeout = $expired - time();
			$timeout = ($timeout < 0 ) ? 0 : $timeout;
			
			$this->_debug && $this->_log('socket_select (timeout:'.$timeout.')');
			$n = socket_select($read, $write, $e, $timeout );
			
			if( $n ){
				foreach ($write as $w) {
					$id = array_search($w, $this->_sockets);
					$this->_debug && $this->_log('Request send (id:'.$id.')');
					socket_write($w, $this->_makePayload($id));
					socket_shutdown($w, 1);
				}
				foreach ($read as $r) {
					$id = array_search($r, $this->_sockets);
					$data = socket_read($r, CLES_ASYNCHTTP_GETBYTES);
					$this->_debug && $this->_log('Response recieved (id:'.$id.', length:'.strlen($data).')');
					if (strlen($data) == 0) {
						if ($this->_errornos[$id] == CLES_ASYNCHTTP_INPROGRESS) {
							$this->_errornos[$id] = -1;
							$this->_errorstrs[$id] = 'Connection Failed'.__LINE__;
						}
						socket_close($r);
						unset($this->_sockets[$id]);
						$this->_debug && $this->_log('Connection closed (id:'.$id.')');
					} else {
						$this->_errornos[$id] = 0;
						$this->_errorstrs[$id] = '';
						$this->_responses[$id] .= $data;
					}
				}
			} else {
				foreach ($this->_sockets as $id => $s) {
					$this->_debug && $this->_log('Timeout (id:'.$id.')');
					$this->_errornos[$id] = CLES_ASYNCHTTP_TIMEOUT;
					$this->_errorstrs[$id] = socket_strerror(CLES_ASYNCHTTP_TIMEOUT);
					socket_close($s);
					unset($this->_sockets[$id]);
				}
				break;
			}
		}
	}
	
	function _sendSync(){
		$this->_debug && $this->_log('Using sync mode.');
		$expired = time() + $this->timeout;

		foreach ($this->_requests as $id => $request) {
			$url = parse_url($request[0]);
			$port = ($url['port'] ? $url['port'] : 80);

			$timeout = $expired - time();
			$timeout = ($timeout < 1 ) ? 1 : $timeout;
			$s = fsockopen($url['host'], $port, $this->_errornos[$id], $this->_errorstrs[$id], $timeout);
			stream_set_timeout($s, $timeout);
			if ($s) {
				$this->_responses[$id] = '';
				$this->_debug && $this->_log('Request send (id:'.$id.')');
				fputs($s, $this->_makePayload($id));
				while (!feof($s)) {
					$data = fgets($s, CLES_ASYNCHTTP_GETBYTES);
					$this->_debug && $this->_log('Response recieved (id:'.$id.', length:'.strlen($data).')');
					$this->_responses[$id] .= $data;
				}
				$this->_debug && $this->_log('Connection closed (id:'.$id.')');
				fclose($s);
			} else {
				$this->_errornos[$id] = -1;
				$this->_errorstrs[$id] = 'Connection Failed '.__LINE__;
			}
		}
	}
	
	function _makePayload($id){
		$params = $this->_requests[$id];
		$url = $params[0];
		$method =  (strtoupper($params[1]) == 'POST') ? 'POST' : 'GET';
		$headers = $params[2];
		$post = $params[3];
		
		$url = parse_url($url);
		if (isset ($url['query'])) {
			$url['query'] = "?".$url['query'];
		} else {
			$url['query'] = "";
		}

		if (!isset ($url['port']))
			$url['port'] = 80;

		$request = $method.' '.$url['path'].$url['query']." HTTP/1.0\r\n";
		$request .= ( $url['port'] == 80 )?
			"Host: " . $url['host'] . "\r\n" :
			"Host: " . $url['host'] . ':' . $url['port'] . "\r\n";
		$request .= 'User-Agent: '.$this->userAgent."\r\n";
		if (isset ($url['user']) && isset ($url['pass'])) {
			$request .= "Authorization: Basic ".base64_encode($url['user'].":".$url['pass'])."\r\n";
		}
		$request .= $headers;

		if( $method == "POST" ){
			$postdata = array();
			while (list ($name, $value) = each($post)) {
				$postdata[] = $name."=".urlencode($value);
			}
			$postdata = implode("&", $postdata);
			$request .= "Connection: Close\r\n";
			$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$request .= "Content-Length: ".strlen($postdata)."\r\n";
			$request .= "\r\n";
			$request .= $postdata;
		} else {
			$request .= "\r\n";
		}
		
		return $request;
	}

	function _async_connect($host, $port, &$errno, &$errstr, $timeout) {
		$ip = gethostbyname($host);
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (socket_set_nonblock($socket)) {
			$r = @socket_connect($socket, $ip, $port);
			if ($r || socket_last_error() == CLES_ASYNCHTTP_INPROGRESS) {
				return $socket;
			}
		}

		$errno = socket_last_error($socket);
		$errstr = socket_strerror($errno);
		socket_close($socket);
		return false;
	}
}
