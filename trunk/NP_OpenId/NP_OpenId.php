<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_OpenId ($Revision: 1.1 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: NP_OpenId.php,v 1.1 2008-02-03 13:11:23 hsur Exp $
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
// ParanoidHTTPFetcher bug?
define('Auth_Yadis_CURL_OVERRIDE', '1');

// constants
define('NP_OPENID_COOKIE', 'EXTAUTH');

// libs
require(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once "Auth/OpenID/Consumer.php";
require_once "cles/SQLStoreForNucleus.php";
require_once "Auth/OpenID/SReg.php";
require_once "Auth/OpenID/PAPE.php";

class NP_OpenId extends NucleusPlugin {

	function getName() {
		return 'OpenId';
	}
	function getAuthor() {
		return 'hsur';
	}
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/21';
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
		return array ('FormExtra', 'ValidateForm', 'PreAddComment', 'PostAddComment', 'PostDeleteComment', 'ExternalAuth', 'Logout', 'LoginSuccess');
	}
	function getTableList() {
		return array(
			sql_table('plugin_openid'),
			sql_table('plugin_openid_comment'),
			sql_table('plugin_openid_profile'),
			sql_table('plugin_openid_assc'),
			sql_table('plugin_openid_nonce')
		);
	}
	function getDescription() {
		return '[$Revision: 1.1 $]<br />Adds OpenID authentication to anonymous comment, to prevent robots from spamming.';
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
	
	function init() {

/* 
		// For DEBUG
		require_once 'Auth/OpenID/FileStore.php';
		$store_path = "/tmp/_php_consumer_test";
		@mkdir($store_path);
		$this->store = new Auth_OpenID_FileStore($store_path);
*/

		$this->store = new cles_SQLStoreForNucleus();
		$this->consumer = new Auth_OpenID_Consumer($this->store);
		$this->loginedUser = null;
		$this->comments = array();
	}

	function install() {
		$this->store->createTables();
		
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_openid') 
			. ' ('
  			. '  cookie varchar(40) NOT NULL default \'\','
  			. '  identity varchar(255) NOT NULL,'
 			. '  sreg text NOT NULL default \'\','
  			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
  			. '  PRIMARY KEY (cookie)'
			. ' );'
        );
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_openid_profile') 
			. ' ('
  			. '  identity varchar(255) NOT NULL,'
  			. '  nick varchar(255) NOT NULL unique default \'\','
  			. '  email varchar(255) ,'
 			. '  sreg text NOT NULL default \'\','
  			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
  			. '  PRIMARY KEY (identity)'
			. ' );'
        );
		sql_query(
			  'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_openid_comment') 
			. ' ('
  			. '  cnumber int(11) NOT NULL,'
  			. '  citem int(11) NOT NULL,'
  			. '  identity varchar(255) NOT NULL default \'\','
			. '  ts datetime NOT NULL default \'0000-00-00 00:00:00\','
 			. '  PRIMARY KEY(cnumber), '
 			. '  INDEX(citem) '
			. ' );'
        );
		
		global $CONF;
		$loginedHtml = "<p>Thanks for signing in(<%identity%>). Now you can comment or send mail. (<a href=\"<%url%>\" rel=\"nofollow\">Sign out</a>/<a href=\"<%profileUpdateUrl%>\" rel=\"nofollow\" target=\"_blank\">Update Profile</a>)\n<script type=\"text/javascript\">\n(function(){\nvar onload_org = window.onload;\nwindow.onload = function(){\nif(onload_org) onload_org();\ndocument.getElementById('nucleus_cf_name').value = '<%nick%> [OpenID]';\ndocument.getElementById('nucleus_cf_name').style.background = '#FFFFCC';\ndocument.getElementById('nucleus_cf_name').readOnly = true;\ndocument.getElementById('nucleus_cf_email').value = '<%email%>';\ndocument.getElementById('nucleus_cf_email').style.background = '#FFFFCC';\ndocument.getElementById('nucleus_cf_email').readOnly = true;\n}\n})()\n</script></p>";
		$notLoginedHtml =  '<p>If you have a OpenID identity, you can sign in to use it here.<br /><form method="post" action="<%url%>">Identity URL: <input style="padding-left:20px; background:url('.$CONF['PluginURL'].'openid/openid.png) no-repeat left center #FFF;" type="text" name="openid_identifier" value="" size="50"/><input type="submit" value="Sign in" /></form></p>';
		$templateHtml =  '<a href="<%identity%>" rel="nofollow" title="OpenID Profile"><img src="' . $CONF['PluginURL'] . 'openid/openid.png" border="0"/><%identity%></a>';
		$templateAdminHtml = '<a href="<%identity%>" rel="nofollow" title="OpenID Profile"><img src="' . $CONF['PluginURL'] . 'openid/openid.png" border="0"/><%identity%></a>';

		$this->createOption('permitComment', 'Permit comments w/o login?', 'yesno', 'yes', '');
		$this->createOption('permitMail', 'Permit mail w/o login?', 'yesno', 'yes', '');
		
		$this->createOption('LoginedHtml', 'Logined Template', 'textarea', $loginedHtml, '');
		$this->createOption('NotLoginedHtml', 'Not Logined Template', 'textarea', $notLoginedHtml, '');
		$this->createOption('TemplateHtml', 'Template html', 'textarea', $templateHtml, '');
		$this->createOption('TemplateAdminHtml', 'Template admin html', 'textarea', $templateAdminHtml, '');
		
		$this->createOption('CommentFormError', 'Error message (comment)', 'text', 'To submit comment, you need to sign-in to OpenID.', '');
		$this->createOption('MemberMailError', 'Error message  (mail form)', 'text', 'To send email, you need to sign-in to OpenID.', '');
		
		$this->createOption('dropdb', 'Erase  on uninstall?', 'yesno', 'no', '');
		$this->createOption('debug', 'Debug mode ?', 'yesno', 'no');
		
		$this->createOption('enableLinkedWith', 'Enable local account linked with OpenID account ? ', 'yesno', 'no');
		$this->createMemberOption('linkedWith', 'Linked with following account (hash value)', 'text', '');
	}

	function unInstall() {
		if ($this->getOption('dropdb') == 'yes'){
			sql_query('DROP TABLE '.sql_table('plugin_openid'));
			sql_query('DROP TABLE '.sql_table('plugin_openid_profile'));
			sql_query('DROP TABLE '.sql_table('plugin_openid_comment'));
			sql_query('DROP TABLE '.sql_table('plugin_openid_assc'));
			sql_query('DROP TABLE '.sql_table('plugin_openid_nonce'));
		}
	}
	function doAction($type) {
		switch ($type) {
			case 'verify' :
				if( $this->login() ){
					$this->_info('Authentication success: identity=' . $this->loginedUser['identity']);
					$this->_doLoginLocal($this->loginedUser['identity']);
					$url = preg_replace('/action=logout&?/','', requestVar('return_url'));
					$this->_redirect( $url );
				} else {
					$this->_info('Authentication failure');
					return 'Authentication failure';
				}
				break;
				
			case 'doauth' :
				return $this->doAuth( requestVar('openid_identifier'), requestVar('return_url') );
				break;
				
			case 'rd' :
				$this->logout();
				$this->_redirect( requestVar('url') );
				break;
				
			case 'updateProfile':
				if( $this->isLogined() ){
					$profile = array();
					$profile['nick'] = requestVar('nick');
					$profile['email'] = requestVar('email');

					if( requestVar('submit') )	
						$this->_doUpdateProfile($profile);
				} else {
					$this->_info('Authentication failure');
					return 'Authentication failure';
				}
				break;
			default :
				return 'Unknown action: '.$type;
		}
		return '';
	}
	
	function doAuth($identifier, $returnUrl){
		global $CONF;
		if( !$identifier ) return 'Missing OpenID identifier.';

		$auth_request = $this->consumer->begin($identifier);
		if (!$auth_request) {
			$this->reason = $auth_request;
			return "OpenID identifier is invalid.";
		}
		$sreg_request = Auth_OpenID_SRegRequest::build(
			// Required
			array('nickname'),
			// Optional
			array('fullname', 'email')
		);
		$auth_request->addExtension($sreg_request);
		
		$returnTo = $CONF['PluginURL'].'openid/rd.php?action=verify&return_url='.urlencode($returnUrl);			
		$trustRoot = $CONF['IndexURL'];
		
		if ($auth_request->shouldSendRedirect()) {
			$redirect_url = $auth_request->redirectURL($trustRoot, $returnTo);

			if (Auth_OpenID::isFailure($redirect_url)) {
				return "Could not redirect to server: " . $redirect_url->message;
			} else {
				header("Location: ". $redirect_url);
				$this->redirectTo = $redirect_url;
			}
		} else {
			$form_id = 'openid_message';
			$form_html = $auth_request->formMarkup($trustRoot, $returnTo,
			false, array('id' => $form_id));

			if (Auth_OpenID::isFailure($form_html)) {
				return "Could not redirect to server: " . $form_html->message;
			} else {
				$page_contents = array(
				"<html><head><title>",
				"OpenID transaction in progress",
				"</title></head>",
				"<body onload='document.getElementById(\"".$form_id."\").submit()'>",
				$form_html,
				"</body></html>");
				print implode("\n", $page_contents);
				exit;
			}
		}
	}
	
	function _doUpdateProfile($profile){
		$query = sprintf('REPLACE INTO ' . sql_table('plugin_openid_profile') 
				. ' ( identity, nick, email, ts ) '
				. " values('%s', '%s', '%s', now())",
				 mysql_real_escape_string( $this->loginedUser['identity']  ),
				 mysql_real_escape_string( $profile['nick']  ),
				 mysql_real_escape_string( $profile['email'] )
		);
		sql_query($query);

		$this->loginedUser['nick'] = $profile['nick'];
		$this->loginedUser['email'] = $profile['email'];

		setcookie($CONF['CookiePrefix'] . 'comment_user', $this->loginedUser['nick'], 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_email', $this->loginedUser['email'], 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
	}
	
	function _doLoginLocal($name){
		if( $this->getOption('enableLinkedWith') != 'yes' ) return false;
		
		$linkedWith = $this->getAllMemberOptions('linkedWith');
		ksort($linkedWith, SORT_NUMERIC);
		
		$localId = -1;
		foreach( $linkedWith as $id => $accountList ){
			$accounts = explode(",", $accountList);
			$accounts = array_map("trim", $accounts);
			
			foreach( $accounts as $account ){
				if( $account == '*' || $account == $name ){
					$localId = $id;
					break 2;
				}
			}
		}
		if( $localId == -1 ) return false;
		
		global $manager, $CONF, $member;
		$member =& MEMBER::createFromID($localId);
		$member->loggedin = 1;
		
		$member->newCookieKey();
		$member->setCookies(0);
		if ( isset($CONF['secureCookieKey']) ) {
			$member->setCookieKey(md5($member->getCookieKey().$CONF['secureCookieKeyIP']));
			$member->write();
		}
		$manager->notify('LoginSuccess', array('member' => &$member) );
		
		$this->_info('Login local account :' . $member->getDisplayName() );
		ACTIONLOG::add(INFO, 'Login successful for '.$member->getDisplayName().' (sharedpc=0, OpenId)');
		return true;
	}
	
	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'OpenId: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'OpenId: '.$msg);
	}

	function _redirect($url){
		header('Location: '.$url);
	}
	
	function _generateKey(){
		mt_srand( (double) microtime() * 1000000);
		return md5(uniqid(mt_rand()));
	}
	
	function isLogined(){
		global $CONF;
		if( $this->loginedUser ) return true;
		
		$cookie = cookieVar($CONF['CookiePrefix'] . NP_OPENID_AUTH_COOKIE);
		if( ! $cookie ) return false;
		
		$query = sprintf('SELECT a.cookie as cookie, a.identity as identity, a.sreg as sreg, a.ts as ts, p.nick as nick, p.email as email FROM ' . sql_table('plugin_openid') . ' a '
				. ' LEFT OUTER JOIN ' . sql_table('plugin_openid_profile') . ' p ON a.identity = p.identity '
				. " where a.cookie = '%s' and a.ts > date_sub( now(), interval 1 day)"
				, mysql_real_escape_string( trim($cookie) )
		);
		$res = sql_query($query);
		if( @mysql_num_rows($res) > 0) {
			$this->loginedUser = mysql_fetch_assoc($res);
			$this->loginedUser = array_merge($this->loginedUser, unserialize($this->loginedUser['sreg']));
			return true;
		}
		return false;
	}
	
	function login(){
		global $CONF;
		//$return_url = $CONF['PluginURL'].'openid/rd.php?action=verify&return_url='.urlencode(requestVar('return_url'));
		$return_url = $CONF['PluginURL'].'openid/rd.php';
		$response = $this->consumer->complete( $return_url );
		if ($response->status == Auth_OpenID_CANCEL) {
			$this->message = 'Verification cancelled.';
			return false;
		} else if ($response->status == Auth_OpenID_FAILURE) {
			$this->message = "OpenID authentication failed: " . $response->message;
			$this->reason = $response;
			return false;
		} else if ($response->status != Auth_OpenID_SUCCESS) {
			$this->message = 'Unknown status: ' . $response->status;
			return false;
		}
		
		// Auth_OpenID_SUCCESS
		
		$identity = $response->getDisplayIdentifier();
		$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
		$sreg = $sreg_resp->contents(); // assoc
			
		$ts = time();
		$cookie = $this->_generateKey();
		$query = sprintf('REPLACE INTO ' . sql_table('plugin_openid') 
				. ' ( cookie, identity, sreg, ts ) '
				. " values('%s', '%s', '%s', '%s')",
				 mysql_real_escape_string( $cookie ),
				 mysql_real_escape_string( $identity ),
				 mysql_real_escape_string( serialize($sreg) ),
				 mysql_real_escape_string( date("Y/m/d H:i:s", $ts ) )
		);
		sql_query($query);
		
		$query = sprintf('SELECT a.cookie as cookie, a.identity as identity, a.sreg as sreg, a.ts as ts, p.nick as nick, p.email as email  FROM ' . sql_table('plugin_openid') . ' a '
				. ' LEFT OUTER JOIN ' . sql_table('plugin_openid_profile') . ' p ON a.identity = p.identity '
				. " where a.cookie = '%s' and a.ts > date_sub( now(), interval 1 day)"
				, mysql_real_escape_string( trim($cookie) )
		);
		$res = sql_query($query);		
		
		if( @mysql_num_rows($res) > 0) {
			$this->loginedUser = mysql_fetch_assoc($res);
			$this->loginedUser = array_merge($this->loginedUser, unserialize($this->loginedUser['sreg']));
			
			setcookie($CONF['CookiePrefix'] . NP_OPENID_AUTH_COOKIE , $cookie, 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			setcookie($CONF['CookiePrefix'] . 'comment_user', $this->loginedUser['nick'], 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			setcookie($CONF['CookiePrefix'] . 'comment_email', $this->loginedUser['email'], 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
			return true;
		}
		
		return false;
	}
		
	function logout(){
		global $CONF;
		$this->loginedUser = null;
		setcookie($CONF['CookiePrefix'] . NP_OPENID_AUTH_COOKIE, '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_user', '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		setcookie($CONF['CookiePrefix'] . 'comment_email', '', 0, $CONF['CookiePath'], $CONF['CookieDomain'], $CONF['CookieSecure']);
		return true;
	}
	
	function event_ExternalAuth(&$data){
		if( $data['externalauth']['source'] == $this->getName() ) return;
        if( isset($data['externalauth']['result']) && $data['externalauth']['result'] == true ){
            return;
        }
		
		if( $this->isLogined() ){
			$data['externalauth']['result'] = true;
			$data['externalauth']['plugin'] = $this->getName();
		}
	}

	function doSkinVar($skinType, $type = "") {
		global $CONF, $manager, $member;
		if($skinType != 'item') return;
		if( $member->isLoggedIn() ) return;
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		
		$aVars = array();
		if( $this->isLogined() ){
			// Logined
			$return_url = $CONF['PluginURL'] . 'openid/rd.php?action=rd&url='
						. urlencode( 'http://'.serverVar("HTTP_HOST") .serverVar("REQUEST_URI") );			
			$aVars['url'] = htmlspecialchars( $return_url, ENT_QUOTES );
			$aVars['nick'] = $this->loginedUser['nick'];
			$aVars['email'] = $this->loginedUser['email'];
			$aVars['ts'] = $this->loginedUser['ts'];
			$aVars['profileUpdateUrl'] = $CONF['PluginURL'] . 'openid/profile.php';
			$aVars['identity'] = $this->loginedUser['identity'];
			
			echo TEMPLATE::fill($this->getOption('LoginedHtml'), $aVars);
		} else {
			// not logined
			$aVars['url'] = $CONF['PluginURL'] . 'openid/rd.php?action=doauth&return_url='
						. urlencode( 'http://'.serverVar("HTTP_HOST") .serverVar("REQUEST_URI") );	

		    echo TEMPLATE::fill($this->getOption('NotLoginedHtml'), $aVars);
		}		
	}
	
	function event_FormExtra(&$data) {
		global $CONF, $manager, $member;
		if( $member->isLoggedIn() ) return;
		
		switch ($data['type']) {
			case 'commentform-notloggedin' :
			case 'membermailform-notloggedin': 
			case 'item': 
				break;
			default :
				return;
		}
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;

		$this->isLogined();
	}

	function event_ValidateForm(&$data) {
		global $manager, $member;
		if( $member->isLoggedIn() ) return;
		
		$externalauth = array ( 'source' => $this->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		
		switch ($data['type']) {
			case 'comment' :
				if( (! $this->isLogined() ) && $this->getOption('permitComment') == 'no' )
					$data['error'] = $this->getOption('CommentFormError');
				break;
			case 'membermail' :
				if( (! $this->isLogined() ) && $this->getOption('permitMail') == 'no' )
					$data['error'] = $this->getOption('MemberMailError');
				break;
			default :
				return;
		}
	}
	
	function event_PreAddComment(&$data) {
		global $member;
		if( $member->isLoggedIn() ) return;
		
		if( ! $this->isLogined() ) return;
		$data['comment']['user'] = $this->loginedUser['nick'].' [OpenID]';
	}
	
	function event_PostAddComment(&$data) {
		global $member;
		if( $member->isLoggedIn() ) return;
		
		if( ! $this->isLogined() ) return;
		global $itemid;
		$query = sprintf('INSERT INTO ' . sql_table('plugin_openid_comment') 
				. '( cnumber, citem, identity, ts ) '
				. "values('%s', '%s', '%s', now() )",
				 mysql_real_escape_string( $data['commentid'] ),
				 mysql_real_escape_string( intval($itemid) ),
				 mysql_real_escape_string( $this->loginedUser['identity']  )
		);
		sql_query($query);
	}
	
	function event_PostDeleteComment(&$data) {
		$query = sprintf('DELETE FROM ' . sql_table('plugin_openid_comment')
				. " where cnumber = '%s'",
				 mysql_real_escape_string( intval($data['commentid']) )
		);
		sql_query($query);
	}
	
	function event_LoginSuccess(&$data) {
		if( $this->isLogined() )
			$this->logout();
	}
	
	function event_Logout(&$data) {
		if( $this->isLogined() )
			$this->logout();
	}
	
	function doTemplateCommentsVar(&$item, &$comment){
		global $member;
		$itemid = intval($item['itemid']);
		if( ! $this->comments[$itemid] ){
			$this->comments[$itemid]['cached'] = true;
			$query = sprintf('SELECT c.cnumber as cnumber, c.identity as identity, p.nick as nick, p.email as email, p.sreg as sreg FROM ' . sql_table('plugin_openid_comment') . ' c '
					. ' LEFT OUTER JOIN ' . sql_table('plugin_openid_profile') . ' p ON c.identity = p.identity '
					. " WHERE citem = '%s'"
					, mysql_real_escape_string( intval($itemid) )
			);
			$res = sql_query($query);
			$this->comments[$itemid] = array();
			while( $a =& mysql_fetch_assoc($res)) {
				$cnumber = $a['cnumber'];
				$this->comments[$itemid][$cnumber] = $a;
			}
		}
		$cnumber = $comment['commentid'];
		if( $openIdComment = $this->comments[$itemid][$cnumber] ){
			$aVars['identity'] =  $openIdComment['identity'];
			$sreg = unserialize($openIdComment['sreg']);
			if( is_array($sreg) )
				$aVars = array_merge($aVars, $sreg);
			
			if ( $member->isLoggedIn() )
				echo TEMPLATE::fill($this->getOption('TemplateAdminHtml'), $aVars);
			else
				echo TEMPLATE::fill($this->getOption('TemplateHtml'), $aVars);
		}
	}
}
