�@���̃m�[�g�ł́ANucleus �̐V�����o�[�W���������J����
���ۂɁASQLite �ł𐻍삷����@�ɂ��ďq�ׂ܂��B


�����������@installsqlite.php �ɂ��āB�@����������

�@��Ȏg�p�ړI�́ANucleus �̃o�[�W�����A�b�v����������
�ɁASQLite �Ή��ɂ��邽�߂ɃR�A�t�@�C�������������邱
�Ƃł��B�R�A�� PHP �t�@�C���� mysql_xxxx() �����ׂāA
nucleus_mysql_xxxx() �ɏ����������܂��B�g�p����ۂ́A
���̃t�@�C���� Nucleus �̃��[�g�f�B���N�g��(config.php
�̂���ꏊ)�Ɉړ����A�u���E�U�ŃA�N�Z�X���Ă��������B

�@�����āAinstall.php �y�сAconfig.php �ɁA
�winclude($DIR_NUCLEUS.'sqlite/sqlite.php');�x�������I��
����������܂��B

�@backup.php �́ASQLite �ł̃f�[�^���X�g�A���œK������
��悤�ɁA�ύX����܂��isqlite_restore_execute_queries()
���g�p�����悤�ɂȂ�܂��j�B

�@����ɁAinstall.php �ł̃C���X�g�[����ʂ�HTML���኱
�C������A�ꕔ��MySQL���ٓI�ȃI�v�V�����Ɂwdummy�x���w
�肳���悤�ɕύX����܂��B


�����������@�蓮�ŕύX���Ȃ��Ƃ����Ȃ������@����������

�@installsqlite.php �̎��s�ŕK�v�ȕύX�̂����d�v�ȕ���
���Ă͖w�Ǎs���܂����A�ꕔ�蓮�ŕύX���Ȃ���΂Ȃ�
�Ȃ�����������܂��B

�@SQLite �ł́A�N�G���[�ŁwSELECT i.itime FROM ....�x��
���Ă��Aitime �� sql_fetch_xxx �ŃL���b�`�ł��܂���B��
���ŁA�����̕\�����wSELECT i.itime as itime FROM ....�x
�̗l�ɕύX���܂��B�ǂ���ύX����Ηǂ����́Asqlite.php 
�Łw$SQLITECONF['DEBUGMODE']=true;�x�����
�w$SQLITECONF['DEBUGREPORT']=true;�x���w�肷��΁A�u���O
�̃\�[�X�R�[�h�Ɂw<!--sqlite_DebugMessage .... 
sqlite_DebugMessage-->�x�ŕ\�������R�����g�����Q�Ƃ���
���ƂŌ�������\��������܂��BNucleus3.22�ł́A��
���̕�������ɑ������܂��B

�EBLOG.php�̂S�T�U�s�ڕt�߂ƂT�O�S�s�ڕt�߂�"i.itime,"�B
�EITEM.php �S�O�s�ڕt�߂̂�"i.itime,"�B
�ECOMMENTS.php �V�V�s�ڕt�߂�"c.ctime,"�B

(�ǋL�Fver 0.65b ���A�w$SQLITECONF['DEBUGMODE']=true;�x
�ɂđΏ����Ă��܂��B)

�@����ɁAinstall.sql �� nucleus_plugin_option �e�[�u��
�쐬�����̃N�G���[�����ȉ��̂悤�ɕύX���܂��iauto_increment
���폜���܂��j�B

CREATE TABLE `nucleus_plugin_option` (
  `ovalue` text NOT NULL,
  `oid` int(11) NOT NULL,
  `ocontextid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`oid`,`ocontextid`)
) TYPE=MyISAM;

�@ver 0.70b �ȍ~�Anucleus/libs/backup.php �́A�����ŕύX
�����悤�ɂȂ�܂����B
