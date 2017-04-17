<?php
//$Id: uploadActions.php 14326 2011-04-11 20:31:25Z fabaroa $
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';
include_once '../lib/settings.php';
include_once '../lib/tables.php';
include_once '../lib/mime.php';
include_once '../lib/fileFuncs.php';

function checkForACCabinet($cab,$user, $db_doc) {
	$db_object = $user->getDbObject();
	$whereArr = array('deleted' => 0);
	$cabinetsInfo = getTableInfo($db_object, 'departments', array(), $whereArr);
	$gblStt = new GblStt($user->db_name, $db_doc);
	$settingArr = array();
	while($row = $cabinetsInfo->fetchRow ()) {
		if($gblStt->get('indexing_'.$row['real_name']) 
			&& $gblStt->get('indexing_'.$row['real_name']) != 'odbc_auto_complete'
			&& $gblStt->get('indexing_'.$row['real_name']) != 'odbc_auto_complete') {
			$settingArr[] = $row['real_name'];
		}
	}

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('ROOT');
		$xmlDoc->append_child($root);

		if(in_array($cab,$settingArr)) {
			$removeAC = $xmlDoc->create_element('REMOVE_AC');
			$text = $xmlDoc->create_text_node('true');
			$removeAC->append_child($text);
			$root->append_child($removeAC);
		}

		foreach($settingArr AS $acCab) {
			if($acCab != $cab) {
				$acCabinet = $xmlDoc->create_element('AC_CABINET');
				$acCabinet->set_attribute('name',$acCab);
				$text = $xmlDoc->create_text_node($user->cabArr[$acCab]);
				$acCabinet->append_child($text);
				$root->append_child($acCabinet);
			}
		}
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('ROOT');
		$xmlDoc->appendChild($root);

		if(in_array($cab,$settingArr)) {
			$removeAC = $xmlDoc->createElement('REMOVE_AC');
			$text = $xmlDoc->createTextNode('true');
			$removeAC->appendChild($text);
			$root->appendChild($removeAC);
		}

		foreach($settingArr AS $acCab) {
			if($acCab != $cab) {
				$acCabinet = $xmlDoc->createElement('AC_CABINET');
				$acCabinet->setAttribute('name',$acCab);
				$text = $xmlDoc->createTextNode($user->cabArr[$acCab]);
				$acCabinet->appendChild($text);
				$root->appendChild($acCabinet);
			}
		}
		$xmlStr = $xmlDoc->saveXML ();
	}

	header('Content-type: text/xml');
	echo $xmlStr;
}

