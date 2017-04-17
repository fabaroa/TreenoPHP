<?php
// $Id: home.php 14188 2011-01-04 15:13:41Z acavedon $

//define("CHECK_VALID", "yes");
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
include_once '../lib/licenseFuncs.php';

if (($logged_in == 1 && strcmp($user->username, "") != 0)) {
	if( isSet($_GET['restore']) || $user->restore) {
		$user->access = array();
		$user->cabArr = array();
		$user->fillUser();
        $user->restore = 0;
		$user->doc_id = 0;
?>
<script>
       if(parent.topMenuFrame.document.getElementById('userObjTD')) {
               parent.topMenuFrame.restoreDefault(0);
       }
</script>
<?php
		setSessionUser($user);
	}

	//variables that may need to be translated
	$cabname = $trans['Cabinet'];
	$numFolders = $trans['Number of Folders'];
	$noResFound = $trans['No Results Found'];

	$folders = "Folders";
	$delete = "Delete";
	$sortAscending = "Sort Ascending";
	$sortDescending = "Sort Descending";
	$noResFound .= "<br/>Displaying All Cabinets";

	$settings = new GblStt($user->db_name, $db_doc);
	//$delCabinets = $settings->get('deleteCabinets');
	$delCabinets = '1'; // TreenoV4 expects is always ON for admins

	//$setCabinets = '';
	$setCabinets = '1'; // TreenoV4 expects is always ON for admins

	if($delCabinets === '1')
		$setCabinets = 1;
	
	//get user settings for whether or not to display delete cabinets
	$userSettings = new Usrsettings($user->username, $user->db_name);
	//$uDelCabinets = $userSettings->get('deleteCabinets');
	$uDelCabinets = '1'; // TreenoV4 expects is always ON for admins
	
	if($uDelCabinets === '1')
		$setCabinets = 1;
	elseif($uDelCabinets === '0')
		$setCabinets = 0;

	if($setCabinets === '') {
		$setCabinets = 0;
	}
	echo<<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Delete Cabinet</title>
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script type="text/javascript" src="../lib/windowTitle.js"></script>
<script type="text/javascript" src="../search/searchResults.js"></script>
<script type="text/javascript">
//parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
//parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
//parent.viewFileActions.window.location = '../energie/bottom_white.php';
//parent.sideFrame.window.location = '../energie/left_blue_search.php';
//if(top.topMenuFrame && top.topMenuFrame.removeBackButton) top.topMenuFrame.removeBackButton();

function submitAll(cabname)
{
  document.location.href = "searchResults.php?cab="+cabname;
}
function deleteCabinet( cabinet)
{
		message = "This Will Remove All Folders and Files in Cabinet";
    	answer = window.confirm(message);
        if(answer == true) {
			parent.mainFrame.window.location = 'search_frame2.php?delete=1&cabinet='+cabinet;
			if(parent.searchPanel.cabinetDeleted) {
				parent.searchPanel.cabinetDeleted(cabinet);
			}
       	}
}
ENERGIE;
	echo "  setTitle(1, \"Home\");\n";
	echo<<<ENERGIE
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
</head>
<body class="centered">
ENERGIE;
	if (isset ($_GET['noResFound']) and ($_GET['noResFound']) != "") {
		echo "<div class=\"error\">$noResFound</div>";
	}
	if ($user->noCabinets()) {
		$noCabs = 1;
	} else {
		$noCabs = 0;
	}
	echo <<<HTML
<script>
	var noCabs = ('$noCabs' == '1') ? true : false;
	if(!noCabs) {
		if(parent.searchPanel.location.href.search(/searchPanel/) == -1) {
//			parent.document.getElementById('searchPanel').setAttribute('scrolling','auto');
//			parent.searchPanel.location.href = "searchPanel.php";		
		}
	} else {
//		parent.searchPanel.location.href = "blue_bar.php";		
	}
	if(parent.topMenuFrame && parent.topMenuFrame.removeBackButton) {
//		parent.topMenuFrame.removeBackButton();
	}
</script>
HTML;
	if ($noCabs) {
		$dieMessage = $trans['dieMessage'];
		$myMsg = "<div style=\"text-align: center; color: red\">";
		$myMsg .= "$dieMessage</div>\n</body>\n</html>";
		setSessionUser($user);
		die($myMsg);
	}

	foreach ($user->cabArr as $myCab => $arbCab) {
		if ($user->access[$myCab] != "none")
			$cablist[] = $myCab;
	}

	for ($i = 0; $i < sizeof($cablist); $i ++) {
		$filterQ=getTableInfo($db_object,'cabinet_filters',array('index1','search'),array('cabinet'=>$cablist[$i],'username'=>$user->username),'queryAll');
		$wArr=array('deleted'=>0);
		foreach ($filterQ as $filter){
			$wArr[$filter['index1']]=$filter['search'];
		}
		$newCabList[$cablist[$i]] = getTableInfo($db_object,$cablist[$i],array('COUNT(doc_id)'),$wArr,'queryOne');
	}
	$cabsort = 'ASC';
	$numsort = 'ASC';

	if (isset ($_GET['cabsort']) and $_GET['cabsort'] == 'DESC') {
		$newCabList = array_reverse($newCabList, true);
	} elseif (isset ($_GET['numsort']) and $_GET['numsort'] == 'ASC') {
		$isSorted = uasort($newCabList, "strnatcasecmp");
		$numsort = 'DESC';
	} elseif (isset ($_GET['numsort']) and $_GET['numsort'] == 'DESC') {
		$isSorted = uasort($newCabList, "reverseCmp");
	} else {
		$cabsort = 'DESC';
	}
	echo "<p class=\"sortmsg\" id=\"sortmsg\">&nbsp;</p>\n";
	echo "<table class=\"lnk cabList\">\n"; //lnk_black
	echo "<tr id=\"heading\">\n";
	echo "<th style=\"width: 80px\"></th>\n";
	if ($user->isDepAdmin() && $setCabinets == 1 && isValidLicense($db_doc)) {
		echo "<th style=\"width: 50px\">$delete</th>\n";
	}
	echo "<th id=\"cabsort\" class=\"pointed\" ";
	echo "onclick=\"top.mainFrame.window.location=";
	echo "'$_SERVER[PHP_SELF]?cabsort=$cabsort'\" ";
	echo "onmouseover=\"showSort('$cabname', '$cabsort');\" ";
	echo "onmouseout=\"removeSort();\">$cabname</th>\n";
	echo "<th id=\"numsort\" class=\"pointed\" style=\"width: 15%\" ";
	echo "onmouseover=\"showSort('$numFolders', '$numsort');\" ";
	echo "onmouseout=\"removeSort();\" ";
	echo "onclick=\"top.mainFrame.window.location";
	echo "='$_SERVER[PHP_SELF]?numsort=$numsort'\">$folders</th>\n";
	echo "</tr>\n";

	foreach ($newCabList as $myCab => $myNum) {
		if ($user->cabArr[$myCab]) {
			$tmp = $user->cabArr[$myCab];
			echo "<tr class=\"lnk_black pointed\" ";
			echo "onmouseover=\"this.bgColor='#888888'\" ";
			echo "onmouseout=\"this.bgColor='#ebebeb'\">\n";
			echo "<td >\n";
			echo "<div class=\"imgDiv\">\n";
			echo "<img src=\"images/cabinet.gif\" alt=\"cabinet\" />";
			echo "</div>\n";
			if ($user->isDepAdmin() && $setCabinets == 1 && isValidLicense($db_doc)) {
				echo "<td onclick=\"deleteCabinet('$myCab');\">\n";
				echo "<div class=\"imgDiv\">\n";
				echo "<img src=\"images/trash.gif\" alt=\"cabinet\" />";
				echo "</div>\n";
				echo "</td>\n";
			}
			echo "</td>\n";
			echo "<td style=\"text-align: left\" >";
			echo $tmp;
			echo "</td>\n";
			echo "<td  ";
			echo "style=\"text-align: center\">";
			echo "$myNum";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "</body>\n</html>\n";

	setSessionUser($user);
} else {
	logUserOut();
}

function reverseCmp($str1, $str2) {
	return - (strnatcasecmp($str1, $str2));
}
?>
