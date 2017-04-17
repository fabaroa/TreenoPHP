<?php
include_once '../settings/settings.php';
include_once '../lib/tabFuncs.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/mime.php';
include_once '../energie/energiefuncs.php';
include_once '../lib/odbc.php';
include_once '../lib/utility.php';
include_once '../lib/sagWS.php';
include_once '../lib/PDF.php';
include_once '../centera/centera.php';
include_once '../classuser.inc';
/*COPY FILES
* TODO ocrtxt subfolders fix permissions when done
* TODO check prepend
* TODO check append
* TODO check file into existing
* TODO
*/

function writeFileFromString( $to_path,$doc_id,$db_object,$username,$cab,$db_name,$DEFS,$fileString,$filename,$tab ){
	/* DEBUG */
	//global $timer;
	//user object
	$db_doc = getDbObject('docutron');
	$gblStt = new Gblstt($db_name,$db_doc);
	$user = new user();
	//save db_object to the user object
	$user->db_object = $db_object;
	//save db_name to the user object
	$user->db_name = $db_name;
	//save the username to the user object
	$user->username = $username;
	//get indexing ordering for particular cabinet for the sett array
	$sett["indexing_ordering_$cab"]	= $gblStt->get("indexing_ordering_$cab");
	//get the default system indexing ordering for the sett array
	$sett["indexing_ordering"]	= $gblStt->get("indexing_ordering");
	//check to see if centera is enabled
	$centeraModule = check_enable('centera', $user->db_name);
	//need to lock the table here
	lockTables($db_object,array($cab."_files",$cab,'audit'));
	//get list of existing files
	//error_log("in function writefilefromstring lib/indexing2.php");
	$tmpFiles = getTableInfo($db_object, $cab.'_files', array('subfolder', 'filename'),
	array('doc_id' => (int) $doc_id, 'filename' => 'IS NOT NULL'), 'queryAll');
	$existingFiles = array ();
	foreach($tmpFiles as $myFile) {
		if(!$myFile['subfolder']) {
			$subfolder = 'main';
		} else {
			$subfolder = $myFile['subfolder'];
		}
		if(!isset($existingFiles[$subfolder])) {
			$existingFiles[$subfolder] = array ();
		}
		$existingFiles[$subfolder][] = strtolower($myFile['filename']);
	}
	if(!isset($existingFiles['main'])) {
		$existingFiles['main'] = array ();
	}
	$filesList		= array($filename);
	$foldersList	= array();
	$fileSize = strlen($fileString);
	$fileCreated = 0;

	$type = $gblStt->get('fileFormat');
	$orderingOffset=1;
	/* MAIN FILES*/
	$sqlarr = array ();
	writeFileAndSQL($fileString,
	$filesList,
	$orderingOffset,
	$tab,
	$sqlarr,
	$to_path,
	$fileSize,
	$fileCreated,
	$doc_id,
	$username,
	$db_name,
	$existingFiles['main'],
	$centeraModule);
	addTabsToFolderIndexing($db_name, $cab, $doc_id, $to_path, $sqlarr, $gblStt);
	unlockTables($db_object);
	$table = $cab."_files";
	for( $i=0; $i<sizeof($sqlarr);$i++ ){
		$db_object->extended->autoExecute( $table, $sqlarr[$i] );
	}
	allowWebWrite ($to_path, $DEFS);
	$db_doc->disconnect();
	return true;
}

