<?PHP
/* This script looks for all the files in the given department for any filesizes betweem 4KB and 15KB, and runs gocr on the file for barcodes
 * Generates a report in the given username's inbox
 */
include_once '../db/db_common.php';
include_once '../lib/settings.php';
include_once '../lib/mime.php';
include_once '../lib/searchLib.php';

$department = $argv[1];
$username = $argv[2];
$dStart = $argv[3];
$dEnd = $argv[4];

$DATA_DIR = $DEFS['DATA_DIR'];
$dateStart = "0000-00-00 00:00:00";
$dateEnd = date("Y-m-d")." 23:59:59";
$db_dept = getDBObject($department);

if( isSet($dStart) and isISODate ($dStart)) {
	$dateStart = $dStart." 00:00:00";
}
$dateStart_out =date("F j, Y", strtotime($dateStart));

if( isSet($dEnd) and isISODate ($dEnd) ) {
	$dateEnd = $dEnd." 23:59:59";
}
$dateEnd_out =date("F j, Y", strtotime($dateEnd));

$filename = "report".date("Y_m_d_H_i_s").".xls";
$inboxPath = $DATA_DIR."/$department/personalInbox/$username/";
$fd = fopen($inboxPath.$filename, "w+");
fwrite($fd, "Start: $dateStart_out \t End: $dateEnd_out\n");

$cabArr = getTableInfo($db_dept, 'departments', array('real_name'), array('deleted' => (int)0), 'queryCol');
foreach($cabArr AS $cabinet) {
	fwrite($fd, "Cabinet: $cabinet\n");
	$cabIndiceArr = getCabinetInfo($db_dept, $cabinet);
	$folders = getTableInfo($db_dept, $cabinet, array(), array('deleted' => (int)0), 'getAssoc');
	$whereArr = array('display=1', 'deleted=0', 'filename IS NOT NULL', "date_created > '".$dateStart."'", 
			"date_created < '".$dateEnd."'", "file_size > 4000", "file_size < 15000");
	$files = getTableInfo($db_dept, 
		$cabinet."_files", 
		array(),
		$whereArr
	);

	while($eachFile = $files->fetchRow()) {
		processFile($eachFile, $folders, $cabIndiceArr, $DATA_DIR, $fd,$DEFS);
	} 
}

fclose($fd);


function processFile($eachFile, $folders, $cabIndiceArr, $DATA_DIR, $fd, $DEFS)
{
	$filename = $eachFile['filename'];
	$doc_id = $eachFile['doc_id'];
	$folderInfo = $folders[$doc_id];
	$location = $folderInfo['location'];
	$location = $DATA_DIR."/".str_replace(" ", "/", $location);
	$fileSize = $eachFile['file_size'];
//echo "location: $location  file: $filename\n";

	if( getMimeType($location."/".$filename,$DEFS) == 'image/tiff' ) {
		$barcode = false;
		fwrite($fd, "\tFolder: ");
		foreach($cabIndiceArr AS $cabIndex) {
			fwrite($fd, $folderInfo[$cabIndex]."\t"); 
		}
		fwrite($fd, "\n\t\tFilename: $filename\n");
		fwrite($fd, "\t\tFile size in range of barcode sheet: $fileSize k\n");
		$barcode = gocrTiff($location."/".$filename, $DEFS);

		if( $barcode !== false ) {
			fwrite($fd, "\t\tBarcode found: $barcode\n");
		}
	}
}

function findFilesize($fullPath)
{
	$fileSize = filesize($fullPath);
	if( $fileSize > 4000 AND $fileSize < 15000 ) {
//		echo "filesize fits barcode size\n";
		return $fileSize;
	}
	return false;
}

function gocrTiff($fullPath, $DEFS)
{
	$regex = '/code="(.*)" crc=/';
	$regexError = '/error="(.*)"/';
	$code128Regex = '/type="128"/';

	$testPNM = "/tmp/testBarcode1.pnm";
	exec($DEFS['TIFFTOPNM_EXE'] . ' ' . escapeshellarg($fullPath).' > '.$testPNM);
	exec($DEFS['PAMFILE_EXE'] . ' '.$testPNM);
	$ocrData = exec($DEFS['GOCR_EXE'] . ' '.$testPNM);
	preg_match($regex, $ocrData, $matches);
	if( $matches and isSet($matches[1]) ) {
		return $matches[1];
	}
	return false;
}
?>
