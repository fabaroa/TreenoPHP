<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/odbc.php';
include_once '../lib/quota.php';
include_once '../lib/random.php';
include_once '../lib/settings.php';
include_once '../lib/sagWS.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
include_once '../documents/documents.php';
include_once '../lib/xmlObj.php';

if (!empty ($DEFS['CUSTOM_LIB'])) {
	require_once $DEFS['CUSTOM_LIB'];
}

function getCabInfo( $db_object, $cab ) {
	$fieldnames = getCabinetInfo($db_object, $cab);

	$xmlObj = new xml("CABINET");
	foreach( $fieldnames as $index ) {
		$xmlObj->createKeyAndValue("INDEX",str_replace("_"," ",$index));
	}
	$xmlObj->setHeader();
}

function editIndiceController( $db_object, $user ) {
	$queryArr = array();
	$indiceOrderArr = array();
	$deletedIndices = array();
	$changeIndicesValue = array();
	$changeIndicesNum = array();
	$newIndices = array();
	$errorArr = array();
	$db_doc = getDbObject('docutron');

	$xmlStr = file_get_contents('php://input');
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem ($xmlStr);
 		$cabinet = $domDoc->get_elements_by_tagname ('CABINET');
 		$cab = $cabinet[0]->get_attribute ('name');
 		$newCab = $cabinet[0]->get_content ();
 		$deleted = $domDoc->get_elements_by_tagname("DELETED");
 		foreach ($deleted as $myDel) {
 			$deletedIndices[] = $myDel->get_content();
 		}
 		$tmpArr = $domDoc->get_elements_by_tagname("INDICE_NUM");
 		foreach ($tmpArr as $indice) {
 			$changeIndicesNum[] = array ('name' =>
 					$indice->get_attribute('name'), 'value' =>
 					$indice->get_content());
 		}
 		$tmpArr = $domDoc->get_elements_by_tagname("INDICE_VALUE");
 		foreach ($tmpArr as $indice) {
 			$changeIndicesValue[$indice->get_attribute('current')] =
 				$indice->get_content();
 		}
 		$tmpArr = $domDoc->get_elements_by_tagname("NEW_INDICE");
 		foreach ($tmpArr as $indice) {
 			$newIndices[$indice->get_attribute('indiceNum')] =
 				$indice->get_content();
 		}
 	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
 		$cabinet = $domDoc->getElementsByTagName ('CABINET');
 		$cab = $cabinet->item(0);
		$cab = $cab->getAttribute ('name');
 		$newCab = $cabinet->item(0);
		$newCab = $newCab->nodeValue;
 		$deleted = $domDoc->getElementsByTagName("DELETED");
		for ($i = 0; $i < $deleted->length; $i++) {
			$myDel = $deleted->item($i);
 			$deletedIndices[] = $myDel->nodeValue;
 		}
 		$tmpArr = $domDoc->getElementsByTagName("INDICE_NUM");
 		for ($i = 0; $i < $tmpArr->length; $i++) {
			$indice = $tmpArr->item ($i);
 			$changeIndicesNum[] = array ('name' =>
 					$indice->getAttribute('name'), 'value' =>
 					$indice->nodeValue);
 		}
 		$tmpArr = $domDoc->getElementsByTagName("INDICE_VALUE");
		for ($i = 0; $i < $tmpArr->length; $i++) {
			$indice = $tmpArr->item ($i);
 			$changeIndicesValue[$indice->getAttribute('current')] =
 				$indice->nodeValue;
 		}
 		$tmpArr = $domDoc->getElementsByTagName("NEW_INDICE");
		for ($i = 0; $i < $tmpArr->length; $i++) {
			$indice = $tmpArr->item ($i);
 			$newIndices[$indice->getAttribute('indiceNum')] =
 				$indice->nodeValue;
 		}
 	}

	$cabInfo = getTableInfo($db_object,'departments',array('departmentname','departmentid'),array('real_name'=>$cab),'queryRow');
	$curCab = $cabInfo['departmentname'];
	$cabID = $cabInfo['departmentid'];
	if(trim($newCab)) { 
		if( $curCab != $newCab) {
			if( !in_array($newCab, $user->cabArr)) {
				$updateArr = array();
				$updateArr['departmentname'] = $newCab;
				$whereArr = array();
				$whereArr['real_name'] = $cab;
				updateTableInfo($db_object,'departments',$updateArr,$whereArr);
				$user->audit("cabinet name changed", "From: $curCab  To: $newCab" );
			} else {
				$errorArr[] = "Cabinet already exists -> $newCab";
			}
		}
	} else {
		$errorArr[] = "Cabinet name is empty";
	}

	$indiceNames = getCabinetInfo($db_object, $cab);
	foreach($indiceNames AS $name ) {
		$indiceOrderArr[$name] = sizeof($indiceOrderArr)+1;
	}

	$deletedArr = array();
	$gblStt = new Gblstt($user->db_name, $db_doc);
	//Checks that there is at least one index in the cabinet
	if( count($newIndices) + count($indiceNames) <= count($deletedIndices) ) {
		$errorArr[] = "Minimum one index required";
	} else {
		foreach( $deletedIndices AS $indices ) {
			unset($indiceOrderArr[$indices]);
			$name = trim(str_replace(" ","_",$indices));

			$setKey = 'dt,'.$user->db_name.','.$cabID.','.$name;
			$gblStt->removeKey($setKey); 
		
			$queryArr[] = "ALTER TABLE $cab DROP COLUMN $name";
			$user->audit("indice dropped", "cabinet: $cab Index: ".$indices);
			$deletedArr[] = $name;
		}
	}

	foreach( $changeIndicesNum AS $indices ) {
 		$name = str_replace( " ", "_", $indices['name']);
 		$indiceOrderArr[$name] = $indices['value'];
	}

	foreach( $changeIndicesValue AS $current => $myNew) {
 		$cur = str_replace( " ", "_", $current);
 		$new = str_replace( " ", "_", $myNew);
 		$new = str_replace( "-", "_", $new);
		if( $user->invalidNames(strtolower($new)) ||
			$user->invalidJscriptNames(strtolower($new)) ) {
			$errorArr[] = "Reserved Word -> $new";	
		} elseif( !array_key_exists( $new, $indiceOrderArr ) && !is_numeric($new[0]) ) {
			$indiceOrderArr[$new] = $indiceOrderArr[$cur];
			if (getDbType () == 'mssql') {
				$queryArr[] = "sp_rename '$cab.$cur', '$new', 'COLUMN'";
			} elseif(getDbType () == 'pgsql') {
				$queryArr[] = "ALTER TABLE $cab RENAME $cur TO $new";
			} else {
				$queryArr[] = "ALTER TABLE $cab CHANGE $cur $new VARCHAR(255) NULL";
			}

			//update the fieldFormat table
			$uArr = array('field_name' => $new);
			$wArr = array('field_name' => $cur, 'cabinet_id' => $cabID);
			updateTableInfo($db_object, 'field_format', $uArr, $wArr);
			
			//update data type definitions
			$uArr = array('k' => 'dt,'.$user->db_name.','.$cabID.','.$new);
			$wArr = array('k' => 'dt,'.$user->db_name.','.$cabID.','.$cur);
			updateTableInfo($db_doc,'settings',$uArr,$wArr);

			if($gblStt->get('indexing_'.$curCab) == 'odbc_auto_complete') {
				//update odbc mapping definitions
				$uArr = array('docutron_name' => $new);
				$wArr = array(	'cabinet_name'	=> $curCab,
								'docutron_name' => $cur );
				updateTableInfo($db_object,'odbc_mapping',$uArr,$wArr);
			} elseif($gblStt->get('indexing_'.$curCab) == 'auto_complete_'.$curCab) {
				//change the auto complete columns to match	
				alterTable($db_object, "auto_complete_".$curCab, "CHANGE $cur $new VARCHAR(255) NULL");
			}

			$user->audit("indice changed", "cabinet: $cab Index: $cur to $new");
			unset($indiceOrderArr[$cur]);
		} elseif( array_key_exists( $new, $indiceOrderArr ) ) {
			$errorArr[] = "Index already exists -> $new";
		} else {
			$errorArr[] = "First character cannot be a number -> $new";
		}
	}

	foreach( $newIndices AS $indiceNum => $newInd ) {
		$name = str_replace( " ","_", $newInd);
		if( $user->invalidNames(strtolower($name)) ||
			$user->invalidJscriptNames(strtolower($name)) ) {
			$errorArr[] = "Reserved Word -> $name";	
		} elseif( !array_key_exists( $name, $indiceOrderArr ) && !is_numeric($name[0]) ) {
 			$indiceOrderArr[$name] = $indiceNum;
			$queryArr[] = "ALTER TABLE $cab ADD $name VARCHAR(255) NULL";
			$user->audit("indice added", "cabinet: $cab Indice: $name");
		} elseif( array_key_exists( $name, $indiceOrderArr ) ) {
			$errorArr[] = "Index already exists -> $name";
		} else {
			$errorArr[] = "First character cannot be a number -> $name";
		}
	}
	uasort($indiceOrderArr,"strnatcasecmp");
	editCabinetIndices($db_object,$cab,$queryArr);
	foreach($deletedArr AS $name) {
		unset($indiceOrderArr[$name]);
	}
	rearrangeIndices($db_object,$cab,$indiceOrderArr, $errorArr);
	echo implode("\n",$errorArr);
}

