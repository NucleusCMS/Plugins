/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 * 
 * 	Useful functions used by almost all dialog window pages.
 */
/*
 * Modified for Nucleus Plugin by Katsumi
 * The license turned to GPL.
 */

// Gets a element by its Id. Used for shorter coding.
function GetE( elementId )
{
	return document.getElementById( elementId )  ;
}

function ShowE( element, isVisible )
{
	if ( typeof( element ) == 'string' )
		element = GetE( element ) ;
	element.style.display = isVisible ? '' : 'none' ;
}

function SetAttribute( element, attName, attValue )
{
	if ( attValue == null || attValue.length == 0 )
		element.removeAttribute( attName, 0 ) ;			// 0 : Case Insensitive
	else
		element.setAttribute( attName, attValue, 0 ) ;	// 0 : Case Insensitive
}

function GetAttribute( element, attName, valueIfNull )
{
	var oAtt = element.attributes[attName] ;

	if ( oAtt == null || !oAtt.specified )
		return valueIfNull ? valueIfNull : '' ;

	var oValue = element.getAttribute( attName, 2 ) ;
	
	if ( oValue == null )
		oValue = oAtt.nodeValue ;

	return ( oValue == null ? valueIfNull : oValue ) ;
}

var KeyIdentifierMap = 
{
	End		: 35,
	Home	: 36,
	Left	: 37,
	Right	: 39,
	'U+00007F' : 46		// Delete
} 

// Functions used by text fields to accept numbers only.
function IsDigit( e )
{
	if ( !e )
		e = event ;

	var iCode = ( e.keyCode || e.charCode ) ;

	if ( !iCode && e.keyIdentifier && ( e.keyIdentifier in KeyIdentifierMap ) ) 
			iCode = KeyIdentifierMap[ e.keyIdentifier ] ;

	return (
			( iCode >= 48 && iCode <= 57 )		// Numbers
			|| (iCode >= 35 && iCode <= 40)		// Arrows, Home, End
			|| iCode == 8						// Backspace
			|| iCode == 46						// Delete
			|| iCode == 9						// Tab
		) ;
}

String.prototype.Trim = function()
{
	return this.replace( /(^\s*)|(\s*$)/g, '' ) ;
}

String.prototype.StartsWith = function( value )
{
	return ( this.substr( 0, value.length ) == value ) ;
}

String.prototype.Remove = function( start, length )
{
	var s = '' ;

	if ( start > 0 )
		s = this.substring( 0, start ) ;

	if ( start + length < this.length )
		s += this.substring( start + length , this.length ) ;

	return s ;
}

String.prototype.ReplaceAll = function( searchArray, replaceArray )
{
	var replaced = this ;

	for ( var i = 0 ; i < searchArray.length ; i++ )
	{
		replaced = replaced.replace( searchArray[i], replaceArray[i] ) ;
	}

	return replaced ;
}

function OpenFileBrowser( url, width, height )
{
	// oEditor must be defined.
	
	var iLeft = ( oEditor.FCKConfig.ScreenWidth  - width ) / 2 ;
	var iTop  = ( oEditor.FCKConfig.ScreenHeight - height ) / 2 ;

	var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes,scrollbars=yes" ;
	sOptions += ",width=" + width ; 
	sOptions += ",height=" + height ; 
	sOptions += ",left=" + iLeft ; 
	sOptions += ",top=" + iTop ; 

	// The "PreserveSessionOnFileBrowser" because the above code could be 
	// blocked by popup blockers.
	if ( oEditor.FCKConfig.PreserveSessionOnFileBrowser && oEditor.FCKBrowserInfo.IsIE )
	{
		// The following change has been made otherwise IE will open the file 
		// browser on a different server session (on some cases):
		// http://support.microsoft.com/default.aspx?scid=kb;en-us;831678
		// by Simone Chiaretta.
		var oWindow = oEditor.window.open( url, 'FCKBrowseWindow', sOptions ) ;
		if ( oWindow )
		{
			// Detect Yahoo popup blocker.
			try
			{
				var sTest = oWindow.name ; // Yahoo returns "something", but we can't access it, so detect that and avoid strange errors for the user.
			oWindow.opener = window ;
			}
			catch(e)
			{
			alert( oEditor.FCKLang.BrowseServerBlocked ) ;
	}
		}
		else
			alert( oEditor.FCKLang.BrowseServerBlocked ) ;
	}
    else { var oWindow =window.open( url, 'FCKBrowseWindow', sOptions ) ; }
	if ( oWindow ) {
		if (FCKConfig.NucleusUseImageManager) {// Image-Manager plugin
			SetImageManager_oWindow=oWindow;
			SetImageManager_timer=setInterval("SetImageManager()",1);// Check the window every 1 mili second.
		} else {//media.php
			oWindow.window.opener.includeImage=function(collection,filename,type,width,height)
			{
				if (this.GetE('txtUrl')) this.GetE('txtUrl').value=FCKConfig.NucleusMediaDir+collection+'/'+filename;
				if (this.GetE('txtWidth')) this.GetE('txtWidth').value=width;
				if (this.GetE('txtHeight')) this.GetE('txtHeight').value=height;
				//if (this.GetE('txtAttTitle')) this.GetE('txtAttTitle').value=filename;
			};
			oWindow.window.opener.includeOtherMedia=function(collection, filename)
			{
				if (this.GetE('txtUrl')) this.GetE('txtUrl').value=FCKConfig.NucleusMediaDir+collection+'/'+filename;
			};
		}
	}
}

var SetImageManager_oWindow;
var SetImageManager_timer;
function SetImageManager(){
	var oWindow=SetImageManager_oWindow;
	if (!oWindow.window.onOK) return;
	clearInterval(SetImageManager_timer);
	oWindow.window.base_url=FCKConfig.NucleusMediaDir;
	oWindow.window.__dlg_close=function(val)
		{
			var sActualBrowser;
			try {
				sActualBrowser=oWindow.window.opener.sActualBrowser.toLowerCase();
			} catch(e) {
				sActualBrowser='';
			}
			if (val) switch (sActualBrowser){
			case "link":
				if (oWindow.window.opener.GetE('txtLnkUrl')) oWindow.window.opener.GetE('txtLnkUrl').value=val['f_url'];
				break;
			default:
				if (oWindow.window.opener.GetE('txtUrl')) oWindow.window.opener.GetE('txtUrl').value=val['f_url'];
				if (oWindow.window.opener.GetE('txtWidth')) oWindow.window.opener.GetE('txtWidth').value=val['f_width'];
				if (oWindow.window.opener.GetE('txtWidth')) oWindow.window.opener.GetE('txtHeight').value=val['f_height'];
				//if (oWindow.window.opener.GetE('txtAttTitle')) oWindow.window.opener.GetE('txtAttTitle').value=filename;
			}
			oWindow.close();
		};
}