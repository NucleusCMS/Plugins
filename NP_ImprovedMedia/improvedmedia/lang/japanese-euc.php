<?php
/**
 * ImprovedMedia plugin for Nucleus CMS
 * Version 3.0.1
 * Written By Mocchi, Feb.28, 2010
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 */

define('_IM_DESCRIPTION',	'�ե���������̾���ѹ���ǽ�����֡��ǥ��쥯�ȥ������ǽ���ɲä��������ե����������ǽ�˺����ؤ��ޤ����ޤ���������ѹ����뤳�Ȥˤ�ꡢ��ǥ������ǥ��쥯�ȥ����̤Υ����֥��Υǥ��쥯�ȥ�����֤������Ѥ��ǽ�Ȥ��ޤ���');
define('_IM_OPTION_PRIVATE',	'�ץ饤�١��ȡ����쥯����󡦥ե������Ȥ��ޤ�����');
define('_IM_OPTION_ITEMDISPLAY',	'��Ĥβ��̤ˤ����ĤΥե������ɽ�����ޤ�������5����50�ޤǡ�');
define('_IM_OPTION_GREYBOX',	'Media Control������ɥ���GreyBox�桼�ƥ���ƥ���Ȥ��ޤ�����');
define('_IM_OPTION_EACHBLOGDIR',	'���줾��Υ����֥���������̥ǥ��쥯�ȥ�ǤΥե�������������Ѥ��ޤ�����');

define('_IM_HEADER_TEXT',	' - ImprovedMedia plugin for Nucleus CMS');
define('_IM_HEADER_RENAME_CONFIRM',	'�ե�����̾���ѹ��ʥ��ƥå� 1��1��');
define('_IM_HEADER_ERASE_CONFIRM',	'�ե�����κ���ʥ��ƥå� 1��1��');
define('_IM_HEADER_UPLOAD_SELECT',	'�ե�����Υ��åץ��ɡʥ��ƥå� 1��1��');
define('_IM_HEADER_EMBED_CONFIRM',	'�ե�����������ʥ��ƥå� 2��1��');
define('_IM_HEADER_EMBED',	'�ե�����������ʥ��ƥå� 2��2��');
define('_IM_HEADER_SUBDIR_CREATE_CONFIRM',	'���֡��ǥ��쥯�ȥ�κ���');
define('_IM_HEADER_SUBDIR_REMOVE_CONFIRM',	'���֡��ǥ��쥯�ȥ�κ��');
define('_IM_HEADER_SUBDIR_RENAME_CONFIRM',	'���֡��ǥ��쥯�ȥ�̾���ѹ�');

define('_IM_ANCHOR_TEXT',	'�ե��������');
define('_IM_VIEW_TT',	' :�ե�����ɽ�� (������������ɥ��������ޤ�)');
define('_IM_FILTER',	'�ե��륿��: ');
define('_IM_FILTER_APPLY',	'�ե��륿��Ŭ��');
define('_IM_FILTER_LABEL',	'�ե��륿������ʸ����ʸ��̵�ط���: ');
define('_IM_UPLOAD_TO',	'���åץ�����');
define('_IM_UPLOAD_NEW',	'�������åץ���');
define('_IM_UPLOADLINK',	'�������ե�����Υ��åץ���');
define('_IM_COLLECTION_SELECT',	'����');
define('_IM_COLLECTION_TT',	'���Υ��쥯����󡦥ǥ��쥯�ȥ���ڤ��ؤ�');
define('_IM_COLLECTION_LABEL',	'���쥯����󡦥ǥ��쥯�ȥ���ѹ�: ');
define('_IM_MODIFIED',	'������');
define('_IM_FILENAME',	'�ե�����̾');
define('_IM_DIMENSIONS',	'������');
define('_IM_WEBLOG_LABEL',	'�����֥�');

