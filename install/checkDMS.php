<?php

chdir(dirname(__FILE__));
require_once '../lib/settings.php';

$allDefs = array (
	'DATA_DIR',
	'DB_HOST',
	'DB_TYPE',
	'DOC_DIR',
	'FIRSTFILE',
	'HOST',
	'KEEP_BCPAGE',
	'OCR_THRESH',
	'OCRDEBUG',
	'TMP_DIR',
	'UPLOAD_TMP',
	'WWW_GROUP',
	'WWW_USER',
	'ANTIWORD_EXE',
	'CONVERT_EXE',
	'FILE_EXE',
	'GOCR_EXE',
	'GOCRFULL_EXE',
	'GUNZIP_EXE',
	'JPEGTOPNM_EXE',
	'MD5SUM_EXE',
	'MKISO_EXE',
	'PAMCOMP_EXE',
	'PAMFILE_EXE',
	'PAMFLIP_EXE',
	'PDF2PS_EXE',
	'PEAR_EXE',
	'PHP_EXE',
	'PNGTOPNM_EXE',
	'PNMDEPTH_EXE',
	'PNMSCALE_EXE',
	'PNMTOJPEG_EXE',
	'PNMTOPNG_EXE',
	'PNMTOPS_EXE',
	'PNMTOTIFF_EXE',
	'PS2PDF_EXE',
	'PSSELECT_EXE',
	'PSTOPNM_EXE',
	'TAR_EXE',
	'TIFF2PDF_EXE',
	'TIFFCP_EXE',
	'TIFFINFO_EXE',
	'TIFFSPLIT_EXE',
	'TIFFTOPNM_EXE',
	'UNZIP_EXE',
	'WGET_EXE',
	'ZIP_EXE',
);

$optDefs = array (
	'BGRUN_EXE',
	'CACLS_EXE',
	'DB_PASS',
	'DB_PORT',
	'DB_USER',
	'IPCONFIG_EXE',
	'PGDUMP_EXE',
	'TASKKILL_EXE',
	'TASKLIST_EXE',
);

$missingDefs = array ();
$missingOpt = array ();

foreach ($allDefs as $myDef) {
	if(!isset($DEFS[$myDef])) {
		$missingDefs[] = $myDef;
	}
}

foreach ($optDefs as $myDef) {
	if(!isset($DEFS[$myDef])) {
		$missingOpt[] = $myDef;
	}
}

if(count($missingDefs)) {
	echo "This Treeno installation is missing the following required DEFS " .
		"variables:\n";
	foreach($missingDefs as $myDef) {
		echo "\t$myDef\n";
	}
	echo "\n";
} else {
	echo "This Treeno installation is not missing any required DEFS " . 
		"variables.\n";
	echo "\n";
}

if(count($missingOpt)) {
	echo "This Treeno installation may or may not need the following DEFS " .
		"variables:\n";
	foreach($missingOpt as $myDef) {
		echo "\t$myDef\n";
	}
	echo "\n";
} else {
	echo "This Treeno installation is not missing any optional DEFS " . 
		"variables.\n";
	echo "\n";
}

?>
