<?php
/*
	ver 0.05 : if
	           doIf
	           Content-Type text/html
	ver 0.04 : wysiwyg
	ver 0.03 : ADMIN
	ver 0.02 : NP_ResetAdminCSS
	ver 0.01 : initial release
*/
class NP_znCustomAdmin extends NucleusPlugin {
	function getName()              { return 'znCustomAdmin'; }
	function getAuthor()            { return ''._ZNCA1.''; }
	function getURL()               { return 'http://wa.otesei.com/NP_znCustomAdmin'; }
	function getVersion()           { return '0.05'; }
	function supportsFeature($w)    { return ($w == 'SqlTablePrefix') ? 1 : 0; }
	function getDescription()       { return 'Admin'._ZNCA2.''; }
	function getEventList()
	{
		return array(
			'AdminPrePageHead', 
			'PreSendContentType' // we need to force text/html instead of application/xhtml+xml
		);
	}
	function init()
	{
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		$incFile  = (file_exists($this->getDirectory().$language.'.php')) ? $language : 'english';
		include_once($this->getDirectory().$incFile.'.php');
		$this->language = $incFile;
	}
	function install()
	{
		$this->createOption('custom_flag', ''._ZNCA3.'', "yesno", 'yes');
		$this->createOption('adminskin'  , 'Admin'._ZNCA4.'', 'select', 'helium', 'default||helium|helium'); //|||
		
		//option
		$this->createBlogOption('admin_add' , ''._ZNCA5.' (admin)', 'select', 'helium', 'default||helium|helium'); //|||
		$this->createBlogOption('admin_edit', ''._ZNCA6.' (admin)', 'select', 'helium', 'default||helium|helium'); //|||
	}
	function event_AdminPrePageHead($data)
	{
		global $manager;
		$this->action = $data['action']; //
		
		//
		$refreshOptionAction = array(
			'pluginoptions', 
			'blogsettings'
		);
		if (in_array($data['action'], $refreshOptionAction))
		{
			$qid = mysql_query("SELECT tdname FROM ".sql_table('template_desc')." WHERE tddesc='znCustomAdmin'");
			$skinSelectStr = 'default|';
			while($row = mysql_fetch_array($qid)) $skinSelectStr .= '|'.$row['tdname'].'|'.$row['tdname'];
			$skinOid = $this->getPluginOptionID('adminskin');
			$addOid  = $this->getPluginOptionID('admin_add');
			$editOid = $this->getPluginOptionID('admin_edit');
			mysql_query("UPDATE ".sql_table("plugin_option_desc")." SET oextra='".addslashes($skinSelectStr)."' WHERE oid=".intval($skinOid));
			mysql_query("UPDATE ".sql_table("plugin_option_desc")." SET oextra='".addslashes($skinSelectStr)."' WHERE oid=".intval($addOid ));
			mysql_query("UPDATE ".sql_table("plugin_option_desc")." SET oextra='".addslashes($skinSelectStr)."' WHERE oid=".intval($editOid));
		}
	}
	function event_PreSendContentType($data)
	{
		//helium skin JavaScriptapplication/xhtml+xml
		if ($data['contentType'] == 'application/xhtml+xml') $data['contentType'] = 'text/html';
	}
	function doIf($para1='', $para2 = '')
	{
		//action
		return ($this->action == strtolower($para1));
	}
	//
	//oid
	//
	function getPluginOptionID($name)
	{
		return quickQuery("SELECT oid AS result FROM ".sql_table('plugin_option_desc')." WHERE opid=".intval($this->getID())." AND oname='".addslashes($name)."'");
	}
}

/* 
 * 
 */
class ADMINFACTORY extends BaseActions
{
	var $template;
	
