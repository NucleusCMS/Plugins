<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_Paint ($Revision: 1.273 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * 
  * $Id: NP_Paint.php,v 1.273 2010/06/06 11:44:19 hsur Exp $
*/

/*
  * Copyright (C) 2005-2010 CLES. All rights reserved.
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

if (!class_exists('NucleusPlugin')) exit;
define("NP_PAINT_IMAGE_NAME", "pbbsimage");
define("NP_PAINT_TICKET_TABLE", "plugin_paint_tickets");
define("NP_PAINT_TICKET_LIFETIME", 86400);

global $DIR_LIBS;
require_once($DIR_LIBS . 'MEDIA.php');

// load class
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('cles/Template.php');
require_once(dirname(__FILE__).'/paint/PaintPlugin.php');

class NP_Paint extends NucleusPlugin {

	// name of plugin
	function getName() {
		return 'Paint';
	}

	// author of plugin
	function getAuthor() {
		return 'hsur';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/5';
	}

	// version of the plugin
	function getVersion() {
		return '1.18.0';
	}
	
	function hasAdminArea() {
		return 1;
	}

	function install() {
		// FORM default
		$this->createOption('defaultWidth', _PAINT_defaultWidth, 'text', '300', 'numerical=true');		
		$this->createOption('defaultHeight', _PAINT_defaultHeight, 'text', '300', 'numerical=true');		
		$this->createOption('defaultAnimation', _PAINT_defaultAnimation, 'yesno', 'yes');		
		$this->createOption('defaultApplet', _PAINT_defaultApplet, 'select', '', '');
		$this->createOption('defaultPalette', _PAINT_defaultPalette, 'select', '', '');

		$this->createOption('defaultImgType', _PAINT_defaultImgType, 'select', 'AUTO', 'AUTO|AUTO|JPG|JPG|PNG|PNG');
		$this->createOption('defaultImgCompress', _PAINT_defaultImgCompress, 'text', '15', 'numerical=true');		
		$this->createOption('defaultImgDecrease', _PAINT_defaultImgDecrease, 'text', '60', 'numerical=true');		
		$this->createOption('defaultImgQuality', _PAINT_defaultImgQuality, 'text', '75', 'numerical=true');		

		$this->createOption('defaultAppletQuality', _PAINT_defaultAppletQuality, 'select', '1', '1|1|2|2|3|3|4|4|5|5');

		$this->createOption('bodyTpl', _PAINT_bodyTpl, 'textarea', '<%paint(<%url%>|<%w%>|<%h%>|'.NP_PAINT_IMAGE_NAME.')%>');
		$this->createOption('tagTpl', _PAINT_tagTpl, 'textarea', '<%img%><a href="<%viewer%>" onclick="window.open(this.href, \'popupwindow\', \'width=400, height=400, scrollbars, resizable\'); return false; " >'._PAINT_STAR.'Anime</a>');
		$this->createOption('imageTpl', _PAINT_imageTpl, 'textarea', '<%image(<%url%>|<%w%>|<%h%>|<%alt%>)%><%continue%>');
		$this->createOption('continueTpl', _PAINT_continueTpl, 'textarea', '<br /><a onclick="window.open(this.href, \'popupwindow\', \'width=400, height=400, scrollbars, resizable\'); return false;" href="<%actionurl%>">'._PAINT_STAR.'Continue</a>');
		// debug
		$this->createOption('debug', _PAINT_debug, 'yesno', 'no');
		
		// for authentication
		sql_query("
			CREATE TABLE IF NOT EXISTS 
			".sql_table(NP_PAINT_TICKET_TABLE).'
			(
				`ticket` CHAR(40) NOT NULL,
				`member` INT(11) NOT NULL,
				`generated` TIMESTAMP,
				PRIMARY KEY (`ticket`, `member`),
				INDEX genarated_idx (`generated` DESC)
			)
		');
	}
	
	function unInstall() {
		sql_query("DROP TABLE ".sql_table(NP_PAINT_TICKET_TABLE));
	}
	
	function getMinNucleusVersion() { return 330; }
	function getMinNucleusPatchLevel() { return 0; }

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return '[$Revision: 1.273 $]<br />' . _PAINT_DESCRIPTION;
	}

	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
//			case 'HelpPage':
				return 1;
			default :
				return 0;
		}
	}
	
	function getEventList() {
		return array('PrePluginOptionsEdit', 'PreItem');
	}
	
	function event_PrePluginOptionsEdit(&$data) {
		$this->_loadPlugin();
		$trimChar = array(
			'=' => '',
			'|' => '',
			';' => '',
		);

		switch($data['context']){
			case 'global':
				if( $data['plugid'] != $this->getID() ) return ;
				
				$appletTypeinfo = '';
				foreach($this->applets as $key => $applet){
					if($appletTypeinfo) $appletTypeinfo .= '|';
					$applet->name = strtr($applet->name, $trimChar);
					$key = strtr($key, $trimChar);
					$appletTypeinfo .= "{$applet->name}|{$key}";
				}
				$paletteTypeinfo = '';
				foreach($this->palettes as $key => $palette){
					if($paletteTypeinfo) $paletteTypeinfo .= '|';
					$palette->name = strtr($palette->name, $trimChar);
					$key = strtr($key, $trimChar);
					$paletteTypeinfo .= "{$palette->name}|{$key}";
				}
				foreach($data['options'] as $oid => $option ){
					switch($data['options'][$oid]['name']){
						case 'defaultApplet':
							$data['options'][$oid]['typeinfo'] = $appletTypeinfo;
							break;
						case 'defaultPalette':
							$data['options'][$oid]['typeinfo'] = $paletteTypeinfo;
							break;
					}
				}
								
				break;
			default:
				// nothing	
		}
	}

/*
	function doItemVar($item, $arg){
		$arr[1] = $arg;
		echo $this->_convertPaint($arr);
	}
*/

	function event_PreItem($data) {
		$this->_loadPlugin();
		$preg_expr = "#<\%paint\((.*?)\)%\>#i";
		$this->currentItem = &$data["item"];
		
		$this->currentItem->body = preg_replace_callback($preg_expr, array(&$this, '_convertPaint'), $this->currentItem->body);
		$this->currentItem->more = preg_replace_callback($preg_expr, array(&$this, '_convertPaint'), $this->currentItem->more);
	}
	
	function _convertPaint($matches) {
		global $CONF, $DIR_MEDIA, $member;
		list($url, $w, $h, $alt) = explode("|", $matches[1], 4);
		
		if (strstr($url,'/'))
			$collection = intval(dirname($url));
		else
			$collection = intval($this->currentItem->authorid);
			
		$vars = array (
			'w' => intval($w),
			'h' => intval($h),
			'url' => $url,
			'alt' => $alt,
		);
		
		$basename = basename($url, '.jpg');
		$basename = basename($basename, '.png');
		foreach( $this->viewers as $key => $viewer ){
			foreach( $viewer->animeExt as $ext  ){
				$file = $DIR_MEDIA . $collection . '/' . $basename . '.' . $ext;
				if( file_exists( $file ) ){
					$vars['viewer'] = htmlspecialchars($CONF['ActionURL'] . "?action=plugin&name=Paint&type=viewer&w=".$vars['w']."&h=".$vars['h']."&file={$collection}/{$basename}.{$ext}&viewer=$key", ENT_QUOTES);
					break 2;
				}
			}
		}
		
		if ( $member->isLoggedIn() && $member->getID() == $collection && $this->viewers['continue'] ){
			$vars['actionurl'] = htmlspecialchars($CONF['ActionURL'] . "?action=plugin&name=Paint&type=viewer&w=".$vars['w']."&h=".$vars['h']."&file=".$url."&viewer=continue", ENT_QUOTES);
			$vars['continue'] = TEMPLATE :: fill($this->getOption('continueTpl'), $vars);
		}
		
		$vars['img'] = TEMPLATE :: fill($this->getOption('imageTpl'), $vars);
		
		if( $vars['viewer'] )
			return TEMPLATE :: fill($this->getOption('tagTpl'), $vars);			
		return $vars['img'];
	}
			
	function init(){
		$this->template =& new cles_Template(dirname(__FILE__).'/paint/template');
		
		$this->parsers = Array();
		$this->applets = Array();
		$this->palettes = Array();
		$this->viewers = Array();
		$this->files = Array();
		$this->disabledPlugin = Array();
		$this->isPluginLoaded = false;
		
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
			@ include_once($this->getDirectory().'language/'.$language.'.php');
	}
		
	function doAction($type) {
		@ini_set('display_errors', 0);
		//@ini_set('error_reporting', 0);
		
		global $member;
		$this->_loadPlugin();
		header('Pragma: no-cache');
		header('Expires: 0');
		
		switch ($type) {
			case 'postimg':
				$err = $this->_postimg(requestVar('parser'));
				
				if($err){
					$this->_warn('postimg Err: ' . $err);
					$file = $line = '';
					if( ! headers_sent($file, $line) ){
						header("HTTP/1.0 500 Internal Server Error");
						header("Status: 500 Internal Server Error");
						echo 'postimg Err: ' . mb_convert_encoding($err, 'UTF-8', _CHARSET);
					} else {
						$this->_warn(_PAINT_HeadersAlreadySent . "file:$file, line:$line" );
						echo "error\n" . mb_convert_encoding($err, "UTF-8", _CHARSET);
					}
				}
				// always no err
				return '';
				break;

			case 'applet':
				if ( ! $member->isLoggedIn() )
					return _PAINT_NeedLogin;
				return $this->_showApplet();
				break;
				
			case 'postsuccess':
				if ( ! $member->isLoggedIn() )
					return _PAINT_NeedLogin;
				
				if( intRequestVar('ow') ){
					$this->_showOk();
					return '';
				}
				
				return $this->_showItemAddForm();
				break;
			
			case 'viewer':
				return $this->_showViewer();
				break;

			case 'plugin':
				echo 'NP_Paint: ' . $this->getVersion() . ' ($Id: NP_Paint.php,v 1.273 2010/06/06 11:44:19 hsur Exp $)<br />';
				foreach( $this->parsers as $key => $name){
					echo 'Parser: ' . $key . ' (' . $name->id .  ')<br />';
				}
				foreach( $this->applets as $key => $name){
					echo 'Applet: ' . $key . ' (' . $name->id .  ')<br />';
				}
				foreach( $this->palettes as $key => $name){
					echo 'Palette: ' . $key . ' (' . $name->id .  ')<br />';
				}
				foreach( $this->viewers as $key => $name){
					echo 'Viewer: ' . $key . ' (' . $name->id .  ')<br />';
				}
				return '';
				break;
				
			case 'getLoginkey':
				if ( $member->isLoggedIn() ){
					header('Content-Type: application/xml');
					echo $this->_getLoginkey();
				} else {
					header("HTTP/1.0 500 Internal Server Error");
					header("Status: 500 Internal Server Error");
					echo _PAINT_NeedLogin;
				}
				return '';
				break;
			
			// other actions result in an error
			case '':
			default:
				return 'Unexisting action: ' . $type;
		}
	}
	
	function doSkinVar($skinType, $type = '') {
		global $member, $CONF;
		
		if($mes = $this->_phpVersionWarning() ){
			echo $mes;
			$this->_warn($mes);
		}
		
		switch ($type) {
			case '' :
			case 'form' :
				if ( $member->isLoggedIn() ){
					$this->_loadPlugin();
					$vars = array (
						'ActionURL' => $CONF['ActionURL'],
						'appletSelect' => $this->_getAppletSelect(),
						'paletteSelect' => $this->_getPaletteSelect(),
						'paletteSelectExtra' => $this->_getPaletteSelectExtra(),
						'qualitySelect' => $this->_getQualitySelect(),
						'animation' => ($this->getOption('defaultAnimation') == 'yes') ? 'checked="checked"' : '',
						'defaultWidth' => $this->getOption('defaultWidth'),
						'defaultHeight' => $this->getOption('defaultHeight'),
					);
					$tpl = $this->template->fetch('form', strtolower(__CLASS__));
					echo $this->template->fill($tpl, $vars, false);
				} else { 
					echo '<span class="paint">Powered by <a href="http://blog.cles.jp/">NP_Paint</a></span>';
				}
				break;
		}
	}

	function _phpVersionWarning(){
		$required = '4.3.2';
		if( ! version_compare(phpversion() , $required , '>=') ){
			return 'Warning' . _PAINT_phpVersion_before . $required . _PAINT_phpVersion_after ;
		}
		return '';
	}
	
	function _showApplet(){
		global $DIR_MEDIA, $CONF, $member;

		$w = intRequestVar('w') ? intRequestVar('w') : $this->getOption('defaultWidth');
		$h = intRequestVar('h') ? intRequestVar('h') : $this->getOption('defaultHeight');
		$animation = intRequestVar('animation') ? true : false;
		$ow = intRequestVar('ow');
		$usePch = intRequestVar('usepch') ? true : false;
		$file = requestVar('file');
		$quality = intRequestVar('quality') ? intRequestVar('quality') : 1 ;
		
		if( $file ){
			$collection = intval(dirname($file));
			if( $collection != intval($member->getID()) ){
				$file = null;
				$this->_warn( _PAINT_illegalCollection );
			}
		}
		
		$applet =& $this->applets[requestVar('applet')];
		if(! $applet ){
			$this->_warn(_PAINT_canNotFindApplet);
			return _PAINT_canNotFindApplet;
		}
		$palette =& $this->palettes[requestVar('palette')];
		
		$header = $beforeapplet = $param = $afterapplet = $copyright = '';
		
		if($applet){
			$header .= $applet->getHeaderPart();	
			$beforeapplet .= $applet->getBeforeAppletPart();
			$param .= $applet->getParamPart();
			$afterapplet .= $applet->getAfterAppletPart();
			$copyright .= $applet->getCopyrightsPart();
		}

		if($palette){
			$header .= $palette->getHeaderPart();	
			$beforeapplet .= $palette->getBeforeAppletPart();
			$param .= $palette->getParamPart();
			$afterapplet .= $palette->getAfterAppletPart();
			$copyright .= $palette->getCopyrightsPart();
		}
				
		if( $animation ){
			$param .= '<param name="thumbnail_type" value="animation" />';
		}
		
		$copyright .= 'NP_Paint by <a href="http://blog.cles.jp">cles</a><br />';
		
		if( $file && file_exists( $DIR_MEDIA . $file ) ){
			$appletName = requestVar('applet');
			list($animePath, $appletName) = $this->findAppletByImg($file);
			
			if( $animePath && $usePch ){
				$param .= '<param name="thumbnail_type" value="animation" />';
				$param .= "<param name=\"pch_file\" value=\"{$CONF['MediaURL']}{$animePath}\" />";
			} else {
				$param .= "<param name=\"image_canvas\" value=\"{$CONF['MediaURL']}{$file}\" />";
			}
			
			if( $ow ){
				list($prefix, ) = explode('-', basename($file), 2);
			} else {
				$prefix = $this->_getFilePrefix();
			}
		} else {
			$prefix = $this->_getFilePrefix();
			$ow = 0;
		}
		
		$vars = array (
				'w' => $w,
				'h' => $h,
				'user' => $member->getDisplayName(),
				'loginkey' => $this->_generateLoginkey(),
				'prefix' => $prefix,
				'charset' => _CHARSET,
				'header' => $header,
				'beforeapplet' => $beforeapplet,
				'param' => $param,
				'afterapplet' => $afterapplet,
				'copyright' => $copyright,
				'ow' => $ow,
				'file' => $file,
				'imgJpeg' => ( $this->getOption('defaultImgType') == 'AUTO' ) ? 'true' : 'false',
				'imgDecrease' => ( $this->getOption('defaultImgType') == 'AUTO' ) ? $this->getOption('defaultImgDecrease') : '0',
				'imgCompress' => ( $this->getOption('defaultImgType') == 'AUTO' ) ? $this->getOption('defaultImgCompress') : '0',
				'quality' => $quality,
		);
		
		echo $applet->getPageTemplate($vars);
		return '';
	}
	
	// return list($animeFile, $appletName)
	function findAppletByImg($file){
		global $DIR_MEDIA;
		$collection = intval(dirname($file));
		$basename = basename($file, '.jpg');
		$basename = basename($basename, '.png');
				
		foreach( $this->applets as $key => $applet  ){
			foreach( $applet->animeExt as $ext ){
				$anime = $DIR_MEDIA . $collection . '/' .  $basename . $ext;
				if( file_exists( $anime ) ){
					$appletName = $key;
					$animeFile = $collection . '/' .  $basename . $ext;
					$ret = Array($animeFile, $appletName);
					return $ret;
				}
			}
		}
		return null;
	}
	
	function getFileInfo($animeFile){
		global $DIR_MEDIA;
		
		list(,$ext) = explode('.', basename($animeFile), 2);
		$animeFile = $DIR_MEDIA . $animeFile;
		
		$fileInfo = Array();
		if( is_readable($animeFile) && ($ext == 'spch' || $ext == 'rpch') ){
			$fp = fopen($animeFile, 'r');
			while (!feof ($fp)) {
				$line = fgets($fp, 100);
				if (preg_match('/([a-zA-Z0-9_.-]+)=([a-zA-Z0-9_.-]+)/i', $line, $matches)) {
					list(, $key, $value, ) = $matches;
					$fileInfo[$key] = $value;
				} else {
					break;
				}
			}
			fclose($fp);
		}
		return 	$fileInfo;
	}
	
	function _showViewer(){
		global $member;
		
		if(	! $file = requestVar('file') ){
			return _PAINT_fileIsNotSet;
		}
		if(	! $type = requestVar('viewer') ){
			return _PAINT_viewerIsNotSet;
		}

		$w = intRequestVar('w') ? intRequestVar('w') : $this->getOption('defaultWidth');
		$h = intRequestVar('h') ? intRequestVar('h') : $this->getOption('defaultHeight');

		if(	! $viewer = $this->viewers[$type] ){
			return _PAINT_canNotFindViewer .' viewer:' . $type;
		}
		if( $type == 'continue' ){
			if ( ! $member->isLoggedIn() )
				return _PAINT_NeedLogin;
			$collection = intval(dirname($file));
			if ( $collection != $member->getId() ){
				return _PAINT_illegalCollection;
			}
		}
		
		$vars = array (
				'w' => intval($w),
				'h' => intval($h),
				'file' => $file,
				'charset' => _CHARSET,
		);
		
		echo $viewer->getPageTemplate($vars);
		return '';
	}
		
	function _loadPlugin( $typelist = false, $reload = false ){
		if( $this->isPluginLoaded && ( ! $reload ) )
			return ;
		if( $this->isPluginLoaded ){
			$this->init();
		}
		
		$this->isPluginLoaded = true;
		
		if( ! $typelist ){
			$typelist = 'Applet|Palette|Parser|Viewer';
		}
		
		$pluginDir = $this->getDirectory();
		$dirhandle = opendir( $pluginDir );
		while ($filename = readdir($dirhandle)) {
			if (preg_match('/^('.$typelist.')_(.*)\.php$/i',$filename,$matches)) {
				list(, $type, $name, ) = $matches;
				
				$pluginPath = $pluginDir . $filename;
				if( ! is_readable( $pluginPath ) ){
					$this->_warn(_PAINT_canNotReadFile . " file:$pluginPath, type:$type, name:$name");
					continue;
				}
				
				require_once($pluginPath);
				if( class_exists($type . '_' . $name) ){
					eval('$plugin =& new ' . $type . '_' . $name . '();');
					$plugin->appletBaseUrl = $this->getAdminURL() . 'applet/';
					$plugin->template =& $this->template;
	
					if( $plugin->enable ){
						//$this->_info('Plugin installed: ' . $name);
						switch( strtolower($type) ){
							case 'parser':
								$this->parsers[strtolower($name)] =& $plugin;
								break;
							case 'applet':
								$this->applets[strtolower($name)] =& $plugin;
								break;
							case 'palette':
								$this->palettes[strtolower($name)] =& $plugin;
								break;
							case 'viewer':
								$this->viewers[strtolower($name)] =& $plugin;
								break;
							default:
								// nothing
								$this->_warn('Unknown plugin: ' . $name);
						}
					} else {
						$this->disabledPlugin[strtolower($type.'_'.$name)] =& $plugin;
					}
				} else {
					$this->_warn(_PAINT_canNotLoadClass . " file:$pluginPath, type:$type, name:$name");
					continue;
				}
			}
		}
		closedir($dirhandle);
	}
	
	function _postimg($parser){
		global $member, $DIR_MEDIA;
		
		$parser =& $this->parsers[$parser];
		if(! $parser ){
			$this->_warn('Parser not found');
			return 'Parser not found';
		}
		
		$data =& $parser->parse();
		// exist parse error
		if( ! is_array($data) ){
			$this->_warn($data);
			return $data;
		}
		
		// Login check
		$user = '';
		$loginkey = '';
		if( $data['header'] ){
			if (preg_match('/user=([^&=]+)&loginkey=([^&=]+)&prefix=([^&=]+)&ow=([^&=]+)&applet=([^&=]+)/i', urldecode($data['header']), $word)) {
				list(, $user, $loginkey, $prefix, $ow, $applet, ) = $word;
			}
		}
		
		if( ! MEMBER::exists($user) ){
			$this->_warn("User Not Found ( user:$user, loginkey:$loginkey, prefix:$prefix )");
			return _PAINT_UserNotFound;
		}
		
		$member =& MEMBER::createFromName($user);
		if ( ! $this->_checkLoginkey($loginkey, intval($member->getID())) ){
			$this->_warn("Invalid Ticket ( user:$user, loginkey:$loginkey, prefix:$prefix )");
			return _PAINT_InvalidTicket;
		}
		$member->loggedin = 1;
		$this->_info("Login OK ( user:$user, loginkey:$loginkey, prefix:$prefix)");
		$this->_info(count($data['images']) . ' images found.');
		foreach( $data['size'] as $idx => $size ){
			$this->_info('DataSize['.$idx.']: '.$size);
		}
		
		if( $prefix ){
			$this->_getFilePrefix($prefix);
		} else {
			$this->_warn(_PAINT_canNotFindPrefix);
			$this->_getFilePrefix();			
		}
		
		$collection = intval($member->getID());
		// ow delete
		if( $ow ){
			foreach( Array('.jpg', '.png', '.pch', '.spch', '.rpch') as $ext ){
				if( file_exists( $file = $DIR_MEDIA . $collection . '/' . $this->_getFilePrefix() . '-' . NP_PAINT_IMAGE_NAME . $ext ) ){
					if( unlink($file) ){
						$this->_info(_PAINT_deleteFile . ' file:'. $file);
					} else {
						$this->_warn(_PAINT_deleteFile_failure . ' file:'. $file);
					}
				}
			}
		}
		
		foreach( $data['images'] as $idx => $imgdata ){
			$imgDir = $DIR_MEDIA . $collection;
			$tmpFileName = $this->_getFilePrefix() . '_'. $idx . '.tmp';
			
			$this->_info('tmpfile: '.$tmpFileName);
			$err = MEDIA::addMediaObjectRaw($collection, $tmpFileName, $imgdata);
			if($err){
				$this->_warn('addMediaObjectRaw Error: '. $err);
				return $err;
			}
			
			$imageSize = @GetImageSize($imgDir . '/' . $tmpFileName);
			// detect file type
			if($imageSize === false){
				switch( $applet ){
					case 'paintbbs':
						$suffix = '.pch';
						break;
					case 'shipainter':
						$suffix = '.spch';
						break;
					case 'shipainterpro':
						$suffix = '.rpch';
						break;
				}
			} else {
				switch($imageSize[2]){
					case IMAGETYPE_JPEG:
						$suffix = '.jpg';				
						break;
					case IMAGETYPE_PNG:
						$suffix = '.png';
						break;
					default:
						$suffix = '.dat';
				}
			}
			
			$filename = $this->_getFilePrefix() . '-' . NP_PAINT_IMAGE_NAME .$suffix;
			if( rename($imgDir . '/' . $tmpFileName, $imgDir . '/' . $filename ) === FALSE ){
				$this->_warn(_PAINT_rename_failure . ": {$tmpFileName} => {$filename}" );
			} else {
				$this->_info(_PAINT_rename_ok . ": {$tmpFileName} => {$filename}" );
			}
			
			
			if( !( function_exists('ImageTypes') && defined('IMG_JPG') && defined('IMG_PNG') )){
				$this->_info(_PAINT_GDNotSupported);
				continue;
			}
			
			if( $this->getOption('defaultImgType') == 'JPG' && $suffix == '.png' && (@ImageTypes() & IMG_JPG) ){
				$this->_info(_PAINT_convertToJpg);
				$imgres = @ImageCreateFromPNG($imgDir . '/' . $filename);
				if( $imgres ){
					$suffix = '.jpg';
					$filename = $this->_getFilePrefix() . '-'. NP_PAINT_IMAGE_NAME . $suffix;
					$this->_info('convertedFilename: ' . $filename);	
					if( @ImageJPEG($imgres, $imgDir . '/' . $filename, $this->getOption('defaultImgQuality'))){
						$this->_info(_PAINT_convertToJpg_succeeded);
						if( @unlink($imgDir . '/' . $filename) ){
							$this->_info(_PAINT_deleteFile . ':'. $filename);
						} else {
							$this->_warn(_PAINT_deleteFile_failure .':'. $filename);
						}
					} else {
						$this->_warn(_PAINT_convertToJpg_failure);
					}
					@ImageDestroy($imgres);
				} else {
					$this->_warn(_PAINT_pngRead_failure);
				}
			}
		}		
		return '';
	}
		
	function _getFilePrefix($prefix = ''){
		if( $prefix )
			$this->filePrefix = $prefix;
		if( ! $this->filePrefix )
			$this->filePrefix = date('YmdHis');
//			$this->filePrefix = uniqid('');
		return $this->filePrefix;
	}

	function _showItemAddForm() {
		global $member, $manager, $CONF;
		$manager->loadClass('ADMIN');
		
		$query = 'SELECT tblog as blogid from '.sql_table('team').' where tmember=' . intval($member->getID()) . ' limit 1';
		$res = sql_query($query);
		if (mysql_num_rows($res) > 0) {
			$obj = mysql_fetch_object($res);
			$blogid = $obj->blogid;
		} else {
			return _ERROR_NOTONTEAM;			
		}
		
		$body = '';
		if( intRequestVar('w') && intRequestVar('h') && requestVar('prefix') ){
			$prefix = requestVar('prefix');
			
			$collection = intval($member->getID());
			$mediaobjects = MEDIA::getMediaListByCollection($collection, $prefix . '-'.NP_PAINT_IMAGE_NAME);
			
			$this->_info( Count($mediaobjects) . ' mediaobject(s) found.');
			
			if( Count($mediaobjects) > 0){
				foreach($mediaobjects as $mo){
					if (preg_match('/\.(jpg|png)$/i', $mo->filename)){
						$filename = $mo->filename;
						break;
					}
				}
				
				$vars = array (
					'url' => $collection . '/' . $filename,
					'w' => intRequestVar('w'),
					'h' => intRequestVar('h'),
				);
				$body = TEMPLATE :: fill($this->getOption('bodyTpl'), $vars);
			}
		}

		$item['body'] = $body;
		$item['title'] = '';
		//default category
		//$item['catid'] = '';

		ob_start();
		$factory =& new PAGEFACTORY($blogid);
		$factory->createAddForm('bookmarklet',$item);
		$content = ob_get_contents();
		ob_end_clean();

		$replace = '<head>
<meta http-equiv="Content-Type" content="text/html; charset=' . _CHARSET . '" />
<base href="' . $CONF['AdminURL'] . '" />';	
		$content = preg_replace ("/<head>/", $replace, $content);
		
		$replace = '<form method="post" action="index.php" >';
		$content = preg_replace ("/<form[^>]*>/", $replace, $content);
		
		echo $content;
		return '';
	}
	
	function _showOk(){
		$vars = array(
			'charset' => _CHARSET,
		);
		$tpl = $this->template->fetch('ok', strtolower(__CLASS__));	
		return $this->template->fill($tpl, $vars, false);
	}
		
	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'Paint: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'Paint: '.$msg);
	}

	function _getAppletSelect($default = null){
		if( $default ){
			$recomended = _PAINT_STAR;
		} else {
			$recomended = '';
			$default = $this->getOption('defaultApplet');
		}
		
		$ret = '<select name="applet">';
		foreach( $this->applets as $key => $applet ){
			$selected = ( $default == $key ) ? 'selected="selected"' : '';
			$ret .= '<option value="' . $key . '" ' . $selected . ' >' . ($selected ? $recomended : '') .$applet->name .  '</option>' . "\n";
		}
		$ret .= '</select>';
		return $ret;
	}	
	
	function _getPaletteSelect(){
		$ret = '<select name="palette">';
		foreach( $this->palettes as $key => $palette ){
			$selected = ( $this->getOption('defaultPalette') == $key ) ? 'selected="selected"' : '';
			$ret .= '<option value="' . $key . '" ' . $selected . '>' . $palette->name .  '</option>' . "\n";
		}
		$ret .= '</select>';
		return $ret;
	}
	function _getPaletteSelectExtra(){
		foreach( $this->palettes as $key => $palette ){
			$ret .= $palette->getExtraOption();
		}
		return $ret;
	}
	
	function _getQualitySelect($selected = null){
		if(! $selected )
			$selected = $this->getOption('defaultAppletQuality');
		$qualities = Range(1,5);
		
		$tpl = '<select name="quality">';
		foreach( $qualities as $quality ){
			$tpl .= '<option value="' . $quality . '" '  .  ( $quality == $selected ? 'selected="selected" ' : '' )  . '>' . $quality . '</option>';
		}
		$tpl .= '</select>';
		
		return $tpl;
	}
	
	function _generateLoginkey(){
		global $member;
		$memberid = $member->getID();
		
		$done = false;
		$ticket = null;
		while(!$done){
			$ticket = sha1(uniqid('').mt_rand(1,999999999));
			$query = sprintf('INSERT INTO ' . sql_table(NP_PAINT_TICKET_TABLE) 
				. "(ticket, member) VALUES('%s', %s)"
				, mysql_real_escape_string( $ticket )
				, mysql_real_escape_string( intval($memberid) )
			);
			if( sql_query($query) ) $done = true;
		}
		return $ticket;
	}
	
	function _checkLoginkey($ticket, $memberid ){
		$query = sprintf('SELECT count(*) as result FROM ' . sql_table(NP_PAINT_TICKET_TABLE) 
			. " WHERE ticket = '%s' AND member = %s AND generated > DATE_SUB(NOW(),INTERVAL %s SECOND)"
			, mysql_real_escape_string( $ticket )
			, mysql_real_escape_string( intval($memberid) )
			, mysql_real_escape_string( NP_PAINT_TICKET_LIFETIME )
		);
		$result = quickQuery($query) ? true : false ;

		$query = sprintf('DELETE FROM ' . sql_table(NP_PAINT_TICKET_TABLE)
			. " WHERE ticket = '%s' OR generated < DATE_SUB(NOW(),INTERVAL %s SECOND)"
			, mysql_real_escape_string( $ticket )
			, mysql_real_escape_string( NP_PAINT_TICKET_LIFETIME )
		);
		sql_query($query);
		
		return $result;
	}
	
	function _getLoginkey(){
		$ret  = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
		$ret .= '<loginkey>'.$this->_generateLoginkey().'</loginkey>'."\n";
		return $ret;
	}
}
