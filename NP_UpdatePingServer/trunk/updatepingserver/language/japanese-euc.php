<?php

define('_NP_PINGSERVER_DESCRIPTION', 'Receive "weblogUpdates.ping"');

// Global options
define('_NP_PINGSERVER_GLOBALOPTION_DBFLAG',      '�ץ饰�������������ˡ��ǡ����١����Υơ��֥�������ޤ��� ��');
define('_NP_PINGSERVER_GLOBALOPTION_QUICKMENU',   '�����å���˥塼��ɽ�����ޤ��� ��');
define('_NP_PINGSERVER_GLOBALOPTION_DESCLEN',     'ping���������Ȥκǿ�����ȥ꡼��ɽ��ʸ����');
define('_NP_PINGSERVER_GLOBALOPTION_ADDSTR',      'ping���������Ȥκǿ�����ȥ꡼��������ɽ��ʸ������Ķ�����Ȥ����դ��ä���ʸ����');
define('_NP_PINGSERVER_GLOBALOPTION_DATEFORMAT',  'ping���������Ȥκǿ�����ȥ꡼�ι��������Υե����ޥå�');
define('_NP_PINGSERVER_GLOBALOPTION_LISTHEADER',  '�ǿ�����ȥ꡼�ꥹ�ȤΥإå�');
define('_NP_PINGSERVER_GLOBALOPTION_LISTBODY',    '�ǿ�����ȥ꡼�ꥹ�Ȥ�����');
define('_NP_PINGSERVER_GLOBALOPTION_LISTFOOTER',  '�ǿ�����ȥ꡼�ꥹ�ȤΥեå�');
define('_NP_PINGSERVER_GLOBALOPTION_LOGFLAG',     'ping�������ä����ˡִ����������פ˥����ɲä��ޤ�����');
define('_NP_PINGSERVER_GLOBALOPTION_DATAMAXHOLD', 'ping�����ǡ����κ����ݻ���');

// Global option values
define('_NP_PINGSERVER_GLOBALOPTION_LISTHEADER_VALUE', '<ul class="latestupdate">');
define('_NP_PINGSERVER_GLOBALOPTION_LISTBODY_VALUE',   '<li>BlogName:'
													 . '<a href="<%blogurl%>" title="<%blogtitle%>"><%blogtitle%></a>'
													 . '<ul><li>Latest Entry:'
													 . '<a href="<%entryurl%>" title="<%entrytitle%>"><%entrytitle%></a>'
													 . '@<%datetime%>'
													 . '<ul><li>Description:<small><%entrydesc%></small>'
													 . '</li></ul></li></ul></li>');
define('_NP_PINGSERVER_GLOBALOPTION_LISTFOOTER_VALUE', '</ul>');


