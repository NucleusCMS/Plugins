<?php 
class NP_ToolbarButtons extends NucleusPlugin { 
	function getName() { return get_class($this); } 
	function getAuthor()  { return 'Katsumi + nakahara21'; } 
	function getVersion() { return '0.3'; } 
	function getURL() { return 'http://nakahara21.com';} 
	function getMinNucleusVersion() { return 250; } 
	function getDescription() { return get_class($this).' plugin'; } 
	function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); } 
	function getEventList() { return array('PrepareItemForEdit', 'PreAddItemForm', 
		'AdminPrePageHead', 'AdminPrePageFoot', 
		'AddItemFormExtras', 'EditItemFormExtras', 
		'PreToolbarParse','PrePluginOptionsEdit'); } 
	function install(){
		$this->createOption("lbtns", "Codes (Before default toolbars)", "textarea",''."\n");
		$this->createOption("rbtns", "Codes (After default toolbars)", "textarea",''."\n");
		$this->createOption("addscripts", "Optional Javascripts Codes ", "textarea",''."\n");
	}
	function event_PrePluginOptionsEdit(&$data) {
		global $CONF;
//			$aurl = $CONF['ActionURL'] . '?action=plugin&amp;name=ToolbarButtons&amp;type=redirect';
//			$extra = '<br /><a href="'.$aurl.'" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return help(this.href);">Button Maker</a>';
			$maker = '<form style="margin:0;"><table><tr><td>ボタンの種類</td><td><input type="radio" name="inc_mode" value="3" tabindex="120" checked="checked" id="btn_type_a" /><label for="btn_type_a">A: 選択中の前後にタグ等を挿入する</label> <br /><input type="radio" name="inc_mode" value="5"  id="btn_type_b" /><label for="btn_type_b">B: カーソル位置にテキスト等を挿入する</label></td></tr><tr><td>前に挿入するコード</td><td><input id="preadd" size="40" maxlength="160" value="" />(AB共)</td></tr><tr><td nowrap>後ろに挿入するコード</td><td><input id="postadd" size="40" maxlength="160" value="" />(Aのみ)</td></tr><tr><td>ツールバーチップ</td><td><input id="inputtitle" size="40" maxlength="160" value="" /></td></tr><tr><td>ボタン表示</td><td><input id="buttoncode" size="40" maxlength="160" value="" /></td></tr><tr><td colspan="2"><INPUT TYPE="button" VALUE="コードを生成" onClick="inserButtons()"><span id="so" style="color:red;"></span></td></tr><tr><td colspan="2"><textarea cols="60" rows="12" id="inputcodes" ></textarea><br /><INPUT TYPE="button" VALUE="Beforeに追加" onClick="reflectButtons(0)"><INPUT TYPE="button" VALUE="Afterに追加" onClick="reflectButtons(1)"></form></table>';

		foreach($data['options'] as $tmp){
			if($tmp['name'] == 'lbtns' || $tmp['name'] == 'rbtns'){
/*
				$oid = $tmp['oid'];
				$data['options'][$oid]['extra'] = $extra;
*/
				$name = $tmp['name'];
				$$tmp['name'] = 'plugoption['.$tmp['oid'].']['.$tmp['contextid'].']';
			}
			if($tmp['name'] == 'rbtns'){
				$oid = $tmp['oid'];
				$data['options'][$oid]['extra'] .= <<<EOD
			<script type="text/javascript">
			//<![CDATA[
			function inserButtons(){ 
				var tag="";

				var caution = document.getElementById("so");
				if(document.getElementById('buttoncode').value == ''){
					caution.innerHTML = '『ボタン表示』の入力がありません!';
					return;
				}
				
				caution.innerHTML = '';

				if(document.getElementById('btn_type_a').checked){
					tag = tag + "\\t\\t\\t<span class=\"jsbutton\" \\n\\t\\t\\tonmouseover=\"BtnHighlight(this);\" \\n\\t\\t\\tonmouseout=\"BtnNormal(this);\" \\n\\t\\t\\tonclick=\"insertAroundCaret('";
					tag = tag + document.getElementById('preadd').value;
					tag = tag + "','";
					tag = tag + document.getElementById('postadd').value;
				}

				if(document.getElementById('btn_type_b').checked){
					tag = tag + "\\t\\t\\t<span class=\"jsbutton\" \\n\\t\\t\\tonmouseover=\"BtnHighlight(this);\" \\n\\t\\t\\tonmouseout=\"BtnNormal(this);\" \\n\\t\\t\\tonclick=\"insertAtCaret('";
					tag = tag + document.getElementById('preadd').value;
				}
					tag = tag + "')\" \\n\\t\\t\\ttitle=\"";
					tag = tag + document.getElementById('inputtitle').value;
					tag = tag + "\">\\n\\t\\t\\t";
					tag = tag + document.getElementById('buttoncode').value;
					tag = tag + "\\n\\t\\t\\t</span>\\n";
				document.getElementById('inputcodes').value += tag;		
			} 

			function reflectButtons(lr) {
				elName = ['$lbtns','$rbtns'];
				data = document.getElementById('inputcodes').value;		
				
				ElementsList = document.getElementsByName(elName[lr]);
				for (i = 0; i < ElementsList.length; i++) {
					ElementsList[i].value += data;
				}
				document.getElementById('inputcodes').value = '';		
			}
			function helperinit() {
				var htitle = document.getElementsByTagName("h2");
				subhtitle=document.createElement("div");
				subhtitle.style.fontWeight="normal";
				subhtitle.innerHTML = '$maker';
				htitle[0].appendChild(subhtitle);
				htitle[0].style.styleFloat = "left";
				htitle[1].style.clear = "left";


				var tables = document.getElementsByTagName("table");
				for (i = 0; i < tables.length; i++) {
					tables[i].style.width = "auto";
				}
			}

			window.onload = helperinit;
			
			//]]>
			</script>
EOD;
			}
		}
	}
