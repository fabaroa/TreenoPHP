<?php
	/** This will be expecting file information in the XML as well as a DIME
	 * attachement that holds the file contents. The file info in the XML
	 * will tell wether this is a new file or an old one with a new version.
	 * This will be determined by if DEPARTMENT is set 
     * @package SOAPServer
     */
	include_once "Net/DIME.php";
	include_once '../lib/SOAPfuncs.php' ;
	include_once '../lib/inbox.php' ;
	include_once '../lib/utility.php' ;
	include_once '../classuser.inc' ;
	include_once '../lib/versioning.php' ;
	include_once '../lib/notes.php';
	include_once '../lib/fileFuncs.php';
	include_once '../lib/webServices.php';

	// Debug (brad, dont remove)
//$debugh = fopen("debug.txt", "w+") ;
	// Set up and get all the XML stuff
    $ndm = new Net_DIME_Message();
	$db_doc = getDbObject ('docutron');
	$http_data = $HTTP_RAW_POST_DATA ;
    $ndm->decodeData($http_data);
//fwrite($debugh, print_r($ndm->parts[0]['data'], true));
	// use case-folding so we are sure to find the tag in $map_array
	$xml_parser = xml_parser_create() ;
	$retval = xml_parse_into_struct($xml_parser, $ndm->parts[0]['data'], $vals, $index);

	$username = $vals[$index['USERNAME'][0]]['value'] ;
	// Now get the file name and parse out needed information or get it
	// from the XML depending on if it is a new document
	if(array_key_exists('DEPARTMENT', $index)){
		$department = $vals[$index['DEPARTMENT'][0]]['value'];
		$cabinet = $vals[$index['CABINET'][0]]['value'] ;
		$folderid = $vals[$index['FOLDERID'][0]]['value'];
		$subfolder = $vals[$index['SUBFOLDER'][0]]['value'] ;
		$filename = $vals[$index['FILENAME'][0]]['value'];
		$filename = stripInvalidChars($filename);
//fwrite($debugh, print_r($vals, true));
//fclose($debugh);
/*
`echo $deparment >> /tmp/debug.txt`;
`echo $cabinet >> /tmp/debug.txt`;
`echo $folderid >> /tmp/debug.txt`;
`echo $subfolder >> /tmp/debug.txt`;
`echo $filename >> /tmp/debug.txt`;
`echo $username >> /tmp/debug.txt`;
*/

		$db_object = getDbObject($department) ;
		$department = $vals[$index['DEPARTMENT'][0]]['value'];
		//need to check if a file with the same name, docid, subfolder, and cabinet already exists
        if ( !fileAlreadyExists($db_object, $cabinet, $folderid, $subfolder, $filename) )
        {
	    	// make new document in database with makeVersionsed
		    // NEED TO USE getOrderSett($department, $cabinet) in the last order field!!!!!!!
			$whereArr = array('doc_id' => (int) $folderid);
			if(!empty($subfolder) and $subfolder != 'Main') {
				$whereArr['subfolder'] = $subfolder;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}

			$ordering = getOrdering($db_object, $department, $cabinet, $whereArr, $db_doc);
            
			if ($subfolder == "") 
				$tab = NULL;
			else
				$tab = $subfolder;

			$insertArr = array(
				"filename"	=> $filename,
				"doc_id"	=> (int)$folderid,
				"subfolder"	=> $tab, 
				"ordering"	=> (int)$ordering,
				"date_created"	=> date('Y-m-d H:i:s'),
				"who_indexed"	=> $username
			);
		
			$res = $db_object->extended->autoExecute($cabinet."_files",$insertArr);
			dbErr($res);
			$updateArr = array();
			$updateArr['parent_id'] = 'id';
			$updateArr['parent_filename'] = 'filename';
			$whereArr = array();
			$whereArr['doc_id'] = (int)$folderid;
			if($subfolder) {
				$whereArr['subfolder'] = $subfolder;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}
			$whereArr['ordering'] = (int)$ordering;
			$whereArr['filename'] = $filename;
			updateTableInfo($db_object,$cabinet."_files",$updateArr,$whereArr);

		    // get the destination filename
            global $DEFS;
		    $whereArr = array('doc_id'=>(int)$folderid);
		    $result = getTableInfo($db_object,$cabinet,array(),$whereArr);
		    $row = $result->fetchRow ();
		    $path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/{$row['location']}");
		    $path = $path."/".$subfolder; //this could be a bad idea
		    
			$file = $path ."/".$filename;
		    // write the file
	        if(file_exists($path) && is_dir($path))
			{
	            $handle = fopen($file, "w+");
	            fwrite($handle, $ndm->parts[1]['data']);
	            fclose($handle) ;
				allowWebWrite($file,$DEFS);
				chmod($file, 0755);
			}
			else
			{
			}
        }
        else
        {        
        }
		    //make new document in database with makeVersionsed
	}
	else{ // put in as new version of doc specified by data in filename
		$filedata = $vals[$index['FILENAME'][0]]['value'] ;
		getfilenameinfo($filedata, $department, $cabinet, $fileid, $filename) ;
		$db_object = getDbObject($department) ;
		$cabinet = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinet), 'queryOne');

		/* check in new version with checkInHelp. This is from versioning 
		 * and will do everything needed to create the new versioin */
		//make new temp user object
		$user = makeTempUser($username, $department) ;
		$parentid = getParentID($cabinet, $fileid, $db_object) ;

		// get info about check in stuff that will happen
		$fileArr = getCheckInDetails($cabinet, $parentid, $db_object, $username, $filename);

		$handle = fopen($fileArr['path'], "w+") ;
		fwrite($handle, $ndm->parts[1]['data']) ;
		fclose($handle) ;
		allowWebWrite($fileArr['path'],$DEFS);
		chmod($fileArr['path'], 0755);

		checkInVersion($db_object, $fileArr, $cabinet, $parentid, $user, $db_doc);

		//Add versioning note
		$note = "Checked in versioned file: $filename in cabinet: $cabinet";
		$fileID = getRecentID($cabinet, $fileArr['parent_id'], $db_object);
		addNote($fileArr['doc_id'], $fileArr['ordering'], $fileArr['subfolder'], $cabinet, $user, $fileID, $note, $db_object);
		$user->audit("Checked in file", $note);
	}