function uploadACFile($cab,$type,$user, $db_doc) {
	global $DEFS;
	$db_object = $user->getDbObject();

	//gets the filename that was uploaded
	$windows = false;
	$fileUploadName = str_replace("'","",$_FILES['f1']['name']);
	$fileUploadName = str_replace("`","",$fileUploadName);
	//gets the temp destination for where it was stored
	$source = $_FILES['f1']['tmp_name'];
	//creates the destination of where the file should be moved to
	$dest = "{$DEFS['DATA_DIR']}/$user->db_name/$fileUploadName";
	//gets the number of indices -- function is in lib/cabinets.php
	//moves uploaded file
	move_uploaded_file($source,$dest);
 	allowWebWrite ($dest, $DEFS);
	//THIS IS PROBLEMATIC. WHY!
	if($user->db_name == 'client_files15' && $cab == "Documents" ) {
		$dest = removeQuotesFromTabSeparated($dest,$DEFS['DATA_DIR']."/".$user->db_name);
	}
 
//WILL PASSWEISZ CUSTOM CODE

if(!strcmp($cab,"Work_Orders")){
		$fd = file($dest);
		$newtemp = "/tmp/WOupload.txt";
		$newfd = fopen($newtemp,"w+");
		$insertArr = array();
	foreach($fd as $line){
    	$items = explode("\t",$line);
        $insertArr["customer_name"]= str_replace("\"","",trim($items[0]));
        $insertArr["cust_no"] = trim($items[1]);
        $insertArr["RO"] = trim($items[2]);
        $insertArr["open_date"] = strtoupper(date("dMY",strtotime(trim($items[3]))));
        $insertArr["close_date"] = strtoupper(date("dMY",strtotime(trim($items[4]))));
        $insertArr["serial"] = trim($items[5]);
        $insertArr["make"] = trim($items[6]);
        $insertArr["model"] = trim($items[7]);
        $insertArr["year"] = trim($items[8]);
        $insertArr["license"] = trim($items[9]);
        $insertArr["warr_date"] = strtoupper(date("dMY",strtotime(trim($items[10]))));
        $insertArr["options"] = str_replace("\"","",trim($items[11]));

        $year = $insertArr["year"];
        if($year >= 0 && $year <30){
            $year = $year + 2000;
        }else{
            $year = $year + 1900;
        }

    $select = "select name_or_address,phone_number from Work_Orders where customer_number = \"";
    $select .= $insertArr["cust_no"]."\"";

    $res = $db_object->queryRow($select);

    $name_addr = $insertArr["customer_name"];
    $phone = $res["phone_number"];
    $name_from_db = $res["name_or_address"];
    if(strlen($name_addr) < strlen($name_from_db)){
        $name_addr = $name_from_db;
    }

//    $insert = "insert into $cab values(\"".$insertArr["RO"]."\",\"".$name_addr;
//    $insert .= "\",\"".$year."\",\"".$insertArr["make"]." ".$insertArr["model"];
//    $insert .= "\",\"".$insertArr["serial"]."\",\"".$insertArr["license"]."\",\"".$insertArr["close_date"];
//    $insert .= "\",\"".$insertArr["warr_date"]."\",\"".$insertArr["open_date"]."\",\"".$insertArr["options"];
//    $insert .= "\",\"".$insertArr["cust_no"]."\",\"".$phone."\")";
//  $db_object->query($insert);
	$outline =$insertArr["RO"]."\t".$name_addr."\t".$year."\t".$insertArr["make"]." ".$insertArr["model"]."\t";
	$outline .= $insertArr["serial"]."\t".$insertArr["license"]."\t".$insertArr["close_date"]."\t";
	$outline .= $insertArr["warr_date"]."\t".$insertArr["open_date"]."\t".$insertArr["options"]."\t";
	$outline .= $insertArr["cust_no"]."\t".$phone."\n";
	fwrite($newfd,$outline);

	}
	fclose($newfd);
	unlink($dest);
	copy($newtemp,$dest);
}
//END WILL PASSWEISZ CUSTOM CODE
	$tmp = file($dest);
	if( ereg("\r\n",$tmp[0]))
			$windows = true;

	$fields = getCabinetInfo($db_object, $cab);
	if($type == "new") {
		$ct = substr_count($tmp[0],"\t")+1;	
		if($ct > sizeof($fields)) {
			$extras = $ct - sizeof($fields);
			for($i=0;$i<$extras;$i++) {
				$fields[] = "extra".($i+1);
			}
		}
		dropTable($db_object,"auto_complete_".$cab);
		createACTable($db_object, $cab, $fields);
	} 
	//load the file into the newly created table in the database
	$mess = autoCompLoad($user->db_name,$db_object,"auto_complete_".$cab,$fields,$dest,$windows);
	if($mess) {
		dropTable($db_object,"auto_complete_".$cab);
	} else {
		$gblStt = new GblStt($user->db_name, $db_doc);
		//close connection and connect to docutron
		if(!$gblStt->get('indexing_'.$cab)) {
			$gblStt->set('indexing_'.$cab, 'auto_complete_'.$cab);
		}
	}
	//removes file from system after uploaded to a table
	unlink($dest);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<body onload="parent.mainFrame.uploadComplete('<?php echo $mess; ?>')">
</body>
</html>
<?php
}

