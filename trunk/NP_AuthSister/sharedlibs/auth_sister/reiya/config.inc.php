<?php

$auth_sister_image	= 'reiya.png';

//$auth_sister_fpath 	= './reiya';
//$auth_sister_font	= 'FONT_HERE';

$auth_sister_fsize	= 10;
$auth_sister_fx		= 100;
$auth_sister_fy		= 50;

//妹ヘッダ
$auth_sister_header	= '<link rel="stylesheet" type="text/css" href="[basedir]/reiya/style.css" />
<style type="text/css">
<!--
.reiya {
	background-image: url([imageurl]);
}	
-->
</style>';

//表示部分
$auth_sister_html	= '<div class="reiya">
  <div class="reiyareiya">
	<table border="0" cellspacing="0" cellpadding="0">
	  <tr>
		<td><input name="[authID]" type="text" class="reiya_input" id="[authID]" /></td>
		<!--td><input name="button" type="submit" class="reiya_submit" id="button" value="送信" /></td-->
	  </tr>
	</table>
  </div> 
</div>';
