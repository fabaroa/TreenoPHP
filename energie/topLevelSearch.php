<?php
require_once '../check_login.php';
require_once '../classuser.inc';
require_once '../lib/searchLib.php';
if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	if($user->cab and $user->doc_id) {
		//DON'T DO TOP LEVEL SEARCH INSIDE WORKFLOW!
		echo "<script type=\"text/javascript\">"."window.location = \"home.php?noResFound=1\";"."</script>";
		die();
	}
	if (isset ($_GET['topTerms'])) {
		$tlsArray = $_SESSION['tlsArray'];
		$topTerms = $tlsArray['topTerms'];
		$exact = $tlsArray['exact'];
	}
	elseif (isset ($_POST['search'])) {
		$topTerms = $_POST['search'];
		$exact = $_POST['exact'];
		if ($_POST['exact'] == '1') {			
			$exact = true;
		} else {
			$exact = false;
		}
		$_SESSION['tlsArray'] = array ('topTerms' => $topTerms, 'exact' => $exact);
	} else {
		$tlsArray = $_SESSION['tlsArray'];
		$exact = $tlsArray['exact'];
		$topTerms = $tlsArray['topTerms'];
	}

	//translated variables
	$selectCabMess = $trans['Select to View'];
	$cabname = $trans['Cabinet'];
	$resFound = "Results Found";
	$sortAscending = "Sort Ascending";
	$sortDescending = "Sort Descending";

	$myURL = getRequestURI ();
	$tempArray = explode('?', $myURL);
	if (count($tempArray) > 1) {
		$argArray = explode("&", $tempArray[1]);
		$count = 0;
		$newArray = array ();
		foreach ($argArray as $myArg) {
			if (!strpos($myArg, "sort")) {
				$newArray[] = $myArg;
			}
		}
		if ($newArray) {
			$myArgs = implode("&", $newArray);
		}
		$myURL = $tempArray[0].'?'.$myArgs.'&';
	} else {
		$myURL = $tempArray[0].'?';
	}

	$user->setSecurity();
	if (isset ($_GET['autosearch'])) {
		$topTerms = $_GET['autosearch'];
	}
	if ($topTerms) {
		$terms = splitOnQuote($db_object, $topTerms, true);
		//related data structures
		$i = 0; //used to keep size of arrays
		$tableDepartmentNames = array_merge(array_keys($user->access, 'rw'), array_keys($user->access, 'ro'));
		//search through each cabinet
		$tableSearchResults = array ();
		$resultCount = array ();

		if ($terms) {
			foreach ($tableDepartmentNames as $myCabName) {
				if (isset ($user->cabArr[$myCabName])) {
					//This function is located in lib/utility.php
					$tempName = searchTable($db_object, $myCabName, $exact, $terms);
					$count = getTableInfo($db_object, $tempName, array('COUNT(*)'), array(), 'queryOne');

					// Check for any problems and go home if there are
					if (PEAR :: isError($count)) {
						echo "<script type=\"text/javascript\">"."window.location = \"home.php?noResFound=1\";"."</script>";
						die();
					}

					if ($count > 0) {
						$tableSearchResults[$myCabName] = $tempName;
						$resultCount[$myCabName] = $count;
						$good = $myCabName;
					}
				}
			}

			if($exact) {
				$user->audit('top level search',"exact: ".implode(" AND ",str_replace("%","",$terms)));
			} else {
				$user->audit('top level search',"any: ".implode(" OR ",str_replace("%","",$terms)));
			}
			$cabsort = 'asc';
			$numsort = 'asc';

			if ($resultCount) {
				if (!empty ($_GET['cabsort']) and $_GET['cabsort'] == 'desc') {
					uksort($resultCount, "reverseCmp");
				}
				elseif (!empty ($_GET['numsort']) and $_GET['numsort'] == 'asc') {
					uasort($resultCount, "strnatcasecmp");
					$numsort = 'desc';
				}
				elseif (!empty ($_GET['numsort']) and $_GET['numsort'] == 'desc') {
					uasort($resultCount, "reverseCmp");
				} else {
					uksort($resultCount, "strnatcasecmp");
					$cabsort = 'desc';
				}
			}

			$numResults = sizeof($tableSearchResults);
			//Check to see if there is any results to display proper message
			// *** IF YOU WANT TO CREATE AN OPTION FOR AUTO GOTO RESULTS CHANGE 1 TO 0 ***
			if ($exact) {
				$exactStr = '1';
			} else {
				$exactStr = '0';
			}

			echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Top Level Search Results</title>
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script type="text/javascript" src="../lib/windowTitle.js"></script>
<script type="text/javascript" src="../energie/func.js"></script>
<script type="text/javascript" src="../search/searchResults.js"></script>
<script type="text/javascript">
cabNameStr = '$cabname';
cabSortStr = '$cabsort';
numSortStr = '$numsort';
resFoundStr = '$resFound';
exact = '$exactStr';
myURL = '$myURL';

setTitle(1, "Top Level Search Results");
parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
parent.sideFrame.window.location = '../energie/left_blue_search.php';
if(parent.topMenuFrame.removeBackButton) {
  parent.topMenuFrame.removeBackButton();
}

function loadSearchRes(tempTable, cabName) {
	var urlStr = 'searchResults.php?topTerms=1&table=' + tempTable + '&cab=';
	urlStr += cabName + '&exact=' + exact;
	parent.mainFrame.location = urlStr;
}

function registerEvents() {
	var cabSort = document.getElementById('cabsort');
	var numSort = document.getElementById('numsort');
	var tableHeadRow = document.getElementById('heading');
	var tableRow;
	cabSort.onmouseover = function() {
		showSort(cabNameStr, cabSortStr);
	};
	cabSort.onmouseout = removeSort;
	cabSort.onclick = function() {
		top.mainFrame.window.location = myURL + 'cabsort=cabSortStr';
	};
	numSort.onmouseover = function() {
		showSort(resFoundStr, numSortStr);
	};
	numSort.onmouseout = removeSort;
	numSort.onclick = function() {
		top.mainFrame.window.location = myURL + 'numsort=numSortStr';
	};
	tableRow = getNextSibByTag(tableHeadRow.nextSibling, 'TR');
	while(tableRow) {
		tableRow.onmouseover = function() {
			this.style.backgroundColor='#888888';
		}
		tableRow.onmouseout = function() {
			this.style.backgroundColor='#ebebeb';
		}
		tableRow = getNextSibByTag(tableRow.nextSibling, 'TR');
	}
}
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
</head>
ENERGIE;

			if ($numResults > 1 || (isset($_GET['case']) and $_GET['case'] == 2)) {
				// greater than one, put out table stuff
				echo<<<ENERGIE
<body class="centered" onload="registerEvents()">
	<p class="sortmsg" id="sortmsg">&nbsp;</p>
	<table class="lnk cabList">
		<tr id="heading">
			<th></th>
			<th id="cabsort" class="pointed">$cabname</th>
			<th id="numsort" class="pointed">$resFound</th>
		</tr>

ENERGIE;
			}
			elseif ($numResults == 1) { // skip this page and move on to searchResults
				// make searchResults?search=$search
				echo<<<RESULTS
<script type="text/javascript">
  parent.mainFrame.location = "searchResults.php?topTerms=1&table={$tableSearchResults[$good]}&cab=$good&exact=$exactStr";
</script>
RESULTS;
				die();
			} else { // No results, display message
				echo<<<RESULTS
</head>
<script>
	document.onLoad = location = 'home.php?noResFound=1' ;
</script>
RESULTS;
				//<div class="error">$noResFound</div>
				die();
			}
			foreach ($resultCount as $depName => $numFolders) {
				if (isset ($user->cabArr[$depName]) and $user->cabArr[$depName]) {
					$dispName = $user->cabArr[$depName];
					echo<<<ENERGIE
<tr
	class="lnk_black pointed"
	onclick="loadSearchRes('{$tableSearchResults[$depName]}', '$depName')"
>
	<td style="width: 80px">
		<div class="imgDiv">
			<img src="images/cabinet.gif" alt="" />
		</div>
	</td>
	<td style="text-align: left">$dispName</td>
	<td style="width: 25%; text-align: center">$numFolders Folders</td>
</tr>

ENERGIE;
				}
			}
			echo "</table>\n";
			echo "</body>\n</html>\n";
		} else {
			echo "<script language=\"javascript\">"." window.location = 'home.php?noResFound=1';"."</script>";
		}
	} else { // There was nothing searched for
		echo "<script language=\"javascript\">"." window.location = 'home.php?noResFound=1';"."</script>";

	}

	setSessionUser($user);

} //ends logged in check
else {
	//we want to log them out
	echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<body>
<script type="text/javascript">
  document.onload = top.window.location = "../logout.php";
</script>
</body>
</html>
ENERGIE;
}

function reverseCmp($str1, $str2) {
	return - (strnatcasecmp($str1, $str2));
}

			
?>
