<?php
echo "start<br>\n";
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
$fp=fopen("\\treeno\\logs\\badCab.log","a+");
fwrite($fp,date("Y-m-d H:i:s")."\n");
require_once '../db/db_common.php';
$db_doc = getDbObject ('docutron');
$selectDept = "SELECT real_department FROM licenses";
$deptArr = $db_doc->queryAll($selectDept); 
foreach($deptArr as $value)
{
	$dept = $value["real_department"];
	$db_dept = getDbObject ($dept);
	$findCabs = "SELECT departmentid,real_name FROM departments where deleted = 0"; 
	$cabArr = $db_dept->queryAll($findCabs);
	echo $dept."\n";
	foreach($cabArr as $cab)
	{ 
		$query="SELECT COUNT(*) as qty FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N'".$cab["real_name"]."'";
		$checkCab = $db_dept->queryOne($query);
		$query="SELECT COUNT(*) as qty FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N'".$cab["real_name"]."_files'";
		$checkCab_files = $db_dept->queryOne($query);
		$query="SELECT COUNT(*) as qty FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N'".$cab["real_name"]."_indexing_table'";
		$checkCab_indexing_table = $db_dept->queryOne($query);
		if ($checkCab == 0 || $checkCab_files == 0 || $checkCab_indexing_table == 0)
		{
			fwrite($fp,$dept.": ".$cab["real_name"]." marked for deletion\n");
			$updateCab = "update departments set deleted = 1 where departmentid=".$cab["departmentid"]; 
			echo $updateCab."\n";
			$fixed = $db_dept->queryAll($updateCab);
		}
		else
		{
			//echo($dept.": ".$cab["real_name"]." OK\n");
		}
	}
	$db_dept->disconnect();
}
$db_doc->disconnect();
fwrite($fp,"Ended"."\n");
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
$totalhours = $totaltime/3600;
fwrite($fp,'This ran in ' .duration($totaltime). ' .'."\n");
fclose($fp);
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