define('_IM_FORBIDDEN_ACCESS',	'�ػߤ��Ƥ������ˤ�륢�������Ǥ�');
define('_IM_ALT_TOOLONG',	'����ʸ��40ʸ����Ķ���Ƥ��ޤ�');
define('_IM_ERASE_FAILED',	'�ե�����κ���˼��Ԥ��ޤ���');
define('_IM_MISSING_FILE',	'�ե�����򸫤Ĥ��뤳�Ȥ��Ǥ��ޤ���Ǥ���');
define('_IM_MISSING_DIRECTORY',	'���쥯����󡦥ǥ��쥯�ȥ�򸫤Ĥ��뤳�Ȥ��Ǥ��ޤ���Ǥ���');
define('_IM_REMIND_DIRECTORY',	'���åץ����ѥե�������Ǥ�դΥǥ��쥯�ȥ��������Ƥ���������');
define('_IM_REMIND_MEDIADIR',	'���åץ����ѥե������Ŭ�ڤ����֤���Ƥ��ޤ��󡣥��åץ����ѥե�����Ȥ��Υ����������¤�����ꤷ�Ʋ�������');
define('_IM_RENAME_FORBIDDEN',	'�ե�����˥��������Ǥ��ޤ���Ǥ��������ʤ��Υ������������ǧ���Ƥ�������');
define('_IM_RENAME_FILEEXIST',	'�ѹ�������̾���Υե����뤬���Ǥ�¸�ߤ��Ƥ��ޤ�');
define('_IM_RENAME_TOOLONG',	'�ե�����̾��30ʸ����Ķ���Ƥ��ޤ�');
define('_IM_RENAME_WRONG',	'�ե�����̾�˻���ʸ���ʳ����ޤޤ�Ƥ��ޤ�');
define('_IM_NOTICE',	'��ա�');
define('_IM_FUNCTION',	'��ǽ');
define('_IM_RENAME_FAILED',	'̾�����ѹ��˼��Ԥ��ޤ���');
define('_IM_RENAME_BLANK',	'�ե�����̾������Ǥ�');
define('_IM_RENAME',	'̾���ѹ�');
define('_IM_RENAME_DESCRIPTION',	'�ѹ����̾���򡢳�ĥ�Ҥ̤��ǡ�30ʸ���ޤǤ����Ϥ��Ƥ������������ѤǤ���ʸ���ϱѿ�����3����ε���ʥ�������С����ϥ��ե󡢥ץ饹�ˤǤ������ܸ�ϻ��ѤǤ��ޤ���');
define('_IM_RENAME_AFTER',	'�ѹ����̾��');
define('_IM_RENAME_FILENAME',	'̾�����ѹ�����ե�����');
define('_IM_FILENAME_CLICK',	'�ե�����̾');
define('_IM_FILTER_NONE',	'�ե��륿�ʤ�');
define('_IM_ACTION',	'ư��');
define('_IM_RETURN',	'������');
define('_IM_COLLECTION',	'���쥯����󡦥ǥ��쥯�ȥ�');
define('_IM_COLLECTION_DESC',	'�ե�����򥢥åץ��ɤ���ǥ��쥯�ȥ�����򤷤Ƥ���������');
define('_IM_SHORTNAME',	'�����֥����Ȥ�û��̾');
define('_IM_UPDATE',	'��Ͽ��');
define('_IM_TYPE',	'����');
define('_IM_ERASE',	'���');
define('_IM_ERASE_CONFIRM',	'�ʲ��Υե������õ�ޤ�');
define('_IM_ERASE_DONE',	'�������');
define('_IM_INCLUDE',	'ʸ�Ϥ�����');
define('_IM_INCLUDE_DESC',	'�ʲ��Υե������ʸ�Ϥ��������ޤ���');
define('_IM_INCLUDE_ALT',	'�ե����������ʸ');
define('_IM_INCLUDE_ALT_DESC',	'�ե�����δ�ñ������ʸ��40ʸ���ޤǤ����Ϥ��Ƥ���������ɬ�ܤǤ���');
define('_IM_INCLUDE_MODIFIED',	'��������');
define('_IM_INCLUDE_FILE_SELECTED',	'���򤷤��ե�����');
define('_IM_INCLUDE_WAY',	'�ե������ɽ����ˡ');
define('_IM_INCLUDE_WAY_POPUP',	'����ʸ�򵭻���ɽ����������å��ǥݥåץ��å�ɽ��');
define('_IM_INCLUDE_WAY_INLINE',	'�����򤽤ΤޤޤΥ������ǵ�����������');
define('_IM_INCLUDE_WAY_OTHER',	'���Υե�����ϡ�������Ϥ�������ʸ�����󥫡��ƥ����ȤȤʤ�ޤ���ʸ���������ʸ�򥯥�å�����ȡ��ե����뤬�ݥåץ��å�ɽ������ޤ���');
define('_IM_INCLUDE_CODE',	'���ϲ��̤������ޤ�륳����');
define('_IM_INCLUDE_CODE_DESC',	'���ϲ��̤ˤϡ��ʲ��Υ����ɤ������ޤ�ޤ������Υ����ɤϺ��Խ����ʤ��Ǥ���������');
define('_IM_INCLUDE_WAY_DECIDE',	'����');
define('_IM_UPLOAD_USED_ASCII',	'�ե�����̾��ɬ���ѿ���');
define('_IM_UPLOAD_USED_ASCII_DESC1',	'���ܸ���Ѥ���ȡ����ɥ쥹��ʸ���������ƥ֥饦���ǻ��ȤǤ��ʤ��ʤ�ޤ���ǽ��������ޤ���');
define('_IM_UPLOAD_USED_ASCII_DESC2',	'���ξ��ϡ������ǽ���Ѥ��ƺ�����Ƥ��������������ơ�̾�����ѹ����Ƥ��顢���٥��åץ��ɤ��Ƥ���������');
define('_IM_UPLOAD_USED_FILETYPE',	'�������ѤǤ���ե�����Υ�����');
define('_IM_UPLOAD_CONPLETE',	'�ե�����Υ��åץ��ɤ��������ޤ���');
define('_IM_COLLECTION_AMOUNT',	'�ե������: ');
define('_IM_COLLECTION_BRANK',	'�ե�����ʤ�');
define('_IM_REQUIREMENT',	'����ʸ�����Ϥ��Ƥ�������');
define('_IM_ITEMDISPLAY_WRONG',	'1�ڡ�����ɽ���ե��������5�狼��50��δ֤ǻ��ꤷ�Ƥ���������');

