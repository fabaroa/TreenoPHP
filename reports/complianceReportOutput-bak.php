<?php
// $Id: complianceReportOutput.php 14159 2010-12-07 15:59:06Z acavedon $
/*
 * complianceReportOutput.php - generate and post output for either the 
 * display or excel file.
 * 
 * Author:  Al Cavedon
 * Created: 11/17/2010
 */

include_once '../check_login.php'; 
include_once '../settings/settings.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/searchLib.php';
//include_once '../tools/Globals.php';  // DBG:


if (($logged_in == 1 && strcmp($user->username,"") != 0 && 
    ($user->isDepAdmin() || $seeReport))) {
    	
    // pick up the passed cabinet name
	if( isset($_GET['cab'])) {
		$cabinet = $_GET['cab'];
	} else {
		// ERROR, no cabinet name passed in
		echo "ERROR: field values not communicated to complianceReportOutput script!\n";
		die();
	}

	// pick up the passed report button value
	if( isset($_GET['export'])) {
		$EXPORT = $_GET['export'];
	} else {
		// ERROR...
		echo "ERROR: report button value not passed across...\n";
		die();
	}
	
	// Are we reporting the completed folders or those missing required document types
	if( isset($_GET['missComp'] ) ) {
		$MISSING = $_GET['missComp'];
	} else {
		// should have been set...
		die("ERROR: missing value not passed in...\n");
	}
	
    // department DB ptr
	$db_dept = $user->getDbObject();
	// real name of cabinet, vs the passed in GUI name
	$db_cab  = getTableInfo($db_object, 'departments', 
							array('real_name'), 
							array("departmentname = '".$cabinet."'"),
							'queryOne');
	
	//  document types list
	$docStr = getTableInfo($db_object, 'compliance', 
						   array('document_types'), 
						   array("cabinet = '".$cabinet."'"), 
						   'queryOne');
	$docArr1 = array(); //init
	$docArr1 = explode(";;", $docStr);
	$dtnArr = array(); //init
	// support for idx values
	foreach($docArr1 AS $thisDoc) {
		$thisDocArr = explode("^^", $thisDoc);
		$dtnStr = getTableInfo($db_dept, "document_type_defs", 
							   array('document_table_name'),
							   array("document_type_name = '".$thisDocArr[0]."'"), 
							   'queryOne');
		/* 
		 * Group of arrays based on doc_table_name 
		 */
		// Array of doc_table_name and doc_type_name Associations
		$dtnaArr["$dtnStr"] = $thisDocArr[0];
		// Array of doc. table names found=1 (or not = 0) Values
		$dtnvArr["$dtnStr"] = 0;
		// Array of doc. table names Index names
		$dtniArr["$dtnStr"] = $thisDocArr[1];
		// Array of doc. table names index Search values
		$dtnsArr["$dtnStr"] = $thisDocArr[2];
	}
	
	/*
	 * header and setup...
	 */
    if($EXPORT) {
		/*
		 * Export to excel file...
		 */
		// create and open output file
		$file_path = $DEFS['DATA_DIR']."/".
					 $user->db_name."/personalInbox/".
					 $user->username."/";
		$file=$username."_report".date("Y_m_d_H_i_s").".xls";
		$fp = fopen($file_path.$file,"w+");
	} else {
		// Display - HEADER 
		echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Portfolio Report</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../lib/sorttable.js"></script>
	<script type="text/javascript">
		function adjustWidth() {
			var clientw = document.documentElement.clientWidth;
			$('mainDiv').style.width = (clientw *.95)+'px';
		}

		function initFunc () {
			ts_makeSortable ($('myTable'));
		}
		Event.observe (window, 'load', initFunc);
	</script>
	<style type="text/css">
		td {
			text-align: left;
		}

		a {
			text-decoration: none;
			color: black;
		}
	</style>
</head>
ENERGIE;

		// BODY - 
		echo<<<ENERGIE
<body class="centered">
	<!-- page header -->
	<!-- <div id="mainDiv" class="mainDiv"> -->
	<div id="mainTable" class="mainTable">
		<div class="mainTitle">
			<span>Portfolio report</span>
		</div>
		<div class="inputForm" style="width:100%">
			<table id="myTable">
				<!-- headers -->
				<tr>
ENERGIE;
	}
	
	// grab all doc_id's to be processed
	$folderIds = getTableInfo($db_dept, $db_cab, 
							  array('doc_id'), array(), 'queryCol');
	// names of all of the cabinets fields
	$fieldNames = getCabinetInfo($db_dept, $db_cab);
	$output = array(); // init
	$i = 0; // init, array element number for output records
	foreach($folderIds AS $docId) {
		// document# names
		$pfoArr = getTableInfo($db_dept, $db_cab.'_files', 
							array('document_table_name'),
							array("doc_id = '".$docId."'", "filename IS NULL"),
							'queryAll');
		// tmp array, used to mark doc. table names found
		$tmpvArr = $dtnvArr;
		
		// look at each folder for the document type used
		foreach($pfoArr AS $pfo) {
			$docName = $pfo['document_table_name'];
			// look at each document type for the index value used
			//-id from document_field_defs_list, where doc._table_name & arb_field_name
			//-doc._field_value from document_field_value_list...
				// where document_id (doc_id) & doc._field_defs_list_id (id)
			if($dtniArr["$docName"] != NULL && $docName != NULL) {
				$docTypeName = $tmpaArr["$docName"];
				$docFDLId = getTableInfo($db_dept, 'document_field_defs_list',
										 array('id'), 
										 array('document_table_name'=>$docName, 
										 	   'arb_field_name'=>$dtniArr["$docName"]),
										 'queryOne');	
				$docFValue = getTableInfo($db_dept, 'document_field_value_list',
										  array('document_field_value'),
										  array('document_id'=>$docId, 
										  		'document_field_defs_list_id'=>$docFDLId),
										  'queryOne');
				// is search index value found within this value?
				if(strstr($docFValue, $dtnsArr["$docName"]) !== FALSE) {
					// set this doc. type as found
					$tmpvArr["$docName"] = 1;
				} else {
					// set this doc. type as found
					$tmpvArr["$docName"] = 0;
					break;
				}
			} else {
				// set this doc. type as found
				$tmpvArr["$docName"] = 1;
			}
		}	// end foreach(each folder)
		
		// did we find all of the document types?
		$missingDocTypes = array();  // init
		if($MISSING) {
			$save = 0;  // init
			foreach(array_keys ($tmpvArr) AS $index) {
				if($tmpvArr[$index] == 0) {
					$missingDocTypes[] .= $dtnaArr[$index];
					$save = 1;
				}
			}
		} else {
			$save = 1;  // init
			foreach(array_keys ($tmpvArr) AS $index) {
				if($tmpvArr[$index] == 0) {
					// don't need the docTypes
					$save = 0;
				}
			}
		}
		if($save) {
			// place info into array for output later
			$output[$i] = array(); // init multidimensional
			$docFields = getTableInfo($db_dept, $db_cab, 
							  		  array(), array("doc_id = '".$docId."'"), 
							  		  'queryAll');
			foreach($fieldNames AS $fieldName) {
				// field values
				$output[$i][$fieldName] = $docFields[0][$fieldName];
			}
			if($MISSING) {
				// list of missing document types
				$output[$i]['types'] = implode("; ", $missingDocTypes);
			}
			// next row
			$i++;
		}
	}


	if($EXPORT) {
		/*
		 * generate output and write to CSV file
		 */
	
		$cabFields = getCabinetInfo($db_dept, $db_cab);
		$outStr = ""; // init
		// headers
		foreach($cabFields AS $fieldName) {
			$outStr .= "$fieldName\t";
		}
		if($MISSING) {
			$outStr .= "missing document types\n";
		} else {
			$outStr .= "\n";
		}
		
		// data rows
		foreach($output AS $outputRow) {
			foreach($cabFields AS $fieldName) {
				$outStr .= $outputRow["$fieldName"]."\t";
			}
			if($MISSING) {
				$outStr .= $outputRow['types']."\n";
			} else {
				$outStr .= "\n";
			}
		}
		
		// Write output and finish
		fwrite($fp, $outStr);
		fclose($fp);
		downloadFile($file_path, $file, 1, 0, $file);
		
	} else {
	/*
	 * display output to screen...
	 */

		// display column headers
		$cabFields = getCabinetInfo($db_dept, $db_cab);
		foreach($cabFields AS $fieldName) {
			echo<<<ENERGIE
					<th>$fieldName\t</th>
ENERGIE;
		}
	
		// include the missing doc. type header if we are not doing 'complete'
		if($MISSING) {
			echo<<<ENERGIE
					<th>missing document types</th>
ENERGIE;
		}
		
		// end row
		echo<<<ENERGIE
				</tr>
ENERGIE;

		// start looping through the output row by row
		$outputRow = array();
		foreach($output AS $outputRow) {
			echo<<<ENERGIE
				<tr>
ENERGIE;
	
			// loop through fields in this row
			foreach($cabFields AS $fieldName) {
				$myField = $outputRow[$fieldName];
				echo<<<ENERGIE
					<td>$myField</td>
ENERGIE;
			}  // end foreach(fields for this row)

			// include the missing doc. type field if not doing 'complete'
			if($MISSING) {
				$myType = $outputRow['types'];
				echo<<<ENERGIE
					<td>$myType</td>
ENERGIE;
			}	// end if(missing field added)
		}	// end foreach(row)
	
		// close off html for page
		echo<<<ENERGIE
				</tr>
			</table>
		</div>
	</div>
</body>
</html>
ENERGIE;

	}  // end of if(export)/else(display)
	
	setSessionUser($user);
} else {
	logUserOut();
}

?>