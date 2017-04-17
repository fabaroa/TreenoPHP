<?php
include_once '../lib/settings.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/email.php';
include_once '../lib/mime.php';
include_once '../lib/indexing2.php';
include_once '../movefiles/moveFiles2.php';
include_once '../lib/odbc.php';
include_once '../DataObjects/DataObject.inc.php';
include_once '../lib/delegate.php';
include_once '../centera/centera.php';
include_once '../workflow/node.inc.php';
include_once '../documents/documents.php';

function moveNestedFolders($path,$extraFolders,&$filelist) {
	foreach($extraFolders AS $dir) {
		$path = str_replace("\\\\", "\\", $path);
		$h = opendir($path."/".$dir);
		while(false !== ($file = readdir($h))) {
			if(is_file($path."/".$dir."/".$file)) {
				//move files	
				if(!file_exists($path."/".$file)) {
					rename($path."/".$dir."/".$file,$path."/".$file);
					$filename = $file;
				} else {
					$ct = 1;
					$filename = $file;
					while(file_exists($path."/".$filename)) {
						$fpieces = explode(".",$file);
						$fpieces[sizeof($fpieces)-2] .= "-".$ct;
						$filename = implode(".",$fpieces);
						$ct++;
					}
					rename($path."/".$dir."/".$file,$path."/".$filename);
				}
				$filelist[] = $filename; 
			} else {
				if($file != "." && $file != "..") {
					moveNestedFolders($path,array($dir."/".$file),$filelist);
				}
			}
		}
		@rmdir($path."/".$dir);
	}
}


function sortFileList( &$allFiles, $key, $sortDir ) {
	$tmpFiles = array();
	$tmpFiles = $allFiles;
	$files = array();
	foreach( $allFiles as $file ) {
		$files[] = $file[$key];
	}
	usort( $files, 'strnatcasecmp' );
	$newAllfiles = array();
	foreach( $files as $file ) {
		for($i=0;$i<sizeof($tmpFiles);$i++) {
			if( $tmpFiles[$i][$key]==$file ){ 
				$newAllfiles[] = $tmpFiles[$i];
				unset($tmpFiles[$i]);
				break;
			}
		}
		$tmpFiles = array_values($tmpFiles);
	}
	
	if( $sortDir=='DESC' ) {
		krsort( $newAllfiles );
	}
	$allFiles = $newAllfiles;
}

function sortByKey($array, $key, $dir) {
	$sortedArr = array();
	foreach($array as $item) {
		$newKey = $item[$key];
		while(isset($sortedArr[$newKey])) {
			if(is_numeric($newKey)) {
				$newKey += 1;
			} else {
				$newKey .= 'a';
			}
		}
		$sortedArr[$newKey] = $item;
	}
	if($dir == 'ASC') {
		ksort($sortedArr);
	} else {
		krsort($sortedArr);
	}
	return array_values($sortedArr);
}
/*
function checkFolders($folderlist,$db_doc) {
	$fold = array();
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->orderBy('username', 'ASC');
	$DO_user->find();
	$names = array();
	error_log ('hi');
	while($DO_user->fetch())
		$names[] = $DO_user->username;
	$i=0;
	while ($i < sizeof($folderlist)) {
		if (!in_array($folderlist[$i], $names) ){ 
			$fold[] = $folderlist[$i];
		}
		$i++;
	}
	return ($fold);
}
 */

function getOrderSett($dbname, $cab, &$db_doc) {
	$usrStt = new GblStt($dbname, $db_doc) ;
	$ordering = $usrStt->get("indexing_ordering_$cab") ;
	if($ordering == ""){
		$ordering = $usrStt->get('indexing_ordering') ;
		if ($ordering == "")
			$ordering = 0; // Default to prepend if none
}
	return $ordering ;
}

// This function returns the next order value for a specific folder or tab
// based on the ordering (prepend or append) setting passed to it. If it
// is not passed, it will try to determine it.
function getOrderingValue($db_object, $files_table, $doc_id, $tab, $ordering, $numFiles=1) {

	if($ordering == '1') {
		$qordering = 'MAX(ordering) + 1';
	} else {
		$qordering = 'MIN(ordering) - '.$numFiles;
	}

	$whereArr = array('doc_id' => (int) $doc_id);
	if( $tab != 'Main' and $tab != NULL ) {
		$whereArr['subfolder'] = $tab;
	} else {
		$whereArr['subfolder'] = 'IS NULL';
	}
	$max = getTableInfo($db_object, $files_table, array($qordering), $whereArr, 'queryOne');

	if($max === '')
		$max = 1;

	return($max);
}

