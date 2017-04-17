<?php
include_once '../lib/indexing2.php';
include_once '../lib/xmlObj.php';

function createISO ($user,$cab,$temp_table,$is_files) {
    //loads the backup page with a temp table argument
    $settings=new Usrsettings($user->username, $user->db_name );
    if($settings->get('context' )=="update")
        $settings->set('context','stop');  //stop the poll page
    echo "<script>\n";
	echo " top.mainFrame.window.location='";
	echo "../CDBackup/cdStatus.php?DepID={".$cab."}&";
	echo "temp_table=$temp_table&is_files=$is_files';\n";
	echo "</script>\n";
    die();
}

function createCSV ($user, $cab, $temp_table, $db_object) {
    global $DEFS;
                                                                                                                             
    $uname=$user->username;
	$dep = $user->db_name;
                                                                                                                             
    if(!file_exists("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup")) {
        mkdir("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup");
	allowWebWrite ("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup", $DEFS, 0777);
    } else {
        if (file_exists("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchResults.xls"))
            unlink("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchResults.xls");
    }
    $res = getCabinetAll($db_object, $cab, $dep, $uname, $temp_table);
    //if there is an error, die
    if(PEAR::isError($res))
        die("Error created the text file for $uname");
    
	$info = getTableColumnInfo ($db_object, $cab);
	$head_str = '';
	foreach($info as $info_row) {
		$head_str.=$info_row."\t";
	}
	$head_str.="\n";                                                                                                                         
    //create files with headers
    $fp=fopen("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchHeadings.xls","w");
    fwrite($fp,$head_str);
    fclose($fp);
                                                                                                                             
    //combine files
	$concatArr = array (
			escapeshellarg("{$DEFS['DATA_DIR']}/$dep/$uname" .
				"_backup/searchHeadings.xls"),
			escapeshellarg("{$DEFS['DATA_DIR']}/$dep/$uname" . 
				"_backup/searchData.xls")
			);
	$destFile = escapeshellarg("{$DEFS['DATA_DIR']}/$dep/$uname" .
			"_backup/searchResults.xls");
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$myStr = str_replace('/', '\\', implode ('+', $concatArr));
		$cmd = "copy /b " . $myStr . ' ' . $destFile;
	} else {
		$cmd = "cat " . implode (' ', $concatArr) . ' > ' . $destFile;
	}
	shell_exec($cmd);
	
	unlink("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchHeadings.xls");
    unlink("{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchData.xls");
                                                                                                                             
    $user->audit("Exported search results as file", "Cabinet: $cab");
    //use mime function to allow user to download this file from a hidden frame
    echo "<script>\n";
	echo "top.leftFrame1.window.location='sendCSV.php';";
	echo "</script>\n";
}

function createBookmark( $bookmarkValue, $uname, $dep, $cab ) {
	$userSettings = new Usrsettings($uname, $dep);
	$allBookmarks = unserialize(base64_decode($userSettings->get('bookmarks')));
	$mess = 'Bookmark '.stripslashes($bookmarkValue).' has been added';

	if (isset ($_SESSION['tlsArray'])) {
		$tlsArray = $_SESSION['tlsArray'];
		$topTerms = $tlsArray['topTerms'];
		$exactSearch = $tlsArray['exact'];
	} else {
		$tlsArray = array ();
		$topTerms = '';
		$exactSearch = false;
	}

	$newBookmark = array();
	if($topTerms) {
		$newBookmark['topLevel'] = $topTerms;
		if($exactSearch) {
			$newBookmark['exact'] = '1';
		} else {
			$newBookmark['exact'] = '0';
		}
	} else {
		$searchArray = $_SESSION['searchResArray'];
		$newBookmark['fields'] = $searchArray;
		$newBookmark['cabinet'] = $cab;
	}

	$newBookmark['name'] = $bookmarkValue;
	$allBookmarks[] = $newBookmark;
	$serializedBookmarks = base64_encode(serialize($allBookmarks));
	$userSettings->set('bookmarks', $serializedBookmarks);
																														 
	$loadBookmark = sizeof($allBookmarks) - 1;
	echo $loadBookmark."-".$mess;
}

function getCountFilesInFolder( $db_object, $cabinet, $doc_id ) {
	$count = getTableInfo($db_object, $cabinet.'_files', array('COUNT(*)'),
		array('doc_id' => (int) $doc_id, 'display' => 1, 'deleted' => 0),'queryOne');
	if( $count > 1 )
		echo "This will remove all ".$count." files in folder";
	elseif( $count == 1 )
		echo "This will remove all ".$count." file in folder";
	else
		echo "This will remove ".$count." files in folder";
}

function getDataTypeDefs( $db_object, $department, $DepID ) {
	$whereArr = array("department='$department'","k " . LIKE . " 'dt,$department,$DepID,%'");
	$dataTypeInfo = getTableInfo($db_object,'settings',array('k','value'),$whereArr);
	$str = '';
    while( $result = $dataTypeInfo->fetchRow() ) {
        $key = str_replace( "dt,$department,$DepID,", "", $result['k'] );
        $value = $result['value'];
                                                                                                                             
        $str .= $key."\t".$value."\n";
    }
    echo trim( $str );
}

