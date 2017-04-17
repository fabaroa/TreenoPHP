<?php
include_once '../lib/random.php';
include_once '../lib/odbc.php';
include_once '../lib/utility.php';
include_once '../search/search.php';
include_once '../settings/settings.php';
include_once '../lib/cabinets.php';

function getSearchStr( $searchStr ) {
	$tok= preg_split("/[\s]+/", trim($searchStr));
    for($i=0;$i<sizeof($tok);$i++) {
	  if( substr_count($tok[$i], "\"") == 2 )
	    $exp .= " \"([^\"]+)\"";
      elseif( substr_count($tok[$i],"\"") == 0 )
          $exp .= " (\S+)";
	  else {
		$i++;
		while( substr_count($tok[$i],"\"") == 0 && $i < sizeof($tok) ) {
		  $i++;
		}
        if( substr_count($tok[$i],"\"") == 0)
	      $exp .= " \"([^\"]+)";
		else
	      $exp .= " \"([^\"]+)\"";
	  }
	}
return trim($exp); 
}

function splitOnQuote($db, $searchStr, $multiple) {
	$stillQuotes = true;

	$searchStr = trim($searchStr);
	$numQuotes = substr_count( $searchStr, "\"" );
	if( $numQuotes > 2 ) {//tests and removes more than two quotes in search string
		$searchArray = explode("\"", $searchStr);
		$searchStr = "\"".implode("",$searchArray)."\"";
	}

	$searchTerms = array();
	while($stillQuotes) {
		$searchStr = trim($searchStr);
		$firstQuote = strpos($searchStr, '"');
		if($firstQuote !== false) {
			$secondQuote = strpos($searchStr, '"', $firstQuote + 1);

			if($secondQuote === false) {
				$secondQuote = strlen($searchStr);
				$stillQuotes = false;
				$jumpSecond = 0;
			} else {
				$jumpSecond = 1;
			}
			$quotedLength = $secondQuote - $firstQuote - 1;
			if($quotedLength != 0) {
				$quotedText = substr($searchStr, $firstQuote + 1, 
					$quotedLength);
				$searchTerms[] = "'$quotedText'";
			}

			$searchStr = substr_replace($searchStr, '', $firstQuote, 
					$quotedLength + 1 + $jumpSecond);
		} else {
			$stillQuotes = false;
		}
	}

	if($searchStr !== "") {
		$termArray = explode(" ", $searchStr);
		foreach($termArray as $searchTerm) {
			if($searchTerm !== "") {
				$myTerm = $db->escape($searchTerm, true);
				$searchTerms[] = "'%$myTerm%'";
			}
		}
	}
	if($multiple)
		return $searchTerms;
	if(isset($searchTerms[0])) {
		return $searchTerms[0];
	}
	return '';
}

/*
 * searchTable searches a table, and returns an array of the folders id
 */
function searchTable($db_object, $cab, $exact, $terms) {
	$names = getCabinetInfo($db_object, $cab);
	$temp_table = '';
	$date = "DATE_ADD(NOW(), INTERVAL 3600 SECOND)";
	if ($exact) {
		for ($i = 0; $i < sizeof($terms); $i ++) {
			$queryArr = array();
			$prev = $temp_table;
			if ($i == 0) {
				$tableArr = array("$cab");
			} else {
				$tableArr = array("$prev","$cab");
				$queryArr[] = "$cab.doc_id=$prev.result_id";
			}
			$queryArr[] = "deleted=0";
			
			$fieldArr = array();
			for($j=0;$j<count($names);$j++) {
				$fieldArr[] = $names[$j]." " . LIKE . " $terms[$i]";
			}
			$queryArr[] = "(".implode(" OR ",$fieldArr).")";
			
			$orderArr = array('doc_id' => 'DESC');
			$temp_table = createTemporaryTable($db_object);
			insertFromSelect($db_object,$temp_table,array('result_id'),$tableArr,array('doc_id'),$queryArr,$orderArr);
		}
	} else {
		for ($i = 0; $i < sizeof($terms); $i ++) {
			$queryArr = array();
			$prev = $temp_table;
			if ($i == 0) {
				$tableArr = array($cab);
			} else {
				$tableArr = array($cab);
				if(getTableInfo($db_object,$prev,array('COUNT(*)'),array(),'queryOne')) {
					$tableArr[] = $prev;
//					$queryArr[] = "$cab.doc_id=$prev.result_id";
				}
			}
			
			$fieldArr = array();
			for($j=0;$j<count($names);$j++) {
				$fieldArr[] = $names[$j]." " . LIKE . " $terms[$i]";
			}
			$queryArr[] = "(".implode(" OR ",$fieldArr).")";
			$queryArr2 = array("deleted=0 AND (".implode(" AND ",$queryArr).")" );
			$orderArr = array('doc_id' => 'DESC');
			$temp_table = createTemporaryTable($db_object);
			insertFromSelect($db_object,$temp_table,array('result_id'),$tableArr,array('DISTINCT(doc_id)'),$queryArr2,$orderArr);
		}
	}
	return $temp_table;
}

function searchACForInbox ($db_dept, $db_doc, $cabinet, $value, $acTable, $user) {	
	$gblStt = new GblStt ($user->db_name, $db_doc);
	$indices = getCabinetInfo ($db_dept, $cabinet);
	$search = new search ();
	if ($acTable == 'odbc_auto_complete') {
		$uniqueField = getTableInfo ($db_dept, 'odbc_auto_complete', array('lookup_field'), 
					array ('cabinet_name' => $cabinet, 'location' => 'inbox'),'queryOne');
		$location = 'inbox';
		if(!$uniqueField) {
			$uniqueField = getTableInfo ($db_dept, 'odbc_auto_complete', array('lookup_field'), 
					array ('cabinet_name' => $cabinet),'queryOne');
			$location = '';
		}
	} else {
		$uniqueField = $indices[0];
		$location = '';
	}
	$tempTable = $search->getSearch ($cabinet, array ($uniqueField => "\"".$value."\""),
			$db_dept);
	
	$count = getTableInfo ($db_dept, $tempTable, array ('COUNT(result_id)'),
			array (), 'queryOne');

	if(!$count) {
		$row = searchAutoComplete ($db_dept, $acTable, $uniqueField, $value,
				$cabinet, $db_doc, $location, $user->db_name, $gblStt);
		if ($row) {
			$newFolderVals = array ();
			foreach ($indices as $index) {
				$newFolderVals[] = $row[$index];
			}
			$temp_table = '';
			$docID = createFolderInCabinet($db_dept, $gblStt, $db_doc, $user->username,
					$user->db_name, $cabinet, $newFolderVals, $indices,$temp_table);
			$queryArr = array ('result_id' => (int) $docID);
			$res = $db_dept->extended->autoExecute ($tempTable, $queryArr);
			dbErr($res);
		}
	}
	return $tempTable;
}

function isISODate ($myDate) {
	if (strlen ($myDate) == 10) {
		if (substr_count ($myDate, '-') == 2) {
			if (is_numeric (substr ($myDate, 0, 4)) and
				is_numeric (substr ($myDate, 5, 2)) and
				is_numeric (substr ($myDate, 8, 2))) {

				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

?>
