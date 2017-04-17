<?PHP
include_once '../db/db_common.php';
include_once '../lib/utility.php';

$db_dept = getDbObject('client_files');
$doc_id = getTableInfo($db_dept, 'poiu', array('doc_id'), array('mimo' => 'wrgwrgwergrwegrgrhgfngfn'), 'queryOne');
print_r($doc_id);
if($doc_id == null) {
	echo "doc_id == null\n";
}

?>
