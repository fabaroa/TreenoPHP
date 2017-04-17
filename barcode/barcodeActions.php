<?php
include_once '../check_login.php';

function getReconciliation($db_object,$user) {
	$orderBy = $_POST['orderby'];
	$direction = $_POST['direction'];
	$page = $_POST['page'];
	$start = (50 * ($page-1));
	$sort = array($orderBy => $direction);
	$search = NULL;
	if( isSet($_POST['search']) ) {
		$search = $_POST['search'];
	} else {
		$search = NULL;
	}

	if($user->isDepAdmin()) {
		$searchUsers = array();
		$distinctUsers = getTableInfo($db_object, 'barcode_reconciliation', 
			array('distinct(username) AS dusers'), array("department='$user->db_name'","username!=''"), 'queryCol'
		);
		foreach($distinctUsers AS $duser) {		
			if( $user->greaterThanUser($duser) ) {
				$searchUsers[] = $duser;
			}
		}
		$count = countBarcodeReconciliation($db_object,$user->db_name,$search, $searchUsers);
		$barcodeHistory = getBarcodeReconciliationInfo($db_object,$start,50,$user->db_name,$sort,$search, $searchUsers);
	} else {
		$count = countBarcodeReconciliation($db_object,$user->db_name,$search,array($user->username));
		$barcodeHistory = getBarcodeReconciliationInfo($db_object,$start,50,$user->db_name,$sort,$search,array($user->username));
	}
	$user->audit('Viewed Unreconciled Barcodes','Search Value: '.$search);
	$arbHeaders = array(	'id'			=>'ID',
							'barcode_info'	=>'Barcode Info',
							'username'		=>'Username',
							'cab'			=>'Cabinet',
							'barcode_field'	=>'Folder',
							'date_printed'	=>'Date Printed' );
	createXML($barcodeHistory,$count,$user,$arbHeaders);
}

function createXML($barcodeHistory,$count,$user,$arbHeaders) {
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$doc = domxml_new_doc ('1.0');
 		$root = $doc->create_element ('ROOT');
 		$root->set_attribute ('total', $count);
 		$doc->append_child ($root);
 		foreach ($barcodeHistory as $history) {
 			$entry = $doc->create_element ('ENTRY');
 			$root->append_child ($entry);
			foreach ($history AS $name => $value) {
 				if ($name != 'department') {
 					if ($name == 'id') {
 						$entry->set_attribute ('id', $value);
 					} elseif ($name == 'cab') {
 						if (array_key_exists ($value, $user->cabArr)) {
 							$value = $user->cabArr[$value];
 						}
 					}
 					$column = $doc->create_element ('COLUMN');
 					$text = $doc->create_text_node ($value);
 					$column->append_child ($text);
 					$entry->append_child ($column);
 				}
 			}
 		}
 	
 		foreach ($arbHeaders as $real => $arb) {
 			$header = $doc->create_element ('HEADER');
 			$root->append_child ($header);
 			$header->set_attribute ('name', $real);
 			$text = $doc->create_text_node ($arb);
 			$header->append_child ($text);
 		}
 		$xmlStr = $doc->dump_mem(true);
 	} else {
 		$doc = new DOMDocument ();
 		$root = $doc->createElement ('ROOT');
 		$root->setAttribute ('total', $count);
 		$doc->appendChild ($root);

 		foreach ($barcodeHistory as $history) {
 			$entry = $doc->createElement ('ENTRY');
 			$root->appendChild ($entry);
 			foreach ($history as $name => $value) {
 				if ($name != 'department') {
 					if ($name == 'id') {
 						$entry->setAttribute ('id', $value);
 					} elseif ($name == 'cab') {
 						if (array_key_exists ($value, $user->cabArr)) {
 							$value = $user->cabArr[$value];
 						}
 					}
 					$column = $doc->createElement ('COLUMN');
 					$column->appendChild ($doc->createTextNode ($value));
 					$entry->appendChild ($column);
 				}
 			}
 		}
 		foreach ($arbHeaders as $real => $arb) {
 			$header = $doc->createElement ('HEADER');
 			$root->appendChild ($header);
 			$header->setAttribute ('name', $real);
 			$header->appendChild ($doc->createTextNode ($arb));
 		}
 		$xmlStr = $doc->saveXML();
  	}

  	header('Content-type: text/xml');
 	echo $xmlStr;
}

function reconcileBarcodes($db_doc, $user, $db_object) {
	$xmlStr = file_get_contents('php://input');
 	$barcodeIDs = array ();
 	if(substr(PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem( $xmlStr );
 		$barcodes = $domDoc->get_elements_by_tagname('RECONCILE');
 		foreach ($barcodes as $barcode) {
 			$barcodeIDs[] = $barcode->get_content();
 		}
 	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML ($xmlStr);
 		$barcodes = $domDoc->getElementsByTagName('RECONCILE');
 		for ($i = 0; $i < $barcodes->length; $i++) {
			$barcode = $barcodes->item($i);
			$tmp = $barcode->firstChild;
 			$barcodeIDs[] = $tmp->nodeValue;
 		}
 	}
 	foreach($barcodeIDs AS $ID) {
 		$barcodeInfo = getTableInfo($db_doc,'barcode_reconciliation',array(),array('id'=>(int)$ID),'queryRow');
		$auditString = "Barcode ID: ".$barcodeInfo['id']."\n";
		$auditString .= "Barcode field: ".$barcodeInfo['barcode_field']."\n";
		$auditString .= "Cabinet: ".$barcodeInfo['cab']."\n";
		$auditString .= "Delete Barcode Page: ".$barcodeInfo['delete_barcode']."\n";
		$auditString .= "Compress Splits: ".$barcodeInfo['compress']."\n";
		$auditString .= "Split Type: ".$barcodeInfo['split_type']."\n";
		$auditString .= "Printed by user: ".$barcodeInfo['username']."\n";
		$auditString .= "Date printed: ".$barcodeInfo['date_printed']."\n";
		$auditString .= "Department: ".$barcodeInfo['department']."\n";
		$user->audit('Deleted Unreconciled Barcodes',$auditString);

		$count = getTableInfo($db_doc, 'barcode_lookup', array('count(id)'), 
			array('id' => (int)$barcodeInfo['id']), 'queryOne');
		if($count == 0) {
			$insertArr = array(	'id'			=> (int)$barcodeInfo['id'],
								'department'	=> $user->db_name);
			$res = $db_doc->extended->autoExecute('barcode_lookup',$insertArr);
			dbErr($res);
		}
		
		$barcodeInfo['barcode_rec_id'] 	= (int)$barcodeInfo['id'];
		unset($barcodeInfo['id']);
		unset($barcodeInfo['department']);
		$barcodeInfo['date_processed'] 	= date('Y-m-d G:i:s');
		$barcodeInfo['description'] 	= 'deleted';
		$barcodeInfo['delete_barcode'] 	= (int)$barcodeInfo['delete_barcode'];
		$barcodeInfo['compress'] 		= (int)$barcodeInfo['compress'];
		$res = $db_object->extended->autoExecute('barcode_history',$barcodeInfo);
		dbErr($res);
		deleteTableInfo($db_doc, 'barcode_reconciliation', array('id'=>(int)$ID));
	}
}

if($logged_in and $user->username) {
	
	if(isset ($_GET['history']) and $_GET['history'] == 1) {
		getReconciliation($db_doc,$user);
	} elseif(isset ($_GET['reconcile']) and $_GET['reconcile'] == 1) {
		reconcileBarcodes($db_doc,$user,$db_object);
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
