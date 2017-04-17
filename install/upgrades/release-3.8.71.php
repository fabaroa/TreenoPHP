<?PHP
chdir('..');
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../lib/fileFuncs.php';

$db_doc = getDbObject('docutron');

$query = 'ALTER TABLE users ADD ldap_user VARCHAR(100) NULL';
$res = $db_doc->query($query);
dbErr($res);

?>
