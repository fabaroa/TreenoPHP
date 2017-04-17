<?php
include_once '../../db/db_common.php';

$db_doc = getDbObject('docutron');
$tableDef = array(
	'id '.AUTOINC,
	'PRIMARY KEY (id)',
	"location VARCHAR(255) NOT NULL DEFAULT ''",
	"department VARCHAR(255) NOT NULL DEFAULT ''",
	"cabinet VARCHAR(255) NOT NULL DEFAULT ''",
	"file_id INT NOT NULL DEFAULT 0",
);
$db_doc->query("CREATE TABLE ocr_queue (".implode(', ', $tableDef).')');

echo "Please Add [TIFF2PDF_EXE] to your DMS.DEFS file\n";
echo "Please Add [TIFFINFO_EXE] to your DMS.DEFS file\n";
?>
