<?php 

// plugin description
define('_PAINT_defaultWidth', '�ǥե���ȤΥ����Х�������(��)');		
define('_PAINT_defaultHeight', '�ǥե���ȤΥ����Х�������(�⤵)');		
define('_PAINT_defaultAnimation', 'ư��ե��������¸���뤫��');		
define('_PAINT_defaultApplet', '�ǥե����Applet');
define('_PAINT_defaultPalette', '�ǥե����Palette');
define('_PAINT_defaultImgType', '�ǥե���Ȥβ�������');
define('_PAINT_defaultImgCompress', '����������AUTO�ξ��θ���������Ψ[0-100]');		
define('_PAINT_defaultImgDecrease', '����������AUTO�ξ��˸�����ͭ���ˤʤ�����[KB](���Ѥ��ʤ�����0)');		
define('_PAINT_defaultImgQuality', '����������JPG�ξ��β����ʼ�[0-100]');		
define('_PAINT_defaultAppletQuality', '�ǥե���Ȥ�Quality(�����ڥ��󥿡��Τ�)');
define('_PAINT_bodyTpl', '��ʸ�ƥ�ץ졼��');
define('_PAINT_tagTpl', 'Paint�����ƥ�ץ졼��');
define('_PAINT_imageTpl', '�������ƥ�ץ졼��');
define('_PAINT_continueTpl', 'Continue���ƥ�ץ졼��');
define('_PAINT_debug', '������Ϥ�Ԥ�����');

// log message
define('_PAINT_DESCRIPTION',			'�����������ץ�åȤȤ�Ϣ�Ȥ��Ǥ���褦�ˤ��ޤ����ܤ����ϥإ�פ򻲾Ȥ��Ƥ���������');
define('_PAINT_HeadersAlreadySent'.		'�إå��Ϥ��Ǥ���������Ƥ��ޤ�');
define('_PAINT_NeedLogin',				'������ɬ�פǤ�');
define('_PAINT_UserNotFound',			'�桼������¸�ߤ��ޤ���');
define('_PAINT_InvalidTicket',			'�����åȤ�ͭ���ǤϤ���ޤ�����Ʋ��̱����Ρ֥����åȤ򹹿�����פ�¹Ԥ��Ƥ��������Ƥ��Ƥ���������');
define('_PAINT_phpVersion_before',		'NP_Paint�ˤ�PHP');
define('_PAINT_phpVersion_after',		'�ʹߤ�ɬ�פǤ�');
define('_PAINT_illegalCollection',		'������Collection�����ꤵ��Ƥ��ޤ�');
define('_PAINT_canNotFindApplet',		'Applet�����Ĥ���ޤ���');
define('_PAINT_fileIsNotSet',			'�ե����뤬���ꤵ��Ƥ��ޤ���');
define('_PAINT_viewerIsNotSet',			'�ӥ奢�������ꤵ��Ƥ��ޤ���');
define('_PAINT_canNotFindViewer',		'�ӥ奢�������Ĥ���ޤ���');
define('_PAINT_canNotReadFile',			'�ե�������ɤ߹���ޤ���');
define('_PAINT_canNotFindPrefix',		'Prefix�����Ĥ���ʤ��Τ��������ޤ�');
define('_PAINT_deleteFile',				'�ե�����������ޤ�');
define('_PAINT_deleteFile_failure',		'�ե�����κ���˼��Ԥ��ޤ���');
define('_PAINT_rename_failure',			'tmpfile�Υ�͡���˼��Ԥ��ޤ���');
define('_PAINT_rename_ok',				'tmpfile���͡��ष�ޤ���');
define('_PAINT_GDNotSupported',			'PHP��GD�����ݡ��Ȥ���Ƥ��ޤ��󡣲����Ѵ��򥹥��åפ��ޤ�');
define('_PAINT_convertToJpg',			'������JPG���Ѵ����ޤ�');
define('_PAINT_convertToJpg_succeeded',	'������JPG�Ѵ����������ޤ���');
define('_PAINT_convertToJpg_failure',	'������JPG�Ѵ��˼��Ԥ��ޤ���');
define('_PAINT_pngRead_failure',		'PNG�������ɤ߹��ߤ˼��Ԥ��ޤ���');
define('_PAINT_canNotLoadClass',		'���饹�Υ��ɤ˼��Ԥ��ޤ���');

define('_PAINT_STAR',					'��');

// index.php
define('_Paint_directoryNotWriteable',	'�ǥ��쥯�ȥ꤬¸�ߤ��ʤ������񤭹��߲�ǽ�ˤʤäƤ��ޤ���: ');
define('_PAINT_fileOpen_failure',		'�ե�����Υ����ץ�˼��Ԥ��ޤ���');
define('_PAINT_canNotAutoInstall',		'���ε�ǽ�ϼ�ư���󥹥ȡ���Ǥ��ޤ���');
define('_PAINT_autoInstall',			'���󥹥ȡ����ǽ��Applet/Palette/����¾');
define('_PAINT_noSuchPlugin',			'���Τ褦�ʥץ饰����Ϥ���ޤ���');
define('_PAINT_appletinstall',			'Applet/Palette�Υ��󥹥ȡ���');
define('_PAINT_iniDownload',			'����ե�����Υ��������');
define('_PAINT_doDownload',				'��ư���󥹥ȡ����ɬ�פ�����ե��������������');
define('_PAINT_installSuffix',			'�Υ��󥹥ȡ���');
define('_PAINT_downloadSuffix',			'����ե�������������ɤ��ƥ����Ф����֤��Ƥ�������');

// Applet
define('_PAINT_Applet',	'(��������Applet)');
define('_PAINT_Applet_PaintBBS_name',	'PaintBBS');
define('_PAINT_Applet_Shipainter_name',	'�����ڥ��󥿡�');
define('_PAINT_Applet_Shipainterpro_name',	'�����ڥ��󥿡�Pro');

// Palette
define('_PAINT_Palette',	'(ưŪ�ѥ�å�)');

// Parser
define('_PAINT_Parser_useinput',				'php://input�����Ѥ��ƥǡ�����������ޤ�');
define('_PAINT_Parser_contentLengthNotFound',	'CONTENT_LENGTH�������Ǥ��ޤ���Ǥ�����³�Ԥ��ޤ�');

// Viewer
define('_PAINT_Viewer_infoNotFond',	'����Ϥ���ޤ���');
define('_PAINT_Viewer_spch_desc',	'(�����ڥ��󥿡��Υ��˥᡼��������)');
define('_PAINT_Viewer_pch_desc',	'(PaintBBS�Υ��˥᡼��������)');