function copyFiles( $from_path, $to_path, $doc_id, $db_object, $username, $cab,
$db_name, $existing, $settObj, $DEFS ){
	//error_Log("cz 2015-11-09 copyFiles() from_path: ".$from_path.", to_path: ".$to_path);
////error_log("copyFiles() existing=".$existing);
	/* DEBUG */
	//global $timer;
	$user = new user();
	$user->db_object = $db_object;
	$user->db_name = $db_name;
	if ($username == "") $username="unknown";
	$user->username = $username;
	////error_log("user: $username");
	$sett["indexing_ordering_$cab"]	= $settObj->get("indexing_ordering_$cab");
	$sett["indexing_ordering"]	= $settObj->get("indexing_ordering");
	$centeraModule = check_enable('centera', $user->db_name);
	//need to lock the table here
	lockTables($db_object,array($cab."_files",$cab,'audit'));
	
	$tmpFiles = getTableInfo($db_object, $cab.'_files', array('subfolder', 'filename'),
	array('doc_id' => (int) $doc_id, 'filename' => 'IS NOT NULL'), 'queryAll');
	$existingFiles = array ();
	foreach($tmpFiles as $myFile) {
		if(!$myFile['subfolder']) {
			$subfolder = 'main';
		} else {
			$subfolder = $myFile['subfolder'];
			//MC 3/2/2017 below added because we were looking at subfolders that were caps and lowercase twice and overwriting same named files
			$subfolder = strtoupper($subfolder);
		}
		if(!isset($existingFiles[$subfolder])) {
			$existingFiles[$subfolder] = array ();
		}
		$existingFiles[$subfolder][] = strtolower($myFile['filename']);
	}
	if(!isset($existingFiles['main'])) {
		$existingFiles['main'] = array ();
	}
	$filesList		= array();
	$foldersList	= array();
	$fileSize = 0;
	$fileCreated = 0;
	////error_log("copyFiles() pass 1");
	getFilesAndFolders( $from_path, $filesList, $foldersList, $fileSize, $fileCreated );

	$db_doc = getDbObject('docutron');
	//	$gblStt = new Gblstt($db_name,$db_doc);
	$type = $settObj->get('fileFormat');
	if($type ||	check_enable("lite",$db_name)) {
		$tmpDir = getUniqueDirectory($from_path);
		$tiffList = array();
		$newFilesList = array();
		foreach($filesList AS $file) {
			if(getMimeType($from_path."/".$file, $DEFS) == 'image/tiff') {
				rename($from_path."/".$file,$tmpDir.$file);
				$tiffList[] = $tmpDir.$file;
			}
		}
		if(count($tiffList)) {
			if(!$type || $type == "pdf") {
				createPDFFromTiffs($tiffList,$tmpDir,NULL,$from_path);
			} else {
				createPDFFromTiffs($tiffList,$tmpDir,NULL,$from_path,"MTIFF");
			}
		} else {
			delDir($tmpDir);
		}

		if(count($foldersList)) {
			foreach($foldersList AS $subfolder) {
				$filesList	= array();
				$fList		= array();
				$fileSize = 0;
				$fileCreated = 0;
				$sub_path = "$from_path/$subfolder";
				////error_log("copyFiles() pass 2");
				getFilesAndFolders($sub_path,$filesList,$fList,$fileSize,$fileCreated);

				$tmpDir = getUniqueDirectory($sub_path);
				$tiffList = array();
				$newFilesList = array();
				foreach($filesList AS $file) {
					if(getMimeType($sub_path."/".$file, $DEFS) == 'image/tiff') {
						rename($sub_path."/".$file,$tmpDir.$file);
						$tiffList[] = $tmpDir.$file;
					}
				}
				if(count($tiffList)) {
					if(!$type || $type == "pdf") {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$sub_path);
					} else {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$sub_path,"MTIFF");
					}
				} else {
					delDir($tmpDir);
				}
			}
		}

		$filesList		= array();
		$foldersList	= array();
		$fileSize = 0;
		$fileCreated = 0;
		////error_log("copyFiles() pass 3");
		getFilesAndFolders( $from_path, $filesList, $foldersList, $fileSize, $fileCreated );
	}
	$indexing_pref = 1; //default prepend
	if( $existing=="On" )
		$orderingOffset=calculateOffSet2($cab,$sett,$doc_id,$db_object,sizeof($filesList), $indexing_pref);
	else
		$orderingOffset=1;
	/* MAIN FILES*/
	$sqlarr = array ();
	copyAndSQL2(	$filesList,$orderingOffset,NULL,$sqlarr,$to_path,$from_path,$fileSize,$fileCreated,$doc_id,$username, $db_name,$existingFiles['main'], $centeraModule, $indexing_pref);
	addTabsToFolderIndexing($db_name, $cab, $doc_id, $to_path, $sqlarr, $settObj);
	/* LOOP FOR SUBFOLDERS */
	/*THIS HAS NOT BEEN TESTED*/
	$fl = $foldersList;
	if( sizeof( $fl ) > 0 )
	{
		for( $j=0; $j < sizeof($fl); $j++ )
		{
			//may have to assume something else
			$documentName = str_replace("_"," ","{$fl[$j]}"); //new code
			//error_log($documentName);
			$t_dir = "$to_path/{$fl[$j]}"."1"; //asuming that documents type always start with 1
			if( !is_dir( $t_dir ) ) {
				mkdir( $t_dir, 0755 );
				//NEW CODE for Treeno v4
				//fma changed to $db_object $db_dept = getDbObject($db_name);
				$query = "SELECT document_type_name,document_table_name FROM document_type_defs where document_type_name='".$documentName."'";
				$getDocTypeRes = $db_object->queryAll($query);
				if (count($getDocTypeRes)==0)
				{
					error_log("***AnyBarcode didn't exist ".$db_name."|".$query);
					continue;
					//document_Type_name doesn't exist
				}
				//error_log(print_r($getDocTypeRes[0], true));
				$idb=0;
				while (dbErrLocal($getDocTypeRes) && $idb<5)
				{
					$db_object->disconnect();
					$db_object = getDbObject($db_name);
					$getDocTypeRes = $db_dept->queryAll($query);
					error_log("***AnyBarcode retry # ".$idb."** ".$db_name."|".$query);
					++$idb;
					sleep(10);
				}
				$documentName=$getDocTypeRes[0]['document_table_name']; //need to get the document
				$cabQuery="select departmentid from departments where real_name='".$cab."'";
				$getCabIDRes = $db_object->queryAll($cabQuery);
				//$db_dept->disconnect();
				$cabinetID = $getCabIDRes[0]['departmentid'];
				$indices = array();
				if ($username=="unknown")
				{
					$tempusername="admin";
				}
				else
				{
					$tempusername=$username;
				}
				$subfolderID = createDocumentInfoHERE($db_name,$cabinetID,$doc_id,$documentName,$indices,$tempusername, $db_doc, $db_object);
				//*******End of NEW CODE
				//addSubfolderSQL( $sqlarr, $fl[$j], $doc_id, $username );
				$foldername = getCabIndexArr($doc_id,$cab,$db_object);
				$message = "TAB missing during indexing, ";
				$message .= "Cabinet: $cab, Folder: ".implode("|",$foldername)."; Tab Name: ".$fl[$j];
				$insertArr = array( "username"  => $username,
				"datetime"  => date("Y-m-d G:i:s"),
				"info"      => $message,
				"action"    => "tab created" );
				$res = $db_object->extended->autoExecute('audit',$insertArr);
				dbErr($res);
			}
		}
	}
	for( $j=0; $j<sizeof( $fl ); $j++ ){
		unset( $filesList );
		unset( $fileSize );
		unset( $fileCreated );
		//cz 2015-11-09: strtolower causes problem - inconsistent with how existingFiles is built and how C#-side uploading files
		//$subfolder	= strtolower($fl[$j]);
		$subfolder	= $fl[$j];
		$docuSubfolder=$subfolder."1";
		$arrayEmpty = array();
		$filesList = array ();
		////error_log("copyFiles() pass 4");
		getFilesAndFolders("$from_path/$subfolder",$filesList,$arrayEmpty,$fileSize,$fileCreated);
		if( $existing=="On" )
			$orderingOffset=calculateOffSet($cab,$sett,$doc_id,$db_object,sizeof($filesList));
		else
			$orderingOffset=1;
		//add entry for each subfolder in the sqlarr
		//do the exact same thing as for the main folder for each subfolder
		if(!isset($existingFiles[$docuSubfolder])) {
			$existingFiles[$docuSubfolder] = array ();
		}
		copyAndSQL(	$filesList,$orderingOffset,$docuSubfolder,$sqlarr,"$to_path/$subfolder"."1","$from_path/$subfolder",$fileSize,$fileCreated,$doc_id,$username, $db_name, $existingFiles[$docuSubfolder],$centeraModule);
	}
	unlockTables($db_object);
	$table = $cab."_files";
	for( $i=0; $i<sizeof($sqlarr);$i++ ){
	$db_object->extended->autoExecute( $table, $sqlarr[$i] );
	}
	allowWebWrite ($to_path, $DEFS);
	$db_doc->disconnect();
}
function copyFilesSub( $from_path, $to_path, $doc_id, $db_object, $username, $cab,
 		$db_name, $existing, $settObj, $DEFS ){
		////error_log("copyFilesSub() existing=".$existing);
	/* DEBUG */
	//global $timer;
	$user = new user();
	$user->db_object = $db_object;
	$user->db_name = $db_name;
	$user->username = $username;
	$sett["indexing_ordering_$cab"]	= $settObj->get("indexing_ordering_$cab");
	$sett["indexing_ordering"]	= $settObj->get("indexing_ordering");
	$centeraModule = check_enable('centera', $user->db_name);
	//need to lock the table here
	lockTables($db_object,array($cab."_files",$cab,'audit'));
	$tmpFiles = getTableInfo($db_object, $cab.'_files', array('subfolder', 'filename'),
		array('doc_id' => (int) $doc_id, 'filename' => 'IS NOT NULL'), 'queryAll');
	$existingFiles = array ();
	foreach($tmpFiles as $myFile) {
		if(!$myFile['subfolder']) {
			$subfolder = 'main';
		} else {
			$subfolder = $myFile['subfolder'];
		}
		if(!isset($existingFiles[$subfolder])) {
			$existingFiles[$subfolder] = array ();
		}
		$existingFiles[$subfolder][] = strtolower($myFile['filename']);
	}
	if(!isset($existingFiles['main'])) {
		$existingFiles['main'] = array ();
	}
	$filesList		= array();
	$foldersList	= array();
	$fileSize = 0;
	$fileCreated = 0;
	////error_log("copyFilesSub() pass 1");
	getFilesAndFolders( $from_path, $filesList, $foldersList, $fileSize, $fileCreated );

//	$gblStt = new Gblstt($db_name,$db_doc);
	$type = $settObj->get('fileFormat');
	if($type ||	check_enable("lite",$db_name)) {
		$tmpDir = getUniqueDirectory($from_path); 
		$tiffList = array();
		$newFilesList = array();
		foreach($filesList AS $file) {
			if(getMimeType($from_path."/".$file, $DEFS) == 'image/tiff') {
				rename($from_path."/".$file,$tmpDir.$file);
				$tiffList[] = $tmpDir.$file;
			}
		}
		if(count($tiffList)) {
			if(!$type || $type == "pdf") {
				createPDFFromTiffs($tiffList,$tmpDir,NULL,$from_path);
			} else {
				createPDFFromTiffs($tiffList,$tmpDir,NULL,$from_path,"MTIFF");
			}
		} else {
			delDir($tmpDir);
		}

		if(count($foldersList)) {
			foreach($foldersList AS $subfolder) {
				$filesList	= array();
				$fList		= array();
				$fileSize = 0;
				$fileCreated = 0;
				$sub_path = "$from_path/$subfolder";
				////error_log("copyFilesSub() pass 2");
				getFilesAndFolders($sub_path,$filesList,$fList,$fileSize,$fileCreated);

				$tmpDir = getUniqueDirectory($sub_path); 
				$tiffList = array();
				$newFilesList = array();
				foreach($filesList AS $file) {
					if(getMimeType($sub_path."/".$file, $DEFS) == 'image/tiff') {
						rename($sub_path."/".$file,$tmpDir.$file);
						$tiffList[] = $tmpDir.$file;
					}
				}
				if(count($tiffList)) {
					if(!$type || $type == "pdf") {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$sub_path);
					} else {
						createPDFFromTiffs($tiffList,$tmpDir,NULL,$sub_path,"MTIFF");
					}
				} else {
					delDir($tmpDir);
				}
			}
		}

		$filesList		= array();
		$foldersList	= array();
		$fileSize = 0;
		$fileCreated = 0;
		////error_log("copyFilesSub() pass 3");
		getFilesAndFolders( $from_path, $filesList, $foldersList, $fileSize, $fileCreated );
	}

	if( $existing=="On" )
		$orderingOffset=calculateOffSet($cab,$sett,$doc_id,$db_object,sizeof($filesList));
	else
		$orderingOffset=1;
	/* MAIN FILES*/
	$sqlarr = array ();
	copyAndSQL(	$filesList,$orderingOffset,NULL,$sqlarr,$to_path,$from_path,$fileSize,$fileCreated,$doc_id,$username, $db_name,$existingFiles['main'], $centeraModule);
	addTabsToFolderIndexing($db_name, $cab, $doc_id, $to_path, $sqlarr, $settObj);
	/* LOOP FOR SUBFOLDERS*/
	$fl = $foldersList;
	if( sizeof( $fl ) > 0 )
	{
		for( $j=0; $j < sizeof($fl); $j++ )
		{
			$t_dir = "$to_path/{$fl[$j]}";
			if( !is_dir( $t_dir ) ) {
				mkdir( $t_dir, 0755 );
				addSubfolderSQL( $sqlarr, $fl[$j], $doc_id, $username );
				$foldername = getCabIndexArr($doc_id,$cab,$db_object);
                $message = "subfolder missing during indexing, ";
                $message .= "Cabinet: $cab, Folder: ".implode("|",$foldername)."; Tab Name: ".$fl[$j];
                $insertArr = array( "username"  => $username,
                                    "datetime"  => date("Y-m-d G:i:s"),
                                    "info"      => $message,
                                    "action"    => "tab created" );
                $res = $db_object->extended->autoExecute('audit',$insertArr);
                dbErr($res);
			}
		}
	}
	for( $j=0; $j<sizeof( $fl ); $j++ ){
		unset( $filesList );
		unset( $fileSize );
		unset( $fileCreated );
		$subfolder	= $fl[$j];
		$arrayEmpty = array();
		$filesList = array ();
		////error_log("copyFilesSub() pass 4");
		getFilesAndFolders("$from_path/$subfolder",$filesList,$arrayEmpty,$fileSize,$fileCreated);
		//$filesList = array_reverse($filesList);
		////error_log("copyFilesSub() pass 5. filesList - ".print_r($filesList,true));
		$indexing_pref = 1; //default prepend
		if( $existing=="On" )
			$orderingOffset=calculateOffSet2($cab,$sett,$doc_id,$db_object,sizeof($filesList), $indexing_pref);
		else
			$orderingOffset=1;
		//add entry for each subfolder in the sqlarr
		//do the exact same thing as for the main folder for each subfolder
		if(!isset($existingFiles[$subfolder])) {
			$existingFiles[$subfolder] = array ();
		}
		copyAndSQL2(	$filesList,$orderingOffset,$subfolder,$sqlarr,"$to_path/$subfolder","$from_path/$subfolder",$fileSize,$fileCreated,$doc_id,$username, $db_name, $existingFiles[$subfolder],$centeraModule, $indexing_pref);
	}
	unlockTables($db_object);
	$table = $cab."_files";
	for( $i=0; $i<sizeof($sqlarr);$i++ ){
		$db_object->extended->autoExecute( $table, $sqlarr[$i] );
	}
	allowWebWrite ($to_path, $DEFS);
}

