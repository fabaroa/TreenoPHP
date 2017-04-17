<?php
// $Id: auditBackup.php 14281 2011-03-18 19:57:09Z acavedon $

include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ('../classuser.inc');

include_once '../lib/quota.php';

#Functions for Backup related administration of admin

//function to fix a filesize in bytes to KB, MB, GB

function fixFileSize($size) {

	$i = 0;
	//fix significance
	while ($size >= 1024) {
		$size = $size / 1024;
		$i ++;
	}

	//set label after number
	if ($i == 0)
		$label = "bytes";
	elseif ($i == 1) $label = "KB";
	elseif ($i == 2) $label = "MB";
	elseif ($i == 3) $label = "GB";
	else
		$label = "TB";

	printf("%.3f", $size);
	echo " $label";
}

#function to print all records
function print_rec_range($rec_low, $rec_high) {
	echo "<p>$rec_low is the lowest record in the database\n";
	echo "$rec_high is the highest record in the database\n";
}

#function to create a backup name using the database name, date, and time
function auditCreateFileName($user) {
	#ex: dbname_audit_2003-05-08_23-15-06_bak.csv
	$name = $user->db_name."_audit_".date("Y-m-d_G-i-s")."_bak.csv";
	return $name;
}

#function to create a dump file within a specified record range, return 1 if already exists
function auditCreateDump($db_object, $user, $low, $high, $db_doc) {
	global $trans;
	global $DEFS;
	$exists = $trans['File Already Exists'];
	$compressError = $trans['Compressing Error'];
	$aborting = $trans['Aborting'];

	$dump_dir = "{$DEFS['DATA_DIR']}/";
	$dump_filename = auditCreateFileName($user);

	//don't create file if it already exists
	if (file_exists($dump_filename)) {
		echo "<p>$dump_filename $exists";
		return (1);
	}
	$old_mask = umask(0);

	$full_dump_dir = "$dump_dir$user->db_name"."/audit_backup_files/";
	$full_dump_path = $full_dump_dir.$dump_filename;
	if (!file_exists ($full_dump_dir)) {
		mkdir ($full_dump_dir);
	}
	getOutfile($db_object, $full_dump_path, $low, $high);
	lockTables($db_doc, array('licenses'));
	//create directory if it does not exist
	if (!(is_dir("$dump_dir"."$user->db_name"))) {
		mkdir("$dump_dir"."$user->db_name"."/", 0777);
	}
	if (!(is_dir("$dump_dir"."$user->db_name"."/audit_backup_files/"))) {
		if (checkQuota($db_doc, 4096, $user->db_name)) {
			mkdir("$dump_dir"."$user->db_name"."/audit_backup_files/", 0777);
			$updateArr = array('quota_used'=>'quota_used+4096');
			$whereArr = array('real_department'=> $user->db_name);
			updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
		} else {
			unlockTables($db_doc);
			return 1;
		}
	}
	unlockTables ($db_doc);
	umask($old_mask);

	$compressed_filename = auditFileCompress("$dump_dir$user->db_name"."/audit_backup_files/", $dump_filename, $DEFS);
	lockTables($db_doc, array ('licenses'));
	if($compressed_filename == -1) {
		echo "<span class='error'>$compressError $full_dump_path $aborting</span>";
		unlockTables($db_doc);
		return 1;
	}
	$finfo = stat($compressed_filename);
	if(checkQuota($db_doc, $finfo[7], $user->db_name)) {
		$updateArr = array('quota_used'=>'quota_used+'.$finfo[7]);
		$whereArr = array('real_department'=> $user->db_name);
		updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
		unlink($full_dump_path);
	} else {
		unlockTables($db_doc);
		unlink($full_dump_path);
		unlink($compressed_filename);
		return 1;
	}
	unlockTables($db_doc);
	$db_doc->disconnect();
	return $dump_filename.".zip";
}

#function to compress a file given the filename input (returns name of the zipped with the path if successful
function auditFileCompress($path, $filename, $DEFS) {
	global $trans;
//	$compressError = $trans['Compressing Error'];
	$cmd = $DEFS['ZIP_EXE'] . " -j " . escapeshellarg("$path$filename.zip") . ' ' .
		escapeshellarg("$path$filename");
	if (!shell_exec($cmd)) {
		return -1;
	}

	return $path.$filename.".zip";
}