	function AllowedActions()
	{
		return array(
			'if',
			'else',
			'endif',
			'elseif',
			'ifnot',
			'elseifnot',
			'charset',              //_CHARSET
			'sitename',             //$CONF['SiteName']
			'adminurl',             //baseUrl
			'extrahead',            //$extrahead
			'membername',           //$member->getDisplayName()
			'indexurl',             //$CONF['IndexURL']
			'nucleusversion',       //getNucleusVersion()
			'nucleuspatchlevel',    //getNucleusPatchLevel()
			'nucleusversionstring', //$nucleus['version']
			'quickmenu',            //
			'skinfile',             //
			'donate',               //donate
			'thisyear',             //
		);
	}
	function checkCondition($field, $name='', $value = '')
	{
		global $member, $manager;
		
		$condition = 0;
		switch($field) {
				
			case 'loggedin':
				$condition = $this->_ifMember($name);
				break;
			case 'hasplugin':
				$condition = $this->_ifHasPlugin($name, $value);
				break;
			default:
				$condition = $manager->pluginInstalled('NP_' . $field) && $this->_ifPlugin($field, $name, $value);
				break;
		}
		return $condition;
	}
	function _ifMember($name = '')
	{
		global $member;
		switch ($name)
		{
			case 'superadmin':
				$condition = $member->isLoggedIn() && $member->isAdmin();
				break;
			case '':
				$condition = $member->isLoggedIn();
				break;
		}
		return $condition;
	}
	function _ifHasPlugin($name, $value)
	{
		global $manager;
		$condition = false;
		// (pluginInstalled method won't write a message in the actionlog on failure)
		if ($manager->pluginInstalled('NP_'.$name)) {
			$plugin =& $manager->getPlugin('NP_' . $name);
			if ($plugin != NULL) {
				if ($value == "") {
					$condition = true;
				} else {
					list($name2, $value2) = explode('=', $value, 2);
					if ($value2 == "" && $plugin->getOption($name2) != 'no') {
						$condition = true;
					} else if ($plugin->getOption($name2) == $value2) {
						$condition = true;
					}
				}
			}
		}
		return $condition;
	}
	function _ifPlugin($name, $key = '', $value = '')
	{
		global $manager;

		$plugin =& $manager->getPlugin('NP_' . $name);
		if (!$plugin) return;

		$params = func_get_args();
		array_shift($params);

		return call_user_func_array(array(&$plugin, 'doIf'), $params);
	}
	function setTemplate(&$template)
	{
		$this->template = $template;
	}
	function parse_charset()
	{
		echo _CHARSET;
	}
	function parse_sitename()
	{
		global $CONF;
		echo htmlspecialchars($CONF['SiteName']);
	}
	function parse_adminurl()
	{
		global $CONF;
		echo htmlspecialchars($CONF['AdminURL']);
	}
	function parse_extrahead()
	{
		echo $this->template['extrahead'];
	}
	function parse_membername()
	{
		global $member;
		echo $member->getDisplayName();
	}
	function parse_indexurl()
	{
		global $CONF;
		echo $CONF['IndexURL'];
	}
	function parse_nucleusversion()
	{
		echo getNucleusVersion();
	}
	function parse_nucleuspatchlevel()
	{
		echo getNucleusPatchLevel();
	}
	function parse_nucleusversionstring()
	{
		global $nucleus;
		echo $nucleus['version'];
	}
	function parse_quickmenu()
	{
		global $action, $member, $manager;
		$qmenu = $this->template['qmenu'];
		
		if (($action != 'showlogin') && ($member->isLoggedIn())) {
			echo $this->template['sqmenuhead'];
			
			$aPluginExtras = array();
			$manager->notify(
				'QuickMenu',
				array(
					'options' => &$aPluginExtras
				)
			);
			if (count($aPluginExtras) > 0)
			{
				foreach ($aPluginExtras as $aInfo)
				{
					//echo '<li><a href="'.htmlspecialchars($aInfo['url']).'" title="'.htmlspecialchars($aInfo['tooltip']).'">'.htmlspecialchars($aInfo['title']).'</a></li>';
					//<li><a href="<%url%>" title="<%tooltip%>"><%title%></a></li>
					$qInfo = array(
						'url'     => htmlspecialchars($aInfo['url']    , ENT_QUOTES),
						'tooltip' => htmlspecialchars($aInfo['tooltip'], ENT_QUOTES),
						'title'   => htmlspecialchars($aInfo['title']  , ENT_QUOTES),
					);
					echo TEMPLATE::fill($qmenu, $qInfo);
				}
			}
			
			echo $this->template['sqmenufoot'];
		}
	}
	function parse_skinfile($filename)
	{
		global $CONF;
		echo $CONF['SkinsURL'] . $this->template['IncludePrefix'] . $filename;
	}
	function parse_donate($linktext = '')
	{
		$u        = 'http://nucleuscms.org/donate.php';
		$linktext = htmlspecialchars($linktext);
		$l = ($linktext) ? '<a href="'.$u.'" title="'.$linktext.'">'.$linktext.'</a>' : $u;
		echo $l;
	}
	function parse_thisyear()
	{
		echo date('Y');
	}
}