function addSubfolderSQL( &$sqlarr, $subfolder, $doc_id, $username )
{
	$newArr	= array( 	"doc_id"		=> (int)$doc_id,
	"subfolder"		=> $subfolder,
	"date_created"		=> date("Y-m-d H:i:s"),
	"who_indexed"		=> $username,
	"file_size"		=> (int)4096,
	"parent_id"		=> (int)0 );
	$sqlarr[] = $newArr;
}

function writeFileAndSQL($fileString,
$fileList,
$orderingOffset,
$subfolder,
&$sqlarr,
$to_path,
$fileSize,
$fileCreated,
$doc_id,
$username,
$department,
$existingFiles,
$centeraModule=0)
{
	//copy file to its location use rename and check if success
	$str	= $fileList[0];
	$dt		= date( "Y-m-d H:i:s", $fileCreated[$str] );
	$destinationFile = "$to_path/$str";
	//make sure the file doesn't exist!
	$filename=$str;
	$existingFiles = array_map("strtolower", $existingFiles);
	for( $ii=1; in_array(strtolower($filename), $existingFiles); $ii++ ){
		$filename = "$ii-$str";
	}
	$destinationFile = '';
	if( $subfolder ){
		$destinationFile = "$to_path/$subfolder/$filename";
	} else {
		$destinationFile = "$to_path/$filename";
	}
	$fp = fopen( $destinationFile, 'w+' );
	if( fwrite( $fp, $fileString ) ){
		$ordering			= $orderingOffset;
		//add entry to extended->autoExecuteArray for files table
		$newArr	= array( 	"filename"		=> $filename,
		"doc_id"		=> (int)$doc_id,
		"parent_filename"	=> $filename,
		"ordering"		=> (int)$ordering,
		"subfolder"		=> $subfolder,
		"date_created"		=> $dt,
		"who_indexed"		=> $username,
		"file_size"		=> (int)$fileSize[$str],
		"parent_id"		=> (int)0 );
		//	if(check_enable('centera',$user->db_name) && $settObj->get('centera_'.$cab) == 1) {
		//		//error_log("INSERTING INTO CENTERA");
		//		$newArr['ca_hash'] = centput($destinationFile, $DEFS['CENT_HOST'],$user,$cab);
		//	}
		$sqlarr[] = $newArr;
	}
	fclose( $fp );
}

