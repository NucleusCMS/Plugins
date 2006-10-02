// ================================================
// PHP image browser - iBrowser 
// ================================================
// iBrowser - tinyMCE editor interface (IE & Gecko)
// ================================================
// Developed: net4visions.com
// Copyright: net4visions.com
// License: GPL - see license.txt
// (c)2005 All rights reserved.
// ================================================
// Revision: 1.0                   Date: 06/28/2005
// ================================================

	function TinyMCE_ibrowser_getControlHTML(control_name) {
		switch (control_name) {
			case 'ibrowser':
				return '<img id="{$editor_id}_ibrowser" src="{$pluginurl}/images/ibrowser.gif" title="iBrowser" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreAndSwitchClass(this,\'mceButtonDown\');" onclick="(iBrowser_click(\'{$editor_id}\'));">';
		}	
		return '';
	}
	//-------------------------------------------------------------------------
	// starting iBrowser
	function iBrowser_click(editor, sender) {	
		var wArgs = {};				
		if (tinyMCE.selectedElement != null) {
			if(tinyMCE.selectedElement.nodeName.toLowerCase() == 'img') { // selected object is image		
				if(tinyMCE.selectedElement.parentNode.nodeName.toLowerCase() == 'a') {
					var a = tinyMCE.selectedElement.parentNode; 
				} else {
					var im = tinyMCE.selectedElement;
				}
			} else if (tinyMCE.selectedElement.nodeName.toLowerCase() == 'a') { // selected object is link
				var a = tinyMCE.selectedElement;
			}
		}		
		//-------------------------------------------------------------------------
		if (a) { // selected object is link			
			wArgs.a = a;				
			//var str = a.attributes['onclick'].value;	
			var str = a.mce_onclick;			
			wArgs.popSrc = unescape(str.substring(str.indexOf('?url=')+5, str.indexOf('&')));	// popup image src			 				
			wArgs.popTitle     = a.title;
			wArgs.popClassName = a.className;	
			
			// set image popup properties
			children = (a.childNodes);
			for (var i = 0; i < children.length; i++) {
				if (children[i].tagName == 'IMG') {				
					//wArgs.src 		= children[i].src;					
					//
					tsrc = children[i].src;				
					if (tsrc.lastIndexOf('?') >= 0) { // dynamic thumbnail				
						var str = tsrc.substring(tsrc.lastIndexOf('?')+1,tsrc.length);
						firstIndexOf = str.indexOf('&');
						tstr = str.substring(4, firstIndexOf);
						wArgs.src  = tstr; // image part of src
						wArgs.tsrc = tsrc; // full src incl. dynamic parameters
					} else { // regular image
						wArgs.src = tsrc;							
					}		
					
					//
					wArgs.alt 		= children[i].alt;
					wArgs.title 	= children[i].title;
					wArgs.width 	= children[i].style.width  ? children[i].style.width  : children[i].width;
					wArgs.height 	= children[i].style.height ? children[i].style.height : children[i].height;
					wArgs.border 	= children[i].border;
					wArgs.align 	= children[i].align;
					wArgs.hspace 	= children[i].hspace;
					wArgs.vspace 	= children[i].vspace;
					wArgs.className = children[i].className;
				}
			}			
			
		//-------------------------------------------------------------------------
		} else if (im) { // selected object is image			
			tsrc = im.src;				
			if (tsrc.lastIndexOf('?') >= 0) { // dynamic thumbnail				
				var str = tsrc.substring(tsrc.lastIndexOf('?')+1,tsrc.length);
				firstIndexOf = str.indexOf('&');
				tstr = str.substring(4, firstIndexOf);
				wArgs.src  = tstr; // image part of src
				wArgs.tsrc = tsrc; // full src incl. dynamic parameters
			} else { // regular image
				wArgs.src = tsrc;							
			}		
			wArgs.alt 		= im.alt;
			wArgs.title 	= im.title;
			wArgs.width 	= im.style.width  ? im.style.width  : im.width;
			wArgs.height 	= im.style.height ? im.style.height : im.height;
			wArgs.border 	= im.border;
			wArgs.align 	= im.align;
			wArgs.className = im.className;
			if (im.hspace >= 0) {
				// (-1 when not set under gecko for some reason)
				wArgs.hspace = im.attributes['hspace'].nodeValue;
			}
			if (im.vspace >= 0) {
				// (-1 when not set under gecko for some reason)
				wArgs.vspace = im.attributes['vspace'].nodeValue;
			}			
		}		
		//-------------------------------------------------------------------------
		// open iBrowser dialog
		var winUrl = tinyMCE.baseURL + '/plugins/ibrowser/ibrowser.php?lang=' + tinyMCE.settings['language'];
		if (tinyMCE.isMSIE) { // IE
			var rArgs = showModalDialog(winUrl, wArgs, 'dialogHeight:500px; dialogWidth:580px; scrollbars: no; menubar: no; toolbar: no; resizable: no; status: no;');  
			//-------------------------------------------------------------------------
			// returning from iBrowser (IE) and calling callback function
			if (rArgs) {				
				iBrowser_callback(editor, sender, rArgs);
			}
		} else if (tinyMCE.isGecko) { // Gecko 
			var wnd = window.open(winUrl + '?editor=' + editor + '&callback=iBrowser_callback', 'ibrowser', 'status=no, modal=yes, width=625, height=530');
			wnd.dialogArguments = wArgs;
		}
	}
	//-------------------------------------------------------------------------
	// iBrowser callback
	function iBrowser_callback(editor, sender, iArgs) {
		var ed = tinyMCE.getInstanceById(editor);
		ed.contentWindow.focus();
		if (iArgs) { // IE			
			var rArgs = iArgs;
		} else { // Gecko
			var rArgs = sender.returnValue;
		}
		
		if (tinyMCE.selectedElement != null && tinyMCE.selectedElement.nodeName.toLowerCase() == 'img') { // is current cell a image ?
			var im = tinyMCE.selectedElement;
		}
		if (tinyMCE.selectedElement != null && tinyMCE.selectedElement.nodeName.toLowerCase() == 'a') { // is current cell a link ?
			var a = tinyMCE.selectedElement;
		}	
		
		if (rArgs) {
			if (!rArgs.action) { // no action set - image				
				if (!im) { // new image// no image - create new image								
					this.selectedElement = getFocusElement(editor, sender);
					this.selectedInstance = ed;
					this.selectedInstance.contentDocument.execCommand('insertimage', false, rArgs.src);
					im = this.getElementByAttributeValue(this.selectedInstance.contentDocument.body, 'img', 'src', rArgs.src);					
				}
				// set image attributes
				rArgs.src = eval(tinyMCE.settings['urlconverter_callback'] + "(rArgs.src, im);");
				setAttrib(im, 'src', rArgs.src, true);				
				setAttrib(im, 'alt', rArgs.alt, true);
				setAttrib(im, 'title', rArgs.title, true);
				setAttrib(im, 'align', rArgs.align, true);
				setAttrib(im, 'border', rArgs.border);
				setAttrib(im, 'hspace', rArgs.hspace);
				setAttrib(im, 'vspace', rArgs.vspace);
				setAttrib(im, 'width', rArgs.width);
				setAttrib(im, 'height', rArgs.height);				
				setAttrib(im, 'className', rArgs.className, true); 
			
			} else if (rArgs.action == 1) { // action set - image popup								
				if (a) { // edit popup								
					a.href        = "javascript:void(0);";
					rArgs.popSrc  = escape(rArgs.popSrc);					
					setAttrib(a, 'title', rArgs.popTitle, true);
					setAttrib(a, 'className', rArgs.popClassName, true);						
       
					if (tinyMCE.isMSIE) { // IE
						a.onclick="window.open('" + rArgs.popUrl + "?url=" + rArgs.popSrc + '&clTxt=' + rArgs.popTxt + "','Image', 'width=550, height=300, scrollbars=no, toolbar=no, location=no, status=no, resizable=yes, screenX=100, screenY=100'); return false;";
					} else if (tinyMCE.isGecko) { // Gecko
						a.setAttribute("onclick","window.open('" + rArgs.popUrl + "?url=" + rArgs.popSrc + '&clTxt=' + rArgs.popTxt + "','Image','width=550, height=300, scrollbars=no, toolbar=no, location=no, status=no, resizable=yes, screenX=100, screenY=100'); return false;");     
					}
				} else { // create new popup									
					var a;
					a = document.createElement('A');
					a.href = "javascript:void(0)";
					rArgs.popSrc  = escape(rArgs.popSrc);				
					setAttrib(a, 'title', rArgs.popTitle, true);
					setAttrib(a, 'className', rArgs.popClassName, true);						
					if (tinyMCE.isMSIE) { // IE
						a.onclick="window.open('" + rArgs.popUrl + "?url=" + rArgs.popSrc + '&clTxt=' + rArgs.popTxt + "','Image', 'width=500, height=300, scrollbars=no, toolbar=no, location=no, status=no, resizable=yes, screenX=100, screenY=100'); return false;";
						if (ed.contentWindow.document.selection.type == 'Control') {
							var selection = ed.contentWindow.document.selection.createRange();
							a.innerHTML = selection(0).outerHTML;
							selection(0).outerHTML = a.outerHTML;
						} else {
							var selection = ed.contentWindow.document.selection.createRange();
							if (selection.text == '') {								
								a.innerHTML = '#';
							} else {
								a.innerHTML = selection.htmlText;								
							}
                      		selection.pasteHTML(a.outerHTML);							
						}
					} else if (tinyMCE.isGecko) { // Gecko
						a.setAttribute("onclick","window.open('" + rArgs.popUrl + "?url=" + rArgs.popSrc + '&clTxt=' + rArgs.popTxt + "','Image','width=500, height=300, scrollbars=no, toolbar=no, location=no, status=no, resizable=yes, screenX=100, screenY=100'); return false;");     
						if (ed.contentWindow.getSelection().rangeCount > 0 && ed.contentWindow.getSelection().getRangeAt(0).startOffset != ed.contentWindow.getSelection().getRangeAt(0).endOffset) {
							a.appendChild(ed.contentWindow.getSelection().getRangeAt(0).cloneContents());
						} else {							
							a.innerHTML = '#';
						}        
						insertNodeAtSelection(ed.contentWindow, a);
					}
				}
			//-------------------------------------------------------------------------
			} else if (rArgs.action == 2) { // action set - delete popup link				
				ed.contentDocument.execCommand('Unlink');								
			}
		}
		tinyMCE.triggerNodeChange();
		return;
	}
	//-------------------------------------------------------------------------
	function getFocusElement(editor, sender) {		
		var ed = tinyMCE.getInstanceById(editor);		
		var sel = '' + (window.getSelection ? ed.contentWindow.getSelection() : document.getSelection ? ed.contentWindow.document.getSelection() : ed.contentWindow.document.selection.createRange().text);
		var elm = (sel && sel.anchorNode) ? sel.anchorNode : null;
		if (ed.selectedElement != null && ed.selectedElement.nodeName.toLowerCase() == "img") {
			elm = ed.selectedElement;
		}
		return elm;
	}
	//-------------------------------------------------------------------------
	function getElementByAttributeValue(node, element_name, attrib, value) {
		var elements = this.getElementsByAttributeValue(node, element_name, attrib, value);
		if (elements.length == 0) {
			return null;
		} 
		return elements[0];
	}
	//-------------------------------------------------------------------------
	function getElementsByAttributeValue(node, element_name, attrib, value) {
		var elements = new Array();
		if (node && node.nodeName.toLowerCase() == element_name) {
			if (node.getAttribute(attrib) && node.getAttribute(attrib).indexOf(value) !=  - 1) {
				elements[elements.length] = node;
			} 
		}
		if (node.hasChildNodes) {
			for (var x = 0; x < node.childNodes.length; x++) {
				var childElements = this.getElementsByAttributeValue(node.childNodes[x], element_name, attrib, value);
				for (var i = 0; i < childElements.length; i++) {
					elements[elements.length] = childElements[i];
				} 
			}
		}
		return elements;
	}
	//-------------------------------------------------------------------------
	function setAttrib(element, name, value, fixval) { // set element attributes
		if (!fixval && value != null) {
			var re = new RegExp('[^0-9%]', 'g');
			value = value.replace(re, '');
		}
		if (value != null && value != '') {
			element.setAttribute(name, value);
		} else {
			element.removeAttribute(name);
		}
	}
	//-------------------------------------------------------------------------
	 function insertNodeAtSelection(win, insertNode) { // Gecko
		  // get current selection
		  var sel = win.getSelection();
	
		  // get the first range of the selection
		  // (there's almost always only one range)
		  var range = sel.getRangeAt(0);
	
		  // deselect everything
		  sel.removeAllRanges();
	
		  // remove content of current selection from document
		  range.deleteContents();
	
		  // get location of current selection
		  var container = range.startContainer;
		  var pos = range.startOffset;
	
		  // make a new range for the new selection
		  range = document.createRange();
	
		  if (container.nodeType == 3 && insertNode.nodeType == 3) {	
				// if we insert text in a textnode, do optimized insertion
				container.insertData(pos, insertNode.nodeValue);
		
				// put cursor after inserted text
				range.setEnd(container, pos+insertNode.length);
				range.setStart(container, pos+insertNode.length);	
		  } else {	
				var afterNode;
				if (container.nodeType==3) {	
				  // when inserting into a textnode
				  // we create 2 new textnodes
				  // and put the insertNode in between
		
				  var textNode = container;
				  container = textNode.parentNode;
				  var text = textNode.nodeValue;
		
				  // text before the split
				  var textBefore = text.substr(0,pos);
				  // text after the split
				  var textAfter = text.substr(pos);
		
				  var beforeNode = document.createTextNode(textBefore);
				  var afterNode = document.createTextNode(textAfter);
		
				  // insert the 3 new nodes before the old one
				  container.insertBefore(afterNode, textNode);
				  container.insertBefore(insertNode, afterNode);
				  container.insertBefore(beforeNode, insertNode);
		
				  // remove the old node
				  container.removeChild(textNode);
	
			} else {	
				  // else simply insert the node
				  afterNode = container.childNodes[pos];
				  container.insertBefore(insertNode, afterNode);
			}
	
			range.setEnd(afterNode, 0);
			range.setStart(afterNode, 0);
		  }
	
		  sel.addRange(range);
		  
		  // remove all ranges
		  win.getSelection().removeAllRanges();
	  };