function insertDeletedFiles ($path, &$queryArr, $db_doc) {
	if(is_dir($path)) {
		$h = opendir($path);
		while(false !== ($file = readdir($h))) {
			if(is_file($path."/".$file)) {
				if(file_exists($path."/".$file)) {
					$queryArr['filename'] = $file;
					$res = $db_doc->extended->autoExecute('inbox_recyclebin',$queryArr);
					dbErr($res);
				}
			}
		}
	} else {
		$res = $db_doc->extended->autoExecute('inbox_recyclebin',$queryArr);
		dbErr ($res);
	}
}

function getFolderStats($folderlist, $path, $owner=null, $delUser=null) {
	$allFolders = array();
	foreach($folderlist as $myFolder) {
        if( substr_count($myFolder,'../') > 0 ) {
            $foldername = str_replace('../','',$myFolder);
        } else {
            $foldername = $myFolder;
        }
	
        if( is_dir($path.$myFolder) ) {
		$st = array ();
		if($fp = opendir($path.$myFolder)) {
			while(false !== ($file = readdir($fp))) {
				if($file != "." && $file != "..") {
					$fpath = $path.$myFolder."/".$file;
					if(file_exists($fpath)) {
						$st = stat($fpath);
					}
				}
			}
		}


		if($st) {
			$allFolders[] = array(
				'realTime'  => $st[9],
				'time'      => date("F j, Y, g:i:s a", $st[9]),
				'size'      => checkFiles($myFolder, $path)." File(s)",
				'name'      => $foldername,
				'urlName'   => rawurlencode($foldername),
				'type'      => 'folder'
				//'mimeType'	=> 'folder'
			);
		} elseif(is_dir($path.$foldername)) {
			@rmdir($path.$foldername);
		}
        }
    }
	return $allFolders;
}

function getFileStats($filelist, $path, $owner=null, $delUser=null) {
	global $DEFS;
	$allFiles = array();
	foreach($filelist as $myFile) {
		//$mimeType = getMimeType($path.$myFile, $DEFS);
        $fileStat = stat($path.$myFile);
        $fileInfo = array(
            'realTime'  => $fileStat[9],
            'time'      => date("F j, Y, g:i:s a", $fileStat[9]),
            'size'      => ceil($fileStat[7]/1024)." KB",
            'name'      => $myFile,
            'urlName'   => rawurlencode($myFile),
            'type'      => 'file'
		//	'mimeType'	=> $mimeType
        );
        $allFiles[] = $fileInfo;
    }
	return $allFiles;
}

function delegateFromInbox($user, &$delegateObj, $getUsername) {
	if( isSet($_POST['check1']) )
		$filesSelected = $_POST['check1'];
	else
		$filesSelected = array();

	$comments = $_POST['comments'];
	$delegated_user = $_POST['delegated_user'];
	$status = $_POST['status'];
	//TODO
	//This is really inefficient right now
	$path = $user->getRootPath()."/personalInbox/".$user->username;
	foreach($filesSelected AS $file) {
		$file = urldecode($file);
		$fileArr = array();
		if(is_dir($path."/".$file)) {
			$folder = $file; 
			$handle = opendir($path."/".$folder);
			while (false !== ($file = readdir($handle))) {
				if(is_file($path."/".$folder."/".$file)) {
					$fileArr[] = $file;
				}
			}
			$delegateObj->addToDelegateList($delegated_user, $getUsername, $folder, $fileArr, $status, $comments);
		} else {
			$fileArr[] = $file;
			$delegateObj->addToDelegateList($delegated_user, $getUsername,"",$fileArr, $status, $comments);
		}
	}
	return "File successfully delegated";
}