function copyAndSQL($filesList,
$orderingOffset,
$subfolder,
&$sqlarr,
$to_path,
$from_path,
$fileSize,
$fileCreated,
$doc_id,
$username,
$department,
$existingFiles,
$centeraModule=0)
{
////error_log("copyAndSQL()");
	for( $i=0; $i < sizeof( $filesList ); $i++ ){
		//copy file to its location use rename and check if success
		$str	= $filesList[$i];
		$dt		= date( "Y-m-d H:i:s", $fileCreated[$str] );
		if( $str!= "INDEX.DAT" && $str != "INDEX.DATRW" && $str != "Thumbs.db" ){
			$destinationFile = "$to_path/$str";
			//make sure the file doesn't exist!
			$filename=$str;
			$existingFiles = array_map("strtolower", $existingFiles);
			for( $ii=1; in_array(strtolower($filename), $existingFiles); $ii++ ){
				$filename = "$ii-$str";
			}
			//MC 3/31/2017 (above code is redundant )this code added to look at the physical disk because of errors with php array case sensitivity and overwritten files
			$increment=1;
			while(file_exists("$to_path/$filename")){
				error_log("INDEXING2.php line ".__LINE__."  Aren't you glad we changed the code here and didn't overwrite this file?-->$to_path/$filename");
				$filename = "$increment-$str";
				$increment++;
			}
			$destinationFile = "$to_path/$filename";
			if( copy( "$from_path/$str", $destinationFile ) ){
				$ordering			= $i + $orderingOffset;
				//add entry to extended->autoExecuteArray for files table
				$newArr	= array( 	"filename"		=> $filename,
				"doc_id"		=> (int)$doc_id,
				"parent_filename"	=> $filename,
				"ordering"		=> (int)$ordering,
				"subfolder"		=> $subfolder,
				"date_created"		=> $dt,
				"who_indexed"		=> $username,
				"file_size"		=> (int)$fileSize[$str],
				"parent_id"		=> (int)0 );
				//	if(check_enable('centera',$user->db_name) && $settObj->get('centera_'.$cab) == 1) {
				//		//error_log("INSERTING INTO CENTERA");
				//		$newArr['ca_hash'] = centput($destinationFile, $DEFS['CENT_HOST'],$user,$cab);
				//	}
				$sqlarr[] = $newArr;
			}
		}
	}
}

