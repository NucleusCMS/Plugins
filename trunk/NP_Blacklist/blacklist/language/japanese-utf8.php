<?php 
define('NP_BLACKLIST_name', 'ブラックリスト');
define('NP_BLACKLIST_nameTips', 'ブラックリスト管理メニュー');		

define('NP_BLACKLIST_description', '各種ブラックリストを利用してspamから防ぎます (SpamCheck API 2.0互換)');		

define('NP_BLACKLIST_enabled', 'プラグインを有効にする');		
define('NP_BLACKLIST_redirect', 'ブラックリストに該当した場合のリダイレクト先(空欄の場合にはリダイレクトせずにエラー画面を表示します)');		
define('NP_BLACKLIST_ipblock', 'IPによる防御を有効にするか？');		
define('NP_BLACKLIST_ipthreshold', '同一IPから何度spamを受信したらIPブラックリストに追加するか？');		
define('NP_BLACKLIST_BulkfeedsKey', 'Bulkfeeds API Key');		
define('NP_BLACKLIST_SkipNameResolve', 'DNS逆引きをスキップするか？');

define('NP_BLACKLIST_isCreated', 'が作成されました');
define('NP_BLACKLIST_canNotCreate', 'が作成できません');
define('NP_BLACKLIST_isNotWritable', 'は書き込み可能になっていません.');

define('NP_BLACKLIST_ipBlocked', 'IPによるブロック');
define('NP_BLACKLIST_ipListed', 'IPアドレスが');
define('NP_BLACKLIST_urlListed', 'URLが');
define('NP_BLACKLIST_found', 'に登録されています');
define('NP_BLACKLIST_expressionDeleted', '削除が成功しました');
define('NP_BLACKLIST_containdError', 'NGワードが正規表現として正しくありません');
define('NP_BLACKLIST_alreadyMatched', 'は、すでにNGワードとして登録されています');
define('NP_BLACKLIST_emptyExpression', '空のNGワードを登録することはできません');
define('NP_BLACKLIST_logIsEmpty', 'ログは空です');
define('NP_BLACKLIST_delete', '削除');
define('NP_BLACKLIST_newEntryAdded', '登録が成功しました');
define('NP_BLACKLIST_blockDeleted', '削除が成功しました');
define('NP_BLACKLIST_fileHasBeenReset', 'をリセットしました');
define('NP_BLACKLIST_yourExpression', '入力した文字列');
define('NP_BLACKLIST_matchedRule', 'マッチしたルール');
define('NP_BLACKLIST_didNotMatch', 'マッチするルールはありませんでした');
