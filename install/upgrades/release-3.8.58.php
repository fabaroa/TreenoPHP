<?php
chdir ('..');
require_once '../db/db_common.php';

$db_doc = getDbObject('docutron');

$query = 'ALTER TABLE ldap ADD department VARCHAR(255) NOT NULL';
$res = $db_doc->query ($query);

$uArr = array('department' => 'client_files');
updateTableInfo($db_doc,'ldap',$uArr,array());
?>
