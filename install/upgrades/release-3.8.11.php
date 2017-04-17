<?PHP
/*This script updates the cabinet indices from an empty string to NULL*/
chdir('..');
include_once '../db/db_common.php';
include_once '../lib/utility.php';

$db_doc = getDBObject('docutron');
$depList = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
foreach($depList AS $department) {
	$db_dept = getDBObject($department);
	$cabList = getTableInfo($db_dept, 'departments', array('real_name'), array(), 'queryCol');
	foreach($cabList AS $cabinet) {
		$cabinetIndices = getCabinetInfo($db_dept, $cabinet);
		foreach($cabinetIndices AS $index) {
			$query = "UPDATE $cabinet set $index = NULL WHERE $index = ''";
			$res = $db_dept->query($query);
			dbErr($res);
		}
	}
}
?>
