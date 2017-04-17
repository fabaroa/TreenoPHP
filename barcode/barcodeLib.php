<?php
require_once '../lib/filter.php';

function printBarcode($db_name, $db_object, $barcode, $username, $cabinet,
		$docID, $subfolder, $workflow, $uid, $user, $db_doc, $tabID = NULL) {
		//error_log("made it to printbarcode");
	echo<<<HTML
	<div style="height:300px">
	<div>
		<span style="font-size: 14pt">Printed By: {$user->username}</span>
	</div>
HTML;

	$depInfo = getLicensesInfo($db_doc, $db_name);
	$row = $depInfo->fetchRow ();
	$arbDeptName = $row['arb_department'];
	$match = array ();
	preg_match('/[0-9].*/', $db_name, $match);
	if($match) {
		$dbID = $match[0];
	} else {
		$dbID = 0;
	}

	//TEMPORARY PLACE FOR BARCODE SETTINGS
	$gblStt = new GblStt($db_name, $db_doc);
	$settingsList = new settingsList($db_doc, $db_name, $db_object);
	$deleteBarcode = 1;
	$splitType = 'stif';
	$compress = 1; 
	$documentView = true;
	
	if($username) {
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->get('username', $username);
		$barcode = "$dbID 0 ".$DO_user->id;
	} elseif($cabinet) {
		$res = getTableInfo($db_object,'departments',array('departmentname','departmentid'),array('real_name'=>$cabinet),'queryRow');
		$arbCabName = $res['departmentname'];
		$cabID = $res['departmentid'];
		
		$settingsPermissions = $settingsList->getSettingsList();
		$cabSettings = array ();
		if (isset ($settingsPermissions[$cabinet])) {
			$cabSettings = $settingsPermissions[$cabinet];
		}
		if( isSet($cabSettings['deleteBC']) ) {
			$deleteBarcode = $cabSettings['deleteBC'];
		}
		if( isSet($cabSettings['compress']) ) {
			$compress = $cabSettings['compress'];
		}
		if(isset($cabSettings['bcFormat-mtif']) && $cabSettings['bcFormat-mtif'] == 1) {
			$splitType = 'mtif';
		} elseif( isset($cabSettings['bcFormat-asis']) && $cabSettings['bcFormat-asis'] == 1) {
			$splitType = 'asis';
		} elseif( isset($cabSettings['bcFormat-pdf']) && $cabSettings['bcFormat-pdf'] == 1) {
			$splitType = 'pdf';
		} else {
			$splitType = 'stif';
		}

		//$cabID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cabinet), 'queryOne');
		$barcode = "$dbID $cabID";
		if($docID) {
			$barcode .= " $docID";
			$subFolderP = '';
//error_log("fileID: ".$tabID." and subfolder: ".$subfolder."**");			
			if($subfolder && $subfolder != 'main') {
				if($tabID) {
					$whereArr = array('id' => $tabID);
				} else {
					$whereArr = array(
						"doc_id"	=> (int)$docID,
						"subfolder"	=> $subfolder, 
						"filename"	=> 'IS NULL',
						"display"	=> 1,
						"deleted"	=> 0
							 );
				}
				$res = getTableInfo($db_object,$cabinet."_files",array('id','document_id','document_table_name'),$whereArr);
				$row = $res->fetchRow();				
				$documentName = $row['document_table_name'];
				$documentView = isset($documentName) && $documentName != "";
				$documentID = $row['document_id'];
				$wArr = array('document_table_name' => $documentName);
				$docDefInfo = getTableInfo($db_object,'document_type_defs',array('id', 'document_type_name'),$wArr,'queryRow');
				$docDefID = $docDefInfo['id'];
				$docType = $docDefInfo['document_type_name'];
				if( strcmp($docType, "") == 0 ) {
					$subFolderP = "<p>$documentStr: ".h($subfolder)."</p>\n";
					$subFolderP .= "<p>$documentTypeStr: ".h($subfolder)."</p>";
					$tab = $subfolder;
				} else {
					$sArr = array('document_field_value');
					$wArr = array(	'document_defs_list_id' => (int)$docDefID,
									'document_id'			=> (int)$documentID);
					$docIndices = getTableInfo($db_object,'document_field_value_list',$sArr,$wArr,'queryCol');
					//error_log("barcodeLip.php print document docIndices count - ".count($docIndices)."; indeices -".print_r($docIndices, true));
					if(isset($docIndices)&& count($docIndices)>0)
					{
						$tab = implode(" ",$docIndices);
						$subFolderP = "<p>Document: ".h(implode(" ", $docIndices))."</p>\n";
						$subFolderP .= "<p>Document Type: $docType</p>";
					}
					else
					{
						$tab = $docType;						
						$subFolderP = "<p>Document Type: $docType</p>";
					}
				}
				$subID = $row['id'];
				$barcode .= " ".$subID;
			}
		} elseif($workflow) {
			$info = getWFDefsInfo($db_object, $workflow);
			$wfDefsID = $info[1];
			$barcode = 'W '.$barcode.' '.$wfDefsID.' '.$uid;
			$wfOwner = getTableInfo($db_doc, 'users', array ('username'), array
					('id' => (int) $uid), 'queryOne');
		}
	}

	if($username) {
		echo "<div>\n";
		echo "<span style=\"font-size: 14pt\">User Inbox: $username</span>";
		echo "</div>\n";
		$auditString = "User Inbox: $username\n";
	}
	    
	echo "<div>\n";
	echo "<span style=\"font-size: 12pt\">Department: $arbDeptName</span>\n";
	echo "</div>\n";
	$barcodeRec = false;
	if($cabinet) {
		echo "<div>\n";
		echo "<span style=\"font-size: 12pt\">Cabinet: $arbCabName</span>\n";
		echo "</div>\n";
		$barcode_field = "";
		if($docID) {
			echo $subFolderP;
			$uniqueArray = getCabIndexArr($docID, $cabinet, $db_object);
			$barcode_field = implode(' ',$uniqueArray);
			if( $subFolderP ) {
				if($documentView ) {
					$barcode_field .= " Document:$tab";
				} else {
					$barcode_field .= " Tab:$tab"; 
				}
			}

			$ct = 0;
			echo "<table style='width:100%'>\n";

			$keys = array_keys($uniqueArray);
			$vals = array_values($uniqueArray);
			for($i=0;$i<count($keys);$i++) {
				$keys[$i] = h($keys[$i]);
				$vals[$i] = h($vals[$i]);
				echo "<tr>\n";
				echo "<td width='33%'>\n";
				echo "<span style='font-weight:bold'>".str_replace('_', ' ', $keys[$i]).": </span>";
				echo "<span>".$vals[$i]."</span>";
				echo "</td>\n";

				$i++;
				if ($i < count ($keys)) {
					echo "<td width='33%'>\n";
					echo "<span style='font-weight:bold'>".str_replace('_', ' ', $keys[$i]).": </span>";
					echo "<span>".$vals[$i]."</span>";
					echo "</td>\n";
				} else {
					echo "<td width='33%'>\n";
					echo "<span style='font-weight:bold'>&nbsp;</span>";
					echo "<span>&nbsp;</span>";
					echo "</td>\n";
				}

				$i++;
				if ($i < count ($keys)) {
					echo "<td width='33%'>\n";
					echo "<span style='font-weight:bold'>".str_replace('_', ' ', $keys[$i]).": </span>";
					echo "<span>".$vals[$i]."</span>";
					echo "</td>\n";
				} else {
					echo "<td width='33%'>\n";
					echo "<span style='font-weight:bold'>&nbsp;</span>";
					echo "<span>&nbsp;</span>";
					echo "</td>\n";
				}
				echo '</tr>';
			}
			echo "</table>\n";
			$barcodeRec = true;
		}
		if($workflow) {
			echo "<div>\n";
			echo "<span>Workflow: $workflow</span>\n";
			echo "</div>\n";
			echo "<div>\n";
			echo "<span>Workflow Owner: $wfOwner</span>\n";
			echo "</div>\n";
		}

		lockTables($db_doc,array('barcode_reconciliation'));
		$barcodeArr = array(	"barcode_info"		=> $barcode,
								"barcode_field"		=> substr($barcode_field, 0,
									255),
								"cab"				=> $cabinet,
								"username"			=> $user->username,
								"date_printed"		=> date('Y-m-d H:i:s'), 
								'delete_barcode'	=> $deleteBarcode,
								'split_type'		=> $splitType,
								'compress'			=> $compress,
								"department"		=> $db_name );
		$db_doc->extended->autoExecute("barcode_reconciliation",$barcodeArr);
		$barcode = getTableInfo($db_doc,'barcode_reconciliation',array('MAX(id)'),array(),'queryOne');
		unlockTables($db_doc);
		$auditString = "Barcode field: $barcode_field\n";
		$auditString .= "Cabinet: $cabinet\n";
	} elseif($username) {
		lockTables($db_doc,array('barcode_reconciliation'));
		$barcodeArr = array(	"barcode_info"		=> $barcode,
								"cab"				=> $username,
								"username"			=> $user->username,
								'delete_barcode'	=> $deleteBarcode,
								'split_type'		=> $splitType,
								'compress'			=> $compress,
								"date_printed"		=> date('Y-m-d H:i:s'), 
								"department"		=> $db_name );
		$db_doc->extended->autoExecute("barcode_reconciliation",$barcodeArr);
		$barcode = getTableInfo($db_doc,'barcode_reconciliation',array('MAX(id)'),array(),'queryOne');
		unlockTables($db_doc);
	} else {
		$auditString = "Barcode: $barcode\n";
	}

	$auditString .= "Printed by user: ".$user->username."\n";
	$auditString .= "Date printed: ".date('Y-m-d H:i:s')."\n";
	$auditString .= "Department: $db_name\n";

	echo <<<HTML
	</div>
	<div class="barcode">
HTML;
	/*
	if(isset($workflow)) {
		$barcode = $barcodeID;
	} else {
		$barcode = strtoupper($barcode);
	}
	*/
	//$user->audit("barcode printed",$barcode);
	$user->audit("barcode printed", $auditString);

	//echo "<img src=\"outputBarcode.php?barcode=$barcode\" alt=\"$barcode\">\n";
	$type = "C128B";
	$style = 196;
	$drawtext = 'on';
	$width = "650";
	$height = "120";
	$xres = "2";
	$font = "4";

	$barcodeInfo = array(
		'code'		=> strtoupper($barcode),
		'type'		=> $type,
		'style'		=> $style,
		'width'		=> $width,
		'height'	=> $height,
		'xres'		=> $xres,
		'font'		=> $font
	);

	$getArr = array();
	foreach($barcodeInfo as $key => $value) {
		$getArr[] = "$key=$value";
	}
	$getStr = '?'.implode('&amp;', $getArr);
	echo "<img src=\"../barcode/image.php$getStr\" alt=\"$barcode\">\n";
	echo "</div>";
}

function printBarcodeHeader() {
	echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Print Barcode</title>
<script type="text/javascript">
function printThisFrame()
{
	//if(document.all)
		document.body.focus();
	if(document.getElementsByTagName('img').length > 0) {
		window.print();
	} else {
		alert('There are no barcodes to print');
	}
}
</script>
<style type="text/css">
table {
  border-collapse: collapse;
}
td {
  border: 1px solid black;
  font-size: 12pt;
}
div.barcode {
  text-align: center;
  margin: auto;
}
</style>
</head>
<body onload="printThisFrame()">
HTML;
}

function printBarcodeFooter() {
	echo <<<HTML
</div>
</body>
</html>
HTML;
}

?>
