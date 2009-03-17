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
		?>���Υڡ����Ǥϡ�SQLite �ǻ��Ѥ��뤿��� Nucleus �γƥե�������ѹ���Ԥ��ޤ���<br /><br />
		���٤Ƥ� &quot;mysql_xxx&quot; �ؿ��ƤӽФ���ɽ���� &quot;nucleus_mysql_xxx&quot;���ѹ�����ޤ�.<br /><br />
		<?php if (!function_exists('mysql_query')) {?>
			�����ѹ��ϡ�MySQL �ؿ��� PHP �˥��󥹥ȡ��뤵��Ƥ��ʤ����ˤϡ�ɬ�ܤǤϤ���ޤ���<br />
			���ξ�� &quot;install.php&quot; �� &quot;config.php&quot; �������ѹ���ɬ�פǤ���<br />
			�⤷MySQL �ؿ������󥹥ȡ��뤵��Ƥ��餺������Ū�ˤ⥤�󥹥ȡ��뤵��ʤ����Ȥ��μ¤ʾ�硢<br />
			<a href="?go=yes&amp;modify=no">�����򥯥�å����Ƥ�������</a>(install.php �� config.php ���ѹ�����ޤ�)��<br /><br />
			�����Ǥʤ���С����Υ�󥯤򥯥�å����Ƥ���������<br /><br />
		<?php } ?>
		<a href="?go=yes&amp;modify=yes">�ѹ��򳫻�</a>(���Υǥ��쥯�ȥ�Τ��٤Ƥ� PHP �ե����뤬�ѹ�����ޤ�)��<br /><br />
		�Ϥ�����ˡ��ѹ������٤� PHP �ե�����Υѡ��ߥå���󤬡��ɤ߽񤭲�ǽ�ˤʤäƤ��뤳�Ȥ��ǧ���Ƥ���������
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
	?>���٤Ƥ��ѹ�����λ���ޤ�����<hr />
	<a href="install.php">�����򥯥�å����� Nucleus w/SQLite �Υ��󥹥ȡ���򳫻Ϥ��Ƥ���������</a><br />
	<?php
} else {
	?>All modificatios are sccesfully done.<hr />
	<a href="install.php">Click here to install Nucleus w/SQLite</a><br />
	<?php
}
if (@rename('installsqlite.php','installsqlite.php~')) {
	if ($charset=='EUC-JP') {
		echo '(&quot;installsqlite.php&quot; �� &quot;installsqlite.php~&quot; �˥ե�����̾���ѹ�����Ƥ��ޤ���)';
	} else {
		echo '(&quot;installsqlite.php&quot; has been renamed to &quot;installsqlite.php~&quot;.)';
	}
}
?></body></html>