//function to remove a specified range of rows from the audit table
function auditTableTruncate($db_object, $low, $high) {
	$whereArr = array("id >= ".(int)$low." AND id <= ".(int)$high);
	deleteTableInfo($db_object,'audit',$whereArr);
}

#function to return maximum table size
function getMaxTableSize() {

	return 2 * (1024 * 1024 * 1024); //max table size is set to 2 GB
}

#html file headers
//echo"<html>\n";
//echo"<head>	<link REL='stylesheet' TYPE='text/css' HREF='../lib/style.css'>\n";
//echo"<title>Audit Table Administration</title>";
//echo"</head>\n";
//echo"<body>\n";

#------------------------------------------------------------

#Gets number of records in a table and prints it
function getNumRecords($db_object) {
	return  getTableInfo($db_object,'audit',array('COUNT(id)'),array(),'queryOne');
}

#-----------------------------------------------------------

#Gets the lowest and highest record ID numbers and prints the two numbers
function getRecRange($db_object, & $low_rec, & $high_rec) {
	$row = getTableInfo($db_object,'audit',array('MIN(id) AS min','MAX(id) AS max'),array(),'queryRow');
	if(!$row) {//if there is no entry, set them to null
		$low_rec = "";
		$high_rec = "";
	} else {
		$low_rec = $row['min'];
		$high_rec = $row['max'];
	}
}

#-------------------------------------------------------------

#Gets the dates for the first and last record and prints the dates
function getDateRange($db_object, $low_rec, $high_rec, & $low_date, & $high_date) {
	if ($low_rec == "" || $high_rec == "") { //no entries
		$low_date = "";
		$high_date = "";
	} else {
		$res = getTableInfo($db_object,'audit',array('datetime'),array("id=".(int)$low_rec." OR id=".(int)$high_rec),'queryCol');
		$low_date = $res[0];
		$high_date = $res[1];
	}
}

#------------------------------------------------------------------

#Gets the size of the table in bytes and prints to the screen
function getTableSize($db_object) {
	$count = getTableInfo($db_object, 'audit', array('COUNT(*)'), array(), 'queryOne');
	return $count;
}

#------------------- table output ---------------------------------
function printAuditAdminTable($db_object, $max_table_size, $size_of_table, $num_records, $low_rec, $high_rec, $low_date, $high_date) {

	global $trans;

	//$tableTitle = $trans['Audit Table Administration'];
	$tableTitle = "Audit Table Archive Data";
	$spaceAllowed = $trans['Space Allowed'];
	$spaceUsed = $trans['Space Used'];
	$percentUsed = $trans['Percent Used'];
	$numRecords = $trans['Number of Records'];
	$firstRecID = $trans['First Record ID'];
	$earliestDate = $trans['Earliest Date'];
	$lastRecID = $trans['Last Record ID'];
	$latestDate = $trans['Latest Date'];

	echo "
		  <center>
		  <table class='settings' width='578'>
			<tr class='tableheads'>
				<td colspan='3' align='center'>$tableTitle</td>
			</tr>
			<tr>
		 		<td align='center'><div>$spaceAllowed: ";
	fixFileSize($max_table_size);
	echo "         </div>
				</td>
				<td align='center'><div>$spaceUsed: ";
	printf(getTableSize($db_object));
	//         fixFileSize($size_of_table);
	echo "
				</td>
				<td align='center'><div>$percentUsed: ";
	printf("%.3f", ($size_of_table / $max_table_size) * 100);
	echo "%
		  </div>
				</td>
			</tr>
		  </table>
		  <table class='settings' width='578'>
			<tr>
				<td align='left'><div>$numRecords</td>
				<td align='left'><div>$num_records</td>
		  	</tr>
			<tr>
		  		<td align='left'><div>$firstRecID</td>
				<td align='left'><div>$low_rec</td>
			</tr>
			<tr>
		  		<td align='left'><div>$earliestDate</td>
				<td align='left'><div>$low_date</td>
			</tr>
			<tr>
				<td align='left'><div>$lastRecID</td>
				<td align='left'><div>$high_rec</td>
			</tr>
			<tr>
				<td align='left'><div>$latestDate</td>
				<td align='left'><div>$high_date</td>
			</tr>
		  </table>
		  </center>
		
		   ";
}

?>
