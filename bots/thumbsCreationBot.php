<?php

require_once '../db/db_common.php';
require_once '../lib/mime.php';
require_once '../lib/imageFuncs.php';

$goodExt = array('jpg', 'jpeg', 'pdf', 'tif', 'tiff');


$db_doc = getDbObject('docutron');

$departments = getTableInfo($db_doc, 'licenses', array('real_department'), 
		array(), 'queryCol');

foreach($departments as $department) {
	$db_dept = getDbObject($department);
	$cabinets = getTableInfo($db_dept, 'departments', array('real_name'),
			array('deleted' => 0), 'queryCol');
	foreach($cabinets as $cabinet) {
		$query = 'SELECT * FROM ' . $cabinet . ', ' . $cabinet .
			'_files WHERE ' . $cabinet . '.doc_id = ' . $cabinet .
			'_files.doc_id AND filename IS NOT NULL AND '. $cabinet .
			'_files.deleted = 0 AND ' . $cabinet . '.deleted = 0';
		$files = $db_dept->queryAll($query);
		dbErr($files);
		foreach($files as $fileInfo) {
			$myExt = strtolower(getExtension($fileInfo['filename']));
			if(in_array($myExt, $goodExt)) {
				$fileLoc = $DEFS['DATA_DIR'] . '/' .
					str_replace(' ', '/', $fileInfo['location']);
				if($fileInfo['subfolder']) {
					$fileLoc .= '/' . $fileInfo['subfolder'];
				}
				$fileLoc .= '/' . $fileInfo['filename'];
				$fileStartPath = $DEFS['DATA_DIR'] . '/' . $department;
				$thumbStartPath = $DEFS['DATA_DIR'] . '/' . $department . 
					'/thumbs';
				$thumbLoc = str_replace($fileStartPath, $thumbStartPath, 
						$fileLoc) . '.jpeg';
				if(file_exists($fileLoc) and !file_exists($thumbLoc)) {
					createThumbnail($fileLoc, $thumbLoc, $db_doc, $department);
				}
			}
		}
	}
}

?>
