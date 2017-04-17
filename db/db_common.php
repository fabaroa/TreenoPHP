<?php
/* vim: set tabstop=4 shiftwidth=4 softtabstop=4: */
if(file_exists('../lib/utility.php')) {
	include_once '../lib/utility.php';
	include_once '../lib/settings.php';
} elseif (file_exists ('lib/utility.php')) {
	include_once 'lib/utility.php';
	include_once 'lib/settings.php';
} else {
	include_once '../../lib/utility.php';
	include_once '../../lib/settings.php';
}

function getdbType() {
	global $DEFS;
	return $DEFS['DB_TYPE'];
}

function getdbHost() {
	global $DEFS;
	return $DEFS['DB_HOST'];
}

/**
 * This function gives the database object connecting to 
 * the given database name. This supports both mysql and db2
 * databases but this needs to be predefined.
 * you can use it this way
 * $dbt = getDbObject('docutron');
 * where 'docutron' is a database name (application specific).
 * but in DB2 its defined as 'dtron' due to 8 char constraint.
 * this validaton can take care here itself. 
 *
 * @package  db
 * @author   kishore varma <rkishore@thebostongroup.com>
 * @access   public
 * @see      installDB2.php
 * @return	MDB2_Driver_Common
 */

function &getDbObject($database) {
	global $DEFS;
	if( isset( $DEFS['DB_DEBUG'] ) and $DEFS['DB_DEBUG'] == 1 or( isset( $DEFS['DB_DEBUG_CONNECT'] ) and $DEFS['DB_DEBUG_CONNECT']==1 )){
		$pid = getmypid();
		$donotlog=false;
		$dateStr = '';
		$dateStr = date ('Y-m-d H:i:s');
		//do not log connects from the web if DB_DEBUG_BOTS is turned on
		if( isset( $_SERVER['REMOTE_ADDR']) and isset( $DEFS['DB_DEBUG_BOTS'] ))
			$donotlog=true;
		if (isset ($_SERVER) and isset ($_SERVER['REMOTE_ADDR']) and !isset($DEFS['DB_DEBUG_BOTS']) and $DEFS['DB_DEBUG_BOTS']!=1 ) {
			$backtrace = debug_backtrace();
			$errStr = $dateStr . ' -- ' . $_SERVER['REMOTE_ADDR'] . ' -- ' .
				"$database connect: file-{$backtrace[0]['file']} line-{$backtrace[0]['line']}: function={$backtrace[0]['function']} \n";
		} else {
			$backtrace = debug_backtrace();
			$errStr = $dateStr . ' [pid='.$pid . '] -- ' . "$database connect: file-{$backtrace[0]['file']} line-{$backtrace[0]['line']}: function={$backtrace[0]['function']} \n";
		}
		if( !$donotlog ){
			error_log ($errStr, 3, $DEFS['DB_DEBUG_FILE']);
		}
	}
	$db_engine = $db_username = $db_password = $db_host = '';
	if(file_exists('../db/db_engine.php')) {
		include '../db/db_engine.php';
	} elseif(file_exists('db/db_engine.php')) {
		include 'db/db_engine.php';
	} else {
		include 'db_engine.php';
	}
	$db_dsn = array (
		'phptype'	=> $db_engine,
		'username'	=> $db_username,
		'password'	=> $db_password,
		'hostspec'	=> $db_host,
		'database'	=> getDatabase($database),
		'new_link'	=> true,
	);
	if(isset($DEFS['DB_PORT'])) {
		$db_dsn['port'] = $DEFS['DB_PORT'];
	}
	$db =& MDB2::factory($db_dsn, array('portability' => MDB2_PORTABILITY_ALL));
	dbErr($db);
	if (isset ($DEFS['DB_DEBUG']) and $DEFS['DB_DEBUG'] == 1) {
		$db->setOption ('debug', 1);
		$db->setOption ('debug_handler', 'MDB2Debug');
	}
	$db->loadModule ('Extended');
	$db->loadModule ('Reverse');
	$db->loadModule ('Manager');
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db;
}

function MDB2Debug(&$db, $scope, $message, $is_manip = null) {
	global $DEFS;
	if ($scope != 'prepare') {
		$dateStr = '';
		$dateStr = date ('Y-m-d H:i:s');
		if (isset ($_SERVER) and isset ($_SERVER['REMOTE_ADDR'])) {
			$bt = debug_backtrace();
			$errStr = $dateStr . ' -- ' . $_SERVER['REMOTE_ADDR'] . ' -- ';
			if(isSet($bt[0]['file'])) {
				$errStr .= $bt[0]['file'] . ' -- ';
			}
			$errStr .= $db->database_name . ' -- ' . $message."\n";
		} else {
			$errStr = $dateStr . ' -- ' . $db->database_name . ' -- ' .  $bt[0]['file'] . ' -- ' .
				$message."\n";
		}
		error_log ($errStr, 3, $DEFS['DB_DEBUG_FILE']);
	}
}


/**
 * This function gives the database object connecting to
 * the given database name and the requited parameters
 * you can use it this way
 * $dbt = getDatabaseConn('odbc(db2)','db2inst1','ibm','','docutron');
 * where 'docutron' is a database name.
 * here  validation is if any parameter is incorrect it fails to connect.
 *
 * @package  db
 * @author   kishore varma <rkishore@thebostongroup.com>
 * @access   public
 * @param    engine,username,password,localhost and database name.
 * @returns  DB object.
 * @see      dblookup.php
 */
function getDatabaseConn($db_engine, $db_username, $db_password, $db_host, $database) {
	$db_dsn = array (
		'phptype'	=> $db_engine,
		'username'	=> $db_username,
		'password'	=> $db_password,
		'hostspec'	=> $db_host,
		'database'	=> $database
	);
	$db =& MDB2 :: factory($db_dsn);
	dbErr($db);
	$db->loadModule ('Extended');
	$db->loadModule ('Reverse');
	$db->loadModule ('Manager');
	return $db;
}

/**
 * This function will return the database name dynamically for DB2.
 * @param   String	 $database
 * @see
 */
function getDatabase($database) {
	if (getdbType() == 'db2') {
		if (substr($database, 0, 12) == 'client_files') {
			$strlen = substr($database, 12, strlen($database));
			$database = "clf".$strlen;
		} elseif (substr($database, 0, 8) == 'docutron') {
			$strlen = substr($database, 8, strlen($database));
			$database = "dtron".$strlen;
		}
	}
	return trim($database);
}
?>
