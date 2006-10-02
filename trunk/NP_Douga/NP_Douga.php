<?php

class NP_Douga extends NucleusPlugin
{

	function getName() { return 'Douga'; }
	function getAuthor() { return 'nakahara21'; }
	function getURL() { return 'http://xx.nakahara21.net/'; }
	function getVersion() { return '0.7'; }
		
	
	function getDescription() {
		return '動画を埋め込むプラグイン';
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
	
	function install() {
	}
	
	
	function event_PreItem($data) { 
		$this->currentItem = &$data["item"]; 
		$this->currentItem->body = preg_replace_callback("/<\%douga\((.*)\)%\>/Us", array(&$this, 'embeddouga'), $this->currentItem->body); 
		$this->currentItem->more = preg_replace_callback("/<\%douga\((.*)\)%\>/Us", array(&$this, 'embeddouga'), $this->currentItem->more); 
	} 

	function embeddouga($matches){
		global $CONF; 
		
		$farray = array();
		$farray = explode("|",$matches[1]);
		
		$filename = $farray[0];
		if (!strstr($filename,'/')) {
			$filename = $this->currentItem->authorid . '/' . $filename;
		}
		$filename = htmlspecialchars($CONF['MediaURL']. $filename);
		$out = <<<EOD
<OBJECT ID="MediaPlayer" WIDTH=320 HEIGHT=309
  CLASSID="CLSID:22D6f312-B0F6-11D0-94AB-0080C74C7E95"
  STANDBY="Loading Windows Media Player components..." 
  TYPE="application/x-oleobject"
CODEBASE="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,7,1112">
<PARAM NAME="ShowStatusBar" VALUE="true">
<PARAM name="Volume" value="true">
<PARAM name="ShowControls" value="true">
<PARAM NAME="ShowAudioControls" VALUE="true">
<PARAM NAME="ShowPositionControls" VALUE="true">
<PARAM NAME="AutoStart" VALUE="true">
<PARAM NAME="FileName" VALUE="$filename">

<embed type="video/x-ms-wmv" 
 pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" 
Name="MediaPlayer" src="$filename" 
AutoStart=1 ShowStatusBar=1 volume=-1 HEIGHT=309 WIDTH=320></embed>
</OBJECT>
EOD;


		return $out;

	}
}
?>