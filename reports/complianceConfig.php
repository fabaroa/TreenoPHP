<?php
// $Id: complianceConfig.php 14657 2012-02-06 13:48:38Z acavedon $
/*
 * complianceConfig.php - This script generates a web page for setting up a new
 * or modifying an existing compliance record for a selected cabinet.
 * Select a cabinet from the pulldown. If it already has a compliance for that
 * cabinet, it brings up what you have thus far and allows you to make changes.
 * If you do not have a compliance for that cabinet yet, it creates all of the 
 * necessary database entries and starts you along the way to creating one.
 * 
 * Author:  Al Cavedon
 * Created: 11/17/2010
 */

require_once '../check_login.php';
require_once '../lib/cabinets.php';
require_once '../modules/modules.php';
include_once '../classuser.inc';
include_once '../documents/documents.php';
//include_once '../tools/Globals.php';  //DBG:


if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ) {
  $dieMessage        = $trans['dieMessage']; 
  $selectCabLabel    = $trans['Choose Cabinet'];
  $cabLabel          = $trans['Cabinet'];   
  $usernamed         = $trans['Username'];
  $r_w               = $trans['read_write']; 
  $r_o               = $trans['read_only'];
  $none              = $trans['no_permissions'];
  $update            = $trans['Update'];

	// docutron db object pointer
	$db_doc  = getDbObject('docutron');
	if(!isValidLicense($db_doc)) {
		die("INVALID LICENSE - no access to docutron db");
	}
	
	// department db object pointer
	$db_dept = $user->getDbObject();
	if (isset ($_GET['default'])) {
  		$default = $_GET['default'];
	} else {
		$default = '';
	}
	if (isset ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}
	if (isset ($_GET['DepID'])) {
		$DepID = $_GET['DepID'];
	} else {
		$DepID = '';
	}
	
	// header
	echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
ENERGIE;

	// cabinet pulldown
	$user->addCabinetJscript("getDepartment");
  
	// 
	echo<<<ENERGIE
<script type="text/javascript">

	function mOver(t) {
		t.style.backgroundColor = '#888888';	
	}

	function mOut(t) {
		t.style.backgroundColor = '#ffffff';	
	}
	
	docType = "";
	function selectDocument(docType) {
		var selBox = getEl('docList');
		removeDefault(selBox);
		clearDiv(getEl('errMsg'));
		getEl('docDiv').style.display = "block";

		docType = selBox.options[selBox.selectedIndex].value;		

		var xmlDoc = createDOMDoc();
		var root = xmlDoc.createElement('ROOT');
		xmlDoc.appendChild(root);
		createKeyAndValue(xmlDoc,root,'function','xmlGetDocumentIndexes');
		createKeyAndValue(xmlDoc,root,'docType',docType);
		
		postXML(domToString(xmlDoc));
	}
	
	function fillDocumentPermissions(XML) {
		togglePermissionCheckBoxes();
		var permList = XML.getElementsByTagName('PERMISSION');
		if(permList.length > 0) {
			for(var i=0;i<permList.length;i++) {
				var id = permList[i].firstChild.nodeValue;		
				getEl('check-'+id).checked = true;
			}
		}
	}
	
  </script> 
  <title>Configure Compliance</title>
</head>
<body class="centered">
ENERGIE;

	if($user->noCabinets()){
		die("<div class=\"error\">$dieMessage</div></body></html>");
	}
	
//	if($DepID != NULL) {
		echo <<<ENERGIE
<div class="mainDiv" style="width:700px">
<div class="mainTitle">
<span>Configure Required Document Types</span>
</div>
<form name="getDepartment" action="{$_SERVER['PHP_SELF']}">
<table class="inputTable">
<tr>
<td class="label">
<label for="cabSel">$cabLabel</label>
</td>
<td>
ENERGIE;
	
	// select cabinet pulldown.
	$user->getDropDown( "complianceConfig.php?default=$default", $user, 1 );
	echo "\n    </td>\n   </tr>\n   </table></form>\n";

	// select a cabinet in this department from pulldown
	$cab = getTableInfo( $db_dept, 'departments', array('departmentname'),
						 array('departmentid' => (int)$DepID), 'queryOne' );

	// complete list of document types within this department
  	$docTypeList = getTableInfo( $db_dept, 'document_type_defs',
  								 array('document_type_name'), array(), 'queryAll', 
  								 array('document_type_name' => 'ASC') );
	
  	// filtered document types list for this cabinet
  	$filteredDocId = getTableInfo( $db_dept, 'document_settings', array('list_id'),
  								   array('cab' => $cab), 'queryOne');
  	
  	// grab the document_id's for the filtered list of document types 
  	if($filteredDocId) {
  		$filteredDocListArr = array();
  		$filteredDocListArr = getTableInfo( $db_dept, 'document_settings_list', array('document_id'),
  											array('list_id' => $filteredDocId), 'queryAll');
  	}

//  	// string list of document types for a selected portfolio
//	$portfolioDocTypes = getTableInfo( $db_dept, 'compliance', 
//									   array('document_types'), 
//									   array('cabinet' => $cab), 'queryOne' );
//									   
	echo "\n<form name=\"docTypes\" method=\"post\" 
		  		  action=\"complianceUpdate.php?cab=$cab&amp;default=$default\">\n";
	
	// document type list - headers
	echo "<div class=\"inputForm\">\n";
	echo "<table>\n";
	echo "	<tr>\n";
	echo "    <th> </th>\n";
	echo "    <th>Document Type Names</th>\n";
	echo "    <th>Document Indexes</th>\n";
	echo "    <th>Document Index Value</th>\n";
	echo "	</tr>";

	if($DepID != NULL) {
	
		// get the list of document types for this department/cabinet
		$enArr = array('cab'=>$cab);
		$docTypeArr = getDocumentTypes($enArr, $user, $db_object, $db_dept);
		
		// get the existing list of document types in this portfolio
		$portDTlist = getTableInfo($db_dept, 'compliance', array('document_types'),
								   array('cabinet'=>$cab), 'queryOne');
	
		// index for list of document types
		$docIdx = 0;
		
		// if existing compliance, mark the checked (document types)
		foreach ($docTypeArr as $docType) {
			$checked = 0;  // init
			// get the document table name for this document type name for use as HTML item name
			$documentNum = getTableInfo($db_dept, 'document_type_defs', 
										array('document_table_name'),
										array('document_type_name'=>$docType), 
										'queryOne');
			$docNumA = $documentNum."_A";
			$docNumB = $documentNum."_B";
			$docNumC = $documentNum."_C";
			
			// break apart document type and index/value list
			if ($portDTlist) {
				$portTypeList = explode(";;", $portDTlist);
				foreach ($portTypeList as $portTypeValue) {
					if($portTypeValue) {
						$portType = explode("^^", $portTypeValue);
						if (!strcmp($docType, $portType[0])) {
							$checked = 1;
							break;  // found it - same time
						}
					}
				}
			}
			echo "	<tr>\n";
			if ($checked) {
				echo "		<td><input type='checkbox' name='$docNumA' value='$docType' checked='checked'/></td>\n";
			} else {
				echo "		<td><input type='checkbox' name='$docNumA' value='$docType'/></td>\n";
			}
			echo "		<td>$docType</td>\n";
			
			// index field pull down
			if ($checked && $portType[1]) {

				echo "	<td>\n";
				echo "		<div id=\"docDiv\" class=\"docDiv\">\n";
				echo "		<select id=\"docList\"\n";
				echo "				class=\"docList\"\n";
				echo "				name=\"$docNumB\"\n";
				echo "				onchange=\"selectDocument(name)\">\n";
				echo "			<option value=\"$portType[1]\">$portType[1]</option>\n";
			} else {
				echo "	<td>\n";
				echo "		<div id=\"docDiv\" class=\"docDiv\">\n";
				echo "		<select id=\"docList\"\n";
				echo "				class=\"docList\"\n";
				echo "				name=\"$docNumB\"\n";
				echo "				onchange=\"selectDocument(name)\">\n";
				echo "			<option value=\"\">Choose index</option>\n";
			}
			
			// get the index field names for the pull down
			$docTableName = getTableInfo($db_dept, 'document_type_defs', 
										 array('document_table_name'),
										 array('document_type_name' => $docType), 
										 'queryOne');
			$idxList = array();
			$idxList = getTableInfo($db_dept, 'document_field_defs_list', 
									array('arb_field_name'),
									array('document_table_name' => $docTableName), 
									'queryAll');
			
			foreach($idxList AS $key => $value) {
				foreach($value as $k1 => $v1) {
				echo "			<option value='$v1'>$v1</option>\n";
				}
			}
			
			echo <<<ENERGIE
							</select>
						</div>
						</td>
ENERGIE;

			// text field for document type index value
			if ($checked && $portType[2]) {
				echo "	<td>\n";
				echo "		<input id=\"idxValue\" type=\"text\" name=\"$docNumC\" value=\"$portType[2]\">\n";
				echo "	</td>\n";
			} else {
				echo "	<td>\n";
				echo "		<input id=\"idxValue\" type=\"text\" name=\"$docNumC\" value=\"\">\n";
				echo "	</td>\n";
			}
			echo "	</tr>\n";
	
			$docIdx++;
		}	// end foreach(compliance)
	}
	echo "</table>\n</div>\n";
	echo "<br>";
	
	// update button
	echo "	<input type='submit' name='submitCompliance' value='Update' style='right'/>\n";
	echo "</form>\n</div>\n";

	// close page
	echo "</div>\n";
    echo "</body>\n</html>";

  setSessionUser($user);

	if( isset($_GET['message'] )) {
		echo "<div id='errorDiv' class=\"error\">{$_GET['message']}</div>\n";
	} else {
		echo "<div id='errorDiv' class=\"error\">&nbsp;</div>\n";
	}
} else {
	logUserOut();
}

?>