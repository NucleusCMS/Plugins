<?php 
/*

NP_MarkdownEditor
=================

NP_MarkdownEditor (Markdown Editor for Nucleus) is developped by yu (http://nucleus.datoka.jp/), 
and is inspired by ...
*	[Markdown](http://daringfireball.net/ "Original by John Gruber")
*	[PHP Markdown](http://www.michelf.com/projects/php-markdown/ "by Michel Fortin")
*	[Showdown](http://www.attacklab.net/ "Javascript parser by John Fraser")
*	[NP_ToolbarButtons](http://japan.nucleuscms.org/bb/viewtopic.php?p=19233 "by Katsumi and nakahara21")


Usage
-----
1.	Download "Showdown" and copy showdown.js to nucleus/javascript directory.
2.	Install this plugin.
3.	Disable system option "convert breaks" to off.
4.	Enable option for markdown editor in member settings.

Lisence
-------
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
(see nucleus/documentation/index.html#license for more info)


*/

class NP_MarkdownEditor extends NucleusPlugin { 
	function getName() { return 'Markdown Editor for Nucleus'; } 
	function getAuthor()  { return 'yu'; } 
	function getVersion() { return '0.1'; } 
	function getURL() { return 'http://nucleus.datoka.jp/';} 
	function getMinNucleusVersion() { return 250; } 
	function getDescription() { return _MARKDOWNEDITOR_DESCRIPTION; } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); } 
	function getEventList() { return array(
		'BookmarkletExtraHead', 'AdminPrePageHead', 'AdminPrePageFoot', 
		'AddItemFormExtras', 'EditItemFormExtras', 
		'PreToolbarParse'); } 
	function install(){
		$this->createMemberOption('flgMarkdown', _MARKDOWNEDITOR_FLG_USE, 'yesno', 'no');
	}
	
	var $isadminpage=false; 
	function event_BookmarkletExtraHead(&$data){
		$this->before();
		$data['extrahead'] .= <<< EOH
<link rel="stylesheet" href="styles/bookmarklet-mde.css" type="text/css" />
<script type="text/javascript" src="javascript/showdown.js"></script>
<script type="text/javascript" src="javascript/markdowneditor.js"></script>

EOH;
	}
	function event_AdminPrePageHead(&$data){ 
		$this->action = $data['action'];
		if ($this->action != 'createitem' and $this->action != 'itemedit') return;
		
		$this->isadminpage=true; 
		$this->before();
		
		$data['extrahead'] .= <<< EOH
<link rel="stylesheet" href="styles/admin-mde.css" type="text/css" />
<script type="text/javascript" src="javascript/showdown.js"></script>
<script type="text/javascript" src="javascript/markdowneditor.js"></script>
<script type="text/javascript">
useAdminPage = true;
</script>

EOH;
	} 
	function event_AdminPrePageFoot(&$data){ $this->after(); } 
	function event_AddItemFormExtras(&$data){ if (!$this->isadminpage) $this->after(); } 
	function event_EditItemFormExtras(&$data){ if (!$this->isadminpage) $this->after(); } 
	
	var $ob_ok=false; 
	function before() { 
		global $member; 
		
		if ($this->getMemberOption($member->getID(),'flgMarkdown') == 'yes') $this->ob_ok=ob_start(); 
	} 
	
	function after() { 
		global $manager, $member; 
		if (!$this->ob_ok) return; 
		
		$buff=ob_get_contents(); 
		ob_end_clean(); 
		
		$lbutton=''; 
		$rbutton=''; 
		$script=''; 
		$pattern='/<div([^>]*?)class="jsbuttonbar"([^>]*?)>/'; 
		if (preg_match($pattern,$buff,$matches)){ 
			$manager->notify('PreToolbarParse',array('lbutton' => &$lbutton, 'rbutton' => &$rbutton, 'script' => &$script)); 
			//$buff=str_replace($matches[0],$matches[0].$lbutton,$buff); 
			
			$pattern=array('/<\/div>([^<]*?)<textarea([^>]*?)id="inputbody"([^>]*?)>/', 
				'/<\/div>([^<]*?)<textarea([^>]*?)id="inputmore"([^>]*?)>/'); 
			$replace=array('</div><textarea$2id="inputbody"$3>', 
				'</div><textarea$2id="inputmore"$3>'); 
			$buff=preg_replace($pattern,$replace,$buff); 
			
			$pattern='/<\/div><textarea([^>]*?)id="inputbody"([^>]*?)>/'; 
			if (preg_match($pattern,$buff,$matches)){ 
				$buff=str_replace($matches[0],$rbutton.$matches[0],$buff); 
			}
			$pattern='/<\/div><textarea([^>]*?)id="inputmore"([^>]*?)>/'; 
			if (preg_match($pattern,$buff,$matches)){ 
				$buff=str_replace($matches[0],$rbutton.$matches[0],$buff); 
			}
		} 
		
		if ($this->isadminpage) {
			//no newline/indent between "</textarea>" and "</td>" ... it means "replaced".
			$str1 = _MARKDOWNEDITOR_STR1;
			$str2 = _MARKDOWNEDITOR_STR2;
			$str3 = _MARKDOWNEDITOR_STR3;
			$prevbody = <<< EOH
</textarea></td>
	</tr><tr>
		<td>{$str1}</td>
		<td>
			<div id="mde-prevtitle"></div>
			<div id="mde-prevbody"></div>
		</td>

EOH;
			$prevmore = <<< EOH
</textarea></td>
	</tr><tr>
		<td>{$str1}</td>
		<td>
			<div id="mde-prevmore"></div>
		</td>

EOH;
			$pattern="{</textarea>(?:[^<]+?)</td>}"; //check some chars between "</textarea>" and "</td>"
			$buff = preg_replace($pattern, $prevbody, $buff, 1);
			$buff = preg_replace($pattern, $prevmore, $buff, 1);
		}
		
		echo $buff.$script; 
	}
	
	function event_PreToolbarParse(&$data) { 
		global $CONF;
		$lbutton=&$data['lbutton']; 
		$rbutton=&$data['rbutton']; 
		$script=&$data['script'];
		
		// Left buttons
		$rbutton .= <<< EOH
EOH;
		
		// Right buttons
		$rbutton .= <<< EOH
</div>
<div class="jsbuttonbar">
{$this->rbtns}

EOH;

		// Additional scripts
		$script .= <<< EOH
EOH;
	} 
	
	function init(){
		// include language file for this plugin
		$language = $this->getDirectory().ereg_replace( '[\\|/]', '', getLanguageName()).'.php';
		if (file_exists($language)) include_once($language);
		else include_once($this->getDirectory().'english.php');
		
		/* ----- Add buttons (right of default toolbar) ----- */
		$this->rbtns = <<< EOH
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertMarkdownLink('inline');updAllPreviews();restoreCaret('top');" 
title="Link (inline style) (Ctrl+Alt+A)">
<img src="images/button-link.gif" alt="Link (inline style) (Ctrl+Alt+A)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertMarkdownLink('ref');updAllPreviews();restoreCaret('top');" 
title="Link (reference style) (Ctrl+Alt+F)">
<img src="images/button-link-ref.gif" alt="Link (reference style) (Ctrl+Alt+F)" width="16" height="16"/>
</span>

<span class="jsbuttonspacer"></span>

<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertAtCaretMulti('  ',2);updAllPreviews();restoreCaret('range');" 
title="Line Break (Ctrl+Alt+Space)">
<img src="images/button-linebreak.gif" alt="Line Break (Ctrl+Alt+Space)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="removeAtCaretMulti('  ',1);updAllPreviews();restoreCaret('range');" 
title="Remove Line Break (Ctrl+Alt+Space)">
<img src="images/button-unlinebreak.gif" alt="Remove Line Break (Ctrl+Alt+Space)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertAtCaretMulti('\t',0);updAllPreviews();restoreCaret('range');" 
title="Tab (Tab)">
<img src="images/button-tab.gif" alt="Tab (Tab)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="removeAtCaretMulti('\t');updAllPreviews();restoreCaret('range');" 
title="Remove Tab (Shift+Tab)">
<img src="images/button-untab.gif" alt="Remove Tab (Shift+Tab)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertAtCaretMulti('*\t',1);updAllPreviews();restoreCaret('range');" 
title="List (Ctrl+Alt+U)">
<img src="images/button-list.gif" alt="List (Ctrl+Alt+U)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="removeAtCaretMulti('*\t');updAllPreviews();restoreCaret('range');" 
title="Remove List (Ctrl+Alt+U)">
<img src="images/button-unlist.gif" alt="Remove List (Ctrl+Alt+U)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="insertAtCaretMulti('> ',0);updAllPreviews();restoreCaret('range');" 
title="Quote (Ctrl+Alt+Q)">
<img src="images/button-quote.gif" alt="Quote (Ctrl+Alt+Q)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="removeAtCaretMulti('> ');updAllPreviews();restoreCaret('range');" 
title="Remove Quote (Ctrl+Alt+Q)">
<img src="images/button-unquote.gif" alt="Remove Quote (Ctrl+Alt+Q)" width="16" height="16"/>
</span>

<span class="jsbuttonspacer"></span>

<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="convertEntities();updAllPreviews();restoreCaret('range');" 
title="Convert Entities (Ctrl+Alt+E)">
<img src="images/button-entity.gif" alt="Convert Entities (Ctrl+Alt+E)" width="16" height="16"/>
</span>
<span class="jsbutton" 
onmouseover="BtnHighlight(this);" onmouseout="BtnNormal(this);" 
onclick="RevertEntities();updAllPreviews();restoreCaret('range');" 
title="Revert Entities (Ctrl+Alt+W)">
<img src="images/button-unentity.gif" alt="Revert Entities (Ctrl+Alt+W)" width="16" height="16"/>
</span>

EOH;
//end of $this->rbtns
		
	}
}
?>
