<?php

define('_NP_PINGSERVER_DESCRIPTION', 'Receive "weblogUpdates.ping"');

// Global options
define('_NP_PINGSERVER_GLOBALOPTION_DBFLAG',      'プラグインを削除する時に、データベースのテーブルも削除しますか ？');
define('_NP_PINGSERVER_GLOBALOPTION_QUICKMENU',   'クイックメニューに表示しますか ？');
define('_NP_PINGSERVER_GLOBALOPTION_DESCLEN',     'ping送信サイトの最新エントリーの表示文字数');
define('_NP_PINGSERVER_GLOBALOPTION_ADDSTR',      'ping送信サイトの最新エントリーが、最大表示文字数を超えたときに付け加える文字列');
define('_NP_PINGSERVER_GLOBALOPTION_DATEFORMAT',  'ping送信サイトの最新エントリーの更新日時のフォーマット');
define('_NP_PINGSERVER_GLOBALOPTION_LISTHEADER',  '最新エントリーリストのヘッダ');
define('_NP_PINGSERVER_GLOBALOPTION_LISTBODY',    '最新エントリーリストの本体');
define('_NP_PINGSERVER_GLOBALOPTION_LISTFOOTER',  '最新エントリーリストのフッタ');
define('_NP_PINGSERVER_GLOBALOPTION_LOGFLAG',     'pingを受け取った時に「管理操作履歴」にログを追加しますか？');
define('_NP_PINGSERVER_GLOBALOPTION_DATAMAXHOLD', 'ping受信データの最大保持数');

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


