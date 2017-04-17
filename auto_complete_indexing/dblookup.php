<?php
include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../db/db_engine.php';
//need for function countFiles
include_once '../lib/filename.php';
include_once '../lib/cabinets.php';
include_once '../lib/settings.php';
include_once '../lib/indexing2.php';
include_once '../lib/odbc.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/sagWS.php';

if ($logged_in and $user->username !== '') {
	$settings = new GblStt($user->db_name, $db_doc);
	$dateFuncs = $settings->get('date_functions');
	if (!$dateFuncs) {
		$dateFuncs = 'false';
	}

	$of = $trans['of'];
	$Submit = $trans['Submit'];
	$Delete = $trans['Delete'];
	$Skip = $trans['Skip'];
	$back = $trans['Back'];
	$from = $trans['from'];
	$foldersLeft = $trans['foldersLeft'];

	$ID = $_GET['ID'];
	$cab = $_GET['cab'];
	$allIndices = getCabinetInfo($db_object, $cab);
	$numIndices = count($allIndices);
	//need post variable for image scrolling
	if (!isset ($_POST['blanker'])) {
		$_POST['blanker'] = $_GET['blanker'];
	}
	if ($_POST['Submit'] == "Delete") {
		$result = getTableInfo($db_object,$cab."_indexing_table",array(),array('id'=>(int)$ID));
		$badrow = $result->fetchRow();
		//      $badpath = $user->db_name."/indexing/".$cab."/".$badrow['folder'];
		$badpath = $user->db_name."/indexing/".$cab."/".$badrow['folder'];

		$badpath = $DEFS['DATA_DIR']."/".$badpath;
		if (is_dir($badpath)) {
			delDir($badpath);
		    	$whereArr = array('id'=>(int)$ID);
		    	deleteTableInfo($db_object,$cab."_indexing_table",$whereArr);
		}
		echo "<script>\n";
		echo "var urlStr = '../secure/getImage.php?cab=$cab&type=auto_complete_indexing';";
		echo "top.mainFrame.IndexMainFrame.window.location.href = urlStr;";
		echo "</script>\n";
	}
	elseif ($_POST['Submit'] == "Submit" || isset ($_POST['banner']) || isset
			($_GET['page'])) {
		$fieldName = $_GET['name'];

		if (isset ($_POST['banner'])) {
			$bannerID = $_POST['banner'];
		} else {
			$bannerID = $_GET['banner'];
		}

		$blanker = $_POST['blanker'];

		$tableName = $settings->get("indexing_$cab");
		$count = 0;

		if ($tableName == "odbc_auto_complete") {
			$transInfo = getTableInfo($db_object, 'odbc_auto_complete', array(), 
				array('cabinet_name' => $cab), 'queryRow');
			$odbcDBObj = getODBCDbObject($transInfo['connect_id'], $db_doc);
			$bannerInfo = getODBCRow($odbcDBObj, $bannerID, $cab, $db_object, '', $user->db_name, $settings);
			$tmpArray = array();
			foreach($bannerInfo as $myKey => $myTmp) {
				if(in_array($myKey, $allIndices)) {
					$tmpArray[$myKey] = $myTmp;
				}
			}
			$bannerInfo = $tmpArray;
			foreach($allIndices as $myIndex) {
				if(!isset($bannerInfo[$myIndex])) {
					$bannerInfo[$myIndex] = '';
			}
			}
		} elseif ($tableName == 'sagitta_ws_auto_complete') {
			$bannerInfo = getSagRow($cab, $bannerID, $user->db_name);
		} else {
			$findBanner = getTableInfo($db_object, $tableName, array(), array($fieldName => $bannerID));
			$bannerInfo = $findBanner->fetchRow();
		}
		if($bannerInfo) {
			$count = 1;
		} else {
			$count = 0;
		}

		$cabinetID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');

		// used for scrolling PART 1
		$indexingFoldersInfo = getTableInfo($db_object,$cab."_indexing_table",array(),array('id'=>(int)$ID));
		$myrow = $indexingFoldersInfo->fetchRow();
		$filePath = $myrow['path'];
		$temp = substr(strrchr($filePath, ' '), 1);

		//$filePath = substr($filePath, 0, strrpos($filePath, ' '));
		$directoryName = substr(strrchr($filePath, ' '), 1);
		$filePath = str_replace(' ', '/', $filePath,3);
		$filePath = $DEFS['DATA_DIR'].'/'.$filePath;

		//returns an array of sorted filenames
		//corresponding page-1 == element in array 
		if (!(is_dir($filePath))) {
			echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>File No Longer Exists</title>
</head>
<body>
<script type="text/javascript">
	document.onload = top.mainFrame.window.location = "../secure/indexing.php?cab=$cab";
</script>
</body>
</html>

ENERGIE;
			die();
		}
		$filesArray = $_SESSION['indexFileArray'];
		//returns a pool of characters, numbers, and special characters
		$pool = $user->characters(4);
		echo<<<ENERGIE
<html>
<head>
<title>DBLookup</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<link rel="stylesheet" type="text/css" href="../lib/calendar.css"/>
<script src="../lib/calendar.js"></script>		
</head>
<script type="text/javascript">
var formToSubmit = 'submitBtn';
var dateFunctions = $dateFuncs;
ENERGIE;
		//Test to see if scrolling is allowed
		$setScroll = $settings->get('scroll');
		// for updating old db versions 
		if (!$setScroll) {
			$settings->set('scroll', '1');
			$setScroll = $settings->get('scroll');
		}

		if ($setScroll) {
			//beginning of scroll PART1
			$numFiles = countFiles($filePath);

			echo<<<ENERGIE
function passIndex(cab, ID, page, oldPage) 
{
	var sumVar;
	var passString = "&"; //string that is added to onload statement
	var verPage;

	if(page < 1) {
		verPage = 1;
	} else if(page > $numFiles) {
		verPage = $numFiles;
	} else if((page > 0) && (page <= $numFiles)) {
		verPage = page;
	} else {
		verPage = oldPage;
	}

ENERGIE;
			$ct = count($allIndices);
			for ($k = 0; $k < $ct; $k ++) {
				$formInputBox = $allIndices[$k];
				//sumVar = document.banner.$formInputBox.value;
				echo<<<ENERGIE
	
	sumVar = document.getElementById("field-$k").value;
	if($k == $ct - 1) {
		passString = passString + "$k=" + sumVar;
	} else {
		passString = passString + "$k=" + sumVar + "&";
	}
	
ENERGIE;
			}
			echo<<<ENERGIE
	var urlStr = '../secure/getImage.php?cab=' + cab + '&ID=' + ID + '&type=auto_complete_indexing';
	urlStr += '&name=$fieldName&page=' + verPage + '&banner=$bannerID';
	urlStr += passString + '&numIndex=$ct&blanker=$blanker';

	document.onload = parent.IndexMainFrame.window.location = urlStr;
}

ENERGIE;
		}

		//jscript function for back button that saves user entered values
		echo<<<ENERGIE
function passBack(cab, ID, page)
{

ENERGIE;
		//	$formInputBox = $allIndices[0]; 
		//firstIndex = document.banner.$formInputBox.value;
		echo<<<ENERGIE
	
	firstIndex = document.getElementById("field-0").value;
	var urlStr = 'indexValues.php?cab=' + cab + '&ID=' + ID;
	urlStr += '&name=$fieldName&page=' + page + '&passString=' + firstIndex;

	document.onload = parent.bottomFrame.window.location = urlStr;
}

function loadGetImage()
{
	var urlStr = '../secure/getImage.php?cab=$cab&quota=$ID' + '&type=auto_complete_indexing';
	top.mainFrame.IndexMainFrame.window.location.href = urlStr;
}

function checkEnter(currPage, value)
{
	if(event.keyCode == 13) {
		return passIndex('$cab','$ID', value, currPage);
	}
}
var currShowing;

function registerFuncs() {
	var numIndices = '$numIndices';
	if(!numIndices) {
		numIndices = 0;
	}
	for(var i = 0; i < numIndices; i++) {
		var inpField = document.getElementById('field-' + i);
		if(inpField) {
			if(dateFunctions && (inpField.name.search(/date/i) != -1 || inpField.name.search(/DOB/i) != -1)) {
				inpField.validate = validateDate;
				newImg = document.createElement('img');
				newImg.src = '../images/edit_16.gif';
				newImg.style.cursor = 'pointer';
				newImg.style.verticalAlign = 'middle';
				newImg.input = inpField;
				newImg.onclick = dispCurrMonthIndex;
				inpField.parentNode.insertBefore(newImg, inpField.nextSibling);
			} else {
				inpField.validate = function(){return true;};
			}
		}
	}
}
	function validateForm() {
		var numIndices = '$numIndices';
		var inpField;
		for(var i = 0; i < numIndices; i++) {
			inpField = document.getElementById('field-' + i);
			if(inpField) {
				if(!inpField.validate()) {
					var errDiv = document.createElement('div');
					errDiv.style.position = 'absolute';
					errDiv.style.bottom = '1em';
					errDiv.className = 'error';
					errDiv.style.left = '2em';
					errDiv.appendChild(document.createTextNode(inpField.msg));
					document.body.appendChild(errDiv);
					return false;
				}
			}
		}
		return true;
	}
function dispCurrMonthIndex() {
	var inputBox = this.input;
	if(currShowing[inputBox.id]) {
		if (currShowing[inputBox.id].shim) {
			document.body.removeChild (currShowing[inputBox.id].shim);
		}
		document.body.removeChild(currShowing[inputBox.id]);
		currShowing[inputBox.id] = null;
	} else {
		var currDate = new Date();
		var newDiv = document.createElement('div');
		newDiv.style.visibility = 'hidden';
		new Calendar(currDate.getMonth(), currDate.getFullYear(), newDiv, inputBox);
		document.body.appendChild(newDiv);
		newDiv.style.position = 'absolute';
		newDiv.style.zIndex = 100;
		var tmpVal = 0;
		var el = inputBox;
		while (el) {
			tmpVal += el.offsetLeft;
			el = el.offsetParent;
		}
		tmpVal += inputBox.offsetWidth + 30;
		newDiv.style.left = tmpVal + 'px';
		if(newDiv.offsetLeft < 0) {
			newDiv.style.left = '0px';
		}
		newDiv.style.top = '0px';
		var iframe = document.createElement ('iframe');
		iframe.style.display = 'none';
		iframe.style.left = '0px';
		iframe.style.position = 'absolute';
		iframe.style.top = '0px';
		iframe.src = 'javascript:false;';
		iframe.frameborder = '0';
		iframe.style.border = '0px';
		iframe.scrolling = 'no';
		document.body.appendChild(iframe);
		iframe.style.top = newDiv.style.top;
		iframe.style.left = newDiv.style.left;
		iframe.style.width = newDiv.offsetWidth + 'px';
		iframe.style.height = newDiv.offsetHeight + 'px';
		iframe.style.zIndex = newDiv.style.zIndex - 1;
		newDiv.style.visibility = 'visible';
		iframe.style.display = 'block';
		newDiv.shim = iframe;
		currShowing[inputBox.id] = newDiv;
	}
}
</script>
<script type="text/javascript" src="../energie/func.js"></script>
</head>
<body onload="registerFuncs()" style="overflow-y: scroll">

ENERGIE;
		if ($count == 0) {
			echo "<div class=\"lnk_black\">ID Not Found</div>\n";
		}
		echo "<form name=\"banner\" target=\"bottomFrame\" method=\"POST\" ";
		echo "action=\"submitIndex.php?ID=$ID&cab=$cab\" onsubmit=\"return validateForm()\">\n";
		echo "<table width=\"100%\">\n<tr>\n";
		for ($i = 0; $i < sizeof($allIndices); $i ++) {
			$fieldName = str_replace("_", " ", $allIndices[$i]);
			echo "<td class=\"lnk_black\" width=\"17%\">$fieldName</td>\n";
		}
		echo "</tr>\n<tr>\n";
		for ($i = 0; $i < sizeof($allIndices); $i ++) {
			$fieldName = $allIndices[$i];
			$tmpName = strtolower($fieldName);
			if ($tmpName == "date_indexed") {
				$fieldVal = date('Y')."-".date('m')."-".date('d');
			} else {
				$fieldVal = $bannerInfo[$fieldName];
			}
			//check for settings for data type definitions
			$setstr = "dt,".$user->db_name.",$cabinetID,$fieldName";
			$setRet = $settings->get($setstr);

			//if there is a value entered by user
			if (isset ($_GET[$i]) and $_GET[$i]) {
				//$_GET[$i] comes from previously entered values saved after
				//changing pages
				$passInValue = $_GET[$i];
 				$fieldStr .= "<td style=\"white-space: nowrap\" class=\"lnk_black\" width=\"20%\">\n";
				$fieldStr .= "<input type=\"textfield\" id=\"field-$i\" name=\"$fieldName\" ";
				$fieldStr .= "onkeypress=\"return inputFilter(event);\" ";
				$fieldStr .= " value=\"$passInValue\" size=\"15\">\n</td>\n";
				//if ID is not found, first index, and bannerID is posted
			}
			elseif (($count == 0) and ($i == 0) and (isset ($_POST['banner']))) {
				//$_POST['banner'] comes from previously submitted value from
				//indexValues.php
				$passInValue = $_POST['banner'];
 				$fieldStr .= "<td style=\"white-space: nowrap\" class=\"lnk_black\" width=\"20%\">\n";
				$fieldStr .= "<input type=\"textfield\" id=\"field-$i\" name=\"$fieldName\" ";
				$fieldStr .= "onkeypress=\"return inputFilter(event);\" ";
				$fieldStr .= " value=\"$passInValue\" size=\"15\">\n</td>\n";
				//does something exist in the settings table?
			}
			elseif (!$setRet) {
 				$fieldStr .= "<td style=\"white-space: nowrap\" class=\"lnk_black\" width=\"20%\">\n";
				$fieldStr .= "<input type=\"textfield\" id=\"field-$i\" name=\"$fieldName\" ";
				$fieldStr .= "onkeypress=\"return inputFilter(event);\" ";
				$fieldStr .= " value=\"$fieldVal\" size=\"15\">\n</td>\n";
			} else {
				$items = explode(",,,", $setRet);
				$count = count($items);
				if ($count == 1) {
					$fieldStr .= "<td class=\"lnk_black\" width=\"20%\">\n";
					$fieldStr .= "<input type=\"textfield\" ";
					$fieldStr .= "id=\"field-$i\" name=\"$fieldName\" ";
					$fieldStr .= "onkeypress=\"return inputFilter(event);\" ";
					$fieldStr .= "value=\"$setRet\" size=\"15\"/>\n</td>\n";
				} else {
					$fieldStr .= "<td class=\"lnk_black\" width=\"20%\">\n";
					$fieldStr .= "<select id=\"$i\" name=\"$fieldName\">\n";
					$fieldStr .= "<option value=\"\"></option>\n";
					for ($o = 0; $o < $count; $o ++) {
						$fieldStr .= "<option value=\"{$items[$o]}\">";
						$fieldStr .= "{$items[$o]}</option>\n";
					}
					$fieldStr .= "</select>\n";
				}
				$fieldStr .= "</td>\n";
			}
		}
		//if page is passed from getImage.php or indexValues.php
		if (isset ($_GET['page']) and $_GET['page']) {
			$currPage = $_GET['page'];
			//if page is not passed, when scroll is disabled, set to first page
		} else {
			$currPage = 1;
		}

		//returns the filename at the page number
		$fileName = $filesArray[$currPage -1];
		$fileName = substr($fileName, strrpos($fileName, '/') + 1);

		echo $fieldStr;
		echo<<<ENERGIE
</tr>
</table>
<table width="100%">
<tr>
<td width="10%">

<input type="submit" id="submitBtn" name="Submit" value="$Submit"/>
</td>
<td width="10%">
<input type="submit" name="delete" value="$Delete"/>

</td>
<td width="10%">
<input type="button" value="$Skip" onclick="loadGetImage()">
</td>
<td valign="middle">
<a href="#" class="lnk_black" onclick="passBack('$cab','$ID','$currPage')">
<img src="../energie/images/back_button.gif" border="0">&nbsp;$back
</a>
</td>

ENERGIE;
		$whereArr = array('finished<total','flag=0','upforindexing=0');
		$count = getTableInfo($db_object,$cab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');
		echo "<td>\n$count $foldersLeft<br>\n";
		echo "Now Viewing $directoryName/$fileName\n</td>\n";
		echo "</tr>\n</table>\n";
		echo "</form>\n";

		//Beginning of scroll PART 2
		if ($setScroll) {
			//PART 1 used to be here
			//this is for $previous which passes the previous page
			if ($currPage == 1) {
				$previousPage = 1;
			} else {
				$previousPage = $currPage -1;
			}

			//this checks if $nextPage has reached the last page
			if ($currPage == $numFiles) {
				$nextPage = $numFiles;
			} else {
				$nextPage = $currPage +1;
			}

			//for arrows to flip through tif images
			echo<<<ENERGIE
<table width="25%" class="lnk" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="15%" align="right">
<a href="#" onclick="passIndex('$cab','$ID','1','$currPage')">
<img src="../energie/images/begin_button.gif" border="0">
</a>
</td>
<td width="2%" align="center">
<a href="#" onclick="passIndex('$cab','$ID','$previousPage','$currPage')">
<img src="../energie/images/back_button.gif" border="0">
</a>
</td>
<td nowrap="nowrap" align="center">
&nbsp;
<input name="textfield" value="$currPage" type="text" size="3" onkeypress="checkEnter('$currPage', this.value)"/>
&nbsp;
</td>
<td nowrap="yes" width="15%" align="center" class="lnk_black">
$of $numFiles&nbsp;
</td>
<td width="2%" align="center">
<a href="#" onclick="passIndex('$cab','$ID','$nextPage','$currPage')">
<img src="../energie/images/next_button.gif" border="0">
</a>
</td>
<td width="5%" align="left">
<a href="#" onclick="passIndex('$cab','$ID','$numFiles','$currPage')">
<img src="../energie/images/end_button.gif" border="0">
</a>
</td>
</tr>
</table>

ENERGIE;
			//End of scroll code
		}
		echo "</body>\n";
		echo "</html>\n";
	} else {
		echo<<<ENERGIE
<script type="text/javascript">
	top.mainFrame.window.location = "../secure/indexing.php";
</script>

ENERGIE;
	}

	setSessionUser($user);
	//end of login check
} else {
	//we want to log them out
	echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Log Out</title>
</head>
<body>
<script type="text/javascript">
	document.onload = top.window.location = "../logout.php";
</script>
</body>
</html>

ENERGIE;
}
?>
