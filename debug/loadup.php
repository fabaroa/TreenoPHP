#!/usr/bin/php -q
<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';

$db_raw = getDbObject('client_files' );
$db_name = "client_files";
//check 3 argv for cabinet
//loadup is setup for a cabinet with two index fields
$cabname = $argv[2];
$count = getTableInfo($db_raw, $cabname.'_files', array('COUNT(*)'), array(), 'queryOne');
$num = getTableInfo($db_raw,$cabname,array('MAX(doc_id)'),array(),'queryOne');
$columns = getCabinetInfo ($db_raw, $cabname);
$number = $num + 1;
for ($i = 0; $i < $argv[1]; $i ++) {
	$location = $DEFS['DATA_DIR']."/".$db_name."/".$cabname;
	$loc = $cabname;
	if ($count >= 30000) {
		$cab_number = floor($count / 30000);
		$loc .= "__".$cab_number;
		if (!file_exists("$location"))
			mkdir("$location");
	}
	$insertArr1 = array(
		'location'	=> "$db_name $location $number-loadup",
		'deleted'	=> (int)0,
		$columns['0']	=> "load-$number",
		$columns['1']	=> "load-$number"
			   );
	$res = $db_raw->extended->autoExecute($cabname,$insertArr1);
	dbErr($res);
	mkdir("$location/$number-loadup");

	for ($j = 1; $j <= 10; $j ++) {
		copy ('1.TIF', "$location/$number-loadup/$j.TIF");
		$insertArr2 = array(
			"filename"		=> $j.'.TIF',
			"doc_id"		=> (int)$number,
			"ordering"		=> (int)$j,
			"date_created"		=> date('Y-m-d H:i:s'),
			"who_indexed"		=> 'admin',
			"parent_filename"	=> $j.'.TIF',
				   );
		$res = $db_raw->extended->autoExecute($cabname.'_files',$insertArr2);
		dbErr($res);
	}
	$number ++;
	$count ++;
}
$db_raw->disconnect ();
?>
