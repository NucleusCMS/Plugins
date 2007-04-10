<?php
    /***************************************
    * SQLite-MySQL wrapper for Nucleus     *
    *                           ver 0.8.5  *
    * Written by Katsumi                   *
    ***************************************/
//
//  The licence of this script is GPL
//
//  The features that are supported by this script but not
//  generally by SQLite are as follows:
//
//  CONCAT, IF, IFNULL, NULLIF, SUBSTRING, 
//  match() against(),
//  replace, UNIX_TIMESTAMP, REGEXP, DAYOFMONTH, MONTH, YEAR, 
//  ADDDATE, DATE_ADD, SUBDATE, DATE_SUB, FIND_IN_SET,
//  CURDATE, CURRENT_DATE, CURTIME, CURRENT_TIME, CURRENT_TIMESTAMP, 
//  LOCALTIME, LOCALTIMESTAMP, SYSDATE, DATE_FORMAT, TIME_FORMAT, 
//  DAYNAME, DAYOFWEEK, DAYOFYEAR, EXTRACT, FROM_DAYS, FROM_UNIXTIME,
//  HOUR, MINUTE, MONTH, MONTHNAME, PERIOD_ADD, PERIOD_DIFF, QUARTER,
//  SECOND, SEC_TO_TIME, SECOND, WEEK, WEEKDAY, YEAR, YEARWEEK,
//  FORMAT, INET_ATON, INET_NTOA, MD5,
//  ACOS, ASIN, ATAN, CEIL, CEILING, COS, COT, CRC32, DEGREES, 
//  EXP, FLOOR, GREATEST, MAX, LEAST, MIN, ln, log, log2, log10,
//  MOD, PI, POW, POWER, RADIANS, RAND, SIGN, SIN, SQRT, TAN,
//  ASCII, BIN, BIT_LENGTH, CHAR, CHAR_LENGTH, CONCAT_WS,
//  CONV, ELT, EXPORT_SET, FIELD, HEX, INSERT, LOCATE,
//  INSTR, LCASE, LOWER, LEFT, LENGTH, OCTET_LENGTH,
//  LOAD_FILE, LPAD, LTRIM, MAKE_SET, MID, SUBSTRING,
//  OCT, ORD, QUOTE, REPEAT, REVERSE, RIGHT, RPAD,
//  RTRIM, SOUNDEX, SPACE, SUBSTRING_INDEX, TRIM,
//  UCASE, UPPER,


// Register user-defined functions used in SQL query.
// The SQLite_QueryFunctions object is created to register SQLite queries.
$SQLITECONF['object']=new SQLite_Functions;
// After the registration, the object is not required any more.
unset($SQLITECONF['object']);


