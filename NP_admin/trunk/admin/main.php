<?php

require_once(dirname(__FILE__).'/basic.php');
require_once(dirname(__FILE__).'/skinvars.php');
require_once(dirname(__FILE__).'/actions.php');

class NP_admin_main extends NP_admin_actions{
	function NP_admin_main(){
		return $this->init();
	}
	function &getAdminObject(){
		static $admin;
		if (isset($admin)) return $admin;
		global $DIR_LIBS;
		require_once($DIR_LIBS.'ADMIN.php');
		require_once(dirname(__FILE__).'/handlers/admin.php');
		$admin=new NP_admin_admin();
		return $admin;
	}
	function &getHandler($type,$id=0){
		static $cache;
		if (isset($cache[$type][$id])) return $cache[$type][$id];
		$admin=&$this->getAdminObject();
		switch($type){
			case 'blog':
				require_once(dirname(__FILE__)."/handlers/blog.php");
				$cache[$type][$id]=new NP_admin_blog($admin,$id);
				return $cache[$type][$id];
			case 'category':
				require_once(dirname(__FILE__)."/handlers/category.php");
				$cache[$type][$id]=new NP_admin_category($admin,$id);
				return $cache[$type][$id];
			case 'comment':
				require_once(dirname(__FILE__)."/handlers/comment.php");
				$cache[$type][$id]=new NP_admin_comment($admin,$id);
				return $cache[$type][$id];
			case 'global':
				require_once(dirname(__FILE__)."/handlers/global.php");
				$cache[$type][$id]=new NP_admin_global($admin,$id);
				return $cache[$type][$id];
			case 'item':
				require_once(dirname(__FILE__)."/handlers/item.php");
				$cache[$type][$id]=new NP_admin_item($admin,$id);
				return $cache[$type][$id];
			case 'member':
				require_once(dirname(__FILE__)."/handlers/member.php");
				$cache[$type][$id]=new NP_admin_member($admin,$id);
				return $cache[$type][$id];
			default:
				exit('ERROR-getHandler');
		}
	}
	function doSkinVar() {
		$args=func_get_args();
		array_shift($args);
		$method=array_shift($args);
		if (strlen($method)==0) $method='admin';
		if (!method_exists($this,"var_$method")) {
			echo htmlspecialchars("<%admin($method)%>",ENT_QUOTES);
			return;
		}
		return call_user_func_array(array($this,"var_$method"),$args);
	}
	var $skin,$parser,$handler;
	function selector(){
		global $manager;
//TODO: show error when headers already sent out
//TODO: support several skins
//TODO: support action
		selectSkin('admin');
		global $skinid;
		$skin = new SKIN($skinid);
		
		if (!$skin->isValid) {
			doError(_ERROR_NOSUCHSKIN);
		}
		
		// Construct a blog object if 'blogid' is set
		global $blog;
		$blogid=intRequestVar('blogid');
		if ($blogid) $blog=$manager->getBlog($blogid);
		
		// parse the skin
		$skin->parse('index');
	}
	var $action;
	function event_InitSkinParse(&$data){
		global $manager,$member;
		$skin=&$data['skin'];
		// Check the actions.
		$action=getVar('action');
		if (!$member->isLoggedIn()) {
			//TODO: support pass through
			$action='showlogin';
		}
		switch($action){
			case '':
				$action='overview';
				break;
			// Actions that do not require valid ticket.
			case 'showlogin': case 'overview': case 'itemlist': case 'blogcommentlist': 
			case 'bookmarklet': case 'blogsettings': case 'banlist': case 'deleteblog': 
			case 'editmembersettings': case 'browseownitems': case 'browseowncomments': 
			case 'createitem': case 'itemedit': case 'itemmove': case 'categoryedit': 
			case 'categorydelete': case 'manage': case 'actionlog': case 'settingsedit': 
			case 'backupoverview': case 'pluginlist': case 'createnewlog': case 'usermanagement': 
			case 'skinoverview': case 'templateoverview': case 'skinieoverview': case 'itemcommentlist': 
			case 'commentedit': case 'commentdelete': case 'banlistnewfromitem': case 'banlistdelete': 
			case 'itemdelete': case 'manageteam': case 'teamdelete': case 'banlistnew': case 'memberedit': 
			case 'memberdelete': case 'pluginhelp': case 'pluginoptions': case 'plugindelete': 
			case 'skinedittype': case 'skinremovetype': case 'skindelete': case 'skinedit': 
			case 'templateedit': case 'templatedelete': case 'activate':
				break;
			case 'login': 
				$action='overview'; //TODO: Is this right way?
				break;
			// Additional actions
			case 'forgotpassword':
				//TODO: Currently the process doesn't come here because of not logged in.
				break;
			// Actions that require a valid ticket.
			case 'additem': case 'itemupdate': case 'itemmoveto': case 'categoryupdate': 
			case 'categorydeleteconfirm': case 'itemdeleteconfirm': case 'commentdeleteconfirm': 
			case 'teamdeleteconfirm': case 'memberdeleteconfirm': case 'templatedeleteconfirm': 
			case 'skindeleteconfirm': case 'banlistdeleteconfirm': case 'plugindeleteconfirm': 
			case 'batchitem': case 'batchcomment': case 'batchmember': case 'batchcategory': 
			case 'batchteam': case 'regfile': case 'commentupdate': case 'banlistadd': 
			case 'changemembersettings': case 'clearactionlog': case 'settingsupdate': 
			case 'blogsettingsupdate': case 'categorynew': case 'teamchangeadmin': case 'teamaddmember': 
			case 'memberadd': case 'addnewlog': case 'addnewlog2': case 'backupcreate': 
			case 'backuprestore': case 'pluginup': case 'plugindown': case 'pluginupdate': 
			case 'pluginadd': case 'pluginoptionsupdate': case 'skinupdate': case 'skinclone': 
			case 'skineditgeneral': case 'templateclone': case 'templatenew': case 'templateupdate': 
			case 'skinieimport': case 'skinieexport': case 'skiniedoimport': case 'skinnew': 
			case 'deleteblogconfirm': case 'sendping': case 'rawping': case 'activatesetpwd':
				if (!$manager->checkTicket()) {
					//TODO: support pass through
					$this->msg=_ERROR_BADTICKET;
					$action='error';
				} else {
					// TODO: save something to DB here.
					// Possible redirect to overview page or appropriate normal page?
				}
				break;
			default:
				$this->msg='Invalid action';
				$action='error';
				break;
		}
		$this->action=$action;
	}
}