<?php
// $Id: auditRestoreBackupTable.php 14281 2011-03-18 19:57:09Z acavedon $

#initialize connection to database and settings...
include_once '../check_login.php';
include_once 'auditBackup.php';
//include ( '../classuser.inc');

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {

	//variables that  may have to be translated
	$docTitle = $trans['Backup Whole Table'];
	$selectBackupFile = $trans['Select Backup File'];
	$selectFile = $trans['Select File'];
	$download = $trans['Download'];

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
	$backupFiles = array ();
	$dh = opendir ($dump_dir . $user->db_name . '/audit_backup_files');
	$myEntry = readdir ($dh);
	while ($myEntry !== false) {
		if ($myEntry !== '.' and $myEntry !== '..') {
			$backupFiles[] = $myEntry;
		}
		$myEntry = readdir ($dh);
	}
	closedir ($dh);

	$myDumpDir = $dump_dir.$user->db_name.'/audit_backup_files/';

	echo "<p><center>
	  <table class='settings' width='578'>
	  <form name=\"restoreAudit\" method=\"POST\" target=\"leftFrame1\" action=\"auditDisplay.php?dir=$myDumpDir\">
		<tr class='tableheads'>
			<td colspan='2' align='center'>Download Audit Table Archive</td>
		</tr>
		<tr>
		<td align=\"left\">$selectBackupFile</td>
	        <td><select name=\"file\">
		<option selected value=\"default\">$selectFile</option>";

	foreach ($backupFiles as $tmp) {
		if (strpos($tmp, ".zip") !== false)
			echo "<option name=\"$tmp\">$tmp</option>";
	}
	echo "	</td></tr>
	  	<tr>
		  <td colspan=\"2\" align=\"right\"><input type=\"submit\" name=\"Download\" value=\"$download\"></td>
		</tr>
	  </form>
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