function editCabinetIndices($db_object, $cab, $queryArr) {
	foreach($queryArr AS $newColumn) {
		$res = $db_object->query ($newColumn);
		dbErr($res);
	}
}

function rearrangeIndices($db_object, $cab, $orderArr,&$errorArr) {
	$indices = getCabinetInfo($db_object, $cab);
	$orderArr = array_keys($orderArr);
	$needReOrder = false;
	for($i = 0; $i < count($indices); $i++) {
		if($indices[$i] != $orderArr[$i]) {
			$needReOrder = true;
			break;
		}
	}
	if($needReOrder) {
		$curCount = getTableInfo($db_object, $cab, array('COUNT(*)'), array(), 'queryOne');
		$temp_table = getRandString();

		createCabinet($db_object,$temp_table,$orderArr);
		if (getDbType () == 'mssql') {
			$res = $db_object->beginTransaction ();
			dbErr ($res);
			$res = $db_object->query ('SET IDENTITY_INSERT ' . $temp_table . ' ON');
			dbErr($res);
		}
		$insArr = array_merge(array('doc_id','location','deleted'), $orderArr);
		insertFromSelect($db_object, $temp_table, $insArr, $cab, $insArr);
		if (getDbType () == 'mssql') {
			$res = $db_object->query ('SET IDENTITY_INSERT ' . $temp_table . ' OFF');
			dbErr($res);
			$res = $db_object->commit ();
			dbErr ($res);
		}
		$newCount = getTableInfo($db_object, $temp_table, array('COUNT(*)'), array(), 'queryOne');
		if( $curCount == $newCount ) {
			$last_value = 0;
			if(getDbType()=='pgsql'){
				$last_value=getTableInfo($db_object,strtolower($cab),array('max(doc_id)'),array(),'queryOne');
				if( $last_value == '' ){
					$last_value = 1;
				}
				dbErr( $last_value );
			}
			$query = "DROP TABLE $cab";
			$res = $db_object->query($query);
			dbErr($res);
			renameTable ($db_object, $temp_table, $cab);
			if(getDbType() == 'pgsql') {
				renameTable( $db_object, $temp_table."_doc_id_seq", $cab."_doc_id_seq" );
				$updateSeq = "SELECT SETVAL('$cab"."_doc_id_seq',$last_value)";
				dbErr($db_object->query($updateSeq));
				$dropPrimaryKey = "ALTER TABLE $cab DROP CONSTRAINT $temp_table"."_pkey";
				dbErr($db_object->query($dropPrimaryKey));
				$addPrimaryKey = "ALTER TABLE $cab ADD PRIMARY KEY(doc_id)";
				dbErr($db_object->query($addPrimaryKey));
				$query = "ALTER TABLE $cab ALTER COLUMN doc_id SET DEFAULT nextval('{$cab}_doc_id_seq')";
				$res = $db_object->query($query);
				//dbErr( $db_object->query( "DROP SEQUENCE $temp_table"."_doc_id_seq"));
				dbErr($res);
			}
		} else {
			$errorArr[] = "Indice order cannot be rearranged";
		}
	}
}

