<?php
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('removeBreaks')) {
	function removeBreaks($var)
	{
		return preg_replace("/<br \/>([\r\n])/", "$1", $var);
	}
}


class NP_Wikistyle extends NucleusPlugin {

	function getName()
	{
		return 'Wikistyle'; 
	}
	
	function getAuthor()
	{ 
		return 'nakahara21';
	}
	
	function getURL()
	{
		return 'http://nakahara21.com';
	}
	
	function getVersion()
	{
		return '0.51';
	}
	
	function getDescription()
	{ 
		return 'convert WikiTag';
	}

	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function getEventList()
	{
		return array(
					'PreItem'
				);
	}

	function event_PreItem(&$data)
	{
		$this->currentItem =& $data["item"];
		$this->convert_wikitag($this->currentItem->body);
		if ($this->currentItem->more) {
			$this->convert_wikitag($this->currentItem->more);
		}
	}

	function convert_wikitag(&$text)
	{
		$text = removeBreaks($text);
		$tmp_lines = explode("\n", $text);
		$tmp_lines[] = "&nbsp;";
		$text = "";
		$level = array();
		$templevel = array();
		
		$endline = count($tmp_lines) - 1;
		for ($i=0;$i<=$endline;$i++) {
//			$tmp_lines[$i] = trim($tmp_lines[$i]);
			$level = '';
		//__________
		$replaceFrom = array(
			'/([^:\/\/\w]|^)((https:\/\/)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',		
			'/([^:\/\/\w]|^)((http:\/\/|www\.)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',
			'/([^:\/\/\w]|^)((ftp:\/\/|ftp\.)([\w\.-]+)([\/\w+\.~%&?@=_:;#,-]+))/ie',
			'/([^:\/\/\w]|^)(mailto:(([a-zA-Z\@\%\.\-\+_])+))/ie'			
		);
		$replaceTo = array(
			'$this->createLinkCodeWiki("\\1", "\\2","https")',		
			'$this->createLinkCodeWiki("\\1", "\\2","http")',
			'$this->createLinkCodeWiki("\\1", "\\2","ftp")',
			'$this->createLinkCodeWiki("\\1", "\\3","mailto")'			
		);

			if ($level[p] = preg_match('/^(https:\/\/|http:\/\/|www\.|ftp:\/\/|ftp\.|mailto:)/ie', $tmp_lines[$i])) {
				$tmp_lines[$i] = preg_replace($replaceFrom, $replaceTo, $tmp_lines[$i]);
				$text .= $tmp_lines[$i] . '<br />';
			}
		//__________
			if (($level[h] = strspn($tmp_lines[$i], '*')) > 6) {
				$level[h] = 6; // limitation ;(
			}
			if ($level[h]) {
				$tmp_lines[$i] = ltrim(substr($tmp_lines[$i], $level[h]));
				$text .= '<h' . intval($level[h]) . ' class="wiki">' . $tmp_lines[$i] . '</h' . intval($level[h]) . '>';
			}
		//__________
			if (($level[u] = strspn($tmp_lines[$i], '-')) > 3) { 
				$level[r] = 4; 
				$level[u] = 3; // limitation ;(
			}
			if ($level[u] && !$level[r]) {
				$tmp_lines[$i] = ltrim(substr($tmp_lines[$i], $level[u]));
//				$tmp_lines[$i] = '<li>' . $tmp_lines[$i] . '</li>';
//_-------------
				if ($temptoplevel == 'u' && $templevel[o]) {
					$tmp_lines[$i] = str_repeat("</ol>\n",$templevel[o]) . "\n" . '<li>' . $tmp_lines[$i] . '</li>';
					$templevel[o] = 0;
				}else{
					$tmp_lines[$i] = '<li>' . $tmp_lines[$i] . '</li>';
				}
//_-------------
				
				$difflevel = $level[u] - $templevel[u];
				if ($difflevel < 0) {
					$text .= str_repeat("</ul>\n", 0 - $difflevel);
				}
				if ( $difflevel > 0) {
					$text .= str_repeat("<ul>\n", $difflevel);
				}
				
				$text .= $tmp_lines[$i];
				if (!array_sum($templevel)) {
					$temptoplevel = 'u';
				}
				$templevel[u] = $level[u];
			}
		//__________
			if ($level[r]) {
				$tmp_lines[$i] = ltrim(substr($tmp_lines[$i], $level[r]));
				$text .= '<div class="hr"><hr /></div>';
			}
		//__________
			if (($level[o] = strspn($tmp_lines[$i],'+')) > 3) { 
				$level[o] = 3; // limitation ;(
			}
			if ($level[o]) {
				$tmp_lines[$i] = ltrim(substr($tmp_lines[$i], $level[o]));

//				$tmp_lines[$i] = '<li>' . $tmp_lines[$i] . '</li>';
//_-------------
				if ($temptoplevel == 'o' && $templevel[u]) {
					$tmp_lines[$i] = str_repeat("</ul>\n", $templevel[u]) . "\n" . '<li>' . $tmp_lines[$i] . '</li>';
					$templevel[u] = 0;
				} else {
					$tmp_lines[$i] = '<li>' . $tmp_lines[$i] . '</li>';
				}
//_-------------
				
				$difflevel = $level[o] - $templevel[o];
				if ($difflevel < 0) {
					$text .= str_repeat("</ol>\n", 0 - $difflevel);
				}
				if ( $difflevel > 0) {
					$text .= str_repeat("<ol>\n", $difflevel);
				}
				
				$text .= $tmp_lines[$i];
				if (!array_sum($templevel)) {
					$temptoplevel = 'o';
				}
				$templevel[o] = $level[o];
			}
		//__________
			if (array_sum($level) == 0) {
				if (array_sum($templevel)) {
					if ($templevel[u]) {
						$text .= str_repeat("</ul>\n", $templevel[u]);
					}
					if ($templevel[o]) {
						$text .= str_repeat("</ol>\n", $templevel[o]);
					}
					$templevel = array();
					$temptoplevel = '';
				}
				if ($tmp_lines[$i] && $i != $endline) {
					$text .= $tmp_lines[$i]."<br />";
				}
			}
			if ($tmp_lines[$i] && $i != $endline) {
				$text .= "\n";
			}
		}
	} 

	function createLinkCodeWiki($pre, $url, $protocol = 'http')
	{
		$post = '';
	
		// it's possible that $url ends with an entities 
		// since htmlspecialchars is applied before URL linking
		if (preg_match('/(&\w+;)+$/i', $url, $matches)) {
			$post = $matches[0];	// found entities (1 or more)
			$url = substr($url, 0, strlen($url) - strlen($post));
		}

		if (!ereg('^'.$protocol.'://',$url)) {
			$linkedUrl = $protocol . (($protocol == 'mailto') ? ':' : '://') . $url;
		} else {
			$linkedUrl = $url;
		}
			
			
		if ($protocol != 'mailto') {
			$displayedUrl = $linkedUrl;
		} else {
			$displayedUrl = $url;
		}
		return $pre . '<a href="' . htmlspecialchars($linkedUrl) . '" target="_blank">' . htmlspecialchars($displayedUrl) . '</a>' . $post;
	}
	

}
?>