function removeQuotesFromTabSeparated($fileLoc,$destPath) {
	global $DEFS;
	$fileArr = file($fileLoc);
	$newFileArr = array();
	for($i=0;$i<sizeof($fileArr);$i++) {
		$lineArr = explode("\t",trim($fileArr[$i]));
		$tmpArr = array();
		foreach($lineArr AS $col) {
			if ($col{0} == '"') {
				$tmpArr[] = substr($col, 1, strlen ($col) - 2);
			} else {
				$tmpArr[] = $col;
			}
		}
		$newFileArr[] = implode("\t",$tmpArr);
	} 

	$newFile = $destPath."/johnsonPolicies.txt";
	$fp = fopen($newFile,'w+');
	fwrite($fp,implode("\n",$newFileArr));
	fclose($fp);

	return $newFile;
}

function copyACCabinet($cab,$copyCab,$user, $db_doc) {
	$db_object = $user->getDbObject();
	//Drops table if exists before creating a new one
	dropTable($db_object,"auto_complete_".$cab);
	$gblStt = new GblStt($user->db_name, $db_doc);
	$gblStt->set('indexing_'.$cab, 'auto_complete_'.$copyCab);

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('ROOT');
		$xmlDoc->append_child($root);
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement('ROOT');
		$xmlDoc->appendChild($root);
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

function removeACCabinet($cab,$user, $db_doc) {
	$db_object = $user->getDbObject();
	//Drop auto complete indexing table link to the cabinet selected
	dropTable($db_object, "auto_complete_".$cab);

	$gblStt = new GblStt($user->db_name, $db_doc);
	$gblStt->removeKey('indexing_'.$cab);
 	$t = "Auto Complete Indexing Is Disabled For -> ".$user->cabArr[$cab];
  
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$xmlDoc = domxml_new_doc('1.0');
 		$root = $xmlDoc->create_element('ROOT');
 		$xmlDoc->append_child($root);
 		
 		$mess = $xmlDoc->create_element('MESSAGE');
 		$text = $xmlDoc->create_text_node($t);
 		$mess->append_child($text);
 		$root->append_child($mess);
 		$xmlStr = $xmlDoc->dump_mem(false);
 	} else {
 		$xmlDoc = new DOMDocument (); 
 		$root = $xmlDoc->createElement('ROOT');
 		$xmlDoc->appendChild($root);
 		
 		$mess = $xmlDoc->createElement('MESSAGE');
 		$text = $xmlDoc->createTextNode($t);
 		$mess->appendChild($text);
 		$root->appendChild($mess);
 		$xmlStr = $xmlDoc->saveXML ();
 	}

  	header('Content-type: text/xml');
 	echo $xmlStr;
}

function viewACTable($cab,$user,$p) {
	$db_dept = getDbObject($user->db_name);

	$usrSett = new Usrsettings($user->username,$user->db_name); 
	$resPage = $usrSett->get('results_per_page');
	if(!$resPage) {
		$resPage = 25;
		$usrSett->set('results_per_page',$resPage);
	}

	$count = getTableInfo($db_dept,'auto_complete_'.$cab,array('COUNT(*)'),array(),'queryOne');
	$pageCount = ceil($count / $resPage);
	if(!$p || $p <= 0) {
		$p = 1;
	} elseif($p > $pageCount) {
		$p = $pageCount;
	}
	$start = ($p - 1) * $resPage;
	$indNames = getTableColumnInfo ($db_dept, 'auto_complete_'.$cab);
	$acRows = getTableInfo($db_dept,'auto_complete_'.$cab,array(),array(),'queryAll',array(),$start,$resPage);

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('ROOT');
		$xmlDoc->append_child($root);
		$page = $xmlDoc->create_element('PAGE');	
		$text = $xmlDoc->create_text_node($p);
		$page->append_child($text);
		$page->set_attribute('total',$pageCount);
		$root->append_child($page);
		
		foreach($indNames AS $info) {
			$head = $xmlDoc->create_element('HEADER');	
			$text = $xmlDoc->create_text_node(str_replace("_"," ",$info));
			$head->append_child($text);
			$root->append_child($head);
		}

		foreach ($acRows as $row) {
			$entry = $xmlDoc->create_element('ENTRY');	
			foreach($row AS $v) {
				$indice = $xmlDoc->create_element('INDICE');	
				$text = $xmlDoc->create_text_node($v);
				$indice->append_child($text);
				$entry->append_child($indice);
			}
			$root->append_child($entry);
		}
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('ROOT');
		$xmlDoc->appendChild($root);
		$page = $xmlDoc->createElement('PAGE');	
		$text = $xmlDoc->createTextNode($p);
		$page->appendChild($text);
		$page->setAttribute('total',$pageCount);
		$root->appendChild($page);
		
		foreach($indNames AS $info) {
			$head = $xmlDoc->createElement('HEADER');	
			$text = $xmlDoc->createTextNode(str_replace("_"," ",$info));
			$head->appendChild($text);
			$root->appendChild($head);
		}

		foreach ($acRows as $row) {
			$entry = $xmlDoc->createElement('ENTRY');	
			foreach($row AS $v) {
				$indice = $xmlDoc->createElement('INDICE');	
				$text = $xmlDoc->createTextNode($v);
				$indice->appendChild($text);
				$entry->appendChild($indice);
			}
			$root->appendChild($entry);
		}
		$xmlStr = $xmlDoc->saveXML ();
	}

	header('Content-type: text/xml');
	echo $xmlStr;
}

