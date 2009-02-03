<?php

define('_NP_PINGSERVER_DESCRIPTION', 'Receive "weblogUpdates.ping"');

// Global options
define('_NP_PINGSERVER_GLOBALOPTION_DBFLAG',      'Delete database tables of this plugin when uninstalling ?');
define('_NP_PINGSERVER_GLOBALOPTION_QUICKMENU',   'Show in quick menu ?');
define('_NP_PINGSERVER_GLOBALOPTION_DESCLEN',     'Entry description\'s width.');
define('_NP_PINGSERVER_GLOBALOPTION_ADDSTR',      'Entry description\'s add str.');
define('_NP_PINGSERVER_GLOBALOPTION_DATEFORMAT',  'Format Entry updatetime.');
define('_NP_PINGSERVER_GLOBALOPTION_LISTHEADER',  'Header of new entry list.');
define('_NP_PINGSERVER_GLOBALOPTION_LISTBODY',    'Body of new entry list.');
define('_NP_PINGSERVER_GLOBALOPTION_LISTFOOTER',  'Footer of new entry list.');
define('_NP_PINGSERVER_GLOBALOPTION_LOGFLAG',     'Add action-log when received ping.');
define('_NP_PINGSERVER_GLOBALOPTION_DATAMAXHOLD', 'Maximum quantity holding data.');

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


