<?php
require_once '../check_login.php';
require_once '../documents/documents.php';
require_once '../lib/cabinets.php';
require_once '../lib/filter.php';
require_once '../lib/inbox.php';
require_once '../lib/searchLib.php';
require_once '../lib/tabFuncs.php';
require_once '../search/search.php';
require_once '../settings/settings.php';
require_once '../lib/licenseFuncs.php';

if ($logged_in and $user->username) {
	//translated variables
	$cabLabel = $trans['Cabinet_inbox'];
	$folders  = $trans['Folder'];
	$main     = $trans['Main'];
	$Tab      = $trans['Tab'];
	$File     = $trans['File'];

	if(!isValidLicense($db_doc)) {
?>
<html>
	<head>
		<title><?php echo $inboxSelector ?></title>
		<link rel="stylesheet" type="text/css" href="../lib/style.css">
	</head>
	<body class="tealbg" style="color:red">
		<div>Invalid License</div>
		<div>Inbox Operations Are Not Permitted</div>
	</body>
</html>
<?php
	die();
	}

	$settings = new GblStt ($user->db_name, $db_doc);
	$userStt = new Usrsettings ($user->username,$user->db_name, $db_doc);

	$db_object = $user->getDbObject ();
	$user->setSecurity ();
	
	$cabinets = array ();
	if ($user->access) {
		$cabinets = array_keys ($user->access, 'rw');
	}
	if (sizeof ($cabinets) == 1) {
		$cab = $cabinets[0];
	} elseif (isset ($_GET['cab'])) {
		$cab = $_GET['cab'];
	} else {
		$cab = '';
	}

	$folderValue = '';
	$encFolderValue = '';
	$encOdbcValue = '';
	$odbcValue = '';
	$searchType = '';
	if (isset ($_POST['searchType'])) {
		$searchType = $_POST['searchType'];
	}
	if ($searchType == 'folder' and !empty ($_POST['folderSearch'])) {
			$folderValue = $_POST['folderSearch'];
	} elseif ($searchType == 'odbc' and !empty ($_POST['odbcSearch'])) {
			$odbcValue = $_POST['odbcSearch'];
	}
	if (!$folderValue and !$odbcValue) {
		if (!empty ($_GET['search'])) {
			$folderValue = $_GET['search'];
		} elseif (!empty ($_GET['odbcSearch'])) {
			$odbcValue = $_GET['odbcSearch'];
		}
	}	

	if (isset ($_GET['doc_id'])) {
		$doc_id = $_GET['doc_id'];
	} else {
		$doc_id = '';
	}
	$page = 0;
	$temp_table = '';

	if ($cab and $user->checkSecurity ($cab) == 2) {
		if($folderValue) {
			$v = $folderValue;
			$folderValue = str_replace ('_', '\_', $folderValue);
			$folderValue = str_replace ('~', ' ', stripslashes ($folderValue));
			$folderValue = str_replace ('\\', '', $folderValue);
			$folderValue = str_replace ('\'', ' ', $folderValue);
			$folderValue = splitOnQuote ($db_object, $folderValue, true);		
			$temp_table = searchTable ($db_object, $cab, true, $folderValue);
			$folderValue = $v;
		} elseif ($odbcValue) {
			$temp_table = searchACForInbox ($db_object, $db_doc, $cab, $odbcValue,
					$settings->get ('indexing_'.$cab), $user);
		}

		if (!empty($temp_table)) {
			$total = getTableInfo ($db_object, $temp_table, array
					('COUNT(*)'), array (), 'queryOne');
			$isSearch = true;
		} else {
			$total = 0;
			$isSearch = false;
		}
		$noResults = false;
		if (!$total) {
			$search = new search ();
			$temp_table = $search->getSearch ($cab, array(), $db_object);
			$total = getTableInfo ($db_object, $temp_table, array
					('COUNT(*)'), array (), 'queryOne');
			if($isSearch) {
				$noResults = true;
			}
		}
	
		$per_page = 10;
		$totalPages = ceil ($total / $per_page);
		if (isset ($_POST['idpage'])) {
			$page = $_POST['idpage'];
		} elseif (isset( $_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 0;
		}
		
		if (($page + 1) >= $totalPages and $totalPages > 0) {
			$page = $totalPages - 1;
		} elseif($page < 0) {
			$page = 0;
		}

		$tempPage = $page + 1;
		$start = $page * $per_page;

		$indices = getCabinetInfo ($db_object, $cab);
		$allFolders = getTableInfo ($db_object, array ($cab, $temp_table), array
				(implode (",", $indices), 'result_id'), 
				array ("$cab.doc_id = $temp_table.result_id", 'deleted = 0'),
				'queryAll', array ('result_id' => 'DESC'), $start, $per_page);
		if (count ($allFolders) == 1) {
			$result = $allFolders[0];
			$doc_id = $result['result_id'];
			unset ($result['result_id']);
			if (isset ($result['rownumber'])) {
				unset ($result['rownumber']);
			}
			$folderName = implode (' - ',$result);
		} elseif ($doc_id) {
			$result = getTableInfo ($db_object, array ($cab), array(implode(',',
							$indices)), array ('doc_id' => (int) $doc_id), 'queryRow');
			$folderName = implode (' - ', $result);
		}
		$URL = "inboxSelect1.php?cab=$cab&search=".urlencode($folderValue).
			"&odbcSearch=".urlencode($odbcValue);
		$encurl = h($URL);
		$encFolderValue = urlencode($folderValue);
		$encOdbcValue = urlencode($odbcValue);
	} else {
		$URL = "";
	}	
	echo<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Inbox Selector</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css">
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/behaviour.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
var pageNum = $page;
var URL = '$URL';
var cabinet = '$cab';
var tempTable = '$temp_table';
var docID = '$doc_id';
var folderValue = '$encFolderValue';
var odbcValue = '$encOdbcValue';
</script>
HTML;
	echo <<<HTML
<script type="text/javascript" src="inboxSelect1.js"></script>
<style type="text/css">
html {
	border-style: none;
}
body {
	border-style: none;
}
img {
	cursor: pointer;
	border: 0;
}
td.selectHeader {
	font-weight: bold;
	font-size: 11pt;
}
div.spacerDiv {
	height: 2em;
}
div.noRes {
	font-weight: bold;
	font-size: 9pt;
}
</style>
</head>
<body class="tealbg" style="margin:0px">
	<div style="text-align:right">
		<img src="../images/left.GIF" 
			id="minView"
			style="cursor:pointer;vertical-align:middle" 
			title="Minimize" 
			alt="Minimize"
			onclick="parent.mainFrame.toggleInboxView(1)"
		/>
	</div>
HTML;
	if (sizeof ($cabinets) > 0) {
		echo<<<HTML
<form name="getDepartment" method="post" action="inboxSelect1.php" style="padding-left:5px;padding-right:5px;margin:0px">
<table class="lnk">
<tr>
<td class="selectHeader">$cabLabel</td>
</tr>
<tr>
<td>
<select id="cabSel" name="cab">
HTML;
		if ($cab) {
			echo<<<HTML
<option selected value="$cab">{$user->cabArr[$cab]}</option>
HTML;
		} else {
			echo<<<HTML
<option selected value="__default">Choose a Cabinet</option>
HTML;
		}
		foreach ($user->cabArr as $realCabinet => $arbCabinet) {
			if ($user->getRWorRO ($user->access[$realCabinet], 2) and $cab !=
					$realCabinet) {
				echo<<<HTML
<option value="$realCabinet">$arbCabinet</option>
HTML;
			}
		}
		echo<<<HTML
</select>
</td>
</tr>
</table>
</form>
HTML;

		if ($cab) {
			if ($user->checkSecurity ($cab) == 2) {
				echo<<<HTML
<div id="spacerDiv">&nbsp;</div>
<form name="searchFolder" method="post" action="inboxSelect1.php?cab=$cab" style="padding-left:5px;padding-right:5px;margin:0px">
<table>
HTML;
				if ($settings->get ('indexing_'.$cab)) {
					echo<<<HTML
<tr>
<td class="selectHeader">ODBC Search:</td>
</tr>
<tr>
<td>
<input id="odbcSearch" type="text" name="odbcSearch" value="" size="20">
</td>
</tr>
<tr>
<td>
<input type="button" name="odbcSubmit" id="odbcSubmit" value="Search ODBC">
</td>
</tr>
HTML;
				}
				echo<<<HTML
<tr>
<td class="selectHeader">$folders:</td>
</tr>
<tr>
<td>
<input type="text" name="folderSearch" value="$folderValue" id="folderSearch" size="20">
</td>
</tr>
<tr>
<td>
<input type="button" id="folderSubmit" name="folderSubmit" value="Search Folders">
</td>
</tr>
<tr>
<td>
<input type="button" value="Add Folder" name="add" id="btnAdd">
<input type="hidden" id="searchType" name="searchType">
</td>
</tr>
</table>
</form>
HTML;
				if ($totalPages > 1) {
					echo<<<HTML
<form method="post" action="$encurl" style="padding-left:5px;padding-right:5px;margin:0px">
<table>
<tr>
<td>
<img id="btnBack" src="../energie/images/back_button.gif" alt="Previous Results">
</td>
<td>
<input type="text" name="idpage" value="$tempPage" size="2">
</td>
<td>of $totalPages</td>
<td>
<img id="btnNext" src="../energie/images/next_button.gif" alt="Next Results">
</td>
</tr>
</table>
</form>
HTML;
				}
				if($noResults) {
					echo<<<HTML
<div class="noRes">No results found, showing all folders.</div>
HTML;
				}
		
				if ($total > 0) {
					echo<<<HTML
<form name="getFolder" method="post" action="$encurl" style="padding-left:5px;padding-right:5px;margin:0px">
<table>
<tr>
<td>
<select id="folderID" name="folderID" size="10">
HTML;
					//displays selected folder first
					$i = 0;
					if ($doc_id) {
						echo<<<HTML
<option selected value="$doc_id">
HTML;
echo h($folderName);
echo<<<HTML
</option>
HTML;
						$i++;
					}
					//displays the rest of the folders
					foreach ($allFolders as $result) {
						$tempID = $result['result_id'];
						if ($tempID != $doc_id) {
							unset ($result['result_id']);
							if (isset ($result['rownumber'])) {
								unset ($result['rownumber']);
							}
							$folderName = implode (' - ', $result);
							echo<<<HTML
<option value="$tempID">
HTML;
echo h($folderName);
echo<<<HTML
</option>
HTML;
						}
						$i++;	
					}
					echo<<<HTML
</select>
</td>
</tr>
HTML;

					//retrieve tabs if folder is selected
					if($doc_id) {
						echo<<<HTML
<tr>
<td>&nbsp</td>
</tr>
HTML;
			if($settings->get('inboxWorkflow') || $userStt->get('inboxWorkflow')) {
echo<<<HTML
<tr>
	<td class="selectHeader">Workflow:</td>
</tr>
<tr>
<td>
<select name="workflowSelect" id="workflowSelect">
<option value="__default">None</option>
HTML;
	$sArr = array('DISTINCT(defs_name)');
	$oArr = array('defs_name'=>'ASC');
	$wfList = getTableInfo($db_object,'wf_defs',$sArr,array(),'queryCol',$oArr);
	foreach($wfList AS $wf) {
		$wf = h($wf);
echo<<<HTML
<option value="$wf">$wf</option>
HTML;
	}
echo<<<HTML
</select>
</td>
</tr>

<tr>
<td>&nbsp</td>
</tr>
HTML;
		}
						$disabledFile = '';
						if($user->checkSetting('documentView', $cab)) {
							echo<<<HTML
<tr>
<td class="selectHeader">Document:</td>
</tr>
<tr>
<td>
HTML;
							$enArr = array(
								'cabinet' => $cab,
								'doc_id' => $doc_id,
								'filter' => 'All'
							);
							$docuArr = getFolderDocuments($enArr, $user, $db_doc, $db_object);
							$disabledFile = '';
							if(count($docuArr) == 0) {
								$disabledFile = 'disabled';
								echo '<span id="tabSelect">No Documents Found.</span>';
							} else {
								echo '<select name="tabSelect" id="tabSelect">';
								foreach($docuArr as $myDoc) {
									$realName = $myDoc['subfolder_name'];
									$dispName = $myDoc['name'] . ': ' .
										implode(' - ',
											array_values($myDoc['documents']));
									echo "<option value=\"$realName\">".h($dispName)."</option>";
								}
								echo '</select>';
							}
							echo<<<HTML
</td>
</tr>
HTML;
						} else {
							echo<<<HTML
<tr>
<td class="selectHeader">$Tab:</td>
</tr>
<td>
<select name="tabSelect" id="tabSelect">
<option value="Main">Main</option>
HTML;
							$whereArr = array (
								"doc_id"	=> (int) $doc_id,
								"filename"	=> 'IS NULL',
								"display"	=> 1,
								"deleted"	=> 0
							);
							$tabInfo = getTableInfo ($db_object, $cab.'_files',
									array (), $whereArr);
							$myTabs = array ();
							while ($row = $tabInfo->fetchRow ()) {
								$myTabs[$row['subfolder']] = str_replace('_', ' ',
										$row['subfolder']);
							}
							$notShowTab = getNoShowTabs ($cab, $doc_id, $user->db_name);

							foreach ($myTabs as $realName => $dispName) {
								if (!in_array($realName, $notShowTab)) {
									echo<<<HTML
<option value="$realName">$dispName</option>
HTML;
								}
							}
							echo<<<HTML
</select>
</td>
</tr>
HTML;
						}
						echo <<<HTML
<tr>
<td>
<input type="button" value="$File" $disabledFile name="btnSelect" id="btnSelect">
HTML;
						if($user->checkSetting('documentView', $cab)) {
							echo '&nbsp;<select id="AddDocumentSel">';
							echo '<option value="__default">Add Document</option>';
							foreach(getDocumentTypes(array('cab' => $cab), $user,
								$db_doc, $db_object) as $realName => $arbName) {

								echo '<option value="'.$realName.'">'.$arbName.'</option>';
							}
							echo '</select>';
						}
						echo <<<HTML
</td>
</tr>
HTML;
					}
				} else {
					echo<<<HTML
<div class="noRes">No Folders In Cabinet</div>
HTML;
				}
				echo<<<HTML
</table>
</form>
HTML;
			} else {//check for read/write access failed..log user out
				logUserOut ();
			}	
		}
	}
echo<<<HTML
</body>
</html>
HTML;
	setSessionUser($user);
} else {//check Login
	logUserOut ();
}
?>