function deleteFromInbox($path, $user, $db_doc) {
	global $DEFS;
	$filesSelected = $_POST['check1'];
	$type = $_GET['type']; 
	$queryArr = array();
	$queryArr['username'] = $user->username;
	$queryArr['department'] = $user->db_name;

	$inboxPath = $DEFS['DATA_DIR']."/".$user->db_name."/recyclebin";
	if (!file_exists($inboxPath)) {
		mkdir( $inboxPath, 0777 );
	}
	
	$date = date('Y-m-d');
	$inboxPath .= "/".$date;
	if (!file_exists($inboxPath)) {
		mkdir( $inboxPath, 0777 );
	}

	$inboxPath .= "/".$user->username;
	if (!file_exists($inboxPath)) {
		mkdir( $inboxPath, 0777 );
	}

	$time = date('G-i-s');
	$inboxPath .= "/".$time;
	if (!file_exists($inboxPath)) {
		mkdir( $inboxPath, 0777 );
	}

	if ($_GET['folder']) {
		$inboxPath .= "/".$_GET['folder'];
		if( !file_exists( $inboxPath ) ) {
			mkdir( $inboxPath, 0777 );
		}
	}

	$queryArr['date_deleted'] = $date." ".str_replace('-', ':', $time);
	if (sizeof($filesSelected) > 0) {
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->orderBy('username', 'ASC');
		$DO_user->find();
		while($DO_user->fetch()) {
			$names[] = $DO_user->username;
		}
		
		$folder = $_GET['folder'];
		if( $type == 1 ) {
			$queryArr['type'] = 'personal';
		} else {
			$queryArr['type'] = 'public';
		}
		
		for ($i = 0; $i < sizeof($filesSelected); $i ++) {
			if(isSet($queryArr['folder'])) {
				unset($queryArr['folder']);
			}
			if($folder) {
				$queryArr['folder'] = $folder;
			}
			
        	$fname = urldecode( $filesSelected[$i] );
			if(is_dir($path."/".$fname)) {
				$queryArr['folder'] = $fname;
			} else {
				$queryArr['filename'] = $fname;
			}
			if( in_array($fname, $names) ) {
				$fpath = "../".$fname; 
			} else {
				$fpath = $fname;
			}
			
			$filesMissing = '';
			if (file_exists($path."/".$fpath)) {
				$queryArr['path'] = $path;
				insertDeletedFiles($path."/".$fpath,$queryArr, $db_doc);
				rename("$path/$fpath", "$inboxPath/$fname");
                if($type == 1) {
                	$user->audit("path deleted from personal inbox", "From: ".$path."/".$fname." , TO: ".$inboxPath."/".$fname);
                } else {
                    $user->audit("path deleted from inbox", "From: ".$path."/".$fname." , TO: ".$inboxPath."/".$fname);
				}
			} else
                $filesMissing = $fname." ";
                                                                                                                             
			if ($filesMissing) {
            	$mess = str_replace( " ", ",", trim( $filesMissing ) );
                $mess .= " have been moved or delete since last update";
			} else
                $mess = "Selected files successfully deleted";
        }
	} else
		$mess = "No files were selected";

return( $mess );
}

