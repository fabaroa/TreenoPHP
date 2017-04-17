<?php
chdir ('..');
require_once '../db/db_common.php';

$db_doc = getDbObject('docutron');

$query = 'ALTER TABLE ldap ADD suffix VARCHAR(255) NULL';
$res = $db_doc->query ($query);
dbErr($res);

?>
