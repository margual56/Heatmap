<?php

/* getRemoteIPAddress **********************************************************
*******************************************************************************/
function getRemoteIPAddress() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	return $_SERVER['REMOTE_ADDR'];
}

/* r_var_dump ******************************************************************
*******************************************************************************/
function ob_var_dump() {
	return call_user_func_array("r_var_dump", func_get_args());
}
function r_var_dump() {
	$argc = func_num_args();
	$argv = func_get_args();

	$result='';
	if ($argc > 0) {
		ob_start();
		call_user_func_array('var_dump', $argv);
		$result = ob_get_contents();
		ob_end_clean();
	}
	return $result;
}