function moveFromInbox($path, $user, &$db_raw, $filesArr, $db_object) {

	global $DEFS;
 	$delFiles = array ();
	$dispDoc = "";
 	$dh = opendir ($path);
 	$myEntry = readdir ($dh);
 	while ($myEntry !== false) {
 		if (is_file ($path.'/'.$myEntry) and getExtension($myEntry) == 'DAT') {
 			$delFiles = $path . '/' . $myEntry;
 		}
 		$myEntry = readdir ($dh);
 	}
 	closedir ($dh);
 	foreach ($delFiles as $myFile) {
 		unlink ($myFile);
 	}
	$cabinet = $_GET['cab'];
    if( $user->checkSecurity( $cabinet ) == 2 ) {
		$doc_id = $_GET['doc_id'];
		$tab = $_GET['tab'];
		$filesSelected = $filesArr;
		
		$gblStt = new gblStt($user->db_name, $db_raw);
		$fieldValues = getCabIndexArr($doc_id,$cabinet,$db_object);
		$location = getTableInfo($db_object,$cabinet,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');
		$location = $DEFS['DATA_DIR']."/".str_replace(" ", "/", $location);
		
		$foldername = implode(" ",$fieldValues);
		$loc = "Cab: $cabinet Folder: $foldername";
		if($user->checkSetting("documentView",$cabinet)) {
			$enArr = array('cabinet' => $cabinet,
							'doc_id' => $doc_id,
							'filter' => 'All');
			$docArr = getFolderDocuments($enArr,$user,$db_raw,$db_object);
			
			foreach($docArr AS $myDoc) {
				if($myDoc['subfolder_name'] == $tab) {
					$dispDoc = $myDoc;
					break;
				}
			}
			if($dispDoc) {
				$loc .= " Document: ".$dispDoc['name'].": ".implode(" - ",$dispDoc['documents']);
			} else {
				$loc .= " Tab: $tab";
			}
		} else {
			$loc .= " Tab: $tab";
		}
			
		usort( $filesSelected, 'strnatcasecmp' );
			
		if(!is_dir($location)) {
			mkdir($location,0755);
		}

		if ($tab && strtolower($tab) != "main" ) {
			$location .= "/".$tab;
			if(!is_dir($location)) {
				mkdir($location,0755);
			}
		} else {
			$tab = NULL;
		}

		$DO_user = DataObject::factory('users', $db_raw);
		$DO_user->orderBy('username', 'ASC');
		$DO_user->find();
		$names = array();
		while($DO_user->fetch())
			$names[] = $DO_user->username;

		$whereArr = array('doc_id'=>(int)$doc_id,'filename'=>'IS NOT NULL');	
		if($tab) {
			$whereArr['subfolder'] = $tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$fileList = array();
		$filelist = getTableInfo($db_object,$cabinet."_files",array('filename'),$whereArr,'queryCol');
		
		foreach ($filelist as &$file) {
			$file = strtolower($file);
		}
		
		$quotaInfo = getLicensesInfo( $db_raw, $user->db_name );
		$result = $quotaInfo->fetchRow();
		$quota_allowed = $result['quota_allowed'];
		$quota_used = $result['quota_used'];
		$space_allowed = $quota_allowed - $quota_used;
																																 
		$insertFiles = array();
		$curFileLoc = array();
		$destFileLoc = array();
		$list = array();
		
		$diskSpaceNeeded = checkInboxSelected( $path, $filesSelected );
		if($diskSpaceNeeded < $space_allowed) {
			$orderType = getOrderSett($user->db_name,$cabinet, $db_raw);
			$updateArr = array('quota_used'=>'quota_used+'.$diskSpaceNeeded);
			$whereArr = array('real_department'=> $user->db_name);
			updateTableInfo($db_raw,'licenses',$updateArr,$whereArr,1);
			
			for ($i = 0; $i < sizeof($filesSelected); $i ++) {
						$fname = urldecode( $filesSelected[$i] );
				if( in_array($fname, $names) ) {
					$fpath = "../".$fname; 
				} else {
					$fpath = $fname;
				}

				if (is_dir($path."/".$fpath)) {
					$folderList = listFiles($fpath,$path);
					usort($folderList,"strnatcasecmp");
					$list = array_merge($list,$folderList);
					//$link = getLink( $user->db_name, $cabinet, $doc_id );		
					//$user->audit($auditmess, "<a href=\"$link\">link to $fname</a>");
					$auditmess = "folder has been filed";
					$user->audit($auditmess, "Moved: folder $fname to $loc");
				} else {
					if (is_file($path."/".$fpath)) {
						$list[] = $fpath;
						$auditmess = "file has been filed" ;
						//$link = getLink( $user->db_name, $cabinet, $doc_id );		
						//$user->audit($auditmess, "<a href=\"$link\">link to $fname</a>");
						$user->audit($auditmess, "Moved:$fname to $loc");
					}
				}
			}

			$orderID = 0;
			lockTables($db_object, array ($cabinet . '_files','audit'));
			$orderID = getOrderingValue($db_object, $cabinet."_files", $doc_id, $tab, $orderType, sizeof($list));
			foreach( $list AS $filename ) {
				if( substr_count($filename,"@@~~") > 0) {
					$name = explode("@@~~",$filename);	
					$folder = $name[0];
					$filename = $name[1];
					$st = stat($path."/".$folder."/".$filename);
					$curFileLoc[] = $path.$folder."/".$filename;
				} else {
					$st = stat($path."/".$filename);
					$curFileLoc[] = $path.$filename;
				}
				$newFilename = getNewFilename($filename,$filelist,$location);
				$size = $st[7];
				$date = date("Y-m-d G:i:s", $st[9]);
				$insertFiles[] = array(
									"doc_id"			=> (int)$doc_id,
									"filename"			=> $newFilename,
									"subfolder"			=> $tab,
									"ordering"			=> (int)$orderID, 
									"date_created"		=> $date,
									"who_indexed"		=> $user->username,
									"parent_id"			=> (int)0,
									"parent_filename"	=> $newFilename,
									"file_size"			=> (int)$size	
									  );
				$destFileLoc[] = $location."/".$newFilename;
				$orderID++;
			}
			$ibxErrs = array ();

			for($i=0;$i<sizeof($curFileLoc);$i++) {
				if(!@rename( $curFileLoc[$i], $destFileLoc[$i])) {
					$mess = "Error copying file"; 
					$myErr = 'file '.$curFileLoc[$i].' could not be moved to '.$destFileLoc[$i];
					unlockTables($db_object);
					$user->audit ('inbox fatal error', $myErr);
					return $mess;
				}

				if( check_enable('centera',$user->db_name) && $gblStt->get('centera_'.$cabinet) == 1){
					$insertFiles[$i]['ca_hash'] = centput($destFileLoc[$i],	$DEFS['CENT_HOST'], $user, $cabinet);
				}

				$res = $db_object->extended->autoExecute($cabinet."_files", $insertFiles[$i]);
				dbErr($res);
			}
/*
			for($i=0;$i<sizeof($curFileLoc);$i++) {
				if( check_enable('centera',$user->db_name) && $gblStt->get('centera_'.$cabinet) == 1){
					$insertFiles[$i]['ca_hash'] = centput($destFileLoc[$i],	$DEFS['CENT_HOST'], $user, $cabinet);
				}
			}

			foreach( $insertFiles AS $fileInfo ) {
				$res = $db_object->extended->autoExecute($cabinet."_files", $fileInfo);
				dbErr($res);
			}
*/
			unlockTables($db_object);

			if(isSet($_GET['wf']) && $_GET['wf'] != "__default" && $_GET['wf'] != '') {
				$wf = $_GET['wf'];
				$wfRes = getWFDefsInfo($db_object,$wf);
				$wf_def_id = (int)$wfRes[1];

				$file_id = NULL;
				if($user->checkSetting('documentView',$cabinet)) {
					$sArr = array('id');
					$wArr = array(	'doc_id'	=> (int)$doc_id,
								'subfolder'	=> $tab,
								'filename'	=> 'IS NULL');
					$file_id = getTableInfo($db_object,$cabinet."_files",$sArr,$wArr,'queryOne');
				}

				$sArr = array('file_id');
				$wArr = array(	"cab='$cabinet'",
								"doc_id=".(int)$doc_id,
								"status!='COMPLETED'");
				if($file_id) {
					$wArr['file_id'] = (int)$file_id;
				}
				$fidArr = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne');
				$wfBool = false;
				if(is_array($fidArr)) {
					foreach($fidArr AS $fid) {
						if($user->checkSetting('documentView',$cabinet)) {
							if($fid == $file_id) {
								$wfBool = true;
								break;
							}
						} else {
							if($fid == -1) {
								$wfBool = true;
								break;
							}
						}

						if($fid == -2) {
							$wfBool = true;
							break;
						}
					}
				}

				if(!$wfBool) {
					$wf_doc_id = (int)addToWorkflow($db_object,$wf_def_id,$doc_id,$file_id,$cabinet,$user->username);
					$cabDispName = getTableInfo($db_object, 'departments', array('departmentname'),
						array('real_name' => $cabinet), 'queryOne');
					$stateNodeObj   = new stateNode($db_object,$user->db_name,$user->username,
													$wf_doc_id,$wf_def_id,$cabinet,$cabDispName,$doc_id,$db_raw);
					$stateNodeObj->notify();
				}
			}
			$mess = "Files successfully moved";
		} else {
			$mess = "Not Enough Space To Move Selected Files";
		}
	} else {
		$mess = "Invalid permissions";
	}
return( $mess );
}
/*function searchResults($cab, $temp_table, $db_obj, $user, $value, $acTable = '',$deleted=0) {

    $fieldnames = getCabinetInfo( $db_obj, $cab );
    if( $user->checkSecurity( $cab ) == 2 ) {
		for ($i = 0; $i < sizeof($terms); $i ++) {
			$queryArr = array();
			$prev = $temp_table;
			if ($i == 0) {
				$tableArr = array("$cab");
			} else {
				$tableArr = array("$prev","$cab");
				$queryArr[] = "$cab.doc_id=$prev.result_id";
			}

			$fieldArr = array();
			for($j=0;$j<count($names);$j++) {
				$fieldArr[] = $names[$j]." " . LIKE . " '%$terms[$i]%'";
			}
			$queryArr[] = implode(" OR ",$fieldArr);
			$queryArr2 = array("deleted=0 AND (".implode(" OR ",$queryArr).")" );

			$temp_table = createTemporaryTable($db_object);
			insertFromSelect($db_object,$temp_table,array('result_id'),$tableArr,array('doc_id'),$queryArr2);
		}
	}
}*/

function moveToInbox ($user) {
	$xmlStr = file_get_contents ('php://input');
	$filesSelected = array();
	$list = array ();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem ($xmlStr);
		$username = $domDoc->get_elements_by_tagname('DESTINATION_USER');
		$username = $username[0]->get_content();
		$oldUser = $domDoc->get_elements_by_tagname('CURRENT_USER');
		$oldUser = $oldUser[0]->get_content();
		$path = $domDoc->get_elements_by_tagname('PATH');
		$path = $path[0]->get_content();
		$path = str_replace("\\\\", "\\", $path);
		$folder = $domDoc->get_elements_by_tagname('FOLDER');
		$folder = $folder[0]->get_content();
		$files = $domDoc->get_elements_by_tagname('FILE');
		foreach($files AS $file) {
			$filesSelected[] = $file->get_content();
		}
	} else {
		$xml = simplexml_load_string ($xmlStr);
		$username = $xml->DESTINATION_USER[0];
		$oldUser = $xml->CURRENT_USER[0];
		$path = $xml->PATH[0];
		$path = str_replace("\\\\", "\\", $path);
		$folder = $xml->FOLDER[0];
		foreach ($xml->FILE as $file) {
			$filesSelected[] = $file;
		}
	}
    //loops through all the selected files/folders
    for ($i = 0; $i < sizeof($filesSelected); $i ++) {
        $fname = urldecode($filesSelected[$i]);
		$fpath = $path."/".$oldUser;
		if($folder) {
			$fpath .= "/".$folder;
		}
        //audits the files/folders being moved
        if (is_dir($fpath."/".$fname)) {
            $auditmess = "folder has been moved";
            $user->audit($auditmess, "Moved: folder $fname from ".$oldUser."'s personal inbox to ".$username."'s personal inbox");
        } else {
            if (is_file($fpath."/".$fname)) {
				if($folder) {
					$fn = $folder."/".$fname;
				} else {
					$fn = $fname;
				}
                $auditmess = "file has been moved";
                $user->audit($auditmess, "Moved:$fn from ".$oldUser."'s personal inbox to ".$username."'s personal inbox");
            }
        }
        $list[] = $fname;
    }
    //makes personal inbox for the user if it doesn't exist
    if(!file_exists($path."/".$username)) {
        mkdir($path."/".$username, 0755);
    }

    //checks to see if user is inside a folder
    if($folder) {
        $p = $path."/".$username."/".$folder;
        $folder = $folder;
        //creates folder if it doesn't exist
        if(!file_exists($path."/".$username."/".$folder)) {
            mkdir($path."/".$username."/".$folder, 0755);
        }
    } else {
        $p = $path."/".$username;
    }

    //creates a list of all files that exist
    $filelist = array();
    $handle = opendir($p);
    while (false !== ($file = readdir($handle))) {
        if($file != "." && $file != "..") {
            $filelist[] = $file;
        }
    }

    //checks to see if filenames exist already if so add a counter to file
    //so we don't overwrite the existing file
    foreach( $list AS $filename ) {
        $newFilename = getNewFilename($filename,$filelist,$path."/".$username."/".$folder);
        if($folder){
            $curFileLoc[] = $path."/".$oldUser."/".$folder."/".$filename;
            $destFileLoc[] = $path."/".$username."/".$folder."/".$newFilename;
        } else {
            $curFileLoc[] = $path."/".$oldUser."/".$filename;
            $destFileLoc[] = $path."/".$username."/".$newFilename;
        }
    }

    //physicallly moves the files
    for($i=0;$i<sizeof($curFileLoc);$i++) {
        //echo "<br>copy $curFileLoc[$i] to $destFileLoc[$i]";
        if(!@rename($curFileLoc[$i],$destFileLoc[$i])) {
			echo false;
			die();
        }
    }
	echo true;
}

//For renaming delegated items which needs to update the database to keep in sync
function renameDelegateItem($user, $delegateID, $path, $origName, $newName, $db_object) {
	if( ($delegateID > 0) && ($delegateID != null) ) {
		//Third param to disable build of delegate obj
		$delegateObj = new delegate($user->getRootPath()."/personalInbox/",$user->username, $db_object, 0);
		$delegateObj->renameFolder($delegateID, $newName);
	}
}

function renameFolder ($user) {
 	$xmlStr = file_get_contents ('php://input');
	$mess = ''; 
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem ($xmlStr);
 		$origName = $domDoc->get_elements_by_tagname ('ORIGINAL_FILENAME');
 		$origName = $origName[0]->get_content ();
 		$newName = $domDoc->get_elements_by_tagname ('NEW_FILENAME');
 		$newName = $newName[0]->get_content ();
 		$path = $domDoc->get_elements_by_tagname ('PATH');
 		$path = $path[0]->get_content ();
 		$path = str_replace("\\\\", "\\", $path);
 	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
 		$origName = $domDoc->getElementsByTagName ('ORIGINAL_FILENAME');
		$origName = $origName->item(0);
		$origName = $origName->nodeValue;
 		$newName = $domDoc->getElementsByTagName ('NEW_FILENAME');
		$newName = $newName->item(0);
		$newName = $newName->nodeValue;
 		$path = $domDoc->getElementsByTagName ('PATH');
		$path = $path->item(0);
		$path = $path->nodeValue;
		$path = str_replace("\\\\", "\\", $path);
 	}
 	if (file_exists ($path."/".$origName)) {
 		if (is_file ($path."/".$origName)) {
 			$pos = strrpos ($newName, '.');
 			$filename = substr ($newName, 0, $pos);
  		} else {
  			$filename = $newName;
  		}
 		if ($filename) {
 			if (!file_exists ($path."/".$newName)) {
 				if (@rename ($path."/".$origName, $path."/".$newName)) {
 					echo "1";
 					$path = explode ("/", $path);
 					$path = implode ("/", array_slice ($path, 4));
 					$user->audit('rename inbox file', $path . '/' . $origName .
 							' renamed to ' . $path . '/' . $newName);
  					//$mess = "Folder name successfully changed";
  				} else {
 					$mess = 'An error occured while renaming a file/folder';
  				}
  			} else {
 				$mess = 'Name already exists.  Please choose another name';
  			}
  		} else {
 			$mess = 'Name is blank';
  		}
  	} else {
 		$mess = 'File Not Found. This file/folder has either been moved or ' .
 			'deleted';
  	}
  	echo $mess;
}

