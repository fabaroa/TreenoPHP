<?php


function createFilesTable($db_object) {
	//check for null, because we need to create it if it doesn't exist
	$temp_table = createMoveFilesTempTable($db_object);
	return $temp_table;
}

function getOrderType( $department, $cabinet, $doc_id, $tab, $username, $prepend, $db_doc, $db_object) {
	//create function
	$settings = new GblStt($department, $db_doc);
	$ordering = $settings->get("indexing_ordering_$cabinet") ;
	if($ordering == ""){
		$usrStt = new Usrsettings( $username, $department ) ;
		$ordering = $usrStt->get('indexing_ordering') ;
		if($ordering == "")
			$ordering = 0 ; // Default to prepend if none
	}

	if( $ordering == 1 ) {
		$orderType = "MAX(ordering)+1";
	} else {
		$orderType = "MIN(ordering)-$prepend";
	}

	$whereArr = array('doc_id'=>(int)$doc_id);
	if( $tab ) {
		$whereArr['subfolder'] = $tab;
        } else {
		$whereArr['subfolder'] = 'IS NULL';
        }
	return (getTableInfo($db_object,$cabinet."_files",array($orderType),$whereArr,'queryOne'));
}

function getNewFilename( $filename, &$destFileList ,$path=NULL) {
	$newFilename = $filename;
	$fnameArr = explode(".",$filename);
	$temp = $fnameArr[0];
	$num = substr(strrchr($temp,'-'),1);
	if( is_numeric( $num ) ) {
		$pos = strrpos($temp,"-");
		$temp = substr( $temp, 0, $pos); 
		$ct = $num[sizeof($num)-1] + 1;
	} else {
		$ct = 1;
	}
	//$ct = 1;
	while( in_array( strtolower($newFilename), $destFileList ) ) {
        $fnameArr[0] = $temp."-$ct";
        $newFilename = implode('.', $fnameArr);
		$ct++;
	}
	if ($path) {
		while( is_file( $path."/".$newFilename ) ) {
	        $fnameArr[0] = $temp."-$ct";
	        $newFilename = implode('.', $fnameArr);
			$ct++;
		}
	}
	$destFileList[] = $newFilename;
	return $newFilename;
}

function getVersFilename( $filename, $fileInfo, &$destFileList ) {
	$newFilename = $filename;
	$fnameArr = explode(".",$filename);
	$temp = $fnameArr[0];
	$num = substr(strrchr($temp,'-'),1);
	if( is_numeric( $num ) ) {
		$pos = strrpos($temp,"-");
		$temp = substr( $temp, 0, $pos); 
		$ct = $num[sizeof($num)-1] + 1;
	} else {
		$ct = 1;
	}
	while( in_array( strtolower($newFilename), $destFileList ) ) {
    	$fnameArr[0] = $temp."-$ct-".$fileInfo['v_major']."_".$fileInfo['v_minor'];
        $newFilename = implode('.', $fnameArr);
		$ct++;
	}
	$destFileList[] = $newFilename;
	return $newFilename;
}

function setCurrentFileLocation( $folderLoc, $subfolder, $fname, &$currentFileListLocations ) {
	global $DEFS;
	$currentFileLocation = $DEFS['DATA_DIR']."/".$folderLoc."/";
    if( $subfolder != NULL ) {
    	$currentFileLocation .= $subfolder."/".$fname;
    } else {
    	$currentFileLocation .= $fname;
    }
    $currentFileListLocations[] = $currentFileLocation;
}

function createDestLocForInbox( $user, $destLoc, $type ) {
	if( $type == "personalInbox" ) {
		$destLoc .= $user->db_name."/".$type."/".$user->username."/";
	} else {
		$destLoc .= $user->db_name."/".$type."/";
	}
	if( !file_exists( $destLoc ) ) {
		mkdir( $destLoc, 0777 );
	}
	$newDir = "MovedFiles-".$user->username."-".date('M-d-Y-H-i-s');
	$destLoc .= $newDir."/";
	mkdir( $destLoc, 0777 );
	return $destLoc;
}

function createDestFileList( $location, &$destFileList ) {
	$handle = opendir($location);
	while (false !== ($file = readdir($handle)))
	{
		// Check if file, then count or add it
		if(is_file($location."/".$file))
			$destFileList[] = $file;
	}
}
?>
