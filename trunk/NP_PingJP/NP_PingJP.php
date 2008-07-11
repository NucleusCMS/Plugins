<?php
/**
  *
  * Send weblog updates ping
  *     plugin for NucleusCMS(version 3.31 or lator)
  *     Note: based on NP_Ping v1.5
  * PHP versions 4 and 5
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
  *
  * @author    shizuki
  * @copyright 2007 shizuki
  * @license   http://www.gnu.org/licenses/gpl.txt  GNU GENERAL PUBLIC LICENSE Version 2, June 1991
  * @version   1.61
  * @link      http://shizuki.kinezumi.net/
  *
  * History of NP_Ping
  *   v1.0 - Initial version
  *   v1.1 - Add JustPosted event support
  *   v1.2 - JustPosted event handling in background
  *   v1.3 - pinged variable support
  *   v1.4 - language file support
  *   v1.5 - remove arg1 in exec() call
  *
  * History of NP_PingJP
  *   v1.6  - Modified NP_Ping v1.5
  *          merge NP_SendPing(by Tokitake) code
  *   v1.61 - Merge Asynchronous request code(by hsur)
  *   v1.62 - Add background mode
  *
  * NP_PingJP.php ($Revision: 1.10 $)
  * $Id: NP_PingJP.php,v 1.10 2008-07-11 07:00:55 shizuki Exp $
  */


  /**
   * Require files for Asynchronous request
   */
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require("cles/AsyncHTTP/RawPost.php");

class NP_PingJP extends NucleusPlugin
{

var $ahttp;
var $debug   = false;
var $servers = array(
	array(
		'server' => 'pingomatic',
		'name'   => _PINGJP_PINGOM,
		'addr'   => 'http://rpc.pingomatic.com/',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'weblogs',
		'name'   => _PINGJP_WEBLOGS,
		'addr'   => 'http://rpc.weblogs.com/rpc2',
		'method' => 'weblogUpdates.extendedPing',
	),
	array(
		'server' => 'technorati',
		'name'   => _PINGJP_TECHNOR,
		'addr'   => 'http://rpc.technorati.com/rpc/ping',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'blogrolling',
		'name'   => _PINGJP_BLOGR,
		'addr'   => 'http://rpc.blogrolling.com/pinger/',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'google',
		'name'   => _PINGJP_GOOGLE,
		'addr'   => 'http://blogsearch.google.co.jp/ping/RPC2',
		'method' => 'weblogUpdates.extendedPing',
	),
	array(
		'server' => 'yahoo',
		'name'   => _PINGJP_YAHOO,
		'addr'   => 'http://api.my.yahoo.co.jp/RPC2',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'goo',
		'name'   => _PINGJP_GOO,
		'addr'   => 'http://blog.goo.ne.jp/XMLRPC',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'ask',
		'name'   => _PINGJP_ASK,
		'addr'   => 'http://ping.ask.jp/xmlrpc.m',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'blog360',
		'name'   => _PINGJP_BLOG360,
		'addr'   => 'http://ping.blog360.jp/rpc',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'pingoo',
		'name'   => _PINGJP_PINGOO,
		'addr'   => 'http://pingoo.jp/ping',
		'method' => 'weblogUpdates.ping',
	),
	array(
		'server' => 'blogs',
		'name'   => _PINGJP_BLOGS,
		'addr'   => 'http://ping.blo.gs/',
		'method' => 'weblogUpdates.extendedPing',
	),
	array(
		'server' => 'weblogues',
		'name'   => _PINGJP_WEBLOGUES,
		'addr'   => 'http://www.weblogues.com/RPC/',
		'method' => 'weblogUpdates.extendedPing',
	),
	array(
		'server' => 'bloggde',
		'name'   => _PINGJP_BLOGGDE,
		'addr'   => 'http://xmlrpc.blogg.de/ping',
		'method' => 'bloggUpdates.ping',
	),
);

	// {{{ function getName()

	/**
	 * Name of the plugin
	 *
	 * @return string
	 *     The name easy to understand for man of the plugin
	 */
	function getName()
	{
		return 'Ping';
	}

