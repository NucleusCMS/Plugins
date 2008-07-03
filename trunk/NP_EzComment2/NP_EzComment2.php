<?php

class NP_EzComment2 extends NucleusPlugin
{
	var $authModule  = null;
	var $flgLoggedin = false;

	function getName()
	{
		return 'Ez Comment II';
	}

	function getAuthor()
	{
		return 'shizuki';
	}

	function getURL()
	{
		return 'http://japan.nucleuscms.org/wiki/plugins:ezcomment2';
	}

	function getVersion()
	{
		return '1.0';
	}

	function getDescription()
	{
		return  _NP_EZCOMMENT2_DESC;
	}

	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		global $manager;
		return array(
			'ExternalAuth',
			'LoginSuccess',
		);
	}

	function event_ExternalAuth(&$data)
	{
		if ($this->flgLoggedin) return;
		global $manager;
		$pluginName = 'NP_' . $data['externalauth']['source'];
		if ($manager->pluginInstalled($pluginName)) {
			$authPlugin =& $manager->getPlugin($pluginName);
			if( $authPlugin->isLoggedin() ){
				$this->flgLoggedin = true;
				$this->authModule  = $authPlugin;
			}
		}
	}

	function event_LoginSuccess($data)
	{
		$this->flgLoggedin = true;
		$this->authModule  = $data['member'];
	}

	function install()
	{
		if (!TEMPLATE::exists('EzCommentTemplateDefault')) {
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
			createBlogOption('secret',     _NP_EZCOMMENT2_OP_SECRETMODE,  'yesno', 'no');
			createBlogOption('secComment', _NP_EZCOMMENT2_OP_SUBSTIUTION, 'text',  _NP_EZCOMMENT2_OP_SUBSTIUTION_VAL);
			createBlogOption('secLabel',   _NP_EZCOMMENT2_OP_CHECKLABEL,  'text',  _NP_EZCOMMENT2_OP_CHECKLABEL_VAL);
			createOption('tabledel',       _NP_EZCOMMENT2_OP_DROPTABLE,   'yesno', 'no');
			$sql = 'CREATE TABLE IF NOT EXISTS %s ('
				 . '`comid`  int(11) NOT NULL,'
				 . '`secflg` tinyint(1) NULL,'
				 . 'PRIMARY KEY(`comid`) );';
			sql_query(sprintf($sql, sql_table('plug_ezcomment2')));
			$this->updateTable();
		}
	}

	function uninstall()
	{
		if ($this->getOption('tabledel') == 'yes')
			sql_query('DROP TABLE '.sql_table('plug_ezcomment2'));
	}

	function init()
	{
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . 'language/' . $language . '.php')) {
			include_once($this->getDirectory() . 'language/' . $language . '.php');
		} else {
			include_once($this->getDirectory() . 'language/english.php');
		}
	}

	function updateTable()
	{
		$sql = 'SELECT cnumber FROM ' . sql_table('comment') . ' ORDER BY cnumber';
		$res = sql_query($sql);
		$sql = 'INSERT INTO ' . sql_table('plug_ezcomment2') . '(`comid`) VALUES (%d)';
		while ($cid = mysql_fetch_assoc($res)) {
			sql_query(sprintf($sql, $cid['cnumber']));
		}
	}

	function doTemplateVar(&$item,
							$showType       = '',
							$showMode       = '5/1/1',
							$destinationurl = '',
							$formTemplate   = 'EzCommentTemplateDefault',
							$listTemplate   = 'EzCommentTemplateDefault')
	{
		$this->doSkinVar('template', $showType, $showMode, $destinationurl, $formTemplate, $listTemplate, $item);
	}

	function doSkinVar($skinType,
					   $showType       = '',
					   $showMode       = '5/1/1',
					   $destinationurl = '',
					   $formTemplate   = 'EzCommentTemplateDefault',
					   $listTemplate   = 'EzCommentTemplateDefault',
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
		if (!$commentOrder) $commentOrder = 1;
		if (!$formTemplate) $formTemplate = 'EzCommentTemplateDefault';
		if (!$listTemplate) $listTemplate = 'EzCommentTemplateDefault';

		switch ($showType) {
			case 'list':
				$listTemplate = TEMPLATE::read($listTemplate);
				$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder);
				break;
			case 'form':
				$formTemplate = TEMPLATE::read($formTemplate);
				$this->showForm($commentItem, $formTemplate, $destinationurl);
				break;
			default:
				$listTemplate = TEMPLATE::read($listTemplate);
				$formTemplate = TEMPLATE::read($formTemplate);
				if ($sortOrder) {
					$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder);
					$this->showForm($commentItem, $formTemplate, $destinationurl);
				} else {
					$this->showForm($commentItem, $formTemplate, $destinationurl);
					$this->showComment($commentItem, $listTemplate, $maxToShow, $commentOrder);
				}
				break;
		}
	}

