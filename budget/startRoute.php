<?PHP
include_once '../budget/routeBudgetContract.inc.php';
include_once '../budget/routeBudgetLicense.inc.php';
include_once '../lib/settings.php';

global $DEFS;
$data_dir = $DEFS['DATA_DIR'];
$db_doc = getDbObject ('docutron');
//$routeObj = new routeBudgetContract('client_files', 'Contracts', '/opt/test', $db_doc);
$routeObj = new routeBudgetContract('client_files', 'Contracts', '/opt/contracts', $db_doc);
echo "before route()\n";
$routeObj->route();
echo "after route()\n";
$routeObj->stop();

//$licenseObj = new routeBudgetLicense('client_files', 'Contracts', '/opt/test', $db_doc);
$licenseObj = new routeBudgetLicense('client_files', 'Contracts', '/opt/licenses', $db_doc);
echo "starting license\n";
$licenseObj->route();
echo "after license route\n";
$licenseObj->stop();


?>