function copyAndSQL2($filesList,
$orderingOffset,
$subfolder,
&$sqlarr,
$to_path,
$from_path,
$fileSize,
$fileCreated,
$doc_id,
$username,
$department,
$existingFiles,
$centeraModule=0,
$indexing_pref=1)
{
////error_log("copyAndSQL2() indexing_pref: ".$indexing_pref);
	for( $i=0; $i < sizeof( $filesList ); $i++ ){
		//copy file to its location use rename and check if success
		$str	= $filesList[$i];
		$dt		= date( "Y-m-d H:i:s", $fileCreated[$str] );
		if( $str!= "INDEX.DAT" && $str != "INDEX.DATRW" && $str != "Thumbs.db" ){
			$destinationFile = "$to_path/$str";
			//make sure the file doesn't exist!
			$filename=$str;
			$existingFiles = array_map("strtolower", $existingFiles);
			//error_log("copyAndSQL2:".print_r($existingFiles,true));
			for( $ii=1; in_array(strtolower($filename), $existingFiles); $ii++ ){
				$filename = "$ii-$str";
			}
			//MC 3/31/2017 (above code is redundant )this code added to look at the physical disk because of errors with php array case sensitivity and overwritten files
			$increment=1;
			while(file_exists("$to_path/$filename")){
				error_log("INDEXING2.php line ".__LINE__."  Aren't you glad we changed the code here and didn't overwrite this file?-->$to_path/$filename");
				$filename = "$increment-$str";
				$increment++;
			}
			$destinationFile = "$to_path/$filename";
			
			if( copy( "$from_path/$str", $destinationFile ) ){
				if( $indexing_pref==0 ) {
					$ordering			= $i + $orderingOffset;
				}
				else {
					$ordering			= $orderingOffset - $i;
				}
				//add entry to extended->autoExecuteArray for files table
				$newArr	= array( 	"filename"		=> $filename,
				"doc_id"		=> (int)$doc_id,
				"parent_filename"	=> $filename,
				"ordering"		=> (int)$ordering,
				"subfolder"		=> $subfolder,
				"date_created"		=> $dt,
				"who_indexed"		=> $username,
				"file_size"		=> (int)$fileSize[$str],
				"parent_id"		=> (int)0 );
				//	if(check_enable('centera',$user->db_name) && $settObj->get('centera_'.$cab) == 1) {
				//		//error_log("INSERTING INTO CENTERA");
				//		$newArr['ca_hash'] = centput($destinationFile, $DEFS['CENT_HOST'],$user,$cab);
				//	}
				$sqlarr[] = $newArr;
			}
		}
	}
}

