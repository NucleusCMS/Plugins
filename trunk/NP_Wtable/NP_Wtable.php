<?php

class NP_Wtable extends NucleusPlugin {

	function getName() {	// name of plugin
		return 'Convert table'; 
	}
	
	function getAuthor()  {	// author of plugin 
		return 'nakahara21'; 
	}
	
	function getURL() 	{	// an URL to the plugin website
		return 'http://xx.nakahara21.net/'; 
	}
	
	function getVersion() {	// version of the plugin
		return '0.2'; //tate array
	}
	
	// a description to be shown on the installed plugins listing
	function getDescription() { 
		return 'Convert table';
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList() { return array('PreItem'); }

	function event_PreItem(&$data) {
		$this->currentItem = &$data["item"]; 

		$this->currentItem->body = removeBreaks($this->currentItem->body);
//		$this->currentItem->body = str_replace("\r\n","\n",$this->currentItem->body);
		$this->currentItem->body = preg_replace_callback("#\|(.*)\|\r\n#", array(&$this, 'list_table'), $this->currentItem->body); 
		$this->currentItem->body = preg_replace_callback("#\!(.*)\!#", array(&$this, 'convert_table'), $this->currentItem->body); 
		$this->currentItem->body = addBreaks($this->currentItem->body);

		$this->currentItem->more = preg_replace_callback("#\|(.*?)\|#", array(&$this, 'convert_table'), $this->currentItem->more); 
	}

	function list_table($text) { 
		return "!".$text[1]."!";
	} 

	function convert_table($text) { 
		$rows = explode('!!',$text[1]);
		for($r =0; $r < count($rows); $r++){
			$cell = explode('|',$rows["$r"]);
			for($c = 0; $c < count($cell); $c++){
				$cols["$c"]["$r"] = $cell["$c"];
			}
		}
		
		for($c = 0; $c < count($cols); $c++){
			$cols["$c"] = array_reverse ($cols["$c"], TRUE);
			$rowspan = 1;
//			print_r($cols["$c"]);
			foreach($cols["$c"] as $key => $val){
				if($val == '~'){
					$rowspan ++;
					$row["$key"]["$c"] = $val;
				}elseif($val == '>'){
					$row["$key"]["$c"] = $val;
				}elseif($rowspan > 1){
					$row["$key"]["$c"] = '<td rowspan="'.$rowspan.'">'.$val.'</td>';
					$rowspan = 1;
				}else{
					$row["$key"]["$c"] = '<td>'.$val.'</td>';
				}
			}
		}
		$row = array_reverse ($row, TRUE);
//		print_r($row);
		
		for($r = 0; $r < count($row); $r++){
			$out .= '<tr>';
			$colspan = 1;
			for($c =0; $c < count($row["$r"]); $c++){
				if($row["$r"]["$c"] == '~'){
					$out .= '';
				}elseif($row["$r"]["$c"] == '>'){
					$out .= '';
					$colspan ++;
				}elseif($colspan >1){
					$out .= str_replace('<td>','<td colspan="'.$colspan.'">',$row["$r"]["$c"]);
					$colspan = 1;
				}else{
					$out .= $row["$r"]["$c"];
				}
			}
			
			$out .= '</tr>';
		}
		
		return '<table border=1>'.$out.'</table>';
	} 
}
?>