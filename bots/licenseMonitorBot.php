<?php
chdir (dirname (__FILE__));
include_once '../db/db_common.php';
include_once '../lib/utility.php';

$db = getDbObject('docutron');

$timestamp = time();

$res = getTableInfo($db, 'user_session', array('DISTINCT(department)'), array(), 'queryCol');

foreach($res as $dept) {
echo "department: $dept\n";

	$count = getTableInfo($db, 'user_session', array('COUNT(id)'), array('department' => $dept), 'queryOne');
	$queryArr = array (
		'num_used'		=> (int) $count,
		'currtime'		=> (int) $timestamp,
		'department'	=> $dept
	);
	$res = $db->extended->autoExecute('license_util', $queryArr);
	dbErr($res);
}

$db->disconnect();

?>
