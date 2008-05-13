<?php 
class NP_subSilver_action { 
	var $plug;
	function NP_subSilver_action(&$plug){
		$this->plug=&$plug;
		foreach($plug as $key=>$value) $this->$key=&$plug->$key;
	}
	function doAction($type,$p1='') {
		echo htmlspecialchars($type);
	}
}
?>