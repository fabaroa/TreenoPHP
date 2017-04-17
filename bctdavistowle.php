<?php
chdir ('C:/Treeno/treeno/energie');
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../lib/odbc.php';

$cabinetArr = array(
	'clientid'		=> 'Clients',
	'policyid'		=> 'Policies',
	'lossid'		=> 'Claims',
	'memoid'		=> 'Activity_Log',
	'marketingid'	=> 'Marketing'
);

$cabinet = $cabinetArr[key($_GET)];

if(!$cabinet) die("No matching cabinet found!\n");

$searchStr = current($_GET);
if( $cabinet =='Clients' ) {
	$db_raw = getDbObject( 'docutron' );
	if( PEAR::isError( $db_raw ) ) {
		print_r($db_raw );
		die();
	}
	$db_object = getODBCDbObject( 1, $db_raw );
	if( PEAR::isError( $db_object ) ) {
		print_r( $db_object );
		die();
	}
	$gblStt = new GblStt ('client_files', $db_raw);
	$db_dept = getDbObject( 'client_files' );
	$row =   getODBCRow( $db_object,$searchStr, 'Client', $db_dept,'Client_trans', 'client_files', $gblStt );
	$db_raw->disconnect ();
	$db_object->disconnect ();
	$db_dept->disconnect ();
	$searchStr = $row['client_code'];
	$cabinet = 'Client';
}
else if( $cabinet == 'Policies' )
	$cabinet = 'Policy';

$newURL = "login.php?autosearch=$searchStr&cabinet=$cabinet";
header("Location: $newURL");

?>
