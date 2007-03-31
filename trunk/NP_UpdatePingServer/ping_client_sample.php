<html>
<head>
<title>XML-RPC(PHP) Client Demo</title>
</head>
<body>
<h1>XML-RPC(PHP) Client Demo</h1>

<?php
$CONF = array();
include_once("./config.php");	// include Nucleus libs and code
include_once($DIR_LIBS . "xmlrpc.inc.php");
$xmlrpc_internalencoding = _CHARSET;
$xmlrpc_defencoding = 'UTF-8';

//クライアントの作成
	$xmlrpc_host = "your host name";                                        // Nucleus を設置しているサーバのホスト名
	$xmlrpc_path = "/?action=plugin&name=UodatePingServer&type=updateping"; // pingサーバスクリプトのパス
//	$xmlrpc_path = "/RPCS";                                                 // .htaccess を設定している時はこっち
	$c = new xmlrpc_client( $xmlrpc_path, $xmlrpc_host, 80 );
//	$c->setDebug(1);                                                        // デバッグモードを有効にする場合はアンコメント

//メッセージ作成
	$message = new xmlrpcmsg(
	'weblogUpdates.ping',                                                   // pingメソッドの選択
//	'weblogUpdates.extendedPing',                                           // pingメソッドの選択
		array(
		new xmlrpcval('your weblog title'),                                 // ブログのタイトル
		new xmlrpcval('http://your.nucleus.url/path/'),                     // ブログのURL
//		new xmlrpcval('http://your.nucleus.url/path/to/contents/'),         // 変更があったコンテンツのURL(空白でも可)
//		new xmlrpcval('http://your.nucleus.url/path/to/feed.xml'),          // RSSやAtom等のURL
		)
	);
/******* メソッドに weblogUpdates.extendedPing を指定した場合は、3、4番目が必須になります ******/

//メッセージ送信
$response = $c->send($message);

// Process the response.
if (!$response->faultCode()) {
	$struct = $response->value();
	$resultval =  $struct->structmem('message');
    echo "Value: ".$resultval->scalarval();
    echo "The XML received:<pre>" . htmlspecialchars($response->serialize());
    echo "</pre>";

}else{
    echo "Fault Code:   " . $response->faultCode()   . "<br>";
    echo "Fault Reason: " . $response->faultString() . "<br>";
    echo "The XML received:<pre>" . htmlspecialchars($response->serialize());
    echo "</pre>";

}

?>

</body>
</html>