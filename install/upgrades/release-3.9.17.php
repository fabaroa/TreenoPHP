<?php
include_once '../../db/db_common.php';

$db_doc = getDbObject('docutron');
$query = 'ALTER TABLE odbc_connect ADD department varchar(255) NULL';
$db_doc->query($query);
?>
