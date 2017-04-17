<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'documentDispFuncs.php';
include_once '../search/documentSearch.php';
include_once '../settings/settings.php';
include_once '../search/search.php';

if($logged_in and $user->username) {

	$userSettings = new Usrsettings($user->username, $user->db_name);
	$resPerPage = $userSettings->get('results_per_page');
	$resPerPage = ($resPerPage) ? $resPerPage : 25;
	$cabinet = null;

	if(isSet($_GET['page']) ) {
		$docType = $_SESSION['documentInfo']['docType'];
		$docObj = new documentSearch($db_object,$docType);
		$docObj->tempTable 	= $_SESSION['documentInfo']['tempTable'];
		$docObj->numResults = $_SESSION['documentInfo']['numResults'];
		$page = $_GET['page'];
	} else {
		$filterTable = NULL;
		if(isset($_GET['cabinet']) && isset($_POST['searchCabFirst'])) {
			$docType = $_GET['docType'];
			unset($_GET['docType']);

			$cabinet = $_GET['cabinet'];
			unset($_GET['cabinet']);
			
			$searchArr = array();
			foreach($_GET AS $k => $val) {
				$searchArr[$k] = $val; 
			}

			$filterTable = createTemporaryTable($db_object);	
			if(count($searchArr)) {
				$sArr = array('index1','search');
				$wArr = array('username' => $user->username,
							'cabinet' => $cabinet);
				$oArr = array('index1' => 'ASC');
				$gArr = array('index1','search');
				$filterList = getTableInfo($db_object,'cabinet_filters',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr,true);
				if(count($filterList)) {
					foreach($filterList AS $index => $sList) {
						foreach($sList AS $s) {
							$searchArr[$index] = trim($searchArr[$index]).' "'.$s.'"';
						}
					}
				}
				$searchObj = new search();
				$temp_table = $searchObj->getSearch($cabinet,$searchArr,$db_object);

				$dCol = array('result_id');
				$sArr = array('document_id');
				$tArr = array($temp_table,$cabinet.'_files');
				$wArr = array(	"result_id=doc_id",
						"document_table_name='".$docType."'");
				insertFromSelect($db_object,$filterTable,$dCol,$tArr,$sArr,$wArr);
			} else {
				$sArr = array('index1','search');
				$wArr = array('username' => $user->username,
							'cabinet' => $cabinet);
				$oArr = array('index1' => 'ASC');
				$gArr = array('index1','search');
				$filterList = getTableInfo($db_object,'cabinet_filters',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr,true);
				if(count($filterList)) {
					$searchArr = array();
					foreach($filterList AS $index => $sList) {
						foreach($sList AS $s) {
							$searchArr[$index] = trim($searchArr[$index]).' "'.$s.'"';
						}
					}
					$searchObj = new search();
					$temp_table = $searchObj->getSearch($cabinet,$searchArr,$db_object);
				}

				if(isSet($temp_table)) {
					$dCol = array('result_id');
					$sArr = array('document_id');
					$tArr = array($temp_table,$cabinet.'_files');
					$wArr = array(	"result_id=doc_id",
							"document_table_name='".$docType."'");
					insertFromSelect($db_object,$filterTable,$dCol,$tArr,$sArr,$wArr);
				} else {
					$dCol = array('result_id');
					$sArr = array('document_id');
					$tArr = array($cabinet,$cabinet.'_files');
					$wArr = array(	"$cabinet.deleted= 0",
									"$cabinet.doc_id=".$cabinet."_files.doc_id",
									"document_table_name = '$docType'");
					insertFromSelect($db_object,$filterTable,$dCol,$tArr,$sArr,$wArr);
				}
			}
		} else {
			$docType = $_GET['docType'];
		}

		$page = 1; 
		$docObj = new documentSearch($db_object,$docType,$filterTable,$user->username);
		$searchArray = array();
		foreach($docObj->fields AS $k => $info) {
			if(isSet($_POST[$k])) {
				$searchArray[$k] = $_POST[$k];
			}
		}

		if(!isSet($cabinet)) {
			$cabArr = array_merge(array_keys($user->access,'rw'),
					array_keys($user->access,'ro'));
		} else {
			$cabArr = array("'".$user->cabArr[$cabinet]."'");
		}
		$docObj->search($cabArr,$searchArray,$cabinet);
	} 

	$numCols = count($docObj->fields);
	$p = $resPerPage * ($page - 1);
	$perPage =  ($resPerPage + $p);

	$res = $docObj->getResults($p*$numCols,$perPage);
	$pages = ceil($docObj->numResults / $resPerPage);

	$docInfo = array(	'tempTable' 	=> $docObj->tempTable,
						'docType'		=> $docObj->docType,
						'numResults'	=> $docObj->numResults );
	$_SESSION['documentInfo'] = $docInfo;
	$ct = 1;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>Search Results</title>
		<link rel="stylesheet" type="text/css" href="documents.css">
		<script type="text/javascript" src="../lib/settings.js"></script>
		<script type="text/javascript" src="documents.js"></script>
		<script type="text/javascript" src="documentsWizard.js"></script>
		<script type="text/javascript" src="../lib/prototype.js"></script>
		<script type="text/javascript">
			var docType = '<?php echo $docType; ?>';
			var docName = '<?php echo $docObj->docName; ?>';
			var documentID = '';
			var numCols = '<?php echo $numCols; ?>';
			var docValArr = new Array();
			var boolSelect = true;
			var perPage = '<?php echo $resPerPage; ?>';
			var prevDoc = '';
			var curField = '';
			var dir = '';
			
			function selectAction(type) {
				var errMsg = getEl('errorMsg');
				clearDiv(errMsg);

				var ct = 1;
				var i = 1;
				var docIDArr = new Array();
				if(boolSelect) {
					while(el = getEl('documentCheck:'+ct)) {
						if(el.checked == true) {
							if(type == 'showRelDoc') {
								showRelatedDocuments(el);
								return;
							} else if(type == "editDoc") {
								openEditDocument(el.value);
								return;	
							}
							docIDArr[docIDArr.length] = el.value;
							i++;
						}
						ct++;
					}

					if(docIDArr.length > 0 && type == "deleteDoc") {
						deleteDocument(docIDArr);
					} else {
						errMsg.appendChild(document.createTextNode('No documents selected'));
					}
				} else {
					errMsg.appendChild(document.createTextNode('Please Save or Cancel Document'));
					getEl('field2-'+documentID).focus();
					getEl('field2-'+documentID).select();
				} 
			}

			function deleteDocument(documentArr) {
				var docTable = getEl('docTable');
			
				var xmlDoc = createDOMDoc();
				var root = xmlDoc.createElement('ROOT');
				xmlDoc.appendChild(root);

				createKeyAndValue(xmlDoc,root,'function','xmlDeleteDocuments');
				createKeyAndValue(xmlDoc,root,'docType',docType);
				for(var i=0;i<documentArr.length;i++) {
					createKeyAndValue(xmlDoc,root,'doc'+(i+1),documentArr[i]);
					var row = getEl('document-'+documentArr[i]).sectionRowIndex;
					docTable.deleteRow(row);
				}
				postXML(domToString(xmlDoc));

				var ct = docTable.rows.length - 1;
				adjustResults(docType,docName,ct);
			}

			function adjustResults(dType,dName,num) {
				var docRes = getEl(docType);
				clearDiv(docRes);

				docRes.appendChild(document.createTextNode(dName+'('+num+')'));
			}

			function sortDocField(field) {
				if(curField == field) {
					if(dir == "DESC") {
						dir = "ASC";
					} else {
						dir = "DESC";
					}
				} else {
					curField = field;
					dir = "DESC";
				}
				getEl('newPage').value = 1;
				getPageResults(1);
			}
		</script>	
		<style>
			#actionDiv {
				display		: none;
				position	: absolute;
				top			: 45px;
				left		: 35%;
				padding		: 2px;
				font-size	: 12pt;
				width		: 210px;
			}

			#actionDiv img {
				padding-top 	: 2px;
				padding-bottom 	: 2px;
				padding-left 	: 5px;
				padding-right 	: 5px;
				vertical-align	: text-top;
			}

			#actionDiv span {
				position: absolute;
				width	: 100px;
				cursor	: pointer;
			}

			#errorMsg {
				position	: absolute;
				top			: 25px;
				left		: 70%;
				color		: #990000;
   				font-size	: 9pt ;
			}

			body {
				font-family: Tahoma, Verdana, sans-serif;
			}
		</style>
	</head>
	<body>
		<?php searchDocToolBar(); ?> 	
		<?php displayPagingButtons($pages,$page); ?> 	
		<div id="actionDiv" >
			<span id="cancelBtn"
				onmouseover="addHighLight(this)"
				onmouseout="remHighLight(this,'white')">
				<img src="../energie/images/cancl_16.gif" 
					alt="Cancel"
					title="Cancel"
					width="14" />Cancel
			</span>
			<span id="saveBtn" 
				style="left:105px"
				onmouseover="addHighLight(this)"
				onmouseout="remHighLight(this,'white')">
				<img src="../energie/images/save.gif" 
					alt="Save"
					title="Save"
					width="14" />Save
			</span>
		</div>
		<div id="errorMsg"></div>
		<div id="docTypeDiv" class="documentType" style="width:98%">
			<div class="documentTypeOn" style="left:0%;white-space:nowrap;width:25%">
				<span id="<?php echo $docObj->docType; ?>" style="padding:5px">
				<?php echo $docObj->docName." (".$docObj->numResults.")"; ?>
				</span>
			</div>
