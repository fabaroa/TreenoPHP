<?php
require_once '../check_login.php';
require_once '../search/fileSearch.php';
require_once '../settings/settings.php';
require_once '../search/searchResultsExtras.php';
require_once '../search/searchResultsFuncs.php';
//This file displays the results of an advanced search. Each result displays
//the information about a file in the cabinet.
$userSettings = new Usrsettings($user->username, $user->db_name);
if ($logged_in and $user->username) {
	echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>File Search Results</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />

ENERGIE;
	$allBookmarks = unserialize(base64_decode($userSettings->get('bookmarks')));
	$sort['name'] = 'ASC';
	$sort['date'] = 'ASC';
	$sort['who'] = 'ASC';
	$sort['hits'] = 'DESC';

	//Starts session variables to keep track of sorts during frame reloads
	if (isset ($_GET['sorttype']) and isset ($_GET['sortdir'])) {
		$sorttype = $_GET['sorttype'];
		$sortdir = $_GET['sortdir']; //fieldname the user wants to sort
		$_SESSION['sorttype'] = $sorttype;
		$_SESSION['sortdir'] = $sortdir;
		$_SESSION['sortcab'] = $_GET['cab'];
	} elseif (isset ($_SESSION['sortcab']) and $_SESSION['sortcab'] == $_GET['cab']) {
		$sorttype = $_SESSION['sorttype'];
		$sortdir = $_SESSION['sortdir'];
	} else {
		$sorttype = 'name';
		$sortdir = 'ASC';
	}

	$myURL = $_SERVER['PHP_SELF'].'?';
	foreach ($_GET as $key => $value) {
		if (strcmp($key, 'sorttype') != 0 and strcmp($key, 'sortdir') != 0 and strcmp($key, 'temp_table') != 0 and $key != 'searchmess') {

			$myURL .= "$key=$value&";
		}
	}

	$myURL = h($myURL);

	if ($sortdir == 'DESC') {
		$sort[$sorttype] = 'ASC';
	} else {
		$sort[$sorttype] = 'DESC';
	}
	$searchmess = '';
	if (isset ($_GET['searchmess'])) {
		$searchmess = $_GET['searchmess'];
	}
	elseif (isset ($_GET['mess'])) {
		$searchmess = $_GET['mess'];
	}
	$tmpArray = array ();
	if (isset ($_POST['subfolder'])) {
		$tmpArray = $_POST;
		$_SESSION['fsrArray'] = $tmpArray;
	} else {
		$tmpArray = $_SESSION['fsrArray'];
	}
	$advancedArr = array ();
	if (isset ($tmpArray['file']) and $tmpArray['file']) {
		$advancedArr['file'] = $tmpArray['file'];
	} else {
		$advancedArr['file'] = '';
	}
	if (isset ($tmpArray['context']) and $tmpArray['context']) {
		$advancedArr['context'] = $tmpArray['context'];
	} else {
		$advancedArr['context'] = '';
	}
	if (isset ($tmpArray['contextbool']) and $tmpArray['contextbool']) {
		$advancedArr['contextbool'] = true;
	} else {
		$advancedArr['contextbool'] = false;
	}
	if (isset ($tmpArray['subfolder']) and $tmpArray['subfolder']) {
		$advancedArr['subfolder'] = $tmpArray['subfolder'];
	} else {
		$advancedArr['subfolder'] = '';
	}
	if (isset ($tmpArray['date']) and $tmpArray['date']) {
		$advancedArr['date'] = $tmpArray['date'];
	} else {
		$advancedArr['date'] = '';
	}
	if (isset ($tmpArray['date-dRng'])) {
		if($tmpArray['date-dRng']) {
			$advancedArr['date2'] = $tmpArray['date-dRng'];
		} else {
			$advancedArr['date2'] = '';
		}
	}
	if (isset ($tmpArray['who']) and $tmpArray['who']) {
		$advancedArr['who'] = $tmpArray['who'];
	} else {
		$advancedArr['who'] = '';
	}
	if (isset ($tmpArray['notes']) and $tmpArray['notes']) {
		$advancedArr['notes'] = $tmpArray['notes'];
	} else {
		$advancedArr['notes'] = '';
	}
	extract($advancedArr);
	if (isset ($_GET['cab'])) {
		$cabinet = $_GET['cab'];
	} else {
		$cabinet = $_SESSION['cab'];
	}
	
	$_SESSION['cab'] = $cabinet;
	$cabSearchArray = array ();
	foreach (getCabinetInfo($db_object, $cabinet) as $myIndex) {
		if (isset ($tmpArray[$myIndex]) and $tmpArray[$myIndex]) {
			$cabSearchArray[$myIndex] = $tmpArray[$myIndex];
		}
	}

	if (isset ($_POST['newBookmarkName'])) {
		// inform user of new bookmark and get name
		$newBookmarkName = $_POST['newBookmarkName'];
		$searchmess = "Bookmark <b>".stripslashes($newBookmarkName)."</b> has been added</div>";
		$newBookmark = array ();
		$newBookmark['name'] = $newBookmarkName;
		$newBookmark['cabinet'] = $cabinet;
		$newBookmark['fields'] = $cabSearchArray;
		$newBookmark['advanced'] = $advancedArr;

		// put it in all the bookmarks
		$allBookmarks[] = $newBookmark;

		// add bookmarks to database
		$userSettings->set('bookmarks', base64_encode(serialize($allBookmarks)));
		$loadBookmark = sizeof($allBookmarks) - 1;
		echo<<<ENERGIE
<script type="text/javascript">
  top.searchPanel.loadBookmarks($loadBookmark);
</script>

ENERGIE;
	}
	$fromBack = '';
	if (isset ($_GET['fromBack'])) {
		$fromBack = $_GET['fromBack'];
	}
	if (isset ($_GET['search'])) {
		$searchObj = $_GET['search'];
	}

	$glbSettings = new GblStt($user->db_name, $db_doc);

	//////////////////////////////////////////////////////////////////////
	//This section is concerned with burning results to cd.
	$userSettings = new Usrsettings($user->username, $user->db_name);
	$admin_backup = $glbSettings->get("adminBackup");
	$user_backup = $glbSettings->get("userBackup");

	if (($user->isAdmin() and $admin_backup == 1) or $user->isDepAdmin()) {
		$cd_permission = 1;
	}
	elseif ($user_backup != 0) {
		$cd_permission = 1;
	} else {
		$cd_permission = 0;
	}

	$csvRestrict_ro = $glbSettings->get('csvRestrict');
	if ($csvRestrict_ro == "") {
		$csvRestrict_ro = "off";
	}

	$isoRestrict_ro = $glbSettings->get('isoRestrict');
	if ($isoRestrict_ro == "") {
		$isoRestrict_ro = "off";
	}

	$csvRestrict_user = $userSettings->get('csvRestrict');
	if ($csvRestrict_user == "") {
		$csvRestrict_user = "off";
	}

	$isoRestrict_user = $userSettings->get('isoRestrict');
	if ($isoRestrict_user == "") {
		$isoRestrict_user = "off";
	}

	if($user->checkSetting('documentView', $cabinet) || (isset($_GET['documentView']) and $_GET['documentView'] == 1)) {
		$documentView = 1;
	} else {
		$documentView = 0;
	}
	////////////////////////////////////////////////////////////////////////////
	//This section retrieves the page number of results that should be displayed
	if (!empty ($_GET['pageNum']) and $_GET['pageNum'] > 0) {
		$pageNum = $_GET['pageNum'];
	} else {
		$pageNum = 1;
	}
	
	if (isset ($_GET['resPerPage']) && $_GET['resPerPage']) {
		$resPerPage = $_GET['resPerPage'];
	} else {
		$resPerPage = 10;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
		This section creates a new fileSearch object. Then it searches the 
	selected cabinet for the file specified, using the findFile function. If
	context is specified it searches all file with file name for that context.
	*/
	$sArr = array('index1','search');
	$wArr = array('username' => $user->username,
				'cabinet' => $cabinet);
	$oArr = array('index1' => 'ASC');
	$gArr = array('index1','search');
	$filterList = getTableInfo($db_object,'cabinet_filters',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr,true);
	if(count($filterList)) {
		$sArr = array();
		foreach($filterList AS $filter => $sList) {
			$_POST[strtoupper($filter)] = "";
			foreach($sList AS $s) {
				$sArr[$filter] = trim($sArr[$filter]).' "'.$s.'"';
				$_POST[strtoupper($filter)] = $_POST[strtoupper($filter)].'"'.$s.'"';
			}
		}
		$tempTableSearch = new search();
		$tempTable = $tempTableSearch->getSearch($cabinet, $sArr, $db_object);
	}	

	if (!isset ($_GET['temp_table'])) {
		if (!isset ($searchObj)) {
			$subfolder = splitOnQuote($db_object, $subfolder, true);
			if (!$contextbool) {
				$context = splitOnQuote($db_object, $context, true);
			}
			$who = splitOnQuote($db_object, $who, true);
			$notes = splitOnQuote($db_object, $notes, true);
			$file = splitOnQuote($db_object, $file, true);
			$search = new fileSearch($user);

			if(!isset($date2)) {
				$date2 = -1;
			}
			$search->findFile($cabinet, $file, $context, $subfolder, $date, $date2, $who, $notes, $contextbool);
			
			$searchObj = base64_encode(serialize($search));
		} else {
			$search = unserialize(base64_decode($searchObj));
		}
	} else {
			
		$search = new fileSearch($user);
		$search->cabinetName = $cabinet;
		$search->tempTableName = $_GET['temp_table'];
		$searchObj = base64_encode(serialize($search));
	}

	
	$tableName = $search->getTempTable();
	$numResults = getTableInfo($db_object, $tableName, array('COUNT(*)'), array(), 'queryOne');

	///////////////////////////////////////////////////////////////////
	//GET A LISt of results the is resPerPage long
	$result = new fileSearchResult();
	$resultList = $search->getResults($pageNum, $resPerPage, $numResults, $sorttype, $sortdir);
	$temp_table = $search->getTempTable();

	//check for export here
	if (isset ($_GET['func']) and $_GET['func'] == 'export_csv') {
		createCSVFiles($user, $cabinet, $temp_table);
	}

	//check for Burn to CD here
	if (isset ($_GET['func']) and $_GET['func'] == 'burn') {
		$DepID = getTableInfo($db_object, 'departments',
			array('departmentid'), array('real_name' => $cabinet, 'deleted' => 0), 'queryOne');
		createISO($user, $DepID, $temp_table, 1);
	}

	////////////////////////////////////////////////////////////////
	//calculate now viewing to and from values
	$totalPages = ceil($numResults / $resPerPage);
	if ($pageNum > $totalPages and $totalPages > 0) {
		$pageNum = $totalPages;
	}
	$from = (($pageNum -1) * $resPerPage) + 1;
	$to = $from + $resPerPage -1;
	if ($pageNum == $totalPages) {
		$to = $numResults;
	}

	///////////////////////////////////////////////////////////////
	//calculate the width of the table so that it will be dynamic 
	//with the number of indices
	$listSize = sizeof($resultList);
	if ($numResults > 0 and $resultList) {
		$temp = $resultList[0];
		$temp2 = sizeof($temp->getIndexHeaders());
	} else {
		$temp2 = 0; //if there are no results to display
		$from = 0;
		$to = 0;
	}
	////////////////////////////////////////////////////////////////
	//sets security to be done once
	$security = $user->checkSecurity($cabinet);
	//2==rw,1==ro

	echo<<<ENERGIE
<script type="text/javascript">
cabinet = '$cabinet';
resPerPage = '$resPerPage';
pageNum = '$pageNum';
numResults = '$numResults';
temp_table = '$temp_table';
totalPages = '$totalPages';
mySelected = '';
parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
parent.viewFileActions.window.location = '../energie/bottom_white.php';
</script>
<script type="text/javascript" src="../lib/file_search_results.js"></script>
</head>
<body>
<div style="display: none">
<input id="totalPages" type="hidden" value="$totalPages"/>
</div>
<form id="f1" method="post" action="file_search_results.php?cab=$cabinet&amp;fromBack=$fromBack&amp;temp_table=$temp_table&amp;pageNum=$pageNum&amp;resPerPage=$resPerPage">
<div style="width: 24%; float: left">
	<div class="lnk_black">Now Viewing: $from - $to</div>
	<div class="lnk_black"><span id="totalresfound">$numResults</span> {$trans['Results Found']}</div>
</div>
ENERGIE;
	$navStyle = "";
	if ($totalPages < 2) {
		$navStyle = "visibility: hidden;";
	}
	echo<<<ENERGIE
       <div id="navTop" style="$navStyle float: left; width: 49%">
       <table style="margin-left: auto; margin-right: auto; text-align: center">
         <tr>
         <td><img src="images/begin_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
				onclick="navArrowsBegin()"/></td>
         <td><img src="images/back_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
				onclick="navArrowsDown()"/></td>
         <td style="white-space: nowrap" class="lnk_black">
           <input name="indexID" value="$pageNum" type="text" onkeypress="return allowDigi(event,this.value)" size="3"/> of <span id="totalpagefound">$totalPages</span>
         </td>
         <td><img src="images/next_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
				onclick="navArrowsUp()"/></td>
         <td><img src="images/end_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
				onclick="navArrowsEnd()"/></td>
         </tr>
       </table>
      </div>
<div style="float: left; width: 24%; text-align: right">
	<span class="lnk_black">Results/Page:</span>
	<select id="res" onchange="getResPerPage(document.getElementById('res').value)"> 
	  <option selected="selected" value="$resPerPage">$resPerPage</option>
	  <option value="100">100</option>
	  <option value="50">50</option>
	  <option value="25">25</option>
	  <option value="10">10</option>
	</select>
</div>
<div style="clear: both">
&nbsp;
</div>
ENERGIE;
	if ($numResults > 0) {
		echo "<div id=\"allBtns\" style=\"text-align: right\">\n";
		if(!check_enable('lite',$user->db_name)) {
			echo<<<ENERGIE
<img id="bookmarkImg" style="cursor: pointer" alt="" src="../images/paste_16.gif" onclick="booknameSpotClick()"/>

ENERGIE;
		}
		//exporting -- make sure allowed in restrictions
		$ro = $security;

		//allow restrictions for exporting to CSV and ISO
		if(!check_enable('lite',$user->db_name)) {
			if ($ro == 1 and ((($csvRestrict_ro == "off" && $csvRestrict_user == "off") || $csvRestrict_user == "off") || $user->isDepAdmin()) or $csvRestrict_user == "off") {
			//		echo "<span><input type='submit' name='export_csv' value='Export Results To Text'/></span>";
				echo "<span><img onclick=\"submitBtn('export_csv')\" style=\"cursor: pointer\" alt=\"\" title=\"Export Results To CSV\" src=\"../images/chart_16.gif\" /></span>\n";
			}
		}

		echo "&nbsp;";

		if(!check_enable('lite',$user->db_name)) {
			if ($cd_permission == 1) { //user has some kind of permissions

				if (($ro == 1 and ($user->isAdmin() || $user_backup == 2) and ((($isoRestrict_ro == "off" && $isoRestrict_user == "off") || $isoRestrict_user == "off") || $user->isDepAdmin())) or (($user->isAdmin() || $user_backup == 1 || $user_backup == 2) and (($isoRestrict_user == "off") || $user->isDepAdmin()))) {

				//			echo "<span><input type='submit' name='burn' value='Make CD of Results'/></span>\n";
					echo "<span><img onclick=\"submitBtn('burn')\" style=\"cursor: pointer\" alt=\"\" title=\"Burn To CD\" src=\"../images/cd_16.gif\" /></span>\n";
				}
			}
		}
		//<input type="button" name="bookmark" value="Bookmark This Search" onclick="booknameSpotClick()"/>

		$tt_name = $search->getTempTable(); //get the temp table name

		// This top here outputs
		echo<<<ENERGIE
</div>
	<div style="text-align: center; margin-left: auto; margin-right: auto" id="errorbox" class="error">$searchmess</div>
ENERGIE;

		///////////////////////////////////////////////////////////////////
		/**
			This section displays each searchResult with all of the data
		in table format. The data are filename, size, path, date created, who indexed,
		docID, and a list of indices.
		*/

		//////////////////////////////////
		//this is for cabinet indices
		$editLabel = $trans['Edit'];
		$deleteLabel = $trans['Delete'];

		$showHits = $userSettings->get('context_hits');

		echo "<div style=\"text-align: center\">\n";

		//============================		
		// These are all the headers

		echo "<table style=\"margin-left: auto; margin-right: auto; border: 0; padding: 2px\" class=\"lnk_black\">";
		echo "     <tr style=\"background-color: #003b6f\">
				<td onclick=\"top.mainFrame.window.location='$myURL"."temp_table=$tt_name&amp;sorttype=name&amp;sortdir={$sort['name']}'\" style=\"white-space: normal; text-align: center; cursor: pointer\"><b style=\"color:white\">File Name</b></td>\n";
		if ($security == 2) // show if to show edit
			{
			echo "  <td style=\"width: 50px\"><b style=\"color:white\">&nbsp;$editLabel&nbsp;</b></td>\n";

			if ($user->isAdmin() && $user->checkSetting('deleteFiles', $cabinet)) // check if to show delete
				echo "<td style=\"width: 50px\"><b style=\"color:white\">&nbsp;$deleteLabel&nbsp;</b></td>";
		}
		echo "<td style=\"white-space: normal; text-align: center\"><b style=\"color:white\">Size</b></td>\n";
		echo "<td onclick=\"top.mainFrame.window.location='$myURL"."temp_table=$tt_name&amp;sorttype=date&amp;sortdir={$sort['date']}'\"  style=\"white-space: normal; text-align: center; cursor: pointer\"><b style=\"color:white\">Date Created</b></td>\n";
		echo "<td onclick=\"top.mainFrame.window.location='$myURL"."temp_table=$tt_name&amp;sorttype=who&amp;sortdir={$sort['who']}'\" style=\"white-space: normal; text-align: center; cursor: pointer\"><b style=\"color:white\">Who Indexed</b></td>\n";
		echo "<td style=\"white-space: normal; text-align: center\"><b style=\"color:white\">Folder Info</b></td>\n";
		if ($showHits && $contextbool) { // Check if to show hits
			echo "<td onclick=\"top.mainFrame.window.location='$myURL"."temp_table=$tt_name&amp;sorttype=hits&amp;sortdir={$sort['hits']}'\" style=\"white-space: normal; text-align: center; cursor: pointer\"><b style=\"color:white\">Hits</b></td>\n";
		}
		echo "</tr>";
		//end  of cabinet indices
		/////////////////////////////////
		if ($numResults > 0) {
			for ($i = 0; $i < $listSize; $i ++) {
				$result = $resultList[$i];
				$name = $result->getFileName();
				$size = $result->getFileSize();
				$path = $result->getPath();
				$date = $result->getCreationDate(); // get information about file
				$who = $result->getWhoCreated();
				$docid = $result->getDocID();
				$hits = $result->getHits();
				$indexHeaders = $result->getIndexHeaders();
				$indices = $result->getIndices();

				$ordering = $result->getOrdering();
				$tab = $result->getTab();
				if ($tab == NULL)
					$tab = "main";
				$fileID = $result->getFileID();
				$whereArr = array("doc_id=".(int)$docid,"ordering < ".(int)$ordering);
				if($tab != "main") {
					$whereArr[] = "subfolder='$tab'";
					$whereArr[] = "filename IS NOT NULL";
				} else {
					$whereArr[] = "subfolder IS NULL";
				}
				$ordering1 = getTableInfo($db_object,$cabinet."_files",array('COUNT(*)'),$whereArr,'queryOne');

				if ($tab == "")
					$tab = "main";
				$mySelected = "s-".$docid.":".$tab.":".$ordering;

				echo<<<ENERGIE
    <tr id="$mySelected" style="background-color: #ebebeb; cursor: pointer" onmouseover="mOver('$mySelected')" 
		onmouseout="mOut('$mySelected')">
	
		<td onclick="openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);">$name</td>
ENERGIE;

				if ($security == 2) {
					//			$tmpfile = strtok($name,".");
					$tmpfile = substr($name, 0, strrpos($name, "."));
					echo "<td onclick=\"dialog('$mySelected','$fileID','$tmpfile')\">\n";
					echo "<img alt=\"\" src=\"images/file_edit_16.gif\" style=\"height:14px;width:14px\"/></td>\n";
				}
				//the trash icon; check whether file deletion is enabled
				if ($user->isAdmin() && $security == 2 && $user->checkSetting('deleteFiles', $cabinet)) {
					echo "<td><img onclick=\"askAdmin('$docid','$name','$tab');\" ";
					echo "src=\"images/trash.gif\" alt=\"\" height=\"14\" width=\"14\"/></td>";
				}

				echo<<<ENERGIE
		<td onclick="openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);">$size</td>
		<td onclick="openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);">$date</td>
		<td onclick="openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);">$who</td>

ENERGIE;
				echo "<td onclick=\"openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);\">";

				// File Info stuff
				for ($j = 0; $j < sizeof($indexHeaders); $j ++) {
					$temp1 = $indexHeaders[$j];
					$temp2 = h($indices[$j]);
					echo " $temp1: $temp2 <br/>";
				}
				echo "</td>\n";

				// Page rank and hits stuff
				if ($showHits && $contextbool) {
					echo "<td onclick=\"openAllThumbs($docid, $pageNum, '$mySelected', '$tab', $ordering, $ordering1, '$fileID',$documentView);\">$hits</td>";
				}
				echo "</tr>\n";
			}
		}

		echo "  </table>\n";
		echo "  </div>\n";
		echo<<<ENERGIE
  <div id="navBot" style="$navStyle margin-left: auto; margin-right: auto">
  <table style="margin-left: auto; margin-right: auto">
    <tr>
      <td><img src="images/begin_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
			onclick="navArrowsBegin()"/></td>
      <td><img src="images/back_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
			onclick="navArrowsDown()"/></td>
         <td style="white-space: nowrap; text-align: center" class="lnk_black">
           <input name="indexID" value="$pageNum" type="text" onkeypress="return allowDigi(event,this.value)" size="3"/>
            of <span id="totalpagefoundb">$totalPages</span>  
         </td>
      <td><img src="images/next_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
			onclick="navArrowsUp()"/></td>
      <td><img src="images/end_button.gif" alt="" onmouseover="javascript:style.cursor='pointer';"
			onclick="navArrowsEnd()"/></td>
    </tr>
  </table>
  </div>
  <script type="text/javascript">
  	if(parent.sideFrame.mySelected) {
		mySelected = parent.sideFrame.mySelected;
		document.getElementById(mySelected).style.backgroundColor = '#8799e0';
	}
  </script>
ENERGIE;
	} else {
		echo<<<ERROR
	<div style="text-align: center" id="errorbox" class="error">There were no results found</div>
ERROR;
	}
	////////////////////////////////////////////////////////////////////////
	//If the user has specified a context search a polling page will be loaded
	//to update the results. It is only started once during a search.
	// poll1 is sent by searchPoll to tell this page not to do this
	if (!count($context) && !isset ($_GET['poll1']) && $contextbool) {
		echo<<<ENERGIE
  <script>
   parent.leftFrame1.window.location='bottom_white.php' ; // to clear it out
   parent.leftFrame1.window.location='../poll/searchPoll.php?temp_table=$temp_table&resPerPage=$resPerPage&pageNum=$pageNum&cab=$cabinet&search=$searchObj';
  </script>
ENERGIE;
	}
	echo<<<ENERGIE
</form>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
