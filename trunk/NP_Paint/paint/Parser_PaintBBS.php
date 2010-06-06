<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Parser_PaintBBS.php ($Revision: 1.33 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Parser_PaintBBS.php,v 1.33 2010/06/06 11:44:19 hsur Exp $
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


class Parser_PaintBBS extends PaintPlugin {
	function Parser_PaintBBS(){
		$this->id = '$Id: Parser_PaintBBS.php,v 1.33 2010/06/06 11:44:19 hsur Exp $';
		$this->name = 'PaintBBS Parser';
		$this->enable = true;
		$this->offset = 0;
		$this->postdata = null;
	}
	
	function _read($length, &$err){
		$data = substr( $this->postdata, $this->offset, $length );
		$this->offset += strlen($data);
		$err = false;
		if( strlen($data) != $length ) $err = true;
		return $data;
	}
			
	function parse(){
		global $manager;
		$plugin = $manager->getPlugin('NP_Paint');

		ini_set("always_populate_raw_post_data", "1");
		global $HTTP_RAW_POST_DATA;
		$this->postdata = $HTTP_RAW_POST_DATA;;
		
		if(! $this->postdata ){
			$plugin->_info(_PAINT_Parser_useinput);
			if( $_SERVER['CONTENT_LENGTH'] ) $length = $_SERVER['CONTENT_LENGTH'];
			if( $_ENV['CONTENT_LENGTH'] ) $length = $_ENV['CONTENT_LENGTH'];
			$input = fopen("php://input", "rb");
			if( $length ){
				$this->postdata = fread($input, $length);
			} else {
				$plugin->_warn(_PAINT_Parser_contentLengthNotFound);
				while (!feof($input)) {
				  $this->postdata .= fread($input, 8192);
				}
			}
			fclose($input);
		}
				
		//TODO: debug
/*
		$test = fopen("/tmp/postdata.dat","wb");
		fwrite($test, $this->postdata);
		fclose($test);
		$plugin->_info('post length: ' . strlen($this->postdata) );
*/

		$data = Array(
			'magic' => null,
			'header' => null,
			'images' => Array(),
			'size' => Array(),
		);
		$err = false;
		
		// magic char
		$data['magic'] = $this->_read(1, $err);
		if($err) return 'Cannot read magic char';
		if( $data['magic'] == chr(0x00) ) return 'magic char is INVALID. (maybe poo=true?) ->' . $data['magic'];
		$plugin->_info('post magic: ' . $data['magic'] );

		// header
		$headerSize = $this->_read(8, $err);
		if($err) return 'Cannot read header size';
		if( ! is_numeric($headerSize) ) return 'Header size is INVALID';
		
		$data['header'] = $this->_read(intval($headerSize), $err);
		if($err) return 'Cannot read header';
		
		// image
		$imageSize = $this->_read(8, $err);
		if($err) return 'Cannot read image size';
		if( ! is_numeric($imageSize) ) return 'Image size is INVALID';
		
		$pad = $this->_read(2, $err);// '\r\n'
		if($err) return 'Cannot read padding';
		if(! $pad === "\r\n" ) return 'Cannot find padding';

		$imageData = $this->_read(intval($imageSize), $err);
		if($err) return 'Cannot read image ' . "(expected: $imageSize, actual:" . strlen($imageData) . ")";
		$plugin->_info("ImageSize (expected: $imageSize, actual:" . strlen($imageData) . ")");
		
		$data['images'][] = $imageData;
		$data['size'][] = strlen($imageData);

		// thumb
		while(true){
			$thumbSize = $this->_read(8, $err);
			if($err) break;
			if( ! is_numeric($thumbSize) ) break;
			
			if( $thumbSize != 0 ){
				$imageData = $this->_read($thumbSize, $err);
				if($err) break;
				$data['images'][] = $imageData;
				$data['size'][] = strlen($imageData);
				$plugin->_info("AnimeSize (expected: $thumbSize, actual:" . strlen($imageData) . ")");
			}
		}
		
		return $data;
	}
}