//fclose($debugh) ;

	function getOrdering($db_object, $department, $cabinet, $whereArr, $db_doc)
	{
		$sett = new GblStt( $department, $db_doc );
		$default = $sett->get( "indexing_ordering" );
		$tmp = $sett->get( "indexing_ordering_$cabinet" );
		if( $tmp === "0" ) //prepend to user settings
			$ordering = getTableInfo($db_object, $cabinet.'_files', array('MIN(ordering) - 1'), $whereArr, 'queryOne');
		else if( $tmp === "1" ) //append to user settings
			$ordering = getTableInfo($db_object, $cabinet.'_files', array('MAX(ordering) + 1'), $whereArr, 'queryOne');
		else if( $default == "0" ) //prepend to default settings
			$ordering = getTableInfo($db_object, $cabinet.'_files', array('MIN(ordering) - 1'), $whereArr, 'queryOne');
		else //append to default settings
			$ordering = getTableInfo($db_object, $cabinet.'_files', array('MAX(ordering) + 1'), $whereArr, 'queryOne'); 
			
		if( $ordering == null )
			$ordering = 1;
	
		return $ordering;
	}

    //check for the file in the database, return true if there is a match or error
    function fileAlreadyExists( $db_object, $cabinet, $doc_id, $subfolder = null, $filename)
    {
    	$whereArr = array (
			'doc_id' => (int) $doc_id, 'filename' => $filename, 'deleted' => 0, 'display' => 1
		);
		if(isset($subfolder)) {
			$whereArr['subfolder'] = $subfolder;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}

		$res = getTableInfo($db_object, $cabinet.'_files', array('COUNT(id)'), $whereArr, 'queryOne');
		if ( $res == 0 ) {
                return false; // file doesnt exist
        }

		// file exists or other error
	    return true;
		$whereArr = array('doc_id'=>(int)$doc_id,'filename'=>$filename);
		if(strtolower($subfolder) != 'main') {
			$whereArr['subfolder'] = $subfolder;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$ret = (getTableInfo($db_object,$cabinet."_files",array('COUNT(id)'),$whereArr,'queryOne') > 0);
        return $ret;
    }
?>
