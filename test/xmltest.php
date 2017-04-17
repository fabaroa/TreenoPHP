<?php

class blah {
	var $db_name = 'client_files';
	function blah()
	{
	}
}

include '../lib/odbcFuncs.php'; 
$user = new blah();
$en = array (
	'cab_name' => 'accounting',
	'odbc_table' => 'karl',
	'odbc_field_count' => 2,
	'odbc_fieldname0' => 'id',
	'odbc_fieldname1' => 'name',
	'odbc_level'=> 1,
	'pk0' => 1,
	'pk1' => 0,
	'fk0' => 0,
	'fk1' => 1 );

echo xmlSetODBCMapping( $user, $en );
?>
