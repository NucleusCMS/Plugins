<?php

/*
	NP_IncludeEX
	by yu (http://nucleus.datoka.jp/)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
	
	USAGE
	-----
	<%IncludeEX(parsed,{shortname}_{skintype}.txt)%> 	--- blogname_archivelist.txt
	<%IncludeEX(php,info.php?y={y}&m={m})%> 			--- info.php?y=2004&m=01
	<%IncludeEX(php+parsed,function.php,PARSE_TWICE)%> 	--- text is first parsed by php, and then parsed by nucleus
															(PARSED_TWICE allow 1-level nesting in "parsed" mode)
	HISTORY
	-------
	Ver 0.32 : Security fix. (2006/09/30)
	           Check blogid, archivelist and currentSkinName
	Ver 0.3  : Add parse-type "php+parsed".
	           Add optional parameter "PARSE_TWICE".
	           Add vars {version_num}, {random_num}, and {random_alpha}.
	           Add plugin option "Debug Mode".
	Ver 0.2  : Add parse-type "parsed+php".
	Ver 0.1  : First imprementation.
*/

// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

class NP_IncludeEX extends NucleusPlugin {

	function getName()    { return 'IncludeEX'; }
	function getAuthor()  { return 'yu'; }
	function getURL()     { return 'http://works.datoka.jp/index.php?itemid=199'; }
	function getVersion() { return '0.32'; }
	function getMinNucleusVersion() { return '200'; }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getDescription() {
		return 'Include a file using some valiables for replacement. [USAGE] &lt;%IncludeEX(parsed,{shortname}_{skintype}.txt)%&gt;, &lt;%IncludeEX(php,info.php?y={y}&m={m})%&gt;, etc. First Parameter is parse-type(none/php/parsed/parsed+php/php+parsed). Second is filename. Third is optional flag(PARSE_TWICE only now). Variables in filename are need to be braced. Available variables are: '. join(' ', $this->vars);
	}
	
	function install() {
		$desc_incmode = 'Include mode in second parsing(in "parsed+php" parse-type). [file] Make temporary file. Need <?php ?> tag and you can use HTML tags together like "php" parse-type. [memory] Use eval function, maybe faster. Only PHP code(No <?php ?> tag).';
		
		$this->createOption('debugmode','Debug Mode: print filename only.','yesno','no');
		
		if(getNucleusVersion() < 220) {
			$this->createOption('incmode',$desc_incmode,'text','file');
		}
		else {
			$this->createOption('incmode',$desc_incmode,'select','file','File|file|Memory|memory');
		}

	}

	function uninstall() {
	}

	function init() {
		// set var type
		$this->vars = array(
			'{blogid}',
			'{shortname}',
			'{catid}',
			'{catname}',
			'{itemid}',
			'{memberid}',
			'{skinid}',
			'{skinname}',
			'{skintype}',
			'{date}',
			'{y}',
			'{m}',
			'{d}',
			'{archivetype}',
			'{archivedate}',
			'{archive_y}',
			'{archive_m}',
			'{archive_d}',
			'{version}',
			'{version_num}',
			'{random_num}',
			'{random_alpha}',
			);
	}
	
	// helper function
	function _set_alpha($begin, $end) {
		$r_alpha = range($begin, $end);
		$random_alpha = $r_alpha[array_rand($r_alpha)];
		return $random_alpha;
	}
	
	function doSkinVar($skinType, $parsetype, $filename, $spflag='') {
		global $blogid, $catid, $itemid, $memberid, $skinid, $currentSkinName, $archive;
		global $blog, $CONF, $nucleus;

		if ($parsetype) $parsetype = strtolower($parsetype);
		if ($spflag) $spflag = strtoupper($spflag);
		
		// prepare
		if ($archive) {
			sscanf($archive,'%d-%d-%d',$y,$m,$d);
			if ($y && $m && $d) {
				$archive = sprintf('%04d-%02d-%02d',$y,$m,$d);
			} elseif ($y && $m && !$d) {
				$archive = sprintf('%04d-%02d',$y,$m);
			}
			sscanf($archive,'%4c-%2c-%2c',$archive_y,$archive_m,$archive_d);
		}
		$archivetype = ($archive_d) ? 'date' : 'month';
		
		if (! isValidSkinName($currentSkinName)) {
			$currentSkinName = SKIN::getNameFromId($skinid);
		}
		
		list($usec, $sec) = explode(' ', microtime());
		$rseed = (float) $sec + ((float) $usec * 100000);
		mt_srand($rseed);
		$random_num = mt_rand(0,9);
		
		$r_alpha = range('a','z');
		$random_alpha = $r_alpha[array_rand($r_alpha)];
		
		// set var value
		$replace = array(
			intval($blogid),
			$blog->getShortName(),
			intval($catid),
			$blog->getCategoryName($catid),
			intval($itemid),
			intval($memberid),
			intval($skinid),
			$currentSkinName,
			$skinType,		//this should get like $type in selector@globalfunc *for template use*?
			date('Y-m-d'),
			date('Y'),
			date('m'),
			date('d'),
			$archivetype,
			$archive,
			$archive_y,
			$archive_m,
			$archive_d,
			$nucleus['version'],
			getNucleusVersion(),
			$random_num,
			$random_alpha,
			);

		// replace
		$filename = str_replace($this->vars, $replace, $filename);
		
		// replace: random_* with param
		$filename = preg_replace('/{random_num:([0-9]+)-([0-9]+)}/e', 'mt_rand($1,$2)', $filename);
		$filename = preg_replace('/{random_alpha:([a-z]+)-([a-z]+)}/e', '$this->_set_alpha($1,$2)', $filename);

		if ($this->getOption('debugmode') == 'yes') {
			echo $filename;
			return;
		}
		
		$oInc = new PLUG_INCLUDE($skinType);
		switch( strtolower($parsetype) ) {
			case 'parsed':
				if ($spflag == 'PARSE_TWICE') $oInc->parse_2times_parsedinclude($filename);
				else $oInc->parse_parsedinclude($filename);
				break;
			case 'php':
				if ( preg_match('/\.php\?(.+)=(.+)/', $filename) ) { // full url for remote fopen
					$skindir = PARSER::getProperty('IncludePrefix');
					$filename = $CONF['SkinsURL'] .$skindir. $filename;
				}
				$oInc->parse_phpinclude($filename);
				break;
			case 'parsed+php':
				ob_start();
				if ($spflag == 'PARSE_TWICE') $oInc->parse_2times_parsedinclude($filename);
				else $oInc->parse_parsedinclude($filename);
				$buff = ob_get_contents();
				ob_end_clean();
				$oInc->parse_buff_phpinclude($buff, $this->getOption('incmode'));
				break;
			case 'php+parsed':
				ob_start();
				if ( preg_match('/\.php\?(.+)=(.+)/', $filename) ) { // full url for remote fopen
					$skindir = PARSER::getProperty('IncludePrefix');
					$filename = $CONF['SkinsURL'] .$skindir. $filename;
				}
				$oInc->parse_phpinclude($filename);
				$buff = ob_get_contents();
				ob_end_clean();
				if ($spflag == 'PARSE_TWICE') $oInc->parse_2times_parsedinclude($buff,'buff');
				else $oInc->parse_buff_parsedinclude($buff);
				break;
			default:
				$oInc->parse_include($filename);
		}
	}
	
}

