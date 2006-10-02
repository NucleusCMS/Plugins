<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('removeBreaks')){
	function removeBreaks($var) {			return preg_replace("/<br \/>([\r\n])/","$1",$var); }
}

class NP_HighlightSource extends NucleusPlugin {

function getName() { return 'HighlightSource';    }
function getAuthor() { return 'nakahara21';    }
function getURL() { return 'http://xx.nakahara21.net'; }
function getVersion() { return '0.8'; }
function getDescription() { return 'HighlightSource'; }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}
	function install() {
		$this->createOption("Li", "Show line number?", "yesno", "no");
	}

	function getEventList() { return array('PreItem'); }

	function event_PreItem($data){
		$this->currentItem = &$data["item"]; 
		$this->currentItem->more = preg_replace("/(<\/hs>)<br \/>\r\n/","$1",$this->currentItem->more);
		$this->currentItem->body = preg_replace_callback('#<hs>(.*?)<\/hs>#s', array(&$this, 'phpHighlight'), $this->currentItem->body); 
		$this->currentItem->more = preg_replace_callback('#<hs>(.*?)<\/hs>#s', array(&$this, 'phpHighlight'), $this->currentItem->more);
	}

	function phpHighlight($matches){
		ini_set('highlight.string', '#CC0000');	//#CC0000 default
		ini_set('highlight.comment', '#FF9900');	//#FF9900 default
		ini_set('highlight.keyword', '#006600');	//#006600 default
		ini_set('highlight.bg', '#dddddd');	//#dddddd default
		ini_set('highlight.default', '#0000CC');	//#0000CC default
		ini_set('highlight.html', '#000000');	//#000000 default
		
		$code = trim(removeBreaks($matches[1]), "\r,\n"); 
		
		if(substr(trim($code),0,2) != "<?"){
			$code = "<?php\n".$code;
			$sflag = 1;
		}
		if(substr(trim($code),-2,2) != "?>"){
			$code = $code."\n?>";
			$eflag = 1;
		}

//		$code = stripslashes($code);
		$code = highlight_string($code, true);
		
		$source = explode('<br />', $code);
		for($i=0;$i<count($source);$i++){
			$precode = $source[$i];
			$source[$i] = $precolor.trim($source[$i])."</font>";
			$source[$i] = preg_replace("/<font color\=\"#([a-z|A-Z|0-9]+)\"><\/font>/s","&nbsp;",$source[$i]);
			$ppp = preg_match_all("/<font color\=\"#([a-z|A-Z|0-9]+)\">/s",$precode,$pmat,PREG_SET_ORDER);
			if($pmat){
				$las = count($pmat) - 1;
				$pcolor = $pmat[$las][1];
			}
			$precolor = '<font color="#'.$pcolor.'">';
		} 
		$code = @join("<br />\n", $source) ;

		if($sflag)
			$code = ereg_replace("(<code><font color\=\"#[a-z|A-Z|0-9]+\">)([\r\n])(<font color\=\"#[a-z|A-Z|0-9]+\">)&lt;\?php<br />\n</font>", '\\1<!--hss-->', $code);
		else
			$code = ereg_replace("(<code><font color\=\"#[a-z|A-Z|0-9]+\">)([\r\n])", '\\1<!--hss-->', $code);

		if($eflag)
			$code = ereg_replace("<br />\n</font><font color\=\"#([a-z|A-Z|0-9]+)\">\?&gt;</font>\n</font>\n</code>", "</font><!--hss--></font></code>", $code);
		else
			$code = ereg_replace("</font>\n</font>\n</code>", "</font><!--hss--></font></code>", $code);

		if($this->getOption('Li') == 'no')
			return '<div class="code">'.$code.'</div>';

		$code = explode('<!--hss-->', $code);
			$source = explode('<br />', $code[1]);
			for($i=0;$i<count($source);$i++){
				$source[$i] = '<li>'.trim($source[$i]).'</li>';
			} 
			$text = '<div class="code">';
			$text .= $code[0];
			$text .= "<ol>". @join('', $source) . "\n</ol>";
			$text .= $code[2];
			$text .= '</div>';

		return $text;
	}

}
?>