function checkIfFolderExists( $db_object, $department, $cab, $db_doc) {
	$settings = new GblStt( $department, $db_doc );
	$existing = $settings->get( 'file_into_existing' );
	if( !$existing )
		$existing = 0;

	if( $existing > 0 ){
		$checkCols = $settings->get( 'compareCols' );
		$checkCols = explode(",", $checkCols);
		$counter = 0;
		$postArr = array();
 		$whereArr = array('deleted' => '0');
		$xmlStr = file_get_contents('php://input');
 		if (substr (PHP_VERSION, 0, 1) == '4') {
 			$domDoc = domxml_open_mem( $xmlStr );
 			$domArr = $domDoc->get_elements_by_tagname( 'FOLDER' );
 			foreach( $domArr as $dom ) {
				if( in_array("-1", $checkCols) OR in_array($counter, $checkCols) ) {
					$key = $dom->get_elements_by_tagname( 'KEY' );
					$value = $dom->get_elements_by_tagname( 'VALUE' );
					if ($key[0]->get_content() != "date_indexed") {
						$whereKey = $key[0]->get_content();
						$whereValue = $value[0]->get_content();
						if($whereValue == "") {
							$whereArr[$whereKey] = 'IS NULL';
						} else {
							$whereArr[$whereKey] = $whereValue;
						}
					}
				}
				$counter++;
  			}
 		} else {
			$domDoc = new DOMDocument ();
			$domDoc->loadXML ($xmlStr);
 			$domArr = $domDoc->getElementsByTagName( 'FOLDER' );
 			for ($i = 0; $i < $domArr->length; $i++) {
				$dom = $domArr->item ($i);
				if( in_array("-1", $checkCols) OR in_array($counter, $checkCols) ) {
					$key = $dom->getElementsByTagName( 'KEY' );
					$value = $dom->getElementsByTagName( 'VALUE' );
					$key = $key->item(0);
					$key = $key->nodeValue;
					$value = $value->item(0);
					$value = $value->nodeValue;
					if ($key != "date_indexed") {
						if($value == "") {
							$whereArr[$key] = 'IS NULL';
						} else {
							$whereArr[$key] = $value;
						}
					}
				}
				$counter++;
  			}
 		}

		$count = getTableInfo($db_object,$cab,array('COUNT(*)'),$whereArr,'queryOne');
		echo $count;
	} else {
		echo "0";
	}
}

function integrityCheck( $db_object, $cab, $doc_id, $temp_table, $user, $db_doc ) {
    include_once '../settings/settings.php';
    global $DEFS;

	//HRH HACKKKKKKKKKKKKKKKKKKKK
	if($cab == 'policies') {
		$tabArr = getTableInfo ($db_object, $cab.'_files',
			array ('subfolder'), array ('doc_id' => (int)$doc_id, 
			'filename' => 'IS NULL'), 'queryCol');

		$connID = getTableInfo ($db_object, 'odbc_auto_complete',
			array ('connect_id'), array ('cabinet_name' => $cab),
			'queryOne');


		$row = getTableInfo ($db_object, $cab, array ('location',
			'policy_id'), array ('doc_id' => (int) $doc_id), 
			'queryRow');

		if($connID) {
			$odbc_db_object = getODBCDbObject($connID,$db_doc);
			$odbcTabArr = getTableInfo ($odbc_db_object,
				array ('trn', 'change_description'),
				array ('policy_id' => $row['policy_id']), 
				'queryAll');

			foreach($odbcTabArr AS $res) {
				$odbcTab = implode(" ",$res);	
				$odbcTab = str_replace(" ","_",$odbcTab);
				$odbcTab = $user->replaceInvalidCharacters(trim($odbcTab));
				if(!in_array($odbcTab,$tabArr)) {
					$insertArr = array(	"doc_id"	=> $doc_id,
										"subfolder"	=> $odbcTab );
					$res = $db_object->extended->autoExecute($cab."_files",$insertArr);
					dbErr($res);

					$location = str_replace(" ", "/", $row['location']);
					if(!file_exists($DEFS['DATA_DIR']."/".$location."/".$odbcTab)) {
						mkdir($DEFS['DATA_DIR']."/".$location."/".$odbcTab,0777);
						updateLicensesInfo($db_doc,'quota_used',4096,$user->db_name);
					} else {
						die('Error creating tab because it already exists on disk but not in database');
					}
				}
			}
		}
	}


	$whereArr = array('doc_id'=>(int)$doc_id);
	$folderInfo = getTableInfo($db_object,$cab,array(),$whereArr);
    $result = $folderInfo->fetchRow();
    $location = str_replace(" ","/",$result['location']);
    if( $location!="" )
    	$location = $DEFS['DATA_DIR']."/$location";

    if(is_dir($location)) {
		$info = getFolderAuditStr($db_object, $cab, $doc_id);
        $user->audit("viewed folder","$info in Cabinet: $cab");
		echo "1\n";
		echo "allthumbs.php?cab=$cab&doc_id=$doc_id&table=$temp_table";
	} else {
		echo "2\n";
    	if(($user->checkSecurity($cab)==2&&$user->checkSetting('deleteFolders', $cab))||$user->isDepAdmin()) {
			echo "emptyFolderChoice.php?cab=$cab&doc_id=$doc_id&table=$temp_table";
    	} else {
		deleteTableInfo($db_object,$temp_table,array('result_id'=>(int)$doc_id));
			echo "searchResults.php?cab=$cab&table=$temp_table&mess=Folder has been Moved or Deleted";
    	}
	}
}

function searchAutoComp($db_dept,$cab,$search,$db_doc,$user) {
  	$indices = getCabinetInfo($db_dept, $cab);
	$gblStt = new GblStt ($user->db_name, $db_doc);

	$acTable = $gblStt->get("indexing_".$cab);
	$sField = $indices[0];
	$row = searchAutoComplete($db_dept, $acTable, $sField, $search, $cab, $db_doc, '', $user->db_name, $gblStt);

	$xmlObj = new xml();
	foreach($row AS $k => $v) {
		$xmlObj->createKeyAndValue("INDICE",$v,array('name' => $k));
	}
	$xmlObj->setHeader();
}
?>
