<?PHP
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once '../lib/settings.php';
include_once '../lib/xmlObj.php';

function startBCReport($enArr, $user) {
	global $DEFS;
	$db_doc = getDBObject('docutron');
	$department = $user->db_name;
	$username = $user->username;
	$startDate = $enArr['startDate'];
	$endDate = $enArr['endDate'];

	$key = "docDaemon_execute";
	$cmd = "";
	if (substr (PHP_OS, 0, 3) != 'WIN') {
		$cmd .= "nice -17 ";
	}
	$cmd .= "php -q ".$DEFS['DOC_DIR']."/bots/barcodeReportBot.php $department $username $startDate $endDate";
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $cmd,
		"department"	=> $department
	);
	$res = $db_doc->extended->autoExecute("settings", $insertArr);
	dbErr($res);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION", "updateMess(XML)");
	$xmlObj->createKeyAndValue('SUCCESS',NULL,array('value' => 1));
	$xmlObj->setHeader();
}
?>
