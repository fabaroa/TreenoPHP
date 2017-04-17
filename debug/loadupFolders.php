#!/usr/bin/php -q
<?php
include_once '../db/db_common.php';
include_once '../lib/settings.php';
include_once '../lib/utility.php';
 $db_name = 'client_files';

 $db_raw = getDbObject('client_files');
//check 3 argv for cabinet
//loadup is setup for a cabinet with two index fields
	$cabname = $argv[2];
	$str = "select max(doc_id),count(*) from $cabname";
	$res = $db_raw->query( $str );

//$id = "max(doc_id), count(*)";
//$res = getCountId($db_raw, $id, $cabname);
$num = $res->fetchrow();
$number = $num['max(doc_id)'] + 1;
$count = $num['count(*)'];
for ($i = 0; $i < $argv[1]; $i ++) {
	$location = $cabname;
	if ($count >= 30000) {
		$cab_number = floor($count / 30000);
		$location .= "__".$cab_number;
		if (!file_exists("../../$db_name/$location"))
			mkdir("../../$db_name/$location");
	}
	$insertArr1 = array(
		'location'	=> "$db_name $location $number-loadup",
		'first'		=> "load-$number",
		'second'	=> "load-$number"
			   );
	$res = $db_raw->extended->autoExecute($cabname,$insertArr1);
	dbErr($res);
	 `mkdir {$DEFS['DATA_DIR']}/$db_name/$location/$number-loadup`;
	for ($j = 1; $j <= 10; $j ++) {
		 `cp 1.TIF {$DEFS['DATA_DIR']}/$db_name/$location/$number-loadup/$j.TIF&`;
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