// The class for SQLite user functions
class SQLite_Functions {

// Constructor is used for the registration of user-defined functions of SQLite
function SQLite_Functions(){
	global $SQLITE_DBHANDLE;
	foreach($this as $key=>$value){
		$key=strtoupper($key);
		if (substr($key,0,7)!='DEFINE_') continue;
		$key=substr($key,7);
		if (substr($value,0,7)=='sqlite_') $value=array('SQLite_Functions',$value);
		@sqlite_create_function($SQLITE_DBHANDLE,$key,$value);
	}
}

var $define_ASCII='ord';

var $define_BIN='decbin';

var $define_BIT_LENGTH='sqlite_userfunc_BIT_LENGTH';
function sqlite_userfunc_BIT_LENGTH($p1){
	return strlen($p1)*8;
}

var $define_CHAR='sqlite_userfunc_CHAR';
function sqlite_userfunc_CHAR(){
	if (!($lastnum=func_num_args())) return null;
	$args=&func_get_args();
	$ret='';
	for ($i=0;$i<$lastnum;$i++) {
		if ($args[$i]!==null) $ret.=chr($args[$i]);
	}
	return $ret;
}

var $define_CHAR_LENGTH='mb_strlen';

var $define_CONCAT_WS='sqlite_userfunc_CONCAT_WS';
function sqlite_userfunc_CONCAT_WS(){
	if (($lastnum=func_num_args())<2) return null;
	$args=&func_get_args();
	if ($args[0]===null) return null;
	$ret='';
	for ($i=1;$i<$lastnum;$i++) {
		if ($args[$i]===null) continue;
		if ($ret) $ret.=$args[0];
		$ret.=(string)($args[$i]);
	}
	return $ret;
}

var $define_CONV='sqlite_userfunc_CONV';
function sqlite_userfunc_CONV($p1,$p2,$p3){
	$t36='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$p1=strtoupper(trim((string)$p1));
	if ($p3<0 && substr($p1,0,1)=='-') {
		$sign='-';
		$p1=substr($p1,1);
	} else $sign='';
	$p3=abs($p3);
	$v=0;
	for ($i=0;$i<strlen($p1);$i++) $v=$v*$p2+strpos($t36,$p1[$i]);
	if (!$v) return '0';
	$ret='';
	while ($v) {
		$i=$v % $p3;
		$ret=$t36[$i].$ret;
		$v=($v-$i)/$p3;
	}
	return $sign.$ret;
}

var $define_ELT='sqlite_userfunc_ELT';
function sqlite_userfunc_ELT(){
	if (($lastnum=func_num_args())<2) return null;
	$args=&func_get_args();
	if ($args[0]<1 || $lastnum<$args[0]) return null;
	return $args[$args[0]];
}

var $define_EXPORT_SET='sqlite_userfunc_EXPORT_SET';
function sqlite_userfunc_EXPORT_SET($p1,$p2,$p3,$p4=',',$p5=64){
	if ($p1<2147483648) $p1=decbin($p1);
	else $p1=decbin($p1/2147483648).decbin($p1 % 2147483648);
	$p1=substr(decbin($p1).str_repeat('0',$p5),0,$p5);
	$p1=str_replace(array('1','0'),array($p2.$p4,$p3.$p4),$p1);
	return substr($p1,0,strlen($p1)-strlen($p4));
}

var $define_FIELD='sqlite_userfunc_FIELD';
function sqlite_userfunc_FIELD(){
	if (($lastnum=func_num_args())<2) return null;
	$args=&func_get_args();
	for ($i=1;$i<$lastnum;$i++) if ($args[0]==$args[$i]) return $i;
	return 0;
}

var $define_HEX='sqlite_userfunc_HEX';
function sqlite_userfunc_HEX($p1){
	if (is_numeric($p1)) return dechex ($p1);
	$p1=(string)$p1;
	$ret='';
	for($i=0;$i<strlen($p1);$i++) $ret.=substr('0'.dechex($p1[$i]),-2);
	return $ret;
}

var $define_INSERT='sqlite_userfunc_INSERT';
function sqlite_userfunc_INSERT($p1,$p2,$p3,$p4){
	if (function_exists('mb_substr')) return mb_substr($p1,0,$p2-1).$p4.mb_substr($p1,$p2+$p3-1);
	return substr($p1,0,$p2-1).$p4.substr($p1,$p2+$p3-1);
}

var $define_LOCATE='sqlite_userfunc_LOCATE';
function sqlite_userfunc_LOCATE($p1,$p2,$p3=1){
	if (substr($p1,1)=='_') $p2='-'.$p2;
	else $p2='_'.$p2;
	if (function_exists('mb_strpos')) return (int)mb_strpos($p2,$p1,$p3);
	if (($p=strpos(substr($p2,$p3),$p1))===false) return 0;
	return $p+$p3;
}

var $define_INSTR='sqlite_userfunc_INSTR';
function sqlite_userfunc_INSTR($p1,$p2){
	return SQLite_Functions::sqlite_userfunc_LOCATE($p2,$p1);
}

var $define_LCASE='sqlite_userfunc_LOWER';
var $define_LOWER='sqlite_userfunc_LOWER';
function sqlite_userfunc_LOWER($p1){
	if (function_exists('mb_strtolower')) return mb_strtolower($p1);
	return strtolower($p1);
}

var $define_LEFT='sqlite_userfunc_LEFT';
function sqlite_userfunc_LEFT($p1,$p2){
	if (function_exists('mb_substr')) return mb_substr($p1,0,$p2);
	return substr($p1,0,$p2);
}

var $define_LENGTH='strlen';
var $define_OCTET_LENGTH='strlen';

var $define_LOAD_FILE='sqlite_userfunc_LOAD_FILE';
function sqlite_userfunc_LOAD_FILE($p1){
	if (!file_exists($p1)) return null;
	if (!is_array($a=@file($p1))) return null;
	$ret='';
	foreach($a as $value) $ret.=$value;
	return $ret;
}

var $define_LPAD='sqlite_userfunc_LPAD';
function sqlite_userfunc_LPAD($p1,$p2,$p3){
	return substr(str_repeat($p3,$p2/strlen($p3)).$p1,0-$p2);
}

var $define_LTRIM='ltrim';

var $define_MAKE_SET='sqlite_userfunc_MAKE_SET';
function sqlite_userfunc_MAKE_SET($p1,$p2,$p3){
	if (($lastnum=func_num_args())<2) return null;
	$args=&func_get_args();
	$ret='';
	if ($args[0]<2147483648) $bits=decbin($args[0]);
	else $bits=decbin($args[0]/2147483648).decbin($args[0] % 2147483648);
	for ($i=1;$i<$lastnum;$i++) {
		if ($bits[strlen($bits)-$i]=='1' && $args[$i]) {
			if ($ret) $ret.=',';
			$ret.=$args[$i];
		}
	}
	return 0;
}

var $define_MID='sqlite_userfunc_SUBSTRING';
var $define_SUBSTRING='sqlite_userfunc_SUBSTRING';
function sqlite_userfunc_SUBSTRING($p1,$p2,$p3=null){
	$p2--;
	if (function_exists('mb_substr')) {
		if ($p3) return mb_substr($p1,$p2,$p3);
		return mb_substr($p1,$p2);
	}
	if ($p3) return substr($p1,$p2,$p3);
	return substr($p1,$p2);
}

var $define_OCT='sqlite_userfunc_OCT';
function sqlite_userfunc_OCT($p1){
	if ($p1===null) return null;
	return SQLite_Functions::sqlite_userfunc_CONV($p1,10,8);
}

var $define_ORD='sqlite_userfunc_ORD';
function sqlite_userfunc_ORD($p1){
	if (function_exists('mb_substr')) $p1=mb_substr($p1,0,1);
	else $p1=substr($p1,0,1);
	$ret=0;
	for ($i=0;$i<strlen($p1);$i++) $ret=$ret*256+ord($p1[$i]);
	return $ret;
}

var $define_QUOTE='sqlite_userfunc_QUOTE';
function sqlite_userfunc_QUOTE($p1){
	if ($p1===null) return 'NULL';
	return str_replace(array("'","\\","\x1A"),array("\\'","\\\\","\\z"),$p1);
}

var $define_REPEAT='str_repeat';

var $define_REVERSE='sqlite_userfunc_REVERSE';
function sqlite_userfunc_REVERSE($p1){
	if (function_exists('mb_strlen')) {
		$ret='';
		for ($i=mb_strlen($p1)-1;0<=$i;$i++) $ret.=mb_substr($p1,$i,1);
		return $ret;
	}
	return strrev($p1);
}

var $define_RIGHT='sqlite_userfunc_RIGHT';
function sqlite_userfunc_RIGHT($p1){
	if (function_exists('mb_substr')) return mb_substr($p1,0-$p2);
	return substr($p1,0-$p2);
}

var $define_RPAD='sqlite_userfunc_RPAD';
function sqlite_userfunc_RPAD($p1,$p2,$p3){
	return substr($p1.str_repeat($p3,$p2/strlen($p3)),0,$p2);
}

var $define_RTRIM='rtrim';
var $define_SOUNDEX='soundex';

var $define_SPACE='sqlite_userfunc_SPACE';
function sqlite_userfunc_SPACE($p1){
	return str_repeat(' ',$p1);
}

var $define_SUBSTRING_INDEX='sqlite_userfunc_SUBSTRING_INDEX';
function sqlite_userfunc_SUBSTRING_INDEX($p1,$p2,$p3){
	if (!is_array($a=explode($p2,$p1))) return null;
	$ret='';
	if (0<$p3) {
		for ($i=0;$i<$p3;$i++) {
			if ($ret) $ret.=$p2;
			$ret.=$a[$i];
		}
	} else {
		for ($i=0;$i<0-$p3;$i++) {
			if ($ret) $ret.=$p2;
			$ret.=$a[count($a)-1-$i];
		}
	}
	return $ret;
}

var $define_TRIM='sqlite_userfunc_TRIM';
function sqlite_userfunc_TRIM($p1,$p2=null,$p3=null){
	if (!$p2 && !$p3) return trim($p1);
	if (!$p2) $p2=' ';
	switch(strtoupper($p1)){
		case 'BOTH':
			while (strpos($p3,$p2)===0) $p3=substr($p3,strlen($p2));
		case 'TRAILING':
			while (strrpos($p3,$p2)===strlen($p3)-strlen($p2)-1) $p3=substr($p3,0,strlen($p3)-strlen($p2));
			break;
		case 'LEADING':
			while (strpos($p3,$p2)===0) $p3=substr($p3,strlen($p2));
			break;
	}
	return $p2;
}

var $define_UCASE='sqlite_userfunc_UPPER';
var $define_UPPER='sqlite_userfunc_UPPER';
function sqlite_userfunc_UPPER($p1){
	if (function_exists('mb_strtoupper')) return mb_strtoupper($p1);
	return strtoupper($p1);
}

var $define_ACOS='acos';
var $define_ASIN='asin';

var $define_ATAN='sqlite_userfunc_ATAN';
function sqlite_userfunc_ATAN($p1,$p2=null){
	if (!$p2) return atan($p1);
	if ($p1>0 && $p2>0) return atan($p1/$p2);
	else if ($p1>0 && $p2<0) return pi-atan(-$p1/$p2);
	else if ($p1<0 && $p2<0) return pi+atan($p1/$p2);
	else if ($p1<0 && $p2>0) return 2*pi-atan(-$p1/$p2);
	else return 0;
}

var $define_CEIL='ceil';
var $define_CEILING='ceil';
var $define_COS='cos';

var $define_COT='sqlite_userfunc_COT';
function sqlite_userfunc_COT($p1){
	return 1/tan($p1);
}

var $define_CRC32='crc32';

var $define_DEGREES='sqlite_userfunc_DEGREES';
function sqlite_userfunc_DEGREES($p1){
	return ($p1/pi)*180;
}

var $define_EXP='exp';
var $define_FLOOR='floor';
var $define_GREATEST='max';
var $define_MAX='max';
var $define_LEAST='min';
var $define_MIN='min';
var $define_ln='log';

var $define_log='sqlite_userfunc_LOG';
function sqlite_userfunc_LOG($p1,$p2=null){
	if ($p2) return log($p1)/log($p2);
	return log($p1);
}

var $define_log2='sqlite_userfunc_LOG2';
function sqlite_userfunc_LOG2($p1){
	return log($p1)/log(2);
}

var $define_log10='log10';

var $define_MOD='sqlite_userfunc_MOD';
function sqlite_userfunc_MOD($p1,$p2){
	return $p1 % $p2;
}

var $define_PI='sqlite_userfunc_PI';
function sqlite_userfunc_PI(){
	return pi;
}

var $define_POW='sqlite_userfunc_POW';
var $define_POWER='sqlite_userfunc_POW';
function sqlite_userfunc_POW($p1,$p2){
	return pow($p1,$p2);
}

var $define_RADIANS='sqlite_userfunc_RADIANS';
function sqlite_userfunc_RADIANS($p1){
	return ($p1/180)*pi;
}

var $define_RAND='sqlite_userfunc_RAND';
function sqlite_userfunc_RAND($p1=null){
	if ($p1) srand($p1);
	return rand(0,1073741823)/1073741824;
}

var $define_SIGN='sqlite_userfunc_SIGN';
function sqlite_userfunc_SIGN($p1){
	if ($p1>0) return 1;
	else if ($p1<0) return -1;
	return 0;
}

var $define_SIN='sin';
var $define_SQRT='sqrt';
var $define_TAN='tan';

var $define_TRUNCATE='sqlite_userfunc_TRUNCATE';
function sqlite_userfunc_TRUNCATE($p1,$p2){
	$p2=pow(10,$p2);
	return ((int)($p1*$p2))/$p2;
}

var $define_FORMAT='sqlite_userfunc_FORMAT';
function sqlite_userfunc_FORMAT($p1,$p2){
	return number_format($p1, $p2, '.', ',');
}

var $define_INET_ATON='sqlite_userfunc_INET_ATON';
function sqlite_userfunc_INET_ATON($p1){
	$a=explode('.',$p1);
	return (($a[0]*256+$a[1])*256+$a[2])*256+$a[3];
}

var $define_INET_NTOA='sqlite_userfunc_INET_NTOA';
function sqlite_userfunc_INET_NTOA($p1){
	$a=array();
	for ($i=0;$i<4;$i++){
		$a[$i]=(string)($p1 % 256);
		$p1=(int)($p1/256);
	}
	return $a[3].'.'.$a[2].'.'.$a[1].'.'.$a[0];
}

var $define_MD5='md5';


var $define_CURDATE='sqlite_userfunc_CURDATE';
var $define_CURRENT_DATE='sqlite_userfunc_CURDATE';
function sqlite_userfunc_CURDATE(){
	return date('Y-m-d');
}

var $define_CURTIME='sqlite_userfunc_CURTIME';
var $define_CURRENT_TIME='sqlite_userfunc_CURTIME';
function sqlite_userfunc_CURTIME(){
	return date('H:i:s');
}

var $define_CURRENT_TIMESTAMP='sqlite_userfunc_NOW';
var $define_LOCALTIME='sqlite_userfunc_NOW';
var $define_LOCALTIMESTAMP='sqlite_userfunc_NOW';
var $define_SYSDATE='sqlite_userfunc_NOW';
function sqlite_userfunc_NOW(){
	return date('Y-m-d H:i:s');
}

var $define_DATE_FORMAT='sqlite_userfunc_DATE_FORMAT';
var $define_TIME_FORMAT='sqlite_userfunc_DATE_FORMAT';
function sqlite_userfunc_DATE_FORMAT($p1,$p2){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	$func='if ($matches=="%") return "%";';
	$func='return date($matches,'.$t.');';
	return preg_replace_callback ('/%([%a-zA-Z])/',create_function('$matches',$func), $p2);
}

var $define_DAYNAME='sqlite_userfunc_DAYNAME';
function sqlite_userfunc_DAYNAME($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('l',$t);
}

var $define_DAYOFWEEK='sqlite_userfunc_DAYOFWEEK';
function sqlite_userfunc_DAYOFWEEK($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('w',$t)+1;
}

var $define_DAYOFYEAR='sqlite_userfunc_DAYOFYEAR';
function sqlite_userfunc_DAYOFYEAR($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('z',$t);
}

var $define_EXTRACT='sqlite_userfunc_EXTRACT';
function sqlite_userfunc_EXTRACT($p1,$p2){
	$t=SQLite_Functions::sqlite_resolvedatetime($p2,$yr,$mt,$dy,$hr,$mn,$sc);
	switch(strtoupper($p1)) {
		case'SECOND': // SECONDS 
			return $sc;
		case'MINUTE': // MINUTES 
			return $m;
		case'HOUR': // HOURS 
			return $hr;
		case'MONTH': // MONTHS 
			return $mt;
		case'YEAR': // YEARS 
			return $y;
		case'MINUTE_SECOND': // 'MINUTES:SECONDS' 
			return date('is',$t);
		case'HOUR_MINUTE': // 'HOURS:MINUTES' 
			return date('Hi',$t);
		case'DAY_HOUR': // 'DAYS HOURS' 
			return date('dH',$t);
		case'YEAR_MONTH': // 'YEARS-MONTHS' 
			return date('Ym',$t);
		case'HOUR_SECOND': // 'HOURS:MINUTES:SECONDS' 
			return date('Hs',$t);
		case'DAY_MINUTE': // 'DAYS HOURS:MINUTES' 
			return date('di',$t);
		case'DAY_SECOND': // 'DAYS HOURS:MINUTES:SECONDS' 
			return date('ds',$t);
		case'DAY': // DAYS 
		default:
			return $dy;
	}
}

var $define_FROM_DAYS='sqlite_userfunc_FROM_DAYS';
function sqlite_userfunc_FROM_DAYS($p1){
	return date('Y-m-d',($p1-719528)*86400);
}

var $define_FROM_UNIXTIME='sqlite_userfunc_FROM_UNIXTIME';
function sqlite_userfunc_FROM_UNIXTIME($p1,$p2=null){
	if ($p2) return sqlite_userfunc_DATE_FORMAT($p1,$p2);
	return date('Y-m-d H:i:s',$p1);
}

var $define_HOUR='sqlite_userfunc_HOUR';
function sqlite_userfunc_HOUR($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return $hr;
}

var $define_MINUTE='sqlite_userfunc_MINUTE';
function sqlite_userfunc_MINUTE($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return $mn;
}

var $define_MONTHNAME='sqlite_userfunc_MONTHNAME';
function sqlite_userfunc_MONTHNAME($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('F',$t);
}


var $define_PERIOD_ADD='sqlite_userfunc_PERIOD_ADD';
function sqlite_userfunc_PERIOD_ADD($p1,$p2){
	$y=(int)($p1/100);
	$m=$p1-$y*100;
	$t=mktime(0,0,0,$m+$p2,1,$y, -1);
	return date('Ym',$t);
}

var $define_PERIOD_DIFF='sqlite_userfunc_PERIOD_DIFF';
function sqlite_userfunc_PERIOD_DIFF($p1,$p2){
	$y1=(int)($p1/100);
	$m1=$p1-$y1*100;
	$y2=(int)($p2/100);
	$m2=$p1-$y2*100;
	$t1=mktime(0,0,0,$m1,1,$y1, -1);
	$t2=mktime(0,0,0,$m2,1,$y2, -1);
	$y1=date('Y',$t1);
	$y2=date('Y',$t2);
	return (int)(mktime(0,0,0,$m1-$m2,1,1970+$y1-$y2, -1)/60/60/24/28);
}

var $define_QUARTER='sqlite_userfunc_QUARTER';
function sqlite_userfunc_QUARTER($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	switch($mt){
		case 1: case 2: case 3: return 1;
		case 4: case 5: case 6: return 2;
		case 7: case 8: case 9: return 3;
		default: return 4;
	}
}

var $define_SECOND='sqlite_userfunc_SECOND';
function sqlite_userfunc_SECOND($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return $sc;
}

var $define_SEC_TO_TIME='sqlite_userfunc_SEC_TO_TIME';
function sqlite_userfunc_SEC_TO_TIME($p1){
	return date('H:i:s',$p1);
}

var $define_WEEK='sqlite_userfunc_WEEK';
function sqlite_userfunc_WEEK($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('W',$t);
}

var $define_WEEKDAY='sqlite_userfunc_WEEKDAY';
function sqlite_userfunc_WEEKDAY($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	if (0<($w=date('w',$t))) return $w-1;
	return 6;
}

var $define_YEAR='sqlite_userfunc_YEAR';
function sqlite_userfunc_YEAR($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('Y',$t);
}

var $define_YEARWEEK='sqlite_userfunc_YEARWEEK';
function sqlite_userfunc_YEARWEEK($p1){
	$t=SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return date('YW',$t);
}


var $define_FIND_IN_SET='sqlite_userfunc_FIND_IN_SET';
function sqlite_userfunc_FIND_IN_SET($p1,$p2){
	if ($p1==null && $p2==null) return null;
	if (!$p2) return 0;
	foreach (explode(',',$p2) as $key=>$value) if ($value==$p1) return ($key+1);
	return 0;
}


var $define_ADDDATE='sqlite_userfunc_ADDDATE';
function sqlite_userfunc_ADDDATE($p1,$p2,$p3='DAY'){
	return date("Y-m-d",sqlite_ADDDATE($p1,$p2,$p3));
}

var $define_SUBDATE='sqlite_userfunc_SUBDATE';
function sqlite_userfunc_SUBDATE($p1,$p2,$p3='DAY'){
	return date("Y-m-d",sqlite_ADDDATE($p1,0-$p2,$p3));
}

var $define_CONCAT='sqlite_userfunc_CONCAT';
function sqlite_userfunc_CONCAT(){
	if (!($lastnum=func_num_args())) return null;
	$args=&func_get_args();
	$ret='';
	for ($i=0;$i<$lastnum;$i++) {
		if ($args[$i]===null) return null;
		$ret.=(string)($args[$i]);
	}
	return $ret;
}

var $define_IF='sqlite_userfunc_IF';
function sqlite_userfunc_IF($p1,$p2,$p3){
	if ((int)$p1) return $p2;
	return $p3;
}

var $define_IFNULL='sqlite_userfunc_IFNULL';
function sqlite_userfunc_IFNULL($p1,$p2){
	if ($p1!=null) return $p1;
	return $p2;
}

var $define_NULLIF='sqlite_userfunc_NULLIF';
function sqlite_userfunc_NULLIF($p1,$p2){
	if ($p1==$p2) return null;
	return $p1;
}


var $define_match_against='sqlite_userfunc_match_against';
function sqlite_userfunc_match_against(){
	if (!($lastnum=func_num_args())) return 0;
	if (!(--$lastnum)) return 0;
	$args=&func_get_args();
	if (!$args[$lastnum]) return 0;
	$pattern='/'.quotemeta($args[$lastnum]).'/i';
	$ret=0;
	for($i=0;$i<$lastnum;$i++) $ret=$ret+preg_match_all ($pattern,$args[$i],$matches);
	return $ret;
}

var $define_replace='sqlite_userfunc_replace';
function sqlite_userfunc_replace($p1,$p2,$p3){
	return str_replace($p3,$p1,$p2);
}

var $define_UNIX_TIMESTAMP='sqlite_userfunc_UNIX_TIMESTAMP';
function sqlite_userfunc_UNIX_TIMESTAMP($p1=null){
	if (!$p1) return time();
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	if ($yr) return mktime($hr,$mn,$sc,$mt,$dy,$yr, -1);
	return $p1;//TIMESTAMP
}

var $define_REGEXP='sqlite_userfunc_REGEXP';
function sqlite_userfunc_REGEXP($p1,$p2){
	return preg_match ("/$p2/",$p1);
}

var $define_DAYOFMONTH='sqlite_userfunc_DAYOFMONTH';
function sqlite_userfunc_DAYOFMONTH($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return $dy;
}

var $define_MONTH='sqlite_userfunc_MONTH';
function sqlite_userfunc_MONTH($p1){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	return $mt;
}


function sqlite_resolvedatetime($p1,&$yr,&$mt,&$dy,&$hr,&$mn,&$sc){
	$t=trim($p1);
	if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $t,$matches)) {
		//DATETIME
		$yr=(int)$matches[1];
		$mt=(int)$matches[2];
		$dy=(int)$matches[3];
		$hr=(int)$matches[4];
		$mn=(int)$matches[5];
		$sc=(int)$matches[6];
	} else if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $t,$matches)) {
		//DATE
		$yr=(int)$matches[1];
		$mt=(int)$matches[2];
		$dy=(int)$matches[3];
		$hr=$mn=$sc=0;
	} else if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $t,$matches)) {
		//YYYYMMDD
		$yr=(int)$matches[1];
		$mt=(int)$matches[2];
		$dy=(int)$matches[3];
		$hr=$mn=$sc=0;
	} else if (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})$/', $t,$matches)) {
		//YYMMDD
		$yr=(int)$matches[1];
		$mt=(int)$matches[2];
		$dy=(int)$matches[3];
		$hr=$mn=$sc=0;
		if ($yr<70) $yr=$yr+2000;
		else $yr=$yr+1900;
	}
	return mktime($hr,$mn,$sc,$mt,$dy,$yr, -1);
}

