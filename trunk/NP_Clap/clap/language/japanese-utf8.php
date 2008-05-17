<?php 

// plugin description
define('NP_CLAP_mailaddr', '通知先メールアドレス');		
define('NP_CLAP_commentedOnly', 'コメントが書かれている場合のみ通知？');		
define('NP_CLAP_antispam_limit', '連続投稿制限(「回数/秒」で指定。「10/600」で600秒あたり10回まで)');		
define('NP_CLAP_antispam_check', 'spamチェックを有効にするか？');		
define('NP_CLAP_listThanksContent', 'Thanksページ内容(画像のURLとコメントを","で区切りって入力します)');		
define('NP_CLAP_deleteData', 'アンインストール時にデータを削除する');		

define('NP_CLAP_description', 'Nucleusでウェブ拍手を実現します');		
define('NP_CLAP_corrected', 'カテゴリの関連付けを修正しました。(%s個のエラーが修正されました。)');		

define('NP_CLAP_1', '○');
define('NP_CLAP_0', '－');

define('NP_CLAP_NOCONTENT', '<font color="red">コンテンツが見つかりません。管理画面からコンテンツを追加してください。</font>');		

define('NP_CLAP_DAYOFWEEK', '日,月,火,水,木,金,土');

define('NP_CLAP_ALLCHECK', '項目を全て<a href="#" onclick="return checkAll(%s,true);">チェックする</a>/<a href="#" onclick="return checkAll(%s,false);">チェックをはずす</a><br />');