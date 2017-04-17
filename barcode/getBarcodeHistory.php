<?php
// $Id: getBarcodeHistory.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../lib/random.php';

if($logged_in and $user->username) {
	$tableTitle = "Processed Barcodes";
	$columnInfo = getTableColumnInfo($db_object, 'barcode_history');

	$arbHeaders = array(
		    'id'				=>'ID',
			'barcode_rec_id'	=>'Reconciliation ID',
		    'barcode_info'  	=>'Barcode Info',
			'username'			=>'Username',
			'cab'           	=>'Cabinet',
			'barcode_field' 	=>'Folder',
			'date_printed'  	=>'Date Printed',
			'date_processed'  	=>'Date Processed',
			'description'		=>'Reconciled Type'
                       );
	if((isSet($_POST) && $_POST) || (isSet($_GET) && $_GET)) {
		$search = array();
		if($_POST) {
			$start = 0;
			$currentPage = 1;
			$auditInfo = array();
			foreach($columnInfo as $column) {
				if(isset($_POST[$column]) && trim($_POST[$column])) {
					$value = $_POST[$column];
					$auditInfo[] = $column." = ".$value;
					if($column == "id" || $column == "barcode_rec_id") {
						if(is_numeric($value)) {
							$search[] = $column."=".$value;
						}
					} elseif($column == 'date_printed' || $column == 'date_processed') {
						$valArr = explode("-",$value);

						$tStamp = mktime(0,0,0,$valArr[1],$valArr[2],$valArr[0]);
						$sDate = date("Y-m-d",$tStamp);
						
						$tStamp = mktime(0,0,0,$valArr[1],$valArr[2]+1,$valArr[0]);
						$eDate = date("Y-m-d",$tStamp);

						$search[] = $column. " >= '$sDate 00:00:00'";		
						$search[] = $column. " < '$eDate 00:00:00'";		
					} else {
						$search[] = $column." " . LIKE . " '%".$value."%'";
					}
				}
			}	
            $user->audit('Viewed Reconciled Barcodes','Search Criteria: '.implode(",",$auditInfo));            
			$_SESSION['barcodeHistory'] = $search;
		} else {
			$currentPage = $_GET['page'];
			$start = (($currentPage-1) * 50);
			$search = $_SESSION['barcodeHistory']; 
		}
		$barcodeHistory = getBarcodeHistory($db_object,$start,50,$search);
		$count = getTableInfo($db_object,'barcode_history',array('COUNT(*)'),$search,'queryOne');
		$totalPages = ceil($count/50);
echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script>
	page = $currentPage;
	last = $totalPages;
	function selectPage(type) {
		if(type == 1) {
			page = 1;
		} else if(type == 2) {
			page = page - 1;
		} else if(type == 3) {
			page = page + 1;
		} else if(type == 4) {
			page = last;
		} else {
			page = type;
		}

		if( page == 0 ) {
			page = 1;
		} else if(page > last) {
			page = last;
		}
		window.location = 'getBarcodeHistory.php?page='+page;
 	}

 	function allowDigi(evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		if (((charCode >= 48 && charCode <= 57) // is digit
			|| charCode == 8) || (charCode == 37) || (charCode == 39)) { // is enter or backspace key
			return true;
		} else if( charCode == 13) {
			selectPage(document.getElementById('pageNum').value);
			return true;
		} else { // non-digit
			return false;
		}
 	}
 
</script>
<title>$tableTitle</title>
<style type="text/css">
	#actionDiv {
		margin: 1em 0 1em 0;
		width : 90%;
		margin-left: auto;
		margin-right: auto;
	}

	#pubSearchDiv {
		margin: 1em 0 1em 0;
		width : 90%;
		margin-left: auto;
		margin-right: auto;
	}
</style>
</head>
<body class="centered">
<div class="mainDiv" style='width:95%'>
<div class="mainTitle"><span>$tableTitle</span></div>
 <div id="pubSearchDiv" style="text-align:center">
  <table class="pubSearchTable">
   <tr class="pubTablehead">
    <td><img style='cursor:pointer' src='../energie/images/begin_button.gif' onclick='selectPage(1)'></td>
    <td><img style='cursor:pointer' src='../energie/images/back_button.gif' onclick='selectPage(2)'></td>
    <td><input type='text' id='pageNum' name='pageNum' onkeypress='return allowDigi(event)' value='$currentPage' size='4'> of $totalPages</td>
    <td><img style='cursor:pointer' src='../energie/images/next_button.gif' onclick='selectPage(3)'></td>
    <td><img style='cursor:pointer' src='../energie/images/end_button.gif' onclick='selectPage(4)'></td>
   </tr>
  </table>
 </div>
 <div class='pubSearchDiv'>
  <table class="pubSearchTable">
ENERGIE;
	echo "<tr class=\"pubTableHead\">\n";
	foreach($arbHeaders AS $value) {
		echo "<th>{$value}</th>\n";
	}
	echo "</tr>\n";
	foreach($barcodeHistory AS $history) {
		echo "<tr>\n";
		foreach($history AS $k => $info) {
			if(array_key_exists($k,$arbHeaders)) {
				echo "<td>".h($info)."</td>\n";
			}
		}
		echo "</tr>\n";
	}
echo<<<ENERGIE
  </table>
  </form>
 </div>
</div>
</body>
</html>
ENERGIE;
	} else {
// Section for front search form (input)
echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<title>$tableTitle</title>
</head>
<body class="centered">
<div class="mainDiv" style='width:500px'>
<div class="mainTitle"><span>$tableTitle</span></div>
 <div>
  <form name='barcodeHistory' method='POST' action='getBarcodeHistory.php'>
  <table class='inputTable'>
ENERGIE;
	foreach($arbHeaders AS $k => $value) {
		echo "<tr>\n";
		echo "<td class=\"label\">{$value}</td>\n";
		echo "<td><input type='text' name='{$k}'></td>\n";
		echo "</tr>\n";
	}
	echo "<tr>\n";
	echo "<td colspan='2' align='center'><input type='submit' name='search' value='Search'></td>\n";
	echo "</tr>\n";
echo<<<ENERGIE
  </table>
  </form>
 </div>
</div>
</body>
</html>
ENERGIE;
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