<!--
			<div class="documentTypeOff" style="left:20%;width:20%;">
				<span>
				<?php echo "Invoices (100)"; ?>
				</span>
			</div>
			<div class="documentTypeOff" style="left:40%;width:20%;">
				<span>
				<?php echo "Payroll (100)"; ?>
				</span>
			</div>
			<div class="documentTypeOff" style="left:60%;width:20%;">
				<span>
				<?php echo "Receipts (100)"; ?>
				</span>
			</div>
			<div class="documentTypeOff" style="left:80%;width:20%;">
				<span>
				<?php echo "Contracts (100)"; ?>
				</span>
			</div>
			<div class="documentTypeOff" style="left:625px">
				<span>
				<?php echo "Miscellaneous (100)"; ?>
				</span>
			</div>
-->
		</div>
		<div id="docResDiv" class="documents" style="width:98%">
			<table id="docTable" class="resTable">
				<tr class="tableHeader">
					<th style="text-align:center">
						<input type="checkbox" id="docCheck" name="docCheck" value="all" onclick="selectAll()" />
					</th>
					<th style="width:100px">
						<span>Cabinet</span>
					</th>
					<?php foreach($docObj->fieldDispNames AS $name): ?>
						<th>
							<span style="cursor:pointer" onclick="sortDocField('<?php echo $name; ?>')">
								<?php echo h($name); ?>
							</span>
						</th>
					<?php endforeach; ?>
				</tr>
				<?php if(count($res)): ?>
				<?php foreach($res AS $id => $info): ?>
					<tr id="document-<?php echo $id; ?>"
						style="cursor:pointer"
						onmouseover="mOverTR(this)" 
						onmouseout="mOutTR(this)"
					>
						<td style="width:25px;text-align:center">
							<input type="checkbox" 
								id="documentCheck:<?php echo $ct++; ?>" 
								value="<?php echo $id; ?>"
								cab="<?php echo $info[0]['cab_name']; ?>"
								doc_id="<?php echo $info[0]['doc_id']; ?>"
								file_id="<?php echo $info[0]['file_id']; ?>"
							/>
						</td>
						<td document_id='<?php echo $id; ?>'
							cab='<?php echo $info[0]['cab_name']; ?>'
							doc_id='<?php echo $info[0]['doc_id']; ?>'
							file_id='<?php echo $info[0]['file_id']; ?>'
							onclick="selectDocument(this)" >
							<span>
								<?php echo $user->cabArr[$info[0]['cab_name']]; ?>
							</span>
						</td>
						<?php foreach($docObj->fields AS $fInfo): 
							$emptyField = true;
						?>
							<?php foreach($info AS $val): ?>
								<?php if($val['field_id'] == $fInfo['id']): 
									$emptyField = false;
								?>
									<td	id="field:<?php echo $val['field_id']; ?>"
										document_id='<?php echo $id; ?>'
										cab='<?php echo $info[0]['cab_name']; ?>'
										doc_id='<?php echo $info[0]['doc_id']; ?>'
										file_id='<?php echo $info[0]['file_id']; ?>'
										onclick="selectDocument(this)" >
										<span><?php echo h($val['field_value']); ?></span>
									</td>
								<?php endif; ?>
							<?php endforeach; ?>
							<?php if($emptyField): ?>
								<td	id="field:<?php echo $fInfo['id']; ?>"
									document_id='<?php echo $id; ?>'
									cab='<?php echo $info[0]['cab_name']; ?>'
									doc_id='<?php echo $info[0]['doc_id']; ?>'
									file_id='<?php echo $info[0]['file_id']; ?>'
									onclick="selectDocument(this)" >
									<span></span>
								</td>
							<?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</table>
		</div>
	</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
