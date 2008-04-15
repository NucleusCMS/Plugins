/* ---------- */
var flgMdEDebug = 0;
var browser = (window.opera) ? 'op' : ( (document.all) ? 'ie' : ( (document.getElementById) ? 'moz' : '' ) );
//var flgSelectedRange = (document.all) ? 0 : 1; //keep no selected-range on IE
var flgSelectedRange = 1;
//var lastNewLineIE; //IE last newline
//var nlBefore;
var useAdminPage;

/* ---------- */
var toggleList = 0;
var toggleQuote = 0;
var toggleLineBreak = 0;
var converter = new Showdown.converter();
var caretAt = new Array(0,0);
var modLen = 0;

window.onload = initMdE;

/* ----- override functions in edit.js ----- */
function updPreview(id) {
	updMdE(id);
}


/* ----- functions for preview ----- */
function initMdE() {
	trace('useAdminPage:'+ useAdminPage);
	document.getElementById('inputbody').onkeydown = keymapMdE;
	document.getElementById('inputmore').onkeydown = keymapMdE;
	if (useAdminPage) updAllPreviews();
}
function updMdE(id) {
	var id = (id) ? id : nonie_FormType; // nonie_FormType == body | more
	var text = document.getElementById('input'+id).value;
	text = mediapreview(text);
	if (id == 'title') {
		if (text == '') text = '(no title)';
	}
	else {
		text = converter.makeHtml(text);
	}
	
	if (useAdminPage) {
		previewMdE("mde-prev"+id, text);
	}
	else {
		previewMdE("prev"+id, text);
	}
}
function previewMdE(id, text) {
	document.getElementById(id).innerHTML = text;
}
function mediapreview(preview) {
	// expand the media commands (without explicit collection)
	preview = preview.replace(/\<\%image\(([^\/\|]*)\|([^\|]*)\|([^\|]*)\|([^)]*)\)\%\>/g,"<img src='"+nucleusMediaURL+nucleusAuthorId+"/$1' width='$2' height='$3' alt=\"$4\" />");

	// expand the media commands (with collection)
	preview = preview.replace(/\<\%image\(([^\|]*)\|([^\|]*)\|([^\|]*)\|([^)]*)\)\%\>/g,"<img src='"+nucleusMediaURL+"$1' width='$2' height='$3' alt=\"$4\" />");
	preview = preview.replace(/\<\%popup\(([^\|]*)\|([^\|]*)\|([^\|]*)\|([^)]*)\)\%\>/g,"<a href='' onclick='if (event &amp;&amp; event.preventDefault) event.preventDefault(); alert(\"popup image\"); return false;' title='popup'>$4</a>");
	preview = preview.replace(/\<\%media\(([^\|]*)\|([^)]*)\)\%\>/g,"<a href='' title='media link'>$2</a>");
	return preview;
}
function keymapMdE(e) {
	var shift, alt, ctrl; 

	if (e != null) { // moz and op 
		keycode = e.which; 
		ctrl  = typeof e.modifiers == 'undefined' ? e.ctrlKey  : e.modifiers & Event.CONTROL_MASK; 
		alt   = typeof e.modifiers == 'undefined' ? e.altKey   : e.modifiers & Event.ALT_MASK; 
		shift = typeof e.modifiers == 'undefined' ? e.shiftKey : e.modifiers & Event.SHIFT_MASK; 
//		e.preventDefault(); e.stopPropagation(); 
	}
	else { // ie
		keycode = event.keyCode; 
		ctrl  = event.ctrlKey; 
		alt   = event.altKey; 
		shift = event.shiftKey; 
//		event.returnValue = false; event.cancelBubble = true; 
	} 

	var k = keychar = '';
	var spCodeTbl = {
		8: 'BackSpace',
		9: 'Tab',
		13:'Enter',
//		16:'Shift',
//		17:'Ctrl',
//		18:'Alt',
		27:'Esc',
		32:'Space',
		33:'PageUp',
		34:'PageDown',
		35:'End',
		36:'Home',
		37:'Left',
		38:'Up',
		39:'Right',
		40:'Down',
		45:'Insert',
		46:'Delete'
		};
	if (! (keychar = spCodeTbl[''+keycode])) {
		keychar = String.fromCharCode(keycode).toUpperCase(); 
	}
	if (ctrl)  k += 'Ctrl+';
	if (alt)   k += 'Alt+';
	if (shift) k += 'Shift+';
	k += keychar;
	trace('key:'+k);
	switch (k) {
	case 'Ctrl+Alt+A': //link (inline)
		keystopMdE(e);
		insertMarkdownLink('inline');
		restoreCaret('top');
		break;
	case 'Ctrl+Alt+F': //link (ref)
		keystopMdE(e);
		insertMarkdownLink('ref');
		restoreCaret('top');
		break;
	case 'Ctrl+Alt+Space': //linebreak
		keystopMdE(e);
		if (toggleLineBreak) removeAtCaretMulti("  ",1);
		else insertAtCaretMulti("  ",2);
		toggleLineBreak = (toggleLineBreak) ? 0 : 1;
		restoreCaret('range');
		break;
	case 'Tab': //tab
		keystopMdE(e);
		insertAtCaretMulti("\t",0);
		restoreCaret('range');
		break;
	case 'Shift+Tab': //remove tab
		keystopMdE(e);
		removeAtCaretMulti("\t");
		restoreCaret('range');
		break;
	case 'Ctrl+Alt+E': //convert entities
		keystopMdE(e);
		convertEntities();
		restoreCaret('range');
		break;
	case 'Ctrl+Alt+W': //revert entities
		keystopMdE(e);
		RevertEntities();
		restoreCaret('range');
		break;
	case 'Ctrl+Alt+U': //unordered list (toggle)
		keystopMdE(e);
		if (toggleList) removeAtCaretMulti("*\t");
		else insertAtCaretMulti("*\t",1);
		toggleList = (toggleList) ? 0 : 1;
		restoreCaret('range');
		break;
	case 'Ctrl+Alt+Q': //quote (toggle)
		keystopMdE(e);
		if (toggleQuote) removeAtCaretMulti('> ');
		else insertAtCaretMulti('> ',0);
		toggleQuote = (toggleQuote) ? 0 : 1;
		restoreCaret('range');
		break;
	case 'Ctrl+Alt+P': //update preview
		keystopMdE(e);
		restoreCaret('top');
		break;
	}
}
function keystopMdE(e) {
	if (e != null) {
		e.preventDefault();
		e.stopPropagation();
	} 
	else {
		event.returnValue = false;
		event.cancelBubble = true;
	}
}

