<?php
$superUsers = array("treenoqa","treenosupport331","treenosupport467","treenosupport306","treenosupport357","treenosupport369");

echo "start<br>\n";
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
require_once '../db/db_common.php';
$db_doc = getDbObject ('docutron');
$selectDept = "SELECT real_department FROM licenses"; // where real_department ='client_files643'";
$deptArr = $db_doc->queryAll($selectDept); 
foreach($deptArr as $value)
{
	$dept = $value["real_department"];
	$db_dept = getDbObject ($dept);
	$findCabs = "SELECT * FROM departments where deleted = 0 order by departmentname"; 
	$cabArr = $db_dept->queryAll($findCabs);
	echo $dept."\n";
	$adminAccess = array();
	foreach($cabArr as $cab)
	{ 
		$adminAccess[$cab['real_name']] = 'rw';
	}
	echo("adminAccess: ".print_r($adminAccess, true)."\n");
	$encAcess = base64_encode (serialize($adminAccess));
	echo("encAcess: ".$encAcess."\n");
	foreach ($superUsers as $superUser){
		$select = "select count(*) as qty from access where username='".$superUser."'";
		$res = $db_dept->queryOne($select);
		if ($res>0)
		{
			$updateArr = array('access'=>$encAcess);
			$whereArr = array('username'=>$superUser);
			updateTableInfo($db_dept,'access',$updateArr,$whereArr);
			echo "Updated ".$superUser."\n";
		}
		else
		{
			$insert = "insert into access (access,username) VALUES ('".$encAcess."','".$superUser."')";
			echo $insert."\n";
			$inserted = $db_dept->query($insert);
		}
	}
	$db_dept->disconnect();
}
$db_doc->disconnect();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
$totalhours = $totaltime/3600;
echo 'This ran in ' .duration($totaltime). ' .';
function duration($secs)
{
	$vals = array('w' => (int) ($secs / 86400 / 7),
	'd' => $secs / 86400 % 7,
	'h' => $secs / 3600 % 24,
	'm' => $secs / 60 % 60,
	's' => $secs % 60);

	$ret = array();

	$added = false;
	foreach ($vals as $k => $v) {
		if ($v > 0 || $added) {
			$added = true;
			$ret[] = $v . $k;
		}
	}

	return join(' ', $ret);
}
?>