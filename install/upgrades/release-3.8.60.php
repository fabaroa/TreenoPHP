<?PHP
chdir('..');
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../lib/fileFuncs.php';

$db_doc = getDbObject('docutron');
$depArr = getTableInfo($db_doc, 'licenses', array('real_department'), array(), 'queryCol');
foreach($depArr AS $department) {
	$db_dept = getDbObject($department);
	$stampDir = $DEFS['DOC_DIR'].'/stamps';
	$newStampDir = $DEFS['DATA_DIR'].'/'.$department.'/stamps';

	if( is_dir($newStampDir) ) {
		echo "stamps directory already exists\n";
		continue;
	}

	if( mkdir($newStampDir, 0777) ) {
		allowWebWrite($newStampDir, $DEFS);
		$dh = opendir($stampDir);
		while( false !== ($file = readdir($dh)) ) {
			if($file != "." AND $file != "..") {
				copy($stampDir."/$file", $newStampDir."/$file");
				allowWebWrite($newStampDir."/$file", $DEFS);
			}
		}
	}
}
?>
