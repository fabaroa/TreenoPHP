<?php
require_once '../lib/versioning.php';
require_once '../lib/redaction.php';
require_once '../centera/centera.php';
require_once '../modules/modules.php';
require_once '../lib/textRedaction.php';

class parseRedact
{
	var $pageWidth;
	var $pageHeight;
	var $nodes;
	var $inNode;
	var $inDocProps;
	var $myData;
	var $currNode;
	var $parser;
	var $docID;
	var $calledDocID;
	var $fileName;
	var $parentFileName;
	var $subfolder;
	var $cabinet;
	var $user;

	function parseRedact() {
		$this->nodes = array();
		$this->inNode = false;
		$this->inDocProps = false;
		$this->myData = '';
		$this->currNode = 0;
		$this->subfolder = '';
	}
	function _createParser() {
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "startElement", "endElement");
		xml_set_character_data_handler($this->parser, "characterData");
	}
	function parse($data) {
		$this->_createParser();
		xml_parse($this->parser, preg_replace("%[\r]+%","", $data));
		xml_parser_free($this->parser);
	}
	function startElement($parser, $name, $attrs) {
		switch($name) {
		case 'PROPERTIES':
			$this->inDocProps = true;
			break;
		case 'NODE':
			$this->inNode = true;
			break;
		}
	}

	function insertIntoTable($db, $user, $data, $db_name, $db_doc) {
		$pNameArray = explode('\.', $this->fileName);
//		$myExt = $pNameArray[1];
		$myExt = end($pNameArray);
		$queryArr = array(
			'cabinet'	=> $this->cabinet,
			'doc_id'	=> (int) $this->docID,
			'filename'	=> $this->fileName,
			'subfolder'	=> $this->subfolder,
			'xml_data'	=> $data
		);
		
		$auditStr = 'Created redaction for cabinet: '.$user->cabArr[$this->cabinet].'.';
		$auditStr .= " - Filename: $this->fileName -";
		if(!empty($this->subfolder)) {
			$auditStr .= " Subfolder: $this->subfolder -";
		}
		if($this->docID == 0) {
			$auditStr .= " This is a template all folders in this cabinet.";
		} else {
			$folderName = getFolderName($this->cabinet, $this->docID, $db);
			$auditStr .= " Folder: $folderName";
		}
		$user->audit('added redaction', $auditStr);

		$redactionArr = getTableInfo ($db, 'redactions', array (),
			array (), 'queryAll');

		lockTables($db, array('redactions'));
		$res = $db->extended->autoExecute('redactions', $queryArr);
		dbErr($res);
		$redactID = getTableInfo($db, 'redactions', array('MAX(id)'), array(), 'queryOne');
		unlockTables($db);
		$fileArr = array ();
		$whereArr = array(
			"doc_id"		=> (int)$this->docID,
			"parent_filename"	=> $this->parentFileName,
			"deleted"		=> 0
				 );

		if(!empty($this->subfolder)) {
			$whereArr['subfolder'] = $this->subfolder;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$fInfo = getTableInfo($db,$this->cabinet."_files",array(),$whereArr);
		while($row = $fInfo->fetchRow()) {
			$myDocID = $row['doc_id'];
			if(!isset($fileArr[$myDocID])) {
				$fileArr[$myDocID] = array ();
			}
			$version = (float) $row['v_major'].'.'.$row['v_minor'];
			$fileArr[$myDocID][$version] = $row;
		}

		$newFileArr = array ();
		foreach($fileArr as $myDocID => $fileRows) {
			$tmpArr = $fileRows;
			ksort($tmpArr);
			$newFileArr[$myDocID] = array_values($tmpArr);
		}
		
		$fileArr = $newFileArr;
		unset($newFileArr);
		$redactFiles = array ();
		foreach($fileArr as $fileClump) {
			$doRedact = true;
			$numVersions = count($fileClump);
			if($numVersions > 1) {
				$newestArr = $fileClump[$numVersions - 1];
				if($newestArr['redaction_id'] != 0) {
					foreach($redactionArr as $myRedact) {
						if($myRedact['id'] == $newestArr['redaction_id']) {
							if($myRedact['doc_id'] != 0 and $newestArr['doc_id'] != $this->calledDocID) {
								$doRedact = false;
								break;
							}
						}
					}
					if($doRedact) {
						$redactFiles[] = $fileClump[0];
					}
				} else {
					//$redactFiles[] = $fileClump[0];
				}
			} else {
				$redactFiles[] = $fileClump[0];
			}
		}
		foreach($redactFiles as $newFile) {
			$parentID = $newFile['id'];
			if($newFile['id'] != $newFile['parent_id']) {
				makeVersioned($this->cabinet, $newFile['id'], $db);
			}
			$version = getNewestVersion($this->cabinet, $newFile['doc_id'], $parentID, $db);
			$version['v_minor'] += 1;
			//The 'id' column is autoincrement, and it will be a key
			//differentiator between the old version and the new version.
			unset($newFile['id']);

			//These will be blank, and in PostgreSQL, if a date is blank, it
			//will insert correctly -- it must be NULL.
			unset($newFile['date_to_delete']);
			unset($newFile['date_locked']);
			
			//This is necessary because empty string is not NULL -- this causes
			//problems in PostgreSQL.
			if($newFile['subfolder'] === '') {
				unset($newFile['subfolder']);
			}
			$newFile['v_major'] 		= (int) $version['v_major'];
			$newFile['v_minor'] 		= (int) $version['v_minor'];
			$newName = $pNameArray[0].'-'.$newFile['v_major'].'_'.$newFile['v_minor'].'.'.$myExt;
			$newFile['filename'] 		= $newName;
			$newFile['parent_filename'] = $this->parentFileName;
			$newFile['redaction'] 		= 'IN PROCESS';
			$newFile['display'] 		= 1;
			$newFile['deleted'] 		= 0;
			$newFile['file_size'] 		= 0;
			$newFile['redaction_id'] 	= (int) $redactID;
			$newFile['doc_id'] 			= (int) $newFile['doc_id'];
			$newFile['ordering'] 		= (int) $newFile['ordering'];
			$newFile['parent_id'] 		= (int) $newFile['parent_id'];
			$newFile['document_id'] 	= (int) $newFile['document_id'];
			$newFile['who_indexed']		= $user->username;
			$newFile['date_created']	= date("Y-m-d G:i:s"); 
			lockTables($db, array($this->cabinet.'_files'));
			$res = $db->extended->autoExecute($this->cabinet.'_files', $newFile);
			dbErr($res);
			$newID = getTableInfo($db, $this->cabinet.'_files', array('MAX(id)'), array(), 'queryOne');
			unlockTables($db);
			updateTableInfo($db, $this->cabinet.'_files',
				array ('display' => 0), 
				array ('parent_id='.$parentID.' AND id <> '.
				$newID));
			$filesToRedact = array (
				'cabinet'		=> $this->cabinet,
				'file_id'		=> (int) $newID,
				'department'	=> $db_name
			);
			$res = $db_doc->extended->autoExecute('files_to_redact', $filesToRedact);
			dbErr($res);
		}
	}

	function endElement($parser, $name) {
		if($name == 'PROPERTIES') {
			$this->inDocProps = false;
		} elseif($name == 'NODE') {
			$this->inNode = false;
			$this->currNode++;
		} else {
			if($this->inNode) {
				$this->nodes[$this->currNode][$name] = $this->myData;
			} elseif($this->inDocProps) {
				if($name == 'HEIGHT') {
					$this->pageHeight = $this->myData;
				} elseif($name == 'WIDTH') {
					$this->pageWidth = $this->myData;
				} elseif($name == 'DOCID') {
					$this->docID = $this->myData;
				} elseif($name == 'CALLEDDOCID') {
					$this->calledDocID = $this->myData;
				} elseif($name == 'FILENAME') {
					$this->fileName = $this->myData;
				} elseif($name == 'PARENTFILENAME') {
					$this->parentFileName = $this->myData;
				} elseif($name == 'SUBFOLDER') {
					$this->subfolder = $this->myData;
				} elseif($name == 'CABINET') {
					$this->cabinet = $this->myData;
				}
			}
		}
		$this->myData = '';
	}

	function getRedaction($db, $data) {
		$this->parse($data);
		return $this->fetchFromTable($db);
	}
	
	function fetchFromTable($db) {
		if($this->docID) {
			$whereArr = array(
				"doc_id"			=> (int)$this->docID,
				"filename"			=> $this->fileName,
				"deleted"			=> 0
					 );
			if(!empty($this->subfolder)) {
				$whereArr['subfolder'] = $this->subfolder;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}
			$fInfo = getTableInfo($db,$this->cabinet."_files",array(),$whereArr,'query',array('doc_id'=>'ASC'));
			$row = $fInfo->fetchRow();
			$redactionID = getTableInfo($db, $this->cabinet.'_files', array('redaction_id'),
				array('parent_id' => (int) $row['id'], 'deleted' => 0), 'queryOne',
				array('v_major' => 'DESC', 'v_minor' => 'DESC'));
			
			$xml = getTableInfo($db, 'redactions', array('xml_data'), array('id' => (int) $redactionID), 'queryOne');
		} else {
			$xml = getTableInfo($db, 'redactions', array('xml_data'), 
				array(
					'doc_id' => 0,
					'subfolder' => $this->subfolder,
					'cabinet' => $this->cabinet,
					'filename' => $this->fileName
				), 'queryOne');
		}
/*		Not sure why this is here could be a template code. Removed to fix ticket #390440
		if(!$xml and $this->docID != 0) {
			$this->docID = 0;
			return $this->fetchFromTable($db);
		} else {
*/		
			if (!$xml) {
				return '<drawDoc></drawDoc>';
			}
			return $xml;
//		}
	}

	function characterData($parser, $data) {
		$this->myData = $data;
	}
	
	function newImage($file, $newFile) {
		global $user, $DEFS;
//set compression to -lzw if global setting says so. fixes xp proc_close
    $glbSettings = new GblStt( $user->db_name, $db_doc);
		if ($glbSettings->get('imgCompression')==1){
			$imgcompression1='-lzw';
			$imgcompression='-lzw';
		} else {
			$imgcompression1='-g4';
			$imgcompression='-flate';
		}
		list($newWidth, $newHeight) = getimagesize($file);
		$oldWidth = $this->pageWidth;
		$oldHeight = $this->pageHeight;
		$xScale = $newWidth / $oldWidth;
		$yScale = $newHeight / $oldHeight;
		if(getMimeType($file, $DEFS) == 'image/tiff') {
			$isTiff = true;
			shell_exec ($DEFS['CONVERT_EXE'] . ' ' .
				escapeshellarg ($file) . ' ' . 
				escapeshellarg ($file.'.png'));
			$jpegStr = @file_get_contents ($file.'.png');
			@unlink ($file.'.png');
		} else {
			$isTiff = false;
			$jpegStr = file_get_contents($file);
		}
		$tmpImg = imagecreatefromstring($jpegStr);
		$newImg = imagecreatetruecolor($newWidth, $newHeight);
		imagecopy($newImg, $tmpImg, 0, 0, 0, 0, $newWidth, $newHeight);
		$startX = -1;
		$startY = -1;
		$endX = -1;
		$endY = -1;
		$color = '';
		$redactArr = array ();
		$redactColor = imagecolorallocate($newImg, 255, 255, 255);
		$redactAdmin = imagecolorallocatealpha($newImg, 100, 100, 100, 64);
		$highlight = false;
		$setTrans = false;

		$db_doc = getDbObject('docutron');
        	$allowNonRedact = '';
        	$allowNonRedact = getTableInfo($db_doc, 'settings', array('value'), array("k='allowNonRedact'", "department='{$user->db_name}'"), 'queryOne');
		
		foreach($this->nodes as $myNode) {
			$oldNodeWidth = $myNode['WIDTH'];
			$newNodeWidth = ceil($xScale * $oldNodeWidth);
			$oldNodeHeight = $myNode['HEIGHT'];
			$newNodeHeight = ceil($xScale * $oldNodeHeight);
			$startX = ceil($xScale * $myNode['LEFT']);
			$startY = ceil($yScale * $myNode['TOP']);
			$endX = $startX + $newNodeWidth;
			$endY = $startY + $newNodeHeight;
			if($myNode['TYPE'] == 'redact') {
				$redactArr[] = array ($startX, $startY, $endX, $endY);
			if(isset($allowNonRedact) && $allowNonRedact == '0') {
		                imagefilledrectangle($newImg, $startX, $startY, $endX, $endY, $redactColor);
                	}

			} elseif($myNode['TYPE'] == 'stamp' || $myNode['TYPE'] == 'textOver') {
				$highlight = true;
				if(isSet($DEFS['REDACT_BW'])) {
					$highlight = false;
				}
				
				$dbName = $user->db_name;
				if($myNode['TYPE'] == 'stamp')
					$gdImg = createStamp($dbName, $myNode['USER'], $myNode['TIMESTAMP'], $myNode['TIME'],
						$newNodeWidth, $newNodeHeight, $myNode['IMAGE'], $xScale, $yScale);
				else {
					$gdImg = getTextImage($myNode['TEXT'], $myNode['SIZE']);
					
				}
				$oldX = imagesx($gdImg);
				if($oldX != $newNodeWidth) {
					imagecopyresized($newImg, $gdImg, $startX, $startY, 0, 0, 
						$newNodeWidth, $newNodeHeight, $oldNodeWidth, $oldNodeHeight);
				} else {
					imagecopyresized($newImg, $gdImg, $startX, $startY, 0, 0, 
						$newNodeWidth, $newNodeHeight, $newNodeWidth, $newNodeHeight);
				}
			} else {
				$highlight = true;
				$color = $this->convertColor($newImg, $myNode['COLOR']);
				imagefilledrectangle($newImg, $startX, $startY, $endX, $endY, $color);
			}
		}
		$newImgAdmin = imagecreatetruecolor($newWidth, $newHeight);
		imagecopy($newImgAdmin, $newImg, 0, 0, 0, 0, $newWidth, $newHeight);
		imagepalettecopy($newImgAdmin, $newImg);
		
		if(!isset($allowNonRedact) || $allowNonRedact == '1') {
			foreach($redactArr as $myPoint) {
				list($startX, $startY, $endX, $endY) = $myPoint;
				imagefilledrectangle($newImg, $startX, $startY, $endX, $endY, $redactColor);
				imagefilledrectangle($newImgAdmin, $startX, $startY, $endX, $endY, $redactAdmin);
			}
		}
		if($isTiff) {
			$tmpFile = $DEFS['TMP_DIR'] . '/docutron/' . $user->username .
				'/tmpRedact.png';
			imagepng($newImg, $tmpFile);
			
			$tmpAdminFile = $DEFS['TMP_DIR'] . '/docutron/' . $user->username .
				'/tmpRedactAdmin.png';
			
			imagepng($newImgAdmin, $tmpAdminFile);
			if(!$highlight) {
				$cmd = $DEFS['PNGTOPNM_EXE'] . ' -quiet ' . escapeshellarg ($tmpFile) .
					' > ' . escapeshellarg ($tmpFile . '.pnm');
				shell_exec ($cmd);
				$cmd = $DEFS['PNMDEPTH_EXE'] . " 1 -quiet " . escapeshellarg ($tmpFile . '.pnm') . 
					' > ' . escapeshellarg ($tmpFile . '.pnm2');
				shell_exec ($cmd);
				unlink ($tmpFile . '.pnm');
				$cmd = $DEFS['PNMTOTIFF_EXE'] . " ".$imgcompression1." -quiet " . escapeshellarg ($tmpFile . '.pnm2') . 
					" > " . escapeshellarg($newFile);
				shell_exec ($cmd);
				unlink ($tmpFile . '.pnm2');
				if (filesize ($newFile) < 1000) {
					$cmd = $DEFS['PNGTOPNM_EXE'] . " -quiet " . escapeshellarg ($tmpFile) . 
							" | " . $DEFS['PNMTOTIFF_EXE'] . " ".$imgcompression." -quiet > " . 
							escapeshellarg($newFile);
					shell_exec ($cmd);

				}
				unlink($tmpFile);
			} else {
				$cmd = $DEFS['PNGTOPNM_EXE'] . ' -quiet ' . escapeshellarg ($tmpFile) .
					' > ' . escapeshellarg ($tmpFile . '.pnm');
				shell_exec ($cmd);
				$cmd = $DEFS['PNMTOTIFF_EXE'] . " ".$imgcompression." -quiet " . escapeshellarg ($tmpFile . '.pnm') . 
					" > " . escapeshellarg($newFile);
				shell_exec ($cmd);
				unlink ($tmpFile . '.pnm');
				unlink($tmpFile);
			}
			$cmd = $DEFS['PNGTOPNM_EXE'] . ' -quiet ' . escapeshellarg ($tmpAdminFile) .
				' > ' . escapeshellarg ($tmpAdminFile . '.pnm');
			shell_exec ($cmd);
			$cmd = $DEFS['PNMTOTIFF_EXE'] . " ".$imgcompression." -quiet " . escapeshellarg ($tmpAdminFile . '.pnm') . 
				" > " . escapeshellarg($newFile . '.adminRedacted');
			shell_exec ($cmd);
			unlink ($tmpAdminFile . '.pnm');
			unlink($tmpAdminFile);
		} else {
			imagejpeg($newImg, $newFile);
			imagejpeg($newImgAdmin, $newFile.'.adminRedacted');
		}

		$caHash = '';
		if(check_enable('centera',$user->db_name)) {
			$caHash = centput($newFile, $DEFS['CENT_HOST'],$user, $this->cabinet );
			$caHash2 = centput($newFile.'.adminRedacted', $DEFS['CENT_HOST'],$user, $this->cabinet );
			$fd = fopen($newFile.'.adminRedacted.ca_hash', 'w+');
			fwrite($fd, $caHash2.','.filesize($newFile.'.adminRedacted'));
			fclose($fd);
		}
		
		return $caHash;
	}

	function convertColor($newImg, $color) {
		switch($color) {
			case 'aqua':
				$newColor = array (0, 255, 255);
				break;
			case 'black';
				$newColor = array (0, 0, 0);
				break;
			case 'blue';
				$newColor = array (0, 0, 255);
				break;
			case 'fuchsia';
				$newColor = array (255, 0, 255);
				break;
			case 'gray';
				$newColor = array (190, 190, 190);
				break;
			case 'green';
				$newColor = array (0, 128, 0);
				break;
			case 'lime';
				$newColor = array (0, 255, 0);
				break;
			case 'maroon';
				$newColor = array (128, 0, 0);
				break;
			case 'navy';
				$newColor = array (0, 0, 128);
				break;
			case 'olive';
				$newColor = array (128, 128, 0);
				break;
			case 'purple';
				$newColor = array (128, 0, 128);
				break;
			case 'red';
				$newColor = array (255, 0, 0);
				break;
			case 'silver';
				$newColor = array (192, 192, 192);
				break;
			case 'teal';
				$newColor = array (0, 128, 128);
				break;
			case 'yellow';
				$newColor = array (255, 255, 0);
				break;
		}
		list($r, $g, $b) = $newColor;
		return imagecolorallocatealpha($newImg, $r, $g, $b, 64);
	}
}

?>