	// }}}
	// {{{ function getAuthor()

	/**
	 * Author of the plugin
	 *
	 * @return string
	 *     The name of the plugin author
	 */
	function getAuthor()
	{
		return 'admun (Edmond Hui)+ Tokitake + shizuki';
	}

	// }}}
	// {{{ function getURL()

	/**
	 * URL of the site which can download a plugin
	 *
	 * @return string
	 *     URL of the site which can download a plugin
	 */
	function getURL()
	{
		return 'http://shizuki.kinezumi.net/';
	}

	// }}}
	// {{{ function getVersion()

	/**
	 * Version of the plugin
	 *
	 * @return string
	 *     Version of the plugin
	 */
	function getVersion()
	{
		return '1.62';
	}

	// }}}
	// {{{ function getMinNucleusVersion()

	/**
	 * Requier NucleusCMS version of the plugin
	 *
	 * @return string
	 *     Requier NucleusCMS version of a plugin
	 */
	function getMinNucleusVersion()
	{
		return '331';
	}

	// }}}
	// {{{ function getDescription()

	/**
	 * Description of the plugin
	 *
	 * @return string
	 *     Description of a plugin
	 */
	function getDescription()
	{
		return _PINGJP_DESC;
	}

	// }}}
	// {{{ function supportsFeature($what)