/* 
 * 
 */
class CUSTOMPAGEFACTORY extends PAGEFACTORY
{
	var $template;
	var $method;
	
	//
	//
	//
	function init($content, $method)
	{
		$this->template      = $content;
		$this->method        = $method;
		$this->actions       = array_merge($this->actions, $this->getOriginalActions());
		$this->ifexImgJsFlag = FALSE;
	}
	//
	//
	//
	function getTemplateFor($type)
	{
		return $this->template;
	}
	//
	//
	//
	function getOriginalActions()
	{
		//
		return array(
			'defaultcategory',       //hidden
			'currentblogcategories', // (showNewCatFlag, tabindex)
			'wysiwyg',               //wysiwygJavaScript
			'pluginform',            //
			'pluginitemoption',      //
			'znitemfieldex',         //NP_znItemFieldEX
			'znitemfieldexpresence', //NP_znItemFieldEX
		);
	}
	//
	//
	//
	function parse_wysiwyg($id)
	{
		$id = preg_replace('/[\'"<>]/', '', $id);
		echo '<script language="JavaScript">generate_wysiwyg("'.$id.'");</script>';
	}
	//
	//
	//
	function parse_pluginform($plugName)
	{
		global $manager;
			
		switch ($this->method) {
			case 'add':
				if (!$manager->pluginInstalled($plugName)) return;
				$plugin = & $manager->getPlugin($plugName);
				if (method_exists($plugin, 'event_AddItemFormExtras')) {
					call_user_func(
						array(&$plugin, 'event_AddItemFormExtras'), 
						array(
							'blog' => &$this->blog
						)
					);
				}
				break;
			case 'edit':
				if (!$manager->pluginInstalled($plugName)) return;
				$plugin = & $manager->getPlugin($plugName);
				if (method_exists($plugin, 'event_EditItemFormExtras')) {
					call_user_func(
						array(&$plugin, 'event_EditItemFormExtras'), 
						array(
							'variables' => $this->variables,
							'blog' => &$this->blog,
							'itemid' => $this->variables['itemid']
						)
					);
				}
				break;
		}
	}
	//
	//
	//
	function parse_pluginitemoption($plugName)
	{
		global $itemid, $manager;
		if (!$manager->pluginInstalled($plugName)) return;
		
		$context = 'item';
		$contextid = $itemid;
		// get all current values for this contextid
		// (note: this might contain doubles for overlapping contextids)
		$aIdToValue = array();
		$res = sql_query('SELECT oid, ovalue FROM ' . sql_table('plugin_option') . ' WHERE ocontextid=' . intval($contextid));
		while ($o = mysql_fetch_object($res)) {
			$aIdToValue[$o->oid] = $o->ovalue;
		}
		// get list of oids per pid
		$query = 'SELECT * FROM ' . sql_table('plugin_option_desc') . ',' . sql_table('plugin')
			   . ' WHERE opid=pid and ocontext=\''.addslashes($context).'\' and pfile=\''.$plugName.'\' ORDER BY porder, oid ASC';
		$res = sql_query($query);
		$aOptions = array();
		while ($o = mysql_fetch_object($res)) {
			if (in_array($o->oid, array_keys($aIdToValue)))
				$value = $aIdToValue[$o->oid];
			else
				$value = $o->odef;
			array_push($aOptions, array(
				'pid' => $o->pid,
				'pfile' => $o->pfile,
				'oid' => $o->oid,
				'value' => $value,
				'name' => $o->oname,
				'description' => $o->odesc,
				'type' => $o->otype,
				'typeinfo' => $o->oextra,
				'contextid' => $contextid,
				'extra' => ''
			));
		}
		global $manager;
		$manager->notify('PrePluginOptionsEdit',array('context' => $context, 'contextid' => $contextid, 'options'=>&$aOptions));
		$iPrevPid = -1;
		foreach ($aOptions as $aOption) {
			// new plugin?
			if ($iPrevPid != $aOption['pid']) {
				$iPrevPid = $aOption['pid'];
			}
			echo '<tr>';
			listplug_plugOptionRow($aOption);
			echo '</tr>';
		}
	}
	//
	//
	//
	function parse_znitemfieldex($fname)
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_znItemFieldEX')) return;
		$znItemFieldEX = & $manager->getPlugin('NP_znItemFieldEX');
		
