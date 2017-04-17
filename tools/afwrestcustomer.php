<?php
include_once '../lib/odbc.php';
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';
//get contents of xml document
//$xmlStr = file_get_contents('php://input');
//error_log("afwrestcustomer.php before decode: ".$xmlStr);
$xmlStr = urldecode(file_get_contents('php://input'));
//error_log("afwrestcustomer.php after decode: ".$xmlStr);

//set variables 
$cabinet = '';
$dept = '';
$myUniqueKeys = array();
$keepNonUniqueFieldsUnchanged = false;
$where = parseWhereXml( $xmlStr, $cabinet, $dept, $myUniqueKeys, $keepNonUniqueFieldsUnchanged  );
$db_raw = getDbObject( 'docutron' );
if( PEAR::isError( $db_raw ) ) {
	print_r($db_raw );
	die('bad');
}
//
//get odbc connector
//$db_object = getODBCDbObject( 1, $db_raw );
$whereArr = array ();
//switch on each cabinet name
//install the custom file if it exists
if(isSet($DEFS['CUSTOM_LIB']))
{
	include_once($DEFS['CUSTOM_LIB']);
}
switch( $cabinet ) {
	//added for SWK, each new cabinet will require this function
	case 'AP':
		//go get navision (swk).
		$function_name = "GetCustom{$cabinet}";
		if(function_exists("$function_name"))
		{
			$db_dept = getDBObject('client_files');
			$fields = $function_name($db_dept, $where['po']);
			$index = array();
			$value = array();
			foreach($fields as $key=>$val)
			{
				$index[] = $key;
				$value[] = $val;
			}
		}
		createxml( $cabinet, $index, $value, '' );
		return 0;
	break;
	case 'Customers' :
		//set variables
/*		$table = 'AFW_Customer';
		$uniqueid = 'custno';
		$name = escapeMSQ($where['Customer_Name']);
		$nameArr = explode( ' ', $name );
		$name = $nameArr[0];
		$whereArr[0] = "(firstname like '$name%' OR midname like '$name%'" .
			" OR lastname like '$name%')";
		$addr1 = escapeMSQ($where['Address_1']);
		if( $addr1 != '' )
			$whereArr[] = "addr1='$addr1'";
		$addr2 = escapeMSQ($where['Address_2']);
		if( $addr2 != '' )
			$whereArr[] = "addr2='$addr2'";
		$select = "SELECT $uniqueid FROM $table WHERE ";
		$select.=implode(' and ',$whereArr);
		//get from odbc database
		$res = $db_object->queryAll( $select );
		if(count($res) > 1 and count($nameArr) > 1) {
			$newName = $nameArr[count($nameArr) - 1];
			$whereArr[0] = "(firstname like '$newName%' OR midname like " .
				"'$newName%' OR lastname like '$newName%')";
			$select = "SELECT $uniqueid FROM $table WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
		}
		if((count($res) == 0 || count($res) > 1) and count($nameArr) > 1) {
			$newName = $nameArr[0] . ' '. $nameArr[1];
			$whereArr[0] = "(firstname like '$newName%' OR midname like " .
				"'$newName%' OR lastname like '$newName%')";
			$select = "SELECT $uniqueid FROM $table WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
		}
		$custno = $res[0][$uniqueid];
*/
		$custno = doCustomer($db_object, $where);
		createxml( $cabinet, 'customer_number', $custno, '' );
		break;
	case 'Claims' :
		$table = 'AFW_Claim';
		$uniqueid = 'claimid';
		$whereArr = array();
		$tables = array( 'AFW_Claim' );
		$claimstatus = escapeMSQ($where['Claim_Status']);
		$lossdate = formatDate( $where['Loss_Date'] );
		if( $claimstatus!='' )
			$whereArr[] = "claimstatus='$claimstatus'";
		$losstype = escapeMSQ($where['Kind_of_Loss']);
		if( $losstype!='' )
			$whereArr[] = "causeofloss='$losstype'";
		$claimno = escapeMSQ($where['Claim_Number']);
		if( $claimno != '' )
			$whereArr[] = "claimno='$claimno'";
		$polno = escapeMSQ($where['Policy_Number']);
		if( $polno!='' ){
			if( !in_array( 'AFW_BasicPolInfo', $tables ) ){
				$whereArr[]="AFW_BasicPolInfo.PolId=AFW_Claim.PolId";
				$tables[]="AFW_BasicPolInfo";
				$whereArr[]="AFW_BasicPolInfo.PolNo='$polno'";
			}else{
				$whereArr[]="AFW_BasicPolInfo.PolNo='$polno'";
			}
		}
		$custname = escapeMSQ($where['Customer_Name']);
		$custname = explode( ' ', $custname );
		$custname = $custname[0];
		if( $custname!='' ){
			$whereArr[] = "AFW_BasicPolInfo.CustId=AFW_Customer.CustId";
			$whereArr[] = "(AFW_Customer.FirstName like '$custname%' Or " .
				"AFW_Customer.MidName LIKE '$custname%' OR " .
				"AfW_Customer.LastName like '$custname%')";
			$tables[] = "AFW_Customer";
		}
		if( $lossdate!='' )
			$whereArr[] = "LossDate='$lossdate'";
		$table = implode( ',',$tables );	
		$select = "SELECT $uniqueid FROM $table WHERE ";
		$select.=implode(' and ',$whereArr);
		//get from odbc database
		$res123 = $db_object->QueryAll('||SQLCOLUMNS|||AFW_Claim');//
		$res = $db_object->queryAll( $select );
		$custno = $res[0][$uniqueid];
		if (PEAR::isError($res)) {
			$error = $res->getMessage();
			#$error = print_r($res, true);
		} else {
			$error = $select;
		}
		createxml( $cabinet, 'claim_id', $custno, $error );
		return 0;
		break;
	case 'Policies' :
		$uniqueid = 'polid';
		$tables[] = 'AFW_BasicPolInfo';
//		$poltypelob = escapeMSQ($where['Coverage_Type']);
//		if( $poltypelob!='' )
//			$whereArr[] = "poltypelob='$poltypelob'";
		$name = escapeMSQ($where['Customer_Name']);
		$name = explode( ' ', $name );
		$name = $name[0];
		if( $name!='' ){
			$whereArr[] = "AFW_Customer.CustId=AFW_BasicPolInfo.CustId";
			$whereArr[] = "(AFW_Customer.FirstName like '$name%' Or " .
				"AFW_Customer.MidName like '$name%' or " .
				"AfW_Customer.LastName like '$name%')";
			$tables[] = 'AFW_Customer';
		}
		$polno = escapeMSQ($where['Policy_Number']);
		if( $polno!='' )
			$whereArr[] = "polno='$polno'";
		$poleffdate = $where['Effective_Date'];
		$poleffdate = formatDate( $poleffdate );
		if( $poleffdate!='' )
			$whereArr[] = "PolEffDate='$poleffdate'";
		$transArr = escapeMSQ($where['tempTransDateDetail']);
		$transArr = explode( " ", $transArr );
		$transdate = formatDate($transArr[0]);
		$transdesc = $transArr[1];
		if( $transdate!= '' ){
			$whereArr[]="AFW_BasicPolInfo.PolId=AFW_PolicyTransaction.PolId";
			$whereArr[]="AFW_PolicyTransaction.Description like '$transdesc%'";
			$tables[] = 'AFW_PolicyTransaction';
		}
		$table = implode( ',', $tables );
		$select = "SELECT * FROM $table";
		$select .= " WHERE ";
		$select.=implode(' and ',$whereArr);
		//get from odbc database
		$res = $db_object->queryAll( $select );
		if( PEAR::isError($res) ){
				print_r( $res );
				die('bad in policy');
		}
		$polid = $res[0][$uniqueid];
		createxml( $cabinet, 'policy_id', $polid, $select );
		return 0;
		break;
	case 'Activity' :
		$tables = array('AFW_Transaction');
		$action = escapeMSQ($where['Action']);
		$custname = escapeMSQ($where['Customer_Name']);
		$descLoc = strpos($where['Description'], "\r\n");
		if($descLoc !== false) {
			$desc = substr($where['Description'], 0, $descLoc);
		} else {
			$desc = $where['Description'];
		}
		$desc = escapeMSQ(substr($desc, 0, 10));
		$effdate = formatDate($where['Effective_Date'],false);
		$polno = escapeMSQ($where['Policy_Number']);
		if( $action!='' )
			$whereArr[] = "DBAction='$action'";
		$custnameArr = explode( ' ', $custname );
		$custname = $custnameArr[0];
		if( $custname!='' ){
			$tables[] = 'AFW_Customer';
			$whereArr[] = 'AFW_Transaction.CustId=AFW_Customer.CustId';
			$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
				"AFW_Customer.MidName like '$custname%' OR " .
				"AFW_Customer.LastName like '$custname%')";
		}
		if( $desc!='' )
			$whereArr[]="Comment1 like '$desc%'";
		if( $effdate!='' ){
			$whereArr[]="EndEffDate like '$effdate%'";
		}
		if( $polno!='' ){
			$tables[] = 'AFW_BasicPolInfo';
			$whereArr[]="AFW_BasicPolInfo.PolNo='$polno'";
			$whereArr[]="AFW_Transaction.PolId=AFW_BasicPolInfo.PolId";
		}
		if ($where['Tran_Date']) {
			$tranDate = formatDate($where['Tran_Date'], false, $where['Tran_Time']);
			$whereArr[] = "TranDate = '$tranDate'";
		}
		$uniqueid = 'tranid';
		$table = implode(',', $tables );
		$select = "SELECT * FROM $table";
		$select .= " WHERE ";
		$select.=implode(' and ',$whereArr);
		//get from odbc database
		$res = $db_object->queryAll( $select );
		if( PEAR::isError($res) ){
				print_r( $res );
				die('bad in activity');
		}
		if( sizeof( $res ) > 1 and sizeof($custnameArr) > 1 ){
			//try the other where clause with two name pieces
			$custname = $custnameArr[0] . ' ' . $custnameArr[1];
			$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
				"AFW_Customer.MidName like '$custname%' OR " .
				"AFW_Customer.LastName like '$custname%')";
			$select = "SELECT * FROM $table";
			$select .= " WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
		}
		$transid = $res[0][$uniqueid];
		if(!$transid) {
			$whereArr = array();
			$tables = array('AFW_Transaction');
			$action = escapeMSQ($where['Action']);
			$custname = escapeMSQ($where['Customer_Name']);
			$descLoc = strpos($where['Description'], "\r\n");
			if($descLoc !== false) {
				$desc = substr($where['Description'], 0, $descLoc);
			} else {
				$desc = $where['Description'];
			}
			$desc = escapeMSQ(substr($desc, 0, 10));
			$effdate = formatDate($where['Effective_Date'],false);
			$polno = escapeMSQ($where['Policy_Number']);
			if( $action!='' )
				$whereArr[] = "DBAction='$action'";
			$custname = explode( ' ', $custname );
			$custname = $custname[0];
			if( $custname!='' ){
				$tables[] = 'AFW_Customer';
				$whereArr[] = 'AFW_Transaction.CustId=AFW_Customer.CustId';
				$whereArr['custname'] = "( AFW_Customer.FirstName like '$custname%' or " .
					"AFW_Customer.midname like '$custname%' or " .
					"AFW_Customer.LastName like '$custname%')";
			}
			if( $desc!='' )
				$whereArr[]="Comment1 like '$desc%'";
			if( $effdate!='' ){
				$whereArr[]="EndEffDate like '$effdate%'";
			}
			if ($where['Tran_Date']) {
				$tranDate = formatDate($where['Tran_Date'], false, $where['Tran_Time']);
				$whereArr[] = "TranDate = '$tranDate'";
			}
			$uniqueid = 'tranid';
			$table = implode(',', $tables );
			$select = "SELECT * FROM $table";
			$select .= " WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
			if( PEAR::isError($res) ){
				$fd = fopen($DEFS['TMP_DIR'] .'/badAfW', 'w+');
				fwrite($fd, print_r( $res, true ));
				fwrite($fd, 'bad in activity');
				fclose($fd);
				die();
			}
			if( sizeof( $res ) > 1 and sizeof($custnameArr) > 1 ){
				//try the other where clause with two name pieces
				$custname = $custnameArr[0] . ' ' . $custnameArr[1];
				$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
					"AFW_Customer.MidName like '$custname%' OR " .
					"AFW_Customer.LastName like '$custname%')";
				$select = "SELECT * FROM $table";
				$select .= " WHERE ";
				$select.=implode(' and ',$whereArr);
	
				//get from odbc database
				$res = $db_object->queryAll( $select );
			}
			$transid = $res[0][$uniqueid];
		}
		createxml( $cabinet, 'tran_id', $transid, $select);
		return 0;
		break;
	case 'Activities' :
		$tables = array('AFW_Transaction');
		$action = escapeMSQ($where['Action']);
		$custname = escapeMSQ($where['Customer_Name']);
		$descLoc = strpos($where['Description'], "\r\n");
		if($descLoc !== false) {
			$desc = substr($where['Description'], 0, $descLoc);
		} else {
			$desc = $where['Description'];
		}
		$desc = escapeMSQ(substr($desc, 0, 10));
		$effdate = formatDate($where['Effective_Date'],false);
		$polno = escapeMSQ($where['Policy_Number']);
		if( $action!='' )
			$whereArr[] = "DBAction='$action'";
		$custnameArr = explode( ' ', $custname );
		$custname = $custnameArr[0];
		if( $custname!='' ){
			$tables[] = 'AFW_Customer';
			$whereArr[] = 'AFW_Transaction.CustId=AFW_Customer.CustId';
			$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
				"AFW_Customer.MidName like '$custname%' OR " .
				"AFW_Customer.LastName like '$custname%')";
		}
		if( $desc!='' )
			$whereArr[]="Comment1 like '$desc%'";
		if( $effdate!='' ){
			$whereArr[]="EndEffDate like '$effdate%'";
		}
		if( $polno!='' ){
			$tables[] = 'AFW_BasicPolInfo';
			$whereArr[]="AFW_BasicPolInfo.PolNo='$polno'";
			$whereArr[]="AFW_Transaction.PolId=AFW_BasicPolInfo.PolId";
		}
		if ($where['Tran_Date']) {
			$tranDate = formatDate($where['Tran_Date'], false, $where['Tran_Time']);
			$whereArr[] = "TranDate = '$tranDate'";
		}
		$uniqueid = 'tranid';
		$table = implode(',', $tables );
		$select = "SELECT * FROM $table";
		$select .= " WHERE ";
		$select.=implode(' and ',$whereArr);
		//get from odbc database
		$res = $db_object->queryAll( $select );
		if( PEAR::isError($res) ){
				print_r( $res );
				die('bad in activity');
		}
		if( sizeof( $res ) > 1 and sizeof($custnameArr) > 1 ){
			//try the other where clause with two name pieces
			$custname = $custnameArr[0] . ' ' . $custnameArr[1];
			$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
				"AFW_Customer.MidName like '$custname%' OR " .
				"AFW_Customer.LastName like '$custname%')";
			$select = "SELECT * FROM $table";
			$select .= " WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
		}
		$transid = $res[0][$uniqueid];
		if(!$transid) {
			$whereArr = array();
			$tables = array('AFW_Transaction');
			$action = escapeMSQ($where['Action']);
			$custname = escapeMSQ($where['Customer_Name']);
			$descLoc = strpos($where['Description'], "\r\n");
			if($descLoc !== false) {
				$desc = substr($where['Description'], 0, $descLoc);
			} else {
				$desc = $where['Description'];
			}
			$desc = escapeMSQ(substr($desc, 0, 10));
			$effdate = formatDate($where['Effective_Date'],false);
			$polno = escapeMSQ($where['Policy_Number']);
			if( $action!='' )
				$whereArr[] = "DBAction='$action'";
			$custname = explode( ' ', $custname );
			$custname = $custname[0];
			if( $custname!='' ){
				$tables[] = 'AFW_Customer';
				$whereArr[] = 'AFW_Transaction.CustId=AFW_Customer.CustId';
				$whereArr['custname'] = "( AFW_Customer.FirstName like '$custname%' or " .
					"AFW_Customer.midname like '$custname%' or " .
					"AFW_Customer.LastName like '$custname%')";
			}
			if( $desc!='' )
				$whereArr[]="Comment1 like '$desc%'";
			if( $effdate!='' ){
				$whereArr[]="EndEffDate like '$effdate%'";
			}
			if ($where['Tran_Date']) {
				$tranDate = formatDate($where['Tran_Date'], false, $where['Tran_Time']);
				$whereArr[] = "TranDate = '$tranDate'";
			}
			$uniqueid = 'tranid';
			$table = implode(',', $tables );
			$select = "SELECT * FROM $table";
			$select .= " WHERE ";
			$select.=implode(' and ',$whereArr);
			//get from odbc database
			$res = $db_object->queryAll( $select );
			if( PEAR::isError($res) ){
				$fd = fopen($DEFS['TMP_DIR'] .'/badAfW', 'w+');
				fwrite($fd, print_r( $res, true ));
				fwrite($fd, 'bad in activity');
				fclose($fd);
				die();
			}
			if( sizeof( $res ) > 1 and sizeof($custnameArr) > 1 ){
				//try the other where clause with two name pieces
				$custname = $custnameArr[0] . ' ' . $custnameArr[1];
				$whereArr['custname'] = "(AFW_Customer.FirstName like '$custname%' or " .
					"AFW_Customer.MidName like '$custname%' OR " .
					"AFW_Customer.LastName like '$custname%')";
				$select = "SELECT * FROM $table";
				$select .= " WHERE ";
				$select.=implode(' and ',$whereArr);
	
				//get from odbc database
				$res = $db_object->queryAll( $select );
			}
			$transid = $res[0][$uniqueid];
		}
		createxml( $cabinet, 'tran_id', $transid, $select);
		return 0;
		break;
	// Great plains integration
	//case 'GP_Receivables_Transaction_Entry':
	//case 'GP_Payables_Payments_Zoom':
	//case 'GP_Payables_Transaction_Entry':
	//case 'GP_Transaction_Entry':
		//SearchTreenoDB($dept, $cabinet, $where, $myUniqueKeys );
		//return 0;
		//break;
	default :
		SearchTreenoDB($dept, $cabinet, $where, $myUniqueKeys, $keepNonUniqueFieldsUnchanged );
		break;
}
//close all resources
if(isset($db_object))
	$db_object->disconnect();

