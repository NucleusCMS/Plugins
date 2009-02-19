<?php
class NP_admin_basic {
	var $svm; //NP_SkinVarManager object
	function init(){
		global $manager;
		$this->svm=&$manager->getPlugin('NP_SkinVarManager');
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists(dirname(__FILE__)."/language/$language.php"))
			include_once(dirname(__FILE__)."/language/$language.php");
		else
			include_once(dirname(__FILE__)."/language/english.php");
	}

	function hsc($text,$mode=''){
		$ret=htmlspecialchars($text,ENT_QUOTES,_CHARSET);
		switch($mode){
			case 'noampamp':
				return str_replace('&amp;amp;','$amp;',$ret);
			case '':
			default:
				return $ret;
		}
	}
	function p($text,$mode=''){
		echo self::hsc($text,$mode);
	}
	function quote($value){
		if (is_integer($value)) return (int)$value;
		else return "'".mysql_escape_string($value)."'";
	}
	function fill($template,$data,$mode='hsc'){
		$search=$replace=array();
		foreach($data as $key=>$value){
			if (is_int($key)) $search[]='<%'.($key+1).'%>';
			else $search[]="<%$key%>";
			switch($mode){
				case 'quote':
					$replace[]=self::quote($value);
				case 'hsc':
				default:
					$replace[]=self::hsc($value);
			}
		}
		return str_replace($search,$replace,$template);
	}
	function query($query,$data=array()){
		$query=preg_replace('/`nucleus_([a-zA-Z0-9_]+)`/',sql_table('').'$1',$query);
		$query=self::fill($query,$data,'quote');
		return sql_query($query);
	}
	function quickQuery($query,$data=array()){
		$res=self::query($query,$data);
		if ($row=mysql_fetch_row($res)) return $row[0];
		else return false;
	}

	function showUsingQuery($query,$template,$note='',$callback=false){
		$template=$this->getTemplate($template);
		$res=sql_query($query);
		$amount=mysql_num_rows($res);
		if (0<$amount) {
			$rowdata=$this->rowdata;
			$this->rowdata=array();
			$this->svm->parse($template['head']);
			$i=0;
			while($row=mysql_fetch_assoc($res)){
				$row['i']=$i++;
				if ($callback) $row=call_user_func($callback,$row);
				$this->rowdata=$row;
				$this->svm->parse($template['body']);
			}
			$this->rowdata=array();
			$this->svm->parse($template['foot']);
			$this->rowdata=$rowdata;
		} else self::p($note);
		return $amount;
	}
	function showUsingArray($data,$template,$note='',$callback=false){
		$template=$this->getTemplate($template);
		$amount=count($data);
		if (0<$amount) {
			$rowdata=$this->rowdata;
			$this->rowdata=array();
			$this->svm->parse($template['head']);
			$i=0;
			foreach($data as $row){
				$row['i']=$i++;
				if ($callback) $row=call_user_func($callback,$row);
				$this->rowdata=$row;
				$this->svm->parse($template['body']);
			}
			$this->rowdata=array();
			$this->svm->parse($template['foot']);
			$this->rowdata=$rowdata;
		} else self::p($note);
		return $amount;
	}

	function getTemplate($template){
		static $cache=array();
		if (isset($cache[$template])) return $cache[$template];
		$filename=$this->svm->handler->getIncludeFileName($template);
		// The template at least contains head, body, and foot.
		$cache[$template]=array();
		if (!file_exists($filename)) {
			$cache[$template]=array('head'=>'','body'=>'<b>Template not found.</b>','foot'=>'');
			return $cache[$template];
		}
		$fsize=filesize($filename);
		if ($fsize) {
			$fd = fopen ($filename, 'r');
			$contents = fread ($fd, $fsize);
			fclose ($fd);
		} else $contents='';
		$contents=preg_split('/(<!--\[[a-zA-Z0-9_]+\]-->[\r]?[\n]?)/',$contents,-1,PREG_SPLIT_DELIM_CAPTURE);
		if (count($contents)<2) {
			// if delimiter does not exist, whole text is used for body.
			$cache[$template]['body']=$contents[0];
		} else {
			foreach($contents as $value){
				if (preg_match('/<!--\[([a-zA-Z0-9_]+)\]-->/',$value,$m)) $key=$m[1];
				else $cache[$template][$key]=$value;
			}
		}
		if (isset($cache[$template]['extends'])) {
			// Template can extend parent one.
			$t=$this->getTemplate(trim($cache[$template]['extends']));
			foreach($t as $key=>$value){
				if (!isset($cache[$template][$key])) $cache[$template][$key]=$value;
			}
			unset($cache[$template]['extends']);//print_r($cache[$template]);exit;
		}
		// head, body and foot must be always exist.
		foreach(array('head','body','foot') as $key){
			if (!isset($cache[$template][$key])) $cache[$template][$key]='';
		}
		return $cache[$template];
	}

}