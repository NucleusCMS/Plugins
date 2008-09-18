<?php 
/*
 * NP_MitasNom
 * This library is GPL
 */
class NP_MitasNom extends NucleusPlugin { 
	var $actionplugin=false;
	var $bconvertbreaks;
	function getName() {
		$this->_checkVersion();
		return 'NP_MitasNom'; 
	}
	function getMinNucleusVersion() { return 220; }
	function getAuthor()  { return 'Katsumi, Cacher, yamamoto'; }
	function getVersion() { return '0.6.0'; }
	function getURL() {return 'http://japan.nucleuscms.org/wiki/plugins:mitasnom';}
	function getDescription() { return $this->translated('WYSIWYG HTML editor plagin using FCKeditor'); } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }
	function install() {
		// Install, upgrade options, and Refresh member options.
		// Note: createOption() is overrided (see below).
		$this->createOption('version','version','text',$this->getVersion(),'access=hidden');
		$this->createOption('width',$this->translated('Width'),'text','100%'); 
		$this->createOption('height',$this->translated('Height'),'text','400'); 
		$this->createOption('toolbar',$this->translated('Toolbar'),'select','Default','Default|Default|Full|Full|Basic|Basic|Custom|Custom'); 
		$this->createOption('toolbar_custom',$this->translated('Custom Toolbar'), 'textarea',
			"['Source','DocProps','-','Save','NewPage','Preview','-','Templates'],\n".
			"['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],\n".
			"['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],\n".
			"['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],\n".
			"'/',\n".
			"['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],\n".
			"['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote'],\n".
			"['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],\n".
			"['Link','Unlink','Anchor'],\n".
			"['Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak'],\n".
			"'/',\n".
			"['Style','FontFormat','FontName','FontSize'],\n".
			"['TextColor','BGColor'],\n".
			"['FitWindow','ShowBlocks','-','About']");
		$this->createOption('addremovetoolbar',$this->translated('Add new toolbar to the menu / Delete toolbar from the menu'),'text',''); 
		$this->createOption('returnafterbr',$this->translated('"<br />" => "<br />\n" conversion?'),'yesno','yes'); 
		$this->createOption('returnafterbrbr',$this->translated('"<br /><br />" => "<br />\n<br />\n" conversion?'),'yesno','no'); 
		$this->createOption('dialogwidth',$this->translated('Width of Popup dialog'),'text','500'); 
		$this->createOption('dialogheight',$this->translated('Height of Popup dialog'),'text','450'); 
		$this->createOption('protectedsource',$this->translated('Protected Sources'), 'textarea',
			'/<script[\s\S]*?\/script>/gi'."\n".
			'/<%[\s\S]*?%>/g');
		//$this->createOption('additionalpsource',$this->translated('Additional Protected Sources'), 'textarea','');
		$this->createOption('usemembersettings',$this->translated('Use member-specific settings?'),'yesno','no'); 
		$this->createOption('useimagemanager',$this->translated('Use Image-Manager plugin instead of media.php?'),'yesno','no'); 
		$this->createOption('usehttps',$this->translated('Use secure server (https) for edititing item?'),'yesno','no'); 
		$this->createOption('usep',$this->translated('Use P tag instead of BR for enter key?'),'yesno','no'); 
		$this->createOption('alwayswysiwyg',$this->translated('Always use WYSIWYG editor?'),'yesno','no'); 
	}
	function getEventList() { return array('EditItemFormExtras','AddItemFormExtras','PrepareItemForEdit',
					'PreAddItem','PreUpdateItem',
					'PreItem','PostPluginOptionsUpdate','PrePluginOptionsEdit',
					'AdminPrePageHead'); }

	// SkinVar is currently used for showing link to create item
	function doSkinVar($skinType,$type,$text='') {
		global $blogid,$CONF,$member;
		switch (strtolower($type)) {
		case 'newitem':
		default:
			if (!$member->isLoggedIn()) return;
			if (!$text) $text=$this->translated('New Item with WYSIWYG');
			$url=$CONF['ActionURL'].'?action=plugin&name=MitasNom&type=createitem&blogid='.$blogid;
			if ($this->getOption('usehttps')=='yes') $url=preg_replace('/^http:/','https:',$url);
			$url=htmlspecialchars($url);
			echo "<a href=\"$url\">$text</a>\n";
			break;
		}
	}

	// TemplateVar is currently used for showing link to edit item
	function doTemplateVar(&$item,$type,$text='') {
		global $CONF,$member;
		switch (strtolower($type)) {
		case 'edititem':
		default:
			if (!$text) $text=$text=$this->translated('Edit Item with WYSIWYG');
			$itemid=$item->itemid;
			$url=$CONF['ActionURL'].'?action=plugin&name=MitasNom&type=itemedit&itemid='.$itemid;
			if ($this->getOption('usehttps')=='yes') $url=preg_replace('/^http:/','https:',$url);
			$url=htmlspecialchars($url);
			echo "<a href=\"$url\">$text</a>\n";
			break;
		}
	}

	// Action is used to show item-editing/newitem-creating window.
	function doAction($type){
		global $DIR_LIBS,$member,$manager,$CONF,$blogid,$itemid;
		if (!$member->isLoggedIn()) return _NOTLOGGEDIN;
		if (!strstr('createitem itemedit',$type)) return _BADACTION;

		// Resolve itemid and blogid
		if (!$blogid && $itemid) $blogid=getBlogIDFromItemID($itemid);
		if (!$blogid) return _BADACTION;
		if (!$member->teamRights($blogid)) return _ERROR_DISALLOWED;
		$blog=&$manager->getBlog($blogid);
		$convBreaks=false;
		if ($blog) if ($blog->convertBreaks()) $convBreaks=true;

		// Get editing HTML
		$this->actionplugin=true;
		include($DIR_LIBS . 'ADMIN.php');
		$a=new ADMIN();
		$CONF['DisableJsTools']=1;
		switch ($type) {
		case 'createitem':
			ob_start();
			$a->action_createitem();
			$buff=ob_get_contents();
			ob_end_clean();
			break;
		case 'itemedit':
			ob_start();
			$a->action_itemedit();
			$buff=ob_get_contents();
			ob_end_clean();
			break;
		default:
			return _BADACTION;
		}
		
		// Return if not valid editing HTML
		// These codes must be changed when non-compatible Nucleus version comes out.
		if (!preg_match('/<head>/',$buff)) return _ERRORMSG;
		if (!preg_match('/<textarea([^>]*)inputbody([^>]*)>([^>]*)<\/textarea>/',$buff)) return _ERRORMSG;
		if (!preg_match('/<textarea([^>]*)inputmore([^>]*)>([^>]*)<\/textarea>/',$buff)) return _ERRORMSG;

		// Create NucleusFCKeditor instances
		if (!class_exists('NucleusFCKeditor')) include ($this->getDirectory().'fckclass.php');
		$body='';
		$more='';
		if ($type=='itemedit') {
			if (preg_match ('/<textarea([^>]*)inputbody([^>]*)>([^>]*)<\/textarea>/',$buff,$matches))
				$body=$matches[3];
				$body=$this->unhtmlentities($body);
			if (preg_match ('/<textarea([^>]*)inputmore([^>]*)>([^>]*)<\/textarea>/',$buff,$matches))
				$more=$matches[3];
				$more=$this->unhtmlentities($more);
		}
		if ($convBreaks) {
			$body=addBreaks($body);
			$more=addBreaks($more);
		}
		$FCKedit1=new NucleusFCKEditor('body',$this,$body);
		$FCKedit2=new NucleusFCKEditor('more',$this,$more);
		$buff1=$FCKedit1->CreateHtml();
		$buff2=$FCKedit2->CreateHtml();

		// Replace texts of editing page
		// These codes must be changed when non-compatible Nucleus version comes out.
		$buff=preg_replace ('/<head>/','<head><base href="'.htmlspecialchars($CONF['AdminURL']).'">', $buff,1);
		$buff=preg_replace ('/<textarea([^>]*)inputbody([^>]*)>([^>]*)<\/textarea>/',$buff1, $buff,1);
		$buff=preg_replace ('/<textarea([^>]*)inputmore([^>]*)>([^>]*)<\/textarea>/',$buff2, $buff,1);
		echo $buff;
	}
	
	// Redirect to WYSIWYG page when the plugin option is set to do so.
	function event_AdminPrePageHead(&$data){
		global $CONF,$blogid,$itemid;
		if ($this->this_getOption('alwayswysiwyg')!='yes') return;
		switch($data['action']){
		case 'itemedit':
			$type='type=itemedit&itemid='.(int)$itemid;
			break;
		case 'createitem':
			$type='type=createitem&blogid='.(int)$blogid;
			break;
		default:
			return;
		}
		$url=$CONF['ActionURL'].'?action=plugin&name=MitasNom&'.$type;
		if ($this->getOption('usehttps')=='yes') $url=preg_replace('/^http:/','https:',$url);
		redirect($url);
	}
	
	// Solve <%image%> tag for showing items
	function event_PreItem(&$data) {
		$item=&$data['item'];
		$pattern=$this->_patternBeforeEdit();
		$replace=$this->_replaceBeforeEdit($item->authorid);
		for ($i=0;$i<1;$i++){
			$item->body=preg_replace($pattern[$i],$replace[$i],$item->body);
			$item->more=preg_replace($pattern[$i],$replace[$i],$item->more);
		}
	}
	
	// NP_MitasNom option in editing dialog.
	// This is more compatible to newer Nucleus version,
	// unless 'id="inputbody"' and 'id="inputmore"' changed.
	function event_AddItemFormExtras(&$data) { return $this->event_EditItemFormExtras($data); }
	function event_EditItemFormExtras(&$data) {
		global $DIR_LIBS,$member,$manager,$CONF,$blogid,$itemid;
		// Resolve itemid and blogid
		if (!$blogid && $itemid) $blogid=getBlogIDFromItemID($itemid);
		if (!$blogid) return _BADACTION;
		if (!$member->teamRights($blogid)) return _ERROR_DISALLOWED;
		$blog=&$manager->getBlog($blogid);
		$convBreaks=false;
		if ($blog) if ($blog->convertBreaks()) $convBreaks=true;
		$authorid=$member->getID();
		if ($itemid) {
			$item=&$manager->getItem($itemid,1,1);
			$authorid=$item['authorid'];
		}

		if ($this->actionplugin) {
			echo '<input type="hidden" name="mitasnom_wysiwyged" id="mitasnom_wysiwyged" value="full"/>';
			return;
		} else echo '<input type="hidden" name="mitasnom_wysiwyged" id="mitasnom_wysiwyged" value=""/>';
		$dwidth=$this->this_getOption('dialogwidth');
		$dheight=$this->this_getOption('dialogheight');
		$mURL=$CONF['MediaURL'].$member->getID().'/';
?>
<h3>NP_MitasNom</h3>
<script type="text/javascript">
//<![CDATA[
function WYSIWYGpopup(textId){
	window.open('plugins/mitasnom/popup.php?blogid=<?php echo (int)$blogid; ?>&id='+textId,'name',
<?php echo "'status=yes,toolbar=no,scrollbars=yes,resizable=yes,width=$dwidth,height=$dheight,top=0,left=0');\n"; ?>
}
function convBreaks(T) {
	T=T+'';
	T=T.replace(/\x0D\x0A/g,"\x0A");
	T=T.replace(/\x0D/g,"\x0A");
	T=T.replace(/\x0A/g,"<br />\x0A");
	return T;
}
function rmvBreaks(T) {
	T=T+'';
	T=T.replace(/\x0A/g,'');
	T=T.replace(/\x0D/g,'');
	T=T.replace(/<br \/>/g,'\n');
	<?php
		if ($this->this_getOption('returnafterbrbr')!='yes') 
			echo 'while (T.match(/\n\n/g)) T=T.replace(/\n\n/g,"\n<br />");'."\n"; 
		echo 'while (T.match(/<br \/>\n/g)) T=T.replace(/<br \/>\n/g,"<br /><br />");'."\n"; 
	?>
	T=T.replace(/\n<br \/>/g,'<br />\n');
	return T;
}
function WYSIWYGgettext(id){
	var T=document.getElementById(id).value+'';
<?php
	if ($convBreaks) echo "\tT=convBreaks(T);\n";
	$replacearray=$this->_replaceBeforeEdit($authorid);
	foreach($this->_patternBeforeEdit() as $key => $pattern) {
		$replace=$replacearray[$key];
		echo "\tT=T.replace($pattern"."g,'$replace');\n";
	}
?>
	return T;
}
function WYSIWYGsettext(id,T){
<?php
	if ($convBreaks) echo "\tT=rmvBreaks(T);\n"; 
	$replacearray=$this->_replaceAfterEdit();
	foreach($this->_patternAfterEdit() as $key => $pattern) {
		$replace=$replacearray[$key];
		echo "\tT=T.replace($pattern"."g,'$replace');\n";
	}
?>
	document.getElementById(id).value=T;
	document.getElementById('mitasnom_wysiwyged').value='image';
}
//]]>
</script>
<p>
<a href="javascript:WYSIWYGpopup('inputbody');" title="Edit by HTML editor"><?php echo $this->translated('WYSIWYG:body'); ?></a>&nbsp;&nbsp;
<a href="javascript:WYSIWYGpopup('inputmore');" title="Edit by HTML editor"><?php echo $this->translated('WYSIWYG:more'); ?></a>
</p>
<?php
	}

	//<%image%>, <%popup%> conversion
	function event_PrepareItemForEdit(&$data) {
		global $CONF,$member;
		if (!$this->actionplugin) return;
		$item=&$data['item'];
		$pattern=$this->_patternBeforeEdit();
		$replace=$this->_replaceBeforeEdit($item['authorid']);
		$item['body']=preg_replace($pattern,$replace,$item['body']);
		$item['more']=preg_replace($pattern,$replace,$item['more']);
	}
	
	// Creates Nucleus-specific tags (<%image%> <%popup%>) from general HTML tags.
	function event_PreAddItem(&$item) { return $this->event_PreUpdateItem($item); }
	function event_PreUpdateItem(&$item) {
		if (!requestVar('mitasnom_wysiwyged')) return;
		$body=&$item['body'];
		$more=&$item['more'];
		$blog=&$item['blog'];
		$body=$this->_restoreImgPopup($body);
		$more=$this->_restoreImgPopup($more);
		if (requestVar('mitasnom_wysiwyged')!='full') return;
		if ($blog->convertBreaks()) {
			$body=removeBreaks($body);
			$more=removeBreaks($more);
		}
		if ($this->this_getOption('returnafterbr')=='yes') {
			$body=$this->_addEnterAfterBr($body);
			$more=$this->_addEnterAfterBr($more);
		}
		$pattern=$this->_patternAfterEdit();
		$replace=$this->_replaceAfterEdit();
		$body=preg_replace($pattern,$replace,$body);
		$more=preg_replace($pattern,$replace,$more);
	}
	function _addEnterAfterBr(&$data){
		// <br/> => <br/>\n conversion
		$array=preg_split('/<br[^>]*>/',str_replace(array("\r","\n"),'',$data));
		$c=count($array);
		$data=$array[0];
		for ($i=1;$i<$c;$i++) {
			$data.='<br />';
			if (strlen($array[$i]) || $this->this_getOption('returnafterbrbr')=='yes') $data.="\n";
			$data.=$array[$i];
		}
		return $data;
	}
	function _restoreImgPopup(&$data){
		global $CONF,$member,$DIR_MEDIA;
		//$mURL=$CONF['MediaURL'].$member->getID().'/';
		$mURL=$CONF['MediaURL'];
		$pattern='/<img [^>]*?src="'.str_replace('/','\/',$mURL).'[^"]*?"[^>]*? \/>/';
		if (preg_match_all ( $pattern, $data, $matches,PREG_SET_ORDER)) {
			foreach($matches as $match){
				$subject=$match[0];
				$src=$width=$height=$alt='';
				$prop=array();
				foreach(explode('" ',substr($subject,5)) as $value) {
					$i=strpos($value,'=');
					$j=strpos($value,'"');
					if ($i===false || $j===false) continue;
					$key=substr($value,0,$i);
					$value=substr($value,$j+1);
					switch ($key) {
					case 'src':
						if (preg_match('/src="'.str_replace('/','\/',$mURL).'([^"]*?)"/',$subject,$match)) $src=$match[1];
						break;
					case 'width':
						$width=$value;
						break;
					case 'height':
						$height=$value;
						break;
					default:
						$prop[$key]=$value;
						break;
					}
				}
				if (!$prop['title']) $prop['title']=$prop['alt'];
				if (!$prop['alt']) $prop['alt']=$prop['title'];
				foreach ($prop as $key => $value) {
					if (!$alt) $alt.=' ';
					$alt.=$key.'="'.$value.'"';
				}
				$data=str_replace($subject,"<%image($src|$width|$height|$alt)%>",$data);
			}
		}
		$pattern='/<a [\s\S]*?href="'.str_replace('/','\/',$mURL).'[^"]*?"[\s\S]*?<\/a>/';
		if (preg_match_all ( $pattern, $data, $matches,PREG_SET_ORDER)) {
			$i=0;
			foreach($matches as $match){
				$subject=$match[0];
				if (preg_match('/href="'.str_replace('/','\/',$mURL).'([^"]*?)"/',$subject,$match)) $href=$match[1];
				else $href='';
				if (preg_match('/title="width=([^\|]*?)\|height=([^"]*?)"/',$subject,$match)) {
					$width=$match[1];
					$height=$match[2];
				} else {
					$old_level = error_reporting(0);
					$size = @GetImageSize($DIR_MEDIA.$href); 
					error_reporting($old_level);
					$width = ($height = 0);
					if ($size) {
						$width = $size[0];
						$height = $size[1];
					}
				}
				if (preg_match('/>([^<]*?)<\/a>/',$subject,$match)) $alt=$match[1];
				else $alt='';
				if (!$width) $width=100;
				if (!$height) $height=100;
				if ($alt) $data=str_replace($subject,"<%popup($href|$width|$height|$alt)%>",$data);
			}
		}
		return $data;
	}

	//Replacement for <%image%>, <%popup%>
	function _patternBeforeEdit(){
		return array( 	'/<%image\(([0-9]*?)\/([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)alt="([^"]*?)"([^\)]*?)\)%>/',
				'/<%image\(([^\|\/]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)alt="([^"]*?)"([^\)]*?)\)%>/',
				'/<%image\(([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)alt="([^"]*?)"([^\)]*?)\)%>/',
				'/<%image\(([0-9]*?)\/([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%image\(([^\|\/]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%image\(([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%popup\(([0-9]*?)\/([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%popup\(([^\|\/]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%popup\(([^\|]*?)\|([0-9]*?)\|([0-9]*?)\|([^\)]*?)\)%>/',
				'/<%([^%]*?)%>/');
	}
	function _replaceBeforeEdit($authorid) {
		global $CONF;
		$mURL=$CONF['MediaURL'].$authorid.'/';
		return array(	'<img src="'.$CONF['MediaURL'].'$1/$2" width="$3" height="$4" $5 alt="$6"$7 />',
				'<img src="'.$mURL.'$1" width="$2" height="$3" $4 alt="$5"$6 />',
				'<img src="'.$CONF['MediaURL'].'$1" width="$2" height="$3" $4 alt="$5"$6 />',
				'<img src="'.$CONF['MediaURL'].'$1/$2" width="$3" height="$4" alt="$5" title="$5" />',
				'<img src="'.$mURL.'$1" width="$2" height="$3" alt="$4" title="$4" />',
				'<img src="'.$CONF['MediaURL'].'$1" width="$2" height="$3" alt="$4" title="$4" />',
				'<a href="'.$CONF['MediaURL'].'$1/$2" title="width=$3|height=$4">$5</a>',
				'<a href="'.$mURL.'$1" title="width=$2|height=$3">$4</a>',
				'<a href="'.$CONF['MediaURL'].'$1" title="width=$2|height=$3">$4</a>',
				'<table border class="mitasnom"><tr><td>'.
					'<img src="'.$this->getAdminURL().'editor/plugins/nucleus/nucleus.gif" width="42" height="15" alt="nucleustag" />'.
					'<%$1%></td></tr></table>');
	}
	function _patternAfterEdit(){
		return array('/<table[^>]*class="mitasnom"[^>]*>[\s\S]*?<%([^%]*?)%>[\s\S]*?<\/table>/');
	}
	function _replaceAfterEdit() {
		return array('<%$1%>');
	}
	
	// Show information when editing plugin option
	var $errormessage;
	function event_PrePluginOptionsEdit($data){
		if ($this->errormessage) echo "<h4>".$this->errormessage."</h4>";
	}
	function returnWithMessage($text) { $this->errormessage=$text;}

	// Customize toolbar menu
	function event_PostPluginOptionsUpdate($data) {
		$plugid=$data['plugid'];
		if ($plugid!=$this->GetID()) return;
		$this->install();// Refresh member option settings.
		if (!($name=$this->getOption('addremovetoolbar'))) return;
		$name=ereg_replace("[^0-9a-zA-Z]","",$name);
		$this->setOption('addremovetoolbar','');
		if (!($toolbar=$this->getOption('toolbar_custom')))
			return $this->returnWithMessage($this->translated('Toolbar definition is empty'));
		if (strstr(' default full basic custom ',strtolower($name)))
			return $this->returnWithMessage($this->translated('Default, Full, Basic and Custom toolbars cannot be removed.'));
		$ret=$this->getOption('toolbar_'.strtolower($name));//$ret=$this->getOption('toolbar_'.$this);
		if (!($oid=$this->this_getOid('toolbar'))) return;
		if (!($extra=$this->this_getExtra($oid))) return;
		if (strstr(strtolower($extra),strtolower($name))) {
			//delete
			$extra=eregi_replace("\|$name\|$name","",$extra);
			sql_query('UPDATE ' . sql_table('plugin_option_desc') .
				' SET oextra = "'.addslashes($extra). '" WHERE oid=' . (int)$oid);
			$this->deleteOption('toolbar_'.strtolower($name));
			$this->returnWithMessage($name.$this->translated(' toolbar is deleted.'));
		} else {
			//add
			$extra=str_replace("|Custom|Custom","|$name|$name|Custom|Custom",$extra);
			sql_query('UPDATE ' . sql_table('plugin_option_desc') .
				' SET oextra = "'.addslashes($extra). '" WHERE oid=' . (int)$oid);
			$this->createOption('toolbar_'.strtolower($name), $name.$this->translated(' Toolbar'), 'textarea', $toolbar);
			$this->returnWithMessage($name.$this->translated(' toolbar is added.'));
		}
	}

	// Member specific stuff
	function this_getOption($name) {
		global $member;
		if ($this->getOption('usemembersettings')!='yes') return $this->getOption($name);
		if (!$this->_useMemberSpecificOption($name)) return $this->getOption($name);
		return $this->getMemberOption($member->getID(), $name);
	}
	function _useMemberSpecificOption($name) {
		foreach ($this->_allMemberSpecificOptions() as $value) if ($name==$value) return true;
		return false;
	}
	function _allMemberSpecificOptions() {
		return array('width','height','toolbar','returnafterbr','returnafterbrbr',
				'dialogwidth','dialogheight','additionalpsource');
	}

	// SQL stuff
	function this_getOid($name,$context='global'){
		$query = 'SELECT oid, oname FROM ' . sql_table('plugin_option_desc') . 
			' WHERE opid=' . intval($this->plugid).
			' and ocontext="'.addslashes($context).'"';
		$res = sql_query($query);
		while ($o = mysql_fetch_object($res)) if ($o->oname==$name) return $o->oid;
		return null;
	}
	function this_getExtra($oid){
		$query = 'SELECT oextra FROM ' . sql_table('plugin_option_desc') . ' WHERE oid=' . intval($oid);
		$res = sql_query($query);
		if ($o = mysql_fetch_object($res)) return $o->oextra;
		return null;
	}

	// Uppgrading stuff
	function _checkVersion(){
		if (!$this->this_getOid('version')) $this->install();
		else if ($this->getVersion()!=$this->getOption('version')) $this->install();
		$this->setOption('version',$this->getVersion());
	}

	// Overrided function
	function createOption($name, $desc, $type, $defValue = '', $typeExtras = '') {
		if (!$this->this_getOid($name)) parent::createOption($name, $desc, $type, $defValue, $typeExtras);
		if ($this->_useMemberSpecificOption($name)) {
			if ($this->getOption('usemembersettings')=='yes') {
				if (!$this->this_getOid($name,'member')) {
					if ($name=='toolbar') {
						$typeExtras=quickQuery(
							'SELECT oextra as result FROM '.sql_table('plugin_option_desc').
							' WHERE opid='.(int)$this->getID().' AND oname="toolbar"');
					}
					if (($Extras=$typeExtras)=='access=hidden') $Extras='';
					$Extras=str_replace('|Custom|Custom','',$Extras);
					$Extras=str_replace('|Full|Full','',$Extras);
					$this->createMemberOption($name, $desc, $type,$this->getOption($name), $Extras);
				}
			} else {
				if ($this->this_getOid($name,'member'))
					$this->deleteMemberOption($name);
			}
		}
		return 1;
	}

	// Language stuff
	var $langArray;
	function translated($english){
		if (!is_array($this->langArray)) {
			$this->langArray=array();
			$language=$this->getDirectory().'language/'.ereg_replace( '[\\|/]', '', getLanguageName()).'.php';
			if (file_exists($language)) include($language);
		}
		if (!($ret=$this->langArray[$english])) $ret=$english;
		return $ret;
	}

	// General function (found on PHP help page) follows
	function unhtmlentities($string) {
		$trans_tbl = get_html_translation_table (HTML_ENTITIES);
		$trans_tbl = array_flip ($trans_tbl);
		return strtr ($string, $trans_tbl);
	}
} 
?>