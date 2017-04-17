<?php
#initialize connection to database and settings...
include_once 'auditBackup.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {

	//variables that may need to be translated
	$docTitle = $trans['Backup Whole Table'];
	$success = $trans['Success Message'];
	$error = $trans['Error Message'];

	//$low_rec=$_GET['low_rec'];
	//$high_rec=$_GET['high_rec'];

	#-------- opening html tags -------------------------
	echo "<html>
	<head><link REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"../lib/style.css\"><title>$docTitle</title></head>
	<body>";

	$user->audit("Backup Audit Table", "Attempting to backup the audit table");
	#-------- get audit Admin table values --------------
	$max_table_size = getMaxTableSize();
	$size_of_table = getTableSize($db_object);
	$num_records = getNumRecords($db_object);
	$low_rec = $high_rec = $low_date = $high_date = '';
	getRecRange($db_object, $low_rec, $high_rec);
	getDateRange($db_object, $low_rec, $high_rec, $low_date, $high_date);

	#------- print the audit admin table on top ----------
	printAuditAdminTable($db_object, $max_table_size, $size_of_table, $num_records, $low_rec, $high_rec, $low_date, $high_date);

	#actual file code
	echo "<p>";
	$filename = auditCreateDump($db_object, $user, $low_rec, $high_rec, $db_doc);
	echo "<center><div class=\"fontproperties\">";

	if ($filename == 1) {
		$user->audit("Backup Audit Table", "$error");
		echo "$error<br>";
	}
	elseif ($filename == 2) {
		$user->audit("Backup Audit Table", "Failed to backup the audit table: Quota Limit Exceeded");
		echo "$error<br>This Operation Will Exceed The Quota Limit";
	} else {
		$user->audit("Backup Audit Table", "$filename $success");
		echo "$filename $success<br>";
	}
	echo "</div></center>";
	echo "</body></html>";
	setSessionUser($user);
} else {
	logUserOut();
}
?>
