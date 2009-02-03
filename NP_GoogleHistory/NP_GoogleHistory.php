<?php
class NP_GoogleHistory extends NucleusPlugin {
	function getName() { return 'My GoogleHistory'; }
	function getAuthor()  { return 'nakahara21'; }
	function getURL() { return 'http://xx.nakahara21.net/'; }
	function getVersion() { return '0.3'; }
	function getDescription() { return 'Show history og google keywords'; }
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption("ex", "extension of logfile (ex: log):", "text", "log");
	}

	function init(){
		global $CONF;
		$this->logdir = $this->getDirectory();
		$this->month = date("Y-m");
		$this->logfile = $this->logdir.$this->month.'.'.$this->getOption("ex");
	}

	function doSkinVar($skinType, $show="gform", $maxtoshow = 5) {
		global $CONF;

		if($show=="list"){
			$this->showGwordList($maxtoshow);
		}
		if($show=="gform"){
?>
<div>
<form method="post" action="<?php echo $CONF['ActionURL'] ?>" target="_blank">
<input type="hidden" name="action" value="plugin" />
<input type="hidden" name="name" value="GoogleHistory" />
<input type="hidden" name="type" value="gsearch" />
<input type="text" value="" name="gword" size="15" />
<input type="submit" value="!" />
</form>
</div>
<?php
		}
		
	}

	function doAction($type) {
		$gword = requestVar('gword');
		switch ($type) {
			case 'gsearch':
				$this->saveGword($gword);
				$gurl = $this->makeGurl($gword);
				header('Location: ' . $gurl);
				break;
			default:
				return 'Unexisting action: ' . $type;
		}
		exit;
	}

	function makeGurl($gword) {
		$en_gword = mb_convert_encoding($gword, "UTF-8", _CHARSET);
		$gurl = 'http://www.google.co.jp/search?ie=UTF-8&oe=UTF-8&q='.urlencode($en_gword);
		return $gurl;
	}

	function saveGword($gword) {
		$time = time();
		$gword = mb_convert_encoding($gword, "sjis", "UTF-8");
		$arr_data = Array($gword,$time);

		$fp = @fopen($this->logfile,"a+");
		if (!$fp) {
			if(!is_dir($this->logdir)) die("No such directory : ".$this->logdir."\n");
			if(!is_writable($this->logdir)) die("Cannot write to this directory : ".$this->logdir."\n");
			die("ERROR\n");
		}
		$tmp = fread ($fp, filesize ($this->logfile));
		ftruncate($fp,0);
		rewind($fp);
		fputs($fp,@join("\t",$arr_data)."\n");
		fputs($fp,$tmp);
		fclose($fp);
	}

	function readlog($maxtoshow){
		if( $handle = opendir($this->logdir)){
			while( false !== $file = readdir($handle)){
				sscanf($file,"%4s-%2s.%s", $y, $m, $ex);
				if(checkdate($m,1,$y) && $ex == $this->getOption("ex")){
					$filelist[] = $file;
				}
			}
			closedir($handle);
		}
		$log = array();
		for($i=0;$this->num<$maxtoshow;$i++){
			if($filelist[$i]){
				$data = @file($this->logdir.$filelist[$i]);
				$this->num += count($data);
				$log = array_merge($log, $data);
			}else{
				break;
			}
		}
			return $log;
	}

	function showGwordList($maxtoshow){
		$log = $this->readlog($maxtoshow);
		if(($amount = min($maxtoshow, $this->num)) >0){
			for($i=0;$i<$amount;$i++){
				list($word,$timestamp) = explode("\t",$log[$i]);
				$word = mb_convert_encoding($word, _CHARSET, "sjis");
				$gtime = date("Y-m-d H:i",$timestamp);
				$gurl = $this->makeGurl($word);
				echo '<li><a href="'.$gurl.'" target="_blank">'.$word.'</a> '.$gtime.'</li>';
			}
		}
	}
}
?>