function exportAC($cab, $user) {
	global $DEFS;
	$department = $user->db_name;
	$username = $user->username;
    $db_object = getDbObject( $department );

    if(!file_exists("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete")) {
        mkdir("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete");
        chmod("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete",0777);
    } else {
        if (file_exists("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteResults.xls")) {
            unlink("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteResults.xls");
		}
    }
    queryAllFromOutFile($db_object, $department, $username, $cab);

    $info = $db_object->reverse->tableInfo('auto_complete_'.$cab);//retrieve heading information

	$head_str = '';
    foreach($info as $info_row) {
        $head_str.=$info_row['name']."\t";
    }
    $head_str.="\n";

    //create files with headers
    $fp = fopen("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteHeadings.xls","w");
    fwrite( $fp, $head_str );
    fclose( $fp );

    //combine files
	$concatArr = array (
			escapeshellarg("{$DEFS['DATA_DIR']}/$department/$username" .
				"_autoComplete/autoCompleteHeadings.xls"),
			escapeshellarg("{$DEFS['DATA_DIR']}/$department/$username" . 
				"_autoComplete/autoCompleteData.xls")
			);
	$destFile = escapeshellarg("{$DEFS['DATA_DIR']}/$department/$username" .
			"_autoComplete/autoCompleteResults.xls");
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$cmdPart = implode ('+', $concatArr) . ' ' . $destFile;
		$cmdPart = str_replace('/', '\\', $cmdPart);
		$cmd = "copy /b " . $cmdPart;
	} else {
		$cmd = "cat " . implode (' ', $concatArr) . ' > ' . $destFile;
	}
	shell_exec($cmd);

    unlink("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteHeadings.xls");
    unlink("{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteData.xls");

    $path = "{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete";

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('ROOT');
		$xmlDoc->append_child($root);
		$root->set_attribute('path',$path);
		$root->set_attribute('filename','autoCompleteResults.xls');
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement('ROOT');
		$xmlDoc->appendChild($root);
		$root->setAttribute('path',$path);
		$root->setAttribute('filename','autoCompleteResults.xls');
		$xmlStr = $xmlDoc->saveXML ();
	}

	header('Content-type: text/xml');
	echo $xmlStr;
}

if($logged_in ==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
	$db_doc = getDbObject ('docutron');
	if(isSet($_GET['checkForACCabinet'])) {	
		checkForACCabinet($_GET['cab'],$user, $db_doc);
	} elseif(isSet($_GET['uploadACFile'])) {
		uploadACFile($_GET['cab'],$_GET['type'],$user, $db_doc);
	} elseif(isSet($_GET['copyACCabinet'])) {
		copyACCabinet($_GET['cab'],$_GET['copyCab'],$user, $db_doc);
	} elseif(isSet($_GET['removeACCabinet'])) {
		removeACCabinet($_GET['cab'],$user, $db_doc);
	} elseif(isSet($_GET['viewACTable'])) {
		if (isset ($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = '';
		}
		viewACTable($_GET['cab'],$user,$page);
	} elseif(isSet($_GET['exportAC'])) {
		exportAC($_GET['cab'],$user);
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
