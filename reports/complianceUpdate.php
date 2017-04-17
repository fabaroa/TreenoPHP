<?php
// $Id: complianceUpdate.php 14159 2010-12-07 15:59:06Z acavedon $
/*
 * complianceUpdate.php - processes the form data from the "Reports ->
 * Compliance -> Configure" setup page.
 *
 * Author:  Al Cavedon
 * Created: 11/17/2010
 */

include_once '../check_login.php';
include_once '../classuser.inc';
//include_once '../tools/Globals.php';  //DBG:

if(!isset($_POST['submitCompliance']))
{
	//This page should not be accessed directly. Need to submit the form.
	echo "error; you need to submit the form!";
}

if( $logged_in==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
	$complianceSuccessI = "compliance successfully created";
	$complianceSuccessU = "compliance successfully updated";
	$complianceSuccessD = "compliance successfully removed";

	$db_object   = $user->getDbObject();
	$cab         = $_GET['cab'];
	$mess        = $_GET['default'];

	// get all of the possible document types
	$docTableNames = getTableInfo($db_object, 'document_type_defs', 
								  array('document_table_name'), 
								  array(), 'queryCol');
	//init
	$i = 0;
	$docTypeArr = array();
	// what document types were posted? create an array of them
	foreach($docTableNames AS $docTableName) {
		$A = $docTableName."_A";
		if(isset($_POST["$A"])) {
			$docTypeArr[$i][0] = $_POST["$A"];
		}
		
		$B = $docTableName."_B";
		if(isset($_POST["$B"]) && $docTypeArr[$i][0] != NULL) {
			$docTypeArr[$i][1] = $_POST["$B"];
		}
		
		$C = $docTableName."_C";
		if(isset($_POST["$C"]) && $docTypeArr[$i][0] != NULL) {
			$docTypeArr[$i][2] = $_POST["$C"];
		}
		
		$i++;
	}
	
	// get current document types in portfolio for this cabinet
	$currDocList = getTableInfo($db_object, 'compliance',
								array('document_types'), 
								array('cabinet'=>$cab),
  								'queryOne');
	
	// DB separators: use ';;' for each doc type, use '^^' for inside doc type
	if ($docTypeArr != NULL) {
		$docTypeList = ""; $first = 1;  // init
		foreach($docTypeArr AS $docTypeArr2) {
			// separate doc types
			if($first) {
				$first = 0;
			} else {
				$docTypeList .= ";;";
			}
			// doc type
			$docTypeList .= $docTypeArr2[0];

			// doc type index and value exist
			$docTypeList .= "^^".$docTypeArr2[1]."^^".$docTypeArr2[2];

		}	// end foreach(selected doc types)
		if ($currDocList != NULL) {
			// update the existing portfolio cabinet record
			$query = "UPDATE compliance
					  SET document_types='$docTypeList' 
					  WHERE cabinet='$cab'";
			$result = $db_object->query($query);
			$complianceSuccess = $complianceSuccessU;
		} else {
			// doesn't exist - create
			$query = "INSERT INTO compliance (cabinet, document_types)
	  				  VALUES('$cab', '$docTypeList')";
			$db_object->query($query);
			$complianceSuccess = complianceSuccessI;
		}
	} elseif ($currDocList != NULL) {
		// remove entry that exists
		$query = "DELETE from compliance
				  WHERE cabinet='$cab'";
		$result = $db_object->query($query);
		$complianceSuccess = $complianceSuccessD;
	}

	if($mess) {
		echo<<<ENERGIE
<script>  
    document.onload = parent.mainFrame.window.location = "complianceReport.php?default=$msg&message=$complianceSuccess";
</script>
ENERGIE;
	} else {
		echo<<<ENERGIE
<script> 
    parent.mainFrame.window.location = "complianceReport.php?msg=$complianceSuccessU";
</script>
ENERGIE;
	}
	setSessionUser($user);
} else {
	logUserOut();
}

?>