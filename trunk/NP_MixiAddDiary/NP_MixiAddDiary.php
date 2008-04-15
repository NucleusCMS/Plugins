<?php
/*
	NP_MixiAddDiary
	Licence: GNU GPL2
	
	PHP_Mixi (sharedlib/phpmixi) is developped by riaf.
	
	History:
	v0.4(original) by Ryuji  http://ryuji.be/
	v0.4(custom)   by Andy   http://www.matsubarafamily.com/lab/
	v0.43          by yu     http://nucleus.datoka.jp/
	v0.44          by Andy   http://mixi.jp/view_bbs.pl?id=633757&comm_id=1214
*/

class NP_MixiAddDiary extends NucleusPlugin {
	function getName()  { return 'MixiAddDiary'; }
	function getAuthor() { return 'Ryuji + Andy + yu'; }
	function getURL()     { return 'http://japan.nucleuscms.org/wiki/plugins:mixiadddiary'; }
	function getVersion() { return '0.44'; }
	function getMinNucleusVersion() { return 220; }
	function supportsFeature($what) { 
		switch($what){ 
		case 'SqlTablePrefix': 
			return 1; 
		default: 
			return 0; 
		}
	}

	function init() {
		// include language file for this plugin
		$language = ereg_replace( '[\\|/]', '', getLanguageName());
		if (file_exists($this->getDirectory().$language.'.php'))
			include_once($this->getDirectory().$language.'.php');
		else
			include_once($this->getDirectory().'english.php');
		
		/*
		require_once($this->getDirectory().'phpMixi.class.php');
		require_once($this->getDirectory().'snoopy/Snoopy.class.php');
		*/
		require_once('sharedlibs/phpmixi/phpMixi.class.php');
		require_once('sharedlibs/snoopy/Snoopy.class.php');
	}

	function getDescription() {
		return _MIXIDIARY_DESCRIPTION;
	}

	function install() {
		$this->createOption('mixi_login_mail',_MIXIDIARY_OPTION_LOGINMAIL,'text','');
		$this->createOption('mixi_login_pass',_MIXIDIARY_OPTION_LOGINPASS,'password','');
		$this->createOption('entry_format',_MIXIDIARY_OPTION_ENTRYFORMAT,'select','body',_MIXIDIARY_OPTION_ENTRYFORMAT_SEL);
		$this->createOption('conv_imgalt', _MIXIDIARY_OPTION_IMGALT, 'yesno', 'yes');
		$this->createOption('conv_link', _MIXIDIARY_OPTION_SHOWURL, 'yesno', 'yes');
		$this->createOption('conv_link_bl', _MIXIDIARY_OPTION_SHOWURL_BL, 'text', '');
		
		$this->createBlogOption('mixi_login_mail',_MIXIDIARY_OPTION_LOGINMAIL,'text','');
		$this->createBlogOption('mixi_login_pass',_MIXIDIARY_OPTION_LOGINPASS,'password','');
		$this->createBlogOption('entry_format',_MIXIDIARY_OPTION_ENTRYFORMAT,'select','0',_MIXIDIARY_OPTION_ENTRYFORMAT_SEL2);
		$this->createBlogOption('title_prefix', _MIXIDIARY_OPTION_TITLEPREFIX, 'text', '');
		$this->createBlogOption('addthisblog', _MIXIDIARY_OPTION_ADD_DEFAULT, 'yesno', 'no');
		
		$this->createCategoryOption('title_prefix', _MIXIDIARY_OPTION_TITLEPREFIX, 'text', '');
	}

	function getEventList() {
		return array('AddItemFormExtras','EditItemFormExtras','PostAddItem', 'PreUpdateItem', 'PreDeleteItem');
	}

	function event_AddItemFormExtras($data) {
		if ($this->getBlogOption($data['blog']->blogid, 'addthisblog') == 'yes')
			$checked = 'checked';
		else $checked = '';
		
		$msg = _MIXIDIARY_OPTION_MSG_SENDTOMIXI;
		echo <<<ITEMFORMEXTRA
			<h3>Mixi Diary</h3>
			<p>
				<input type="checkbox" value="1" id="plug_mixiadddiary_check" name="plug_mixiadddiary_check" $checked />
				<label for="plug_mixiadddiary_check">$msg</label>
			</p>
ITEMFORMEXTRA;
	}

	function event_EditItemFormExtras($data) {
		if ($this->getBlogOption($data['blog']->blogid, 'addthisblog') == 'yes')
			$checked = 'checked';
		else $checked = '';
		
		$msg = _MIXIDIARY_OPTION_MSG_SENDTOMIXI;
		echo <<<EDITFORMEXTRA
			<h3>Mixi Diary</h3>
			<p>
				<input type="checkbox" value="1" id="plug_mixieditdiary_check" name="plug_mixieditdiary_check" $checked />
				<label for="plug_mixieditdiary_check">$msg</label>
			</p>
EDITFORMEXTRA;
	}

