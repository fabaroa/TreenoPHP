<?php
define("CRYPTKEY", "willbemis");

function weakEncrypt($string)
{
	$key = CRYPTKEY;
	$result = '';
	for($i = 0; $i < strlen($string); $i++) {
		$char = $string{$i};
		$keyChar = $key{($i % (strlen($key) - 1))};
		$char = chr(ord($char) + ord($keyChar));
		$result .= $char;
	}
	return base64_encode($result);
}

function weakDecrypt($string)
{
	$string = base64_decode($string);
	$key = CRYPTKEY;
	$result = '';
	for($i = 0; $i < strlen($string); $i++) {
		$char = $string{$i};
		$keyChar = $key{($i % (strlen($key) - 1))};
		$char = chr(ord($char) - ord($keyChar));
		$result .= $char;
	}
	return $result;
}

function tdEncrypt($string) {
	$key = CRYPTKEY;
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$encrypted_data = mcrypt_generic($td, $string);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return base64_encode ($encrypted_data);
}

function tdDecrypt($string) {
	$string = str_replace (' ', '+', $string);
	$string = base64_decode($string);
	$key = CRYPTKEY;
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$real_data = mdecrypt_generic($td, $string);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return $real_data;
}

function checkKey($key) {
	$key = str_replace (' ', '+', $key);
	$kInfo = explode(',', weakDecrypt($key));
	if (count($kInfo) == 2) {
		list($username, $myTime) = $kInfo;
		$currTime = time();
		if($myTime >= $currTime) {
			return array(true, $username);
		}
	}
	return array(false, '');
}


?>
