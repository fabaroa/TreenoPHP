<?php
/**
 * setupIndexingBot2
 * 
 * In each department and each cabinet indexing directory, put every new batch
 * into indexing table. DO NOT RUN MANUALLY
 * 
 * @package DMS
 * @subpackage setupIndexingBot2
 */
/**
 * Report all errors
 */
error_reporting(E_ALL);

chdir(dirname(__FILE__));

/**
 * DEFS
 */
require_once '../lib/settings.php';

/**
 * getTableInfo()
 */
require_once '../lib/utility.php';

/**
 * PEAR::DB
 */
require_once '../db/db_common.php';
require_once '../lib/mime.php';
require_once '../settings/settings.php';
require_once '../lib/cabinets.php';
require_once '../lib/indexing.inc.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/random.php';
require_once '../lib/synchronizeBots.php';
require_once '../lib/licenseFuncs.php';

$db_doc = getDbObject('docutron');
if(!isValidLicense($db_doc)) {
	error_log("INVALID LICENSE attempted to start ".$DEFS['DOC_DIR']."/bots/setupIndexingBot2.php");
	die();
}

$fd = fopen ($DEFS['TMP_DIR'].'/setupIndexingBot2.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);

$baseMemUsage = getMemUsage($DEFS); 
$memUsage = $baseMemUsage;

$setupIndexing = new setupIndexing($DEFS, $db_doc);
$i = 0;

while($memUsage < 10 * $baseMemUsage) {
	foreach($setupIndexing->getDepartments() as $myDept) {
		echo $myDept."\n";
		$cabIndexing =& $setupIndexing->getCabinets($myDept);
		$i = 0;
		for($i = 0; $i < count($cabIndexing); $i++) {
			$cabIndexing[$i]->setupIndexing();
		}
	}
	sleep(5);
	$i++;
	if ($i == 100) {
		$memUsage = getMemUsage($DEFS);
		$i = 0;
	}
}

/**
 * Driver for setupIndexing2
 * 
 * @package setupIndexing
 */
class setupIndexing {
	/**
	 * DMS.DEFS
	 * @var array string
	 */
	var $DEFS;
	
	/**
	 * PEAR::DB
	 * @var object PEAR::DB
	 */
	var $db_doc;
	
	/**
	 * list of departments
	 * @var array string
	 */
	var $departments;
	
	/**
	 * array of cabinetIndexing objects
	 * @var array object cabinetIndexing
	 */
	var $cabinets;
	
	/**
	 * unix timestamp of last DB update
	 */
	var $last;

	/**
	 * array of db objects
	 */
	var $dbArr;

	/**
	 * Constructor
	 * @param array DMS.DEFS variable
	 */
	function setupIndexing(&$DEFS, &$db_doc) {
		$this->DEFS =& $DEFS;
		$this->last = 0;
		$this->db_doc = $db_doc;
			
	}
	
	/**
	 * Return department list, refreshing from DB if necessary.
	 * @return array string
	 */
	function getDepartments() {
		$time = time();
		if($time - $this->last > 600) {
			$this->last = $time;
			$this->departments = getTableInfo($this->db_doc, 'licenses',
				array('real_department'), array(), 'queryCol');
			$this->cabinets = array ();
			foreach($this->departments as $myDept) {
				echo "Get Depts:".$myDept."\n";
				if( isset( $dbArr[$myDept] ) ){
					$db = $dbArr[$myDept];
				}else{
					$db = getDbObject($myDept);
					$dbArr[$myDept] = $db;
				}
				$this->cabinets[$myDept] = array ();
				$cabinets = getTableInfo($db, 'departments', array('real_name'),
					array('deleted' => 0), 'queryCol');
					
				foreach($cabinets as $myCab) {
					$this->cabinets[$myDept][] = new cabinetIndexing($db,
						$myDept, $myCab, $this->DEFS, $this->db_doc);
						
				}
				$db->disconnect();
			}
		}
		return $this->departments;
	}
	
	/**
	 * return cabinetIndexing objects for current department
	 * @param string $department
	 * return object cabinetIndexing 
	 */
	function &getCabinets($department) {
		return $this->cabinets[$department];
	}
}

/**
 * cabinetIndexing
 * 
 * @package cabinetIndexing
 */
class cabinetIndexing {
	var $department;
	var $cabinet;
	var $DEFS;
	var $db;
	var $indexingFolders;
	var $allIndexing;
	var $db_doc;
	var $fileintoexisting;
	
	/**
	 * Constructor
	 * @param object $db PEAR::DB department DB object
	 * @param string $department
	 * @param string $cabinet
	 * @param array $DEFS
	 */
	function cabinetIndexing($db, $department, $cabinet, $DEFS, &$db_doc) {
		$this->cabinet = $cabinet;
		$this->DEFS = $DEFS;
		$this->db = $db;
		$this->db_doc = $db_doc;
		$this->department = $department;
		$this->allIndexing = getTableInfo($this->db,
			$this->cabinet.'_indexing_table', array('folder'), array(),
			'queryCol');
		$myStt = new GblStt($this->department, $db_doc);
		$this->mystt = $myStt;
		$this->fileintoexisting=$this->mystt->get('file_into_existing');
	}
	
	/**
	 * Setup indexing on each batch if new batch is found.
	 */
	function setupIndexing() {
		$basePath = $this->DEFS['DATA_DIR'].'/'.$this->department;
		$basePath .= '/indexing/';
		$basePath .= $this->cabinet;

		$dh = safeOpenDir ($basePath);
		$date = date('Y-m-d H:i:s');

		$myDirs = array ();
		$myEntry = readdir($dh);
		while($myEntry !== false) {
			if($myEntry !== '.' and $myEntry !== '..') {
				$myDirs[] = $basePath.'/'.$myEntry;
			}
			$myEntry = readdir($dh);
		}
		closedir($dh);
		$myDirs =& Indexing::orderByTime($myDirs);
		foreach ($myDirs as $myEntry) {
			if(is_dir($myEntry)) {
					
				if(file_exists($myEntry.'/INDEX.AC')) {
					$barcodeArr = explode(';', trim(
						file_get_contents($myEntry.'/INDEX.AC')));
						
					$barcodeStr = str_replace('"', '',
						$barcodeArr[count($barcodeArr) - 1]);
					
					$this->indexAutoComplete($myEntry,
						$barcodeStr);
					
				} elseif(file_exists($myEntry.'/INDEX.AUTO')) {
					$this->indexSingleAuto ($myEntry);
				} elseif(file_exists($myEntry.'/INDEX.MAUTO')) {
					$this->indexMultiAuto ($myEntry);
					if(file_exists ($myEntry.'.DAT')) {
						unlink($myEntry.'.DAT');
					}
				} elseif(!file_exists($myEntry.'/INDEX.TMP')) {
					if(!in_array(basename($myEntry), $this->allIndexing)) {
						clearstatcache();
						$dirStats = stat($myEntry);
						if( strcmp(basename($myEntry), trim(basename($myEntry))) != 0 ) {
							$newEntry = dirname($myEntry)."/".trim(basename($myEntry));
							$newEntry = Indexing::makeUnique( $newEntry );
							rename($myEntry, $newEntry);
							$myEntry = $newEntry;
						}
						if($dirStats['mtime'] < (time() - 10)) {
							$fileArr = array ();
							$dh2 = safeOpenDir ($myEntry);
							$myEntry2 = readdir($dh2);
							while($myEntry2 !== false) {
								if($myEntry2 !== '.' and $myEntry2 !== '..') {
									$fileArr[] = $myEntry2;
								}
								$myEntry2 = readdir($dh2);
							}
							closedir($dh2);
							if($fileArr) {
								$tblPath = $this->department.' indexing ';
								$tblPath .= $this->cabinet.' ' .
									basename($myEntry);
								$tblFinalPath = $this->department.' ';
								$tblFinalPath .= $this->cabinet.' ' .
									basename($myEntry);
								$queryArr = array(
									'path'			=> $tblPath,
									'final_path'	=> $tblFinalPath,
									'folder'		=> basename ($myEntry),
									'finished'		=> 0,
									'flag'			=> 0,
									'total'			=> 1,
							  		'date'			=> $date
							  	);
							  	$res = $this->db->extended->autoExecute(
							  		$this->cabinet.'_indexing_table', $queryArr
							  	);
							  	
							  	dbErr($res);
							  	$this->allIndexing[] = basename ($myEntry);
							} else {
								@rmdir($myEntry);
							}
						}
					}
				}
			} elseif(is_file($myEntry)) {
				if (getExtension(basename($myEntry)) == 'DAT') {
					unlink($myEntry);
				} else {
					$tmpDir = getUniqueDirectory ($basePath.'/');
					rename ($myEntry, $tmpDir.'/'.basename($myEntry));
				}
			}
		}
	}
	
	function indexAutoComplete($batchLoc, $value) {
		$mySett = $this->mystt;
		$binding = $mySett->get('indexing_'.$this->cabinet);

		$fields = getCabinetInfo($this->db, $this->cabinet);
		if($binding) {
			//Has auto complete set up in one form or another
			if($binding == 'odbc_auto_complete') {
				//External odbc database
				$odbcInfo = getTableInfo($this->db, 'odbc_auto_complete', array(), 
					array('cabinet_name' => $this->cabinet), 'queryRow');
				$uniqueField = $odbcInfo['lookup_field'];
				$db_odbc = getODBCDbObject($odbcInfo['connect_id'],
					$this->db_doc);
				$connectInfo = array (
					'type'		=> 'odbc',
					'db'		=> $db_odbc,
					'db_dept'	=> $this->db,
					'cabinet'	=> $this->cabinet
				);
			} elseif ($binding == 'sagitta_ws_auto_complete') {
				$connectInfo = array (
					'type'		=> 'sagitta_ws',
					'cabinet'	=> $this->cabinet
				);
			} else {
				$uniqueField = $fields[0];
				
				//local auto complete table
				$connectInfo = array (
					'type'	=> 'local',
					'db'	=> $this->db,
					'table'	=> $binding,
					'field'	=> $uniqueField
				);
			}
			$whereArr = array ($uniqueField => $value, 'deleted' => 0);
			$docID = getTableInfo($this->db, $this->cabinet, array('doc_id'),
				$whereArr, 'queryOne');
			
			if(!$docID) {
				$acRow = getAutoCompleteRow($connectInfo, $value, $this->department, $mySett);
				$safeKeys = array();
				$safeValues = array();
				foreach($fields as $myField) {
					$safeKeys[] = $myField;
					if(isset($acRow[$myField])) {
						$safeValues[] = $acRow[$myField];
					} else {
						$safeValues[] = '';
					}
				}
				$temp_table = '';
				$docID = createFolderInCabinet($this->db, $mySett, $this->db_doc, 'admin',
					$this->department, $this->cabinet, $safeValues, $safeKeys,$temp_table); 
			}
			$this->routeToScan($batchLoc, $docID);
		}
	}

	function indexSingleAuto($batchLoc) {
		$indices = getCabinetInfo($this->db, $this->cabinet);
		$fieldArr = array ();
		$barcodeArr = explode(';', trim (file_get_contents 
					($batchLoc.'/INDEX.AUTO')));
		$k = count ($barcodeArr) - 1;
		for ($j = count($indices) - 1; $j >= 0; $j--) {
			$fieldArr[$indices[$j]] = trim (str_replace ('"', '',
					$barcodeArr[$k]));
			$k--;
		}
		$fieldArr = array_reverse ($fieldArr, true);
		$this->indexAuto ($batchLoc, $fieldArr);

	}

	function indexAuto($batchLoc, $fieldVals) {
 		$mySett = new GblStt ($this->department, $this->db_doc);
		$docID=false;
		if( $this->fileintoexisting==1 ){
			$docID = checkFolderExists($this->department, $this->cabinet, $fieldVals, $this->db_doc, $this->db);
		}
		$keys = array_keys($fieldVals);
		$vals = array_values($fieldVals);
		if(!$docID) {
			$temp_table = '';
 			$docID = createFolderInCabinet($this->db, $mySett, $this->db_doc,
 					'admin', $this->department, $this->cabinet, $vals, $keys, $temp_table);
		}
		$this->routeToScan($batchLoc, $docID);
	}

	function routeToScan($batchLoc, $docID) {
 		$barcode = Barcode::createBarcode($this->db, $this->db_doc, 'admin',
  			$this->department, $this->cabinet, $docID);
		$destDir = $this->DEFS['DATA_DIR'].'/Scan/'.basename($batchLoc);
		$destDir = Indexing::makeUnique($destDir);
		touch($batchLoc.'/.lock');
		$fd = fopen($batchLoc.'/INDEX.DAT', 'w+');
		fwrite($fd, $barcode);
		fclose($fd);
		rename($batchLoc, $destDir);
		if(file_exists($destDir.'/INDEX.AC')) {
			unlink($destDir.'/INDEX.AC');
		}
		if(file_exists($destDir.'/INDEX.AUTO')) {
			unlink($destDir.'/INDEX.AUTO');
		}
		if(file_exists($destDir.'/.lock'))
			unlink($destDir.'/.lock');
	}

	function indexMultiAuto($batchLoc) {
		$indices = getCabinetInfo($this->db, $this->cabinet);
		$fileArr = file($batchLoc.'/INDEX.MAUTO');
		$dirs = array ();
		$dh = safeOpenDir($batchLoc);
		$mdatDir = readdir($dh);
		while($mdatDir !== false) {
			if (is_dir($batchLoc.'/'.$mdatDir) and $mdatDir != '.' and
					$mdatDir != '..') {
				$dirs[] = $batchLoc.'/'.$mdatDir;
			}
			$mdatDir = readdir($dh);
		}
		closedir($dh);
		usort($dirs, 'strnatcasecmp');
		for ($i = 0; $i < count($fileArr); $i++) {
			$fieldArr = array ();
			$barcodeArr = explode(';', trim($fileArr[$i]));
			$k = count ($barcodeArr) - 1;
			for ($j = count($indices) - 1; $j >= 0; $j--) {
				$fieldArr[$indices[$j]] = trim (str_replace ('"', '',
						$barcodeArr[$k]));
				$k--;
			}
			$fieldArr = array_reverse ($fieldArr, true);

			$this->indexAuto ($dirs[$i], $fieldArr);
		}
		unlink($batchLoc.'/INDEX.MAUTO');
	}
}
?>
