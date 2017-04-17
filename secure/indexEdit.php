<?php
//$Id: indexEdit.php 14216 2011-01-04 16:17:49Z acavedon $
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';

if($logged_in == 1 && strcmp( $user->username, "" )!=0) { 
	$needValue     = $trans['Must enter at least one field'];
	$db_object = $user->getDBObject();
	$db_doc = getDbObject ('docutron');

	$fields = array();
	$valArr = array();

	$queryArr = array ();
	$origValArr = array ();
	$acIsEnabled = false;
 	$xmlStr = file_get_contents('php://input');
	$domDoc = new DOMDocument ();
	$domDoc->loadXml ($xmlStr);
	$cab = $domDoc->getElementsByTagName("CABINET");
	$cab = $cab->item(0);
	$cab = $cab->getAttribute("name");
	$doc_id = $domDoc->getElementsByTagName("DOCID");
	$doc_id = $doc_id->item(0);
	$doc_id = $doc_id->getAttribute("id");
	$fields = $domDoc->getElementsByTagName("FIELD");
	$autoComplete = $domDoc->getElementsByTagName('AUTOCOMPLETE');
	if($autoComplete->length > 0) {
		$autoComplete = $autoComplete->item(0);
		$acIsEnabled = $autoComplete->getAttribute('insert');
	}
	for($i=0;$i<$fields->length;$i++) {
		$indice = $fields->item($i);
		$myVal = '';
		if ($indice->firstChild) {
			$myVal = $indice->firstChild->nodeValue;
		}
		$queryArr[$indice->getAttribute('name')] = array (
			$myVal,
			$indice->getAttribute('orig_value')
		);
	}
	$updateArr = array ();
	$newRowArr = array ();
 	foreach ($queryArr as $key => $value) {
 		$valArr[] = $key."='".$value[0]."'";
		$newRowArr[$key] = $value[0];
		if(strcmp($value[0],$value[1])) {
			$origValArr[$key] = $value[1];
			$updateArr[$key] = $value[0]; 
		}
  	}

	if($acIsEnabled) {
		$settings = new GblStt($user->db_name, $db_doc);
		$acTable = $settings->get('indexing_'.$cab);
		if($acTable != 'odbc_auto_complete' and $acTable != 'sagitta_ws_auto_complete') {
			$res = $db_object->extended->autoExecute($acTable, $newRowArr);
			dbErr($res);
		}
	}
	$str = implode(",",$valArr);
	if($user->checkSecurity($cab) > 1) {
//ALS: Global Search and Replace is no longer settable... always DISABLED
//		if($user->checkSetting("globalEditFolder",$cab)) {
//			$action = "edited folder globally";
//			$whereArr = $origValArr;
//			if(count($whereArr)) {
//				$strArr = array();
//				foreach($origValArr AS $k => $v) {
//					$strArr[] = "FOLDER INDEX:".$k."{FROM:".$v.",TO:".$updateArr[$k]."}";
//				}
//				$info = "CABINET:".$user->cabArr[$cab]."  ".implode(" , ",$strArr);
//				$user->audit($action, $info);
//				updateTableInfo($db_object,$cab,$updateArr,$whereArr);
//			}
//		} else {
			$action = "edited folder";
			$info = str_replace("'","",$str)." in ".$user->cabArr[$cab];
			$whereArr = array('doc_id'=>(int)$doc_id);
			updateTableInfo($db_object,$cab,$updateArr,$whereArr);
			$user->audit($action, $info);
//		}
	} else {
		logUserOut();
	}
	setSessionUser($user);
} else {//we want to log them out
	logUserOut();
}
?>
