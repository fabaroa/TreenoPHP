<?php 
// $Id: searchResults.php 14870 2012-07-09 17:46:26Z cz $
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/constants.inc';
include_once '../lib/energie.php';
include_once '../lib/quota.php';
include_once '../lib/odbc.php';
include_once '../modules/modules.php';
include_once '../search/search.php';
include_once '../search/searchResultsExtras.php';
include_once '../settings/settings.php';
include_once '../groups/groups.php';
include_once '../lib/sagWS.php';
include_once '../lib/licenseFuncs.php';

if($logged_in and $user->username) {
	$topTerms = '';
	$glbSettings = new GblStt($user->db_name, $db_doc);
	$dateFuncs = $glbSettings->get('date_functions');
	if (!$dateFuncs) {
		$dateFuncs = 'false';
	}
	if(isset($_GET['topTerms']) and $_GET['topTerms'] == 1){
		$tlsArray = $_SESSION['tlsArray'];
		$topTerms = $tlsArray['topTerms'];
		$exactSearch = $tlsArray['exact'];
	}

	if(!isSet($_GET['link'])) {
		$user->todoID = 0;
	}
	$userSettings = new Usrsettings($user->username, $user->db_name);
	$allBookmarks = unserialize(base64_decode($userSettings->get('bookmarks')));
	if(isset($_GET['bookmark']) and $_GET['bookmark']) {
		$currentBookmark = $_GET['bookmark'];
		if(isset($allBookmarks[$currentBookmark]['fields'])) {
			$searchArray = $allBookmarks[$currentBookmark]['fields'];
			$cab = $allBookmarks[$currentBookmark]['cabinet'];
		} else {
			$topTerms = $allBookmarks[$currentBookmark]['topLevel'];
		}
	} elseif(isset($_GET['cab']) and $_GET['cab']) { 
		$cab = $_GET['cab'];
	} elseif(isset($_SESSION['cab']) and $_SESSION['cab']) {
		$cab = $_SESSION['cab'];
	}

	if(isset($_GET['cab']) and !isset($_GET['table'])) {
		$_SESSION['searchResArray'] = array();
		unset ($_SESSION['tlsArray']);
	}
	$acTable = $glbSettings->get('indexing_'.$cab);
	if ($user->checkSetting('editFolder', $cab)) {
		$editFolder = 'true';
	} else {
		$editFolder = 'false';
	}

	$disableAdvSearchArr = array();
    if(!$user->checkSetting('advSearchSubfolder',$cab)) {
        $disableAdvSearchArr[] = 'advSearchSubfolder';
    }
    if(!$user->checkSetting('advSearchFilename',$cab)) {
        $disableAdvSearchArr[] = 'advSearchFilename';
    }
    if(!$user->checkSetting('advSearchContextSearch',$cab)) {
        $disableAdvSearchArr[] = 'advSearchContextSearch';
    }
    if(!$user->checkSetting('advSearchDateCreated',$cab)) {
        $disableAdvSearchArr[] = 'advSearchDateCreated';
    }
    if(!$user->checkSetting('advSearchWhoIndexed',$cab)) {
        $disableAdvSearchArr[] = 'advSearchWhoIndexed';
    }
    if(!$user->checkSetting('advSearchNotes',$cab)) {
        $disableAdvSearchArr[] = 'advSearchNotes';
    }
    $disableAdvSearchStr = implode(",",$disableAdvSearchArr);

	if($user->checkSetting('documentView', $cab) || (isset($_GET['documentView']) and $_GET['documentView'] == 1)) {
		$documentView = true;
	} else {
		$documentView = false;
	}

//ALS: Global Search and Replace is no longer settable - always DISABLED
//	if($user->checkSetting('globalEditFolder', $cab)) {
//		$globalEdit = true;
//	} else {
		$globalEdit = false;
//	}

	if(isset($_GET['table'])) 
		$tempTable = $_GET['table'];

	$index = 0;
	if(isset($_POST['indexID'])) 
		$index = $_POST['indexID'] - 1;
	elseif(isset($_GET['index']))
		$index = $_GET['index'];//index of the current page

	$userType = $user->checkSecurity($cab);
	$delDoc = ($user->checkSetting('deleteDocuments',$cab)) ? 1 : 0;

	$publishFolder = 0;
	$publishDocument = 0;
	if(check_enable("publishing",$user->db_name) && isValidLicense($db_doc)) {
	   	if($user->checkSetting('publishFolder',$cab)) {
			$publishFolder = 1;
		}

	   	if($user->checkSetting('publishDocument',$cab)) {
			$publishDocument = 1;
		}
	}

	$cabinetIndices = getCabinetInfo($db_object, $cab);
	$numberOfIndices = count($cabinetIndices);

	$topTerms = h($topTerms);
	$delFolders = $user->checkSetting('deleteFolders', $cab) ? 1 : 0;
	echo <<<ENERGIE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Search Results</title>
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script type="text/javascript" src="../lib/windowTitle.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/barcode.js"></script>
<script type="text/javascript" src="../lib/calendar.js"></script>
<script type="text/javascript" src="../search/searchResults.js"></script>
<script type="text/javascript" src="../documents/documentsWizard.js"></script>
<script src="../documents/scriptaculous/lib/prototype.js" type="text/javascript"></script>
<script src="../documents/scriptaculous/src/scriptaculous.js" type="text/javascript"></script>
<script src="../lib/behaviour.js" type="text/javascript"></script>
<script src="../lib/searchResSync.js" type="text/javascript"></script>
<script>
	myrules = {
		'div.documents' : function(el) {
								
						}
	};
</script>
<script type="text/javascript">
  var indCt = $numberOfIndices;
  var userType = "{$userType}";
  var documentView = ("{$documentView}") ? true : false;
  var publishFolder = ({$publishFolder}) ? true : false;
  var publishDocument = ({$publishDocument}) ? true : false;
  var globalEdit = ("{$globalEdit}") ? true : false;
  var fid = ("{$user->file_id}") ? "{$user->file_id}" : 0; 
  var delDoc = $delDoc;
  var delFold = $delFolders;
  var topLevel = "$topTerms";
  var documentOpened = "";
  var editDocumentBool = false;
  var subfolderID = "";
  var prevSelectedFileID = "";
  var prevDocumentType = "";
  var prevSelectedIndices = "";
  var cabinet = "$cab";
  var folderID = ""; 
  var tempTable = "";
  var index = 0;
  var prevSelected = "";
  var selectedRow = "";
  var dateFunctions = $dateFuncs;
  var acTable = '$acTable';
  var needInsertAC = false;
  var canEditFolder = $editFolder;
  var advSearchStr = "$disableAdvSearchStr";
  var advSearchArr = advSearchStr.split(',');
  var boolBC = false;
function setSearchPanelCabinet(cab, fieldArr, secLevel, dataTypeDefs) {
	if(!parent.searchPanel || !parent.searchPanel.setCabinet) {
		setTimeout(function(){setSearchPanelCabinet(cab, fieldArr, secLevel, dataTypeDefs);}, 100);
	} else {
		parent.searchPanel.setCabinet(cab, fieldArr, secLevel, false, dataTypeDefs);
	}
	
	if(advSearchArr.length == 6) {
        parent.searchPanel.document.getElementById('advancedBtn').style.display = 'none';
    }

	for(var i=0;i<advSearchArr.length;i++) {
        if(advSearchArr[i]) {
            if(parent.searchPanel.document.getElementById(advSearchArr[i])) {
                parent.searchPanel.document.getElementById(advSearchArr[i]).style.display = 'none';
            }
        }
    }
}
</script>	

<link rel="stylesheet" type="text/css" href="../lib/style.css">
<link rel="stylesheet" type="text/css" href="../lib/calendar.css">
<link rel="stylesheet" type="text/css" href="../lib/searchResSync.css">
<script type="text/javascript">
function resizeTable() {
	if($('folderResults')) {
		var mainTable = $('folderResults');
		var tableColumnLengths = new Array (mainTable.rows[0].cells.length);
		for(var i = 0; i < tableColumnLengths.length; i++) {
			tableColumnLengths[i] = 0;
		}
		var spans;
		var ctStart = 0;
		var totalColumns = mainTable.rows[0].cells.length;
		var actionCol = totalColumns - indCt;
		for(var i = 0; i < mainTable.rows.length; i++) {
			for(var j = 0; j < mainTable.rows[i].cells.length; j++) {
				spans = mainTable.rows[i].cells[j].getElementsByTagName('span');
				if(spans.length == 1) {
					if(ctStart == 0) {
						ctStart = j;
					}
					if(spans[0].offsetWidth > tableColumnLengths[j]) {
						var myWidth = spans[0].offsetWidth + 5;
						//if(myWidth < 150) {
						//	myWidth = 150;
						//}
						tableColumnLengths[j] = myWidth;
					}
				} else {
					tableColumnLengths[j] = 38;
				}
			}
		}
		var tableWidth = 0;
		for(var i = 0; i < tableColumnLengths.length; i++) {
			tableWidth += tableColumnLengths[i];
		}
		
		if(tableWidth < document.body.offsetWidth) {
			var diff = document.body.offsetWidth - tableWidth;
			var extra = diff / (totalColumns - actionCol);
			for(i=actionCol;i<totalColumns;i++) {
				tableColumnLengths[i] += extra;	
			}	

			tableWidth = document.body.offsetWidth;
		}

		var total = 0;
		for(var i = 0; i < tableColumnLengths.length; i++) {
			total += tableColumnLengths[i];
			mainTable.rows[0].cells[i].style.width = tableColumnLengths[i] + 'px';
		}
		
		mainTable.style.width = tableWidth + 'px';
		mainTable.style.visibility = 'visible';
	}
}
</script>
<style>
	.docType {
	}
</style>
</head>
<body onload="resizeTable()">
ENERGIE;
	$getArr = array();
	$search = new search();
	
	$_SESSION['cab'] = $cab;
	$security_level = $user->checkSecurity( $cab );

	$getArr['cab'] = $cab;
	$passedTerms = "";
	if($topTerms) {
		$passedTerms = 1;
		$getArr['topTerms'] = $passedTerms;
	}
	else {
		$topTerms = '';
	}
	$fieldsArr = array();
	foreach($cabinetIndices as $fields) {
		$fieldsArr[] = "'$fields'";
	}
	
	$DepID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');
	$whereArr = array("department='$user->db_name'","k LIKE 'dt,$user->db_name,$DepID,%'");
	echo "<script type=\"text/javascript\">\n";
	$dataTypeInfo = getTableInfo($db_doc,'settings',array('id','k','value'),$whereArr);
    echo "var dataType = new Array();\n";
	foreach($fieldsArr AS $field) {
		echo "dataType[$field] = new Array();\n";
	}

    while( $result = $dataTypeInfo->fetchRow() ) {
		$key = str_replace( "dt,$user->db_name,$DepID,", "", $result['k'] );
		if(in_array($key,$cabinetIndices)) {
			$value = $result['value'];
			$valArr = explode(",,,",$value);
			for($i=0;$i<sizeof($valArr);$i++) {
				echo  "dataType['$key'][$i] = '".addslashes($valArr[$i])."';\n";
			}
		} else {
			deleteTableInfo($db_doc,'settings',array('id' => (int)$result['id']));		
		}
	}
	$dataTypeInfo->free ();
	echo "  var fArr = new Array(".implode(',', $fieldsArr).");";
	echo "  setSearchPanelCabinet('$cab', fArr, $security_level, dataType);\n";
	echo "</script>\n";
	//This is for barcoding with SAGITTA. This gets set on a redirect from
	//energie/energie.php. The key 'barcode' contains the search string,
	//and it places it in the $searchArray array to fool the search results. The
	//search results requires an equal number of $searchArray keys as there are
	//index fields in a cabinet. The only other $_GET key that gets set is
	//'cab', which supplies the cabinet that is searched in. The search only
	//will search in the FIRST index (leftmost).
	if(isset($_GET['barcode'])) {
			$getArr['barcode'] = urlencode($_GET['barcode']);
		$searchArray[$cabinetIndices[0]] = $_GET['barcode'];
    }
	$adminBackup = $glbSettings->get('adminBackup');	
	$userBackup = $glbSettings->get('userBackup');
	if(($user->isAdmin() and $adminBackup) or $user->isDepAdmin())
		$cd_permission = 1;
	elseif($userBackup)
		$cd_permission = 1;
	else
		$cd_permission = 0;

	if( isSet($_GET['sortType']) && isSet($_GET['sortDir']) ) {
		$sortType = str_replace(' ', '_', $_GET['sortType']);
		$sortDir = $_GET['sortDir'];//fieldname the user wants to sort
		$_SESSION['sortType'] = $sortType;
		$_SESSION['sortDir'] = $sortDir;
		$_SESSION['sortCab'] = $cab;
	} elseif( isset($_SESSION['sortCab']) and $_SESSION['sortCab'] == $cab ) {
		$sortType = str_replace(' ', '_', $_SESSION['sortType']);
		$sortDir = $_SESSION['sortDir'];
	} else {
		$sortType = '';
		$sortDir = '';
	}

	$sort = array();
	foreach($cabinetIndices as $indices) {
		$sort[$indices] = "ASC";
	}

	if($sortDir == "DESC")
		$sort[$sortType] = "ASC";
	else
		$sort[$sortType] = "DESC";
	//temp table stores all the results queried

	$getArr['index'] = $index;
	if (isset($_GET['allthumbs'])) {
		$allThumbsOpen = $_GET['allthumbs'];
	} else {
		$allThumbsOpen = 0; 
	}
	if (isset($_GET['mess'])) {
		$mess = $_GET['mess'];//message for the user
	} else {
		$mess = '';
	}

	////////////////////////////////////////////////////////////////////////
	/*
	  This section creates a new user setting.  Then it checks to see if a 
	new selection has been made for the viewing of the results.  If a new
	selection was made it sets the object which stores the information in 
	the database.  Then it will retrieve what the user has selected to display
	that amount of results per page.  Default value is 25 results per page
	*/
	if(isset($_GET['res']))
		$userSettings->set('results_per_page', $_GET['res']);
	$resArray = $userSettings->get('results_per_page');

	if(!$resArray)
		$resultsPerPage = 25;
	else
		$resultsPerPage = $resArray; 

	if(!$topTerms) {
		if(!isset($searchArray)) {
			$searchArray = array();
			if( isSet( $_GET['link'] ) ) {
				$searchArray['doc_id'] = $_GET['doc_id'];
			} elseif( !empty( $user->doc_id ) || isset($_GET['doc_id'])) {
				if(isset($_GET['doc_id'])) {
					$user->doc_id = $_GET['doc_id'];
				}
				$searchArray['doc_id'] = $user->doc_id;
			} else {
				$justSearched = false;
				foreach($cabinetIndices as $indices) {
					$bigIndex = strtoupper($indices);
					if(isset($_POST[$bigIndex])) {
						$justSearched = true;
						if($_POST[$bigIndex]) {
							$searchArray[$indices] = $_POST[$bigIndex];
						}
					}
					if(isset($_POST[$bigIndex.'-dRng'])) {
						$justSearched = true;
						$searchArray[$indices.'-dRng'] = $_POST[$bigIndex.'-dRng'];
					}
				}
				if($justSearched) {
					$_SESSION['searchResArray'] = $searchArray;
				} else {
					if(isSet($_SESSION['searchResArray'])) {
						$searchArray = $_SESSION['searchResArray'];
					}
				}
			}
		}
		if(isset($_GET['legint'])) {
			//$searchArray = $_SESSION['integrationSearch'];	
			$tmpSearchArr = $_SESSION['integrationSearch'];
			unset($_SESSION['integrationSearch']);
			
			//error_log('Cab fields of ['.$cab.']: '.implode($cabinetIndices, ","));
			//error_log('IntegrationSearch: '.print_r($tmpSearchArr, true));
			foreach($cabinetIndices as $cabfield)
			{
				if (array_key_exists($cabfield, $tmpSearchArr) && isset($tmpSearchArr[$cabfield]))
				{
					$tmpVal = $tmpSearchArr[$cabfield];
					if($tmpVal !='""')//&& $tmpVal != '')
					{
						$searchArray[$cabfield] = $tmpVal;	
					}
				}	
			}
			//error_log('SearchResults.php - searchArray: '.print_r($searchArray, true));
			//$searchArray = $tmpSearchArr;			
		}
	} else {
		$searchArray = array();
		if( !empty( $user->doc_id ) || isset($_GET['doc_id'])) {
			if(isset($_GET['doc_id'])) {
				$user->doc_id = $_GET['doc_id'];
			}
			$searchArray['doc_id'] = $user->doc_id;
		} else {
			foreach($cabinetIndices AS $myIndex) {
				$searchArray[$myIndex] = $topTerms;

			}
		}
	}

	if(!isset($tempTable) ) { 
		$tempTable = $search->getSearch($cab, $searchArray, $db_object );
		$user->audit('cabinet search',$search->auditStr);
	} elseif( !empty($_GET['sortDir']) && !empty($_GET['sortType']) ) {
		//$tempTable = $search->searchTempTable($db_object,$cab,$tempTable,$sortType,$sortDir);
		//$user->audit('cabinet search',$search->auditStr);
	} else {
		$tableArr = $db_object->manager->listTables();
		if(getDbType() == "db2") {
			$tmp_table = strtoupper($tempTable);
		} else {
			$tmp_table = $tempTable;
		}
		if(!in_array($tmp_table,$tableArr)) {
			$tempTable = $search->getSearch($cab, $_SESSION['searchResArray'], $db_object );
		}
	}
	$getArr['table'] = $tempTable;

	if($searchArray && !$topTerms) {
		$_SESSION['searchResArray'] = $searchArray;
		echo '<script type="text/javascript">';
		echo 'var mySearchArr = new Object ();';
		foreach ($searchArray as $key => $value) {
			echo "mySearchArr['$key'] = '" . addslashes($value) . "';";
		}
		echo 'parent.searchPanel.loadSearch (mySearchArr);';
		echo '</script>';
	}

	$indiceStr = implode(",",$cabinetIndices);
	echo<<<ENERGIE
<script type="text/javascript">
  tempTable = '$tempTable';
  setIndices('$indiceStr');
</script>
ENERGIE;

	if ( $security_level != 0) {
		if(!$allThumbsOpen) {
			echo <<<ENERGIE
<script type="text/javascript">
setTitle(2, "{$user->cabArr[$cab]}");
if(parent.topMenuFrame.document.getElementById("up")) {
	parent.topMenuFrame.removeBackButton();
}

parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
parent.viewFileActions.window.location = '../energie/bottom_white.php';
</script>
ENERGIE;
		}
	//check for Burn to CD here
	if (isset($_GET['burn'])) {
		//This function is located in lib/utility.php
		$DepID = getTableInfo($db_object, 'departments',
		array('departmentid'), array('real_name' => $cab, 'deleted' => 0), 'queryOne');
		createISO($user,$DepID,$tempTable,0);
	}

	$sArr = array('index1','search');
	$wArr = array('username' => $user->username,
				'cabinet' => $cab);
	$oArr = array('index1' => 'ASC');
	$gArr = array('index1','search');
	$filterList = getTableInfo($db_object,'cabinet_filters',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr,true);
	if(count($filterList)) {
		$sArr = array();
		foreach($filterList AS $filter => $sList) {
			foreach($sList AS $s) {
				if(!isset($sArr[$filter]))$sArr[$filter]="";
				$sArr[$filter] = trim($sArr[$filter]).' "'.$s.'"';
			}
		}
		$tempTable = $search->getSearch($cab, $sArr, $db_object );
	}

	$entries = getTableInfo($db_object, $tempTable, array('COUNT(*)'), array(), 'queryOne');
	if($entries <= 0 && count($searchArray)) {
		//The search did not find anything
		$extTable = $glbSettings->get('indexing_'.$cab);
		if(check_enable('searchResODBC', $user->db_name) and !isset($_GET['legint']) and
			!isset($_GET['noODBC']) and $extTable) { 
			if($glbSettings->get('intSync')) {
				$sf = "";
				if($cab != $glbSettings->get('sync_cabinet')) {
					$sf = $glbSettings->get('sync_field');
				}
?>
				<div style="width:100%;height:100%">
					<div class="syncDivContainer">
						<div style="padding-top:10px">
							<span>Folder Does Not Exist</span>
						</div>
						<div style="padding-top:10px;width:100%;margin-right:auto;margin-left:auto">
							<input type="button" name="btn1" value="Create" onclick="createSearchResFolder()" />
							<input type="button" name="btn2" value="Skip" onclick="reloadSearchRes('<?php echo $cab; ?>')" />
						</div>
						<?php if ($user->checkSetting ('intSync', $cab)): ?>
							<div>
								<div style="padding-top:10px;width:100%;margin-right:auto;margin-left:auto">
								<?php if($cab == $glbSettings->get('sync_cabinet')): ?>
									<input type="text" id="syncFolder" name="sync" value="" onkeypress="syncOnEnter(event,'<?php echo $sf; ?>')" />
								<?php endif; ?>
									<input type="button" name="btn3" value="Sync" onclick="getUnSyncedFolders('<?php echo $sf; ?>')" />
								</div>
							</div>
							<div id="syncAllDiv">
								<div style="position:relative;width:3%;text-align:center">
									<input type="checkbox" id="syncAllBox" name="chkAll" value="" onclick="toggleSearchResults()" checked />
								</div>
							</div>
							<div id="syncDiv"></div>
							<div id="syncBtn">
								<input type="button" name="save" value="Save" />
							</div>
						<?php endif ?>
					</div>
				</div>
		<script>
			if($('syncFolder')) {
				$('syncFolder').focus();
			}
		</script>
<?php
			die();
			} else {

				$hasACResult = 0;

				$transInfo = getTableInfo($db_object, 'odbc_auto_complete',
					array(), array('cabinet_name' => $cab), 'queryRow');
				$findInExternal = true;
				if (substr_count ($transInfo['lookup_field'], ',') > 0) {
					$odbcFields = explode (',', $transInfo['lookup_field']);
					$foundFields = false;
					foreach ($odbcFields as $myField) {
						if (isset ($searchArray[$myField])) {
							$foundFields = true;
							break;
						}
					}
					if ($foundFields) {
						$findInExternal = true;
						$searchVal = array ();
						foreach ($odbcFields as $myField) {
							if (isset ($searchArray[$myField])) {
								$myVal = $searchArray[$myField];
								if(strpos($myVal, '"') === 0) {
									$myVal = substr($myVal, 1, strlen($myVal) - 2);
								}
								$searchVal[$myField] = $myVal;
							}
						}
					} else {
						$findInExternal = false;
					}
				} elseif (isset ($searchArray[$cabinetIndices[0]]) and ($transInfo or $extTable == 'sagitta_ws_auto_complete')) {
					for($i = 1; $i < sizeof($cabinetIndices); $i++) {
						if(isset($searchArray[$cabinetIndices[$i]])) {
							$findInExternal = false;
						}
					}
					if ($findInExternal) {
						$searchVal = $searchArray[$cabinetIndices[0]];
						if(strpos($searchVal, '"') === 0) {
							$searchVal = substr($searchVal, 1, strlen($searchVal) - 2);
						}
					}
				} else {
					$findInExternal = false;
				}
				if (!$searchArray) {
					$findInExternal = false;
				}
				if($findInExternal) {
					$searchedODBC = true;
					echo "<div>Checking In External Database...</div>\n";
					ob_flush();
					flush();
					if($extTable == 'sagitta_ws_auto_complete') {
						$row = getSagRow($cab, $searchVal, $user->db_name);
						if (!$row) {
							$row = array ($cabinetIndices[0] => $searchVal);
						}
					} else {
						$db_odbc = getOdbcDbObject($transInfo['connect_id'], $db_doc);
						if (!is_object($db_odbc)) {
							error_log("db_odbc is not an object for connect_id ".$transInfo['connect_id']);;
						}
						
						if(PEAR::isError($db_odbc)) {
							echo "Error connecting to ODBC Database!";
							echo <<<ENERGIE
	<script type="text/javascript">
	setTimeout(function() { window.location.href = "searchResults.php?cab=$cab&table=$tempTable&noODBC=15";}, 1500);
	</script>
ENERGIE;
							die();
						}
						$row = getODBCRow($db_odbc, $searchVal, $cab, $db_object, '', $user->db_name,$glbSettings);
						if( $transInfo['connect_id']!=0 ){
							$db_odbc->disconnect();
						}
					}
					if($row) {
						$hasACResult = 1;
						if($user->checkSecurity($cab) == 2) {
							createFolderInCabinet (
								$db_object,
								$glbSettings,
								$db_doc,
								$user->username,
								$user->db_name,
								$cab,
								array_values($row),
								array_keys($row),
								$tempTable
							);
						}
					}
					echo <<<ENERGIE
	<script type="text/javascript">
	window.location.href = "searchResults.php?cab=$cab&table=$tempTable&noODBC=1&hasACResult=$hasACResult";
	</script>
ENERGIE;
					die();
				}
			}
		} elseif(isset($_GET['legint'])) {
			foreach($cabinetIndices as $myIndex) {
				if(!array_key_exists($myIndex, $searchArray)) {
					$searchArray[$myIndex] = '';
				}
			}
			if($user->checkSecurity($cab) == 2) {
				createFolderInCabinet (
					$db_object,
					$glbSettings,
					$db_doc,
					$user->username,
					$user->db_name,
					$cab,
					array_values($searchArray),
					array_keys($searchArray),
					$tempTable
				);
			}
			echo <<<ENERGIE
<script type="text/javascript">
window.location.href = "searchResults.php?cab=$cab&table=$tempTable&hasACResult=1";
</script>
ENERGIE;
		}
	}
	$pageCount = floor($entries / $resultsPerPage);
	//make sure to add a page if there isn't an even division of records per page
	if(($entries % $resultsPerPage == 0) and ($entries > $resultsPerPage)) {
		$pageCount--;
	}
	if($entries == $resultsPerPage) {
		$pageCount--;
	}

	if ($entries < 0) {
		$entries = 0;
	}

	if($index > $pageCount) {
		$index = $pageCount;
	} elseif($index < 0) {
		$index = 0;
	}

	$pageOfResults = getIndex($db_object, $index, $cab, $entries, 
	$tempTable, $user, $sortType, $sortDir, $getArr, $resultsPerPage );

	$barcode = 1; 
	createArrows($index, $pageCount, $getArr, 'top');
	if(isValidLicense($db_doc)) {
		printButtons( $user, $glbSettings, $userSettings, $security_level, $cd_permission, $entries, $getArr );
	}
	echo "<div class=\"sortmsg\" style=\"width: 33%; ";
	echo "margin-left: auto; margin-right: auto; position: relative;top:-20px; overflow:visible; ";
	echo "text-align: center;white-space:nowrap\" id=\"sortmsg\">&nbsp;</div>\n";

	echo "<div style=\"position: relative;top:-20px; text-align: center\">\n";
	echo "<div style=\"margin-left: auto; margin-right: auto\">\n";
	echo "<table id=\"folderResults\" border=\"0\" cellspacing =\"1\" ";
	echo "cellpadding = \"0\" class=\"results\" style=\"text-align: left; visibility: hidden\">\n";

	printCabinetIndices( $numberOfIndices, $cabinetIndices,$cab,
	$user,$sort,$getArr, $delFolders, $security_level);
	// Prints parent folder row in table
	printParentFolder($numberOfIndices, $passedTerms, $cab, $user, 
	$delFolders, $security_level );
	//Prints create folder row in table, uses get value 
	if ( $security_level == 2 && $user->checkSetting('editFolder', $cab) && isValidLicense($db_doc)) {		
		printCreateFolder (
			$cab,
			$cabinetIndices,
			$user,
			$tempTable, 
			$delFolders,
			$security_level,
			$passedTerms,
			$index
		);
	}

	if($user->restore != 1) {
		$user->file_id = "";
		$user->doc_id = "";
		$user->cab = "";
	}
	if ($entries > 0) {
		$tmpArr = $getArr;
		$tmpArr['trigger'] = 1;
		$getStr = 'searchResults.php'.formGetStr($tmpArr);
		while ($resultRow = $pageOfResults->fetchRow()) {
			$doc_id = $resultRow['doc_id'];
			$rowID = "$doc_id";
			echo "  <tr id=\"$rowID\"";
			echo "  onmouseover=\"rowMouseover('$rowID');";
			echo " \" onmouseout=\"rowMouseout('$rowID')\" style=\"background-color:#ebebeb\">\n";
			echo "   <td id=\"folder-$doc_id\"";
			echo " onclick=\"integrityCheck('$cab',$doc_id,'$index','$passedTerms');setSelected('$doc_id')\"";
			echo " align=\"center\">\n";
			echo "    <img alt=\"File\" src=\"images/File.gif\" width=\"14\" ";
			echo "border=\"0\">\n";
			echo "   </td>\n";
			if($publishFolder) {
?>
<td align="center" id="<?php echo "publishing-".$doc_id; ?>">
<img src="../images/new_16.gif" 
border="0"  
title="Add folder to publishing"
onclick="top.topMenuFrame.addItem('<?php echo $cab; ?>','<?php echo $doc_id; ?>');setSelected('<?php echo $doc_id; ?>')" />
</td>
<?php
			}
			if ( $security_level == 2 && $user->checkSetting('editFolder', $cab) && isValidLicense($db_doc)) {
				echo "   <td id=\"edit-$doc_id\" align=\"center\" ";
				echo "onclick=\"openEditIndices('$cab',$rowID,'$topTerms','$tempTable',";
				echo "'$index');setSelected('$rowID')\">\n";
				echo "    <img alt=\"Edit Indices\" src=\"images/file_edit_16.gif\" ";
				echo "width=\"14\" border=\"0\">\n";
				echo "   </td>\n";
			}
			//the trash icon; check whether file deletion is enabled
			if ($security_level == 2 && $delFolders && isValidLicense($db_doc)) {
				$bc = ($barcode == 1 && $user->checkSetting('showBarcode', $cab) && !$user->checkSetting('documentView', $cab)) ? 1: 0;
				echo "   <td id=\"delete-$doc_id\" align=\"center\">\n";
				echo "    <img ";

				echo "onclick=\"askAdmin('$doc_id','$cab','$tempTable',";
				echo "'$index','$passedTerms','$bc');\" ";

				echo "alt=\"Delete Folder\" src=\"images/trash.gif\" ";
				echo "border=\"0\" width=\"14\">\n";
				echo "   </td>\n";
			}
			if( $barcode == 1 and $user->checkSetting('showBarcode', $cab) && !$user->checkSetting('documentView', $cab) ) {
				echo "   <td id=\"barcode-$doc_id\" class=\"barcodeCell\" ";
				echo "onclick=\"boolBC=false;printDocutronBarcode('$cab', '$doc_id');";
				echo "setSelected('$rowID')\">\n";
				echo "    <img alt=\"Get Barcode\" src=\"../images/barcode.gif\">\n";
				echo "   </td>\n";
			}
			for ($x = 0; $x < $numberOfIndices; $x ++) {
				$var1 = $cabinetIndices[$x];
				$resStr = "";
				if(array_key_exists($var1,$resultRow)) {
					$resStr = $resultRow[$var1]; 
				}
				//prints row until no other indices
				echo "   <td id=\"$var1-$doc_id\" class=\"lnk_black\" noWrap=\"nowrap\" ";
				echo " onclick=\"integrityCheck('$cab',$doc_id,'$index','$passedTerms');";
				echo "setSelected('$rowID');\" style=\"padding-left:3px;padding-right:3px;font-size:12px\">";
				echo "<span>".h(stripslashes($resStr))."</span></td>\n";
			}
			echo "  </tr>\n";
		}
		$pageOfResults->free ();
	}
	echo " </table>\n";
	echo "</div>\n";
	// **** THIS IF FOR ONE ENTRY ONLY, SELECT IT AND OPEN FILE2 PANE *******
	if(($entries == 1 and !$allThumbsOpen) || isset($_GET['doc_id'])){
		echo "<script>\n";
		echo "integrityCheck('$cab',$doc_id,'$index','$passedTerms');";
		echo "  setSelected($doc_id);\n";
		echo "</script>\n" ;
	}

	createArrows($index, $pageCount, $getArr, 'bottom');
	echo "</div>\n";
	$pageOfResults->free();
	echo <<<ENERGIE
<script type="text/javascript">
if(parent.sideFrame.did) {
	setSelected(parent.sideFrame.did);
}
</script>
ENERGIE;
	} else {
		logUserOut();
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
