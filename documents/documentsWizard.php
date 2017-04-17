<?php
// $Id: documentsWizard.php 14828 2012-05-07 15:47:02Z curran $

include_once '../check_login.php';
include_once '../lib/utility.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
	$selArr = array('document_table_name','document_type_name');
	$docArr = getTableInfo($db_object,'document_type_defs',$selArr,array(),'getAssoc');
	if(!$docArr) {
		$disabled = "disabled";
	} else {
		$disabled = '';
	}
	uasort($docArr,'strnatcasecmp');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>Inbox Recycle Bin</title>
    <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
    <link rel="stylesheet" type="text/css" href="documents.css"/>
    <script type="text/javascript" src="documentsWizard.js"></script>
    <script type="text/javascript" src="scriptaculous/lib/prototype.js"></script>
    <script type="text/javascript" src="scriptaculous/src/scriptaculous.js"></script>
    <script type="text/javascript" src="../lib/settings.js"></script>
    <script type="text/javascript" src="../lib/help.js"></script>
	<script>
		var docTableName = '';
		var docTypeName = '';
		var prevSelected = '';
		var indexArr = new Array();
		function addIndexElement(name) {
			var listElement = document.createElement('li');
			listElement.id = 'name-'+name;
			var divEl = document.createElement('div');
			listElement.appendChild(divEl);

			var editImg = new Image();
			editImg.src = "../energie/images/file_edit_16.gif";
			editImg.alt = "Edit";
			editImg.onclick = function() {	openEditIndex(name) };
			editImg.width = 14;
			editImg.height = 14;
			editImg.align = 'left';
			editImg.style.cursor = 'pointer';
			editImg.style.paddingRight = '2px';
			divEl.appendChild(editImg);

			var deleteImg = new Image();
			deleteImg.src = "../images/trash.gif";
			deleteImg.alt = "Delete";
			deleteImg.onclick = function() {	deleteDocumentField(name);
												new Effect.Fade(listElement) }; 
			deleteImg.width = 16;
			deleteImg.height = 16;
			deleteImg.align = 'right';
			deleteImg.style.cursor = 'pointer';
			divEl.appendChild(deleteImg);

			var subDiv = document.createElement('div');
			subDiv.id = 'text-'+name;
			divEl.appendChild(subDiv);
			subDiv.appendChild(document.createTextNode(name));
		
			listElement.style.display = 'none';
			if(getEl('oldname-'+name)) {
				getEl('currentIndices').insertBefore(listElement,getEl('oldname-'+name));
				getEl('oldname-'+name).parentNode.removeChild(getEl('oldname-'+name));
			} else {
				getEl('currentIndices').appendChild(listElement);
			}

			new Effect.Appear(listElement);
			reInitialize();

			getEl('nextPage').disabled = false;
		}

		function onEnter(e,t) {
			var evt = (e) ? e : event;
		    var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
			if(!t) {
				t = this;
			} 
			if(charCode == 13) {
				if(t.id == 'documentName') {
					addDocumentType();		
				} else if(t.id == 'indexName') {
					addDocumentField();
				} else {
					saveEditIndex(prevSelected);		
				}
			}
		}
		function validChar(e) {
			var evt = (e) ? e : event;
		    var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;

			var character = String.fromCharCode(charCode);
			if(character == '/') {
				return false;
			}

		}
		
		function allowKeys(evt) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
        var pool = "1234567890 ";
        pool += "abcdefghijklmnopqrstuvwxyz";
        pool += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var character = String.fromCharCode(charCode);

        if( (pool.indexOf(character) != -1)
                || (charCode == 8) || (charCode == 9) || (charCode == 37) || (charCode == 39) )
            return true;
        else
            return false;
    }
	</script>
	<style>
		li {
			text-align: left;
			background-color: #ebebeb;
			border: 2px;
			border-style: outset; 
			border-color: #003b6f; 
			margin: 5px; 
			list-style-type: none;
			padding-left: 2px;
			width: 250px;
			height: 22px;
			cursor: move;
			vertical-align: top;
		}

		td.systemLabel { 
			text-align: left; 
			font-weight: bold;
		}

		select {
			width: 200px;
			position: relative;
			left: 0px;
			top:-140px;
		}

		table.systemSelectMenu {
			width: 100%;
		}
	</style>	
