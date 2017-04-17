<?php
include_once '../check_login.php';
include_once '../lib/cabinets.php';
include_once '../modules/modules.php';

if($logged_in and $user->username) {
	$cab = '';
	$userSettings = new Usrsettings($user->username, $user->db_name);
	$gblSettings = new GblStt($user->db_name, $db_doc);
	$dateFuncs = $gblSettings->get('date_functions');
	if (!$dateFuncs) {
		$dateFuncs = 'false';
	}
	
	$searchPanelView = $userSettings->get('searchPanelView');
	if($searchPanelView == NULL) {
		$searchPanelView = 0;
	}

	$allBookmarks = $userSettings->get('bookmarks');
	$allBookmarks = unserialize(base64_decode($allBookmarks));

	//If allBookmarks is empty, set it up as an empty array.
	if(!is_array($allBookmarks)) {
		$allBookmarks = array();
		$tmpStr = base64_encode(serialize($allBookmarks));
		$userSettings->set('bookmarks', $tmpStr);
	}
	
	$bookmarkRestrict_user = $userSettings->get('bookmarkRestrict');
    if(!$bookmarkRestrict_user)
        $bookmarkRestrict_user = 'off';

    $bookmarkRestrict_ro = $gblSettings->get('bookmarkRestrict');
    if(!$bookmarkRestrict_ro)
        $bookmarkRestrict_ro = 'off';
	
	//The real name will be used for the 'value' of the select box, and the
	//value without '_' will be used for display.
	$tmpList = array_merge(array_keys( $user->access,'rw'), array_keys( $user->access,'ro'));
	$cabList = array();

	foreach($tmpList as $myCab) {
		if(isset ($user->cabArr[$myCab])) {
			$cabList[$myCab] = $user->cabArr[$myCab];
			$cab = $myCab;
		}
	}
	uasort($cabList,'strnatcasecmp');

	$sArr = array('document_table_name','document_type_name');
	$wArr = array('enable' => 1);
	$oArr = array('document_type_name' => 'ASC');
	$docTypeArr = getTableInfo($db_object,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);

	$tArr = array('document_type_defs','document_permissions','group_list');
    $sArr = array('document_table_name','groupname');
    $wArr = array('permissions_id != 0',
                  'permissions_id=permission_id',
                  'group_list_id=list_id');
    $permArr = getTableInfo($db_object,$tArr,$sArr,$wArr,'getAssoc',array(),0,0,array(),true);
    foreach($permArr AS $k => $groupArr) {
        $check = false; 
        foreach($groupArr AS $g) {
            if(in_array($g,$user->groups)) {
                $check = true;
                break; 
            }
        }
        if(!$check) {
            unset($docTypeArr[$k]);
        }
    }

	$cabinetIndices = array();
	$fields = array();
	$sec_level = '';
	$dataTypeInfo = '';
	if(count($cabList) == 1) {
		$sec_level = $user->checkSecurity($cab);
		$cabinetIndices = getCabinetInfo($db_object, $cab);	
		foreach($cabinetIndices AS $ind) {
			$fields[] = "'".$ind."'";
		}

		$DepID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');
		$whereArr = array("department='$user->db_name'","k LIKE 'dt,$user->db_name,$DepID,%'");
		$dataTypeInfo = getTableInfo($db_doc,'settings',array('k','value'),$whereArr);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Search Cabinet Stuff</title>
		<link rel="stylesheet" type="text/css" href="../lib/style.css" />
		<link rel="stylesheet" type="text/css" href="../lib/calendar.css" />
		<script type="text/javascript" src="../lib/calendar.js"></script>
		<script type="text/javascript" src="../documents/documentsWizard.js"></script>
		<script type="text/javascript" src="../lib/prototype.js"></script>
		<script type="text/javascript" src="../lib/settings2.js"></script>
		<script type="text/javascript" src="searchPanel.js"></script>
		<style type="text/css">
			html {
				overflow-x: hidden;
				border-style: none;
			}
			body {
				font-size: 11pt;
				border-style: none;
			}
			form {
				margin-bottom: 1em;
			}
		</style>
		<script type="text/javascript">
			var cab = "<?php echo $cab; ?>";
			var sec_level = "<?php echo $sec_level; ?>";
			var cabFields = new Array('<?php echo implode("','",$cabinetIndices); ?>');
			var dataType = new Array();
			var loadingBookmark = false;
			var searchPanelView = <?php echo $searchPanelView ?>;
			<?php foreach($fields AS $ind): ?>
				dataType[<?php echo $ind; ?>] = new Array();
			<?php endforeach; ?>
			<?php if($dataTypeInfo != NULL): ?>
				<?php while($result=$dataTypeInfo->fetchRow()):
					$key = str_replace( "dt,$user->db_name,$DepID,", "", $result['k'] );
					$value = $result['value'];
					$valArr = explode(",,,",$value);
					for($i=0;$i<sizeof($valArr);$i++): ?> 
						dataType['<?php echo $key;?>'][<?php echo $i; ?>] = "<?php echo addslashes($valArr[$i]); ?>";
					<?php endfor;
				endwhile; ?>
			<?php endif; ?>
			var dateFunctions = <?php echo $dateFuncs ?>;
			function resizeSearchPanel() {
				if(!searchPanelView) {
					var cabID = $('DepartmentID');
					var afterMenu = top.document.getElementById('afterMenu');
					var fCol = afterMenu.getAttribute('cols');
					var fWidth;
					if(fCol.search("'") != -1) {
						fCol = fCol.replace(/'/, '');
					}
					fWidth = fCol.split(",")[0];
					if(fWidth < cabID.clientWidth + 45) {
						var newCols = (cabID.clientWidth + 45) + ",*";
						afterMenu.setAttribute('cols', newCols);
					} 
				}
			}

			function registerEvents() {
				if(searchPanelView) {
					document.getElementById('outerDiv').style.display = 'none';
					parent.document.getElementById('afterMenu').setAttribute('cols','20,*');
				}

				var dateCreated = $('dateCreated');
				if(dateCreated) {
					var advancedSearch = $('advancedSearch');
					tmpImg = document.createElement('img');
					tmpImg.src = '../images/edit_16.gif';
					tmpImg.style.cursor = 'pointer';
					tmpImg.style.verticalAlign = 'middle';
					tmpImg.input = dateCreated;
					tmpImg.whereID = 'searchFields';
					tmpImg.onclick = dispCurrMonth;
					dateCreated.parentNode.insertBefore(tmpImg, dateCreated.nextSibling);
					var tmpInput = document.createElement('input');
					var newDiv = document.createElement('div');
					tmpInput.type = 'checkbox';
					tmpInput.id = 'zzdate-range';
					tmpInput.field = 'date';
					tmpInput.fieldDiv = 'zzdate';
					tmpInput.onclick = toggleRangeSearch;
					newDiv.appendChild(tmpInput);
					labelSpan = document.createElement('span');
					labelSpan.style.fontSize = '8pt';
					txtNode = document.createTextNode('Search By Date Range');
					labelSpan.appendChild(txtNode);
					newDiv.appendChild(labelSpan);
					dateCreated.parentNode.parentNode.insertBefore(newDiv, dateCreated.parentNode.nextSibling);
				}
				var cabID = $('DepartmentID');
				if( (cabID != null) && (cabID.options.length == 2) ) {
					cabID.options[1].selected = true;

					var cabLabel = $('cabLabel');
					var cabTxt = cabID.options[cabID.selectedIndex].text;
					var newTxt = document.createTextNode(cabTxt);
					if(cabLabel.childNodes.length > 1) {
						cabLabel.replaceChild(newTxt, cabLabel.lastChild);
					} else {
						cabLabel.appendChild(newTxt);
					}
					cabLabel.style.display = 'block';

					setCabinet(cab,cabFields,sec_level,false,dataType);
					cabID.style.display = 'none';
				}
				showBookmarks();
			}

		</script>
		<style type="text/css">
		input.textBox {
			width: 150px;
		}
		</style>
	</head>
	<body class="tealbg" onload="registerEvents()">
		<div style="position:absolute;top:0px;right:0px">
			<?php if(!$searchPanelView): ?>
			<img src="../images/left.GIF" 
				id="imgView"
				style="cursor:pointer;vertical-align:middle" 
				title="minimize" 
				alt="minimize"
				onclick="parent.topMenuFrame.toggleSearchPanel(1)"
			/>
			<?php else: ?>
			<img src="../images/right.GIF" 
				id="imgView"
				style="cursor:pointer;vertical-align:middle" 
				title="maximize" 
				alt="maximize"
				onclick="parent.topMenuFrame.toggleSearchPanel(0)"
			/>
			<?php endif?>
		</div>
		<div id="outerDiv">
		<?php if(count($cabList)): ?>
			<div id="tlsSearchDiv">
				<form
					method="post"
					action="topLevelSearch.php"
					target="mainFrame"
					name="tlsForm"
				>
					<div>
						<?php echo $trans['Top-Level Search'] ?>
					</div>
					<div>
						<input
							id="searchInput"
							type="text"
							name="search"
							size="16"
							onkeypress="return onTLSKeyPress(event)"
						/>
						<input
							id="tlsBtn"
							type="submit"
							value="<?php echo $trans['GO'] ?>"
							onclick="clearForTLS()"
						/>
					</div>
					<div>
						<span>Match:</span>
						<span class="matchSpan">
							<span class="allAny">all</span>
							<input
								type="radio"
								value="1"
								checked="checked"
								id="radioAll"
								class="matchInput"
								name="exact"
							/>
						</span>
						<span class="matchSpan">
							<span class="allAny">any</span>
							<input
								type="radio"
								value="0"
								id="radioAny"
								class="matchInput"
								name="exact"
							/>
						</span>
					</div>
				</form>
			</div>
			<?php if(($bookmarkRestrict_ro=="off" && $bookmarkRestrict_user=="off") || $bookmarkRestrict_user=="off"): ?>
			<div>
				<form id="bkForm" action="">
					<div id="bkDiv" style="display: none">
						<select id="bookmark" onchange="selectBookmark()">
							<option
								selected="selected"
								value='__bookmarks'
							>
								Bookmarked Searches
							</option>
							<?php foreach($allBookmarks as $i => $markArray): ?>
								<option value="<?php echo $i ?>">
									<?php echo stripslashes(h($markArray['name'])) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</form>
			</div>
			<?php endif; ?>
			<div>
				<div style="height:19px;width:100%;background-color:#003b6f">
					<div id="searchTab"
						class="panelImgDiv"
						onclick="showCabSearch()"
					>
					<span>Cabinet</span>
					</div>
					<?php if(count($docTypeArr)): ?>
					<div id="documentTab"
						class="panelImgDiv1"
						onclick="showDocSearch()"
					>
					<span>Document</span>
					</div>
					<?php endif; ?>
					<div id="toolsGifDiv"
						class="panelImgDiv1"
						onclick="showNotesDiv()"
					>
						<span>Tools</span>
					</div>
				</div>
				<div id="searching">
					<div class="tabBody">
						<div id="chooseCabinet">
							<select
								id="DepartmentID"
								name="DepartmentID"
								onchange="changeCabSearch()"
							>
								<option
									value="__chooseCab" 
									selected="selected"
								>
									<?php echo $trans['Choose Cabinet'] ?>
								</option>
								<?php foreach($cabList as $k => $v): ?>
									<option value="<?php echo $k ?>">
										<?php echo $v ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div id="cabLabel">Cabinet: </div>
							<script type="text/javascript">
								resizeSearchPanel();
							</script>
						</div>
						<div id="cabSearchDiv">
							<form
								name="cabSearchForm"
								id="cabSearchForm"
								method="post" 
								action="searchResults.php"
								target="mainFrame"
							>
								<div id="searchFields">
								</div>
								<div id="advancedBtn" onclick="showHideAdvanced()">
									<img
										src="images/next.gif"
										id="nextBtn"
										alt="Advanced"
										title=""
									/>
									<span>advanced</span>
								</div>
								<div id="advancedSearch">
									<div id="advSearchSubfolder">
										<div>Subfolder</div>
										<div>
	<input onkeypress="return formKeyPress(event)" 
		class="textBox" 
		type="text" 
		name="subfolder" 
	/>
</div>
									</div>
									<div id="advSearchFilename">
										<div>File Name</div>
										<div>
<input onkeypress="return formKeyPress(event)" 
	class="textBox" 
	type="text" 
	name="file" 
/>
										</div>
									</div>
									<?php if(check_enable('context_search',
										$user->db_name)): ?>
										<div id="advSearchContextSearch">
											<div>Context Search</div>
											<div>
	<input onkeypress="return formKeyPress(event)" 
		class="textBox" 
		type="text" 
		name="context" 
	/>

											</div>
<!--											<div>
												<div id="exhaustDiv">
													<input
														class="textBox"
														type="checkbox"
														name="contextbool"
														value="on"
													/>
													<span>Exhaustive</span>
												</div>
											</div> -->
										</div>
									<?php endif; ?>
									<div id="advSearchDateCreated">
										<div>Date Created</div>
										<div id="zzdate-div">
											<input onkeypress="return formKeyPress(event)" 
												class="textBox" 
												type="text" 
												id="dateCreated" 
												name="date" 
											/>

</div>
									</div>
									<div id="advSearchWhoIndexed">
										<div>Who Indexed</div>
										<div>
											<input onkeypress="return formKeyPress(event)" 
												class="textBox" 
												type="text" 
												name="who" 
											/>

										</div>
									</div>
									<div id="advSearchNotes">
										<div>Notes</div>
										<div>
											<input onkeypress="return formKeyPress(event)" 
												class="textBox" 
												type="text" 
												name="notes" 
											/>
										</div>
									</div>
								</div>
								<div id="submitDiv">
									<input
										type="button"
										value="Search"
										id="submitBtn"
										onclick="clearForCabSearch()"
									/>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div id="newNotes" class="tabBody">
					<div>Current Note(s):</div>
					<div>
						<textarea
							readonly="readonly"
							id="oldNotes"
							rows="5"
							cols="20"
						></textarea>
					</div>
					<div id="addNoteDiv">
						<div>New Note:</div>
						<div>
							<textarea
								id="newNote"
								rows="5"
								cols="20"
							></textarea>
						</div>
						<div>
							<input
								type="button"
								value="save"
								onclick="addNote()"
							/>
							<!--
							<input
								type="button"
								value="sort"
								onclick="reverseNotes()"
							/>
							-->
						</div>
					</div>
				</div>
				<?php if(count($docTypeArr)): ?>
				<div id="docSearching">
					<div class="tabBody">
						<div id="chooseDocument">
							<select id="docType"
									name="docType"
									onchange="changeDocSearch()"
							>
								<option value="__default"
										selected="selected">Choose Document</option>
								<?php foreach($docTypeArr AS $k => $docType): ?>
									<option value="<?php echo $k; ?>">
										<?php echo $docType; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div id="docSearchDiv" style="display:none;padding-top:10px">
							<form name="docSearchForm"
								id="docSearchForm"
								method="post" 
								action="../documents/searchDocumentView.php"
								target="mainFrame"
							>
								<div id="docSearchFields">
								</div>
								<div id="docSubmitDiv">
									<input
										type="submit"
										value="Search"
										id="docSubmitBtn"
										onclick="filterSearch()"
									/>
								</div>
							</form>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		</div>
	</body>
</html>
<?php
	setSessionUser($user);
}
?>