define('_IM_SUBDIR',	'���֡��ǥ��쥯�ȥ�');
define('_IM_COLLECTION_FAILED_READ',	'���쥯����󡦥ǥ��쥯�ȥ����μ����˼��Ԥ��ޤ����������������¤��ǧ���Ʋ�����');
define('_IM_SUBDIR_LABEL',	'���֡��ǥ��쥯�ȥ�: ');
define('_IM_SUBDIR_SELECT',	'����');
define('_IM_SUBDIR_NONE',	'�ʤ�');
define('_IM_SUBDIR_TT',	'���Υ��֡��ǥ��쥯�ȥ���ڤ��ؤ�');
define('_IM_SUBDIR_DESC',	'�ե�����򥢥åץ��ɤ��륵�֡��ǥ��쥯�ȥ�����򤷤Ƥ����������ʤ��ξ��ϥ��쥯����󡦥ǥ��쥯�ȥ����¸����ޤ���');
define('_IM_DISPLAY_FILES',	'�ե�����ɽ��');
define('_IM_SUBDIR_REMOVE',	'�ǥ��쥯�ȥ���');
define('_IM_DISPLAY_SUBDIR',	'���֡��ǥ��쥯�ȥ����');
define('_IM_DISPLAY_SUBDIR_TT',	'���֡��ǥ��쥯�ȥ�δ������̤˰�ư');
define('_IM_DISPLAY_SUBDIR_SELECT',	'�� ��');
define('_IM_CREATE_SUBDIR_CONFIRM',	'���֡��ǥ��쥯�ȥ����');
define('_IM_CREATE_SUBDIR_COLLECTION_LABEL',	'���쥯����󡦥ǥ��쥯�ȥ�λ���');
define('_IM_CREATE_SUBDIR_COLLECTION',	'���֡��ǥ��쥯�ȥ��������륳�쥯����󡦥ǥ��쥯�ȥ����ꤷ�Ƥ�������');
define('_IM_CREATE_SUBDIR_INPUT_NAME',	'���֡��ǥ��쥯�ȥ�Υǥ��쥯�ȥ�̾');
define('_IM_CREATE_SUBDIR_CHARS',	'���֡��ǥ��쥯�ȥ�̾�˻��ꤵ�줿�ʳ���ʸ���郎�Ȥ��Ƥ��ޤ�');
define('_IM_CREATE_SUBDIR_CHARS_DESC',	'���֡��ǥ��쥯�ȥ�̾�����ѤǤ���ʸ���ϡ��ѿ�����3����ε���ʥ�������С����ϥ��ե󡢥ץ饹�ˤǤ�����Ĺ��20ʸ���Ǥ�������ʳ���ʸ��������ܸ�ϻ��ѤǤ��ޤ���');
define('_IM_RENAME_SUBDIR_BLANK',	'���֡��ǥ��쥯�ȥ�̾������Ǥ�');
define('_IM_RENAME_SUBDIR_TOOLONG',	'���֡��ǥ��쥯�ȥ�̾��20ʸ����Ķ���Ƥ��ޤ�');
define('_IM_RENAME_SUBDIR_WRONG',	'���֡��ǥ��쥯�ȥ�̾�˻��ꤵ�줿�ʳ���ʸ���郎�Ȥ��Ƥ��ޤ�');
define('_IM_RENAME_SUBDIR_DUPLICATE',	'Ʊ̾�Υ��֡��ǥ��쥯�ȥ꤬���Ǥ�¸�ߤ��Ƥ��ޤ���');
define('_IM_CREATE_SUBDIR_WRONG',	'���֡��ǥ��쥯�ȥ꤬�����Ǥ��ޤ���Τǡ������д����Ԥ����̤��Ƥ�������');
define('_IM_RENAME_SUBDIR_COLLECTION',	'̾�����ѹ����륵�֡��ǥ��쥯�ȥ�');
define('_IM_SUBDIR_NUM_FILES',	'�ե������');
define('_IM_DISPLAY_SUBDIR_LABEL1',	'���֡��ǥ��쥯�ȥ��: ');
define('_IM_DISPLAY_SUBDIR_LABEL2',	', �ե������: ');
define('_IM_DISPLAY_SUBDIR_RETURN',	'�ե��������');
define('_IM_REMOVE_SUBIDR',	'������륵�֡��ǥ��쥯�ȥ�');
define('_IM_REMOVE_SUBIDR_CONFIRM',	'���֡��ǥ��쥯�ȥ�κ���γ�ǧ');
define('_IM_REMOVE_SUBIDR_REMIND', 	'���֡��ǥ��쥯�ȥ��������ȡ���Υե�����⤹�٤�Ʊ���˺������ޤ������κݡ����Ǥ˥����ƥ������������Ƥ��륳���ɤϡ���������ԤäƤ⼫ư�ǽ񤭴������ޤ��󡣤ʤ������쥯����󡦥ǥ��쥯�ȥ�������뤳�ȤϤǤ��ޤ���');
define('_IM_REMOVE_SUBDIR_FAILED', 	'���֡��ǥ��쥯�ȥ�κ���˼��Ԥ��Ƥ��ޤ������֡��ǥ��쥯�ȥ�⤷���ϴޤޤ�Ƥ���ե�������ǧ���Ƥ���������');
define('_IM_DISPLAY_SUBDIR_CAPTION',	'���֡��ǥ��쥯�ȥ����');
define('_IM_DISPLAY_SUBDIR_NOTHING',	'���֡��ǥ��쥯�ȥ�ʤ�');
define('_IM_SUBDIR_REMOVE_FAILED',	'���֡��ǥ��쥯�ȥ�κ���˼��Ԥ��ޤ��������֡��ǥ��쥯�ȥ�Υ����������¤⤷���ϥ��֡��ǥ��쥯�ȥ���Υե�����Υ����������¤��ǧ���Ʋ�������');
define('_IM_SUBDIR_FAILED_READ',	'���֡��ǥ��쥯�ȥ����μ����˼��Ԥ��ޤ����������������¤��ǧ���Ʋ�����');
?>
