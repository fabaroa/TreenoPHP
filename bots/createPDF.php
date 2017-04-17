<?php
chdir(dirname(__FILE__));
include_once '../lib/PDF.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/settings.php';

//I don't think we need this. Let us try to not do it.
//sleep(5);

$filename = $argv[1];
$uname = $argv[2];
$tmpPath = $argv[3];
$db_name = $argv[4];
$pdfListTmp = file($filename);
$pdfList = array ();
foreach($pdfListTmp as $myFile) {
	$pdfList[] = trim($myFile);
}

//Initializes an array containing converted jpeg files
//converts all jpegs to tiffs
convertJpeg2Tif ($pdfList);
createPDFFromTiffs($pdfList,  $tmpPath, $uname);


//Selecting the names of files
$PDFName = $uname."-files.pdf";
$finalPDFName = $PDFName;
$finalPath = $DEFS['DATA_DIR']."/$db_name/personalInbox/$uname";
$finalPDFName = filenameExists( $finalPath.'/'.$finalPDFName );
$origFile = $DEFS['TMP_DIR'].'/docutron/'.$uname.'/'.$PDFName;

if (file_exists ($origFile)) {
	//moves the final PDF to the inbox directory
	rename($origFile, $finalPDFName);
} else {
	error_log ($origFile . ' does not exist.');
}

//Tests to see if filename already exist in directory
//Tacks on a "-$i" if it does
function filenameExists( $filename )
{
	if(file_exists($filename)) 
	{
		$i = 1;
		do {
			$fileArr = explode(".", $filename);
			$fileArr[count($fileArr) - 2] .= "-$i";
			$testName = implode(".", $fileArr);
			$i++;
		} while(file_exists($testName));
		
		return $testName;
	}
	
	return $filename;
}

//Converts jpeg filea to tiff format
function convertJpeg2Tif( &$pdfList)
{
	global $DEFS;
	$pdf = array();

	foreach($pdfList as $filename) {
		$ext = ".jp"; //works for both ".jpg" & ".jpeg"
		$position = strpos( strtolower($filename), $ext );
		if( $position !== false )
		{
			$newName = filenameExists( $filename );
			$position = strpos( strtolower($newName), $ext );
			$ext = substr( $newName, $position);
			$newName = substr( $newName, 0, $position );

			$newName = $newName.".tif";
			// this line is for a windows problem see in ticket #257395
			$cmd = $DEFS['CONVERT_EXE'].' '.escapeshellarg ($filename) . ' '.escapeshellarg ($filename);
			exec ($cmd);
			// --------------------------------------
			$cmd = $DEFS['JPEGTOPNM_EXE'].' ' . escapeshellarg ($filename) . 
				' | '.$DEFS['PNMSCALE_EXE'].' -pixels 500000 | '.$DEFS['PNMTOTIFF_EXE'].' > ' .
				escapeshellarg ($newName);
			exec ($cmd);

			$pdf[] = $newName;
		}
		else
			$pdf[] = $filename;
	}

	$pdfList = $pdf;
}

?>
