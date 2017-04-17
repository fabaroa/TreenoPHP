<?php
//$Id$

chdir(dirname(__FILE__));
//chdir("c:/treeno/tesseract");
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/filter.php';
include_once '../lib/settings.php';
include_once '../lib/mime.php';
include_once '../lib/synchronizeBots.php';

chdir("c:\\Treeno\\tesseract");

$pidFile = "ocr3.pid";
if(file_exists($DEFS['TMP_DIR'].'/'.$pidFile)) {
	$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
	if (!isRunning ($pid, $DEFS)) {
		unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
	} else {
		die('ocr3 is already running');
	}
}
$fd = fopen ($DEFS['TMP_DIR'].'/'.$pidFile, 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);

error_log ('Started ' . $DEFS['DOC_DIR']."/bots/ocr3.php"); 


//$OCR_PATH = "/opt/Vividata/bin/xtrclilite";
$OCR_PATH = "c:\\Treeno\\tesseract\\tesseract.exe";
//$WORD_OCR_PATH = "c:\\Treeno\\apps\\bin\\antiword.exe";
$WORD_OCR_PATH = "c:\\Treeno\\apps\\bin\\antiword.exe";
//$PDF_OCR_PATH = "c:\\Treeno\\apps\\bin\\pdftotext.exe";
$PDF_OCR_PATH = "c:\\Treeno\\apps\\bin\\pdftotext.exe";

/*
if (isset ($DEFS['OCR_PATH'])) {
	$OCR_PATH = $DEFS['OCR_PATH'];
}
*/
if (!file_exists ($OCR_PATH)) {
	error_log("Missing ocr engine");
	$OCR_PATH = "";
}
 
/*
if (isSet ($DEFS['ANTIWORD_EXE'])) {
	$WORD_OCR_PATH = $DEFS['ANTIWORD_EXE'];
}
*/

if (!file_exists ($WORD_OCR_PATH)) {
	error_log("Missing antiword for ocr");
	$WORD_OCR_PATH = "";
}
/*
if( isSet($DEFS['PDFTOTEXT_EXE']) ) {
	$PDF_OCR_PATH = $DEFS['PDFTOTEXT_EXE'];
}
*/
if( !isSet($DEFS['OCRPDF']) OR $DEFS['OCRPDF'] != 1 
	OR !file_exists($PDF_OCR_PATH) ) {
	error_log("Missing pdftotext for ocr");
	$PDF_OCR_PATH = "";
}


$db_doc = getDbObject('docutron');
$wArr = array();
if(isSet($argv[1])) {
	$i = 1;
	$depArr = array();
	while(isSet($argv[$i])) {
		$depArr[] = "'".$argv[$i]."'";
		$i++;
	}
	$wArr = array('department IN('.implode(",",$depArr).')');
}
$oArr = array('id' => 'DESC');
lockTables( $db_doc, array('ocr_queue') );
$qList = getTableInfo($db_doc,'ocr_queue',array(),$wArr,'queryAll',$oArr,1000);
foreach( $qList as $row ){
	$del = 'delete from ocr_queue where id = '.$row['id'];
	$db_doc->query( $del );
}
unlockTables( $db_doc );
$dbArr = array();
foreach( $qList as $qInfo  ) {
	$fullPath = $DEFS['DATA_DIR']."/".$qInfo['location'];
//echo "$fullPath\n";
	if( isset( $dbArr[$qInfo['department']] ) ){
		$db_dept = $dbArr[$qInfo['department']];
	}else{
		$db_dept = getDbObject($qInfo['department']);
		$dbArr[$qInfo['department']] = $db_dept;
	}


	if(ocrFile($db_dept,$qInfo['department'],$qInfo['cabinet'],$qInfo['file_id'],$fullPath,$DEFS)) {
		$wArr = array('id' => (int)$qInfo['id']);
		deleteTableInfo($db_doc,'ocr_queue',$wArr);
	}
}
$db_doc->disconnect();

