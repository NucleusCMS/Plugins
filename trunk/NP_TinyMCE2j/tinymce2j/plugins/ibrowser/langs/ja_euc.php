<?php
	// ================================================
	// PHP image browser - iBrowser 
	// ================================================
	// iBrowser - language file: English
	// ================================================
	// Developed: net4visions.com
	// Copyright: net4visions.com
	// License: GPL - see license.txt
	// (c)2005 All rights reserved.
	// ================================================
	// Revision: 1.1                   Date: 07/07/2005
	// ================================================
	
	//-------------------------------------------------------------------------
	// charset to be used in dialogs
	$lang_charset = 'UTF-8';
	// text direction for the current language to be used in dialogs
	$lang_direction = 'ltr';
	//-------------------------------------------------------------------------
	
	// language text data array
	// first dimension - block, second - exact phrase
	//-------------------------------------------------------------------------
	// iBrowser
	$lang_data = array (  
		'ibrowser' => array (
		//-------------------------------------------------------------------------
		// common - im
		'im_001' => 'Image browser',
		'im_002' => 'iBrowser',
		'im_003' => '��˥塼',
		'im_004' => 'iBrowser �������',
		'im_005' => '����',
		'im_006' => '����󥻥�',
		'im_007' => '����',		
		'im_008' => '�����Υ���饤������/�ѹ�',
		'im_009' => '�ץ�ѥƥ�',
		'im_010' => '�����Υץ�ѥƥ�',
		'im_013' => '�ݥåץ��å�',
		'im_014' => '�����Υݥåץ��å�',
		'im_015' => 'About iBrowser',
		'im_016' => 'Section',
		'im_097' => '�ɹ�����...',
		'im_098' =>	'Open section',
		'im_099' => 'Close section',
		//-------------------------------------------------------------------------
		// insert/change screen - in	
		'in_001' => '����������/�ѹ�',
		'in_002' => '�饤�֥��',
		'in_003' => '�饤�֥�������',
		'in_004' => '����',
		'in_005' => '�ץ�ӥ塼',
		'in_006' => '�����κ��',
		'in_007' => '����å��ǳ���ɽ�����ޤ�',
		'in_008' => '���åץ���/�ե�����̾�ѹ�/�ե������������ꥢ��ɽ�����ޤ�',	
		'in_009' => 'Information',
		'in_010' => '�ݥåץ��å�',		
		'in_013' => 'Create a link to an image to be opened in a new window.',
		'in_014' => '�ݥåץ��åץ�󥯤κ��',	
		'in_015' => '�ե��������',	
		'in_016' => '̾�����ѹ�',
		'in_017' => '�����ե�����̾���ѹ�',
		'in_018' => '���åץ���',
		'in_019' => '�����Υ��åץ���',	
		'in_020' => '������',
		'in_021' => 'Check the desired size(s) to be created while uploading image(s)',
		'in_022' => '���ꥸ�ʥ�',
		'in_023' => 'Image will be cropped',
		'in_024' => '���',
		'in_025' => '�ǥ��쥯�ȥ�',
		'in_026' => '�ǥ��쥯�ȥ�κ���',
		'in_027' => '�ǥ��쥯�ȥ�����',
		'in_028' => '��',
		'in_029' => '�⤵',
		'in_030' => 'Type',
		'in_031' => '������',
		'in_032' => '̾��',
		'in_033' => '��������',
		'in_034' => '��������',
		'in_035' => 'Image info',
		'in_036' => 'Click on image to close window',
		'in_037' => '��ž',
		'in_038' => 'Auto rotate: set to exif info, to use EXIF orientation stored by camera. Can also be set to +180&deg; or -180&deg; for landscape, or +90&deg; or -90&deg; for portrait. Positive values for clockwise and negative values for counterclockwise.',
		'in_041' => '',
		'in_042' => 'none',		
		'in_043' => 'portrait',
		'in_044' => '+ 90&deg;',	
		'in_045' => '- 90&deg;',
		'in_046' => 'landscape',	
		'in_047' => '+ 180&deg;',	
		'in_048' => '- 180&deg;',
		'in_049' => '�����',	
		'in_050' => 'exif����',
		'in_051' => 'WARNING: Current image is a dynamic thumbnail created by iManager - parameters will be lost on image change.',
		'in_052' => '�ե�����̾����/����ͥ������������',
		'in_053' => '������',
		'in_054' => '������ɽ��������˥����å�������ޤ�',
		'in_055' => '������ǲ�������������',
		'in_056' => '�ѥ�᡼��',
		'in_057' => '�ѥ�᡼����ǥե���Ȥ˥ꥻ�åȤ���',
		'in_099' => '�ǥե����',	
		//-------------------------------------------------------------------------
		// properties, attributes - at
		'at_001' => 'Image attributes',
		'at_002' => 'Source',
		'at_003' => 'Title',
		'at_004' => 'TITLE�� - �����˥ޥ����򤢤碌���Ȥ��˥ե��Ȥ���ƥ�����',
		'at_005' => 'Description',
		'at_006' => 'ALT�� - ����������ɽ���˻��Ѥ���ƥ�����',
		'at_007' => 'Style',
		'at_008' => '���򤷤��������뤬css����ѤߤǤ��뤳�Ȥ��ǧ���Ƥ�������',
		'at_009' => 'CSS��������',	
		'at_010' => 'Attributes(°��)',
		'at_011' => '\'align\', \'border\', \'hspace\', \'vspace\' °���ϡ�XHTML 1.0 Strict DTD�Υ��ݡ��ȳ��Ǥ��������css�������Ѥ��Ƥ���������',
		'at_012' => 'Align',	
		'at_013' => '�ǥե����',
		'at_014' => 'left',
		'at_015' => 'right',
		'at_016' => 'top',
		'at_017' => 'middle',
		'at_018' => 'bottom',
		'at_019' => 'absmiddle',
		'at_020' => 'texttop',
		'at_021' => 'baseline',		
		'at_022' => 'Size',
		'at_023' => 'Width',
		'at_024' => 'Height',
		'at_025' => 'Border',
		'at_026' => 'V-space',
		'at_027' => 'H-space',
		'at_028' => 'Preview',	
		'at_029' => '�ü�ʸ��������',
		'at_030' => '�ü�ʸ��������',
		'at_031' => 'Reset image dimensions to default values',
		'at_032' => 'Caption',
		'at_033' => 'checked: set image caption / unchecked: no image caption set or remove image caption',
		'at_034' => 'set image caption',
		'at_099' => '�ǥե����',	
		//-------------------------------------------------------------------------		
		// error messages - er
		'er_001' => '���顼',
		'er_002' => '���������򤵤�Ƥ��ޤ���!',
		'er_003' => '���λ��꤬���ͤǤϤ���ޤ���',
		'er_004' => '�⤵�λ��꤬���ͤǤϤ���ޤ���',
		'er_005' => '�Ϥ����λ��꤬���ͤǤϤ���ޤ���',
		'er_006' => '����;��λ��꤬���ͤǤϤ���ޤ���',
		'er_007' => '�岼;��λ��꤬���ͤǤϤ���ޤ���',
		'er_008' => '�����������ޤ� �ե�����̾:',
		'er_009' => 'Renaming of thumbnails is not allowed! Please rename the main image if you like to rename the thumbnail image.',
		'er_010' => '����̾���ѹ����ޤ�',
		'er_011' => '������̾�������Ǥ��뤫�ѹ�����Ƥ��ޤ���!',
		'er_014' => '�����ե�����̾�����Ϥ��Ƥ�������!',
		'er_015' => 'ͭ���ʥե�����̾�����Ϥ��Ƥ�������!',
		'er_016' => 'Thumbnailing not available! Please set thumbnail size in config file in order to enable thumbnailing.',
		'er_021' => '�����򥢥åץ��ɤ��ޤ�',
		'er_022' => '���åץ����� - �������Ԥ�������...',
		'er_023' => '���������򤵤�Ƥ��ʤ����������ե����뤬¸�ߤ��ޤ���',
		'er_024' => 'File',
		'er_025' => '����¸�ߤ��ޤ�! ��񤭤ξ���OK�򲡤��Ƥ�������...',
		'er_026' => '�������ե�����̾�����Ϥ��Ƥ�������!',
		'er_027' => 'Directory doesn\'t physically exist',
		'er_028' => '���åץ�����˥��顼��������ޤ����� �ƻ�Ԥ��Ƥ�������',
		'er_029' => '�����Υե����륿���פ���Ŭ�ڤǤ�',
		'er_030' => '����ϼ��Ԥ��ޤ���! �ƻ�Ԥ��Ƥ�������',
		'er_031' => '���',
		'er_032' => '�ץ�ӥ塼���ꥢ����Ϥ߽Ф��ʤ������ϥ����ष�ޤ���',
		'er_033' => '�ե�����̾�ѹ��˼��Ԥ��ޤ������ƻ�Ԥ��Ƥ�������',
		'er_034' => '�ǥ��쥯�ȥ�����˼��Ԥ��ޤ���! �ƻ�Ԥ��Ƥ�������',
		'er_035' => 'Enlarging is not allowed!',
		'er_036' => '���������������Ǥ��ޤ���',
	  ),	  
	  //-------------------------------------------------------------------------
	  // symbols
		'symbols'		=> array (
		'title' 		=> 'Symbols',
		'ok' 			=> 'OK',
		'cancel' 		=> '����󥻥�',
	  ),	  
	)
?>