function sqlite_ADDDATE($p1,$p2,$p3){
	SQLite_Functions::sqlite_resolvedatetime($p1,$yr,$mt,$dy,$hr,$mn,$sc);
	$a=explode(' ',preg_replace('/[^0-9]/',' ',trim((string)$p2)));
	switch(strtoupper($p3)) {
		case'SECOND': // SECONDS 
			$sc += (int)$p2;
			break;
		case'MINUTE': // MINUTES 
			$mn += (int)$p2;
			break;
		case'HOUR': // HOURS 
			$hr += (int)$p2;
			break;
		case'MONTH': // MONTHS 
			$mt += (int)$p2;
			break;
		case'YEAR': // YEARS 
			$yr += (int)$p2;
			break;
		case'MINUTE_SECOND': // 'MINUTES:SECONDS' 
			$mn += (int)$a[0];
			$sc += (int)$a[1];
			break;
		case'HOUR_MINUTE': // 'HOURS:MINUTES' 
			$hr += (int)$a[0];
			$mn += (int)$a[1];
			break;
		case'DAY_HOUR': // 'DAYS HOURS' 
			$dy += (int)$a[0];
			$hr += (int)$a[1];
			break;
		case'YEAR_MONTH': // 'YEARS-MONTHS' 
			$yr += (int)$a[0];
			$mt += (int)$a[1];
			break;
		case'HOUR_SECOND': // 'HOURS:MINUTES:SECONDS' 
			$hr += (int)$a[0];
			$mn += (int)$a[1];
			$sc += (int)$a[2];
			break;
		case'DAY_MINUTE': // 'DAYS HOURS:MINUTES' 
			$dy += (int)$a[0];
			$hr += (int)$a[1];
			$mn += (int)$a[2];
			break;
		case'DAY_SECOND': // 'DAYS HOURS:MINUTES:SECONDS' 
			$dy += (int)$a[0];
			$hr += (int)$a[1];
			$mn += (int)$a[2];
			$sc += (int)$a[3];
			break;
		case'DAY': // DAYS 
		default:
			$dy += (int)$p2;
			break;
	}
	return mktime($hr,$mn,$sc,$mt,$dy,$yr, -1);
}

