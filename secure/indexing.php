<?php
// $Id: indexing.php 14657 2012-02-06 13:48:38Z acavedon $
require_once '../check_login.php';
require_once '../lib/cabinets.php';
require_once '../modules/modules.php';

if ($logged_in and $user->username) {
	$dieMessage = $trans['dieMessage'];
	$filestoIndex = $trans['Files to Index'];
	$filesLocked = $trans['Files Locked'];
	$cabLabel = $trans['Cabinet'];
	$truncate = $trans['Truncate'];
	$rusureUnlock = $trans['Are you sure Unlock'];
	$rusureTrun = $trans['Are you sure Truncate'];
	$barcoding = 1; 

	if (isset ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}
	
	if (isset ($_GET['name'])) {
		$cabinet = $_GET['name'];
	} else {
		$cabinet = '';
	}
	
	if (isset ($_GET['type']) and $_GET['type']) {
		$type = $_GET['type'];
	} else {
		$type = '';
	}
	
	$db_object = $user->getDbObject();
	$db_doc = getDbObject('docutron');
	if ($cabinet and isset($_GET['unlock'])) {
		lockTables($db_object,array($cabinet."_indexing_table"));
		$updateArr = array('flag'=>0);
		$whereArr = array('finished'=>'<> total','flag'=>'> 0', 'upforindexing'=>'= 0');
		updateTableInfo($db_object,$cabinet."_indexing_table",$updateArr,$whereArr,0,1);
		unlockTables($db_object);
	}
	if ($cabinet and !isset($_GET['unlock'])) {
		echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Starting Indexing</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
</head>
<frameset rows="*,160" cols="*">
<frame framespacing="0" frameborder="0" src="../secure/getImage.php?cab=$cabinet&type=$type" name="IndexMainFrame" />
<frame framespacing="0" frameborder="0" src="../energie/bottom_white.php" name="bottomFrame" noresize="noresize" />
</frameset>
</html>
HTML;
	} else {
		$whereArr = array("k " . LIKE . " 'indexing_%'","department='$user->db_name'");
		$modRes = getTableInfo($db_doc,'settings',array('k'),$whereArr);
		$modArr = array();
		while ($row = $modRes->fetchRow()) {
			$modArr[$row['k']] = "auto_complete_indexing";
		}
		if($user->isDepAdmin()) {
			$cabinetList = array_keys($user->cabArr);
		} else {
			$cabinetList = array_keys($user->access, 'rw');
		}
		echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Indexing</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/barcode.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript">
	var boolBC = false;
function selectAll(truncAll) {
	var indexID;
	if (truncAll.checked == true) {
		for (i = 0; indexID = document.getElementById('indexID-' + i); i++) {
			indexID.checked = true;
		}
	} else {
		for (i = 0; indexID = document.getElementById('indexID-' + i); i++) {
			indexID.checked = false;
		}
 	}
}
function mOver(row) {
	row.style.backgroundColor = '#888888';
}
function mOut(row) {
	row.style.backgroundColor = '#ebebeb';
}
function goToIndexing(type, cabinet) {
	var xmlArr = {	"include" : "secure/indexingFuncs.php",
					"function" : "xmlSetIndexingSession",
					"cabinet" : cabinet };
	//cz 10-14
	//alert("indexing.php - goToIndexing: type=" +type +", cabinet=" + cabinet);

	postXML(xmlArr);
//	top.mainFrame.window.location = 'indexing.php?name=' + cabinet + '&type=' + type;

}

function loadCabinet() {
	top.mainFrame.window.location = 'indexValues.html';
}

function unlockfiles(name) {
	answer = window.confirm("$rusureUnlock");
	if (answer) {
		if(window.name == "mainFrame") {
	        window.location = "indexing.php?name=" + name + '&unlock=1';
	    }
	    else {
			top.mainFrame.window.location = "indexing.php?name=" + name + '&unlock=1';
		}
	}
}

function truncate() {
	var j;
	answer = window.confirm("$rusureTrun");
	if(answer) {
	}
}
</script>
</head>
<body class="centered">
HTML;
		if ($user->noCabinets()) {
			 echo '<div class="error centered">'.$dieMessage.'</div>';
		} else {
			$security = $user->isAdmin();
			echo<<<HTML
<form id="form1" method="post" action="truncate.php">
<table style="width: 600px; margin-left: auto; margin-right: auto" class="lnk">
<tr style="background-color: #003b6f">
<th>&nbsp;</th>
HTML;
			if ($security) {
				echo<<<HTML
<th>
<span style="font-weight: bold">$truncate</span>
<input type="checkbox" id="truncAll" name="trunc" value="truncAll" onclick="selectAll(this)" />
</th>
HTML;
			}
			if ($barcoding) {
				echo '<th>Barcode</th>';
			}
			echo<<<HTML
<th>$cabLabel</th>
<th>$filestoIndex</th>
<th>$filesLocked</th>
HTML;
			echo '</tr>';
			$i = 0;
			foreach ($cabinetList as $myCab) {
				if (!empty ($user->cabArr[$myCab])) {
					if(isset($modArr['indexing_'.$myCab])) {
						$dir = $modArr['indexing_'.$myCab];
					} else {
						$dir = '';
					}
					$dispName = $user->cabArr[$myCab];

					$whereArr = array('finished<total','flag=0','upforindexing=0');
					$count = getTableInfo($db_object,$myCab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');

					$whereArr = array('finished<>total','flag>0','upforindexing=0');
					$locked = getTableInfo($db_object,$myCab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');

					echo<<<HTML
<tr style="background-color: #ebebeb" class="lnk_black" onmouseover="mOver(this)" onmouseout="mOut(this)">
HTML;
					if($user->checkSetting('documentView',$myCab)) {
					echo<<<HTML
<td style="width: 80px; cursor: pointer">
HTML;
					} else {
					echo<<<HTML
<td style="width: 80px; cursor: pointer" onclick="goToIndexing('$dir', '$myCab')">
HTML;
					}
					echo<<<HTML
<img src="../energie/images/cabinet.gif" alt="" />
</td>
HTML;
					if ($security) {
						echo '<td><input type="checkbox" id="indexID-'.$i.'" name="check[]" value="'.$myCab.'" /></td>';
					}
					if ($barcoding) {
						echo<<<HTML
<td style="cursor: pointer" onclick="boolBC=false;printDocutronBarcode('$myCab')">
<img alt="Get Barcode" src="../images/barcode.gif" />
</td>
HTML;
					}
					
					echo<<<HTML
<td style="width: 250px; cursor: pointer" onclick="goToIndexing('$dir', '$myCab')">$dispName</td>
<td style="cursor: pointer" onclick="goToIndexing('$dir', '$myCab')">$count</td>
HTML;
					echo '<td>'.$locked;
					if ($locked > 0)
						echo '<img style="cursor: pointer" onclick="unlockfiles(\''.$myCab.'\')" src="../energie/images/unlock.gif" alt="" />';
					echo '</td>';
					echo '</tr>';
				}
				$i++;
			}

			echo '</table>';
			if ($security) {
				echo<<<HTML
<table style="margin-left: auto; margin-right: auto; width: 600px; text-align: left">
<tr>
<td>
<input type="submit" name="submit" value="Refresh" onclick="truncate()" />
HTML;
				if ($mess)
					echo '<div class="error centered">'.$mess.'</div>';
				echo<<<HTML
</td>
</tr>
</table>
HTML;
			}
			echo<<<HTML
</form>
HTML;
		}
		setSessionUser($user);
	}
	echo '</body></html>';
} else {
	logUserOut();
}
?>