function ocrFile($db_dept,$dep,$cab,$fileID,$fpath,$DEFS) {
	global $OCR_PATH;
	global $WORD_OCR_PATH;
	global $PDF_OCR_PATH;

	if(is_file($fpath)) {
		$cmd = "";
		$fname = basename($fpath);
		if(!is_dir($DEFS['TMP_DIR']."/".$dep)) {
			mkdir($DEFS['TMP_DIR']."/".$dep);
		}
		$ocrFile = $DEFS['TMP_DIR']."/$dep/$fname.txt";
		echo "$ocrFile\n";
		$ext = strtolower(getExtension($fname));
//		echo "$ext\n";
//		echo "$OCR_PATH\n";
		if(($ext == "tiff" || $ext == "tif") && $OCR_PATH) {
//			echo "hehre\n";
//			$cmd = "$OCR_PATH ".escapeshellarg($fpath)." ".escapeshellarg($ocrFile);
			$fpath = str_replace("/","\\",$fpath);
			$ocrFile = str_replace("/","\\",$ocrFile);
			$cmd = "$OCR_PATH ".$fpath." ".$ocrFile;
//			echo "command: $cmd";
		} else if($ext == "pdf" && $PDF_OCR_PATH) {
			if(!is_dir($DEFS['TMP_DIR']."/".$dep."/pdfocr")) {
				mkdir($DEFS['TMP_DIR']."/".$dep."/pdfocr");
			}
			$tmppdfocr=$DEFS['TMP_DIR']."/".$dep."/pdfocr";
			//empty directory
	    $dh = @opendir($tmppdfocr);
	    while (false !== ($obj = readdir($dh))) {
	        if($obj=='.' || $obj=='..') continue;
	        @unlink($tmppdfocr.'/'.$obj);
	    }
	    closedir($dh);
			chdir($tmppdfocr);
			
			//pdftoppm -mono -r 300 berg.pdf 1 - berg.pdf is variable filename
			$cmd="pdftoppm -mono -r 300 ".escapeshellarg($fpath)." 1";
			shell_exec($cmd);

			//pnmtotiff -g4 *3.pbm > out3.tif - read list of *.pbm and iterate through all
	    $dh = @opendir($tmppdfocr);
	    while (false !== ($obj = readdir($dh))) {
        if(substr($obj, -4)=='.pbm') {
        		$pbmfile = basename($obj, ".pbm"); 
					$cmd=$DEFS['PNMTOTIFF_EXE']." -g4 ".$pbmfile.".pbm > ".$pbmfile.".tif";
					shell_exec($cmd);
        }
	    }
			shell_exec($cmd);
			//tiffcp *.tif out.tif - combines all the tiffs into one give tiff
			$cmd=$DEFS['TIFFCP_EXE']." *.tif out.tif";
			shell_exec($cmd);
			chdir("c:\\Treeno\\tesseract");
			$fpath=$tmppdfocr."/out.tif";
			$fpath = str_replace("/","\\",$fpath);
			$ocrFile = str_replace("/","\\",$ocrFile);
			$cmd = "$OCR_PATH ".$fpath." ".$ocrFile;
			error_log($cmd);
		} else if($ext == "doc" && $WORD_OCR_PATH) {
			$cmd = "$WORD_OCR_PATH ".escapeshellarg($fpath)." > ".escapeshellarg($ocrFile);
		} else if($ext == "txt") {
			if(is_file($fpath)) {
				$fp = fopen($fpath,'r');
				$ocrContext = stream_get_contents($fp);
				fclose($fp);

				return setOcrContext($db_dept,$cab."_files",$fileID,NULL,$ocrContext);
			} else {
				error_log('file does not exist: '.$fpath);
				return false;
			}	
		} else {
			return false;
		}
/*
		if($ext != "doc" && $ext != "txt") {
			if(filesize($fpath) > 51200) {
				$maxWait = 100;
				if(isSet($DEFS['OCR_MAX_WAIT'])) {
					$maxWait = $DEFS['OCR_MAX_WAIT'];
				}
				$wait = $maxWait;
			} else {
				$minWait = 2;
				if(isSet($DEFS['OCR_MAX_WAIT'])) {
					$minWait = $DEFS['OCR_MIN_WAIT'];
				}
				$wait = $minWait;
			}

		$desc = array(
			0=> array( 'pipe', 'r' ), //stdin
			1=> array( 'pipe', 'w' ), //stdout
			2=> array( 'pipe' ,'w' ) //stderr
		);
		//$cmd = escapeshellarg($cmd);
		$proc = proc_open($cmd, $desc, $pipes);//, $cwd, $env );
			$isRunning = true;
			$ct = 0;
			while($isRunning) {
				$stats = proc_get_status( $proc );
				if(!$stats['running']) {
					$isRunning = false;
				} else {
					if($ct > $wait) {
						foreach($pipes as $pipe) {
							fclose($pipe);
						}
						proc_close($proc);
						shell_exec("killall xtrclilite");
						if(is_file($ocrFile)) { 
							return setOcrContext($db_dept,$cab."_files",$fileID,$ocrFile);
						} else {
							return setOcrContext($db_dept,$cab."_files",$fileID,NULL,'-$-');
						}
					} else {
						sleep(1);	
						$ct++;
					} 
				}
			}
			foreach($pipes as $pipe) {
				fclose($pipe);
			}
			proc_close($proc);
			shell_exec("killall xtrclilite");
		} else {
 */
//echo "\nshell exec\n";
		shell_exec($cmd);
//		}

		$ocrFile = $ocrFile.".txt";
		if(is_file($ocrFile)) { 
			return setOcrContext($db_dept,$cab."_files",$fileID,$ocrFile);
		} else {
			return setOcrContext($db_dept,$cab."_files",$fileID,NULL,'-$-');
		}
	} else {
		return true;
	}
}

function setOcrContext($db_dept, $cabFilesTable, $id, $ocrFile,$ocrContext=NULL) {
	$output = "";
	if( file_exists($ocrFile) ) {
		$output = file_get_contents($ocrFile);
		unlink($ocrFile);
		if(!$output) {
			$output = "-$-";
		}
	} else if(!is_null($ocrContext)) {
		$output = $ocrContext;
	} else {
		$output = "-$-";
	}
	$output = returnKeyboardCharsOnly( $output );
	$output = str_replace("\\"," ",$output);
	$output = str_replace("?"," ",$output);
			
	while(!(strpos($output,"  ")===FALSE)) {
		$output=str_replace("  "," ",$output);
	}
	$output = addslashes(strtolower($output));

	if($output) {
		$uArr = array('ocr_context'=>$output);
		$wArr = array('id'=>(int)$id);
		updateTableInfo($db_dept,$cabFilesTable,$uArr,$wArr);
		return true;
	}
	return false;
}
?>
