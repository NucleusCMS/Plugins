<?php
class NP_Mediatocu extends NucleusPlugin
{

	function NP_Mediatocu()
	{
//		$this->baseUrl = $this->getAdminURL();
		global $CONF;
		$this->baseUrl = str_replace ($CONF['AdminURL'], '', $this->getAdminURL()); 
	}

	function getName()
	{
		return 'mediatocu';
	}

	function getAuthor()
	{
		return 'yamamoto,shizuki, Nucleus JP team, and Cacher';
	}

	function getURL()
	{
//		return '';
		return 'http://japan.nucleuscms.org/wiki/plugins:np_mediatocu';//2008-11-10 cacher
	}

	function getVersion()
	{
		return '1.0.8.1 SP1 RC6';
	}

	function getDescription()
	{
		return _MEDIA_PHP_37;
	}

	function supportsFeature($w)
	{
		return ($w == 'SqlTablePrefix') ? 1 : 0;
	}



/**2005.3--2005.09.26 00:50 keiei edit
	* media-tocu3.01.zip  for register_globals=off
	*
	*/
/**
	*	media-tocu3.02.zip
	* T.Kosugi edit 2006.8.22 for security reason
	*/
/**
	*	media-tocu-dirs1.0.zip
	*		extends media-tocu3.02.zip
	* 1.0.7 m17n and making to plugin. by yamamoto
	* 1.0.6 to put it even by the thumbnail image click, small bug.  by yamamoto
	* 1.0.5 to put it even by the thumbnail image click, it remodels it.  by yamamoto
	* 1.0.4 bug fix mkdir if memberdir is missing incase mkdir
	* 1.0.3 bug fix missing memberdir in uploading file.
	* 1.0.2 add checking filname with null
	* 1.0.1 add first dir check
	*
	*/
// add language definition by yamamoto



	function install()
	{
		$this->createOption(
			'thumb_width',
			_MEDIA_PHP_17,
			'text',
			'60',
			'datatype=numerical'
		);
		$this->createOption(
			'thumb_height',
			_MEDIA_PHP_18,
			'text',
			'45',
			'datatype=numerical'
		);
		$this->createOption(
			'thumb_quality',
			_MEDIA_PHP_19,
			'text',
			'70',
			'datatype=numerical'
		);
		$this->createOption(
			'media_per_page',
			_MEDIA_PHP_22,
			'text',
			'9',
			'datatype=numerical'
		);
		$this->createOption(
			'popup_width',
			_MEDIA_PHP_23,
			'text',
			'600',
			'datatype=numerical'
		);//restore 2008-05-14 Cacher
		$this->createOption(
			'popup_height',
			_MEDIA_PHP_24,
			'text',
			'600',
			'datatype=numerical'
		);//restore 2008-05-14 Cacher
		$this->createOption(
			'paste_mode_checked',
			_MEDIA_PHP_27,
			'yesno',
			'no'
		);
		$this->createOption(
			'hidden_dir',
			_MEDIA_PHP_43,
			'text',
			'thumb,thumbnail,phpthumb'
		);
		$this->createOption(
			'filename_rule',
			_MEDIA_PHP_33,
			'select',
			'default',
			_MEDIA_PHP_34 . '|default|' . _MEDIA_PHP_35 . '|ascii'
		);
		$this->createOption(
			'use_gray_box',
			_MEDIA_PHP_36,
			'yesno',
			'yes'
		);
	}

	/**
	  * Add extra header
	  */
	function _addExtraHead(&$data)
	{
		global $member;
		$this->memberid = $member->id;
		$this->memberadmin = $member->admin;
		$this->_getExtraHead($data);
	}

//	function unInstall() {}
//2008-11-10 cacher

	function init()
	{
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory() . 'lang/' . $language . '.php')) {
			include_once($this->getDirectory() . 'lang/' . $language . '.php');
		} else {
			include_once($this->getDirectory() . 'lang/english.php');
		}
	}

	function getEventList()
	{
		return array(
			'AdminPrePageHead',
			'BookmarkletExtraHead',
			'PreSendContentType'
		);
	}

	function event_BookmarkletExtraHead(&$data)
	{
		$this->_addExtraHead($data['extrahead']);
	}

	function event_AdminPrePageHead(&$data)
	{
		global $member;
		$action = $data['action'];
		if (($action != 'createitem') && ($action != 'itemedit')) {
			return;
		}
		$this->_addExtraHead($data['extrahead']);
	}

	function event_PreSendContentType(&$data)
	{
		$pageType = $data['pageType'];
		if (	($pageType == 'bookmarklet-add')
			||	($pageType == 'bookmarklet-edit')
			||	($pageType == 'admin-createitem')
			|| 	($pageType == 'admin-itemedit')
		   ) {
			if ($data['contentType'] == 'application/xhtml+xml') {
				$data['contentType'] = 'text/html';
			}
		}
	}

	function _getExtraHead(&$extrahead)
	{
		global $CONF, $manager, $itemid, $blogid ;
		if ($manager->pluginInstalled('NP_TinyMCE')) return;
		$mediatocu    = $manager->getPlugin('NP_Mediatocu');
		$popup_width  = $mediatocu->getOption('popup_width');//restore 2008-05-14 Cacher
		$popup_height = $mediatocu->getOption('popup_height');//restore 2008-05-14 Cacher
		$mediaPhpURL  = $CONF['AdminURL'] . $this->baseUrl . 'media.php';
//		$GreyBox      = $mediatocu->getOption('use_gray_box');
		if ($mediatocu->getOption('use_gray_box') == 'yes') {
		$extrahead .= <<<_EXTRAHEAD_

	<script type="text/javascript" src="plugins/mediatocu/greybox/AJS.js"></script>
	<script type="text/javascript" src="plugins/mediatocu/greybox/AJS_fx.js"></script>
	<script type="text/javascript">
		var GB_ROOT_DIR = "./plugins/mediatocu/greybox/";
	</script>
	<script type="text/javascript" src="plugins/mediatocu/greybox/gb_scripts.js"></script>
	<link href="plugins/mediatocu/greybox/gb_styles.css" rel="stylesheet" type="text/css" media="all" />
	<style TYPE="text/css">
		#GB_window td {
			border : none;
			background : url(plugins/mediatocu/greybox/header_bg.gif);
		}
	</style>
	<script type="text/javascript">
		function addMedia() {
			GB_showFullScreen('Mediatocu', '{$mediaPhpURL}');
		}
	</script>

_EXTRAHEAD_;
		} else {
		$extrahead .= <<<_EXTRAHEAD_

	<script type="text/javascript">
		function addMedia() {
			window.open('{$mediaPhpURL}', "name" , "width=$popup_width , height=$popup_height , scrollbars=yes , resizable=yes" );
		}
	</script>

_EXTRAHEAD_;
		}
	}
}
?>
