#!/usr/bin/php -q
<?php
/*
 * appends the files from one cab to another
 * assumes each directory is a unique name
 * this script does not move files just database entries
 */
$db_name = 'docutron';
$db_object = getDbObject($db_name);

if (PEAR :: isError($db_object)) {
	die(header("location: /logout.php"));
}
$table = "Private";
$res = getTableInfo($db_object,$table);
  echo  getTableInfo($db_object, $table, array('COUNT(*)'), array(), 'queryOne')."\n";
while ($row = $res->fetchRow()) {
	$sp = explode(" ", $row['location']);
	$location = "client_files Personal_Lines ".$sp[2];
	$title = $row['title'];
	$keywords = $row['keywords'];

	$insertFolder = "insert into Personal_Lines values('','$location','$title','$keywords', NULL)";
        $res = $db_object->query($insertFolder);
        dbErr($res);

	$docQuery = "select max(doc_id) from Personal_Lines";
	$resDoc = $db_object->query($docQuery);
	$rowDoc = $resDoc->fetchRow();
	$qf = "select * from Private_files where doc_id=".$row['doc_id'];
	echo $qf."\n";
	$doc_id = $rowDoc['max(doc_id)'];
	$resf = $db_object->query($qf);
	while ($rowf = $resf->fetchRow()) {
		//prep insert string
		$filename = $rowf['filename'];
		$ordering = $rowf['ordering'];
		$date = $rowf['date_created'];
		$insFile = "insert into Personal_Lines_files values( '','$filename'," .
                        "$doc_id,NULL,$ordering,'$date',NULL,'admin',NULL,NULL,NULL)";
        	$res = $db_object->query($insFile);
        	dbErr($res);
	}
}
$db_object->disconnect ();
?>