		$itemid = intRequestVar('itemid'); //0
		$tname  = "item_b".intval($this->blog->getID());
		
		//
		$sql_str = "SELECT * FROM ".$znItemFieldEX->table_table.$tname." WHERE id=".$itemid;
		$qid = mysql_query($sql_str);
		if ($qid and @mysql_num_rows($qid) > 0) $row_item = mysql_fetch_array($qid);
		
		$ftid = $znItemFieldEX->getIDFromTableName($tname);
		$sql_str = "SELECT * FROM ".$znItemFieldEX->table_fields." WHERE ftid='".$ftid."' AND fname='".$fname."' ORDER BY forder";
		$qid = mysql_query($sql_str);
		$row = mysql_fetch_array($qid);
		echo ($row) ? '' : ''._ZNCA7.'<br />';
		if ($row["ftype"] == 'Image' && $this->ifexImgJsFlag === FALSE) {
			$znItemFieldEX->printImgJs();
			$this->ifexImgJsFlag = TRUE;
		}
		
		$znItemFieldEX->EXFieldForm($row, $row_item, 10000);
	}
	//
	//
	//
	function parse_znitemfieldexpresence() //NP_znItemFieldEX
	{
		global $manager;
		if (!$manager->pluginInstalled('NP_znItemFieldEX')) return;
		$znItemFieldEX = & $manager->getPlugin('NP_znItemFieldEX');
		
		$itemid = intRequestVar('itemid'); //0
		$tname  = "item_b".intval($this->blog->getID());
		$znItemFieldEX->EXFieldPresenceForm($tname, $itemid);
	}
	//
	//
	//
	function parse_defaultcategory()
	{
		$catid = intval($this->blog->getDefaultCategory());
		echo '<input type="hidden" name="catid" value="'.$catid.'" />';
	}
	//
	//
	//
	function parse_currentblogcategories($showNewCat = 0, $startidx = 0)
	{
		if ($this->variables['catid']) {
			$catid = $this->variables['catid'];         // on edit item
		} else {
			$catid = $this->blog->getDefaultCategory(); // on add item
		}
		$this->selectCategory('catid', $catid, $startidx, $showNewCat, $this->blog->getID());
	}
	//
	//
	//
	function selectCategory($name, $selected = 0, $tabindex = 0, $showNewCat = 0, $iForcedBlogInclude)
	{
		global $member;
		echo '<select name="',$name,'" tabindex="',$tabindex,'">';
		//
		if ($showNewCat) {
			if ($member->blogAdminRights($iForcedBlogInclude))
				echo '<option value="newcat-',$iForcedBlogInclude,'">',_ADD_NEWCAT,'</option>';
		}
		$categories = sql_query('SELECT cname, catid FROM '.sql_table('category').' WHERE cblog=' . $iForcedBlogInclude . ' ORDER BY cname ASC');
		while ($oCat = mysql_fetch_object($categories)) {
			if ($oCat->catid == $selected)
				$selectText = ' selected="selected" ';
			else
				$selectText = '';
			echo '<option value="',$oCat->catid,'" ', $selectText,'>',htmlspecialchars($oCat->cname),'</option>';
		}
		echo '</select>';
	}
}
?>