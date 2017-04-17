<?php

/*
 * filter out characters
 */
function f($str){
	$filterStr="'".'<>?\/";:]}[{\|)(*&^%$#@!~`+=';
	for($i=0;$i<strlen($filterStr);$i++){
		$str = str_replace( $filterStr{$i},'',$str);
	}
	return $str;
}
/* 
 * html encode html entities
 */
function h($str) {
	global $DEFS;
	if (!empty($DEFS['UTF8']) && $DEFS['UTF8'] == '1') {
		return htmlentities($str, ENT_QUOTES, 'UTF-8');
	} else {
		return htmlentities($str);
	}
}
function returnKeyboardCharsOnly( $str ){
		$characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$characters .= "abcdefghijklmnopqrstuvwxyz";
		$numbers = "1234567890";
		$special = '`~!@#$%^&*()_+=-\][|}{";:/?.>,< '."'";
		$keyboardArr = str_split( $characters.$numbers.$special );
		$strArr = str_split( $str );
		$str_size = sizeof( $strArr );
		$tmpStr = '';
			foreach( $strArr as $chr ){
				if( in_array( $chr, $keyboardArr ) ){
					$tmpStr .= $chr;
				}
			}
		return $tmpStr;
}

?>
