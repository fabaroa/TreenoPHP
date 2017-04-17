<?php
if( !isset( $argv[1] ) ){
	error_log( "unauthorized running of ecabConversion.php\n" );
	die();
}
if( $argv[1] != 'ecabinet' ){
	error_log( "unauthorized running of ecabConversion.php\n" );
	die();
}
include_once '../db/db_common.php';
include_once '../db/db_engine.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../lib/tables.php';
include_once '../lib/cabinets.php';
include_once '../lib/settings.php';
include_once '../lib/mime.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/random.php';
include_once '../classuser.inc';

$fd = fopen ($DEFS['TMP_DIR'].'/eCabConversion.pid', 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);
class eCabinet {
	var $dirArr;
	var $xmlOmitArr;
	var $cabArr;
	var $cabIDArr;
	var $cabStruct;
	var $db_doc;
	var $db_dept;
	var $dep;
	var $depID;
	var $gblStt;
	var $scanPath;

	function eCabinet() {
		global $DEFS;

		$this->dep = "client_files";
		$this->depID = 0;
		$this->db_doc = getDbObject('docutron');
		$this->db_dept = getDbObject($this->dep);
		$this->gblStt = new Gblstt($this->dep,$this->db_doc);
		$this->scanPath = $DEFS['DATA_DIR']."/Scan";
		$this->dirArr = array();

		$this->setCabArr();
		$this->setCabIDArr();
		$this->setCabStructure();
		$this->setXMLOmitArr();
		$this->parseDirTree("/opt/data/data");
		$this->processDir();
	}

	function parseDirTree($path) {
		$fArr = scandir($path);
		foreach($fArr AS $file) {
			if($file != "." && $file != "..") {
				if(is_dir($path."/".$file)) {
					$this->parseDirTree($path."/".$file);
				} elseif(is_file($path."/".$file)) {
					//$this->processDir($path);
					$this->dirArr[] = $path;
					break;
				}
			}
		}
	}

	function processDir() {
		global $DEFS;
		foreach($this->dirArr AS $dir) {
			if(is_file($dir."/.metadata")) {
				$cab = "";
				$xmlStr = trim($this->formatXML($dir));
				$domDoc = new DOMDocument ();
				if(!$domDoc->loadXML ($xmlStr)) {
					echo "error reading XML";
					die();
				}

				$folderArr = array();
				$domArr = $domDoc->getElementsByTagName('item');
				for($i=0;$i<$domArr->length;$i++) {
					$item = $domArr->item($i)->getAttribute('name');	
					if(!in_array($item,$this->xmlOmitArr)) {
						$value = $domArr->item($i)->getAttribute('value');
						$folderArr[str_replace(" ","_",$item)] = $value; 
						if($item == "capture date") {
							$value = substr($value,0,4)."-".substr($value,4,2)."-".substr($value,6,2);	
						} else if($item == "folder name") {
							$vArr = explode("/",$value);
							$cab = str_replace(" ","_",$vArr[1]);
							$cab = str_replace("-","_",$vArr[1]);
							$cab = "Archive_".$cab;
						} else if($item == "file to download") { 
							$filename = $value;
							unset($folderArr['file_to_download']);
						}
					}
				}

				if( $cab == '' ){
					//path is blank
					$this->fileToInbox($dir."/".$filename);
					continue;
				}
				
				if(!isSet($folderArr['suggested_summary'])) {
					if(is_file($dir."/.bag")) {
						$fArr = file($dir."/.bag");
						foreach($fArr AS $line) {
							$sum = substr($line,0,8);
							if($sum == "summary:") {	
								$folderArr['suggested_summary'] = trim(substr($line,8));
								break;
							}
						}	
					}
				}

				$folderArr = $this->setIndiceOrder($folderArr);
				if(isSet($folderArr['capture_date'])) {
					$cDate = $folderArr['capture_date'];
					if($cDate) {
						$date = substr($cDate,0,4);
						$date .= "-".substr($cDate,4,2);
						$date .= "-".substr($cDate,6,2);

						$folderArr['capture_date'] = $date;
					}
				}

				$indValues = array_values($folderArr);
				$indNames = array_keys($folderArr);
				if(!array_key_exists($cab,$this->cabArr)) {
					$this->addCabinet($cab,$indNames);
				} else {
					$this->addCabinetIndices($cab,$indNames);
				}

				$tempTable = "";
				$doc_id = createFolderInCabinet($this->db_dept,$this->gblStt,$this->db_doc,'admin',$this->dep,$cab,$indValues,$indNames,$tempTable);

				if(is_file($dir."/".$filename)) { 
					if(is_file($dir."/contents.txt.gz")) {
						$gz = gzopen($dir."/contents.txt.gz","r");
						$contents = gzread($gz,100000000);		
						gzclose($gz);
					}

					$fname = str_replace(" ","_",$filename);
					$insertArr = array(
								'doc_id' => (int)$doc_id,
								'filename' => $fname,
								'parent_filename' => $filename,
								'ocr_context' => $contents
									);
					$res = $this->db_dept->extended->autoExecute($cab."_files",$insertArr);
					dbErr($res);

					$sArr = array('location');
					$wArr = array('doc_id' => (int)$doc_id);
					$location = getTableInfo($this->db_dept,$cab,$sArr,$wArr,'queryOne');
					$loc = $DEFS['DATA_DIR']."/".str_replace(" ","/",$location);
					allowWebWrite($loc, $DEFS);
					if(copy($dir."/".$filename,$loc."/".$filename)) {
						////create memory
						allowWebWrite($loc."/".$filename, $DEFS);
						delDir($dir);
					} else {
						echo "problems copying $filename to $batch\n";
					}	
						
				} else {
					echo "file doesn't exist: $dir/$filename\n";
				}
			}
		}
	}

