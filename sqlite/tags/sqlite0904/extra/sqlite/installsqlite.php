<?php
/*******************************************
* mysql_xxx => nucleus_mysql_xxx converter *
*                              for Nucleus *
*     ver 0.6.1b  Written by Katsumi       *
*******************************************/

// The license of this script is GPL

// Check lanuage
if (strpos(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']),'ja')===0) {
	$charset='EUC-JP';
} else {
	$charset='iso-8859-1';
}
header("Content-Type: text/html; charset=$charset");

if (!file_exists('./nucleus/sqlite/convert.php')) exit;
include('./nucleus/sqlite/convert.php');

?><html><body><?php 

$myself='installsqlite.php';
if ((@$_GET['go'])!='yes') {

	if ($charset=='EUC-JP') {
		?>このページでは、SQLite で使用するための Nucleus の各ファイルの変更を行います。<br /><br />
		すべての &quot;mysql_xxx&quot; 関数呼び出しの表記が &quot;nucleus_mysql_xxx&quot;に変更されます.<br /><br />
		<?php if (!function_exists('mysql_query')) {?>
			この変更は、MySQL 関数が PHP にインストールされていない場合には、必須ではありません。<br />
			この場合 &quot;install.php&quot; と &quot;config.php&quot; だけに変更が必要です。<br />
			もしMySQL 関数がインストールされておらず、将来的にもインストールされないことが確実な場合、<br />
			<a href="?go=yes&amp;modify=no">ここをクリックしてください</a>(install.php と config.php が変更されます)。<br /><br />
			そうでなければ、下のリンクをクリックしてください。<br /><br />
		<?php } ?>
		<a href="?go=yes&amp;modify=yes">変更を開始</a>(このディレクトリのすべての PHP ファイルが変更されます)。<br /><br />
		始める前に、変更されるべき PHP ファイルのパーミッションが、読み書き可能になっていることを確認してください。
		</body></html><?php
	} else {
		?>This page is to modify Nucleus core files for using SQLite as database engine.<br /><br />
		All the &quot;mysql_xxx&quot; functions will be converted to &quot;nucleus_mysql_xxx&quot;.<br /><br />
		<?php if (!function_exists('mysql_query')) {?>
			This modification is not required if MySQL functions of PHP are not installed in the server.<br />
			In this case, only the &quot;install.php&quot; and &quot;config.php&quot; must be modified.<br />
			If you are sure that the MySQL function is never (now and in future) installed, <br />
			<a href="?go=yes&amp;modify=no">please click here</a>(install.php and config.php will be changed).<br /><br />
			Otherwise, please click following link.<br /><br />
		<?php } ?>
		<a href="?go=yes&amp;modify=yes">Start modification</a>(all the PHP files in this directory will be changed).<br /><br />
		Please make sure that all PHP files to be modified are readable and writable.
		</body></html><?php
	}
	exit;
}

if ((@$_GET['modify'])=='yes') {
	// Obtain all PHP files in current and child directories.
	$phpfiles=array();
	seekPhpFiles('./',$phpfiles,$myself);
	
	// Modify all PHP files; mysql_xxxx is replaced by nucleus_mysql_XXXX.
	$allok=true;
	foreach ($phpfiles as $file) $allok=$allok && changeFunctions($file);
	if (!$allok) ExitWithError();
}

// Modify config.php, install.php and backup.php
modifyConfigInstall();

if ($charset=='EUC-JP') {
	?>すべての変更が終了しました。<hr />
	<a href="install.php">ここをクリックして Nucleus w/SQLite のインストールを開始してください。</a><br />
	<?php
} else {
	?>All modificatios are sccesfully done.<hr />
	<a href="install.php">Click here to install Nucleus w/SQLite</a><br />
	<?php
}
if (@rename('installsqlite.php','installsqlite.php~')) {
	if ($charset=='EUC-JP') {
		echo '(&quot;installsqlite.php&quot; は &quot;installsqlite.php~&quot; にファイル名が変更されています。)';
	} else {
		echo '(&quot;installsqlite.php&quot; has been renamed to &quot;installsqlite.php~&quot;.)';
	}
}
?></body></html>