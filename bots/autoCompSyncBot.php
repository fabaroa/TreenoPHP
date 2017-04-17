<?php
/*
 * autoCompSyncBot.php - The Auto-Complete Synchronization Bot
 * May 12, 2004
 * 
 * This bot is designed to run from crontab or some other similar scheduler.
 * 
 * It would be overkill to run this more than once a day.
 * 
 * It will update the fields in docutron database with the updated values from
 * the auto complete connection, be it an external ODBC database or a native
 * auto complete table. 
 */
chdir (dirname (__FILE__));

require_once '../lib/utility.php';
require_once '../lib/odbc.php';
require_once '../lib/indexing2.php';
require_once '../settings/settings.php';
require_once '../lib/settings.php';
require_once '../lib/synchronizeBots.php';
require_once '../db/db_common.php';

require_once 'MDB2.php';

$pidFile = "autoCompSyncBot.pid";
if(file_exists($DEFS['TMP_DIR'].'/'.$pidFile)) {
	$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
	if (!isRunning ($pid, $DEFS)) {
		unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
	} else {
		die('autoCompSyncBot is already running');
	}
}
$fd = fopen ($DEFS['TMP_DIR'].'/autoCompSyncBot.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);

error_log ('Started ' . $DEFS['DOC_DIR']."/bots/autoCompSyncBot.php"); 

$maxEntries = 1000;
$db_doc = getDbObject('docutron');

$dbInfo = getLicensesInfo($db_doc);

$sArr = array('real_department');
$depList = getTableInfo($db_doc,'licenses',$sArr,array(),'queryCol',array('id' => 'ASC'));

if(is_file($DEFS['DATA_DIR']."/autocomp.mem")) {
	$endData = file_get_contents($DEFS['DATA_DIR']."/autocomp.mem");
	$endInfo = explode("\t",$endData);
}

//EACH DEPARTMENT
foreach($depList AS $dep) {
	if(isSet($endInfo) && $dep != $endInfo[0]) {
		continue;
	}
	if( file_exists($DEFS['DATA_DIR']."/".$dep."/.skip.auto.comp" ) ){
		continue;
	}
	$mySett = new GblStt($dep, $db_doc);
	$db_dept = getDbObject($dep);
	$allCabinets = getTableInfo($db_dept, 'departments',
		array('real_name'), array('deleted' => 0), 'queryCol',array('departmentid' => 'ASC'));

	//EACH CABINET
	foreach ($allCabinets as $cabinet) {
		if(isSet($endInfo) && $cabinet != $endInfo[1]) {
			continue;
		}
		$binding = $mySett->get('indexing_'.$cabinet);
		
		if($binding) {
			//Has auto complete set up in one form or another
			$fields = getCabinetInfo($db_dept, $cabinet);
			$uniqueField = $fields[0];
			
			if($binding == 'odbc_auto_complete') {
				//External odbc database
				$odbcInfo = getTableInfo($db_dept, 'odbc_auto_complete', array(), 
					array('cabinet_name' => $cabinet), 'queryRow');
				$db_odbc = getODBCDbObject($odbcInfo['connect_id'],
					$db_doc);
				$connectInfo = array (
					'type'		=> 'odbc',
					'db'		=> $db_odbc,
					'db_dept'	=> $db_dept,
					'cabinet'	=> $cabinet
				);
				$myLookup = $odbcInfo['lookup_field'];
				if (substr_count ($myLookup, ',') > 0) {
					$uniqueField = explode (',', $myLookup);
				}
			} elseif($binding == 'sagitta_ws_auto_complete') {
				$connectInfo = array (
					'type'		=> 'sagitta_ws',
					'cabinet'	=> $cabinet
				);
			} else {
				//local auto complete table
				$connectInfo = array (
					'type'	=> 'local',
					'db'	=> $db_dept,
					'table'	=> $binding,
					'field'	=> $uniqueField
				);
			}

			$sArr = array('MAX(doc_id)');
			$wArr = array('deleted' => 0);
			$maxDocID = getTableInfo($db_dept,$cabinet,$sArr,$wArr,'queryOne');

			$curDocID = 0;
			if(isSet($endInfo)) {
				$curDocID = $endInfo[2];
				unset($endInfo);
			}	

			while($curDocID < $maxDocID) { 
				$db_doc->disconnect();
				$db_dept->disconnect();

				$db_doc = getDbObject('docutron');	
				$db_dept = getDbObject($dep);	
				if($binding == "odbc_auto_complete") {
					if($odbcInfo['connect_id']) {
						$db_odbc->disconnect();
						$odbcInfo = getTableInfo($db_dept, 'odbc_auto_complete', array(), 
							array('cabinet_name' => $cabinet), 'queryRow');
						$db_odbc = getODBCDbObject($odbcInfo['connect_id'],$db_doc);
						$connectInfo['db'] = $db_odbc;
					}
					$connectInfo['db_dept'] = $db_dept;
				}

				$sArr = array();
				$wArr = array('deleted = 0','doc_id >= '.$curDocID);
				$oArr = array('doc_id' => 'ASC');

				$folderInfo = getTableInfo($db_dept,$cabinet, $sArr,$wArr,'queryAll',$oArr,0,$maxEntries);
				//EACH FOLDER IN CABINET
				foreach($folderInfo AS $folderRow) {
					if(isFolderEmpty($folderRow,$uniqueField)) {
						$updatedRow = array ();
						if (in_array('date_indexed',array_keys ($folderRow))) {
							if(empty($folderRow['date_indexed'])) {
								list($firstFile) = getFilePathsFromDocID(
									$db_dept, 
									$cabinet, 
									$DEFS['DATA_DIR'], 
									$folderRow['doc_id']
								);
								$dateIndexed = '';
								if($firstFile and file_exists($firstFile)) {
									$fileInfo = stat($firstFile);
									$dateIndexed = strftime("%Y-%m-%d", $fileInfo['ctime']);
									$updatedRow['date_indexed'] = $dateIndexed;
								}
							}
						}
						if (is_array ($uniqueField)) {
							$searchVal = array ();
							foreach ($uniqueField as $myField) {
								if (isset ($folderRow[$myField])) {
									$searchVal[$myField] = $folderRow[$myField];
								} else {
									$searchVal[$myField] = '';
								}
							}
						} else {
							if (isset ($folderRow[$uniqueField])) {
								$searchVal = $folderRow[$uniqueField];
							} else {
								$searchVal = '';
							}
						}
						if($searchVal) {	
							$acRow = getAutoCompleteRow($connectInfo,
								$searchVal, $dep, $mySett);
							reset($acRow);
							while(list($key, $val) = each($acRow)) {
								$acRow[$key] = trim(substr($val, 0, 99));
							}
							reset($acRow);
							
							$mustUpdate = false;
							
							//Loop through each key from auto complete table, and compare
							//values to the values in the cabinet table
							foreach($acRow as $key => $value) {
								if(array_key_exists ($key, $folderRow) and $folderRow[$key] != $value) {
									//They Differ!!
									$mustUpdate = true;
									break;
								}
							}
							if($mustUpdate) {
								$updatedRow = array_merge($updatedRow, $acRow);
							}
							if($updatedRow) {
								//Update row in cabinet table
								if(!MDB2::isConnection($db_dept)) {
									$db_dept = getDbObject($dep);
								}

								$res = $db_dept->extended->autoExecute(
									$cabinet,
									$updatedRow,
									MDB2_AUTOQUERY_UPDATE,
									'doc_id = '.$folderRow['doc_id']
								);
								dbErr($res);
							}
						}
					} else {
						$fp = fopen('/tmp/help','w+');
						fwrite($fp,$dep."\t".$cabinet."\t".$folderRow['doc_id']."\n");
					}
					$indexStr = $dep."\t".$cabinet."\t".$folderRow['doc_id'];
					file_put_contents($DEFS['DATA_DIR']."/autocomp.mem",$indexStr);
					$curDocID = $folderRow['doc_id'];
				}
			}
		}
	}
}
unlink($DEFS['DATA_DIR']."/autocomp.mem");
$db_doc->disconnect();

function isFolderEmpty($row,$uField) {
	global $DEFS;
	if(isSet($DEFS['SYNC_EMPTY_FOLDERS']) && $DEFS['SYNC_EMPTY_FOLDERS'] == 1) {
		unset($row['doc_id']);
		unset($row['location']);
		unset($row['deleted']);
		if(is_array($uField)) {
			foreach($uField AS $f) {
				unset($row[$f]);
			}
		} else {
			unset($row[$uField]);
		}
		$emptyCt = 0;
		foreach($row AS $v) {
			if(!trim($v)) {
				$emptyCt++;
			}
		}
		if(round(($emptyCt/count($row))*100) >= 50) {
			return true;
		} else {
			return false;
		}
	}
	return true;
}
?>
