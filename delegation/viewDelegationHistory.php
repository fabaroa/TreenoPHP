<?php 
include_once '../check_login.php';
include_once '../lib/filter.php';

if($logged_in and $user->username) {
	$tableTitle = "Inbox Delegation History";
	$columnInfo = getTableColumnInfo ($db_object, 'inbox_delegation_history');

	$arbHeaders = array(
		    'id'				=>'ID',
			'delegate_id'		=>'Delegate ID',
		    'delegate_username'	=>'Delegate To',
			'delegate_owner'	=>'Delegate By',
			'folder'           	=>'Folder',
			'filename'				=>'File',
			'date_delegated'  	=>'Date Delegated',
			'date_completed'  	=>'Date Completed',
			'status'			=>'Status',
			'comments'			=>'Comments',
			'action'			=>'Action' );
	if((isSet($_POST) && $_POST) || (isSet($_GET) && $_GET)) {
		$search = array();
		if($_POST) {
			$start = 0;
			$currentPage = 1;
			$auditInfo = array();
			foreach($columnInfo as $column) {
				if($_POST[$column]) {
					$value = $_POST[$column];
					$auditInfo[] = $column." = ".$value;
					$search[] = $column." " . LIKE . " '%".$value."%'";
				}
			}			
            $user->audit('Viewed Inbox Delegated History','Search Criteria: '.implode(",",$auditInfo));            
			$_SESSION['delegationHistory'] = $search;
		} else {
			$currentPage = $_GET['page'];
			$start = (($currentPage-1) * 50);
			$search = $_SESSION['delegationHistory']; 
		}
		$delegationHistory = getDelegationHistory($db_object,$start,50,$search);
		$count = getTableInfo($db_object,'inbox_delegation_history',array('COUNT(*)'),$search,'queryOne');
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
		window.location = 'viewDelegationHistory.php?page='+page;
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
</head>
<body class="centered">
<div class="mainDiv" style='width:95%'>
<div class="mainTitle"><span>$tableTitle</span></div>
 <div>
  <table class='inputTable'>
   <tr>
    <td><img style='cursor:pointer' src='../energie/images/begin_button.gif' onclick='selectPage(1)'></td>
    <td><img style='cursor:pointer' src='../energie/images/back_button.gif' onclick='selectPage(2)'></td>
    <td><input type='text' id='pageNum' name='pageNum' onkeypress='return allowDigi(event)' value='$currentPage' size='4'> of $totalPages</td>
    <td><img style='cursor:pointer' src='../energie/images/next_button.gif' onclick='selectPage(3)'></td>
    <td><img style='cursor:pointer' src='../energie/images/end_button.gif' onclick='selectPage(4)'></td>
   </tr>
  </table>
 </div>
 <div class='inputForm'>
  <table style="white-space:normal">
ENERGIE;
	foreach($columnInfo as $column) {
		echo "<th>{$arbHeaders[$column]}</th>\n";
	}
	foreach($delegationHistory AS $history) {
		echo "<tr>\n";
		foreach($history AS $info) {
			echo "<td>".str_replace(".000000","",h($info))."</td>\n";
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
  <form name='delegationHistory' method='POST' action='viewDelegationHistory.php'>
  <table class='inputTable'>
ENERGIE;
	foreach($columnInfo as $column) {
		echo "<tr>\n";
		echo "<td>{$arbHeaders[$column]}</td>\n";
		echo "<td><input type='text' name='{$column}'></td>\n";
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
