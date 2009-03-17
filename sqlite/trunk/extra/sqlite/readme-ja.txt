　このノートでは、Nucleus の新しいバージョンが公開され
た際に、SQLite 版を製作する方法について述べます。


＊＊＊＊＊　installsqlite.php について。　＊＊＊＊＊

　主な使用目的は、Nucleus のバージョンアップがあった際
に、SQLite 対応にするためにコアファイルを書き換えるこ
とです。コアの PHP ファイルの mysql_xxxx() がすべて、
nucleus_mysql_xxxx() に書き換えられます。使用する際は、
このファイルを Nucleus のルートディレクトリ(config.php
のある場所)に移動し、ブラウザでアクセスしてください。

　加えて、install.php 及び、config.php に、
『include($DIR_NUCLEUS.'sqlite/sqlite.php');』が自動的に
書き足されます。

　backup.php は、SQLite でのデータリストアが最適化され
るように、変更されます（sqlite_restore_execute_queries()
が使用されるようになります）。

　さらに、install.php でのインストール画面のHTMLが若干
修正され、一部のMySQL特異的なオプションに『dummy』が指
定されるように変更されます。

　install.sql の nucleus_plugin_option テーブル作成部分の
クエリー文が以下のように変更されます（auto_incrementが削除
されます）。

CREATE TABLE `nucleus_plugin_option` (
  `ovalue` text NOT NULL,
  `oid` int(11) NOT NULL,
  `ocontextid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`oid`,`ocontextid`)
) TYPE=MyISAM;


＊＊＊＊＊　手動で変更しないといけない事項　＊＊＊＊＊

　installsqlite.php の実行で必要な変更のうち重要な物に
ついては殆ど行われますが、一部手動で変更しなければなら
ない部分がある可能性があります。

　SQLite wrapper 0.81 では、Nucleus 3.2xとNucleus3.3を
変更する上で、手動での変更は必要ありません。
