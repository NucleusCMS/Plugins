<?

class NP_GoogleRank extends NucleusPlugin{

	function getName()
	{
		return 'GoogleRank';
	}

	function getAuthor()
	{
		return 'nakahara21';
	}

	function getURL()
	{
		return 'http://nakahara21.com/';
	}

	function getVersion()
	{
		return '0.50';
	}

	function getDescription()
	{
		return 'Embed GoogleRank';
	}
	
	function supportsFeature ($what)
	{
		switch ($what) {
			case 'SqlTablePrefix':
				return 1;
			default:
			return 0;
		}
	}
	
	function init()
	{
		define('GOOGLE_MAGIC', 0xE6359A60);
	}

	function doSkinVar($skinType, $opt)
	{
		if (serverVar('REQUEST_URI')=='') {
			$uri = (serverVar("QUERY_STRING"))? 
				sprintf("%s%s%s?%s", "http://", serverVar("HTTP_HOST"), serverVar("SCRIPT_NAME"), serverVar("QUERY_STRING") ):
				sprintf("%s%s%s","http://",serverVar("HTTP_HOST"),serverVar("SCRIPT_NAME"));
		} else { 
			$uri = sprintf("%s%s%s","http://",serverVar("HTTP_HOST"),serverVar("REQUEST_URI")); 
		}
		$uri = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%]|i', '', $uri);
		$uri = 'info:' . $uri;
		$temp = $this->strord($uri);
		$ch = $this->GoogleCH($temp);
		$chv = '6' . sprintf("%u", $ch);

		$rankxmlurl =  'http://' . 'www.google.co.jp/search?client=navclient-auto&ch=' . $chv . '&q=' . $uri;
		$rankdataurl =  $rankxmlurl . '&features=Rank';
		echo '<a href="' . $rankxmlurl . '">check</a>';

		$result = @file($rankdataurl);
		if (!$result) {
			return;
		}

		$data = @join("", $result);
		$e = preg_replace('/\s/', "", substr(strrchr( $data, ":" ), 1));
		if ($e != 'n') {
			$e = intval($e);
		} else {
			$e = 'n';
		}
		echo '<img src="' . $this->getAdminURL() . 'imgs/' . $e . '.gif" alt="Google PageRank" title="Google PageRank (' . $e . '/10)" />';

	}

//unsigned shift right
	function zeroFill($a, $b)
	{
		$z = hexdec(80000000);

		if ($z & $a) {
			$a = ($a >> 1);
			$a &= (~$z);
			$a |= 0x40000000;
			$a = ($a >> ($b - 1));
		} else {
			$a = ($a >> $b);
		}

	return $a;
}

	function mix($a,$b,$c)
	{
		$a -= $b; $a -= $c; $a ^= ($this->zeroFill($c,13));
		$b -= $c; $b -= $a; $b ^= ($a<<8);
		$c -= $a; $c -= $b; $c ^= ($this->zeroFill($b,13));
		$a -= $b; $a -= $c; $a ^= ($this->zeroFill($c,12));
		$b -= $c; $b -= $a; $b ^= ($a<<16);
		$c -= $a; $c -= $b; $c ^= ($this->zeroFill($b,5));
		$a -= $b; $a -= $c; $a ^= ($this->zeroFill($c,3));
		$b -= $c; $b -= $a; $b ^= ($a<<10);
		$c -= $a; $c -= $b; $c ^= ($this->zeroFill($b,15));

		return array($a,$b,$c);
	}

	function GoogleCH($url, $length=null, $init=GOOGLE_MAGIC)
	{
		if(is_null($length)) {
			$length = sizeof($url);
		}
		$a = $b = 0x9E3779B9;
		$c = $init;
		$k = 0;
		$len = $length;
	    while ($len >= 12) {
			$a += ($url[$k + 0] + ($url[$k + 1] << 8) + ($url[$k + 2] << 16) + ($url[$k + 3] << 24));
			$b += ($url[$k + 4] + ($url[$k + 5] << 8) + ($url[$k + 6] << 16) + ($url[$k + 7] <<24));
			$c += ($url[$k + 8] + ($url[$k + 9] << 8) + ($url[$k + 10] << 16) + ($url[$k + 11] <<24));
			$mix = $this->mix($a, $b, $c);
			$a = $mix[0]; $b = $mix[1]; $c = $mix[2];
			$k += 12;
			$len -= 12;
		}

		$c += $length;
		/* all the case statements fall through */
		switch($len) { 
			case 11: $c+=($url[$k+10]<<24);
			case 10: $c+=($url[$k+9]<<16);
			case 9 : $c+=($url[$k+8]<<8);
			/* the first byte of c is reserved for the length */ 
			case 8 : $b+=($url[$k+7]<<24);
			case 7 : $b+=($url[$k+6]<<16);
			case 6 : $b+=($url[$k+5]<<8);
			case 5 : $b+=($url[$k+4]);
			case 4 : $a+=($url[$k+3]<<24);
			case 3 : $a+=($url[$k+2]<<16);
			case 2 : $a+=($url[$k+1]<<8);
			case 1 : $a+=($url[$k+0]);
			/* case 0: nothing left to add */
		}
	    $mix = $this->mix($a,$b,$c);
    /*-------------------------------------------- report the result */ 
	    return $mix[2]; 
	} 

//converts a string into an array of integers containing the numeric value of the char 
	function strord($string)
	{ 
		for ($i = 0; $i < strlen($string); $i++) {
			$result[$i] = ord($string{$i});
		}
		return $result;
	}

}

?>