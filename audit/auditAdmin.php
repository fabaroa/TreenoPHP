<?php
#initialize connection to database and settings...
//include '../check_login.php';
require_once '../classuser.inc';
//include ( '../classuser.inc');
include_once 'auditBackup.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {
	//variables that may need to  be translated
	$backupWholeTable = $trans['Backup Whole Table'];

	#----- opening html tags ------------
	echo "<html>
	<head><link REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"../lib/style.css\"><title>Backup Whole Table</title></head>
	<body>";

	#-------- get audit Admin table values --------------
	$max_table_size = getMaxTableSize();
	$size_of_table = getTableSize($db_object);
	$num_records = getNumRecords($db_object);
	$low_rec = $high_rec = $low_date = $high_date = '';
	getRecRange($db_object, $low_rec, $high_rec);
	getDateRange($db_object, $low_rec, $high_rec, $low_date, $high_date);

	#------- print the audit admin table on top ----------
	printAuditAdminTable($db_object, $max_table_size, $size_of_table, $num_records, $low_rec, $high_rec, $low_date, $high_date);

	#---- closing stuff

	setSessionUser($user);
	echo "</body></html>";
} else { //log the user off
	logUserOut();
}
?>