	function event_PreDeleteItem($data) {
		global $manager;
		
		$itemid = $data['itemid'];
		$manager->loadClass('ITEM');
		$item =& $manager->getItem($itemid, 1, 1); //draft=1, future=1
		if(!$item) return;
		
		$mail = $this->_getLoginMail($item['blogid']);
		$pass = $this->_getLoginPass($item['blogid']);
		$mixi = new PHP_Mixi($mail, $pass);
		$mixi->login();
		
		$title  = $this->_makeTitle($item['title'], $item['blogid'], $item['catid']);
		$date = getdate(strtotime($item['itime']));
		$diaries = $mixi->parse_list_diary( '', $date['year'], $date['mon']);
		foreach ($diaries as $diary) {
			if ($diary['subject'] == $title) {
				preg_match('/(?<!owner_)id=(\d+)/', $diary['link'], $idmatch);
				$id = $idmatch[1];
				$mixi->delete_diary($id);
				break;
			}
		}
	}

	function event_PostAddItem($data){
		global $manager;
		if( !isset($_POST['plug_mixiadddiary_check']) ) return;
		
		$itemid = $data['itemid'];
		$manager->loadClass('ITEM');
		$item =& $manager->getItem($itemid, 0, 0); //draft=0, future=0
		if(!$item) return;
		
		$mail = $this->_getLoginMail($item['blogid']);
		$pass = $this->_getLoginPass($item['blogid']);
		$mixi = new PHP_Mixi($mail, $pass);
		$mixi->login();
		
		$this->authorid = $item['authorid']; //for _medialink_callback
		
		$title  = $this->_makeTitle($item['title'], $item['blogid'], $item['catid']);
		$body   = $this->_makeBody($item);
		$images = $this->_makeImages($item);
		
		$mixi->add_diary($title, $body, $images[0], $images[1], $images[2]);
	}

	function event_PreUpdateItem($data) {
		global $manager;
		if( !isset($_POST['plug_mixieditdiary_check']) ) return;
		
		$itemid = $data['itemid'];
		$manager->loadClass('ITEM');
		$item =& $manager->getItem($itemid, 0, 0); //draft=0, future=0 ... $item is old one
		if(!$item) return;
		
		$mail = $this->_getLoginMail($item['blogid']);
		$pass = $this->_getLoginPass($item['blogid']);
		$mixi = new PHP_Mixi($mail, $pass);
		$mixi->login();
		
		$id = 0;
		$oldtitle = $this->_makeTitle($item['title'], $item['blogid'], $item['catid']);
		$date = getdate(strtotime($item['itime']));
		$diaries = $mixi->parse_list_diary('', $date['year'], $date['mon']);
		foreach ($diaries as $diary) {
			if ($diary['subject'] == $oldtitle) {
				preg_match('/(?<!owner_)id=(\d+)/', $diary['link'], $idmatch);
				$id = $idmatch[1];
				break;
			}
		}
		
		$this->authorid = $item['authorid']; //for _medialink_callback
		
		$title  = $this->_makeTitle($data['title'], $item['blogid'], $data['catid']); //$data is new one
		$body   = $this->_makeBody($data, $item['blogid']);
		$images = $this->_makeImages($data, $item['authorid']);
		
		if ($id) {
			$mixi->edit_diary($id, $title, $body, $images[0], $images[1], $images[2]);
		}
		else {
			$mixi->add_diary($title, $body, $images[0], $images[1], $images[2]);
		}
	}


	function _getLoginMail($blogid) {
		if (! $mail = $this->getBlogOption($blogid, 'mixi_login_mail') ) {
			$mail = $this->getOption('mixi_login_mail');
		}
		return $mail;
	}


	function _getLoginPass($blogid) {
		if (! $pass = $this->getBlogOption($blogid, 'mixi_login_pass') ) {
			$pass = $this->getOption('mixi_login_pass');
		}
		return $pass;
	}


	function _makeTitle($title, $blogid, $catid='') {
		if ($catid) {
			if (! $prefix = $this->getCategoryOption($catid, 'title_prefix') ) {
				$prefix = $this->getBlogOption($blogid, 'title_prefix');
			}
		}
		else if ($blogid) {
			$prefix = $this->getBlogOption($blogid, 'title_prefix');
		}
		else $prefix = '';
		
		$title = mb_convert_encoding($prefix . $title, 'EUC-JP', _CHARSET);
		return $title;
	}


