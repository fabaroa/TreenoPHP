<?php
// $Id: documentDispFuncs.php 14704 2012-02-21 16:45:33Z acavedon $
include_once '../lib/licenseFuncs.php';

function printSliderBar($frameWidth) {
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

		function adjustFilenames() {
			var fArr = $('currentFiles').getElementsByTagName('span');
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
		// ]]>
	</script>

<?php
}


function printExportRedactInput($cab,$user) {
	if(check_enable('redaction',$user->db_name) && $user->checkSetting('viewNonRedact',$cab)) {
?>
	<div id="exportRedact">
		<input type="checkbox" id="exportNonRedact">
		<label class="lnk" style="font-size:8pt" for="exportNonRedact">Export Non-Redacted Files</label>
	</div>

<?php
	}
}

function displayToolBar() {
?>
	<div id="toolBar" style="display:none;text-align:left;padding:0px;margin:0px">
		<img id="multiTool"
			src="../images/copy.gif"
			alt="File Actions"
			title="File Actions"
			style="visibility:hidden;padding:0px;cursor:pointer"
			width="16"
			onclick="openActions('fileActions')"
		/>
		<img id="singleTool"
			src="../images/generic.gif"
			alt="Single File Actions"
			title="Single File Actions"
			style="visibility:hidden;padding:0px;cursor:pointer"
			width="16"
			onclick="openActions('singleFileActions')"
		/>	
	</div>
<?php
}

function displayBarcode($subfolder,$cab,$user) {
	if(!$user->checkSetting('documentView',$cab)) {
?>
		<div>
			<fieldset>
				<legend>Barcode</legend>
				<div style="text-align:center">
					<select>
						<option>Choose a Subfolder</option>
						<option><?php echo $subfolder; ?></option>
					</select>
				</div>
			</fieldset>
		</div>
<?php
	}
}

function displayActions($cab,$doc_id,$tab_id,$subfolder,$order,$user,$db_doc, $db_dept) {
	global $trans;

	if(check_enable('workflow',$user->db_name) && $user->checkSetting('wfIcons',$cab)) {
		$wf_document_id = 0;
		$sArr = array('id','status');
        $wArr = array(  "cab= '$cab'",
                        "doc_id= ".(int)$doc_id,
                        "(file_id= $tab_id OR file_id=-2)",
						"status != 'COMPLETED'");
        $oArr = array('file_id' => 'DESC');
        $wfInfo = getTableInfo($db_dept,'wf_documents',$sArr,$wArr,'getAssoc',$oArr);
        foreach($wfInfo AS $id => $st) {
            $status = $st;
            $wf_document_id = $id;
			if($wf_document_id != -2) {
				break;
			}
        }

        $sArr = array('COUNT(id)');
        $wArr = array(  'username'      => $user->username,
                'department'        => $user->db_name,
                'wf_document_id'    => (int)$wf_document_id );
        $ct = getTableInfo($db_doc,'wf_todo',$sArr,$wArr,'queryOne');

        $sArr = array('COUNT(id)');
        $wArr = array(  "cab= '$cab'",
                        "doc_id= ".(int)$doc_id,
                        "(file_id= $tab_id OR file_id=-2)");
        $histCt = getTableInfo($db_dept,'wf_documents',$sArr,$wArr,'queryOne');
    }
	
	$orderDesc = ($order) ? "Detailed View" : "Thumbnail View";
	if( ($user->checkSetting('getAsPDF',$cab)) 			||
		($user->checkSetting('getAsZip',$cab)) 			||
		($user->checkSetting('showBarcode',$cab) 
			&& $user->checkSetting('documentView',$cab))||
		($user->checkSetting('changeThumbnailView',$cab) 
			&& $user->checkSecurity($cab) == 2) 		|| 
		(check_enable('workflow', $user->db_name)
			&& $user->checkSetting('wfIcons',$cab)
			&& (($wf_document_id) 
				|| ($ct) 
				|| ($histCt)))							||
		($user->checkSetting('deleteFiles',$cab)
			&& $user->checkSecurity($cab) == 2)		||
		($user->checkSetting('uploadFiles',$cab))		||
		($user->checkSetting('moveFiles',$cab))			||
		($user->checkSetting('addEditTabs',$cab))		||
		($user->checkSetting('reorderFiles',$cab)
			&& $user->checkSecurity($cab) == 2)			||
		($user->checkSetting('viewMode',$cab))	) {
?>
	<div id="fileActions">
		<script type="text/javascript">
		//<![CDATA[

		//cz
		function removeBackButtons() {
		    parent.topMenuFrame.removeBackButtons();
		}
		
		//cz
		function doSignFile() {
		    /*   var frames = "";
			for (var i=0; i < top.frames.length; i++)
			{
				frames = frames + "Frame "+ i+ ": "+ top.frames[i].name+"; \n";
			}
		    alert(frames);*/
		    
		        //addMessage('Test - sign a file..');
		        //var fileIDs = getSelectedFiles();
		        if(findFirstSelected(1)) {
		                //removeBackButtons();
		                addBackButton();                        //'Back To Results' - func.js::addBackButtonOnclick
		                var postStr = getSelectedFilesDS();//getFiles();

		                /*parent.mainFrame.window.location = '../docuSign/dsFHFrame.php?signFile&'
		                        +'cab='+cab+'&doc_id='+doc_id+'&tab_id='+tab_id +'&checked_files='+postStr;
		                        //'+'fileID='+file_id+'&cabinetID='+cab_id;
		                parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
		                parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
		                //parent.viewFileActions.window.location = '../energie/bottom_white.php';
		                parent.sideFrame.window.location = '../energie/left_blue_search.php';
		                return;*/

		                /*
		                addBackButton();
		                var postStr = getSelectedFiles();//getFiles();
		                parent.mainFrame.window.location = '../docuSign/docuSignLogin.php?signFile&'
		                        +'cab='+cab+'&doc_id='+doc_id+'&tab_id='+tab_id +'&checked_files='+postStr;
		                 */

		                parent.viewFileActions.window.location = '../docuSign/dsFHFrame.php?signFile&'
		                        +'cab='+cab+'&doc_id='+doc_id+'&tab_id='+tab_id +'&checked_files='+postStr;
		                parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
		                return;

		        }
		        else
		        {
		                addMessage('No file is selected. Show status of all files in this folder.');
		            i//parent.mainFrame.window.location = '../docuSign/docuSignStatus.php';
		            parent.leftFrame1.window.location = '../energie/moveThumb.php?docusign=1';
		            return;
		            //parent.leftFrame1.window.location = '../documents/viewDocuments.php?docusign=1&'
		            //  +'cab='+cab+'&doc_id='+doc_id+'&tab_id='+tab_id;

		        }
		        parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
		        parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
		        //parent.viewFileActions.window.location = '../energie/bottom_white.php';
		        parent.sideFrame.window.location = '../energie/left_blue_search.php';

		}

		//cz
		function getSelectedFilesDS() {
		      var i = 1;
		      var checked = new Array();
		      var strIDs ="";
		      var j = 0;
		      while( el = getEl('file-'+i)) {
		              if(el.checked == true) {
		                      if(strIDs =="")
		                              strIDs = el.value;
		                      else
		                              strIDs = strIDs + "_next-file-id_" + el.value;
		                      //checked[j] = el.value;
		                      j++;
		              }
		              i++;
		      }

		      return strIDs;
		}
		
		
		// ]]>
	</script>
		<fieldset id="fsFileActions">
			<legend>
				<img src="../images/close.gif" 
					style="cursor:pointer;vertical-align:middle" 
					title="Minimize" 
					alt="Minimize"
					width="12"
					onclick="closeActions('fileActions')"
				/>
				<span style="font-size:9pt">Actions</span>
			</legend>
			<table cellpadding="1" cellspacing="1" style="text-align:center">
				<tr>
					<?php if($user->checkSetting('getAsPDF',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="<?php echo $trans['Get As PDF File']?>" 
							title="<?php echo $trans['Get As PDF File']?>" 
							src="../images/pdf.gif" 
							onclick="getSelectedFiles('createPDF')"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('getAsZip',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="<?php echo $trans['Get As ZIP File']?>" 
							title="<?php echo $trans['Get As ZIP File']?>" 
							src="../images/zip.gif" 
							onclick="getSelectedFiles2('createZIP')"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('showBarcode',$cab) 
							&& $user->checkSetting('documentView',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="Print Barcode" 
							title="Print Barcode" 
							src="../images/barcode.gif" 
							onclick="printDocutronBarcode('<?php echo $cab; ?>',
														'<?php echo $doc_id; ?>',	
														'<?php echo $subfolder; ?>',
														'',
														'',
														'',
														'<?php echo $tab_id;?>')"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('changeThumbnailView',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="<?php echo $orderDesc; ?>" 
							title="<?php echo $orderDesc; ?>" 
							src="../images/generic.gif" 
							width="24"
							onclick="allowOrdering('<?php echo $order; ?>')"
						/>
					</td>
					<?php endif; ?>
					<?php if(check_enable('workflow', $user->db_name) && $user->checkSetting('wfIcons',$cab)): ?>
						<td	id="wfHist"
						<?php if(!$histCt && !$wf_document_id): ?>
							style="display:none"
						<?php endif; ?>
						>
							<img class="buttons" 
								alt="Workflow History" 
								title="Workflow History" 
								src="../images/addbk_16.gif" 
								onclick="viewWFHistory()"
							/>
						</td>
						<?php if($ct): ?>
						<td id="workflow2">
							<img id="wfImg"
								class="buttons" 
								alt="View Workflow" 
								title="View Workflow" 
								src="../images/edit_24.gif" 
								<?php if($status != "PAUSED"): ?>
								onclick="enterWorkflow()"
								<?php else: ?>
								onclick="editWorkflow()"
								<?php endif; ?>
							/>
						</td>
						<?php elseif(!$wf_document_id || $wf_document_id == -2): ?>	
						<td id="workflow2">
							<img id="wfImg"
								class="buttons" 
								alt="Assign Workflow" 
								title="Assign Workflow" 
								src="../images/email.gif" 
								onclick="assignWorkflow()"
							/>
						</td>
						<?php endif; ?>
					<?php endif; ?>							
					<?php if( check_enable('eSign', $user->db_name)
						&&	$user->checkSetting('docuSign',$cab)
						&& $user->checkSecurity($cab) == 2): ?>
					<td>
						<img class="buttons"  
							alt="<?php echo "Select files to sign or check status" ?>" 
							title="<?php echo "Select files to sign or check status"?>" 
							src="../images/DocuSign.gif" 
							onclick="doSignFile()"
						/>
					</td>
					<?php endif; ?>
					<?php /*error_log("docuSign setting for ".$cab." is: ".$user->checkSetting('docuSign',$cab))*/ ?>
				</tr>
				<tr>
					<?php if($user->checkSetting('deleteFiles',$cab)
						&& $user->checkSecurity($cab) == 2): ?>
					<td style="white-space:nowrap">
						<img class="buttons" 
							alt="<?php echo $trans['Delete File']?>" 
							title="<?php echo $trans['Delete File']?>" 
							src="../images/trash.gif" 
							onclick="getSelectedFiles('deleteFiles')"
						/>
						<!-- not used any longer - DISABLED
						<?php if($user->checkSetting('deleteButtonString',$cab)): ?>
						    <span style='cursor:pointer;font-size:10px'
								onclick="getSelectedFiles('deleteFiles')"> Delete</span>
						<?php endif; ?>
						-->
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('uploadFiles',$cab)
						&& $user->checkSecurity($cab) == 2): ?>
					<td style="white-space:nowrap">
						<img class="buttons" 
							width="20" 
							alt="Upload File" 
							title="Upload File" 
							src="../images/upload1.jpg"
							onclick="uploadFile()"
						/>
						<!-- not used any longer - DISABLED
						<?php if($user->checkSetting('uploadButtonString',$cab)): ?>
						    <span style='cursor:pointer;font-size:10px'
								onclick="uploadFile()"> Upload</span>
						<?php endif; ?>
						-->
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('moveFiles',$cab)
						&& $user->checkSecurity($cab) == 2): ?>
					<td>
						<img class="buttons"  
							alt="<?php echo $trans['Move File']?>" 
							title="<?php echo $trans['Move File']?>" 
							src="../images/movefiles.gif" 
							onclick="moveFile()"
						/>
					</td>					
					<?php endif; ?>
					<!--ALS: no longer used in new TreenoV4
					<?php if($user->checkSetting('addEditTabs',$cab) 
						&& !$user->checkSetting('documentView',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="<?php echo $trans['Add/Edit Tabs']?>" 
							title="<?php echo $trans['Add/Edit Tabs']?>" 
							src="../images/new_tab.gif" />
					</td>
					<?php endif; ?>
					-->
					<?php if($user->checkSetting('reorderFiles',$cab)
						&& $user->checkSecurity($cab) == 2 ): ?>
					<td>
						<img id="reorder" 
							class="buttons" 
							alt="Reorder Pages" 
							title="Reorder Pages" 
							src="../images/ref_24.gif" 
							onclick="reorderFiles()"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('modifyImage',$cab)
						&& $user->checkSecurity($cab) == 2): ?>
					<td>
						<img class="buttons"
							alt="Rotate Image"
							title="Rotate Image"
							src="../energie/images/undo_16.gif"
							onclick="modifyImage('rotate')"
						/>
					</td>
					<td>
						<img class="buttons"
							alt="Flip Image"
							title="Flip Image"
							src="../energie/images/redo_16.gif"
							onclick="modifyImage('flip')"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('viewMode',$cab)): ?>
					<td>
						<img id="viewMode" 
							class="buttons" 
							alt="Full Screen Mode" 
							title="Full Screen Mode" 
							src="../images/opnbr_24.gif" 
							onclick="fullScreenMode()"
						/>
					</td>
					<?php endif; ?>
				</tr>
			</table>
		</fieldset>
	</div>
<?php
		return true;
	} else {
		return false;
	}
}

function displaySingleFileActions($cab,$cabID,$user) {	
	global $trans;
	if(	(check_enable('versioning', $user->db_name) 
			&& $user->checkSecurity($cab) == 2
			&& $user->checkSetting('versioning',$cab))	||
		($user->checkSetting('saveFiles',$cab))			||	
		(check_enable('redaction', $user->db_name)
			&& $user->checkSecurity($cab) == 2
			&& $user->checkSetting('redactFiles',$cab)) ||
		($user->checkSetting('editFilename',$cab) 
			&& $user->checkSecurity($cab) == 2)) {
?>
	<div id="singleFileActions">
		<fieldset id="fsSingleFileActions">
			<legend>
				<img src="../images/close.gif" 
					style="cursor:pointer;vertical-align:middle" 
					title="Minimize" 
					alt="Minimize"
					width="12"
					onclick="closeActions('singleFileActions')"
				/>
				<span style="font-size:9pt">Single File Actions</span>
			</legend>
			<table>
				<tr>
					<?php if($user->checkSetting('editFilename',$cab) 
							&& $user->checkSecurity($cab) == 2): ?>
					<td>
						<img class="buttons" 
							alt="<?php echo $trans['Edit File Name']; ?>" 
							title="<?php echo $trans['Edit File Name']; ?>" 
							src="../energie/images/file_edit_16.gif" 
							onclick="editFile()"
						/>
					</td>
					<?php endif; ?>
					<?php if(check_enable('versioning', $user->db_name) 
						&& $user->checkSecurity($cab) == 2
						&& $user->checkSetting('versioning',$cab)): ?>
					<td>
						<img class="buttons" 
							width="20" 
							alt="Version File" 
							title="Version File" 
							src="../images/version.gif" 
							onclick="versionFile('<?php echo $cabID; ?>')"
						/>
					</td>
					<?php endif; ?>
					<?php if($user->checkSetting('saveFiles',$cab)): ?>
					<td>
						<img class="buttons" 
							alt="Save File" 
							title="Save File" 
							src="../energie/images/save.gif" 
							onclick="saveFile()"
						/>
					</td>
					<?php endif; ?>
					<?php if(check_enable('redaction', $user->db_name) 
						&& $user->checkSecurity($cab) == 2
						&& $user->checkSetting('redactFiles',$cab)): ?>
					<td>
						<img class="buttons"
							width="20" 
							alt="Edit Redaction" 
							title="Edit Redaction" 
							src="../images/generic.gif" 
							onclick="redactFile()"
						/>
					</td>
					<?php endif; ?>
				</tr>
			</table>
			<div id="alertBox" class="alertBox">
				<div class="error" id="errMsg"></div>
			</div>
		</fieldset>
	</div>
<?php
		return true;
	} else {
		return false;
	}
}

function displayPagingButtons($ct,$page = 1) {
?>
	<div id="paging" style="text-align:center;white-space:nowrap">
		<table align="center">
			<tr>
				<td>
					<img style="cursor:pointer;vertical-align:middle" 
						alt="First"
						title="First"
						width="16" 
						src="../energie/images/begin_button.gif" 
						onclick="changePage('FIRST','<?php echo $ct; ?>')"
					/>
				</td>
				<td>
					<img style="cursor:pointer;vertical-align:middle" 
						alt="Previous"
						title="Previous"
						width="16" 
						src="../energie/images/back_button.gif" 
						onclick="changePage('PREV',
											'<?php echo $ct; ?>')"
					/>
				</td>
				<td>
					<input style="height:12px;font-size:9pt" 
						id="newPage"
						type="text" 
						size="2" 
						value="<?php echo $page; ?>" 
						onkeypress="return submitPage(event,'<?php echo $ct; ?>')"
					/>
					<input id="pageNum" 
						type="hidden" 
						totalPages="<?php echo $ct; ?>"
						value="<?php echo $page; ?>" 
					/>
					<span id="pageDetail" style="vertical-align:middle;font-size:9pt">
						<?php echo " of ".$ct; ?>
					</span>
				</td>
				<td>
					<img style="cursor:pointer;vertical-align:middle" 
						alt="Next"
						title="Next"
						width="16" 
						src="../energie/images/next_button.gif" 
						onclick="changePage('NEXT','<?php echo $ct; ?>')"
					/>
				</td>
				<td>
					<img style="cursor:pointer;vertical-align:middle" 
						alt="Last"
						title="Last"
						width="16" 
						src="../energie/images/end_button.gif" 
						onclick="changePage('LAST','<?php echo $ct; ?>')"
					/>
				</td>
			</tr>
		</table>
	</div>
<?php
}

function displayThumbnailView($cab,$fileInfo,&$count,$file_loc,$thumb_loc,$subfolder) {
	$full_file_path = $file_loc."/".$subfolder;
	$full_file_path .= "/".$fileInfo['filename'];

	$full_thumb_path = $thumb_loc."/".$subfolder;
	$full_thumb_path .= "/".$fileInfo['filename'].".jpeg";

	$fileArr = array(	'fileLoc'	=> $full_file_path, 
						'thumbLoc'	=> $full_thumb_path,
						'ca_hash'	=> $fileInfo['ca_hash'],
						'file_size'	=> $fileInfo['file_size']);

	$_SESSION['thumbnailArr'][$fileInfo['id']] = $fileArr;
	$pathInfo = pathInfo($full_file_path);
	$ext = strtolower($pathInfo['extension']);
?>
	<tr>
		<?php showNotes($cab,$fileInfo); ?>
		<td style="text-align:center">
			<?php if(canThumbnail($ext)): ?>
			<img class="ATimg" 
				id="img:<?php echo $count; ?>" 
				alt="Thumbnail" 
				title="" 
				src="../images/thumb.jpg"
				width="65" 
				height="80" 
			/>
			<div style="display:none" id="imgInfo:<?php echo $count++; ?>">
				<?php echo $cab.",".$fileInfo['id']; ?>
			</div>
			<?php else: ?>
			<?php putIcon($ext); ?>
			<?php endif; ?>
		</td>
		<td>&nbsp;</td>
	</tr>
<?php
}

function displayPagedView($cab,$fileInfo,$count,$order) {
?>
	<tr>
		<?php if(!$order): ?> 
		<?php showNotes($cab,$fileInfo); ?>
		<?php else: ?>
			<td></td>	
		<?php endif; ?>
		<td style="font-size:12px;text-align:center">
			<?php echo "Page ".($count+1); ?>
		</td>
		<td style="text-align:right" class="no">
			<input 
				type="checkbox" 
				id="file-<?php echo ($count+1); ?>" 
				name="file[]" 
				value="<?php echo $fileInfo['id']; ?>" 
			/>
		</td>
	</tr>
<?php
}

function displayFilenameView($cab,$fileInfo,$count,$order) {
	$name = $fileInfo['parent_filename'];
	if(strlen($name) > 20) {
		$name = substr($name,0,17)."...";
	}
?>
	<tr>
		<?php if(!$order): ?> 
		<?php showNotes($cab,$fileInfo); ?>
		<?php else: ?>
			<td height="5" style="height:5px;padding:0px;margin:0px">&nbsp;</td>	
		<?php endif; ?>
		<td id="filename-<?php echo $fileInfo['id']; ?>" 
			title="<?php echo $fileInfo['parent_filename']; ?>" 
			style="font-size:8pt;text-align:center;height:5px">	
			<span class="atfilename"><?php echo $name; ?></span>
		</td>
		<td style="text-align:right;height:5px" class="no">
			<input 
				type="checkbox" 
				id="file-<?php echo ($count+1); ?>" 
				name="file[]" 
				value="<?php echo $fileInfo['id']; ?>" />
		</td>
	</tr>
<?php
}

function displayFileInfo($cab,$fileInfo) {
?>
	<tr>
		<td	colspan="3" style="font-size:10px;text-align:center">
			<?php echo $fileInfo['who_indexed'].
				":".
				str_replace(".000000","",$fileInfo['date_created']).
				" Type:".
				getExtension($fileInfo['parent_filename']);
			?>
		</td>
	</tr>
<?php
}

function displayDSFileInfo($cab,$fileInfo) {
?>
<tr>
  <td	colspan="3" style="font-size:10px;text-align:center">
    <?php echo "DocuSign Status: ".
				$fileInfo['status']."<br/> Time_Created: ".$fileInfo['tmcreate'];
			?>
  </td>
</tr>
<?php
}

function showNotes($cab,$fileInfo,$note=1) {
?>
		<td style='width:25px;height:5px'>
			<img id="note-<?php echo $fileInfo['id']; ?>"
				class="buttons" 
				alt="Notes" 
				title="Notes" 
				src="../images/note.gif" 
			<?php if(!$fileInfo['notes'] || !$note): ?>
				style="visibility:hidden"
			<?php endif; ?>
			/>
		</td>
<?php
}

function putIcon($ext) {
	switch($ext){
		case 'txt':
			//for text files
			?>
			<img class="ATimg" alt="ascii" src="../images/ascii.gif" />
			<?php
			break;

		case 'doc':
			//for word documents
			?>
			<img class="ATimg" alt="doc" src="../images/worddoc.gif" />
			<?php
			break;

		case 'xls':
			//for excel files
			?>
			<img class="ATimg" alt="xls" src="../images/xls.gif" />
			<?php
			break;

		case 'ppt':
			//for powerpoint files
			?>
			<img class="ATimg" alt="ppt" src="../images/ppt.gif" />
			<?php
			break;

		case 'zip':
			//for zip files
			?>
			<img class="ATimg" alt="zip" src="../images/zip_32.gif" />
			<?php
			break;

		case 'gz':
			//for gz files
			?>
			<img class="ATimg" alt="zip" src="../images/zip_32.gif" />
			<?php
			break;

		case 'tar':
			//for tar files
			?>
			<img class="ATimg" alt="zip" src="../images/zip_32.gif" />
			<?php
			break;

		case 'ogg':
			//for audio files
			?>
			<img class="ATimg" alt="ogg" src="../images/audio.gif" />
			<?php
		  break;

		case 'mp3':
			//for audio files
			?>
			<img class="ATimg" alt="mp3" src="../images/audio.gif" />
			<?php
			break;
		
		case 'wav':
			//for audio files
			?>
			<img class="ATimg" alt="wav" src="../images/audio.gif" />
			<?php
			break;

		case 'avi':
			//for video files
			?>
			<img class="ATimg" alt="avi" src="../images/video.gif" />
			<?php
			break;

		case 'mov':
			//for video files
			?>
			<img class="ATimg" alt="mov" src="../images/video.gif" />
			<?php
			break;

		case 'mpeg':
			//for video files
			?>
			<img class="ATimg" alt="mpeg" src="../images/video.gif" />
			<?php
			break;

		case 'docx':
			//for word documents
			?>
			<img class="ATimg" alt="doc" src="../images/worddoc.gif" />
			<?php
			break;

		case 'xlsx':
			//for excel files
			?>
			<img class="ATimg" alt="xls" src="../images/xls.gif" />
			<?php
			break;

		case 'pptx':
			//for powerpoint files
			?>
			<img class="ATimg" alt="ppt" src="../images/ppt.gif" />
			<?php
			break;

		default:
			?>
			<img class="ATimg" alt="file" src="../images/generic.gif" />
			<?php
		  break;
	}
}
/*
	Display Functions for document search results
*/
function searchDocToolBar() {
	$toolBarArr = array(//'getPDF' 		=> 'Create PDF',
						'showRelDoc'	=> 'Show Related Documents',
					//	'getZip'		=> 'Zip Document',
					//	'editDoc'		=> 'Edit Document',
					//	'deleteDoc'		=> 'Delete Document' 
						);
?>
		<div id="toolBar" 
			onmouseover="openToolBar()" 
			onmouseout="closeToolBar()"
		>
			<div class="toolBarHeader">
				<span>
					Actions
				</span>
			</div>
			<div id="toolBarActions" style="display:none">
				<table>
					<?php foreach($toolBarArr AS $id => $val): ?>
					<tr onmouseover="addHighLight(this)" 
						onmouseout="remHighLight(this,'#ebebeb')"
						onclick="selectAction('<?php echo $id; ?>')"
					>
						<td>
							<span><?php echo $val; ?></span>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
		</div>
<?php
}
?>