/* ----- user functions ----- */
function convertEntities() {
	caretAt = getCaretAt();
	var seltext = getCaretText();
	var textEl = lastSelected;
	if (textEl && textEl.createTextRange && lastCaretPos) {
		var caretPos = lastCaretPos;
		var newText = seltext; //test
		newText = newText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
		caretPos.text = newText;
		modLen += newText.length - seltext.length;
		//trace("modLen: "+ modLen);
	}
	else if (browser == 'moz' || browser == 'op') {
		newText = mozSelectedText().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
		mozReplace(document.getElementById("input" + nonie_FormType), newText);
		modLen += newText.length - seltext.length;
		//trace("modLen: "+ modLen);
	}
}
function RevertEntities() {
	caretAt = getCaretAt();
	var seltext = getCaretText();
	var textEl = lastSelected;
	if (textEl && textEl.createTextRange && lastCaretPos) {
		var caretPos = lastCaretPos;
		var newText = seltext; //test
		newText = newText.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"");
		caretPos.text = newText;
		modLen += newText.length - seltext.length;
		//trace("modLen: "+ modLen);
	}
	else if (browser == 'moz' || browser == 'op') {
		newText = mozSelectedText().replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"");
		mozReplace(document.getElementById("input" + nonie_FormType), newText);
		modLen += newText.length - seltext.length;
		//trace("modLen: "+ modLen);
	}
}
function insertMarkdownLink(type) {
	switch (type) {
	case 'inline':
		insertAroundCaretPrompt2('[',']','(',')','リンクURL','http://');
		break;
	case 'ref':
		var names = insertAroundCaretPrompt2('[',']','[',']','参照名','');
		var refname = (names[1]) ? names[1] : names[0];
		
		if (refname.substr(0,1) == '^') {
			var text = prompt('脚注を入力','');
		}
		else {
			var text = prompt('リンク情報を入力','http://');
		}
		if (text) {
			text = "\n  ["+ refname +"]: " + text;
			document.getElementById('input' + nonie_FormType).value += text;
			if(scrollTop>-1) {
				document.getElementById('input' + nonie_FormType).scrollTop = scrollTop;
			}
		}
		break;
	}
}

