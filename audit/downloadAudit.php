<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';
include_once '../lib/mime.php';           //added 10/23.03

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isAdmin()) {  
	$uname = $user->username;
	$auditFName = "{$user->username}-audit.csv";
	$tmpPath = $DEFS['DATA_DIR']."/".$user->db_name."/".$uname."_backup";
	if( !file_exists($tmpPath) ) {
		mkdir($tmpPath, 0777);
	}
	chmod($tmpPath, 0777);

	if ( file_exists($tmpPath."/".$auditFName) ) {
		unlink($tmpPath."/".$auditFName);
	}
/*
	if(getDbType() == 'pgsql') {
		$query = "COPY audit TO '$tmpPath/$auditFName' WITH CSV";
	} else {
		$query = "SELECT audit.* INTO OUTFILE '$tmpPath/$auditFName' FIELDS TERMINATED BY ',' ESCAPED BY '".addslashes("\\");
		$query .= "' LINES TERMINATED BY '"."\\n"."' STARTING BY '' FROM audit";
	}
	$res = $db_object->query($query);
	dbErr($res);
*/
//old code above
	if (getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$query = "SELECT * INTO OUTFILE '$tmpPath/$auditFName' FIELDS TERMINATED BY ',' LINES TERMINATED BY \"\\n\" FROM audit";
		$res = $db_object->query($query);
		dbErr($res);
	} else {
		$query = "SELECT * FROM audit";
		$res = $db_object->query($query);
		dbErr($res);
		$fd = fopen($tmpPath.'/'.$auditFName, 'w+');
		if ($fd == null) {
			die("failed to create a files $tmpPath.'/'.$auditFName");
		}

		while ($row = $res->fetchRow()) {
			$value = $row['id'];
			$value .= ",".$row['username'];
			$value .= ",".$row['datetime'];
			$value .= ",".$row['info'];
			$value .= ",".$row['action'];
			if ($value != "")
				fwrite($fd, $value."\n");

		}
		fclose($fd);
	}
//new code above
	downloadFile( $tmpPath, $auditFName, true, true); 
	$user->audit("Audit Table Downloaded", "Audit Table has been downloaded from the database");
	unlink($tmpPath."/".$auditFName);
	setSessionUser($user);
} else {//we want to log them out
	logUserOut();
}
?>
