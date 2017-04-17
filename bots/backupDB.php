<?php
chdir (dirname (__FILE__));
/*TODO make more dynamic upon install*/
include_once "../db/db_common.php";
include_once "../db/db_engine.php";
include_once '../lib/settings.php';
include_once '../lib/utility.php';
include_once '../lib/tables.php';
include_once '../lib/fileFuncs.php';

$path = $DEFS['DATA_DIR']."/backups";
if(!is_dir($path)) {
	mkdir($path);
	allowWebWrite($path, $DEFS);
}

$db_doc = getDbObject('docutron');
$depArr = getTableInfo($db_doc,'licenses',array('real_department'),array(),'queryCol');
$db_doc->disconnect();

//create backup direcotry for that day
$date = date('Y-m-d');
chdir($path);

$path = $date;
mkdir($path);
allowWebWrite ($path, $DEFS);

if(getdbType() == "mysql" || getdbType() == "mysqli" ) {

	//create individual dumps foreach database
	$res = shell_exec($DEFS['MYDUMP_EXE'] . "  -p -e -u $db_username --password=$db_password docutron > ".escapeshellarg($path.'/docutron.sql'));
	allowWebWrite($path.'/docutron.sql', $DEFS);
	foreach($depArr AS $dep) {
		shell_exec($DEFS['MYDUMP_EXE'] . " -p -e -u $db_username --password=$db_password $dep > ". escapeshellarg ($path.'/'.$dep.'.sql'));
		allowWebWrite($path.'/'.$dep.'.sql', $DEFS);
	}
} elseif(getDbType() == 'pgsql') {
	shell_exec($DEFS['PGDUMP_EXE'] . " -U $db_username docutron > " . escapeshellarg($path.'/docutron.sql'));
	allowWebWrite($path.'/docutron.sql', $DEFS);
	foreach($depArr as $dep) {
		shell_exec($DEFS['PGDUMP_EXE'] . " -U $db_username $dep > ". escapeshellarg ($path.'/'.$dep.'.sql'));
		allowWebWrite($path.'/'.$dep.'.sql', $DEFS);
	}
}
//zip the backup directory for that day
shell_exec($DEFS['ZIP_EXE'] . ' -q9r ' . escapeshellarg ($path.'.zip') . ' ' . escapeshellarg($path));
allowWebWrite($path.'.zip', $DEFS);


//remove sql dump files after they have been zipped
unlink($path.'/docutron.sql');
foreach($depArr AS $dep) {
	unlink($path.'/'.$dep.'.sql');
}
rmdir($path);

//get a list of current backups
$path = $DEFS['DATA_DIR'].'/backups';
$hd = opendir($path);
$fileList = array();
while(false !== ($file = readdir($hd))) {
	if(is_file($path."/".$file)) {
		$fileList[] = $file;
	}
}

//remove all backups that are over a week old
usort($fileList,"strnatcasecmp");
while(count($fileList) > 7) {
	unlink($path."/".$fileList[0]);
	unset($fileList[0]);
	$fileList = array_values($fileList);
}

/*
elseif(getdbType() == "db2") {
	//NOT WINDOWS-SAFE
	$home_path = '/home/db2inst1/backup';
	if(!file_exists($home_path)) {
		mkdir($home_path,0755);
		chown($home_path,'db2inst1');
		chgrp($home_path,'db2grp1');
	}


//	global $DEFS;

//	$res = shell_exec("su - db2inst1 -c \"db2 BACKUP DATABASE dtron USER db2inst1 USING docutron TO $home_path\"");
//	print_r($res);
	foreach($depArr AS $dep) {
		$res = shell_exec("su - db2inst1 -c \"db2 BACKUP DATABASE ".getDatabase($dep)." USER db2inst1 USING docutron TO $home_path\"");
		print_r($res);
	}
	$destPath = $DEFS['DATA_DIR']."/client_files";
	if(!file_exists($destPath."/"."db2Backup")) {
		mkdir($destPath."/db2Backup",0755);
		chown($destPath."/db2Backup",'apache');
		chgrp($destPath."/db2Backup",'apache');
	}
	$destPath .= "/db2Backup/";

	$path = "/home/db2inst1";
	$date = date('M-d-Y-H:i:s');
	if(!file_exists($path."/backup-".$date)) {
		mkdir($path."/backup-".$date,0755);
		chown($path."/backup-".$date,'db2inst1');
		chgrp($path."/backup-".$date,'db2grp1');
	}
	$path .= "/backup-".$date."/";
	foreach($depArr AS $dep) {
		$dep = getDatabase($dep);
		backupDB($dep,$path);
	}
	shell_exec("mv $path $destPath");
	chmod($destPath."/backup-".$date,0755);
	chown($destPath."/backup-".$date,'apache');
	chgrp($destPath."/backup-".$date,'apache');
}
*/
?>