if(isset($db_raw))
	$db_raw->disconnect();

//formats the date to AFW hacked date format.
function formatDate($startdate, $keepend=true, $startTime=''){
	if( $startdate=='' ){
		return "";	
	}
    $time = strtotime($startdate);
    $M = date("M",$time);
    $j = date("j",$time);
    $Y = date("Y",$time);
    if( $keepend ) {
	    $end = " 12:00:00:000AM";
	} elseif ($startTime) {
		if (strlen($startTime) == 7) {
			$end = ' '.substr($startTime, 0, 5) . ":00:000" . substr ($startTime, 5, 2);
		} else {
			$end = ' 0'.substr($startTime, 0, 4) . ":00:000" . substr ($startTime, 4, 2);
		}
	}
    if($j>9)
        $enddate = "$M $j $Y".$end;
    else
        $enddate = "$M  $j $Y".$end;
    return $enddate;
}
//parses out the where clause values and fieldnames and sets cabinet
//returns the where variable used in all the switch statements
function parseWhereXml( $xmlStr, &$cabinet, &$dept = '', &$uniqueKeys = array(), &$keepNonUniqueFieldsUnchanged = false ){
	$whereArr = array();
	if (substr(PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem($xmlStr);
		$domcabinet = $domDoc->get_elements_by_tagname( 'cabinet' );
		$item = $domcabinet[0];
		$cabinet = $item->get_content();
		$domterms = $domDoc->get_elements_by_tagname( 'term' );
		for( $i=0; $i<  count($domterms); $i++ ){
			$domelement = $domterms[$i];
			$index = $domelement->get_attribute('index');
			$value = $domelement->get_attribute('value');
			if( $value != '' ){
				$where[$index] = $value;
			} else {
				$where[$index] = '';
			}
		}
	} else {
		$domDoc = DomDocument::loadxml( $xmlStr );
		
		$domDept = $domDoc->getElementsByTagName( 'department' );
		$itemDept = $domDept->item(0);
		$dept = $itemDept->nodeValue;
		
		$domcabinet = $domDoc->getElementsByTagName( 'cabinet' );
		$item = $domcabinet->item(0);
		$cabinet = $item->nodeValue;
		
		$domterms = $domDoc->getElementsByTagName( 'term' );
		for( $i=0; $i<  $domterms->length; $i++ ){
			$domelement = $domterms->item($i);
			$index = $domelement->getAttribute('index');
			$value = $domelement->getAttribute('value');
			$isUniqueKey = $domelement->getAttribute('isUniqueKey');
			if( $value != '' ){
				$where[$index] = $value;			
			} else {
				$where[$index] = '';
			}
			
			if( strtolower($isUniqueKey) == 'true' )
			{
				$uniqueKeys[$index] = $where[$index];
			}
		}
		
		$keepNonUniqueFieldsUnchanged = false;
		$domcabinet = $domDoc->getElementsByTagName( 'keepNonUniqueFieldsUnchanged' );
		if($domcabinet != null)
		{
			$item = $domcabinet->item(0);
			if(($item != null) && ($item->nodeValue == "True"))
			{
				$keepNonUniqueFieldsUnchanged = true;
			}
		}
	}
	return $where;
}
//createxml returns xmldocument for afw integrator
function createxml( $cabinet, $index, $value, $errorvalue='', $afwResult = '' ){
	global $DEFS;
//error_log( $errorvalue );
	if (substr(PHP_VERSION, 0, 1) == '4') {
		if( $value ){
			$doc = domxml_new_doc ('1.0');
			$root = $doc->create_element( "root" );
			$doc->append_child( $root );
			if( is_array($value) )
			{
				$cab = $doc->create_element( "cabinet" );
				$textcab = $doc->create_text_node( $cabinet );
				$root->append_child( $cab );
				$cab->append_child( $textcab );
				for($i = 0; $i < count($value); $i++)
				{
					$key = $index[$i];
					$val = $value[$i]; 
					$term = $doc->create_element( "term" );
					$root->append_child( $term );	
					$term->set_attribute( "index", "$key" );
					$term->set_attribute( "value", $val );				
				}

			}else 
			{
				$term = $doc->create_element( "term" );
				$root->append_child( $term );
				$cab = $doc->create_element( "cabinet" );
				$textcab = $doc->create_text_node( $cabinet );
				$root->append_child( $cab );
				$cab->append_child( $textcab );
				$term->set_attribute( "index", "$index" );
				$term->set_attribute( "value", $value );
			}
			$fd = fopen($DEFS['TMP_DIR'] .'/goodxml.xml', 'w+');
			fwrite($fd, $doc->dump_mem(false));
			fclose($fd);
			echo $doc->dump_mem(false);
		
		}else{
			$doc = domxml_new_doc('1.0'); 
			$root = $doc->create_element( "root" );
			$doc->append_child( $root );
			$cab = $doc->create_element( "error" );
			$textcab = $doc->create_text_node( $errorvalue );
			$root->append_child( $cab );
			$cab->append_child( $textcab );
			$fd = fopen($DEFS['TMP_DIR'] .'/badxml.xml', 'w+');
			fwrite($fd, $doc->dump_mem(false));
			fclose($fd);
			echo $doc->dump_mem(false);
		}
	} else {
			$doc = new DomDocument();
			$root = $doc->createElement( "root" );
			$doc->appendChild( $root );
			
			$tmpfile = $DEFS['TMP_DIR'] .'/goodxml.xml';
			
		if( $value ){	
			$textcab = $doc->createTextNode( $cabinet );
			$cab = $doc->createElement( "cabinet" );
			$root->appendChild( $cab );
			$cab->appendChild( $textcab );
				
			if( is_array($value) )
			{			
				for($i = 0; $i < count($value); $i++)
				{
					$key = $index[$i];
					$val = $value[$i];
					$val = iconv(mb_detect_encoding($val, mb_detect_order(), true), "UTF-8", $val);
		
					$term = $doc->createElement( "term" );
					$root->appendChild( $term );	
					$term->setAttribute( "index", "$key" );
					$term->setAttribute( "value", $val );//htmlentities($header, ENT_XML1));
				}
			}else
			{
				$term = $doc->createElement( "term" );
				$root->appendChild( $term );
				$term->setAttribute( "index", "$index" );
				$term->setAttribute( "value", $value );
			}
		}else{
			$cab = $doc->createElement( "error" );
			$textcab = $doc->createTextNode( $errorvalue );
			$root->appendChild( $cab );
			$cab->appendChild( $textcab );
			$tmpfile = $DEFS['TMP_DIR'] .'/badxml.xml';
		}
		
		if ($afwResult != '')
		{
			$elmAfwResult = $doc->createElement( "afwResult" );
			$txtAfwResult = $doc->createTextNode( $afwResult );
			$root->appendChild( $elmAfwResult );
			$elmAfwResult->appendChild( $txtAfwResult );
		}
			
		$fd = fopen($tmpfile, 'w+');
		fwrite($fd, $doc->savexml());
		fclose($fd);
		//error_log($doc->savexml());
		echo $doc->savexml();
	}
}
function escapeMSQ($myStr)
{
	return str_replace("'", "''", $myStr);
}
function doCustomer($db_afw, $folderArr) {
	$whereArr = array ();
	//set variables
	$table = 'AFW_Customer';
	$uniqueid = 'custno';
	$docUnq = 'customer_number';
	$name = escapeMSQ($folderArr['Customer_Name']);
	$nameArr = explode( ' ', $name );
	$whereArr[0] = "(firstname = '$name' OR midname = '$name'" .
		" OR lastname = '$name')";
	$addr1 = escapeMSQ($folderArr['Address_1']);
	if( $addr1 != '' )
		$whereArr[2] = "addr1='$addr1'";
	$addr2 = escapeMSQ($folderArr['Address_2']);
	if( $addr2 != '' )
		$whereArr[3] = "addr2='$addr2'";
	$select = "SELECT * FROM $table WHERE ";
	$select.=implode(' and ',$whereArr);
	$res = $db_afw->queryAll($select);
	dbErr($res);
	if(count($res) > 1) {
		$whereArr[1] = "status <> 'C'";
		$select = "SELECT * FROM $table WHERE ";
		$select.=implode(' and ',$whereArr);
		$res = $db_afw->queryAll($select);
		dbErr($res);
	}
	if(count($res) > 1) {
		return '';
	} elseif(count($res) == 1) {
		$uniqueID = $res[0]['custno'];
	} else {
		$whereArr[0] = "(firstname LIKE '$name%' OR midname LIKE '$name%'" .
			" OR lastname LIKE '$name%')";
		$select = "SELECT $uniqueid FROM $table WHERE ";
		$select.=implode(' and ',$whereArr);
		$res = $db_afw->queryAll($select);
		if(count($res) > 1) {
			return '';
		} elseif(count($res) == 1) {
			$uniqueID = $res[0]['custno'];
		} else {
			unset($whereArr[0]);
			unset($whereArr[1]);
			$max = count($nameArr);
			if($max > 4) {
				$max = 4;
			}
			for($i = 0; $i < $max; $i++) {
				$whereArr['name'.$i] = "(FirstName like '%{$nameArr[$i]}%' Or " .
					"MidName like '%{$nameArr[$i]}%' or " .
					"LastName like '%{$nameArr[$i]}%')";
			}
			$select = "SELECT * FROM $table";
			$select .= " WHERE ";
			$select.=implode(' and ',$whereArr);
			$res = $db_afw->queryAll($select);
			dbErr($res);
			if(count($res) > 1) {
				return '';
			} elseif(count($res) == 1) {
				$uniqueID = $res[0]['custno'];
			} else {
				for($i = 0; $i < $max; $i++) {
					unset($whereArr['name'.$i]);
					$tmp = $nameArr[$i];
//					if(strpos($tmp, '.') === false) {
						$tmp = str_replace(',', '', $tmp);
						$tmp2 = substr($tmp, 1, strlen($tmp) - 2);
						if($tmp2) {
							$tmp = $tmp2;
						}
						if($tmp) {
							$whereArr['name'.$i] = "(AFW_Customer.FirstName like '%{$tmp}%' Or " .
								"AFW_Customer.MidName like '%{$tmp}%' or " .
								"AfW_Customer.LastName like '%{$tmp}%')";
						}
//					}
				}
				if($whereArr) {
					$select = "SELECT * FROM $table";
					$select .= " WHERE ";
					$select.=implode(' and ',$whereArr);
					$res = $db_afw->queryAll($select);
					dbErr($res);
					if(count($res) > 1) {
						return ''; 
					} elseif(count($res) == 1) {
						$uniqueID = $res[0]['custno'];
					} else {
	/*					unset($whereArr[count($nameArr) - 1]);
						$select = "SELECT * FROM $table";
						$select .= " WHERE ";
						$select.=implode(' and ',$whereArr);
						$res = $db_afw->queryAll($select);
						dbErr($res);
						if(count($res) > 1) {
							return array ('cabinet' => 'INBOX');
						} elseif(count($res) == 1) {
							$uniqueID = $res[0]['custno'];
						} else {
							return array ('cabinet' => 'INBOX');
						}
	*/
						return ''; 
					}
				} else {
					return '';
				}
			}
		}
	}
	return $uniqueID; 
}

function SearchTreenoDB($dept, $cabinet, $intSearch, $myUniqueKeys, $keepNonUniqueFieldsUnchanged = false)
{
	$afwResult ='';
	$errValue ='';
	
	$index = array();
	$value = array();
	
	if(is_array($intSearch))
	{
		if($dept== "client_files15" && strcasecmp($cabinet, "Legacy_Integrator") ==0 )
		{
			if($intSearch["testid"]>=1095 && $intSearch["testid"]<=1110){
				$intSearch_old = $intSearch;
				$cabinet = "Invoices";
				$intSearch = array(
					"invoice_number" => $intSearch_old["testid"]
				);
				$myUniqueKeys = array("invoice_number" => $intSearch_old["testid"], memo=>'');
				
			}
		}
		
		$index = array_keys($intSearch);
		$value = array_values($intSearch);
		
		$db_dept = getDBObject($dept);
		
		$selArr = $index;
		array_push($selArr, 'doc_id');
		error_log('Select these columns from table ['.$cabinet.']: '.implode($selArr, ","));

		if(!is_array($myUniqueKeys) || count($myUniqueKeys) < 1)
		{
			$errValue = 'Warning: No unique search item provided. All search terms will be used in where clause.';
			error_log($errValue);
			$myUniqueKeys = $intSearch;
		}
		$whereArr = array_merge($myUniqueKeys, array('deleted' => 0));				
		error_log('Where clause : '.print_r($whereArr, true));
		
		$row = getTableInfo($db_dept, $cabinet, $selArr, $whereArr, 'queryRow');
		
		if(count($row) > 0)
		{	
			//error_log('intSearch : '.print_r($intSearch, true));
			//error_log('Selection result : '.print_r($row, true));
			$doc_id = $row['doc_id'];
			$index[]='doc_id';
			$value[] = $doc_id;
			
			$diff = array_diff_assoc($intSearch, $row);
			if((count($diff) > 0)&&($keepNonUniqueFieldsUnchanged == false ))
			{
				error_log('Need to update these fields : '.print_r($diff, true));
				$afwResult = updateTableInfo($db_dept, $cabinet, $diff, array("doc_id='{$doc_id}'"));
				$afwResult = 'Record existed and updated: '.$afwResult.')';
			}
			else
			{
				$afwResult = 'Record existed';
			}	
		}
		else
		{
			$afwResult = 'No record exists';
		}	
	}
	else
	{
		$errValue = 'No search item provided.';
	}
	
	error_log($afwResult);
	createxml( $cabinet, $index, $value, $errValue, $afwResult);//'' );	
	return 0;
}
?>