<?PHP
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/xmlObj.php';

function getCabinetFormats($db_dept, $cabinetID) {

	$fieldInfo = getTableInfo($db_dept, 'field_format', 
		array('field_name', 'required', 'regex', 'display', 'is_date'),
		array('cabinet_id' => $cabinetID), 'getAssoc'
	);
	return $fieldInfo;
}

function xmlGetFieldFormats($enArr, $user, $db_doc, $db_dept) {
	$cabinet = $enArr['cabinet'];
	$gblStt = new GblStt($user->db_name, $db_doc);
	$cabinetID = getTableInfo($db_dept, 'departments', array('departmentid'), array('real_name' => $cabinet), 'queryOne');
	$indices = getCabinetInfo($db_dept, $cabinet);
	$fieldInfo = getCabinetFormats($db_dept, $cabinetID);

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION","fillFieldFormats(XML)");
	$parentEl = $xmlObj->createKeyAndValue('INDICES');
	foreach($indices AS $index) {
		$required = 0;
		$regex = "";
		$display = "";
		$isdate = 0;

		$indexingTypeDefs = $gblStt->get("dt,".$user->db_name.",$cabinetID,$index");
		if($indexingTypeDefs != NULL AND $indexingTypeDefs != "") {
			$indexingTypeDefs = 1;
		} else {
			$indexingTypeDefs = 0;
		}
		if( array_key_exists($index, $fieldInfo) ) {
			$indexInfo = $fieldInfo[$index];
			$required = $indexInfo['required'];
			$regex = $indexInfo['regex'];
			$display = $indexInfo['display'];
			$isdate = $indexInfo['is_date'];
		}

		$index = str_replace("_", " ", $index); 
		$attrArr = array("index" => $index, "required" => $required, 
			"regex" => $regex, "display" => $display, "isdate" => $isdate,
			"indexingTypeDefs" => $indexingTypeDefs);
		$xmlObj->createKeyAndValue("INDEX", NULL, $attrArr, $parentEl);
	}
	$xmlObj->setHeader();
}

function xmlAddFieldFormats($enArr, $user, $db_doc, $db_dept) {
	$numIndex = $enArr['numIndex'];
	if(isSet($enArr['cabinet'])) {
		//If they're setting cabinet index requirements, we're going to delete any old index requirements for that cabinet.
		$cabinet = $enArr['cabinet'];
		$cabinetID = getTableInfo($db_dept, 'departments', array('departmentid'), array('real_name' => $cabinet), 'queryOne');
		$query = "DELETE FROM field_format WHERE cabinet_id = $cabinetID";
		$res =& $db_dept->query($query);
		dbErr($res);
	} else {
		//else they're setting document type index requirements so we're going to delete any old doc type requirements for that document type.
		$docType = $enArr['document'];
		$query = "DELETE FROM field_format WHERE document_table_name = '$docType'";
		$res =& $db_dept->query($query);
		dbErr($res);
	}
	//loop through each index requirement and insert it into field_format.
	for($i = 0; $i < $numIndex; $i++) {
		$fieldName = $enArr["index-$i"];
		$required = $enArr["required-$i"];
		$regex = $enArr["regex-$i"];
		$display = $enArr["display-$i"];
		$isdate = $enArr["isdate-$i"];

		if($required == "1") {
			$required = 1;
		} else {
			$required = 0;
		}

		if($isdate == "1") {
			$isdate = 1;
		} else {
			$isdate = 0;
		}

		if($regex === NULL OR $regex == "DISABLED") {
			$regex = "";
		}
		
		if($display === NULL OR $display == "DISABLED") {
			$display = "";
		}

		$insertArr = array(
			"field_name"	=> str_replace(" ", "_", $fieldName),
			"required"		=> $required,
			"regex"			=> $regex,
			"display"		=> $display,
			"is_date"		=> $isdate,
			"document_table_name" => " "
		);
		
		if(isSet($cabinet)) {$insertArr['cabinet_id'] = $cabinetID;} 
		else {$insertArr['document_table_name'] = $docType;}
		$result = $db_dept->extended->autoExecute("field_format", $insertArr);
		dbErr($result);
	}

	$xmlObj = new xml("ENTRY");
	$xmlObj->createKeyAndValue("FUNCTION", "setMessage(XML)");
	$xmlObj->createKeyAndValue("message", "Field Format updated successfully");
	$xmlObj->setHeader();
}
?>