function renameFolder2($user) {
 	$xmlStr = file_get_contents ('php://input');
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem ($xmlStr);
 		$newName = $domDoc->get_elements_by_tagname ('NEW_NAME');
 		$newName = $newName[0]->get_content ();
 		$path = $domDoc->get_elements_by_tagname ('PATH');
 		$path = $path[0]->get_content ();
 		$path = str_replace("\\\\", "\\", $path);
 	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
 		$newName = $domDoc->getElementsByTagName ('NEW_NAME');
		$newName = $newName->item(0);
		$newName = $newName->nodeValue;
 		$path = $domDoc->getElementsByTagName ('PATH');
		$path = $path->item(0);
		$path = $path->nodeValue;
		$path = str_replace("\\\\", "\\", $path);
 	}

	$mess = "";
	$folderPath = dirname($path);
	if(is_dir($path)) {
		if($newName) {
			if(!is_dir($folderPath."/".$newName)) {
				rename($path,$folderPath."/".$newName);
				$mess = "folder successfully renamed";
				$user->audit('rename inbox file', $path.' renamed to ' . $folderPath.'/'.$newName);
				$_SESSION['ibxFolder'] = $newName;
			} else {
				$mess = "folder already exists";
			}
		} else {
			$mess = "Name is blank";
		}
	} else {
 		$mess = 'File Not Found. This file/folder has either been moved or ' .
 			'deleted';
	}
	echo $mess;
}

