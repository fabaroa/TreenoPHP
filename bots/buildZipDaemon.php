<?PHP
/* This bot takes a department, a cabinet, a temptable of fileIDs, and an intended filename to create
	a zip file.  Created for the webservice zip functions
*/
include_once '../lib/utility.php';
include_once '../db/db_common.php';
include_once '../lib/settings.php';
include_once '../lib/fileFuncs.php';

$department = $argv[1];
$cabinet = $argv[2];
$tempFileTable = $argv[3];
$fileName = $argv[4];

if($department == NULL OR $cabinet == NULL OR 
	$tempFileTable == NULL OR $fileName == NULL) {
	error_log("Missing required information\n");
	die("Missing required information\n");
}

$db_dept = getDbObject($department);
$db_doc = getDbObject('docutron');
$zipPath = $DEFS['DATA_DIR']."/$department/zipTemp/$cabinet/$fileName";
$zipfoldername=$fileName;

//Create the zip path
error_log( "makeAllDir( $zipPath )" );
if( !file_exists($zipPath) ) {
	makeAllDir($zipPath);
}

$indices = array();
$tempIndices = getCabinetInfo($db_dept, $cabinet);
foreach($tempIndices AS $index) {
	$indices[] = "$cabinet.$index";	
}

$selectArr = array($cabinet.'_files.id', $cabinet.'_files.doc_id', 'location', 'subfolder', 'filename', implode(",", $indices));
$tableArr = array($cabinet, $cabinet."_files", $tempFileTable);
$whereArr = array("$tempFileTable.result_id=$cabinet"."_files.id", "$cabinet.doc_id=$cabinet"."_files.doc_id");
$infoArr = getTableInfo($db_dept, $tableArr, $selectArr, $whereArr, 'getAssoc');

$indexFileArr = array();
foreach($infoArr AS $fileID => $locationArr) {
	$doc_id = $locationArr['doc_id'];
	$subfolder = $locationArr['subfolder'];
	$dbFilename = $locationArr['filename'];
	$dbLoc = str_replace(" ", "/", $locationArr['location']);
	$origLoc = $DEFS['DATA_DIR']."/".$dbLoc."/".$subfolder."/".$dbFilename;
	$destLoc = $zipPath."/".$doc_id."/".$subfolder."/";
	if( !file_exists($destLoc) ) {
		makeAllDir($destLoc);
	}
	$destLoc .= $dbFilename;
//	error_log( "$origLoc, $destLoc" );
	copy($origLoc, $destLoc);
	allowWebWrite($destLoc, $DEFS);

	//For the index file
	$folderInfo = "";
	foreach($tempIndices AS $index) {
		$folderInfo .= ", ".$locationArr[$index];
	}
	$indexFileArr[$doc_id] = $folderInfo;
}

createIndexFile($department, $cabinet, $tempIndices, $indexFileArr, $zipPath);
error_log( $zipPath );
chdir($zipPath);
$unc = getcwd();
//zips the file under the zip2 extension to prevent users from getting the zip
//	before the zip file is done compressing
//$cmd = $DEFS['ZIP_EXE'] . ' -r '.escapeshellarg($unc.".zip2").' "'.$unc.'" > h:\debug.txt';//.$zipPath.'';
//$cmd = escapeshellarg($cmd);
//error_log( $cmd );
//error_log( shell_exec( $cmd) ) );

reczip( $zipPath, $zipPath.'.zip2', 5, $zipfoldername );
//Renames the zip2 file to zip after the compression
rename($zipPath.".zip2", $zipPath.".zip");
//Removes the zip data directory
delDir($zipPath);
allowWebWrite($zipPath.".zip", $DEFS);

//reczip( $str, $str.time().".zip", 3, 'test'.time());
function reczip( $path, $zipfilename, $normalize, $basepathname ){
	if( !is_dir( $path ) )
		die("$path not found\n" );
	$fileArr = array();
	getFiles( $path, $fileArr );
	print_r( $fileArr );
	$zip = new ZipArchive();
	$zip->open($zipfilename, ZipArchive::CREATE);
	foreach( $fileArr as $file ){
		$newfilename=normalizePath( $file, $normalize );
		echo "newfilename=$basepathname/$newfilename\n";
		$zip->addFile($file, $basepathname.'/'.$newfilename);
	}
	$zip->close();
}

function getFiles( $path, &$fileArr ){
	$files = glob( "$path/*" );
	foreach( $files as $file ){
		echo "file=$file\n";
		if( is_dir( $file ) ){
			getFiles( $file, $fileArr );
		}else{
			$fileArr[] = $file;
		}
	}

}

function normalizePath( $path, $levels ){
	$arr = explode( '/', $path );
	print_r( $arr );
	$npath = array();
	for( $i=0, $count=0; $i < $levels and $count < 20; $count++ ){
		if( $arr[0]!='' ){
			$i++;
			array_shift( $arr );
		}else{
			array_shift( $arr );
		}
	}
	$npath = implode( '/', $arr );
	return $npath;
}

function createIndexFile($department, $cabinet, $tempIndices, $indexFileArr, $zipPath)
{
	$fd = fopen("$zipPath/index.csv", "w+");
	$columnHeader = "Folder, ".implode(", ", $tempIndices);
	fwrite($fd, $columnHeader."\n");
	foreach($indexFileArr AS $doc_id => $folderInfo) {
		fwrite($fd, "$doc_id $folderInfo\n");
	}
	fclose($fd);
}

?>
