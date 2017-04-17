<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/install.php';
include_once '../lib/settings.php';
include_once '../DataObjects/DataObject.inc.php';
include_once '../departments/depfuncs.php';

writeToDMSDefs('DB_HOST', 'localhost');
$DEFS['DB_HOST'] = 'localhost';
writeToDMSDefs('DB_TYPE', 'mysql');
$DEFS['DB_TYPE'] = 'mysql';
writeToDMSDefs('OCR_THRESH', '0.2');
$DEFS['OCR_THRESH'] = '0.2';
writeToDMSDefs('KEEP_BCPAGE', '0');
$DEFS['KEEP_BCPAGE'] = '0';

$db_doc = getDbObject('docutron');
upgradeDBInfos($db_doc);
doDepartmentThings($db_doc);
lowercaseUsername($db_doc);
$db_doc->disconnect ();
setupNewFiles();
#echo shell_exec('php -q removeDupTabs.php');
echo "\nInstalling/Upgrading PEAR::Image_Graph...\n";
shell_exec('pear config-set preferred_state alpha');
shell_exec('pear install -sa Image_Graph');
shell_exec('pear config-set preferred_state stable');

function upgradeDBInfos($db_doc) {
	$allQueries = getDocutronTableDefs();
	foreach($allQueries as $query) {
		$db_doc->query($query);
	}

	$query = "ALTER TABLE user_polls add shared SMALLINT NOT NULL DEFAULT 0";
	$res = $db_doc->query($query);
	$query = "ALTER TABLE user_polls add current_department VARCHAR(100) NOT NULL DEFAULT 0";
	$res = $db_doc->query($query);
	#$query = "ALTER TABLE licenses add dept_licenses INT NOT NULL DEFAULT 0";
	#$res = $db_doc->query($query);
	$query = "ALTER TABLE users ADD db_list_id SMALLINT DEFAULT 0";
	$res = $db_doc->query($query);
	$query = "ALTER TABLE user_security DROP PRIMARY KEY";
	$res = $db_doc->query($query);
	$query = "ALTER TABLE user_security DROP COLUMN uid";
	$res = $db_doc->query($query);
	$query = "ALTER TABLE user_security ADD uid INT AUTO_INCREMENT PRIMARY KEY FIRST";
	$res = $db_doc->query($query);
	$db_doc->query($query);
	$queryArr = array ();
	$query = getSelectQuery('users', array('id', 'db_name', 'defaultdb'),
		array(), array(), 0, 0, $queryArr);
	
	$res = $db_doc->query($query, $queryArr);
	if(!PEAR::isError($res)) {
		$j = 1;
		while($row = $res->fetchRow()) {
			if(!empty($row['db_name'])) {
				updateTableInfo($db_doc, 'users', array('db_list_id' => $j),
					array('id' => (int)$row['id']));
					
				$DO_user = DataObject::factory('users', $db_doc);
				$DO_user->get($row['id']);
				$dbArr = explode(':', $row['db_name']);
				for($i = 0; $i < count($dbArr) - 1; $i++) {
					$priv = $dbArr[$i]{0};
					$dbName = substr($dbArr[$i], 1);
					if($row['defaultdb'] == $dbName) {
						$DO_user->changeDepartmentAccess($dbName, $priv, 1);
					} else {
						$DO_user->changeDepartmentAccess($dbName, $priv, 0);
					}
				}
				$j++;
			}
		}
	}
	$query = "ALTER TABLE users DROP COLUMN db_name";
	$db_doc->query($query);
	$query = "ALTER TABLE users DROP COLUMN defaultdb";
	$db_doc->query($query);
}

function doDepartmentThings($db_doc) {
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	$allQueries = getDepartmentTableDefs();
	foreach($allDepts as $department) {
		$db = getDbObject($department);
		foreach($allQueries as $query) {
			$db->query($query);
		}
		$deptModulesArr = defaultDeptModules($department);
		foreach($deptModulesArr as $moduleArr) {
			$modCt = getTableInfo($db_doc, 'modules', array('COUNT(*)'),
				array('real_name' => $moduleArr['real_name'], 
				'department' => $department), 'queryOne');
			
			if($modCt == 0) {
				$res = $db_doc->extended->autoExecute('modules', $moduleArr);
				dbErr($res);
			}
		}
		$db->disconnect ();
	}
}

function setupNewFiles() {
	global $DEFS;
	if(!file_exists('/etc/init.d/docutron')) {
		copy('conf/docutron', '/etc/init.d/docutron');
		shell_exec('chkconfig docutron on');
		chmod('/etc/init.d/docutron', 0755);
		shell_exec('/etc/init.d/docutron start');
	}
	@mkdir($DEFS['DATA_DIR'].'/Scan');
	chown($DEFS['DATA_DIR'].'/Scan', 'apache');
	chgrp($DEFS['DATA_DIR'].'/Scan', 'apache');
	@mkdir($DEFS['DATA_DIR'].'/splitPDF');
	chown($DEFS['DATA_DIR'].'/splitPDF', 'apache');
	chgrp($DEFS['DATA_DIR'].'/splitPDF', 'apache');
}

function lowercaseUsername($db_doc) {
	$query = "UPDATE users SET username=LOWER(username)";
	$db_doc->query($query);

	$query = "UPDATE wf_todo SET username=LOWER(username)";
	$db_doc->query($query);
	
	$query = "UPDATE user_settings SET username=LOWER(username)";
	$db_doc->query($query);
		
	$allDepts = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
	foreach($allDepts as $department) {
		$db = getDbObject($department);
		
		$query = "UPDATE access SET username=LOWER(username)";
		$db->query($query);

		$query = "UPDATE user_list SET username=LOWER(username)";
		$db->query($query);

		$query = "UPDATE wf_documents SET username=LOWER(username)";
		$db->query($query);

		$db->disconnect ();
	}
}
?>