	function fileToInbox($filename) {
		global $DEFS;

		$fArr = explode("/",$filename);
		$fArr = array_slice($fArr,5,5);
		$batch = $DEFS['DATA_DIR']."/".$this->dep."/personalInbox/admin/Archive-".implode("-",$fArr);
		$fname = basename($filename);

		if(is_file($filename)) {
			if(!is_dir($batch)) {
				mkdir($batch);
				allowWebWrite($batch,$DEFS);
			}

			if(copy($filename,$batch."/".$fname)) {
				allowWebWrite($batch."/".$fname, $DEFS);
				delDir(dirname($filename));
			} else {
				echo "problems copying $filename to $batch\n";
			}	
		} else {
			echo "file doesn't exist: $filename\n";
		}
	}

	function setIndiceOrder($indArr) {
		$folderArr = array('suggested_summary' => "", 'folder_name' => "");
		foreach($indArr AS $key => $value) {
			$folderArr[$key] = $value;
		}
		return $folderArr;
	}

	function addCabinet($cab,$indiceArr) {
		global $DEFS;
		if(!@mkdir($DEFS['DATA_DIR'].'/'.$this->dep.'/'.$cab)) {
			echo $DEFS['DATA_DIR'].'/'.$this->dep.'/'.$cab." already exists\n";
			die();
		}
		allowWebWrite($DEFS['DATA_DIR'].'/'.$this->dep.'/'.$cab, $DEFS);
		if(!@mkdir($DEFS['DATA_DIR'].'/'.$this->dep.'/indexing/'.$cab)) {
			echo $DEFS['DATA_DIR'].'/'.$this->dep.'/indexing/'.$cab." already exists\n";
			die();
		}
		allowWebWrite($DEFS['DATA_DIR'].'/'.$this->dep.'/indexing/'.$cab, $DEFS);

		$insertArr = array (
			'real_name'         => $cab,
			'departmentname'    => str_replace("_"," ",$cab),
		);
		$res = $this->db_dept->extended->autoExecute('departments', $insertArr);
		dbErr($res);

		$sArr = array('departmentid');
		$wArr = array('real_name' => $cab);
		$cabID = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'queryOne');

		$queryArr = array(
			'doc_id '.AUTOINC,
			'PRIMARY KEY (doc_id)',
			'location VARCHAR(100)',
			'deleted SMALLINT DEFAULT 0'
		);

