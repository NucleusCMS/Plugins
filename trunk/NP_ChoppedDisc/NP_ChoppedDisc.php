<?
/* plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}*/

class NP_ChoppedDisc extends NucleusPlugin
{
	function getEventList()
	{
		return array();
	}

	function getName()
	{
		return 'Chopped description';
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
		return '0.7';
	}

	function getDescription()
	{
		return 'Chopped description. &lt;%ChoppedDisc(250,1)%&gt;';
	}

	function supportsFeature($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function parseHighlight($query)
	{
	
		// get rid of quotes
		$query = preg_replace('/\'|"/','',$query);
	
		if (!query) return array();
	
		$aHighlight = explode(' ', $query);
	
		for ($i = 0; $i<count($aHighlight); $i++) {
			$aHighlight[$i] = trim($aHighlight[$i]);
//			if (strlen($aHighlight[$i]) < 3)
//				unset($aHighlight[$i]);
		}
	
//		if (count($aHighlight) == 1)
//			return $aHighlight[0];
//		else
			return $aHighlight;
	}

	function splitLastStr($str, $width=5)
	{
		$posn = (mb_strwidth($str) > $width)? mb_strwidth($str) - $width: 0;
		$resArray[0] = ($posn) ? mb_strcut($str, 0, $posn, _CHARSET) : '';
		$resArray[1] = ($posn) ? mb_strcut($str, $posn, $width + 2, _CHARSET) : $str;
		return $resArray;
	}

	function chopStr($str, $query, $maxLength)
	{
		
		$searchclass =& new SEARCH($query);
		$highlight	 = $searchclass->inclusive;
		$this->highlights = $this->parseHighlight($highlight);

		if(mb_strwidth($str) <= $maxLength)
			return $str;
		
		$toated = "...";
		$tLength = mb_strwidth($toated);
		$maxLength = $maxLength - $tLength;

		$text = highlight($str, $this->highlights, '<\0>');
		$text = '< >' . $text;
		preg_match_all('/(<[^>]+>)([^<>]*)/', $text, $matches);
		for($i=0;$i<count($matches[1]);$i++){
			$matches[1][$i] = ereg_replace("<|>", '', $matches[1][$i]);
		}
/*
		print_r($matches);
		echo "<hr />";
*/
		for ($i=0;$i<count($this->highlights);$i++) {
			for ($e=0;$e<count($matches[1]);$e++) {
				if (eregi($this->highlights[$i], $matches[1][$e])) {
					if (!$hitkey[$i]) {
						$hitkey[$i] = $e;
					}
				}
			}
		}
		

		if(!$hitkey){
			$tt = mb_strcut($matches[2][0], 0, $maxLength, _CHARSET);
			if(mb_strwidth($matches[2][0]) > $maxLength) {
				$tt .= $toated;
			}
		} elseif($hitkey[1]) {
			sort($hitkey);
			foreach ($hitkey as $keyval) {
				$hitWordArray[] = $matches[1][$keyval];
			}

			$list[0] = array("qlen" => 0, "q" => '');
			$trimLength = intval(($maxLength - mb_strwidth(join("",$hitWordArray))) / (count($hitWordArray) +1));
			
			$left = $str;
			$i=0;
			while ($i <= count($hitWordArray)) {
//				$hitWord = $hitWordArray[$i];
				$tempArray = ($hitWord = $hitWordArray[$i]) ? explode($hitWord, $left, 2) : array($left, '');
				$preStr = ($hitWord) ? $this->splitLastStr($tempArray[0], 5) : array($left, '');

				$left = $preStr[1].$hitWord.$tempArray[1];

				$list[$i]['str'] = $preStr[0];
				$list[$i]['len'] = mb_strwidth($preStr[0]);

				$tempTrimLen = $trimLength + $list[$i]['qlen'];

				if($list[$i]['len'] < $tempTrimLen){
					$list[$i]['trimlen'] = 0;
					$addsum += $tempTrimLen - $list[$i]['len'];
				}else{
					$list[$i]['trimlen'] = $list[$i]['len'] - $tempTrimLen;
				}

				if (!$hitWord) break;
				$i++;
				$list[$i]['q'] = $hitWord;
				$list[$i]['qlen'] = mb_strwidth($hitWord);
			}
//	print_r($list);

			for ($i=0;$i<count($list);$i++) {
				if ($list[$i]['trimlen'] && ($addsum > 0)) {
					$list[$i]['trimlen'] = min($list[$i]['trimlen'], $addsum);
					$addsum -= $list[$i]['trimlen'];
					$list[$i]['trimlen'] = $trimLength + $list[$i]['trimlen'] + $list[$i]['qlen'];
				} elseif($list[$i]['trimlen']) {
					$list[$i]['trimlen'] = $trimLength + $list[$i]['qlen'];
				} else {
					$list[$i]['trimlen'] = $list[$i]['len'];
				}
			}
//	print_r($list);

			$tt = mb_strcut(
				 $list[0]['str'],
				 $list[0]['len'] - $list[0]['trimlen'],
				 $list[0]['trimlen'] + 2,
				 _CHARSET);
			if ($list[0]['len'] > $list[0]['trimlen']) {
				$tt = $toated.$tt;
			}

			for ($i=1;$i<count($list);$i++) {
				$tt .= mb_strcut($list[$i]['str'], 0, $list[$i]['trimlen'], _CHARSET);
				if ($list[$i]['len'] > $list[$i]['trimlen']) {
					$tt .= $toated;
				}
			}
		} else {
			$hitWord = $this->highlights[0];
			$keyLength = mb_strwidth($hitWord);
			
			$splitStr = preg_quote($hitWord);
			list($preStr, $hStr) = preg_split("/$splitStr/i",$str,2);

			$preStrLength = mb_strwidth($preStr);
			$hStrLength = mb_strwidth($hStr);
			$halfLength = intval(($maxLength - $keyLength) / 2);

			$hTrimLength = $preTrimLength = $halfLength;
			$minLength = min($preStrLength, $hStrLength, $halfLength);
			if ($preStrLength == $minLength) {
				$hTrimLength = $maxLength - $keyLength - $preStrLength;
				$preTrimLength = $preStrLength;
			}
			if ($hStrLength == $minLength) {
				$preTrimLength = $maxLength - $keyLength - $hStrLength;
				$hTrimLength = $hStrLength;
			}

			$tt = mb_strcut($preStr, $preStrLength - $preTrimLength, $preStrLength, _CHARSET);
			$tt .= $matches[1][1];
			$tt .= mb_strcut($hStr, 0, $hTrimLength,_CHARSET);

			if ($preTrimLength < $preStrLength) {
				$tt = $toated . $tt;
			}
			if ($hTrimLength < $hStrLength) {
				$tt .= $toated;
			}

		}

		return $tt;

/*
		$temp_str_array = mb_split($query,$str);
		$num = count($temp_str_array);
		foreach($temp_str_array as $key => $temp_str){
			$lastp = mb_strwidth($temp_str);
			if($key>0)
				$temp_s  = mb_strcut($temp_str, 0, 40, _CHARSET); 
			if($key>0 || ($key==0 && $lastp>40))
				$temp_s .= "..."; 
			$temp_s .= mb_strcut($temp_str, $lastp-40, $lastp, _CHARSET); 
			$str_array[$key] = $temp_s; 
//			if($key>0 && $lastp<83)
			if(($key>0 && $lastp<83) || $key == $num-1)
				$str_array[$key] = $temp_str; 
			
			$temp_str_length += mb_strwidth($str_array[$key]);
			if($temp_str_length > $maxLength)
				$str_array[$key] = $temp_str; 
		}
		$str = @join($query,$str_array);
		return $str;
*/
	}

	function doTemplateVar(&$item, $maxLength = 250, $addHighlight = 0)
	{
		global $CONF, $manager, $member, $catid;
		global $query;

		if ($manager->pluginInstalled('NP_HighlightSource')) {
			$tempPlugin =& $manager->getPlugin('NP_HighlightSource');
			preg_replace_callback("#<hs(|:[^>]+?)>(.*?)</hs>#s", array(&$tempPlugin, 'phpHighlight'), $item->body); 
			preg_replace_callback("#<hs(|:[^>]+?)>(.*?)</hs>#s", array(&$tempPlugin, 'phpHighlight'), $item->more); 
		}

		$syndicated = strip_tags($item->body);
		$syndicated .= strip_tags($item->more);
		$syndicated = preg_replace("/[\r\n]/", "", $syndicated);

		$syndicated = $this->chopStr($syndicated, $query, $maxLength);

/*
		if ($addHighlight) {
			echo highlight(htmlspecialchars($syndicated), $this->highlights, '<span class="highlight">\0</span>');
		} else {
			echo htmlspecialchars($syndicated);
		}
	}
*/
		if ($addHighlight) {
			global $currentTemplateName;
			$template =& $manager->getTemplate($currentTemplateName);
			echo highlight($syndicated, $this->highlights, $template['SEARCH_HIGHLIGHT']);
		} else {
			echo $syndicated;
		}
	}

}