function calculateOffSet( $cab, $sett, $doc_id, $db_object, $fileCount )
{
////error_log("calculateOffSet()");
	$tbl = $cab."_files";
	if( $sett["indexing_ordering_$cab"]!="" ){
		$indexing_pref=$sett["indexing_ordering_$cab"];
	} else if( $sett["indexing_ordering"]!="" ){
		$indexing_pref = $sett['indexing_ordering'];
	} else{
		$indexing_pref = 1;
	}
	//indexing_pref==1 means prepend the files in folders
	//indexing_pref==0 means append the files in folders
	if( $indexing_pref==0 ) {
		//calculate offset for prepending by selecting the min orderingnumber
		$minimum = getTableInfo($db_object,$tbl,array('MIN(ordering)'),array('doc_id'=>(int)$doc_id,'display'=>1),'queryOne');
		if( $minimum=="" )
		return 1;//start at 1
		else
		return $minimum - $fileCount;
	} else {
		//calc the offset for appending by selecting the max ordering number!!!!
		$maximum = getTableInfo($db_object,$tbl,array('MAX(ordering)'),array('doc_id'=>(int)$doc_id,'display'=>1),'queryOne');
		if( $maximum=="" )
		return 1;//start at 1
		else
		return $maximum + $fileCount;
	}
}

function calculateOffSet2( $cab, $sett, $doc_id, $db_object, $fileCount, &$indexing_pref )
{
	////error_log("indexing2.php::calculateOffSet2() - cab: ".$cab.", doc_id: ".$doc_id.", fileCount: ".$fileCount);
	$tbl = $cab."_files";
	if( $sett["indexing_ordering_$cab"]!="" ){
		$indexing_pref=$sett["indexing_ordering_$cab"];
	} else if( $sett["indexing_ordering"]!="" ){
		$indexing_pref = $sett['indexing_ordering'];
	} else{
		$indexing_pref = 1;
	}
	//indexing_pref==1 means prepend the files in folders
	//indexing_pref==0 means append the files in folders
	if( $indexing_pref==0 ) {
		//calculate offset for prepending by selecting the min orderingnumber
		$minimum = getTableInfo($db_object,$tbl,array('MIN(ordering)'),array('doc_id'=>(int)$doc_id,'display'=>1),'queryOne');
		if( $minimum=="" )
		return 1;//start at 1
		else
		{
			$offset = $minimum - $fileCount;
			////error_log("indexing2.php::calculateOffSet2() - indexing_pref: ".$indexing_pref.", offset: ".$offset);
			return $minimum - $fileCount;
		}
	} else {
		//calc the offset for appending by selecting the max ordering number!!!!
		$maximum = getTableInfo($db_object,$tbl,array('MAX(ordering)'),array('doc_id'=>(int)$doc_id,'display'=>1),'queryOne');
		if( $maximum=="" )
			return $fileCount;//start at 1
		else
		{
			$offset = $maximum + $fileCount;
			////error_log("indexing2.php::calculateOffSet2() - indexing_pref: ".$indexing_pref.", offset: ".$offset);
			return $maximum + $fileCount;
		}
	}
}

