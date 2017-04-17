<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'energiefuncs.php';

$cab = $_GET['cab'];
$checkSecurity = $user->checkSecurity($cab);
if ($checkSecurity == 2) {
	$security = true;
} else {
	$security = false;
}
if ($logged_in == 1 && strcmp($user->username, "") != 0 && ($checkSecurity != 0)) {
	$enabledArr = array ();
	//variables that may have to be translated
	$backToResults = $trans['Back To Results'];
	$enterFilename = $trans['Enter Filename'];
	$NoFiles = $trans['No Files Have Been Checked'];
	$doc_id = $_GET['doc_id'];
	if (isset($_GET['tab'])) {
		$tab = $_GET['tab'];
	} else {
		$tab = '';
	}
	if (isset ($_GET['index'])) {
		$index = $_GET['index'];
	} else {
		$index = '';
	}
	if (isset($_GET['table'])) {
		$temp_table = $_GET['table'];
	} else {
		$temp_table = '';
	}
	if(!empty($_GET['count'])) {
		$count = $_GET['count'];
	} else {
		$count = '';
	}
	if (isset($_GET['ID'])) {
		$ID = $_GET['ID']; //added these for reloading allthumbs.php from moveThumb.php
	} else {
		$ID = '';
	}
	if (isset($_GET['referer'])) {
		$referer = $_GET['referer']; //refering page
	} else {
		$referer = '';
	}
	$_SESSION['allThumbsURL'] = getRequestURI (); 
	$_SESSION['allThumbsGET'] = $_GET; //puts the get array into a session variable
	// for reorderThumbs.php
	if (strcmp($referer, "file_search_results.php") == 0) {
		$pageNum = $_GET['pageNum'];
		$resPerPage = $_GET['resPerPage'];
		$numResults = $_GET['numResults'];
		$fileID = $_GET['fileID'];
	} else {
		$pageNum = '';
		$resPerPage = '';
		$numResults = '';
		$fileID = '';
	}
	
	if(isset($_GET['selected'])) {
		$mySelected = $_GET['selected'];
	} else {
		$mySelected = '';
	}
	//set from redaction to auto select after page reloads
	if (isset($_GET['viewing'])) {
		$divID = $_GET['viewing']; //contains the unique document id of the <tr>
	} else {
		$divID = '';
	}
	if( $divID != NULL ) {
		$mySelected = $divID; //set the selected file
	}
	$settings = new GblStt($user->db_name, $db_doc);
	
	if(check_enable('lite',$user->db_name) || $settings->get('tab_hiding_'.$cab) == 1) {
		$showEveryTab = false;
	} else {
		$showEveryTab = true;
	}

	$frameWidth = 250;
	if($settings->get('frame_width')) {
		$frameWidth = $settings->get('frame_width');
	}
	
	$allTabs = queryAllTabs($db_object, $cab, $doc_id, $settings, $user->db_name, false);
	$whereArr = array('doc_id'=>(int)$doc_id);
	$department = getTableInfo($db_object,$cab,array(),$whereArr);
	if (PEAR :: isError($department)) {
		die("bad query <br>$query");
	}
	$myrow1 = $department->fetchRow();
	//This function is located in lib/utility.php
	$userOrder = new Usrsettings($user->username, $user->db_name);
	$order = $userOrder->get('order');
	if (!array_key_exists('order', $userOrder->settings)) { //if there is no key in user_settings
		// look for one in global settings
		$order = $settings->get('order');
		if (!array_key_exists('order', $settings->settings)) {
			//automatically set order=1 to see thumbnails view
			$settings->set('order', '1');
			$order = $settings->get('order');
		}
	}
	$stringSeparatedTabs = implode(",", $allTabs);

	echo<<<ENERGIE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>View All Thumbnails</title>
<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
<script type="text/javascript">
  parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,$frameWidth');
  parent.viewFileActions.window.location = '../energie/bottom_white.php';
  parent.document.getElementById('rightFrame').setAttribute('rows', '*,40');
  var selectedRow = "";
  var cab = "$cab";
  var did = "$doc_id";
  var tab = "$tab";
  var index = "$index";
  var noFiles = "$NoFiles";
  var temp_table = "$temp_table";
  var referer = "$referer";
  var resPerPage = "$resPerPage";
  var pageNum = "$pageNum"; 
  var numResults = "$numResults";
  var tabArr = new Array();
  var SeparatedTabs = "$stringSeparatedTabs";
  var currThumb = -1;
  var currPage = '$count';
  tabArr = SeparatedTabs.split(",");  
  var tabSelect = '';
  var selArr = new Array();
  var minSelected = 0;
  var buttonPress = false;
  var modified = false;
  var boolBC = false;

		function newOnclick(ID,URL) {
			getEl('editRow:'+ID).onclick = function() {
								parent.topMenuFrame.removeVersButton(true);
								parent.mainFrame.window.location = URL; }
		}

  function adjustFrameWidth(e) {
	var adjWidth;
	if(window.innerHeight) {
		adjWidth = window.innerWidth;
	} else {
		adjWidth = parent.document.getElementById('fileFrame').width;
	}
	getEl('outerDiv').style.width = adjWidth+'px';
  }

  function enableKeyPress(e) {
	evt = (e) ? e : event;
	if(evt.keyCode == 16) {
		buttonPress = true;
	}
  }

  function disableKeyPress(e) {
	evt = (e) ? e : event;
	if(evt.keyCode == 16) {
		buttonPress = false;
	}
  }

  document.onkeydown = enableKeyPress;
  document.onkeyup = disableKeyPress;

  function selectCheck(chk,tab) {
	selArr = new Array();
	tabSelect = tab;	
	selArr[0] = chk.id.replace("tab:"+tab+"-", "");
	if(buttonPress == true) {
		selectAllInBetween();
		buttonPress = false;
	}
  }

  function referenceIdCheck(id) {
	for(var i=0;i<selArr.length;i++) {
		if(id == selArr[i]) {
			return true;
		}
	}
	return false;
  }

  function selectAllInBetween() {
	minSelected = 0;
	var el = document.getElementById(tabSelect).getElementsByTagName('input');
	for(var i=0;i<el.length;i++) {
		if(el[i].type == 'checkbox' && el[i].checked) {
			var refID = el[i].id.replace("tab:"+tabSelect+"-", "");
			if(!referenceIdCheck(refID)) {
				//selArr[selArr.length] = parseInt(refID);
				selArr[selArr.length] = refID;
			}
		}
	}

	selectedChkBox = selArr[0];
	for(var i=1;i<selArr.length;i++) {
		if(selArr[i] < selectedChkBox && selArr[i] > minSelected) {
			minSelected = selArr[i]; 
		}
	}
	
	for(var i=parseInt(minSelected);i<selectedChkBox;i++) {
		var chkBoxId = 'tab:'+tabSelect+'-'+i;
		document.getElementById(chkBoxId).checked = true;
	}
  }

	function modifyImage(type) {
		var postStr = getFiles();
		if(postStr) {
			if(modified) {
				return;
			} else {
				modified = true;
			}
			var xmlhttp = getXMLHTTP();
			var URL = 'modifyImage.php?cab=$cab&doc_id=$doc_id&type='+type;
			xmlhttp.open('POST',URL,true);
			xmlhttp.setRequestHeader('Content-Type',
									 'application/x-www-form-urlencoded');
			xmlhttp.send(postStr);
			xmlhttp.onreadystatechange = function () {
				if(xmlhttp.readyState != 4) {
					return;
				}

				if(xmlhttp.responseXML) {
					var XML = xmlhttp.responseXML;
					var locArr = XML.getElementsByTagName('LOCATION');
					if(locArr.length) {
						window.location = '../'+locArr[0].firstChild.nodeValue;
					}
				}
			};
		} else {
			alertBox('must select a file');
		}
	}		

  function getFiles() {
	var inputTag = document.getElementsByTagName('input');
	var postStr = "";
	for(var i=0;i<inputTag.length;i++) {
		if( inputTag[i].type == 'checkbox' ){
			if( inputTag[i].checked == true ) {
				if( postStr != "" )
					postStr += "&"
				postStr += "check[]="+inputTag[i].value;
			}
		}
	}
	return postStr;
  }

  function adjustDivHeight() {
	if(window.innerHeight) {
		var bodyHeight = window.innerHeight;
	} else {
		var bodyHeight = parent.document.getElementById('fileFrame').height;
	}
	document.getElementById('outerDiv').style.height = bodyHeight+'px';	
  }

	function adjustFilenames(tName) {
		tDiv = 'outerDiv';
		if(tName) {
			tDiv = tName;
		}
		var fArr = $(tDiv).getElementsByTagName('span');
		for(var i=0;i<fArr.length;i++) {
			if(fArr[i].className == 'atfilename') {
				var t = fArr[i].parentNode.title;
				
				if(t.length > 20) {
					clearDiv(fArr[i]);
					fArr[i].appendChild(document.createTextNode(t.substr(0,17)));

					var width = fArr[i].parentNode.offsetWidth-10;
					var j = 17;
					while(fArr[i].offsetWidth < width) {
						clearDiv(fArr[i]);
						if(j < t.length) {
							fArr[i].appendChild(document.createTextNode(t.substr(0,(j))+'...'));
						} else {
							fArr[i].appendChild(document.createTextNode(t.substr(0,(j))));
							break;
						}
						j++;
					}
				}
			}
		}
	}

function fullScreenMode() {
	if(parent.bottomFrame.document.getElementById('newPage')) {
		top.document.getElementById('afterMenu').setAttribute('cols','0,*');
		top.document.getElementById('mainFrameSet').setAttribute('cols','100%,*');
		top.topMenuFrame.document.getElementById('fullScreen').style.display = 'none';
		top.topMenuFrame.document.getElementById('exitFullScreen').style.display = 'block';

		//pNum = 1;
		//if(parent.bottomFrame.document.getElementById('newPage')) {
		//	pNum = parent.bottomFrame.document.getElementById('newPage').value;
		//}
		//top.topMenuFrame.document.getElementById('newPage').value = pNum; 
		//top.topMenuFrame.document.getElementById('pageNum').value = getEl('pageNum').value;
		//top.topMenuFrame.document.getElementById('pageNum').totalPages = getEl('pageNum').totalPages;

		//clearDiv(top.topMenuFrame.document.getElementById('pageDetail'));
		//top.topMenuFrame.document.getElementById('pageDetail').appendChild(top.topMenuFrame.document.createTextNode(getEl('pageDetail').firstChild.nodeValue));

		top.topMenuFrame.document.getElementById('firstPage').onclick = function () {top.bottomFrame.navArrowsBegin() }; 
		top.topMenuFrame.document.getElementById('prevPage').onclick = function () {top.bottomFrame.navArrowsDown() }; 
		top.topMenuFrame.document.getElementById('nextPage').onclick = function () {top.bottomFrame.navArrowsUp() }; 
		top.topMenuFrame.document.getElementById('lastPage').onclick = function () {top.bottomFrame.navArrowsEnd() }; 
	} else {
		parent.sideFrame.alertBox('A file must be opened for Full Screen Mode');
	}
}

</script>
ENERGIE;
	if ($mySelected) {
		if (strcmp($referer, "file_search_results.php") == 0) {
			echo<<<ENERGIE
<script type="text/javascript">
	var mySelected = '$mySelected';
	function setSelectRow()
	{
    setSelectedRow('$mySelected');
	parent.bottomFrame.window.location="files.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index&fileID=$fileID&count=" + currPage;
	}
</script>\n
ENERGIE;
		} else {
			echo<<<ENERGIE
<script type="text/javascript">
	function setSelectRow()
	{
    setSelectedRow('$mySelected');
	parent.bottomFrame.window.location="files.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index&count=" + currPage;
	}
</script>\n
ENERGIE;
		}
	}
	echo<<<ENERGIE
<script type="text/javascript" src="../lib/barcode.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/allthumbs.js"></script>
<script type="text/javascript" src="../documents/scriptaculous/src/slider.js"></script>
<script type="text/javascript" src="../lib/windowTitle.js"></script>
<script>
	
	function scrollFunc() {
		var j = '$divID';
		if( j != '' ) {
			var elementJ = document.getElementById(j);
			var height = elementJ.offsetParent.offsetTop;
			var thepoint = elementJ.offsetTop;
			document.getElementById("outerDiv").scrollTop = thepoint + height;
		}
	}
</script>
ENERGIE;
	echo<<<ENERGIE
</head>
<body class="tealbg" style="margin-top:0; margin-left: 0; margin-right: 0; overflow-x:hidden">

<script type="text/javascript">
window.onload = function() {
ENERGIE;

	//currTab is passed from uploadFile to reload the page and automatically
	//display the current tab
	if (isset ($_GET['currTab']) and $_GET['currTab']) {
		echo "flipVisibleThumbs('{$_GET['currTab']}');";
	}

	echo<<<ENERGIE
		scrollFunc();
		noScroll();
		setTimeout(showThumbs, 1000);
ENERGIE;
	if($user->checkSetting('sliderBar', $cab)) {
		echo "adjustFilenames();";
	}

	if ($mySelected) {
		echo "setSelectRow();";
	}

	echo "return true;}</script>\n";

	$folderLoc = $myrow1['location'];
	$folderLoc = str_replace(' ', '/', $folderLoc);
	unset($myrow1['doc_id']);
	unset($myrow1['location']);
	unset($myrow1['deleted']);
	$myStr = implode(' - ', $myrow1);
	$myStr = str_replace("\n", "", $myStr);
	$myStr = str_replace("\r", "", $myStr);
	$myStr = str_replace(";", "", $myStr);
	$myStr = str_replace("'", "", $myStr);
	
	echo "<script type=\"text/javascript\">\n";
	echo "setTitle(3, \"{$user->cabArr[$cab]}\",'$myStr');\n";
	echo "</script>\n";
	echo "<div id='outerDiv' style='position:absolute;top:0px;width:{$frameWidth}px;overflow:scroll;overflow-x:hidden'>";
	if($user->checkSetting('sliderBar', $cab)) {
?>
	<div id="track" style="margin-top:10px;margin-left:auto;margin-right:5px;width: 200px; height: 10px; background-color: #ebebeb">
		<div id="sliderNob" style="height:15px;width:5px;background-color:red;cursor:move"></div>
	</div>
	<script type="text/javascript">
		//<![CDATA[
		var sld = new Control.Slider('sliderNob','track', {	
				range: $R(0,500),
				sliderValue: 500-(<?php echo $frameWidth; ?>-250),	
				onChange: function(v) {	
					var px = (500-Math.ceil(v)) + 250;
					$('outerDiv').style.width = px+'px';
					parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,'+px);
					setFrameWidth(px);	
					adjustFilenames();
				}
		});

		function setFrameWidth(w) {
			var xmlhttp = getXMLHTTP();
			var URL = '../lib/settingsFuncs.php?func=setFrameWidth&v1='+w;
			xmlhttp.open('GET',URL,true);
			xmlhttp.setRequestHeader('Content-Type',
									 'application/x-www-form-urlencoded');
			xmlhttp.send(null);
			xmlhttp.onreadystatechange = function () {
				if(xmlhttp.readyState != 4) {
					return;
				}

				if(xmlhttp.responseXML) {
				}
			};
		}

		// ]]>
	</script>
<?php
	}	
	echo "<form name=\"thumbs\" method=\"POST\" onSubmit=\"addBackButton()\" target=\"topFrame\" action=\"allthumbs.php\">\n";

	if (check_enable('versioning', $user->db_name))
		$enabledArr['versioning'] = 1;

	if (check_enable('redaction', $user->db_name)) {
		$enabledArr['redaction'] = 1;
	}

	$enabledArr['barcoding'] = 1;
	if (check_enable('workflow', $user->db_name)) {
		$documentInfo = getWorkflowIDs($db_object, $cab, $doc_id);
		if (is_array($documentInfo) ) {
			$status = getWFStatus($db_object, $documentInfo['id']);
		} else {
			$status = '';
		}
		if( $status && $status != "PAUSED" ) {
			$whereArr = array('wf_document_id'=>(int)$documentInfo['id'],'department'=>$user->db_name,'username'=>$user->username);
			$enabledArr['viewHistory'] = 1;
			if( getTableInfo($db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne')) {
				$enabledArr['workflow'] = 1;
			} else if ( $status == "COMPLETED" ) {
				$enabledArr['workflow'] = 3;
			}
		} else {
			if (is_array($documentInfo)) {
				$enabledArr['viewHistory'] = 1;
				$owner = getWFOwner($db_object, $documentInfo['id']);
				if ($owner == $user->username) {
					$enabledArr['workflow'] = 2;
				}
			} else {
					$enabledArr['workflow'] = 3;
				}
			}
	}

	if( $user->checkSetting('modifyImage', $cab) AND $security && isValidLicense($db_doc)) {
	echo<<<ENERGIE
<div>
	<fieldset>
		<legend>Image Manipulation</legend>
		<table>
			<tr style='cursor:pointer'>
				<td onclick="modifyImage('rotate')">Rotate Left 90</td>
			</tr>
			<tr style='cursor:pointer'>
				<td onclick="modifyImage('flip')">Flip</td>
			</tr>
		</table>
	</fieldset>
</div>
ENERGIE;
	}
	
	if(check_enable('redaction', $user->db_name) and 
		$user->checkSetting('viewNonRedact', $cab)) {
		
		printExportRedactInput();
	}
	

	if(isset($enabledArr['barcoding']) and $enabledArr['barcoding'] and $user->checkSetting('showBarcode', $cab)) {
		printSelect($db_object, $cab, $doc_id, $allTabs, $settings, $enabledArr, $user->db_name);
	}
	displaybuttons($cab, $doc_id, $tab, $temp_table, $user, $security, $order, $enabledArr, $settings,$db_object, $db_doc);
	$starting_directory = $DEFS['DATA_DIR'].'/'.$folderLoc;
	$fileArr = queryAllFilesInFolder($db_object, $cab, $doc_id);
	if($showEveryTab) {
		foreach($allTabs as $myTab) {
			$realtabname = $myTab;
			if(!isset ($fileArr[$realtabname])) {
				$fileArr[$realtabname] = array ();
			}
		}
	}
	//Get noShowTabs out of the array.
	$tmpArr = $fileArr;
	foreach($tmpArr as $myTab => $tabArr) {
		if(!in_array($myTab, $allTabs)) {
			unset($fileArr[$myTab]);
		}
	}
	$thumbArr = array();
	$cabPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/'.$cab;
	$cabThumbPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/thumbs/'.$cab;
	$numberOfFiles = array ();
	foreach($fileArr as $myTab => $tabArr) {
		$filesInTab = count($tabArr);
		if($filesInTab or $showEveryTab) {
			$numberOfFiles[$myTab] = $filesInTab;
		}
		$fileLoc = $starting_directory.'/';
		if($myTab != 'main') {
			$fileLoc .= $myTab.'/';			
		}
		foreach($tabArr as $eachFile) {
			$thumbLoc = str_replace($cabPath, $cabThumbPath, $fileLoc.$eachFile['filename']).'.jpeg';
			$thumbArr[$eachFile['id']] = array (
				'fileLoc' => $fileLoc.$eachFile['filename'], 
				'thumbLoc' => $thumbLoc,
				'ca_hash' => $eachFile['ca_hash'],
				'file_size' => $eachFile['file_size']
			);
		}
	}
	
	$_SESSION['thumbnailArr'] = $thumbArr;
	
	$vArr = getVersionedFilesArray($cab, $db_object, $doc_id);
	$cabID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');
	$noFiles = $trans['Tab Is Empty'];
	$maxJpegs = 2000; // the only place which limits the number of thumbnail tifs to display 
	echo "<div id=\"tabsDiv\">\n";
	$thumbCt = 0;
	for ($z = 0; $z < sizeof($allTabs); $z ++) {
		$realtabname = $allTabs[$z];
		if(isset($numberOfFiles[$realtabname])) {
			$myTabName = $realtabname;
			$myTabName = str_replace("_", " ", $myTabName);
			echo "<div class=\"allthumbstoolbox\" ";
			echo "id=\"tabDiv_$allTabs[$z]\">\n";
			echo "<div style=\"background-color:#003b6f;\" ";
			echo "onmouseover=\"javascript:style.cursor='pointer' \"> ";
			echo "<table>\n<tr>\n<td>\n";
			echo "<table><tr>\n";
				echo <<<HTML
<td>
	<input 
		type="checkbox"
	onclick="toggleSelectTab(this, '$realtabname')"
	title="Select All Files In $myTabName"
	>
</td>
HTML;
/*				if ($enabledArr['barcoding'] == 1) {
				echo "<td onclick=\"printDocutronBarcode('$cab', '$doc_id', '$realtabname')\">";
				echo "<img class=\"button\" alt=\"Print Barcode\" title=\"Print Barcode\" src=\"../images/barcode.gif\">";
				echo "</td>";
			}
*/
			echo "<td onclick=\"flipVisibleThumbs('{$allTabs[$z]}');\">\n";
			echo "<img alt=\"folder\" src=\"../images/folder.gif\">\n";
			echo "</td>\n";
			echo "<td onclick=\"flipVisibleThumbs('{$allTabs[$z]}');\" ";
			echo "id=\"tabName_$allTabs[$z]\" class=\"lnk\">$myTabName</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "</div>\n";
			if ($myTabName != "main" and count($numberOfFiles) > 1) {
				echo "<div id=\"$allTabs[$z]\" style=\"padding:1px;display:none;\">\n";
			} else
				echo "<div id=\"$allTabs[$z]\" style=\"padding:1px;\">\n";
		echo "<table style=\"width: 98%\" border=\"0\" cellspacing=\"0\">\n";
			if (!isset($numberOfFiles[$realtabname]) or $numberOfFiles[$realtabname] == 0) {
			echo "<tr>\n<td style=\"color: white\">$noFiles</td>\n</tr>\n";
		} else {
			$count = 0;
			$tabArr = $fileArr[$realtabname];
			$numOfJpegs = $numberOfFiles[$realtabname];
			$numFiles = $numOfJpegs;

			foreach($tabArr as $tabFiles) {
				$dispName = getDisplayName($tabFiles['parent_filename'], $tabFiles['filename']);
				$key_str = $doc_id."@@@";
				if ($realtabname != "main") {
					$path = $starting_directory."/".$realtabname."/";
					$key_str .= "$realtabname@@@";
				} else {
					$path = $starting_directory;
					$key_str .= "@@@";
				}
				$key_str .= $tabFiles['ordering'];
				//place the notes value into the global array
				$all_notes[$key_str] = $tabFiles['notes'];
				$fileName = $tabFiles['filename'];
				$fileID = $tabFiles['id'];
				$myPathInfo = pathinfo($fileName);
				if (isset ($myPathInfo['extension'])) {
					$ext = $myPathInfo['extension'];
					$ext = strtolower($ext);
				} else {
					$ext = '';
				}
				$who_indexed = $tabFiles['who_indexed'];
				$date_created = $tabFiles['date_created'];
				if (!((strcmp("tif", $ext) == 0) || (strcmp("tiff", $ext) == 0)))
					$numOfJpegs --; //files other than tifs should not count

				$orderNum = $tabFiles['ordering'];
				if ($z == 0 && $count == 0) {
					$setStr = "s-".$doc_id.":".$realtabname.":".$orderNum;
				}
				if($realtabname == 'main') {
					$tabName = '';
				} else {
					$tabName = $realtabname;
				}
				//$order=1 if thumbnails view
				if ($order) {

					/* No DB CALLS */
					displayJpeg($cab, $doc_id, $orderNum, $realtabname,
								$fileName, $count, $numOfJpegs, $maxJpegs,
									$fileID, $all_notes, $ext, $thumbCt);
				}

				/* this function makes no data bases calls */
				displayFilename($cab, $doc_id, $orderNum, $tabName, $dispName,
								$count, $fileID, $order, $temp_table, $index,
								$security, $db_object, $user, $vArr, $enabledArr,
								$cabID, $thumbCt);

				/* DB CALLS */
				createCheckbox($tabName, $tabFiles['id'], $count);
				//if not thumb view, details view
				if (!($order)) { //$order=0 if details view

					/* DB CALLS */
					displayDetails($cab, $doc_id, $orderNum, $tabName,
								   $fileName, $count, $fileID, $temp_table,
								   $index, $security, $date_created, $who_indexed,
								   $ext, $numFiles, $all_notes);
				}
				$count ++;
			}
		} //end else
		echo "</table>\n";
			if ((sizeof($numberOfFiles)) /*&& ($z < (sizeof($numberOfFiles) - 1))*/)
			echo "</div>\n</div>\n";
	}
	}
	if(sizeof($numberOfFiles)) {
//		echo "</div>\n";
//		if(sizeof($numberOfFiles) > 1) {
//			echo "</div>\n";
//		}
	} else {
		echo '<div style="padding: 0.5em">'.$noFiles.'</div>';
	}
	echo "</div>\n";
	echo "</form>\n";
	echo "</div>\n";
	if (!$mySelected) {
		echo "<script type=\"text/javascript\">\n";
		echo "parent.bottomFrame.window.location = \"";
		echo "files.php?cab=$cab&doc_id=$doc_id&ID=0&tab=$tab&type=1&table=$temp_table&index=$index\";\n";
		echo "</script>\n";
	}
	
	echo "<script type=\"text/javascript\">\n";
	echo "adjustDivHeight();\n";
	echo "</script>\n";
	echo "</body>\n";
	echo "</html>\n";

	setSessionUser($user);
} else {
	logUserOut();
}
?>
