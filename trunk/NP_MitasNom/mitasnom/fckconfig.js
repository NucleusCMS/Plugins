/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: fckconfig.js
 * 	Editor configuration settings.
 * 	See the documentation for more info.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

/*
 * fckconfig.js for Nucleus plugin.
 * Modified by Katsumi
 * The license of this library turned to GPL.
 */

FCKConfig.EditorAreaCSS = FCKConfig.BasePath + 'css/fck_editorarea.css' ;

FCKConfig.DocType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' ;

FCKConfig.BaseHref = '' ;

FCKConfig.FullPage = false ;

FCKConfig.Debug = false ;
FCKConfig.AllowQueryStringDebug = true ;

FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/default/' ;

FCKConfig.PluginsPath = FCKConfig.BasePath + 'plugins/' ;

// FCKConfig.Plugins.Add( 'placeholder', 'de,en,fr,it,pl' ) ;

//ProtectedSources are added in config.php

//FCKConfig.ProtectedSource.Add( /<script[\s\S]*?\/script>/gi ) ;	// <SCRIPT> tags.
//FCKConfig.ProtectedSource.Add( /<%[\s\S]*?%>/g ) ;	// ASP style server side code <%...%>
//FCKConfig.ProtectedSource.Add( /<\?[\s\S]*?\?>/g ) ;	// PHP style server side code <?...?>
//FCKConfig.ProtectedSource.Add( /(<asp:[^\>]+>[\s|\S]*?<\/asp:[^\>]+>)|(<asp:[^\>]+\/>)/gi ) ;	// ASP.Net style tags <asp:control>

FCKConfig.AutoDetectLanguage	= true ;
FCKConfig.DefaultLanguage	= 'en' ;
FCKConfig.ContentLangDirection	= 'ltr' ;

FCKConfig.EnableXHTML		= true ;	// Unsupported: Do not change.
FCKConfig.EnableSourceXHTML	= true ;	// Unsupported: Do not change.

FCKConfig.ProcessHTMLEntities	= true ;
FCKConfig.IncludeLatinEntities	= true ;
FCKConfig.IncludeGreekEntities	= true ;

FCKConfig.FillEmptyBlocks	= true ;

FCKConfig.FormatSource		= true ;
FCKConfig.FormatOutput		= true ;
FCKConfig.FormatIndentator	= '    ' ;

FCKConfig.ForceStrongEm = true ;
FCKConfig.GeckoUseSPAN	= true ;
FCKConfig.StartupFocus	= false ;
FCKConfig.ForcePasteAsPlainText	= false ;
FCKConfig.AutoDetectPasteFromWord = true ;	// IE only.
FCKConfig.ForceSimpleAmpersand	= false ;
FCKConfig.TabSpaces		= 0 ;
FCKConfig.ShowBorders	= true ;
FCKConfig.ToolbarStartExpanded	= true ;
FCKConfig.ToolbarCanCollapse	= true ;
FCKConfig.IEForceVScroll = false ;
FCKConfig.IgnoreEmptyParagraphValue = true ;
FCKConfig.PreserveSessionOnFileBrowser = false ;
FCKConfig.FloatingPanelsZIndex = 10000 ;