function createNewCabinet($db_object,$user) {
	global $DEFS;
	$indiceName = array();
 	$xmlStr = file_get_contents ('php://input');
 	$allIndices = array ();
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem ($xmlStr);
 		$cabinet = $domDoc->get_elements_by_tagname ('CABINET');
 		$newArbCab = trim($cabinet[0]->get_content());
 		$indices = $domDoc->get_elements_by_tagname ('INDICE');
 		foreach($indices as $newIndice) {
 			$allIndices[] = $newIndice->get_content ();
 		}
 	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
 		$cabinet = $domDoc->getElementsByTagName ('CABINET');
		$newArbCab = $cabinet->item(0);
 		$newArbCab = trim($newArbCab->nodeValue);
 		$indices = $domDoc->getElementsByTagName ('INDICE');
		for ($i = 0; $i < $indices->length; $i++) {
			$newIndice = $indices->item($i);
 			$allIndices[] = $newIndice->nodeValue;
 		}
 	}
 	$newRealCab = str_replace(' ', '_', $newArbCab);
 	$docutron = getDbOBject('docutron');
 	//NEED A LOCK AROUND HERE.
	$hasPermission = true;
 	if( !checkQuota($docutron, 8192,$user->db_name ) ) {
		$m = "This Operation Will Exceed Quota Limit";
		$hasPermission = false;
	}

	$res = getTableInfo($db_object, 'departments',array(),array(),'query',array('departmentname'=>'ASC'));
	$cabArr = array ();
	while ($row = $res->fetchRow()) {
		$cabArr[strtolower($row['real_name'])] = strtolower($row['departmentname']);
	}

	// if real name exists already, new one should be unique
	if( array_key_exists(strtolower($newRealCab),$cabArr)){
		$newRealCab .= "_1";
	}

	if( is_numeric( $newArbCab[0] ) ) {
		$m = $newArbCab." First Character Invalid";
		$hasPermission = false;
	} elseif( $user->invalidNames(strtolower($newArbCab)) ||
	$user->invalidJscriptNames(strtolower($newArbCab)) ) {
		$m = $newArbCab." Reserved Word";
		$hasPermission = false;
	} else if(in_array(strtolower($newArbCab),$cabArr)) {
			$m = "Cabinet Name Taken";
			$hasPermission = false;
	} elseif(in_array($newArbCab, $db_object->manager->listTables())) {
		$m = "Invalid Name";
		$hasPermission = false;
	} elseif($user->invalidCharacter($newRealCab)) {
		$m = "Invalid Character";
		$hasPermission = false;
	}
	foreach ($allIndices as $index) {
		$name = trim(str_replace(' ', '_', $index));
		if( in_array($name, $indiceName) ) {
			$m = $name." Duplicate Index";
			$hasPermission = false;
			break;
		} elseif( $user->invalidNames(strtolower($name)) ||
		$user->invalidJscriptNames(strtolower($name)) ) {
			$m = $name." Reserved Word";
			$hasPermission = false;
			break;
		} elseif( is_numeric( $name[0] ) ) {
			$m = $name." First Character Invalid";
			$hasPermission = false;
			break;
		} else {
			$indiceName[] = $name;
		}
	}

	$xmlObj = new xml();
	if($hasPermission) {
		$res = createFullCabinet($db_object, $docutron, $user->db_name, $newRealCab, $newArbCab, $indiceName,$user);
		if($res) {
			$sArr = array('departmentid');
			$wArr = array('real_name' => $newRealCab);
			$DepID = getTableInfo($db_object,'departments',$sArr,$wArr,'queryOne');

			$xmlObj->createKeyAndValue("CABINET",$DepID);
			$m = "Cabinet successfully created";
		}
		else
		{
			$m = "Cabinet creation failed";
		}
	} else {
		$user->audit('unable to create cabinet', 'Permission Denied');
		//$m .= "Unable to create cabinet: Permission Denied";
	}

	$xmlObj->createKeyAndValue("MESSAGE",$m);
	$xmlObj->setHeader();
}

