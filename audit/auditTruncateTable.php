<?php
// $Id: auditTruncateTable.php 14281 2011-03-18 19:57:09Z acavedon $

#initialize connection to database and settings...
//include '../check_login.php';
//include ( '../classuser.inc');
include_once 'auditBackup.php';

if (($logged_in == 1 && strcmp($user->username, "") != 0) && $user->isAdmin()) {

	//variables that may need to be translated
	$docTitle = $trans['Backup Whole Table'];
	$tableTitle = $trans['Select Range'];
	$note = $trans['Note'];
	$lowIDNum = $trans['Low ID Number'];
	$lowestRecNum = $trans['Lowest Record Number'];
	$highIDNum = $trans['High ID Number'];
	$highestRecNum = $trans['Highest Record Number'];
	$truncate = "Archive";
	$reset = $trans['Reset'];
	$backupSuccess = $trans['Truncated Backup Success'];
	$errorinBackup = $trans['Error in Backup'];
	$errorinTruncate = $trans['Error in Truncate'];

	#----- opening html tags ------------
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

	echo "<p>
	<center>
	  <table class='settings' width='578'>
		<tr class='tableheads'>
			<td colspan='3' align='center'>$tableTitle</td>
		</tr>
		<tr>
	 		<td align='center' colspan='3'><div><i>$note</i></div>
			</td>
			
		</tr>
	  </table>
	  
	  <table class='settings' width='578'>
	  <form method=\"POST\" action=\"auditTruncateTable.php?low_rec=$low_rec&high_rec=$high_rec\">
		<tri>
			<td align='left'><div>$lowIDNum:</td>
			<td align='left'><div><INPUT type=\"text\" name=\"low_value\" size=\"20\"></td>
	  	</tr>
		<tr>
	  		<td align='left'><div>$lowestRecNum:</td>
			<td align='left'><div>$low_rec</td>
		</tr>
		<tr>
			<td align='left'><div>$highIDNum:</td>
			<td align='left'><div><INPUT type=\"text\" name=\"high_value\" size=\"20\"></td>
		</tr>
		<tr>
			<td align='left'><div>$highestRecNum:</td>
			<td align='left'><div>$high_rec</td>
		</tr>
		<tr>
			<td align='center'><div><input type=\"submit\" name=\"sub\" value=\"$truncate\"></div></td>
			<td align='center'><div><input type=\"reset\" name=\"res\" value=\"$reset\"></div></td>
		</tr>
	  </table>
		</center>
	   </form>
	  ";
	
	#if user posts a value, run the compress and truncate functions
	if (isset ($_POST['sub'])) {
		$low_val = 0;
		$high_val = 0;
		if (isset ($_POST['low_value'])) {
			$low_val = $_POST['low_value'];
		}
		if (isset ($_POST['high_value'])) {
			$high_val = $_POST['high_value'];
		}

		if (!$low_val or $low_val > $high_rec or $low_val < $low_rec) {
			$low_val = $low_rec;
		}

		if (!$high_val or $high_val > $high_rec or $high_val < $low_rec) {
			$high_val = $high_rec;
		}

		//account for possible user error in low to high fields
		if ($low_val > $high_val) {
			$tmp = $low_val;
			$low_val = $high_val;
			$high_val = $tmp;
		}

		if($low_val) {
			$compressed_filename = auditCreateDump($db_object, $user, $low_val, $high_val, $db_doc);
			echo "<p><div class=\"fontproperties\">";
			if ($compressed_filename == 1)
				echo "<br>if $errorinBackup<br>";
			else {
				//echo "$low_val to $high_val being truncated in table<br>";
				auditTableTruncate($db_object, $low_val, $high_val);
				echo "$compressed_filename $backupSuccess $low_val to $high_val";
			}
		} else {
			echo "<p><div class=\"fontproperties\">";
			echo "<br>Audit table is empty<br>";
			echo "</p></div>";
		}
	}

	# ------- closing html tags ------------

	setSessionUser($user);

	echo "</body>\n</html>";
} else {
	logUserOut();
}
?>