		foreach($indiceArr as $index) {
			if($index == "suggested_summary") {
				$queryArr[] = "$index ".TEXT16M." NULL";
			} else {
				$queryArr[] = $index." VARCHAR(255) NULL";
			}
		}
		$query = "CREATE TABLE $cab (".implode(', ', $queryArr).')';
		$res = $this->db_dept->query($query);
		dbErr($res);
	
		createCabinet_files($this->db_dept,$cab,$cabID);
		createCabinet_Index_Files($this->db_dept,$cab);

		$updateArr = array('quota_used'=>'quota_used+8192');
		$whereArr = array('real_department'=> $this->dep);
		updateTableInfo($this->db_doc,'licenses',$updateArr,$whereArr,1);

		$res = getTableInfo($this->db_dept, 'departments',array(),array(),'query',array('departmentname'=>'ASC'));
		$cabArr = array ();
		while ($row = $res->fetchRow()) {
			$cabArr[$row['real_name']] = $row['departmentname'];
		}

		uasort($cabArr,'strnatcasecmp');
		$userlist = getTableInfo($this->db_dept,'access');
		$retBool = false;
		while ($row = $userlist->fetchRow()) {
			$sortedAccess = array();
			$access = unserialize(base64_decode($row['access']));
			$username = $row['username'];
			if( $username == 'admin' ) {
				$access[$cab] = 'rw';
			} else {
				$access[$cab] = 'none';
			}

			foreach( $cabArr as $c => $arb ) {
				$sortedAccess[$c] = $access[$c];
			}
			$retBool = true;
			updateTableInfo($this->db_dept, 'access',
				array('access' => base64_encode(serialize($sortedAccess))),
				array('username' => $username));
		}
		$this->cabArr[$cab] = str_replace("_"," ",$cab);
		$this->cabIDArr[$cab] = $cabID;
		$this->cabStruct[$cab] = $indiceArr;
	}

	function addCabinetIndices($cab,$indiceArr) {
		foreach($indiceArr AS $ind) {
			if(!in_array($ind,$this->cabStruct[$cab])) {
				if($ind == "suggested_summary") {
					$colDef = "$ind ".TEXT16M." NULL";
				} else {
					$colDef = "$ind VARCHAR(254) NULL";
				}
				alterTable($this->db_dept,$cab,'ADD',$colDef);
				$this->cabStruct[$cab][] = $ind;
			}
		}
	}

	function formatXML($dir) {
		$xmlStr = file_get_contents($dir."/.metadata");
		$xmlStr = str_replace("&","&amp;",$xmlStr);
		$f = explode("\n",$xmlStr);
		$f[0] = '<?xml version="1.0" standalone="yes"?>';
		unset($f[1]);
		unset($f[2]);
		unset($f[3]);
		unset($f[4]);
		unset($f[5]);
		unset($f[6]);
		unset($f[7]);
		unset($f[8]);

		return implode("\n",$f);
	}

	function setCabArr() {
		$sArr = array('real_name','departmentname');
		$wArr = array();
		$this->cabArr = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'getAssoc');
	}

	function setCabIDArr() {
		$sArr = array('real_name','departmentid');
		$wArr = array();
		$this->cabIDArr = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'getAssoc');
	}

	function setCabStructure() {
		foreach($this->cabArr AS $real => $arb) {
			$this->cabStruct[$real] = getCabinetInfo($this->db_dept,$real);
		}
	}

	function setXMLOmitArr() {
		$this->xmlOmitArr = array(
			'capture device name',
			'capture device type',
			'creation date',
			'document file size',
			'file for thumbnail',
			'file to index',
			'height of document',
			'last read on',
			'location',
			'names sanitized',
			'pages in document',
			'pages ocred in document',
			'process state',
			'processed by compress daemon',
			'processed by backup daemon',
			'processed by gw convert daemon',
			'processed by index daemon',
			'processed by input daemon',
			'processed by ocr daemon',
			'processed by rules daemon',
			'processed by thumb daemon',
			'public',
			'reserved serial number',
			'serial number',
			'time document sent to backup',
			'time document started to be processed',
			'time to compress',
			'time to convert to tiff',
			'time to index',
			'time to input',
			'time to ocr',
			'time to process',
			'time to thumbnail',
			'total elapsed time',
			'total processing time',
			'width of document'	
		);
	}
};

$eCabObj = new eCabinet();
?>