function updateCabinetAccess($db_object, $cabinet, $user) {
	$userList = getTableInfo($db_object,'access');
	while($user1 = $userList->fetchRow()) {
		$uname = $user1['username'];
		if($user->greaterThanUser($uname) && $user->username!=$uname)  {
			$accessArr = unserialize(base64_decode($user1['access']));
			$access = $_POST[$uname];
			if( array_key_exists( $cabinet, $accessArr ) ) {
				if($access != $accessArr[$cabinet]) {
					$user->audit("$cab cabinet permissions changed", "$uname, -$accessArr[$cabinet] +$access");
				}
			}
			$accessArr[$cabinet] = $access;
			$updateArr = array('access'=>base64_encode(serialize($accessArr)));
			$whereArr = array('username'=>$uname);
			updateTableInfo($db_object,'access',$updateArr,$whereArr);
		}
	}
}

function xmlGetCabinetList($db, $user) {
	$xmlObj = new xml('cabinetList');
	foreach ($user->cabArr as $cabinet => $dispName) {
		if($user->access[$cabinet] != "none") {
			$attArr = array('real_name' => $cabinet,
							'arb_name'	=> $dispName);
			$xmlObj->createKeyAndValue('cabinet',NULL,$attArr);
		}
	}
	$xmlObj->setHeader();
}