class PLUG_INCLUDE extends BaseActions {

	function PLUG_INCLUDE($type) {
		// call constructor of superclass first
		$this->BaseActions();
		
		//$this->skin = new SKIN($type);
		//$actions = $this->skin->getAllowedActionsForType($type);
		$actions = SKIN::getAllowedActionsForType($type);
		$this->handler = new ACTIONS($type);
		$this->parser = new PARSER($actions, $this->handler);
		$this->handler->setParser($this->parser);
	}
	
	function parse_buff_phpinclude($buff, $mode = 'memory') {
		// make predefined variables global, so most simple scripts can be used here
	
		// apache (names taken from PHP doc)
		global $GATEWAY_INTERFACE, $SERVER_NAME, $SERVER_SOFTWARE, $SERVER_PROTOCOL;
		global $REQUEST_METHOD, $QUERY_STRING, $DOCUMENT_ROOT, $HTTP_ACCEPT;
		global $HTTP_ACCEPT_CHARSET, $HTTP_ACCEPT_ENCODING, $HTTP_ACCEPT_LANGUAGE;
		global $HTTP_CONNECTION, $HTTP_HOST, $HTTP_REFERER, $HTTP_USER_AGENT;
		global $REMOTE_ADDR, $REMOTE_PORT, $SCRIPT_FILENAME, $SERVER_ADMIN;
		global $SERVER_PORT, $SERVER_SIGNATURE, $PATH_TRANSLATED, $SCRIPT_NAME;
		global $REQUEST_URI;
	
		// php (taken from PHP doc)
		global $argv, $argc, $PHP_SELF, $HTTP_COOKIE_VARS, $HTTP_GET_VARS, $HTTP_POST_VARS;
		global $HTTP_POST_FILES, $HTTP_ENV_VARS, $HTTP_SERVER_VARS, $HTTP_SESSION_VARS;
	
		// other
		global $PATH_INFO, $HTTPS, $HTTP_RAW_POST_DATA, $HTTP_X_FORWARDED_FOR;
	
		if (strtolower($mode) == 'file') { // make temp file to include
			$tmpfname = tempnam("/tmp", "BUFF");
			$fp = fopen($tmpfname, "w");
			fwrite($fp, $buff);
			fclose($fp);
			include $tmpfname;
			unlink($tmpfname);
		}
		else {
			eval($buff); // use eval function instead of include
		}
		
	}

	function parse_buff_parsedinclude($buff) {
		// check current level
		if ($this->level > 3) return;	// max. depth reached (avoid endless loop)
		$this->level = $this->level + 1;
		
		// parse buffer contents
		$this->parser->parse($buff);
		
		$this->level = $this->level - 1;		
	}

	function parse_2times_parsedinclude($filename, $mode='file') {
		// check current level
		if ($this->level > 3) return;	// max. depth reached (avoid endless loop)
		
		if ($mode == 'file') {
			$filename = $this->getIncludeFileName($filename);
			if (!file_exists($filename)) return '';
		}
		
		$this->level = $this->level + 1;
		
		if ($mode == 'file') {
			// read file 
			$fd = fopen ($filename, 'r');
			$contents = fread ($fd, filesize ($filename));
			fclose ($fd);
		}
		else if ($mode == 'buff') {
			$contents = $filename;
		}
		
		// modify outer var-tags
		$this->_modify_tags($contents);
		
		ob_start();
		
		// parse file contents (1st)
		$this->parser->parse($contents);
		
		$buff = ob_get_contents();
		ob_end_clean();
		
		// restore modified var-tags
		$this->_restore_tags($buff);
		
		// parse file contents (2nd)
		$this->parser->parse($buff);
		
		$this->level = $this->level - 1;
	}
	
	// helper function
	
	function _modify_tags(&$contents) {
		$contents = preg_replace('/<%([^>].*)<%/', '<:$1<%', $contents);
		$contents = preg_replace('/%>([^>].*)%>/', '%>$1:>', $contents);
	}
	
	function _restore_tags(&$contents) {
		$target = array('/<:/','/:>/');
		$replace = array('<%','%>');
		$contents = preg_replace($target, $replace, $contents);
	}
}
?>