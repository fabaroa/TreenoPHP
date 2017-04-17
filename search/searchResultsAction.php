<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once "../lib/settings.php";
include_once '../lib/audit.php';
include_once '../search/searchResultsFuncs.php';

if($logged_in and $user->username) {
	if(isset($_GET['cab'])) {
		$cab = $_GET['cab'];
	}
	if(isset($_GET['table'])) {
		$temp_table = $_GET['table'];
	}
	
	if( isSet( $_GET['export_csv'] ) )
		createCSV( $user, $cab, $temp_table, $db_object );
	elseif( isSet( $_GET['burn'] ) ) {
		$DepID = getTableInfo($db_object, 'departments', array('departmentid'),
			array('real_name' => $cab, 'deleted' => 0), 'queryOne');
		createISO( $user, $DepID, $temp_table, 0);
	} elseif( isSet( $_GET['bookmarkSearch'] ) ) {
		$bookmarkValue = $_GET['bookmarkValue'];
		createBookmark( $bookmarkValue, $user->username, $user->db_name, $cab );
	} elseif( isSet( $_GET['fileCount'] ) ) {
		getCountFilesInFolder( $db_object, $cab, $_GET['doc_id'] );	
	} elseif( isSet( $_GET['dataType'] ) ) {
		$DepID = getTableInfo($db_object, 'departments', array('departmentid'),
			array('real_name' => $cab, 'deleted' => 0), 'queryOne');
        getDataTypeDefs( $db_doc, $user->db_name, $DepID );
    } elseif( isSet( $_GET['checkFolder'] ) ) {
		checkIfFolderExists( $db_object, $user->db_name, $cab, $db_doc);
	} elseif( isSet( $_GET['integrityCheck'] ) ) {
		integrityCheck( $db_object, $cab, $_GET['doc_id'], $temp_table, $user, $db_doc );
	} elseif(isSet($_GET['autoComp'])) {
		searchAutoComp($db_object,$cab,$_GET['search'],$db_doc,$user);
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