function extFileMove($db_object,$user) {
 	$db_doc = getDbObject ('docutron');
  	$xmlStr = file_get_contents('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem( $xmlStr );
 		$cab = $domDoc->get_elements_by_tagname('CABINET');
 		$cab = $cab[0]->get_content();
 		$fieldArr = $domDoc->get_elements_by_tagname('INDEX');
 		$tab = $domDoc->get_elements_by_tagname('TAB');
 		if ($tab) {
 			$tab = $tab[0]->get_content();
 		} else {
 			$tab = 'Main';
 		}
 		foreach($fieldArr as $index) {
 			$queryArr[strtolower($index->get_attribute('name'))] =
 				trim($index->get_content());
 		}
  	} else {
 		$domDoc = new DOMDocument (); 
		$domDoc->loadXML ($xmlStr);
 		$cab = $domDoc->getElementsByTagName('CABINET');
 		$cab = $cab->item(0);
		$cab = $cab->nodeValue;
 		$fieldArr = $domDoc->getElementsByTagName('INDEX');
 		$tab = $domDoc->getElementsByTagName('TAB');
 		if ($tab) {
 			$tab = $tab->item(0);
			$tab = $tab->nodeValue;
 		} else {
 			$tab = 'Main';
 		}
		for ($i = 0; $i < $fieldArr->length; $i++) {
			$index = $fieldArr->item ($i);
 			$tmpQueryArr[strtolower($index->getAttribute('name'))] =
 				trim($index->nodeValue);
 		}
 	}
	if (function_exists ('customIntegratorFix')) {
		customIntegratorFix($user->db_name, $cab, $tmpQueryArr);
	}
	$indices = getCabinetInfo($db_object, $cab);
	
	//error_log('Cab fields of ['.$cab.']: '.implode($indices, ","));
	//error_log('Temp QueryArray: '.print_r($tmpQueryArr, true));
	foreach($indices as $cabfield)
	{
		$lcfield = strtolower($cabfield);
		if (array_key_exists($lcfield, $tmpQueryArr) && isset($tmpQueryArr[$lcfield]))
		{
			$tmpVal = $tmpQueryArr[$lcfield];
			if($tmpVal != '' )//&& $tmpVal !='""')
			{
				$queryArr[$lcfield] = $tmpVal;	
			}
		}	
	}
	error_log('cabinetAction.php - queryArray: '.print_r($queryArr, true));
			
	$created = false;
	$whereArr = $queryArr;
 	$whereArr['deleted'] = 0;
	$doc_id = getTableInfo($db_object,$cab,array('doc_id'),$whereArr,'queryOne');
	error_log('Searching Treeno database returns doc_id: '.$doc_id);
	if($doc_id) {
		$created = true;
	} elseif($user->checkSecurity($cab) == 2) {
		$doc_id = searchAndCreateFolder($user, $cab, $db_object, $db_doc, $queryArr);
		error_log('cabinetAction.php - searchAndCreateFolder('.$user->username.', '.$cab.',...) returns doc_id: '.$doc_id);
	} else {
		$doc_id = 0;
	}
	$docIndices = array ();
	if(substr(PHP_VERSION, 0, 1) == '4') {
		$docIndex = $domDoc->get_elements_by_tagname('DOCINDEX');
		for($i = 0; $i < count($docIndex); $i++) {
			$docIndices[$docIndex[$i]->get_attribute('name')] = 
				$docIndex[$i]->get_content();
		}
	} else {
		$docIndex = $domDoc->getElementsByTagName('DOCINDEX');
		for($i = 0; $i < $docIndex->length; $i++) {
			$myInd = $docIndex->item($i);
			$docIndices[$myInd->getAttribute('name')] =
				$myInd->nodeValue;
		}
	}
	if($docIndices && $doc_id) {
		$enArr = array ('document_table_name' => $tab, 'cabinet' => $cab,
			'doc_id' => $doc_id, 'field_count' => count($docIndices));
		$i = 0;
		foreach($docIndices as $k => $v) {
			$enArr['key'.$i] = $k;
			$enArr['field'.$i] = $v;
			$i++;
		}
		$tab = addDocumentToCabinet($enArr, $user, $db_doc, $db_object);
	}

	$xmlObj = new xml('legacyIntegrator');
	$attArr = array('cab' => $cab);
	if($doc_id) {
		$attArr['doc_id'] = $doc_id;
		$attArr['tab'] = $tab;
	} else {
		$attArr['error'] = 'user needs rw privileges';
	}
	$xmlObj->createKeyAndValue('CABINET',NULL,$attArr);
	$xmlObj->setHeader();
}

