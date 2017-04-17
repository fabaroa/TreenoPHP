<?php
chdir('..');
include_once '../db/db_common.php';
include_once '../lib/utility.php';
$db_doc = getDbObject('docutron');
$sArr = array('real_department');
$depList = getTableInfo($db_doc,'licenses',$sArr,array(),'queryCol');
foreach($depList AS $dep) {
	$insertArr = array(	'arb_name'	=> 'Demo',
						'real_name' => 'demo',
						'dir'		=> 'demo',
						'enabled'	=> 0,
						'department'=> $dep);
	$db_doc->extended->autoExecute('modules',$insertArr);
}
?>
