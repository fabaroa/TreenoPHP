<?php

include_once '../lib/prehash.php';

$db_object = getDbObject( 'client_files' );
$cab = 'Benchmark';
dropHashTables( $db_object, $cab );
$db_object->disconnect ();


?>
