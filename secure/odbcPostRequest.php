<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/odbcFuncs.php';
include_once '../lib/xmlParser.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ) {
	$func = '';
	$xmlStr = file_get_contents('php://input');
	if ($xmlStr) {
		$entriesArr = array ();
		xmlGetFuncArgs ($xmlStr, $entriesArr, $func);
	}

	if( $func != "" ) {
		$func( $user, $entriesArr, $db_doc, $db_object );
	}
}

function getOdbcTables( $user, $entriesArr, $db_doc, $db_dept ) {
	$doc = $root = '';
	return xmlGetODBCCabinetList($user,$entriesArr,$doc,$root, $db_doc, $db_dept);
}

function getOdbcTableColumns( $user, $en, $db_doc, $db_dept ) {
	$doc = $root = '';
	header ('Content-type: text/xml');
	xmlGetODBCCabinetColumnList($user, $en,$doc,$root, $db_doc, $db_dept);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		echo $doc->dump_mem(false);
	} else {
		echo $doc->saveXML ();
	}
}

//sets the mappings based on odbc_table.field_name as place holders.
function setODBCMapping( $user, $en, $db_doc, $db_dept ) {
	return xmlSetODBCMapping( $user, $en, $db_doc, $db_dept );
}

//count the levels for a given mapping
//this is based on cabinet name
function getODBCLevels( $user, $entries, $db_doc, $db_dept ) {
	
		
}
//sends back xml
//determines which values are foreign keys
function getODBCLevel( $user, $entries, $db_doc, $db_dept ) {
	//determine which level is requested
	//determine which values are foreign keys
	//return xml that shows what they are
	/*
	 *<root>
	 * <row>
	 *  <key></key>
	 *  <value></value>
	 * </row>
	 *</root>
	 */
}

//adds the docutron names to the odbc_table.field_name
//
function updteODBCMapping( $user, $entries, $db_doc, $db_dept ) {
}
?>
