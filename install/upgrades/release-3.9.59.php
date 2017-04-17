<?php
include_once '../../db/db_common.php';
include_once '../../db/db_engine.php';
include_once '../../lib/utility.php';

$db_doc = getDbObject('docutron');
$Query = 'select * from settings Order by id DESC';
$res = $db_doc->queryAll( $Query );
$row = $res[0];
$NewID= $row['id'];
echo $NewID."<br>new<br>";
$sArr = array('real_department');
$wArr = array();
$depList = getTableInfo($db_doc,'licenses',$sArr,$wArr,'queryCol');
foreach($depList AS $dep) {
	$Query = "insert into settings (id,k,value,department) values ('".++$NewID."','inboxDelOnePage','0','".$dep."');";
	$res = $db_doc->queryAll( $Query );
echo $Query."<br>";
}
?>