function getFilesAndFolders( $from_path, &$filesList, &$foldersList, &$fileSize, &$fileCreated )
{
////error_log("getFilesAndFolders()");
	$dh = opendir( $from_path );
	while( $str = readdir( $dh ) )
	{
		if( $str != "." && $str !=".." )
		{
			if( is_dir( $from_path."/".$str ) )
			$foldersList[] = $str;
			else
			{
				$filesList[]		= $str;//filename
				$st = stat( $from_path."/".$str );
				if(!is_array($fileSize)) {
					$fileSize = array ();
				}
				$fileSize[$str]		= $st[7];//file size in bytes
				if(!is_array($fileCreated)) {
					$fileCreated = array ();
				}
				$fileCreated[$str]	= $st[10];//ctime of file
			}
		}
	}
	usort( $filesList, 'strnatcasecmp' );
	usort( $foldersList, 'strnatcasecmp' );
	////error_log("getFilesAndFolders() filesList - ".print_r($filesList, true));
	////error_log("getFilesAndFolders() foldersList - ".print_r($foldersList, true));
}

function addIndexingCommand( $command, $db_raw, $db_object, $id, $cab ) {
	//add to the settings table
	$insertArr = array('k'=>'upForIndexing','value'=>$command);
	$db_raw->extended->autoExecute('settings',$insertArr);
	//set "up for indexing flag"
	$updateArr = array('upforindexing'=>1);
	$whereArr = array('id'=>(int)$id);
	updateTableInfo($db_object,$cab."_indexing_table",$updateArr,$whereArr);
}

function addTabsToFolderIndexing($db_name, $cabinetName, $docID, $location, &$sqlarr, $gblStt) {
	$tabs = $gblStt->get($cabinetName.'_tabs');
	$allTabs = array();
	if($tabs) {
		$allTabs = explode(',', $tabs);
		foreach($allTabs as $myTab) {
			if(!is_dir($location.'/'.$myTab)) {
				mkdir($location.'/'.$myTab);
				$sqlarr[] = array(
				'doc_id'	=> (int)$docID,
				'subfolder'	=> $myTab
				);
			}
		}
	}
}

function searchAutoComplete($db, $autoCompleteTable, $searchField, $searchTerm, $cabinet, $db_doc, $location='', $department, $gblStt) {
	if ($autoCompleteTable == "odbc_auto_complete") {
		$transInfo = getTableInfo($db, 'odbc_auto_complete', array(),
		array('cabinet_name' => $cabinet), 'queryRow');
		$odbcDBObj = getODBCDbObject($transInfo['connect_id'], $db_doc);
		if(!PEAR::isError($odbcDBObj) or $odbcDBObj==1) {
			$allIndices = getCabinetInfo($db, $cabinet);
			$row = getODBCRow($odbcDBObj, $searchTerm, $cabinet, $db, $location, $department, $gblStt);
			$tmpArray = array();
			foreach($row as $myKey => $myTmp) {
				if(in_array($myKey, $allIndices)) {
					$tmpArray[$myKey] = $myTmp;
				}
			}
			$row = $tmpArray;
		} else {
			echo "cannot establish connection to odbc database";
			return false;
		}
	} elseif($autoCompleteTable == 'sagitta_ws_auto_complete') {
		$row = getSagRow($cabinet, $searchTerm, $department);
	} else {
		$res = getTableInfo($db, $autoCompleteTable, array(), array($searchField => $searchTerm));
		$row = $res->fetchRow();
		if(!is_array($row)) {
			$row = array ();
		}
	}
	return $row;
}