// For creating table structure (table and index/indeces)
var $define_sqlite_table_structure='sqlite_userfunc_sqlite_table_structure';
function sqlite_userfunc_sqlite_table_structure($p1){
	global $SQLITE_DBHANDLE;
	$ret='';
	if ($res=sqlite_query($SQLITE_DBHANDLE,"SELECT sql FROM sqlite_master WHERE tbl_name='$p1'")) {
		while ($array=sqlite_fetch_array($res,SQLITE_NUM)) $ret.=$array[0].";\n";
	}
	return $ret;
}


// Modification of query for some functions.
function sqlite_modifyQueryForUserFunc(&$query,$strpositions,$pattern=null,$replacement=null){
	// Write this part very carefully.  Otherwise, you may allow crackers to do SQL-injection.
	global $SQLITE_MQFUFCB_OK,$SQLITE_MQFUFCB_COUNT,$SQLITE_MQFUFCB_REPLACE;
	
	// Store the previous string
	$orgstrings=array();
	foreach ($strpositions as $start => $end) array_push($orgstrings, trim(substr($query,$start,$end-$start)));
	
	$lquery=strtolower($query);
	if (!$pattern) $pattern=array();
	if (!$replacement) $replacement=array();
	
	// match() against() support. Following way does NOT accept SQL-injection.  Note that the string is always quoted by "'".
	array_push($pattern,'/match \(([^\']*?)\) against \(/i');
	array_push($replacement,'match_against ($1,');	
	// REGEXP support
	if (strpos($lquery,'regexp')!==false) {
		array_push($pattern,'/([^a-z_\.])([a-z_\.]+)[\s]+REGEXP[\s]+\'([^\']*?)\'([^\']?)/i');
		array_push($replacement,'$1regexp($2,\'$3\')$4');	
	}
	// ADDDATE/SUBDATE support (INTERVAL support)
	array_push($pattern,'/([^a-zA-Z_])ADDDATE[\s]*?\(([^,]+),[\s]*?INTERVAL[\s]+([\S]+)[\s]+([^\)]+)\)/i');
	array_push($replacement,'$1adddate($2,$3,\'$4\')');
	array_push($pattern,'/([^a-zA-Z_])DATE_ADD[\s]*?\(([^,]+),[\s]*?INTERVAL[\s]+([\S]+)[\s]+([^\)]+)\)/i');
	array_push($replacement,'$1adddate($2,$3,\'$4\')');
	array_push($pattern,'/([^a-zA-Z_])SUBDATE[\s]*?\(([^,]+),[\s]*?INTERVAL[\s]+([\S]+)[\s]+([^\)]+)\)/i');
	array_push($replacement,'$1subdate($2,$3,\'$4\')');
	array_push($pattern,'/([^a-zA-Z_])DATE_SUB[\s]*?\(([^,]+),[\s]*?INTERVAL[\s]+([\S]+)[\s]+([^\)]+)\)/i');
	array_push($replacement,'$1subdate($2,$3,\'$4\')');
	
	// EXTRACT support
	array_push($pattern,'/([^a-zA-Z_])EXTRACT[\s]*?\(([a-zA-Z_]+)[\s]+FROM/i');
	array_push($replacement,'$1extract(\'$2\',');
	
	// TRIM support:
	array_push($pattern,'/([^a-zA-Z_])TRIM[\s]*?\((BOTH|LEADING|TRAILING)[\s]+FROM/i');
	array_push($replacement,'$1extract(\'$2\',\' \',');
	array_push($pattern,'/([^a-zA-Z_])TRIM[\s]*?\((BOTH|LEADING|TRAILING)[\s]+([\s\S]+)[\s]+FROM/i');
	array_push($replacement,'$1extract(\'$2\',$3,');
	
	// Change it.
	$temp=preg_replace ($pattern,$replacement,$query);
	
	// Comfirm if strings did not change.
	$ok=true;
	foreach ($orgstrings as $key=>$value) if ($value) {
		if (strpos($temp,$value)!==false) {
			// This string is OK, therefore will be ignored in the next "step by step" step.
			$orgstrings[$key]='';
			continue;
		}
		$ok=false;
	}
	if ($ok) { // return if everything is OK.
		$query=$temp;
		return;
	}
	
	// At least one of string changed. Need to do step by step.
	foreach ($pattern as $key=>$pat) {
		// Replace is done step by step for each RegExp replace statement.
		$SQLITE_MQFUFCB_REPLACE=$replace[$key];// Set the grobal var.
		$num=preg_match_all($pat,$query,$matches);
		// First, check if OK.
		$replaceOK=array();
		for ($i=1;$i<=$num;$i++) {
			$SQLITE_MQFUFCB_OK=array();
			$SQLITE_MQFUFCB_OK[$i]=true; // Set the grobal var.
			$SQLITE_MQFUFCB_COUNT=0; // Set the grobal var.
			// Only $i-st replacement will be done in the next line.
			$temp=preg_replace_callback($pat,array('SQLite_Functions','sqlite_modifyQueryForUserFuncCallBack'), $query, $i);
			$ok=true;
			foreach ($orgstrings as $value) if ($value) {
				if (strpos($temp,$value)!==false) continue;
				$ok=false;
				break;
			}
			if ($ok) $replaceOK[$i]=true;
		}
		// Replace
		$SQLITE_MQFUFCB_OK=$replaceOK;// Copy the OK array
		$SQLITE_MQFUFCB_COUNT=0;
		$query=preg_replace_callback($pat,array('SQLite_Functions','sqlite_modifyQueryForUserFuncCallBack'), $query);
	}
}

function sqlite_modifyQueryForUserFuncCallBack($mathces){
	global $SQLITE_MQFUFCB_OK,$SQLITE_MQFUFCB_COUNT,$SQLITE_MQFUFCB_REPLACE;
	if ($SQLITE_MQFUFCB_OK[++$SQLITE_MQFUFCB_COUNT]) return $SQLITE_MQFUFCB_REPLACE;
	else return $mathces[0];
}

}//class SQLite_QueryFunctions

?>