<?PHP
//chdir('..');
include_once '../db/db_common.php';
include_once '../lib/utility.php';

addInboxEnforcement();
function addInboxEnforcement() {
	$db_doc = getDBObject('docutron');
	$depArr = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($depArr AS $department) {
		$db_dept = getDBObject($department);
		$createArr = array(
			"id ".AUTOINC,
			"PRIMARY KEY (id)",
			"cabinetID INT NOT NULL DEFAULT 0",
			"KEY (cabinetID)",
			"fieldName VARCHAR(255) NOT NULL",
			"KEY (fieldName)",
			"required INT(1) DEFAULT 0",
			"regex VARCHAR(255) DEFAULT ''",
			"display VARCHAR(255) DEFAULT ''"
		);

		$query = "CREATE TABLE fieldFormat (".implode(', ', $createArr).')';
		$res = $db_dept->query($query);
		dbErr($res);
	}
}

?>