function getAutoCompleteRow(&$connInfo, $uniqueVal, $department, $gblStt) {
	if($connInfo['type'] == 'odbc') {
		$row = getODBCRow($connInfo['db'], $uniqueVal, $connInfo['cabinet'],
		$connInfo['db_dept'], '', $department, $gblStt);
	} elseif($connInfo['type'] == 'sagitta_ws') {
		$row = getSagRow($connInfo['cabinet'], $uniqueVal, $department);
	} else {
		$acInfo = getTableInfo($connInfo['db'], $connInfo['table'],
		array(), array($connInfo['field'] => $uniqueVal));
		$row = $acInfo->fetchRow();
	}
	if(!is_array($row)) {
		$row = array ();
	}
	return $row;
}
function createDocumentInfoHERE($department,$cabinetID,$docID,$documentName,$indices,$userName, $db_doc,$db_dept)
{
	//error_log("in function createDocumentInfoHERE");
	$cabName = hasAccess($db_dept, $userName, $cabinetID, true);
	if($cabName !== false) {
		//error_log("in function createDocumentInfoHERE: $documentName");
		$sArr = array('id','document_type_name');
		$whereArr = array('document_table_name' => $documentName);
		$typeDefsID = getTableInfo($db_dept,'document_type_defs',$sArr,$whereArr,'queryRow');
		$docType = $typeDefsID['document_type_name'];
		$tabName = "";
		//		unlockTables($db_dept);
		lockTables($db_dept,array($documentName));
		$subfolderID = createTabForDocumentHERE($db_dept,$department,$cabName,$docID,$docType,$tabName, $db_doc,false);
		//error_log("in function createDocumentInfoHERE subfolderid: $subfolderID");
		$date = date('Y-m-d G:i:s');
		$insertArr = array( "cab_name"      => $cabName,
		"doc_id"        => (int)$docID,
		"file_id"       => (int)$subfolderID,
		"date_created"  => $date,
		"date_modified" => $date,
		"created_by"    => $userName );
		$res = $db_dept->extended->autoExecute($documentName,$insertArr);
		dbErrLocal($res);
		$documentID = getTableInfo($db_dept,$documentName,array('MAX(id)'),array(),'queryOne');

		$sArr = array(  'document_id'           => (int)$documentID,
		'document_table_name'   => $documentName);
		$whereArr = array('id' => (int)$subfolderID);
		updateTableInfo($db_dept,$cabName.'_files',$sArr,$whereArr);

		return $subfolderID;
	} else {
		//error_log("in function createDocumentInfoHERE: No Cab Permision $userName");
		return false;
	}
}
function createTabForDocumentHERE($db_dept,$department,$cabName,$docID,$docType,&$name, $db_raw, $mkdir=true) {
//error_log("in function createTabForDocumentHERE".$department);
	global $DEFS;
	$whereArr = array(  'doc_id'    => (int)$docID);
	$loc = getTableInfo($db_dept,$cabName,array('location'),$whereArr,'queryOne');
//error_log("in function createTabForDocumentHERE:".$department.":".$loc);
	if (!$loc) {
		return false;
	}

	$user = new user();

	$docType = str_replace(' ', '_', $docType);
	$docType = $user->replaceInvalidCharacters($docType,"");
	$docType = str_replace("@","",$docType);

	$whereArr = array(  'doc_id'    => (int)$docID,
	'filename'  => 'IS NULL' );
	$tabArr = getTableInfo($db_dept,$cabName.'_files',array('subfolder'),$whereArr,'queryCol');

	$i = 1;
	$name = $docType.$i;
	$tabLoc = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
	$tempTabLoc = $tabLoc."/".$name;
	if ($mkdir) {
		//$i = mt_rand( 10000000,99999999 );
		while(in_array($docType.$i,$tabArr) OR file_exists($tempTabLoc)) {
			$i = mt_rand( 10000000,99999999 );
			$name = $docType.$i;
			$tempTabLoc = $tabLoc."/".$name;
		}
	}
	$tabLoc = $tempTabLoc;
	$insertArr = array( 'doc_id'		=> (int)$docID,
	'subfolder'		=> $name,
	'date_created'	=> date('Y-m-d G:i:s'),
	'file_size'		=> 4096 );
	$res = $db_dept->extended->autoExecute($cabName.'_files',$insertArr);
	$retry=0;
	dbErrLocal($res);
	//loop for possible problems with doctype headers.
	/*
	error_log("*** Ready To autoExecute for doctype ***");
	while (false===dbErrLocal($res) && $retry < 10)
	{
		++$retry;
		$db_dept->disconnect();
		sleep(10);
		$db_dept = getDbObject ($department);
		$res = $db_dept->extended->autoExecute($cabName.'_files',$insertArr);
	}
*/
	$whereArr = array(  'doc_id'    => (int)$docID,
	'subfolder' => $name );
	$subfolderID = getTableInfo($db_dept,$cabName.'_files',array('MAX(id)'),$whereArr,'queryOne');

	if($mkdir) {
		mkdir($tabLoc, 0777);
	}
	$updateArr = array('quota_used'=>'quota_used+4096');
	$whereArr = array('real_department'=> $department);
	updateTableInfo($db_raw,'licenses',$updateArr,$whereArr,1);
	//$db_raw->disconnect();
	return $subfolderID;
}
function dbErrLocal(&$res,$kill=1) {
	global $DEFS;
	if(PEAR::isError($res)) {
		$bt = $res->backtrace;
		$date = date('Y-m-d G:i:s');
		$errLine = $date .' DB ERROR1: message: '.$res->getDebugInfo().', calling file: ' .
				$bt[count($bt) - 1]['file'].', line: '.$bt[count($bt) - 1]['line'];
		error_log($errLine);
		mail('fabaroa@treenosoftware.com', 'DB Error', print_r($res,true));
		if(count($_SESSION) > 1) {
			
		}
		if( $kill )
		die();
		return true;
	}
	return false;
}
?>
