<?
//This script adds any missing settings to the settings_list table based from 
//	queryAllSettings
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/allSettings.php';
include_once '../modules/modules.php';

$db_doc = getDbObject('docutron');
$settings = array();
$settings = getTableInfo($db_doc,'settings',array(),array('k' => 'settings_list_id'));
while( $row = $settings->fetchRow() ) {
	$db_name = $row['department'];
	$list_id = $row['value'];

	$allSettings = array();
	$allSettings = queryAllSettings($db_name);
echo "list_id: $list_id\n";
print_r($allSettings);
	$missingSettings = array();
	$missingSettings = getMissingSettings($db_doc, $list_id, $allSettings);
print_r($missingSettings);
	insertIntoDB($db_doc, $db_name, $list_id, $missingSettings);
}
/*
$allSettings = queryAllSettings('client_files');
$a = getSettings($db_doc, 1, $allSettings);
print_r($a);
*/

$db_doc->disconnect ();

function insertIntoDB($db_doc, $db_name, $list_id, $missingSettings) {
	foreach(array_keys ($missingSettings) as $setting) {
		$insertArr = array(
			"list_id"		=> $list_id,
			"cabinet"		=> "0",
			"setting"		=> $setting,
			"enabled"		=> (int)0,
			"department"	=> $db_name
		);
		$res = $db_doc->extended->autoExecute('settings_list', $insertArr);
		dbErr($res);
	}
}

function getMissingSettings($db_doc, $list_id, $allSettings) {
	$settingsList = array();
	$returnArr = $allSettings;
	$settingsList = getTableInfo($db_doc, 'settings_list', array(), array('list_id' => (int)$list_id));
	while( $row = $settingsList->fetchRow() ) {
		$setting = $row['setting'];
		if( array_key_exists($setting,$returnArr) ) {
			unSet($returnArr[$setting]); 
		}
	}

	return $returnArr;
}

?>
