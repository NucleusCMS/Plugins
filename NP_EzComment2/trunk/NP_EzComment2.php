<?php
/**
 * SHOW Comment Form/List PLUG-IN FOR NucleusCMS
 * PHP versions 5
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
 * @version   $Date$ $Revision: 1.13 $
 * @link      http://japan.nucleuscms.org/wiki/plugins:ezcomment2
 * @since     File available since Release 1.0
 */

/**
 * version history
 *
 * $Log: not supported by cvs2svn $
 * Revision 1.12  2008/07/09 03:54:07  shizuki
 * *** empty log message ***
 *
 * Revision 1.11  2008/07/09 03:54:04  shizuki
 * *Fix header info URL:showblogs -> ezcomment2
 *
 *  * Revision 1.10  2008/07/08 16:14:57  shizuki
 * *Bug fix event_PreComment
 * *Correspondence preparations of NP_LatestWritebacks
 *
 * Revision 1.9  2008/07/08 15:14:27  shizuki
 * * Corresponds to event_PreComment.
 * * Fix typo.
 * * RC2
 *
 * Revision 1.8  2008/07/07 15:42:54  shizuki
 * * The experimental society  PHP Version: 5.2.6/MySQL Server Version (client): (5.1.25-rc-log 5.1.25-rc).
 * * The normal movement is confirmed.
 * * The SQL correction/behavior when installing, is changed a little.
 * * event_PostDeleteCommnent addition * It's corrected when being off login time and secret mode-lessly, so as not to take out a check box.
 * * NP_OpenId is indispensable in the present.
 * * NP_znSpecialTemplateParts is indispensable.
 * * It's expected to add the setting which will establish a password at the time of contribution without NP_OpenId and make it hidden from now on.
 * * It's RC edition, so please cooperate in the one with the environment.
 *
 * Revision 1.7  2008/07/07 10:24:00  shizuki
 * * Still, the human sacrifice test version.
 * * A template was separated for for index pages and item page.
 * * Subdivision of the showComment() function.
 * * It's changed so as not to fly to an indication part for indication in case of and OpenID of anything but the first item of an index page.
 * * A profile change part besides the first item of an index page is being adjusted.
 */

class NP_EzComment2 extends NucleusPlugin
{
	// {{{ properties

	/**
	 * The calling number of times by the index page.
	 *
	 * @var integer
	 */
	var $numcalled;

	/**
	 * OpenID authentication module.
	 *
	 * @var object
	 */
	var $authOpenID;

	/**
	 * Flag of the case that one is invoker.
	 *
	 * @var boolean
	 */
	var $callFlg;

	// }}}
	// {{{ getName()

	/**
	 * Plugin Name
	 *
	 * @return string
	 */
	function getName()
	{
		return 'Ez Comment II';
	}

	// }}}
	// {{{ getAuthor()

	/**
	 * Author Name
	 *
	 * @return string
	 */
	function getAuthor()
	{
		return 'shizuki';
	}

	// }}}
	// {{{ getURL()

