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

// Quick menu
define('_NP_PINGSERVER_QUICKMENU_TITLE',   'ping Server');
define('_NP_PINGSERVER_QUICKMENU_TOOLTIP', '追加モジュールの管理');

// ADMIN Area
define('_NP_PINGSERVER_PLUGIN_OPTION',       'NP_UpdatePingServerのプラグインオプションの設定');
define('_NP_PINGSERVER_GENERAL_SETTINGS',    'モジュール設定');
define('_NP_PINGSERVER_MODULE_TITLE',        'モジュールリスト');
define('_NP_PINGSERVER_MODULE_STATE',        '有効にしますか？');
define('_NP_PINGSERVER_MODULE_ORDERSETTING', 'モジュールの優先順位の設定');
define('_NP_PINGSERVER_MODULE_UPDATED',      'モジュール設定を更新しました');

// Errors
define('_NP_PINGSERVER_ERROR_MODFILEERROR',  'そのようなモジュールは存在しないか、パーミッションが正しく設定されていません');
define('_NP_PINGSERVER_ERROR_MODNOTLOADED',  '何らかの理由により、モジュールがインポートできませんでした');
