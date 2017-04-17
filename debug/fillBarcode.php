<?php

$db_object = getDbObject('client_files2');
$desc = array('Scanned','Reconciled');
$users = array('admin','karl','bemis','will');
$cabinets = array('barcode','HR','Accounting','HRH');
for($i=1;$i<100001;$i++) {
	$num = rand(0,1);
	$user = rand(0,3);
	$cab = rand(0,3);
	$barcode = "2 ".rand(1,40)." ".rand(1,100000);
	$query = "INSERT INTO barcode_history VALUES('','$barcode','{$users[$user]}','{$cabinets[$cab]}','load-$i',NULL,NULL,'{$desc[$num]}')";
	$db_object->query($query);
}
$db_object->disconnect ();
?>