// FORM START ---------------------------------------
	function showForm($commentItem, $template, $destinationurl)
	{
		global $CONF, $manager, $member, $catid, $subcatid;
		$bid =  getBlogIDFromItemID($commentItem->itemid);
		$b   =& $manager->getBlog($bid);
		$b->readSettings();
		if (!$member->isLoggedIn() && !$b->commentsEnabled()) {
			return;
		}
		if (stristr($destinationurl, 'action.php') || empty($destinationurl)) {
			if (stristr($destinationurl, 'action.php')) {
				$logMessage = 'actionurl is not longer a parameter on commentform skinvars.'
							. ' Moved to be a global setting instead.';
				ACTIONLOG::add(WARNING, $logMessage);
			}
			if ($catid) {
				$linkparams['catid'] = intval($catid);
			}
			if ($manager->pluginInstalled('NP_MultipleCategories') && $subcatid) {
				$linkparams['subcatid'] = intval($subcatid);
			}
			$destinationurl = createItemLink($commentItem->itemid, $linkparams);
		} else {
			$destinationurl = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $destinationurl);
		}

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
		if ($member && $member->isLoggedIn()) {
			$formType = 'COMMENT_FORM_LOGGEDIN';
			$loginMember = $member->createFromID($member->getID());
			$formdata['membername'] = $this->_hsc($loginMember->getDisplayName());
		} else {
			$formType = 'COMMENT_FORM_NOTLOGGEDIN';
		}
		$contents   = $template[$formType];
		$formAction =& new EzCommentFormActions($commentItem, $formdata, $loginMember);
		$parser     =& new PARSER($formAction->getAllowedActions(), $formAction);
		$parser->parse(&$contents);
	}

// FORM END -----------------------------------------*/

// LIST START ---------------------------------------
	function showComment($commentItem, $template, $maxToShow, $commentOrder)
	{
		global $manager;
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
			   . 's.secflg  as secret'
			   . ' FROM ' . sql_table('comment') . ' as c, '
			   .            sql_table('plug_ezcomment2') . ' as s '
			   . ' WHERE c.citem = ' . intval($commentItem->itemid) . ', '
			   . ' AND   s.comid = c.cnumber '
			   . ' ORDER BY c.ctime '
			   . $order;
		if ($maxToShow) {
			if ($order == "DESC") {
				$query .=' LIMIT ' . intval($maxToShow);
			} else {
				$query .=' LIMIT ' . intval($startnum) . ',' . intval($maxToShow);
			}
		}
		$comments = sql_query($query);
		$viewnum  = mysql_num_rows($comments);
		$actions->setViewnum($viewnum);
		if ($this->getBlogOption($bid, 'secret') == 'yes') {
			$secretMode = true;
			if ($this->flgLoggedin)
				$secretComments = $this->getSecretComments();
		}

		$parser->parse($template['COMMENTS_HEADER']);

		while ( $comment = mysql_fetch_assoc($comments) ) {
			$comment['timestamp'] = strtotime($comment['ctime']);
			if ($comment['secret']) {
				global $member;
				if (!$this->flgLoggedin ||
					( !$menber->blogAdminRights($bid) &&
					  !in_array($comment['commentid'], $secretComments)) )
				{
					$comment['body']   = $this->getBlogOption($bid, 'secComment');
					$comment['userid'] = $b->getURL();
					$comment['email']  = '#';
					$comment['host']   = '127.0.0.1';
					$comment['ip']     = '127.0.0.1';
				}
			}
			$actions->setCurrentComment($comment);
			$manager->notify('PreComment', array('comment' => &$comment));
			$parser->parse($template['COMMENTS_BODY']);
			$manager->notify('PostComment', array('comment' => &$comment));
		}

		$parser->parse($template['COMMENTS_FOOTER']);

		mysql_free_result($comments);

	}
// LIST END -----------------------------------------

	function getTemplateParts()
	{
		$this->languageInclude();
		return array(
			'COMMENT_FORM_LOGGEDIN'    => _NP_EZCOMMENT2_FORM_LOGGEDIN, 
			'COMMENT_FORM_NOTLOGGEDIN' => _NP_EZCOMMENT2_FORM_NOTLOGGEDIN, 
		);
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}
}

class EzCommentFormActions extends ACTIONS
{

var $commentItem;

var $loginMember;

	function EzCommentFormActions(&$item, $formdata, $member)
	{
		$this->ACTIONS('item');
		$this->commentItem =& $item;
		$this->formdata    =  $formdata;
		$this->loginMember =  $member;
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

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

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