	/**
	 * Check whether the feature is being supported.
	 *
	 * @param  string
	 *     Feature name
	 * @return boolean
	 */
	function supportsFeature($what)
	{
		switch($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	// }}}
	// {{{ function install()

	/**
	 * Plugin installing action
	 *
	 * @return void
	 */
	function install()
	{
		// Default, http://pingomatic.com
		$this->createBlogOption('pingjp_pingomatic',  _PINGJP_PINGOM,    'yesno',    'yes');
		// http://weblogs.com
		$this->createBlogOption('pingjp_weblogs',     _PINGJP_WEBLOGS,   'yesno',    'no');
		// http://www.technorati.com
		$this->createBlogOption('pingjp_technorati',  _PINGJP_TECHNOR,   'yesno',    'no');
		// http://www.blogrolling.com
		$this->createBlogOption('pingjp_blogrolling', _PINGJP_BLOGR,     'yesno',    'no');
		// http://www.google.com
		$this->createBlogOption('pingjp_google',      _PINGJP_GOOGLE,    'yesno',    'yes');
		// http://www.yahoo.co.jp
		$this->createBlogOption('pingjp_yahoo',       _PINGJP_YAHOO,     'yesno',    'yes');
		// http://www.goo.ne.jp
		$this->createBlogOption('pingjp_goo',         _PINGJP_GOO,       'yesno',    'no');
		// http://ask.jp
		$this->createBlogOption('pingjp_ask',         _PINGJP_ASK,       'yesno',    'no');
		// http://blog360.jp
		$this->createBlogOption('pingjp_blog360',     _PINGJP_BLOG360,   'yesno',    'yes');
		// http://pingoo.jp
		$this->createBlogOption('pingjp_pingoo',      _PINGJP_PINGOO,    'yesno',    'no');
		// http://blo.gs
		$this->createBlogOption('pingjp_blogs',       _PINGJP_BLOGS,     'yesno',    'no');
		// http://weblogues.com/
		$this->createBlogOption('pingjp_weblogues',   _PINGJP_WEBLOGUES, 'yesno',    'no');
		// http://blogg.de
		$this->createBlogOption('pingjp_bloggde',     _PINGJP_BLOGGDE,   'yesno',    'no');
		// other ping server
		$this->createBlogOption('pingjp_otherurl',    _PINGJP_OTHER,     'textarea', '');
		// background ?
		$this->createBlogOption('pingjp_background',  _PINGJP_BG,        'yesno',    'yes');
		// Your blog URL
		$this->createBlogOption('pingjp_updateurl',   _PINGJP_UPDURL,    'text',     '');
		// Your RSS URL
		$this->createBlogOption('pingjp_feedurl',     _PINGJP_UPDFEED,   'text',     '');
	}

	// }}}
	// {{{ function init()

	/**
	 * Plugin initialize action
	 *
	 * @return void
	 */
	function init()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . 'language/' . $language . '.php')) {
			include_once($this->getDirectory() . 'language/' . $language . '.php');
		} else {
			include_once($this->getDirectory() . 'language/english.php');
		}
	}

	// }}}
	// {{{ function getEventList()

	/**
	 * Event list plugin exist
	 *
	 * @return array
	 *     exist events
	 */
	function getEventList()
	{
		return array(
			'SendPing',
			'JustPosted',
//			'EditItemFormExtras',
//			'AdminPrePageHead',
		);
	}

	// }}}
	// {{{ function event_AdminPrePageHead($data)

	/**
	 * Event ITEM updated
	 *
	 * @param  array
	 *     extrahead : reference string
	 *         Extra information
	 *     action    : value string
	 *         executed action or pagetype
	 * @return void
	 */
	function event_AdminPrePageHead($data) {
/*		global $manager;
		if (requestVar('np_pingjp_check') != 1 && $data['action'] == 'sendping') {
			$blogid   = intRequestVar('blogid');
			$redirect = $CONF['AdminURL'] . 'index.php?action=itemlist&blogid=' . $blogid;
			$pattern  = '<meta http-equiv="refresh" content="1; url=.+?/>';
			$data['extrahead'] = preg_replace('|'.$pattern.'|', '', $data['extrahead']);
			redirect($redirect);
		}
*/	}

	// }}}
	// {{{ function event_EditItemFormExtras($data)

	/**
	 * Event display ITEM edit form
	 *     adding plugin specify for ITEM edit form
	 *
	 * @param  array
	 *     blog      : reference object
	 *         BLOG object
	 *     variables : value array
	 *         containing all sorts of information on the item that's being edited
	 *             itemid    : intger
	 *                 item ID
	 *             draft     : intger(boolean)
	 *                 ITEM draft status
	 *                     public : 0
	 *                     draft  : 1
	 *             closed    : intger(boolean)
	 *                 ITEM comments status
	 *                     accept     : 0
	 *                     not accept : 1
	 *             title     : string
	 *                 item title
	 *             body      : string
	 *                 item main text
	 *             more      : string
	 *                 item extended text
	 *             author    : string
	 *                 item author
	 *             authorid  : intger
	 *                 item author ID
	 *             timestamp : intger
	 *                 item timestamp
	 *             karmapos  : intger
	 *                 item karmapos
	 *             karmaneg  : intger
	 *                 item karmaneg
	 *             catid     : intger
	 *                 item category ID
	 *     itemid    : value intger
	 *         editing item ID
	 * @return void
	 */
	function event_EditItemFormExtras($data)
	{
/*		?>
		<div id="np_pingjp_form" style="display:block">
			<h3>NP_PingJP</h3>
			<p>
				<label for="np_pingjp_check"><?php echo _PINGJP_FORMEXTRA ?>:</label>
				<input type="checkbox" value="1" id="np_pingjp_check" name="np_pingjp_check" />
			</p>
		</div>
		<?php
*/	}

	// }}}
	// {{{ function event_JustPosted($data)

	/**
	 * Event ITEM timstamp as now
	 *     send update ping or etc.
	 *
	 * @param  array
	 *     blogid : value intger
	 *         blog ID
	 *     pinged : reference boolean
	 *         Update ping completed as true
	 * @return void
	 */
	function event_JustPosted($data)
	{
		if ($data['pinged'] == true) {
			return;
		}
		if ($this->getBlogOption($data['blogid'], 'pingjp_background') == "yes") {
//			$directory = $this->getDirectory();
//			// TODO: Check
//			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//				system("start /b php " . $directory . "ping.php " . $data['blogid'] . " > nul"  );
//			} else {
//				exec("php " . $directory . "ping.php " . $data['blogid'] . " > /dev/null &");
//			}
			register_shutdown_function(array($this, 'sendPing'), $data['blogid'], 2);
		} else {
			$this->sendPings($data['blogid'], 1);
		}
		$data['pinged'] = true;
	}

	// }}}
	// {{{ function event_SendPing($data)

	/**
	 * Event send weblog updates ping
	 *     when add ITEM
	 *
	 * @param  array
	 *     blogid : value intger
	 *         blog ID
	 * @return void
	 */
	function event_SendPing($data)
	{
		$this->sendPing($data['blogid']);
	}

	// }}}
	// {{{ function sendPing($myBlogId, $background = 0)

	/**
	 * Setting ping servers
	 *
	 * @param  intger
	 *     blog ID
	 * @param  intger
	 *     Send ping mode
	 *         0 : display mode
	 *         1 : non display mode
	 *         2 : background mode
	 * @return void
	 */
	function sendPing($bid, $background = 0)
	{
		$targets = $this->getPingingServers($bid);

		$this->ahttp            = new cles_AsyncHTTP_RawPost();
		$this->ahttp->userAgent = "Nucleus(NP_PingJP Plugin)";
		$this->ahttp->timeout   = 15;

		$header   = "Accept-Charset: UTF-8\r\nContent-Type: text/xml\r\n";
		$messages = array();
		if ($background == 1) {
			$logMsg = 'NP_PingJP: ' . _PINGJP_NON_DISPLAY;
			ACTIONLOG::add(INFO, $logMsg);
		} elseif ($background == 2) {
			$logMsg = 'NP_PingJP: ' . _PINGJP_BACKGROUND;
			ACTIONLOG::add(INFO, $logMsg);
		}
		foreach ($targets as $target) {
			$res = $this->sendUpdatePing($bid, $target, $header);
			if ($background == 0) {
				echo _PINGJP_PINGING . $target['name'] . ':<br />';
			}
			$messages[$res[0]] =& $res[1];
		}
		$responses = $this->ahttp->getResponses();
		foreach ($messages as $id => $message) {
			$target = $targets[$id]['name'];
			if (isset($responses[$id])) {
				$response = $message->parseResponse($responses[$id]);
				$results  = $this->processPingResult($response);
			} else {
				$message  = $this->ahttp->getErrorNo($id);
				$errorId  = $this->ahttp->getError($id);
				$response = $this->ahttp->_responses[$id];
				if ($errorId == 110) {
					$results['message'] = "Connection timeout($errorId)";
				} elseif (strpos($message, 'HTTP Error') !== false) {
					preg_match("/.*\[([0-9]{3})\] \(.*\) (.*)$/", $message, $matchies);
					if ($matchies[1]) {
						$rescode = $matchies[1];
						$rescstr = $matchies[2];
						$results['message'] = "HTTP Error: $target $rescode $rescstr";
					} else {
						$results['message'] = "HTTP Error: $target Response Null";
					}
				} else {
					$results['message'] = "Unknown Error: $errorId: $message, $response";
				}
				$results['error'] = true;
			}
			$logMsg = $target . ' : ' . $results['message'];
			if ($results['error']) {
				ACTIONLOG::add(WARNING, 'NP_PingJP Error: ' . $logMsg);
			} elseif ($this->debug) {
				ACTIONLOG::add(INFO, 'NP_PingJP Pinged: ' . $logMsg);
			}
			if ($background == 0) {
				echo $logMsg . "<br />\n";
			}
		}
	}

	// }}}
	// {{{ function sendUpdatePing($myBlogId, $pingServer, $header)

	/**
	 * Setting ping message
	 *
	 * @param  intger
	 *     blog ID
	 * @param  array
	 *     ping server settigs
	 *     name : name of ping server
	 *     host : URI of ping server
	 *     meth : method of ping server accept
	 * @param string
	 *     http request header
	 * @return void
	 */
	function sendUpdatePing($bid, $server, $header)
	{
		global $manager;
		if (!class_exists('xmlrpcmsg')) {
			global $DIR_LIBS;
			include($DIR_LIBS . 'xmlrpc.inc.php');
			$GLOBALS['xmlrpc_internalencoding'] = mb_internal_encoding();
		}
		$b    =& $manager->getBlog($bid);
		$name =  $b->getName();
		$burl =  $this->getBlogOption($bid, 'pingjp_updateurl');
		if (!$burl) {
			$burl = $b->getURL();
		}
		if (_CHARSET != 'UTF-8') {
			mb_convert_encoding($name, 'UTF-8', _CHARSET);
		}
		$data[1] = new xmlrpcval($name);
		$data[2] = new xmlrpcval($burl);
		if ($server['method'] == 'weblogUpdates.extendedPing') {
			$feedURL = $this->getBlogOption($myBlogid, 'pingjp_feedurl');
			if (!$feedURL) {
				global $CONF;
				$feedURL = $CONF['IndexURL'] . 'xml-rss2.php?blogid=' . $bid;
			}
			$data[3] = new xmlrpcval($burl);
			$data[4] = new xmlrpcval($feedURL);
		}
		$message  = new xmlrpcmsg($server['method'], $data);
		$reqestId = $this->ahttp->setRequest($server['addr'], 'POST', $header, $message->serialize());
		return array($reqestId, &$message);
	}

	// }}}
	// {{{ function processPingResult($response)

	/**
	 * Process pinging result
	 *
	 * @param  object
	 *     weblog updates ping response
	 * @return array
	 *     error   : boolean
	 *               ping response status
	 *     message : string
	 *               ping response messages
	 */
	function processPingResult($response)
	{
		global $php_errormsg;
		if (($response == 0) && ($response->errno || $response->errstring)) {
			$ret['error']   = true;
			$ret['message'] = _PINGJP_ERROR
							. ' ' . $response->errno
							. ' : ' . $response->errstring;
		} elseif (($response == 0) && ($php_errormsg)) {
			$ret['error']   = true;
			$ret['message'] = _PINGJP_PHP_ERROR . ' ' . $php_errormsg;
		} elseif ($response == 0) {
			$ret['error']   = true;
			$ret['message'] = _PINGJP_PHP_PING_ERROR;
		} elseif ($response->faultCode() != 0) {
			$ret['error']   = true;
			$ret['message'] = _PINGJP_ERROR . ' : ' . $response->faultString();
		} else {
			$struct = $response->value();	// get response struct
			// get values
			$flerror = $struct->structmem('flerror');
			$flerror = $flerror->scalarval();
			$message = $struct->structmem('message');
			$message = $message->scalarval();
			if ($flerror != 0) {
				$ret['error']   = true;
				$ret['message'] = _PINGJP_ERROR . ' (flerror=1): ' . $message;
			} else {
				$ret['error']   = false;
				$ret['message'] = _PINGJP_SUCCESS . ' : ' . $message;
			}
		}
		return $ret;
	}

	// }}}
	// {{{ function getPingingServers($bid)

	/**
	 * Process pinging result
	 *
	 * @param  intger
	 *     blog ID
	 * @return array
	 *     targets : array
	 *               server : string
	 *                        ping server
	 *               name   : string
	 *                        ping server name
	 *               host   : string
	 *                        server host addr.
	 *               method : string
	 *                        update ping method
	 */
	function gerPingServers($bid)
	{
		$servers = $this->servers;
		$targets = array();
		foreach ($servers as $key => $server) {
			$info = $this->getBlogOption(intval($bid), 'pingjp_' . $server['server']);
			if ($info != 'no') {
				$targets[] = $server;
			}
		}
		$others  = $this->getBlogOption($myBlogId, 'pingjp_otherurl');
		if ($others != '') {
			$servers = preg_split("/[\s,]+/", $others);
			foreach ($servers as $server) {
				if (strpos($server, ',')) {
					list($url, $method) = explode(',', $server);
					$parsed = parse_url($url);
					if ($method == 'ex') {
						$method = 'weblogUpdates.extendedPing';
					}
				} else {
					$parsed = parse_url($server);
					$method = 'weblogUpdates.ping';
				}
				$targets[]['server'] = $parsed['host'];
				$targets[]['name']   = $parsed['host'];
				$targets[]['addr']   = $server;
				$targets[]['method'] = $method;
			}
		}
		return $targets;
	}
}
