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


＊＊＊＊＊　手動で変更しないといけない事項　＊＊＊＊＊

　installsqlite.php の実行で必要な変更のうち重要な物に
ついては殆ど行われますが、一部手動で変更しなければなら
ない部分があります。

　SQLite では、クエリーで『SELECT i.itime FROM ....』と
しても、itime を sql_fetch_xxx でキャッチできません。そ
こで、これらの表現を『SELECT i.itime as itime FROM ....』
の様に変更します。どこを変更すれば良いかは、sqlite.php 
で『$SQLITECONF['DEBUGMODE']=true;』および
『$SQLITECONF['DEBUGREPORT']=true;』を指定すれば、ブログ
のソースコードに『<!--sqlite_DebugMessage .... 
sqlite_DebugMessage-->』で表示されるコメント文を参照する
ことで見つけられる可能性があります。Nucleus3.22では、以
下の物がこれに相当します。

・BLOG.phpの４５６行目付近と５０４行目付近の"i.itime,"。
・ITEM.php ４０行目付近のの"i.itime,"。
・COMMENTS.php ７７行目付近の"c.ctime,"。

(追記：ver 0.65b より、『$SQLITECONF['DEBUGMODE']=true;』
にて対処しています。)

　さらに、install.sql の nucleus_plugin_option テーブル
作成部分のクエリー文を以下のように変更します（auto_increment
を削除します）。

CREATE TABLE `nucleus_plugin_option` (
  `ovalue` text NOT NULL,
  `oid` int(11) NOT NULL,
  `ocontextid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`oid`,`ocontextid`)
) TYPE=MyISAM;

　ver 0.70b 以降、nucleus/libs/backup.php は、自動で変更
されるようになりました。