/* ----- core functions ----- */
function insertAtCaretMulti(text, mode) {
	caretAt = getCaretAt();
	var seltext = getCaretText();
	if (seltext != '') {
		var lines = seltext.split("\n");
		var oldlen = seltext.length;
		var indent;
		for (var i=0; i<lines.length; i++) {
			if (lines[i] == '') continue;
			
			if (mode < 2) {
				indent = '';
				if ( mode == 1 && (indent = lines[i].match(/^[\s\t]+/)) ) { //follow indents
					lines[i] = lines[i].replace(indent, indent + text);
				}
				else {
					lines[i] = text + lines[i];
				}
			}
			else if (mode == 2) {
				lines[i] = lines[i] + text;
			}
		}
		seltext = lines.join("\n");
		modLen += seltext.length - oldlen;
		insertAtCaret(seltext); //change selected text to new one.
//		trace("oldlen:"+ oldlen);
//		trace("newlen:"+ seltext.length);
//		trace("modLen:"+ modLen);
	}
	else {
		insertAtCaret(text);
		modLen += text.length;
	}
}
function insertAroundCaretPrompt(textpre, textpost) {
	caretAt = getCaretAt();
	if (isCaretEmpty()) {
		text = prompt('リンクテキストを入力','');
		if (text == null) return;
		else textpre += text;
	}
	insertAroundCaret(textpre, textpost);
}
function insertAroundCaretPrompt2(pre1, post1, pre2, post2, key, defval) {
	caretAt = getCaretAt();
	var textpre = pre1;
	var seltext = '';
	if (isCaretEmpty()) {
		var text = prompt('リンクテキストを入力','');
		if (text == null) return;
		textpre += text;
		seltext = text;
	}
	else {
		seltext = getCaretText();
	}
	var keytext = prompt(key +'を入力',defval);
	if (keytext == null) keytext = '';
	var textpost = post1 + pre2 + keytext + post2; //allows null keytext
	
	insertAroundCaret(textpre, textpost);
	return new Array(seltext, keytext);
}
function removeAtCaret(text) { //moz works only now
	caretAt = getCaretAt();
	if (browser == 'ie') return;
	var txtarea = document.getElementById('input' + nonie_FormType);
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var s1 = (txtarea.value).substring(0, selStart);
	var s2 = (txtarea.value).substring(selStart, selLength);
	var pretext = s1.substring(s1.length - text.length, s1.length);
	if (pretext == text) {
		//trace('remove match: "'+ text +'"');
		s1 = s1.substring(0, s1.length - text.length);
		txtarea.value = s1 + s2;
		modLen -= text.length;
	}
}
function removeAtCaretMulti(text, mode) {
	caretAt = getCaretAt();
	var seltext = getCaretText();
	if (seltext != '') {
		var lines = seltext.split("\n");
		var oldlen = seltext.length;
		var indent, removed, left;
		for (var i=0; i<lines.length; i++) {
			if (lines[i] == '') continue;
			
			indent = removed = left = '';
			if (mode == 1) {
				if ( lines[i].match(text+'$') != null ) {
					lines[i] = lines[i].substring(0, lines[i].length - text.length);
				}
			}
			else if ( indent = lines[i].match(/^[\s\t]+/) ) {
				if (text == "\s" || text == "\t") { //remove a indent in line-top indent strings
					indent = '' + indent;
					removed = indent.replace(text,'');
					lines[i] = lines[i].replace(indent, removed);
				}
				else {
					left = lines[i].substring(indent.length, lines[i].length);
					if (left.indexOf(text) === 0) {
						left = left.substring(text.length, left.length);
					}
					lines[i] = indent + left;
				}
			}
			else {
				if (lines[i].indexOf(text) === 0) {
					lines[i] = lines[i].substring(text.length, lines[i].length);
				}
			}
		}
		seltext = lines.join("\n");
		modLen += seltext.length - oldlen;
		insertAtCaret(seltext); //change selected text to new one.
//		trace("oldlen:"+ oldlen);
//		trace("newlen:"+ seltext.length);
//		trace("modLen:"+ modLen);
	}
	else {
		removeAtCaret(text);
	}
}
function removeAroundCaret(pre, post) {
	caretAt = getCaretAt();
	var seltext = getCaretText();
	if (seltext == '') return;
	var oldlen = seltext.length;
	if (seltext.slice(0,pre.length) == pre && seltext.slice(-(post.length)) == post) {
		seltext = seltext.slice(pre.length,-(post.length));
	}
	modLen += seltext.length - oldlen;
	insertAtCaret(seltext); //change selected text to new one.
}
function getCaretAt() {
	var txtarea = document.getElementById('input' + nonie_FormType);
	var startPos, endPos;
	if (browser == 'moz' || browser == 'op') {
		startPos = txtarea.selectionStart;
		endPos = txtarea.selectionEnd;
	}
	else {
		var docRange = document.selection.createRange();
		var textRange = document.body.createTextRange();
		textRange.moveToElementText(txtarea);
		
		var range = textRange.duplicate();
		range.setEndPoint('EndToStart', docRange);
		
//		trace('last:'+ docRange.text.substr(docRange.text.length -1,1));
//		trace('code:'+ docRange.text.substr(docRange.text.length -1,1).charCodeAt(0));
//		lastNewLineIE = (docRange.text.substr(docRange.text.length -1,1).charCodeAt(0) == 12539); //bad...
		
		var nl1 = range.text.match(/(\r|\n)/g); // IE is crazy on treating newline or something...
		var nlcnt1 = (nl1 == null) ? 0 : nl1.length;
		startPos = range.text.length - nlcnt1;
		
//		var nl2 = docRange.text.match(/(\r|\n)/g);
//		nlBefore = (nl2 == null) ? 0 : nl2.length;
		
		var range = textRange.duplicate();
		range.setEndPoint('EndToEnd', docRange);
		endPos = range.text.length - nlcnt1;
	}
	return new Array(startPos, endPos);
}
function restoreCaret(type) {
//	trace("caretAt: "+ caretAt[0] +", "+ caretAt[1]);
	switch (type) {
	case 'top':
		caretAt[1] = caretAt[0];
		break;
	case 'range':
		if (flgSelectedRange) {
			caretAt[1] += modLen;
			if (caretAt[0] > caretAt[1]) caretAt[0] = caretAt[1];
		}
		else {
			caretAt[1] = caretAt[0];
		}
		break;
	}
//	trace("modLen: "+ modLen);
//	trace("caretAt(recalc): "+ caretAt[0] +", "+ caretAt[1]);
	modLen = 0; //clear
	

	var textEl = document.getElementById('input' + nonie_FormType);
	if (textEl && textEl.createTextRange && lastCaretPos) {
/*		var nl1 = lastCaretPos.text.match(/(\r|\n)/g);
		var nlAfter = (nl1 == null) ? 0 : nl1.length;
		trace('before: '+ nlBefore +', after:'+ nlAfter);
		//if (lastNewLineIE) {
		if (nlBefore != nlAfter) {
			lastCaretPos.text = lastCaretPos.text + "\n"; //re-add erased newline
		}*/
/*		var tRange = textEl.createTextRange();
		tRange.move("character", caretAt[0]);
		tRange.moveEnd("character", caretAt[1] - caretAt[0]);
		tRange.select();*/
	}
	else if (browser == 'moz' || browser == 'op') {
		textEl.selectionStart = caretAt[0];
		textEl.selectionEnd = caretAt[1];
		textEl.focus();
	}
}

function trace(logtxt) {
	if (! flgMdEDebug) return;
	if (window.console) console.log(logtxt);
	else alert(logtxt);
	//else window.status += logtxt +' / ';
}