	/**
	 * I get a plug-in, the address of the possible site or author's mail address.
	 *
	 * @return string
	 */
	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:ezcomment2';
	}

	// }}}
	// {{{ getPluginDep()

	/**
	 * Plugin Dependency.
	 *
	 * @return array
	 */
	function getPluginDep()
	{
		return array(
//			'NP_OpenId',
//			'NP_znSpecialTemplateParts',
		);
	}

	// }}}
	// {{{ getVersion()

	/**
	 * Plugin Version.
	 *
	 * @return string
	 */
	function getVersion()
	{
		return '$Date$ $Revision: 1.13 $';
	}

	// }}}
	// {{{ getDescription()

	/**
	 * Plugin Description
	 *
	 * @return string
	 */
	function getDescription()
	{
		return  _NP_EZCOMMENT2_DESC;
	}

	// }}}
	// {{{ supportsFeature($what)

	/**
	 * Supports Nucleus Feature
	 *
	 * @param  string
	 * @return boolean
	 */
	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	// }}}
	// {{{ getEventList()

	/**
	 * List of feature event
	 *
	 * @return array
	 */
	function getEventList()
	{
		global $manager;
		return array(
			'FormExtra',
			'PostAddComment',
			'PostDeleteComment',
			'PreComment',
			'TemplateExtraFields',
		);
	}

	// }}}
	// {{{ getTableList()

	/**
	  * Database tables for plugin used
	  *
	  * @return array
	  **/
	function getTableList()
	{
		return array(
			sql_table('plug_ezcomment2'),
		);
	}

	// }}}
	// {{{ install()

	/**
	 * Install function
	 *
	 * @return void.
	 */
	function install()
	{
		if (!TEMPLATE::exists('EzCommentTemplate')) {
			global $DIR_LIBS;
			include_once($DIR_LIBS . 'skinie.php');
			$importer = new SKINIMPORT();
			$importer->reset();
			$template = $this->getDirectory() . 'skinbackup.xml';
			if (!@file_exists($template)) {
				$aErrors[] = 'Unable to import ' . $template . ' : file does not exist';
				continue;
			}
			$error = $importer->readFile($template);
			if ($error) {
				$aErrors[] = 'Unable to import ' . $template . ' : ' . $error;
				continue;
			}
			$error = $importer->writeToDatabase(1);
			if ($error) {
				$aErrors[] = 'Unable to import ' . $template . ' : ' . $error;
				continue;
			}
			if ($aErrors) {
				$message = implode("<br />\n", $aErrors);
				doError($message);
			}
		}
		$this->createBlogOption('secret',     _NP_EZCOMMENT2_OP_SECRETMODE,  'yesno', 'yes');
		$this->createBlogOption('secComment', _NP_EZCOMMENT2_OP_SUBSTIUTION, 'text',  _NP_EZCOMMENT2_OP_SUBSTIUTION_VAL);
		$this->createBlogOption('secLabel',   _NP_EZCOMMENT2_OP_CHECKLABEL,  'text',  _NP_EZCOMMENT2_OP_CHECKLABEL_VAL);
		$this->createOption('tabledel',       _NP_EZCOMMENT2_OP_DROPTABLE,   'yesno', 'yes');
		$sql = 'CREATE TABLE IF NOT EXISTS %s ('
			 . '`comid`  int(11)  NOT NULL, '
			 . '`secflg` tinyint(1)   NULL, '
			 . '`module` varchar(15)  NULL, '
			 . '`userID` varchar(255) NULL, '
			 . 'PRIMARY KEY(`comid`) );';
		sql_query(sprintf($sql, sql_table('plug_ezcomment2')));
		$this->updateTable();
	}

	// }}}
	// {{{ uninstall()

	/**
	 * Un Install function
	 *
	 * @return void.
	 */
	function uninstall()
	{
		if ($this->getOption('tabledel') == 'yes')
			sql_query('DROP TABLE '.sql_table('plug_ezcomment2'));
	}

	// }}}
	// {{{ init()

	/**
	 * Initialize
	 *
	 * @return void.
	 */
	function init()
	{
		$this->languageInclude();
		$this->numcalled = 0;
		$this->callFlg   = false;
		global $manager;
		if ($manager->pluginInstalled('NP_OpenId') && !$this->authOpenID) {
			$this->authOpenID = $manager->getPlugin('NP_OpenId');
		}
	}

	// }}}
	// {{{ event_TemplateExtraFields($data)

	/**
	 * Extra template parts for plugin specified
	 *
	 * @param array
	 *			fields array
	 *					'PLUGIN_NAME' array
	 *									'TEMPLATE_PARTS_NAME'
	 *									'TEMPLATE_PARTS_NAME'
	 *									'TEMPLATE_PARTS_NAME'...
	 * @return void
	 */
	function event_TemplateExtraFields($data)
	{
		$data['fields']['NP_EzComment2'] = array(
			'_NP_EZCOMMENT2_FORM_LOGGEDIN_IDX'    => _NP_EZCOMMENT2_FORM_LOGGEDIN_IDX, 
			'_NP_EZCOMMENT2_FORM_NOTLOGGEDIN_IDX' => _NP_EZCOMMENT2_FORM_NOTLOGGEDIN_IDX, 
			'_NP_EZCOMMENT2_FORM_LOGGEDIN_ITM'    => _NP_EZCOMMENT2_FORM_LOGGEDIN_ITM,
			'_NP_EZCOMMENT2_FORM_NOTLOGGEDIN_ITM' => _NP_EZCOMMENT2_FORM_NOTLOGGEDIN_ITM, 
			'COMMENTS_BODY_IDX'                   => _NP_EZCOMMENT2_COMMENTS_BODY_IDX, 
			'COMMENTS_FOOTER_IDX'                 => _NP_EZCOMMENT2_COMMENTS_FOOTER_IDX, 
			'COMMENTS_HEADER_IDX'                 => _NP_EZCOMMENT2_COMMENTS_HEADER_IDX,
		);
	}

	// }}}
	// {{{ event_PostAddComment($data)

	/**
	 * After adding a comment to the database.
	 *
	 * @param  array
	 *			commentid integer
	 *			comment   array
	 *			spamcheck array
	 * @return void.
	 */
	function event_PostAddComment($data)
	{
		global $member;
		switch (true) {
			case $member->isLoggedin():
				$userID = '"' . $member->getID() . '"';
				$module = '"Nucleus"';
				break;
			case ($this->authOpenID && $this->authOpenID->isLoggedin()):
				$userID = '"' . $this->authOpenID->loggedinUser['identity'] . '"';
				$module = '"OpenID"';
				break;
			default:
				$userID = 'NULL';
				$module = 'NULL';
				break;
		}
		if (postVar('EzComment2_Secret')) {
			$secCheck = 1;
		} else {
			$secCheck = 'NULL';
		}
		$sql = 'INSERT INTO ' . sql_table('plug_ezcomment2')
			 . ' (`comid`, `secflg`, `module`, `userID`) VALUES (%d, %d, %s, %s)';
		sql_query(sprintf($sql, $data['commentid'], $secCheck, $module, $userID));
	}

	// }}}
	// {{{ event_PostDeleteComment($data)

	/**
	 * After a comment has been deleted from the database.
	 *
	 * @param  array
	 *			commentid integer
	 * @return void.
	 */
	function event_PostDeleteComment($data)
	{
		$sql = 'DELETE FROM ' . sql_table('plug_ezcomment2')
			 . ' WHERE `comid` = %d LIMIT 1';
		sql_query(sprintf($sql, $data['commentid']));
	}

	// }}}
	// {{{ event_FormExtra(&$data)

	/**
	 * Inside one of the comment, membermail or account activation forms.
	 *
	 * @param  array
	 *			type string
	 * @return void.
	 */
	function event_FormExtra(&$data)
	{
		global $member, $blogid;
		$this->numcalled++;
		if ($blogid && $this->getBlogOption($blogid, 'secret') == 'yes' &&
			($member->isLoggedin() || ($this->authOpenID && $this->authOpenID->isLoggedin()))) {
				echo '<br /><input type="checkbox" value="1" name="EzComment2_Secret" id="EzComment2_Secret_' . $this->numcalled . '" />';
				echo '<label for="EzComment2_Secret_' . $this->numcalled . '">'.$this->getBlogOption($bid, 'secLabel').'</label><br />';
		}
//		if ($this->authOpenID) {
//			$this->plugOpenIDdoSkinVar($this->commentSkinType, $this->commentItemId);
//		}
	}

	// }}}
	// {{{ event_PreComment(&$data)

	/**
	 * Inside one of the comment, membermail or account activation forms.
	 *
	 * @param  array
	 *			comment array
	 * @return void.
	 */
	function event_PreComment(&$data)
	{
		if ($this->callFlg) return;
		$sql = 'SELECT secflg, userID FROM ' . sql_table('plug_ezcomment2')
			 . ' WHERE comid = ' . intval($data['comment']['commentid']);
		$res = sql_query($sql);
		$flg = mysql_fetch_assoc($res);
		if (!$flg['secflg']) return;
		$data['comment']['identity'] = $flg['userID'];
		global $manager, $member;
		$bid   = intval($data['comment']['blogid']);
		$b     = $manager->getBlog($bid);
		$judge = $this->setSecretJudge($bid, $member, $b);
		$data['comment'] = $this->JudgementCommentSecrets($data['comment'], $judge);
//		print_r($data);
	}

	// }}}
	// {{{ doTemplateVar()

	/**
	 * Basically the same as doSkinVar,
	 * but this time for calls of the <%plugin(...)%>-var in templates (item header/body/footer and dateheader/footer).
	 *
	 * @param  object item object(refarence)
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void.
	 */
	function doTemplateVar(&$item,
							$showType       = '',
							$showMode       = '5/1/1',
							$destinationurl = '',
							$formTemplate   = 'EzCommentTemplate',
							$listTemplate   = 'EzCommentTemplate')
	{
		$this->doSkinVar('template', $showType, $showMode, $destinationurl, $formTemplate, $listTemplate, $item);
	}

	// }}}
	// {{{ doSkinVar()

	/**
	 * When plugins are called using the <%plugin(...)%>-skinvar, this method will be called. 
	 *
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  object item object(refarence)
	 * @return void.
	 */
	function doSkinVar($skinType,
					   $showType       = '',
					   $showMode       = '5/1/1',
					   $destinationurl = '',
					   $formTemplate   = 'EzCommentTemplate',
					   $listTemplate   = 'EzCommentTemplate',
					  &$commentItem    = '')
	{
		if ($skinType != 'item' && $skinType != 'template') return;
		global $manager, $member, $itemid;
		if (!$commentItem && $itemid) {
			$commentItem = $manager->getItem($itemid, 0, 0);
			if (is_array($commentItem)) {
				$commentItem = (object)$commentItem;
			}
		}
		if (!$commentItem || $commentItem->closed) {
			echo _ERROR_ITEMCLOSED;
			return 0;
		}

		if (is_numeric($showType) || strpos($showType, '/') !== false) $showMode = $showType;
		if ($showType != 'list' && $showType != 'form') {
			$showType = '';
		}
		if (!$showMode) {
			$showMode = '5/1/1';
		}
		list($maxToShow, $sortOrder, $commentOrder) = explode('/', $showMode);
		if (!$maxToShow) $maxToShow = 5;
		if (!$sortOrder) $sortOrder = 1;
		if ($commentOrder > 0) {
			$commentOrder = true;
		} else {
			$commentOrder = false;
		}
		if (!$formTemplate) $formTemplate = 'EzCommentTemplate';
		if (!$listTemplate) $listTemplate = 'EzCommentTemplate';

		switch ($showType) {
			case 'list':
				$listTemplate = TEMPLATE::read($listTemplate);
				$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder, $skinType);
				break;
			case 'form':
				$formTemplate = TEMPLATE::read($formTemplate);
				$this->showForm($commentItem, $formTemplate, $destinationurl, $skinType);
				break;
			default:
				$listTemplate = TEMPLATE::read($listTemplate);
				$formTemplate = TEMPLATE::read($formTemplate);
				if ($sortOrder) {
					$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder, $skinType);
					$this->showForm($commentItem, $formTemplate, $destinationurl, $skinType);
				} else {
					$this->showForm($commentItem, $formTemplate, $destinationurl, $skinType);
					$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder, $skinType);
				}
				break;
		}
	}

	// }}}
	// {{{ languageInclude()

	/**
	 * Include language file
	 *
	 * @return void.
	 */
	function languageInclude()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . 'language/' . $language . '.php')) {
			include_once($this->getDirectory() . 'language/' . $language . '.php');
		} else {
			include_once($this->getDirectory() . 'language/english.php');
		}
	}

	// }}}
	// {{{ updateTable()

	/**
	 * Update database table
	 *
	 * @return void.
	 */
	function updateTable()
	{
		$sql = 'SELECT c.cnumber as cid FROM ' . sql_table('comment') . ' as c '
			 . 'LEFT JOIN ' . sql_table('plug_ezcomment2') . ' as s '
			 . 'ON c.cnumber=s.comid WHERE s.comid IS NULL';
		$res = sql_query($sql);
		$sql = 'INSERT INTO ' . sql_table('plug_ezcomment2') . '(`comid`) VALUES (%d)';
		while ($cid = mysql_fetch_assoc($res)) {
			sql_query(sprintf($sql, $cid['cid']));
		}
	}

	// }}}
	// {{{ plugOpenIDdoSkinVar()

	/**
	 * Overwride NP_OpenId's doSkinVar()
	 * 
	 * @param  string
	 * @param  integer
	 * @return void.
	 *
	function plugOpenIDdoSkinVar($skinType, $iid = 0)
	{
		global $CONF, $manager, $member;
		if ($member->isLoggedIn()) return;
		$authOpenID   = $this->authOpenID;
		if (!$authOpenID) return;
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
				$templateDirectory           =  rtrim($this->getDirectory(), '/');
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

	// }}}*/
	// {{{ checkDestinationurl($destinationurl)

	/**
	 * Destinationurl check
	 *
	 * @param  string
	 * @return string
	 */
	function checkDestinationurl($destinationurl, $iid, $cid = 0, $scid = 0)
	{
		if (stristr($destinationurl, 'action.php') || empty($destinationurl)) {
			if (stristr($destinationurl, 'action.php')) {
				$logMessage = 'actionurl is not longer a parameter on commentform skinvars.'
							. ' Moved to be a global setting instead.';
				ACTIONLOG::add(WARNING, $logMessage);
			}
			if ($cid) {
				$linkparams['catid'] = intval($cid);
			}
			global $manager;
			if ($manager->pluginInstalled('NP_MultipleCategories') && $scid) {
				$linkparams['subcatid'] = intval($scid);
			}
			$destinationurl = createItemLink(intval($iid), $linkparams);
		} else {
			$destinationurl = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $destinationurl);
		}
		return $destinationurl;
	}

	// }}}
	// {{{ getCommentatorInfo()

	/**
	 * Get commentator info.
	 *
	 * @return array
	 */
	function getCommentatorInfo()
	{
		global $CONF;
		$user = cookieVar($CONF['CookiePrefix'] .'comment_user');
		if (!$user) {
			$user = postVar('user');
		}
		$userid = cookieVar($CONF['CookiePrefix'] .'comment_userid');
		if (!$userid) {
			$userid = postVar('userid');
		}
		$email = cookieVar($CONF['CookiePrefix'] .'comment_email');
		if (!$email) {
			$email = postVar('email');
		}
		$body    = postVar('body');
		return array(
			$user,
			$userid,
			$email,
			$body
		);
	}
	// {{{ showForm()

	/**
	 * Show comment form
	 *
	 * @param  object
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void.
	 */
	function showForm($commentItem, $template, $destinationurl, $skinType)
	{
		global $CONF, $manager, $member, $catid, $subcatid;
		$bid =  getBlogIDFromItemID($commentItem->itemid);
		$b   =& $manager->getBlog($bid);
		$b->readSettings();
		if (!$member->isLoggedIn() && !$b->commentsEnabled()) {
			return;
		}
		$destinationurl = $this->checkDestinationurl($destinationurl, $commentItem->itemid, $catid, $subcatid);
		list($user, $userid, $email, $body) = $this->getCommentatorInfo();

		$checked = cookieVar($CONF['CookiePrefix'] .'comment_user') ? 'checked="checked" ' : '';

		$formdata = array(
			'self'            => $this->_hsc(serverVar('REQUEST_URI')),
			'destinationurl'  => $this->_hsc($destinationurl),
			'actionurl'       => $this->_hsc($CONF['ActionURL']),
			'itemid'          => intval($commentItem->itemid),
			'user'            => $this->_hsc($user),
			'userid'          => $this->_hsc($userid),
			'email'           => $this->_hsc($email),
			'body'            => $this->_hsc($body),
//			'membername'      => $this->_hsc($membername),
			'rememberchecked' => $checked
		);
		if ($skinType == 'item') {
			$formFlg = '_ITM';
		} else {
			$formFlg = '_IDX';
		}
		if ($member && $member->isLoggedIn()) {
			$formType = '_NP_EZCOMMENT2_FORM_LOGGEDIN' . $formFlg;
			$loginMember = $member->createFromID($member->getID());
			$formdata['membername'] = $this->_hsc($loginMember->getDisplayName());
		} else {
			$formType = '_NP_EZCOMMENT2_FORM_NOTLOGGEDIN' . $formFlg;
		}
//		if ($this->authOpenID && ($skinType == 'item' || $this->numcalled == 0)) {
//			$this->plugOpenIDdoSkinVar($skinType, intval($commentItem->itemid));
//		}
		$this->commentItemId   = intval($commentItem->itemid);
		$this->commentSkinType = $skinType;
		$contents   = $template[$formType];
		include_once($this->getDirectory() . 'EzCommentActions.php');
		$formAction =& new EzCommentFormActions($skinType, $commentItem, $formdata, $loginMember);
		$parser     =& new PARSER($formAction->getAllowedActions(), $formAction);
		$parser->parse(&$contents);
	}

	// }}}
	// {{{ showComment()

	/**
	 * Show comments
	 *
	 * @param  object
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void.
	 */
	function showComment($commentItem, $template, $maxToShow, $commentOrder, $skinType)
	{
		global $manager, $member;
		$bid =  getBlogIDFromItemID($commentItem->itemid);
		$b   =& $manager->getBlog($bid);
		if (!$b->commentsEnabled()) return;
		if (!$maxToShow) {
			$maxToShow = $b->getMaxComments();
		}
		$itemActions =& new ITEMACTIONS($b);
		$itemActions->setCurrentItem($commentItem);
		$commentObj =& new COMMENTS($commentItem->itemid);
		$commentObj->setItemActions($itemActions);
		$commentObj->commentcount = $commentObj->amountComments();
		// create parser object & action handler
		include_once($this->getDirectory() . 'EzCommentActions.php');
		$actions =& new EzCommentActions($commentObj);
		$parser  =& new PARSER($actions->getAllowedActions(), $actions);
		$actions->setTemplate($template);
		$actions->setParser($parser);
		if ($commentObj->commentcount == 0) {
			$parser->parse($template['COMMENTS_NONE']);
			return 0;
		}
		$actions->setPostnum($commentObj->commentcount);
		if ($maxToShow && $maxToShow < $commentObj->commentcount && $commentOrder) {
			$startnum = $commentObj->commentcount - $maxToShow;
		} else {
			$startnum = 0;
		}
		$comments = $this->getComments($commentOrder, intval($commentItem->itemid), $maxToShow, $startnum);
		$viewnum  = mysql_num_rows($comments);
		$actions->setViewnum($viewnum);
		if ($this->getBlogOption($bid, 'secret') == 'yes') {
			$judge = $this->setSecretJudge($bid, $member, $b);
		}

		$templateType = '';
		if ($skinType == 'template') $templateType = '_IDX';
		$blogURL       = $b->getURL();
		$substitution  = $this->getBlogOption($bid, 'secComment');
		$this->callFlg = true;
		$parser->parse($template['COMMENTS_HEADER' . $templateType]);

		while ($comment = mysql_fetch_assoc($comments)) {
			$comment['timestamp'] = strtotime($comment['ctime']);
			if ($judge && $comment['secret']) {
				$comment = $this->JudgementCommentSecrets($comment, $judge);
			}
			$actions->setCurrentComment($comment);
			$manager->notify('PreComment', array('comment' => &$comment));
			$parser->parse($template['COMMENTS_BODY' . $templateType]);
			$manager->notify('PostComment', array('comment' => &$comment));
		}

		$parser->parse($template['COMMENTS_FOOTER' . $templateType]);

		mysql_free_result($comments);

	}

	// }}}
	// {{{ setSecretJudge($bid)

	/**
	 * Setting for judgment of whether it's a comment of a secret.
	 *
	 * @param  intgre
	 * @param  object
	 * @param  object
	 * @return array
	 */
	function setSecretJudge($bid, $member, $b)
	{
		$memberLoggedin = $member->isLoggedin();
		$loginUser      = $member->getID();
		$blogAdmin      = $member->blogAdminRights($bid);
		$blogURL        = $b->getURL();
		$substitution   = $this->getBlogOption($bid, 'secComment');
		if ($this->authOpenID) {
			$openIDLoggedin = $this->authOpenID->isLoggedin();
			$openIDUser     = $this->authOpenID->loggedinUser['identity'];
		}
		return array(
			'memberLoggedin' => $memberLoggedin,
			'loginUser'      => $loginUser,
			'blogAdmin'      => $blogAdmin,
			'blogURL'        => $blogURL,
			'substitution'   => $substitution,
			'openIDLoggedin' => $openIDLoggedin,
			'openIDUser'     => $openIDUser,
		);
	}

	// }}}
	// {{{ JudgementCommentSecrets($comment, $judge)

	/**
	 * Comment is secret ?
	 *
	 * @param  array
	 * @param  array
	 * @param  string
	 * @param  string
	 * @return array
	 */
	function JudgementCommentSecrets($comment, $judge)
	{
/*		if ($judge['memberLoggedin']) {
			echo 'member';
			if ($judge['loginUser']  == intval($comment['identity'])) {
				echo 'commentator';
			} elseif ($judge['blogAdmin']) {
				echo 'admin';
			}
		} elseif ($judge['openIDLoggedin']) {
//			echo 'openid / ';
			echo $judge['openIDUser'].' / ';
			echo $comment['identity'].' / ';
				echo "honnnin";
		}*/
		if (!(($judge['memberLoggedin'] && ($judge['loginUser']  == intval($comment['identity']) || $judge['blogAdmin'])) ||
			($judge['openIDLoggedin'] && $judge['openIDUser'] == $comment['identity']))) {
				$this->changeCommentSet($comment, $judge);
			}
		return $comment;
	}

	// }}}
	// {{{ changeCommentSet($comment, $blogURL, $substitution)

	/**
	 * Change secret comment contents
	 *
	 * @param  array
	 * @param  string
	 * @param  string
	 * @return array
	 */
	function changeCommentSet(&$comment, $judge)
	{
		global $manager;
		$comment['body']        = $judge['substitution'];
		$comment['short']       = $judge['substitution'];
		$comment['excerpt']     = $judge['substitution'];
		$comment['userid']      = $judge['blogURL'];
		$comment['memberid']    = 0;
		$comment['user']        = '#';
		$comment['useremail']   = '#';
		$comment['userwebsite'] = '#';
		$comment['email']       = '#';
		$comment['userlinkraw'] = '#';
		$comment['userlink']    = '#';
		$comment['host']        = '127.0.0.1';
		$comment['ip']          = '127.0.0.1';
		if ($manager->pluginInstalled('NP_LatestWritebacks')) {
			$comment['commentbody'] = $judge['substitution'];
			$comment['commentator'] = '#';
		}
		return $comment;
	}
	// {{{ getComments($comment, $judge)

	/**
	 * Change in the comment contents.
	 *
	 * @param  boolean
	 * @param  integre
	 * @param  integre
	 * @param  integre
	 * @return resouce
	 */
	function getComments($commentOrder, $iid, $maxToShow, $startnum)
	{
		$order = ($commentOrder) ? "DESC" : "ASC";
		$query = 'SELECT '
			   . 'c.citem   as itemid, '
			   . 'c.cnumber as commentid, '
			   . 'c.cbody   as body, '
			   . 'c.cuser   as user, '
			   . 'c.cmail   as userid, '
			   . 'c.cemail  as email, '
			   . 'c.cmember as memberid, '
			   . 'c.ctime, '
			   . 'c.chost   as host, '
			   . 'c.cip     as ip, '
			   . 'c.cblog   as blogid, '
			   . 's.comid   as cid, '
			   . 's.secflg  as secret, '
			   . 's.module  as modname, '
			   . 's.userID  as identity '
			   . ' FROM ' . sql_table('comment') . ' as c '
			   . ' LEFT OUTER JOIN ' . sql_table('plug_ezcomment2') . ' as s '
			   . ' ON c.cnumber = s.comid '
			   . ' WHERE c.citem = ' . intval($iid)
			   . ' ORDER BY c.ctime '
			   . $order;
		if ($maxToShow) {
			if ($order == "DESC") {
				$query .=' LIMIT ' . intval($maxToShow);
			} else {
				$query .=' LIMIT ' . intval($startnum) . ',' . intval($maxToShow);
			}
		}
		return sql_query($query);
		
	}

	// }}}
	// {{{ getTemplateParts()

	/**
	 * Comment form/list template via NP_znSpecialTemplateParts
	 *
	 * @return array
	 *
	function getTemplateParts()
	{
		$this->languageInclude();
		return array(
			'FORM_LOGGEDIN_IDX'    => _NP_EZCOMMENT2_FORM_LOGGEDIN_IDX, 
			'FORM_NOTLOGGEDIN_IDX' => _NP_EZCOMMENT2_FORM_NOTLOGGEDIN_IDX, 
			'FORM_LOGGEDIN_ITM'    => _NP_EZCOMMENT2_FORM_LOGGEDIN_ITM,
			'FORM_NOTLOGGEDIN_ITM' => _NP_EZCOMMENT2_FORM_NOTLOGGEDIN_ITM, 
			'COMMENTS_BODY_IDX'    => _NP_EZCOMMENT2_COMMENTS_BODY_IDX, 
			'COMMENTS_FOOTER_IDX'  => _NP_EZCOMMENT2_COMMENTS_FOOTER_IDX, 
			'COMMENTS_HEADER_IDX'  => _NP_EZCOMMENT2_COMMENTS_HEADER_IDX,
		);
	}

	// }}}
	// {{{ _hsc()

	/**
	 * HTML entity
	 *
	 * @param  string
	 * @return string
	 */
	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}
	// }}}
	
}



