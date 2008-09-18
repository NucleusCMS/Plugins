/*
 * NucleusTag plugin for FCKeditor
 * The license of this file is GPL.
 */

// Nucleus Tag
var FCKNucleusTagProcessor = new Object() ;
FCKNucleusTagProcessor.ProcessDocument = function( document )
{
	var aLinks = document.getElementsByTagName( 'mitasnom' ) ;
	var oLink ;
	var i = aLinks.length - 1 ;
	while ( i >= 0 && ( oLink = aLinks[i--] ) ) {
		if (oLink.title=='nucleustag') {
			var oImg = FCKDocumentProcessors_CreateFakeImage( 'FCK__NucleusTag', oLink.cloneNode(true) ) ;
			oImg.setAttribute( '_fcknucleustag', 'true', 0 ) ;
			oLink.parentNode.insertBefore( oImg, oLink ) ;
			oLink.parentNode.removeChild( oLink ) ;
		}
	}
}

FCKDocumentProcessors.addItem( FCKNucleusTagProcessor ) ;

// Refresh the document
FCK.SetHTML( FCK.GetXHTML( FCKConfig.FormatSource ), true ) ;
