<?php
// $Id: viewDocumentFilters.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$tableTitle = "Show/Hide Document Types";

	$type = "filter";
	if(isSet($_GET['type']) && $_GET['type'] == 1) {
		$type = "pub_filter";
	}

	$sArr = array('document_type_name','id','enable');
	$oArr = array('document_type_name' => 'ASC');
	$docList = getTableInfo($db_object,'document_type_defs',$sArr,array(),'getAssoc',$oArr);
	uksort($docList,'strnatcasecmp');
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>View Document</title>
<script type="text/javascript" src="viewDocuments.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript">
	var cab = "";
	var type = "<?php echo $type; ?>";
	function selectCabinet() {
		var selBox = getEl('cabList');
		removeDefault(selBox);
		clearDiv(getEl('errMsg'));
		getEl('docTypeDiv').style.display = "block";

		cab = selBox.options[selBox.selectedIndex].value;		

		var xmlDoc = createDOMDoc();
		var root = xmlDoc.createElement('ROOT');
		xmlDoc.appendChild(root);
		createKeyAndValue(xmlDoc,root,'function','xmlGetDocumentFilters');
		createKeyAndValue(xmlDoc,root,'cab',cab);
		createKeyAndValue(xmlDoc,root,'type',type);
		
		postXML(domToString(xmlDoc));
	}

	function fillDocumentFilter(XML) {
		toggleFilterCheckBoxes();
		var filterList = XML.getElementsByTagName('FILTER');
		if(filterList.length > 0) {
			for(var i=0;i<filterList.length;i++) {
				var id = filterList[i].firstChild.nodeValue;		
				getEl('check-'+id).checked = true;
			}
		}
	}

	function toggleFilterCheckBoxes() {
		var checkList = document.getElementsByTagName('input');
		for(var i=0;i<checkList.length;i++) {
			if(checkList[i].type == "checkbox") {
				checkList[i].checked = false;
			}
		}
	}

	function addDocumentFilter() {
		var xmlDoc = createDOMDoc();
		var root = xmlDoc.createElement('ROOT');
		xmlDoc.appendChild(root);
		createKeyAndValue(xmlDoc,root,'function','xmlAddDocumentFilter');
		createKeyAndValue(xmlDoc,root,'cab',cab);
		createKeyAndValue(xmlDoc,root,'type',type);
		
		var ct = 1;
		var checkList = document.getElementsByTagName('input');
		for(var i=0;i<checkList.length;i++) {
			if(checkList[i].type == "checkbox" && checkList[i].checked) {
				createKeyAndValue(xmlDoc,root,'filter-'+ct,checkList[i].value);
				ct++;
			}
		}
		postXML(domToString(xmlDoc));
	}
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<style type="text/css">
	div.cabDiv {
		padding-top	: 10px;
		height		: 35px;
	}

	select.cabList {
		width: 250px;
	}
	
	div.docDiv {
		overflow-x	: hidden;
		overflow-y	: scroll;
		height		: 250px;
		width		: 250px;
		margin-right: auto;
		margin-left	: auto;
		border		: 1px solid #ebebeb;
	}

	div#docTypeDiv {
		display		: none;
	}

	div.actionDiv {
		text-align	: right;
		width		: 250px;
		margin-right: auto;
		margin-left	: auto;
		padding-top	: 10px;
		height		: 35px;
	}
</style>
</head>
<body>
	<div class="mainDiv">
		<div class="mainTitle">
			<span><?php echo $tableTitle ?></span>
		</div>
		<div id="cabDiv" class="cabDiv">
			<select id="cabList" 
				class="cabList"
				name="cabList" 
				onchange="selectCabinet()">
				<option value="__default">Choose a Cabinet</option>
				<?php foreach($user->cabArr AS $key => $value): ?>
				<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div id="docTypeDiv">
			<div style="font-weight: bold">Document Types</div>
			<div id="docDiv" class="docDiv">
				<table style="width:100%">
				<?php foreach($docList AS $name => $info): ?>
					<tr>
						<td style="width:25px">
							<input type="checkbox" 
								id="check-<?php echo $info['id'];?>" 
								name="check-<?php echo $info['id'];?>" 
								value="<?php echo $info['id']; ?>" 
							/>
						</td>
						<td style="text-align: left">
							<span><?php echo $name; ?><span>
							<span style="font-style:italic"><?php echo " ".(($info['enable']) ? "" : "(disabled)"); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
			</div>
		</div>
		<div id="actionDiv" class="actionDiv">
			<span id="errMsg" class="error"></span>
			<input type="button" 
				name="btn1" 
				value="Save" 
				onclick="addDocumentFilter()" 
			/>
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