function displayInboxDelegation($user,$userList) {
	$statusArr = array('In Progress', 'Incomplete','Complete','Reject');
?>
	<div style="border-style:double;border-width: 5px;border-color: #003B6F">
		<div class="mainTitle">
			<span>Inbox Delegation</span>
		</div>
		<table class="inputTable">
			<tr>
				<td style="white-space:nowrap">Select User:</td>
				<td>
					<select id="delegated_user" 
						name="delegated_user" 
						style="margin:0px;padding:0px"
						disabled
					>
					<?php foreach($userList AS $uname): ?>
						<?php if(isset($_GET['username']) && $uname == $_GET['username']): ?>
						<option selected value="<?php echo $uname; ?>"><?php echo $uname; ?></option>
						<?php elseif($uname == $user->username && !isSet($_GET['username'])): ?>
						<option selected value="<?php echo $uname; ?>"><?php echo $uname; ?></option>
						<?php else: ?>
						<option value="<?php echo $uname; ?>"><?php echo $uname; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td style="white-space:nowrap">Status:</td>
				<td>
					<select id="status"
						name="status"
						style="margin:0px;padding:0px"
						disabled
					>
					<?php foreach($statusArr AS $status): ?>
						<option value="<?php echo $status; ?>"><?php echo $status; ?></option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td style="white-space:nowrap">Comments:</td>
				<td>
					<textarea id="comments" 
						name="comments" 
						rows="4" 
						cols="20" 
						disabled></textarea>
				</td>
			</tr>
		</table>
		<div style="text-align:center">
			<input type="submit" 
				id="btnDelegate"
				name="btnDelegate" 
				value="Delegate" 
				disabled
			/>
			<input type="button" 
				id="btnCnl"
				name="btnCnl" 
				value="Cancel" 
				onclick="toggleDelegateDiv()" 
			/>
		</div>
	</div>
<?php
}

