<?php
require_once '../lib/install.php';
require_once '../db/db_common.php';
require_once '../lib/utility.php';

shell_exec('pear install -sa MDB2');
writeToDMSDefs('WWW_USER', 'apache');
writeToDMSDefs('WWW_GROUP', 'apache');
writeToDMSDefs('PHP_EXE', 'php');
writeToDMSDefs('TO_PDF', '0');
writeToDMSDefs('FIRSTFILE', '0');
writeToDMSDefs('ZIP_EXE', 'zip');
writeToDMSDefs('UNZIP_EXE', 'unzip');
writeToDMSDefs('MYDUMP_EXE', 'mysqldump');
writeToDMSDefs('TAR_EXE', 'tar');
writeToDMSDefs('MKISO_EXE', 'mkisofs');
writeToDMSDefs('GOCRFULL_EXE', 'gocr');
writeToDMSDefs('GOCR_EXE', 'gocr');
writeToDMSDefs('ANTIWORD_EXE', 'antiword');
writeToDMSDefs('TIFFTOPNM_EXE', 'tifftopnm');
writeToDMSDefs('PNMTOTIFF_EXE', 'pnmtotiff');
writeToDMSDefs('PNMSCALE_EXE', 'pnmscale');
writeToDMSDefs('PNMDEPTH_EXE', 'pnmdepth');
writeToDMSDefs('PAMFILE_EXE', 'pamfile');
writeToDMSDefs('PAMCOMP_EXE', 'pamcomp');
writeToDMSDefs('PAMFLIP_EXE', 'pamflip');
writeToDMSDefs('PNMTOJPEG_EXE', 'pnmtojpeg');
writeToDMSDefs('PNMTOPS_EXE', 'pnmtops');
writeToDMSDefs('PNMTOPNG_EXE', 'pnmtopng');
writeToDMSDefs('PNGTOPNM_EXE', 'pngtopnm');
writeToDMSDefs('PSTOPNM_EXE', 'pstopnm');
writeToDMSDefs('PSSELECT_EXE', 'psselect');
writeToDMSDefs('PS2PDF_EXE', 'ps2pdf');
writeToDMSDefs('PDF2PS_EXE', 'pdf2ps');
writeToDMSDefs('WGET_EXE', 'wget');
writeToDMSDefs('TIFFCP_EXE', 'tiffcp');
writeToDMSDefs('GUNZIP_EXE', 'gunzip');
writeToDMSDefs('CONVERT_EXE', 'convert');
writeToDMSDefs('PEAR_EXE', 'pear');
writeToDMSDefs('MYSQLADMIN_EXE', 'mysqladmin');
writeToDMSDefs('XCACLS_EXE', 'xcacls');
writeToDMSDefs('FILE_EXE', 'file');
writeToDMSDefs('TIFFSPLIT_EXE', 'tiffsplit');
writeToDMSDefs('MD5SUM_EXE', 'md5sum');
writeToDMSDefs('UPLOAD_TMP', '/tmp', false);

if (getDbType () == 'mysql') {
	print_r(get_loaded_extensions());
	if (!extension_loaded ('mysql')) {
		die ("Please install php-mysql RPM\n");
	}
	shell_exec('pear install -sa MDB2_Driver_mysql');
} elseif (getDbType () == 'mysqli') {
	if (!extension_loaded ('mysqli')) {
		die ("Please install php-mysqli RPM\n");
	}
	shell_exec('pear install -sa MDB2_Driver_mysqli');
} elseif (getDbType () == 'mssql') {
	if (!extension_loaded ('mssql')) {
		die ("Please install php-mssql RPM, or compile the extension\n");
	}
	shell_exec('pear install -sa MDB2_Driver_mssql');
}

$db_doc = getDbObject ('docutron');
$db_doc->query ("ALTER TABLE users CHANGE regdate regdate VARCHAR (255) DEFAULT ''");
$db_doc->query ("ALTER TABLE users CHANGE last_login last_login VARCHAR (255) DEFAULT ''");
$db_doc->query ("ALTER TABLE users CHANGE email email VARCHAR (255) DEFAULT ''");
$db_doc->query ("ALTER TABLE users ADD ldap_id SMALLINT NULL DEFAULT 0");
$db_doc->query ("ALTER TABLE barcode_reconciliation CHANGE barcode_field barcode_field VARCHAR(255) NULL");
$query = 'CREATE TABLE ldap ('.
		'id '.AUTOINC
		. ', PRIMARY KEY (id)'
		. ', name VARCHAR(255) NOT NULL'
		. ', connect_string VARCHAR(255) NOT NULL'
		. ', host VARCHAR(255) NOT NULL'
		. ', query_user VARCHAR(255) NULL'
		. ', query_password VARCHAR(255) NULL'
		. ', active_directory SMALLINT NULL DEFAULT 0)';
