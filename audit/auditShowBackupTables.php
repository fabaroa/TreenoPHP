<?php

#initialize connection to database and settings...
//include '../check_login.php';
//include ( '../classuser.inc');
include_once 'auditBackup.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {

	//variables that may be translated
	$docTitle = $trans['Backup Whole Table'];
	$tableTitle = $trans['List of Backup Files'];

	$dump_dir = "{$DEFS['DATA_DIR']}/";

	#-------- opening html tags -------------------------
	echo "<html>
	<head><link REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"../lib/style.css\"><title>$docTitle</title></head>
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

	echo "<p><div class=\"fontproperties\">";

	if (!(is_dir("$dump_dir"."$user->db_name"."/audit_backup_files/"))) {
		echo "<p>There are No Backup Files<br>";
		return 1;
	}
	$dh = opendir ($dump_dir . $user->db_name . '/audit_backup_files');
	$myEntry = readdir ($dh);
	while ($myEntry !== false) {
		if ($myEntry !== '.' and $myEntry !== '..') {
			$backupFiles[] = $myEntry;
		}
		$myEntry = readdir ($dh);
	}
	closedir ($dh);

	$output = '';
	foreach ($backupFiles as $tmp) {
		if (strpos ($tmp, ".zip") !== false) {
			$output .= $tmp . "\n";
		}
	}
	echo "<p><center>
	  <table class='settings' width='578'>
		<tr class='tableheads'>
			<td colspan='3' align='center'>$tableTitle</td>
		</tr>
		<tr>
	        <td><textarea name=\"files\" rows=\"10\" cols=\"60\" readonly>$output</textarea></td>
	 			</tr>
	  </table>
	  </center>
	
	   ";

	#--------- closing html tags

	setSessionUser($user);

	echo "</body>\n</html>";
} else {
	logUserOut();
}
?>
