<?php
/**
 * SHOW Comment Form/List PLUG-IN FOR NucleusCMS
 * PHP versions 5
 *
 * Form and List template parser
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 *
 * @author    shizuki
 * @copyright 2008 shizuki
 * @license   http://www.gnu.org/licenses/gpl.txt  GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 * @version   $Date$ $Revision$
 * @link      http://japan.nucleuscms.org/wiki/plugins:ezcomment2
 * @since     File available since Release 1.0
 */
/**
 * version history
 *
 * $Log$
 */
class EzCommentFormActions extends ACTIONS
{

var $commentItem;

var $loginMember;

var $numcalled;

var $authOpenID;

	function EzCommentFormActions($skinType, &$item, $formdata, $member)
	{
		global $manager;
		$this->ACTIONS($skinType);
		$this->commentItem =& $item;
		$this->formdata    =  $formdata;
		$this->loginMember =  $member;
		$this->numcalled   =  0;
		if ($manager->pluginInstalled('NP_OpenId')) {
			$this->authOpenID = $manager->getPlugin('NP_OpenId');
		}
	}

	function getAllowedActions()
	{
		return array(
					 'text',
					 'self',
					 'formdata',
					 'callback',
					 'errordiv',
					 'ticket',
					 'itemid',
					 'itemlink',
					 'itemtitle',
					 'membername',
					 'memberurl',
					 'openidform',
					);
	}

	function parse_itemid() {
		echo $this->commentItem->itemid;
	}
	
	function parse_itemlink($linktext = '') {
		global $itemid;
		$this->_itemlink($this->commentItem->itemid, $linktext);
	}

	function parse_itemtitle($format = '') {
		switch ($format) {
			case 'xml':
				echo stringToXML ($this->commentItem->itemtitle);
				break;
			case 'attribute':
				echo stringToAttribute ($this->commentItem->itemtitle);
				break;
			case 'raw':
				echo $this->commentItem->itemtitle;
				break;
			default:
				echo $this->_hsc(strip_tags($this->commentItem->itemtitle));
				break;
		}
	}

	function parse_membername($mode='')
	{
		if ($mode == 'realname') {
			echo $this->_hsc($this->loginMember->getRealName());
		} else {
			echo $this->_hsc($this->loginMember->getDisplayName());
		}
	}

	function parse_memberurl()
	{
		echo $this->_hsc($this->loginMember->getURL());
	}

	/**
	 * Parse callback
	 */
	function parse_callback($eventName, $type)
	{
		global $manager;
		$subscriver =& $manager->subscriptions['FormExtra'];
		foreach ($subscriver as $key => $value) {
			if (strtoupper($value) == 'NP_OPENID') {
				unset($subscriver[$key]);
				break;
			}
		}
		$this->numcalled++;
		$manager->notify($eventName, array('type' => $type));
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

	function parse_openidform()
	{
		global $manager;
		if ($manager->pluginInstalled('NP_OpenId')) {
			$this->plugOpenIDdoSkinVar($this->skintype, $this->commentItem->itemid);
		}
	}

	// {{{ plugOpenIDdoSkinVar()

	/**
	 * Overwride NP_OpenId's doSkinVar()
	 * 
	 * @param  string
	 * @param  integer
	 * @return void.
	 */
	function plugOpenIDdoSkinVar($skinType, $iid = 0)
	{
		global $CONF, $manager, $member;
		if ($member->isLoggedIn()) return;
		if (!($authOpenID = $this->authOpenID)) return;
		$ezComment    = $manager->getPlugin('NP_EzComment2');
		$externalauth = array ( 'source' => $authOpenID->getName() );
		$manager->notify('ExternalAuth', array ('externalauth' => &$externalauth));
		if (isset($externalauth['result']) && $externalauth['result'] == true) return;
		$templateEngine     = $authOpenID->_getTemplateEngine();
		$aVars              = array();
		$aVars['PluginURL'] = $CONF['PluginURL'];
		if ($authOpenID->isLoggedin()) {
			// Loggedin
			if ($skinType == 'template') {
				require_once 'cles/Template.php';
				$templateDirectory           =  rtrim($ezComment->getDirectory(), '/');
				$templateEngine              =& new cles_Template($templateDirectory);
				$templateEngine->defaultLang =  'english';
				$aVars['itemid'] = intval($iid);
			}
			$nowURL             = 'http://' . serverVar("HTTP_HOST")
								. serverVar("REQUEST_URI");
			$aVars['url']       = $authOpenID->getAdminURL() . 'rd.php?action=rd'
								. '&url=' . urlencode($nowURL);
			$aVars['nick']      = $authOpenID->loggedinUser['nick'];
			$aVars['email']     = $authOpenID->loggedinUser['email'];
			$aVars['ts']        = $authOpenID->loggedinUser['ts'];
			$aVars['identity']  = $authOpenID->loggedinUser['identity'];
			$aVars['visible']   = $aVars['nick'] ? 'false' : 'true' ;
			$actionUrl          = parse_url($CONF['ActionURL']);
			$aVars['updateUrl'] = $actionUrl['path'];
			if ($skinType == 'item' || ($skinType == 'template' && $this->numcalled == 0)) {
				echo $templateEngine->fetchAndFill('yui',         $aVars, 'np_openid');
				echo $templateEngine->fetchAndFill('form',        $aVars, 'np_openid');
			}
			echo $templateEngine->fetchAndFill('loggedin',    $aVars, 'np_openid');
		} elseif (!$authOpenID->isLoggedin()) {
			// Not loggedin
			$aVars['url']       = $authOpenID->getAdminURL() . 'rd.php?action=doauth'
							    . '&return_url=' . urlencode(createItemLink(intval($iid)));
			echo $templateEngine->fetchAndFill('notloggedin', $aVars, 'np_openid');
		}
	}

	// }}}

}

class EzCommentActions extends COMMENTACTIONS
{

var $viewnum;

var $postnum;

	function EzCommentActions(&$comments)
	{
		$this->COMMENTACTIONS($comments);
	}

	function getAllowedActions()
	{
		$allowedActions   = $this->getDefinedActions();
		$allowedActions[] = 'viewnum';
		$allowedActions[] = 'postnum';
		$allowedActions[] = 'viewparpost';
		return $allowedActions;
	}

	function setPostnum($postnum)
	{
		$this->postnum = $postnum;
	}

	function setViewnum($viewnum)
	{
		$this->viewnum = $viewnum;
	}

	function parse_viewnum()
	{
		echo intval($this->viewnum);
	}

	function parse_postnum()
	{
		echo intval($this->postnum);
	}

	function parse_viewparpost()
	{
		echo intval($this->viewnum) . ' ';
		if ($this->postnum > $this->viewnum) {
			echo '/ ' . $this->postnum . ' ';
		}
		$this->parse_commentword();
	}

}

