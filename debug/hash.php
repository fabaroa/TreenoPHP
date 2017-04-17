<?php

include_once '../lib/prehash.php';

$db_object = getDbObject( 'client_files2' );
$table = 'barcode_history';
$tableInfo = queryColumns( $db_object, $table, 1  );
$prehashTables = '';
$prehashLists = '';
getHashTables ( $db_object, $table, $tableInfo, $prehashTables, $prehashLists );
//makeHashTables( $db_object, $prehashTables, $prehashLists );
for( $i=10000; $i<1000001; $i++ )
{
echo $i."\n";
	rehash( $db_object, $table, $i, 'id' , $tableInfo, $prehashTables, $prehashLists );
}
//print_r($prehashTables);
//print_r($prehashLists);
//searchHashTopLevel($db_object,'snoop',$table,$prehashTables,$prehashLists);
//searchHashField()
$db_object->disconnect ();
echo "done\n";
?>