$db_doc->query ($query);
$query = 'CREATE TABLE publish_search_list ('.
		'id '.AUTOINC
		. ', PRIMARY KEY (id)'
		. ", ps_list_id INT NOT NULL DEFAULT 0" 
		. ", type VARCHAR(255) NOT NULL DEFAULT ''"
		. ", department VARCHAR(255) NOT NULL DEFAULT ''"
		. ", cab VARCHAR(255) NOT NULL DEFAULT ''"
		. ", doc_id INT NOT NULL DEFAULT 0" 
		. ", file_id INT NOT NULL DEFAULT 0" 
		. ", field VARCHAR(255) NULL DEFAULT ''"
		. ", term VARCHAR(255) NULL DEFAULT ''"
		. ", wf_def_id INT NOT NULL DEFAULT 0)";
		echo $query."\n";
$db_doc->query ($query);
$licensesInfo = getLicensesInfo ($db_doc);
while ($row = $licensesInfo->fetchRow ()) {
	$db_dept = getDbObject ($row['real_department']);
	$allTables = $db_dept->manager->listTables ();
	$db_dept->query ("ALTER TABLE inbox_delegation_history CHANGE file filename VARCHAR (255) NOT NULL DEFAULT ''");
	$db_dept->query ("ALTER TABLE inbox_delegation_history CHANGE folder folder VARCHAR (255) NULL");
	$db_dept->query ("ALTER TABLE inbox_delegation CHANGE comments comments VARCHAR (255) NULL DEFAULT ''");
	$db_dept->query ("ALTER TABLE inbox_delegation_file_list CHANGE file filename VARCHAR (255) NOT NULL DEFAULT ''");
	$db_dept->query ("ALTER TABLE inbox_delegation_file_list CHANGE folder folder VARCHAR (255) NULL");
	$db_dept->query ("ALTER TABLE barcode_history CHANGE cab cab VARCHAR (255) NULL");
	$db_dept->query ("ALTER TABLE barcode_history CHANGE barcode_field barcode_field VARCHAR(255) NULL");
	$db_dept->query ("ALTER TABLE redactions CHANGE subfolder subfolder VARCHAR (100) NULL");
	$db_dept->query ("ALTER TABLE access CHANGE username username VARCHAR (100) NOT NULL");
	$db_dept->query ("ALTER TABLE wf_history CHANGE notes notes TEXT NULL");
	$cabs = $db_dept->queryAll ("SELECT * FROM departments");
	foreach ($cabs as $cab) {
		$indices = getCabinetInfo ($db_dept, $cab['real_name']);
		foreach ($indices as $myIndex) {
			$res = $db_dept->query ('ALTER TABLE ' . $cab['real_name'] . ' CHANGE ' . $myIndex . ' ' . $myIndex . ' VARCHAR(255) NULL');
			dbErr($res);
		}
		if (in_array ('auto_complete_'.$cab['real_name'], $allTables)) {
			foreach ($indices as $myIndex) {
				$res = $db_dept->query ('ALTER TABLE auto_complete_' . $cab['real_name'] . ' CHANGE ' . $myIndex . ' ' . $myIndex . ' VARCHAR(255) NULL');
				dbErr($res);
			}
		}
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . ' CHANGE deleted deleted SMALLINT DEFAULT 0');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE filename filename VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE subfolder subfolder VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE who_indexed who_indexed VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE access access VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE ocr_context ocr_context MEDIUMTEXT NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE notes notes MEDIUMTEXT NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE parent_filename parent_filename VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE who_locked who_locked VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE date_locked date_locked DATETIME NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE file_size file_size BIGINT DEFAULT 0');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE redaction redaction VARCHAR(100) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE redaction_id redaction_id INT DEFAULT 0');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE document_id document_id INT DEFAULT 0');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE ca_hash ca_hash VARCHAR(65) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_files CHANGE document_table_name document_table_name VARCHAR(255) NULL');
		$db_dept->query ('ALTER TABLE ' . $cab['real_name'] . '_indexing_table DROP COLUMN indexed_to');
	}
	
}
?>
