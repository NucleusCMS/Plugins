<?php
class NP_SkinVarManager extends NucleusPlugin {
	function getName() { return 'NP_SkinVarManager'; }
	function getMinNucleusVersion() { return 330; }
	function getAuthor()	{ return 'Katsumi'; }
	function getVersion() { return '0.1.1'; }
	function getURL() {return 'http://japan.nucleuscms.org/wiki/plugins:authors:katsumi';}
	function getDescription() {
		return 'This plugin raises RegisterSkinVars event for overriding skinvars and ifvars.<br />
			Also raises PreParseCentents event for tracking what will be parsed as skin.';
	}
	function supportsFeature($what) { return ($what=='SqlTablePrefix')?1:0; }
	function getEventList() { return array('InitSkinParse','PreSkinParse'); }
	function doSkinVar($skinType,$type,$p2='') {
		// The first argument shows what skinver is overrided.
		// The method that override core's function is defined in $this->skinvars.
		$args=func_get_args();
		$skinType=array_shift($args);
		$type=array_shift($args);
		array_unshift($args,$skinType);
		$function=@$this->skinvars[$type];
		if ($function) call_user_func_array($function,$args);
	}
	function doIf($p1='',$p2=''){
		// The first argument contains two things. One is what ifver is overrided.
		// The other is what is the first argument used in overrided method.
		// These are connected by ":" as delimiter.
		// The method that override core's function is defined in $this->ifvars.
		if (!preg_match('/^([a-zA-Z_0-9]+):([\s\S]*)$/',$p1,$m)) exit('Fatal error when parsing if skinvar');
		$type=$m[1];
		$p1=$m[2];
		$function=@$this->ifvars[$type];
		if ($function) return call_user_func($function,$p1,$p2);
		else exit('Fatal error when parsing if skinvar');
	}
	var $skinvars,$ifvars,$search,$replace;
	function event_InitSkinParse(&$data) {
		global $manager;
		// Raise an event
		$skinvars=$ifvars=array();
		$manager->notify('RegisterSkinVars',array('skinvars' => &$skinvars, 'ifvars' => &$ifvars));
		// parsedinclude is overrided by this plugin's method.
		// The other plugin's method cannot override parsedinclude.
		$skinvars['parsedinclude']=array(&$this,'parse_parsedinclude');
		// Construct search and replace arrays.
		$search=$replace=array();
		foreach($skinvars as $key=>$function){
			if (!preg_match('/^[a-zA-Z0-9_]+$/',$key)) continue;
			$search[]="<%$key%>";
			$replace[]="<%SkinVarManager($key)%>";
			$search[]="<%$key(";
			$replace[]="<%SkinVarManager($key,";
		}
		foreach($ifvars as $key=>$function){
			if (!preg_match('/^[a-zA-Z0-9_]+$/',$key)) continue;
			foreach(array('if','ifnot','elseif','elseifnot') as $if){
				$search[]="<%$if($key)%>";
				$replace[]="<%$if(SkinVarManager,$key:)%>";
				$search[]="<%$if($key,";
				$replace[]="<%$if(SkinVarManager,$key:";
			}
		}
		// Set the object properties
		$this->skinvars=$skinvars;
		$this->ifvars=$ifvars;
		$this->search=$search;
		$this->replace=$replace;
		// Set handler and parser
		$this->skin=&$data['skin'];
		$skin=&$data['skin'];
		$type=&$data['type'];
		$actions = $skin->getAllowedActionsForType($type);
		$this->handler =& new ACTIONS($type, $skin);
		$this->parser =& new PARSER($actions, $this->handler);
		$this->handler->setParser($this->parser);
		$this->handler->setSkin($skin);
	}
	// Public properties
	var $skin,$handler,$parser;
	function event_PreSkinParse(&$data) {
		// Convert
		$contents=&$data['contents'];
		$contents=str_replace($this->search,$this->replace,$contents);
	}
	function parse_parsedinclude($skintype,$filename) {
		// check current level
		if ($this->handler->level > 3) return;	 // max. depth reached (avoid endless loop)
		$filename = $this->handler->getIncludeFileName($filename);
		if (!file_exists($filename)) {
			$contents=false;
		} else {
			$fsize = filesize($filename);
			// nothing to include
			if ($fsize <= 0) {
				$contents='';
			} else {
				// read file
				$fd = fopen ($filename, 'r');
				$contents = fread ($fd, $fsize);
				fclose ($fd);
			}
		}
		$this->parse($contents,$filename);
	}
	function parse(&$contents,$filename='') {
		global $manager;
		// Raise an event
		$manager->notify('PreParseContents',
			array('contents' => &$contents,
				'filename' => $filename,
				'skin' => &$this->skin));
		// convert
		$contents=str_replace($this->search,$this->replace,$contents);
		// parse file contents
		if (is_string($contents) && strlen($contents)) {
			$this->handler->level++;
			$this->parser->parse($contents);
			$this->handler->level--;
		}
	}
}