/*
	function doAction($type){
		global $CONF;
		switch ($type) {
			case 'redirect':
				$file = $this->getDirectory().'buttonmaker.html';
				ob_start();
				include($file);
				$contents = ob_get_contents();
				ob_end_clean();
				echo $contents;
				break;
			default:
				break;
		}
		exit;
	}
*/
	function event_PrepareItemForEdit(&$data){ $this->before(); } 
	function event_PreAddItemForm(&$data){ $this->before(); } 
	var $usefoot; 
	function event_AdminPrePageHead(&$data){ $this->usefoot=true; } 
	function event_AdminPrePageFoot(&$data){ $this->after(); } 
	function event_AddItemFormExtras(&$data){ if (!$this->usefoot) $this->after(); } 
	function event_EditItemFormExtras(&$data){ if (!$this->usefoot) $this->after(); } 
	var $ob_ok; 
	function before() { $this->ob_ok=ob_start(); } 
	function after() { 
		global $manager; 
		if (!$this->ob_ok) return; 
		$buff=ob_get_contents(); 
		ob_end_clean(); 
		$lbutton=''; 
		$rbutton=''; 
		$script=''; 
		$pattern='/<div([^>]*?)class="jsbuttonbar"([^>]*?)>/'; 
		if (preg_match($pattern,$buff,$matches)){ 
			$manager->notify('PreToolbarParse',array('lbutton' => &$lbutton, 'rbutton' => &$rbutton, 'script' => &$script)); 
			$buff=str_replace($matches[0],$matches[0].$lbutton,$buff); 
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
		echo $buff.$script; 
	} 
	function event_PreToolbarParse(&$data) { 
		global $CONF;
		$lbutton=&$data['lbutton']; 
		$rbutton=&$data['rbutton']; 
		$script=&$data['script'];
		
		$setOptionURL = $CONF['AdminURL'] . 'index.php?action=pluginoptions&amp;plugid=' . $this->getID();

		$lbutton.="<div style=\"padding-top:4px;padding-bottom:4px;margin-bottom:1px;\">\n";
		$lbutton.= $this->getOption('lbtns');
		$lbutton.= <<<EOD

EOD;
/*
		$lbutton.= <<<EOD
			<span class="jsbutton" 
			onmouseover="BtnHighlight(this);" 
			onmouseout="BtnNormal(this);" 
			onclick="helloWorld()" >
			heii
			</span>

EOD;
*/
		$lbutton.="</div>\n";

//		$lbutton.="<hr style=\"height:1px;color:#ddd;background-color:#ddd\"/>\n";
		$lbutton.="<div style=\"padding-top:4px;padding-bottom:4px;\">\n";

		$rbutton.="</div>\n";
//		$rbutton.="<hr style=\"height:1px;color:#ddd;background-color:#ddd;margin:0px;\"/>\n";

		$rbutton.="<div style=\"padding-top:4px;padding-bottom:4px;margin-top:1px;\">\n";
		$rbutton.= $this->getOption('rbtns');
		$rbutton.=<<<EOD
			<span class="jsbutton" 
			onmouseover="BtnHighlight(this);" 
			onmouseout="BtnNormal(this);" 
			onclick="entitiesCaret()" 
			title="toEntities" >
			&amp;lt;
			</span>

			<a href="$setOptionURL">Edit Buttons</a>

EOD;
		$rbutton.="</div>\n";
		
		$script.= <<<EOD
			<script type="text/javascript">
			//<![CDATA[

EOD;
		$script.= $this->getOption('addscripts');
		$script.= <<<EOD
			function helloWorld(){
				alert('Hello Left World!');
			}
			function helloWorld2(){
				alert('Hello Right World!')
			}
			function entitiesCaret () {
				var textEl = lastSelected;
				if (textEl && textEl.createTextRange && lastCaretPos) {
					var caretPos = lastCaretPos;
					caretPos.text = caretPos.text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;');
				} else if (!document.all && document.getElementById) {
					newText = mozSelectedText().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;');
					mozReplace(document.getElementById('input' + nonie_FormType), newText);
				}
				updAllPreviews();
			}

EOD;
		$script.= <<<EOD
			//]]>
			</script>

EOD;
	} 
} 
?>