FCKConfig.ToolbarSets["Default"] = [
	['Source','Save','Preview'],
	['Cut','Copy','Paste'],
	['Find','Replace','RemoveFormat'],
	['Link','Unlink','Anchor'],
	['Image','Table','Smiley','SpecialChar'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
	'/',
	['Style','FontFormat'],
	['TextColor','FontName','FontSize']
];

FCKConfig.ToolbarSets["Full"] = [
	['Source','DocProps','-','Save','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['OrderedList','UnorderedList','-','Outdent','Indent'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak','UniversalKey'],
	['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
	'/',
	['Style','FontFormat','FontName','FontSize'],
	['TextColor','BGColor'],
	['About']
] ;

FCKConfig.ToolbarSets["Basic"] = [
	['Source','Save','Preview','-','Bold','Italic','RemoveFormat','-','Image','Link','Unlink']
] ;

FCKConfig.ContextMenu = ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','TableCell','Table','Form'] ;

FCKConfig.FontColors = '000000,993300,333300,003300,003366,000080,333399,333333,800000,FF6600,808000,808080,008080,0000FF,666699,808080,FF0000,FF9900,99CC00,339966,33CCCC,3366FF,800080,999999,FF00FF,FFCC00,FFFF00,00FF00,00FFFF,00CCFF,993366,C0C0C0,FF99CC,FFCC99,FFFF99,CCFFCC,CCFFFF,99CCFF,CC99FF,FFFFFF' ;

FCKConfig.FontNames		= 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;
FCKConfig.FontSizes		= '1/xx-small;2/x-small;3/small;4/medium;5/large;6/x-large;7/xx-large' ;
FCKConfig.FontFormats	= 'p;div;pre;address;h1;h2;h3;h4;h5;h6' ;

FCKConfig.StylesXmlPath		= FCKConfig.EditorPath + 'fckstyles.xml' ;
FCKConfig.TemplatesXmlPath	= FCKConfig.EditorPath + 'fcktemplates.xml' ;

FCKConfig.SpellChecker			= 'ieSpell' ;	// 'ieSpell' | 'SpellerPages'
FCKConfig.IeSpellDownloadUrl	= 'http://www.iespell.com/rel/ieSpellSetup211325.exe' ;

FCKConfig.MaxUndoLevels = 15 ;

FCKConfig.DisableImageHandles = false ;
FCKConfig.DisableTableHandles = false ;

FCKConfig.LinkDlgHideTarget		= false ;
FCKConfig.LinkDlgHideAdvanced	= false ;

FCKConfig.ImageDlgHideLink		= false ;
FCKConfig.ImageDlgHideAdvanced	= false ;

FCKConfig.FlashDlgHideAdvanced	= false ;

FCKConfig.SmileyPath	= FCKConfig.BasePath + 'images/smiley/msn/' ;
FCKConfig.SmileyImages	= ['regular_smile.gif','sad_smile.gif','wink_smile.gif','teeth_smile.gif','confused_smile.gif','tounge_smile.gif','embaressed_smile.gif','omg_smile.gif','whatchutalkingabout_smile.gif','angry_smile.gif','angel_smile.gif','shades_smile.gif','devil_smile.gif','cry_smile.gif','lightbulb.gif','thumbs_down.gif','thumbs_up.gif','heart.gif','broken_heart.gif','kiss.gif','envelope.gif'] ;
FCKConfig.SmileyColumns = 8 ;
FCKConfig.SmileyWindowWidth		= 320 ;
FCKConfig.SmileyWindowHeight	= 240 ;

//Following options were Changed for Nucleus.

FCKConfig.Plugins.Add('nucleus');

//var _FileBrowserLanguage	= 'php' ;	// asp | aspx | cfm | lasso | perl | php | py
//var _QuickUploadLanguage	= 'php' ;	// asp | aspx | cfm | lasso | php
// extension to use for the default File Browser (Perl uses "cgi").
//var _FileBrowserExtension = _FileBrowserLanguage == 'perl' ? 'cgi' : _FileBrowserLanguage ;

FCKConfig.UseBROnCarriageReturn	= true ;	// IE only.

var NucleusMediaWindowWidth=500;
var NucleusMediaWindowHeight=450;
var NucleusMediaWindowURL="../../../../media.php";
var NucleusMediaDir="media/";

FCKConfig.LinkBrowser = true ;
FCKConfig.LinkBrowserWindowWidth	= NucleusMediaWindowWidth ;
FCKConfig.LinkBrowserWindowHeight	= NucleusMediaWindowHeight ;
FCKConfig.LinkBrowserURL= NucleusMediaWindowURL ;

FCKConfig.ImageBrowser = true ;
FCKConfig.ImageBrowserWindowWidth  = NucleusMediaWindowWidth ;
FCKConfig.ImageBrowserWindowHeight = NucleusMediaWindowHeight ;
FCKConfig.ImageBrowserURL= NucleusMediaWindowURL;

FCKConfig.FlashBrowser = true ;
FCKConfig.FlashBrowserWindowWidth  = NucleusMediaWindowWidth ;
FCKConfig.FlashBrowserWindowHeight = NucleusMediaWindowHeight ;
FCKConfig.FlashBrowserURL= NucleusMediaWindowURL;

FCKConfig.LinkUpload = false ;
FCKConfig.ImageUpload = false ;//Changed for Nucleus
FCKConfig.FlashUpload = false ;//Changed for Nucleus

FCKConfig.NucleusMediaDir=NucleusMediaDir;

FCKConfig.CustomConfigurationsPath = '../config.php' ;

//Above options  were Changed for Nucleus.

if( window.console ) window.console.log( 'Config is loaded!' ) ;	// @Packager.Compactor.RemoveLine