</head>
<body>
	<div class="mainDiv" style='height:500px'>
		<div class="mainTitle">
			<span>Document Type Wizard</span>
		</div>

 
		<div id="wizard" style="height:430px; width:750px; padding-top:35px">
			<div id="page1" style="padding-left:10%;height:400px">
				<table class="systemSelectMenu">
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdAdd' type="radio" checked="checked" name="mappingType" value="add"
								onclick='getEl("editDoc").disabled=true;'>
							<label style="cursor:help" 
								onclick="requestHelp(this,'documentAdd','english')" 
								for="mappingType">Add Document Type</label>
						</td>
					</tr>
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdEdit' type="radio" name="mappingType" 
								value='edit' <?php echo $disabled; ?>
								onclick='getEl("editDoc").disabled=false;'>
							<label style="cursor:help" 
								onclick="requestHelp(this,'documentEdit','english')" 
								for="mappingType">Edit Document Type</label>
						</td>
					</tr>
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdDisable' type="radio" name="mappingType" 
								value="disable" <?php echo $disabled; ?>
								onclick='getEl("editDoc").disabled=false;'>
							<label style="cursor:help" 
								onclick="requestHelp(this,'documentDisable','english')" 
								for="mappingType">Enable/Disable Document Type</label>
						</td>
					</tr>
					<!--
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdDelete' type="radio" name="mappingType" 
								value="delete" <?php echo $disabled; ?>
								onclick='getEl("editDoc").disabled=false;'>
							<label style="cursor:help" 
								onclick="requestHelp(this,'documentDelete','english')" 
								for="mappingType">Delete Document Type</label>
						</td>
					</tr>
					-->
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdCopy' type="radio" name="mappingType" 
								value="copy" <?php echo $disabled; ?>
								onclick='getEl("editDoc").disabled=false;'>
							<label style="cursor:help" 
								onclick="requestHelp(this,'documentCopy','english')" 
								for="mappingType">Copy Document Type</label>
						</td>
					</tr>
				</table>
					<?php if(sizeof($docArr)): ?>
					<div>	
						<select id='editDoc' size='10' disabled>
							<?php foreach($docArr AS $k => $value): ?>
								<option value='<?php echo $k; ?>'><?php echo $value; ?></option>
							<?php endforeach; ?> 
						</select>
					</div>
					<?php endif; ?>
			</div>
			<div id="page2" style="padding-left:10%;height:400px" class='hideDiv'>
				<span style='font-weight:bold;cursor:help'
					onclick="requestHelp(this,'documentName','english')">Document Name</span>
				<input type='text' id='documentName' name='documentName' onkeypress='onEnter(event,this); return allowKeys(event);'/>
				<input type='button' id="addDocType" name='B1' value='ADD' onclick='addDocumentType()'/>
				<div style='padding-top:25px'>
					<div style='float:left'> 
						<table>
							<tr>
								<th colspan='3'>Add Index Name</th>
							</tr>
							<tr>
								<th	style="cursor:help"
									onclick="requestHelp(this,'documentIndexName','english')">Name</th>
								<td>
									<input disabled 
										type='text' 
										id='indexName' 
										name='indexName' 
										onkeypress='onEnter(event,this)'
									/>
								</td>
								<td>
									<input disabled 
										type='button' 
										id='B2' 
										name='B2' 
										value='ADD' 
										onclick='addDocumentField()'
									/>
								</td>
							</tr>
						</table>
					</div>
					<div style='float:right;padding-right:5px'>
						<fieldset style='width:325px;height:325px'>
							<legend id='leg'>Index List</legend>
							<ul id='currentIndices'>
							</ul>
						</fieldset>
					</div>
				</div>
			</div>
			<div id="page3" style="padding-left:10%;height:400px" class='hideDiv'>
				<table class="systemSelectMenu">
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdEnableDoc' type="radio" checked="checked" name="confirmDisable" value='enable'>
							<label for="confirmDisable"
								style="cursor:help"
								onclick="requestHelp(this,'documentEnableDoc','english')">Enable Document Type</label>
						</td>
					</tr>
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdDisableDoc' type="radio" name="confirmDisable" value='disable'>
							<label for="confirmDisable"
								style="cursor:help"
								onclick="requestHelp(this,'documentDisableDoc','english')">Disable Document Type</label>
						</td>
					</tr>
				</table>
			</div >
			<div id="page4" style="padding-left:10%;height:400px" class='hideDiv'>
				<div id="confirmMessage" style="font-weight:bold"></div>
				<table class="systemSelectMenu">
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdYesDeleteDoc' type="radio" name="confirmDelete" value='yes'>
							<label for="confirmDelete"
								style="cursor:help"
								onclick="requestHelp(this,'documentDeleteYes','english')">Yes</label>
						</td>
					</tr>
					<tr>
						<td class="systemLabel" style="padding-top:35px">
							<input id='rdNoDeleteDoc' type="radio" checked="checked" name="confirmDelete" value='no'>
							<label for="confirmDelete"
								style="cursor:help"
								onclick="requestHelp(this,'documentDeleteNo','english')">No</label>
						</td>
					</tr>
				</table>
			</div>
			<div id="page5" style="text-align:center;height:400px" class='hideDiv'>
				All changes were successfully saved	
			</div>
			<div id="buttonControl">
  				<div style="float:left;width:400px;text-align:right">
   					<span id="errorMsg" class="error"></span>
  				</div>
  				<div style="float:right;width:200px">
					<input id="prevPage" type="button" name="back" value="Back" disabled>
					<input id="nextPage" type="button" name="next" value="Next" 
						onclick='documentController(2)'>
					<input id="cancelWizard" type="button" name="cancel" value="Cancel" onclick="cancelWizard()">
  				</div>
			</div>
		</div>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
