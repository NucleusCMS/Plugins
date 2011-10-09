<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_DragAndDropUploader ($Revision: 1.12 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * 
  * Based on upload.php (http://www.plupload.com)
  * 
  * $Id: NP_DragAndDropUploader.php,v 1.12 2011/10/09 08:49:31 hsur Exp $
*/

/*
  * Copyright (C) 2010 CLES. All rights reserved.
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

define('NP_DRAGANDDROPUPLOADER_RUNTIMES', 'html5,gears,flash,silverlight,browserplus');		

// load class
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('cles/Template.php');

class NP_DragAndDropUploader extends NucleusPlugin {
	function init(){
		global $DIR_LIBS;
		require_once($DIR_LIBS . 'MEDIA.php');		
		
		// include language file for this plugin 
		$language = ereg_replace( '[\\|/]', '', getLanguageName()); 
		if (file_exists($this->getDirectory().'language/'.$language.'.php')) 
			@include_once($this->getDirectory().'language/'.$language.'.php');
		
		$this->te =& new cles_Template(dirname(__FILE__).'/draganddropuploader/template');
	}
	
	// name of plugin
	function getName() {
		return 'Drag and Drop Uploader';
	}

	// author of plugin
	function getAuthor() {
		return 'hsur';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL() {
		return 'http://blog.cles.jp/np_cles/category/31/subcatid/25';
	}

	// version of the plugin
	function getVersion() {
		return '1.2';
	}
	
	function hasAdminArea() {
		return 1;
	}
	
	function getEventList() {
		return array('AddItemFormExtras','EditItemFormExtras','AdminPrePageHead','BookmarkletExtraHead');
	}
	
    function event_AddItemFormExtras(&$data){
		$this->_formExtras();
	}

    function event_EditItemFormExtras(&$data){
		$this->_formExtras();
	}
	
	function _formExtras(){
		$collections = MEDIA::getCollectionList();
		$collections_html = '<select id="plugin_draganddropuploader">';
		foreach($collections as $k => $v){
			$collections_html .= '<option value="'.$k.'">'.$v.'</option>';
		}
		$collections_html .= '</select>';
		
		$tplVars = array(
			'collections' => $collections_html,
		);
		$index = $this->te->fetch('index', strtolower(__CLASS__));
		echo $this->te->fill($index, $tplVars, false);
	}
	
  function event_BookmarkletExtraHead(&$data){
		$this->_extraHead($data);
  }

	function event_AdminPrePageHead(&$data){
		if ( !($data['action'] == 'createitem' || $data['action'] == 'itemedit') ){
			return;
		}
		$this->_extraHead($data);
	}

	function _extraHead(&$data){
		$tplVars = array(
			'plugindirurl' => $this->getAdminURL(),
			'runtimes' => NP_DRAGANDDROPUPLOADER_RUNTIMES,
		);
		$header = $this->te->fetch('extrahead', strtolower(__CLASS__));
		$data['extrahead'] .= "\n" . $this->te->fill($header, $tplVars, false);
    }
	
	function install() {
		// debug
		$this->createOption('debug', 'Debug Mode ?', 'yesno', 'no');
	}
	
	function unInstall() {
	}
	
	function _info($msg) {
		if ($this->getOption('debug') == 'yes') {
			ACTIONLOG :: add(INFO, 'DragAndDropUploader: '.$msg);
		}
	}

	function _warn($msg) {
		ACTIONLOG :: add(WARNING, 'DragAndDropUploader: '.$msg);
	}
	
	function getMinNucleusVersion() { return 330; }
	function getMinNucleusPatchLevel() { return 0; }

	// a description to be shown on the installed plugins listing
	function getDescription() {
		return '[$Revision: 1.12 $]<br />' . NP_DRAGANDDROPUPLOADER_DESCRIPTION;
	}

	function supportsFeature($what) {
		switch ($what) {
			case 'SqlTablePrefix' :
			case 'HelpPage':
				return 1;
			default :
				return 0;
		}
	}
			
	function doAction($type) {
		global $member;
		header('Content-type: text/plain; charset=UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
				
		switch ($type) {
			case 'image_upload':
				// 5 minutes execution time
				@set_time_limit(5 * 60);
				$this->_image_upload();
				return '';
				break;

			// other actions result in an error
			case '':
			default:
				return 'Unexisting action: ' . $type;
		}
	}
	
	function _jsonrpc_error($mes = "", $code = "500"){
		$this->_warn("($code) $mes");
		header("HTTP/1.1 $code $mes");
		echo('{"jsonrpc" : "2.0", "error" : {"code": '.$code.', "message": "'.$mes.'"}, "id" : "id"}');
		exit;
	}
	
	function _jsonrpc_ok(){
		echo('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
		exit;
	}
	
	function _image_upload(){		
		global $member, $DIR_MEDIA, $CONF;
		if( !$member->isLoggedIn() ) return $this->_jsonrpc_error("Not Logged in.", "403");
		$collection = requestVar('collection');
		
		$fileName = isset($_REQUEST['name']) ? requestVar('name') : 'no_name';
		if( $CONF['MediaPrefix'] ){
			$exif = @exif_read_data($_FILES['file']['tmp_name']);
			if( $exif !== false && $exif["DateTime"] && strtotime($exif["DateTime"]) !== false )
				$fileName = strftime("%Y%m%d-", strtotime($exif["DateTime"])) . $fileName;
			else
				$fileName = strftime("%Y%m%d-", time()) . $fileName;
		}
				
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) $contentType = serverVar('HTTP_CONTENT_TYPE');
		if (isset($_SERVER['CONTENT_TYPE'])) $contentType = serverVar('CONTENT_TYPE');
		
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				$err = MEDIA::addMediaObject($collection, $_FILES['file']['tmp_name'], $fileName);
				if($err){
					$this->_jsonrpc_error('addMediaObject Error: '. $err, "500");
				}
			} else
			$this->_jsonrpc_error("Failed to move uploaded file.", "500");
		} else {
			// Read binary input stream and append it to temp file
			$in = fopen("php://input", "rb");
			if ($in) {
				while ($buff = fread($in, 4096))
				$err = MEDIA::addMediaObjectRaw($collection, $fileName, $buff);
				if($err){
					$this->_jsonrpc_error('addMediaObjectRaw Error: '. $err, "500");
				}
			} else
			$this->_jsonrpc_error("Failed to open input stream.", "500");
		}
		
		$this->_jsonrpc_ok();
	}
}