function displayAddDocument() {
?>
	<div style="border-style:double;border-width: 5px;border-color: #003B6F" id="addDocOuterDiv">
		<div class="mainTitle">
			<span>Add Document</span>
		</div>
		<div id="addDocumentDiv">
		</div>
		<div style="text-align:center">
			<input type="button" 
				id="addDocumentBtn"
				name="addDocumentBtn" 
				value="Add" 
				onclick="addNewDoc()"
			/>
			<input type="button" 
				id="addDocumentCnl"
				name="addDocumentCnl" 
				value="Cancel" 
				onclick="cancelAddDoc()"
			/>
		</div>
		<div id="addDocErr" class="error">&nbsp;</div>
	</div>
<?php
}

function toggleSearchPanelView($user,$toggle) {
	global $trans;
	$min = "search panel minimized";
	$max = "search panel maximized";
   	$userSettings = new Usrsettings($user->username,$user->db_name);
   	$userSettings->set('searchPanelView',$toggle);    

   	if($toggle > 0) {
      	$mess = $min; 
   	} else {
       	$mess = $max; 
  	}
   	echo $mess;
}

function toggleInboxView($user,$toggle) {
   	$userSettings = new Usrsettings($user->username,$user->db_name);
   	$userSettings->set('inboxView',$toggle);    

   	if($toggle > 0) {
      	$mess = "Inbox screen Minimized";
   	} else {
       	$mess = "Inbox screen Maximized";
  	}
   	echo $mess;
}

function toggleOpenNewWindow ($user) {
	$userSettings = new Usrsettings ($user->username, $user->db_name);
	$showNew = $userSettings->get ('showNewInbox');
	if ($showNew == '1') {
		$userSettings->set ('showNewInbox', '0');
	} else {
		$userSettings->set ('showNewInbox', '1');
	}
}

?>