	function _makeImages(&$item, $authorid='') {
		global $DIR_MEDIA;
		
		if (empty($authorid)) $authorid = $item['authorid'];
		preg_match_all('/<%(?:popup|image)\(([^|]+)\|/', $item['body'] . $item['more'], $imagematch);
		$images = array ();
		foreach ($imagematch[1] as $mediafile) {
			if (strpos($mediafile, '/') === FALSE) {
				$images[] = $DIR_MEDIA . $authorid . '/' . $mediafile;
			} else {
				$images[] = $DIR_MEDIA . $mediafile;
			}
		}
		return $images;
	}


	function _makeBody(&$item, $blogid='') {
		global $CONF, $manager;
		
		if (empty($blogid)) $blogid = $item['blogid'];
		if (! $format = $this->getBlogOption($blogid, 'entry_format') ) {
			$format = $this->getOption('entry_format');
		}
		$blog =& $manager->getBlog($blogid);
		
		switch ($format) {
			case 'sentence':
				$body = $this->_getSomeText($item['body'], "\n");
				$flg_continue = true;
				break;
			case 'paragraph':
				$body = $this->_getSomeText($item['body'], "\n\n");
				$flg_continue = true;
				break;
			case 'body':
				$body = $item['body'];
				$body = $this->_convertMediaVar($body);
				$body = $this->_convertImgAlt($body);
				$body = $this->_convertLink($body);
				$body = trim( strip_tags($body) );
				if (!empty($item['more'])) $flg_continue = true;
				else $flg_continue = false;
				break;
			case 'all':
				$body = $item['body'] ."\n\n". $item['more'];
				$body = $this->_convertMediaVar($body);
				$body = $this->_convertImgAlt($body);
				$body = $this->_convertLink($body);
				$body = trim( strip_tags($body) );
				$flg_continue = false;
				break;
		}
		
		if ($flg_continue) {
			$body .= _MIXIDIARY_OPTION_MSG_CONTINUE;
			//$url = $blog->getURL() .'?itemid='. $item['itemid'];
			$url = $blog->getURL() . createItemLink($item['itemid']);
			$url = preg_replace('#(?<!:)//#', '/' , $url); //remove overlapped '/' in url
			$body .= $url;
		}
		$body .= "\n\nFrom " . $blog->settings['bname'] .' '. $blog->settings['burl'];
		$body = mb_convert_encoding($body, 'EUC-JP', _CHARSET);
		
		return $body;
	}


	function _getSomeText($data, $delim, $max = 1) {
		$arr = explode($delim, $this->_textformat($data));
		
		$cnt = 0;
		$ret = array();
		foreach ($arr as $a) {
			if (!empty($a)) {
				$ret[] = $a;
				$cnt++;
				if ($cnt >= $max) break;
			}
		}
		return implode($delim, $ret);
	}

	function _textformat($data) {
		$data = preg_replace("#(\r\n|\r|\n|)#", "", $data);
		$data = preg_replace("#(<br />)#", "\n", $data);
		$data = preg_replace("#(</p>)#", "\n\n", $data);
		$data = preg_replace("#\n{3,}#", "\n\n", $data);
		$data = $this->_convertMediaVar($data);
		$data = $this->_convertImgAlt($data);
		$data = $this->_convertLink($data);
		$data = trim( strip_tags($data) );
		return $data;
	}

	function _convertMediaVar($data) {
		$tgt = "/<%media\((.+?)\)%>/";
		$data = preg_replace_callback($tgt, array(&$this, '_medialink_callback'), $data); 
		return $data;
	}

	function _medialink_callback($m) {
		global $CONF;
	
		$mvar = explode('|', $m[1]);
		if ( strstr($mvar[0], '/') ) $memberdir = '';
		else $memberdir = $this->authorid . '/';
		$url = $CONF['MediaURL'] . $memberdir . $mvar[0];
		$text = $mvar[1];
		
		return "$text:$url";
	}

	function _convertLink($data) {
		if ($this->getOption('conv_link') != 'yes') return $data;
		
		$tgt = "{<a href=\"http://([^\"]+?)\">([^<]+?)</a>}";
		$data = preg_replace_callback($tgt, array(&$this, '_urllink_callback'), $data); 
		return $data;
	}

	function _urllink_callback($m) {
		static $blacklist;
		if (!is_array($blacklist)) $blacklist = split(',', $this->getOption('conv_link_bl'));
		
		$url = 'http://' . $m[1];
		$text = $m[2];
		foreach ($blacklist as $bl) {
			if (!empty($bl) and strpos($url, $bl) !== FALSE) return $text;
		}
		return "$text($url)";
	}

	function _convertImgAlt($data) {
		if ($this->getOption('conv_imgalt') != 'yes') return $data;
		
		$tgt = "/<%(?:popup|image)\((.+?)\)%>/";
		$data = preg_replace_callback($tgt, array(&$this, '_imgalt_callback'), $data); 
		return $data;
	}

	function _imgalt_callback($m) {
		$mvar = explode('|', $m[1]);
		if ($mvar[3]) return '[image:'.$mvar[3].']';
	}

}
?>
