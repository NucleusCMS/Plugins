<?php 
   /***********************************
   * NP_SQLite plugin  ver 0.8.0      *
   *                                  *
   * This plugin modifies the plugins *
   *     when these are installed.    *
   *                                  *
   * Written by Katsumi               *
   ***********************************/
//  The licence of this script is GPL
class NP_SQLite extends NucleusPlugin { 
	function getName() { return 'NP_SQLite'; }
	function getMinNucleusVersion() { return 320; }
	function getAuthor()  { $this->install(); return 'Katsumi'; }
	function getVersion() { global $SQLITECONF; return $SQLITECONF['VERSION']; }
	function getURL() {return 'http://hp.vector.co.jp/authors/VA016157/';}
	function getDescription() { return $this->showinfo(); } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }
	function getEventList() { return array('PreAddPlugin','PreSkinParse','QuickMenu'); }
	function hasAdminArea() { return 1; }

	function install(){
		if (!$this->getOption('allowsql')) {
			$this->createOption('allowsql',$this->translated('Allow SQL query during the management?'),'yesno','no');
		}
		if (!$this->getOption('numatonce')) {
			$this->createOption('numatonce',$this->translated('Number of SQL queries at once when DB restore.'),'text','20','datatype=numerical'); 
			$this->createOption('refreshwait',$this->translated('Wating tile (seconds) when DB restore.'),'text','1','datatype=numerical');
		}
	}
	
	var $infostr;
	function showinfo() {
		global $SQLITECONF;
		if ($this->infostr) echo "<b>".$this->infostr."</b><hr />\n";
		return $this->translated('NP_SQLite plugin. Filesize of DB is now ').filesize($SQLITECONF['DBFILENAME']).' bytes.';
	}

	function init(){
	}

	function event_QuickMenu(&$data){
		global $member;
		$this->_showDebugMessage();

		// only show to admins
		if (!($member->isLoggedIn() && $member->isAdmin())) return;

		array_push($data['options'], array(
				'title' => 'SQLite',
				'url' => $this->getAdminURL(),
				'tooltip' => $this->translated('SQLite management')
			) );
	}
	function event_PreSkinParse(&$data){
		$this->_showDebugMessage();
	}
	function _showDebugMessage(){
		global $SQLITECONF;
		if (isset($SQLITECONF['DEBUGMESSAGE'])) sqlite_DebugMessage();
		unset($SQLITECONF['DEBUGMESSAGE']);
	}

	function event_PreAddPlugin(&$data){
		// This event happens before loading "NP_XXXX.php" file.
		// Therefore, modification is possible here.
		$this->modify_plugin($data['file'],true);
	}
	function modify_plugin($pluginfile,$install=false){
		global $DIR_PLUGINS,$SQLITECONF;
		if ($SQLITECONF['OVERRIDEMODE']) return true;

		$admindir=$DIR_PLUGINS.strtolower(substr($pluginfile,3));
		$pluginfile=$DIR_PLUGINS.$pluginfile;
		
		// List up all PHP files.
		$phpfiles=array();
		array_push($phpfiles,$pluginfile.'.php');
		$this->seekPhpFiles($admindir,$phpfiles);
		
		// Modify the PHP files.
		$allok=true;
		foreach ($phpfiles as $file) {
			if (!$this->changeFunctions($file)) {
				$allok=false;
			}
		}
		if ($allok) {
			if ($install) $this->infostr=$this->translated('Pluing was installed sucessfully');
			return true;
		}
		
		if (!$install) return false;
		if ($this->is_japanese()) {
			echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=EUC-JP" /></head>';
			echo '<body><h3>PHP ファイルを手動で変更する必要があります。</h3>';
		} else echo '<html><body><h3>Need to modify PHP file(s) manually.</h3>';
		foreach ($phpfiles as $file) echo $this->show_Lines($file);
		exit ('</body></html>');
	}

	function seekPhpFiles($dir,&$phpfiles){
		if (!is_dir($dir)) return;
		$d = dir($dir);
		$dirpath=realpath($d->path);
		$dirs=array();
		if (substr($dirpath,-1)!='/' && substr($dirpath,-1)!="\\") $dirpath.='/';
		while (false !== ($entry = $d->read())) {
			if ($entry=='.' || $entry=='..') continue;
			if (is_file($dirpath.$entry) && substr($entry,-4)=='.php') array_push($phpfiles,realpath($dirpath.$entry));
			if (is_dir($dirpath.$entry))  array_push($dirs,realpath($dirpath.$entry));
		}
		$d->close();
		foreach($dirs as $dir) $this->seekPhpFiles($dir,$phpfiles);
	}
	function changeFunctions($file){
		if (!is_file($file=realpath($file))) return false;
		if (!is_readable($file)) return false;
		$before=$this->read_from_file($file);
		// Do this process until change does not occur.. 
		// Otherwise, sometime file is not completely modified.
		$after=$this->do_replace($before);
		if ($before!=$after) return $this->write_to_file($file,$after);
		return true;
	}
	function do_replace(&$text) {
		// Do this process until change does not occur.. 
		// Otherwise, sometime file is not completely modified.
		$after=$text;
		do $after=preg_replace('/([^_])mysql_([_a-z]+)([\s]*?)\(/','$1nucleus_mysql_$2(',($before=$after));
		while ($before!=$after);
		return $after;
	}
	function show_Lines($file){
		if (!is_file($file=realpath($file))) return '';
		if (!is_readable($file)) return '';
		$result='';
		$lines=file($file);
		$firsttime=true;
		foreach($lines as $num=>$before) {
			$after=$this->do_replace($before);
			if ($after!=$before) {
				if ($firsttime) $result.="<hr />\nFile: <b>$file</b><br /><br />\n";
				$firsttime=false;
				$result.="Modify line <b>".($num+1)."</b> like: &quot;\n";
				$result.=str_replace('nucleus_mysql_','<font color="#ff0000">nucleus_</font>mysql_',htmlspecialchars($after))."&quot;<br /><br />\n";
			}
		}
		return $result;
	}
	function read_from_file($file) {
		if (function_exists('file_get_contents') ) $ret=file_get_contents($file);
		else {
			ob_start();
			readfile($file);
			$ret=ob_get_contents();
			ob_end_clean();
		}
		return $ret;
	}
	function write_to_file($file,&$text){
		if (!$handle = @fopen($file, 'w')) return false;
		fwrite($handle,$text);
		fclose($handle);
		return true;
	}
	function is_japanese(){
		$language = str_replace( array('\\','/'), array('',''), getLanguageName());
		return (strpos($language,'japanese')===0);
	}

	// Language stuff
	var $langArray;
	function translated($english){
		if (!is_array($this->langArray)) {
			$this->langArray=array();
			$language=$this->getDirectory().'language/'.str_replace( array('\\','/'), array('',''), getLanguageName()).'.php';
			if (file_exists($language)) include($language);
		}
		if (!($ret=$this->langArray[$english])) $ret=$english;
		return $ret;
	}

}
?>