function xmlGetBarcodeSettings($db_dept, $cabinet) {	
	if($cabinet) {
		$cabinetID = (int) getTableInfo($db_dept, 'departments',
			array('departmentid'), array('real_name' => $cabinet), 'queryOne');
	} else {
		$cabinetID = 0;
	}

	$allSett = getTableInfo($db_dept, 'settings_info_list', array (), array(),
		'queryAll');

	$bcSett = getTableInfo($db_dept, array('barcode_settings', 'settings_info_list'),
		array('barcode_settings.id', 'info_list_id', 'setting', 'display_text',
		'value', 'value_text'), array('info_list_id=settings_info_list.id',
		'cabinet_id='.$cabinetID), 'queryAll');

	if(count($bcSett) != 3) {
		$res = $db_dept->extended->autoExecute('barcode_settings',
			array('cabinet_id' => $cabinetID, 'info_list_id' => 1));
		dbErr($res);
		$res = $db_dept->extended->autoExecute('barcode_settings',
			array('cabinet_id' => $cabinetID, 'info_list_id' => 4));
		dbErr($res);
		$res = $db_dept->extended->autoExecute('barcode_settings',
			array('cabinet_id' => $cabinetID, 'info_list_id' => 8));
		dbErr($res);
	}

	$bcSett = getTableInfo($db_dept, array('barcode_settings', 'settings_info_list'),
		array('barcode_settings.id', 'info_list_id', 'setting', 'display_text',
		'value', 'value_text'), array('info_list_id=settings_info_list.id',
		'cabinet_id='.$cabinetID), 'queryAll');
	
	$xmlObj = new xml('barcode_settings');
	foreach($bcSett as $setting) {
		$attArr = array('disp'	=> $setting['display_text'],
						'id'	=> $setting['id'],
						'info_id'	=> $setting['info_list_id']);
		$parentEl = $xmlObj->createKeyAndValue('setting',NULL,$attArr);
		foreach($allSett as $mySett) {
			if($mySett['setting'] == $setting['setting']) {
				$attArr = array('info_id'	=> $mySett['id'],
								'disp'		=> $mySett['value_text'] );
				$xmlObj->createKeyAndValue('possible',NULL,$attArr,$parentEl);
			}
		}
	}
	$xmlObj->setHeader();
}

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$db_object = $user->getDbObject();
	if( isSet($_POST['cab'] ) ) {
		$cab = $_POST['cab'];
	}

	if( isSet($_GET['cabinfo'] ) ) {
		getCabInfo($db_object, $cab);
	} elseif( isSet($_GET['editCabinet'] ) ) {
		editIndiceController($db_object,$user);
	} elseif( isSet($_GET['createCab'] ) ) {
		createNewCabinet($db_object,$user);
		$user->setSecurity(true);
	} elseif(isset($_GET['getCabinetList'])) {
		xmlGetCabinetList($db_object,$user);
	} elseif(isset($_GET['legacyInt'])) {
		extFileMove($db_object,$user);
	} elseif(isset($_GET['getBarcodeSettings'])) {
		xmlGetBarcodeSettings($db_object, $_GET['cabinet']);
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
