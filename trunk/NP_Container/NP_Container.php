<?php
/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	(see nucleus/documentation/index.html#license for more info)
*/


class NP_Container extends NucleusPlugin {

	function getName() { return 'Container'; }
	function getAuthor()  { return 'yu + NKJG'; }
	function getURL() { return 'http://nucleus.datoka.jp/'; }
	function getVersion() { return '0.4'; }
	function getMinNucleusVersion() { return 330; }
	function getEventList() { return array( 'PreSkinParse' ); }

	function getDescription() { 
		return "utility plugin to use container-tag-like description";
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function init() {
		$this->parts = array();
		$this->level = 0;
	}

	function getParts($plugname, $partname='') {
		if ($partname) return $this->parts[$plugname][$partname];
		else return $this->parts[$plugname];
	}

	function setParts($plugname, $partname='', $data) {
		if ($partname) $this->parts[$plugname][$partname] = $data;
		else if ($this->parts[$plugname]) $this->parts[$plugname] = array_merge($this->parts[$plugname], $data);
		else $this->parts[$plugname] = $data;
	}

	function unsetParts($plugname, $partname='') {
		if ($partname) unset( $this->parts[$plugname][$partname] );
		else unset( $this->parts[$plugname] );
	}

	function doSkinVar($skinType, $mode='', $param='') { 
		global $manager;
		
		switch (strtolower($mode)) {
		case 'totemplate': //merge the codes of NP_ContainerToTemplate (NKJG http://niku.suku.name/)
			if (!is_array($manager->templates)) {
				$manager->templates = array();
			}
			$manager->templates[$param] =& $this->getParts($param);
			break;
		}
	}

	function event_PreSkinParse($data) { 
		global $skinid, $manager;
		
		// get and keep handler object to parse container parts
		$this->skin = new SKIN($skinid);
		if (!$this->skin->isValid) return;
		$this->handler = new ACTIONS($data['type']);
		$this->handler->setSkin($skin);
		$this->parser  = new PARSER($this->skin->getAllowedActionsForType($data['type']), $this->handler);
		$this->parser->setProperty('IncludeMode',$this->skin->getIncludeMode());
		$this->parser->setProperty('IncludePrefix',$this->skin->getIncludePrefix());
		$this->handler->setParser($this->parser);
		
		/* pre-include */
		$inctype = 'parsedinclude';
		if ($manager->pluginInstalled('NP_includespecial')) $inctype .= '|includespecial';
		$search = '/<%('. $inctype .')\(([^)]+?)\)%>/s';
		$data['contents'] = preg_replace_callback($search, array(&$this, '_cb_preinclude'), $data['contents']); //nest1
		$data['contents'] = preg_replace_callback($search, array(&$this, '_cb_preinclude'), $data['contents']); //nest2
		
		/* retrieve container data */
		$search = '/<%Container\(begin,([^)]+?)\)%>(.+?)<%Container\(end\)%>/s';
		$data['contents'] = preg_replace_callback($search, array(&$this, '_cb_preskinparse'), $data['contents']);
	}

	function _cb_preinclude($m) {
		$inctype = $m[1];
		$incname = $m[2];
		if ($this->level > 3) return;
		if ($inctype == 'includespecial') list($skinpart, $incname) = explode('|', $incname, 2);
		
		if ( $inctype == 'includespecial' and ($buff = $this->skin->getContent($skinpart)) ) {
			//do nothing ... include only, not parsed
		}
		else if ($incname) {
			ob_start();
			$this->handler->parse_include($incname); //include only, not parsed.
			$buff = ob_get_contents();
			ob_end_clean();
		}
		
		return $buff;
	}

	function _cb_preskinparse($m) {
		$plugname = $m[1];
		$this->setParts($plugname, '', $this->_get_parts($m[2]) );
		
		return ''; // clear strings
	}

	/* get template parts */
	function _get_parts($buff) {
		$parts = array();
		$data = preg_split("{<!--\s*PART\s+name=\"([0-9a-zA-Z_-]+)\"[^>]*-->|<!--\s*/PART[^>]*-->}", $buff, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
//		$data = preg_split("{<!--PART name=\"([0-9a-zA-Z_-]+)\"-->|<!--/PART-->}", $buff, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		while ($data) {
			$type = array_shift($data);
			$type = trim($type);
			if ( empty($type) ) continue;
			$parts[$type] = array_shift($data);
		}
		return $parts;
	}

}

?>
