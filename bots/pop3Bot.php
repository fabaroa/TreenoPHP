<?php
include_once '../lib/settings.php';
include_once '../lib/pop3Lib.php';

$ini = parse_ini_file($DEFS['POP3_INI'],true);
foreach($ini AS $mail_acc) {
	$pop3Obj = new pop3Email($mail_acc);
	$pop3Obj->processEmails();
}
?>
