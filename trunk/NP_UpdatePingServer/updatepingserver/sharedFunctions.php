<?php

class sharedFunctions
{

	function quoteSmart($value)
	{
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		if (!is_numeric($value)) {
			if (version_compare(phpversion(), "4.3.0") == "-1") {
				$value = "'" . mysql_escape_string($value) . "'";
			} else {
				$value = "'" . mysql_real_escape_string($value) . "'";
			}
		} else {
			$value     = intval($value);
		}
		return $value;
	}

	function _hsc($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, _CHARSET);
	}

}
