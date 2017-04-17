<?php 
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/delegate.php';
include_once '../lib/inbox.php';
include_once '../lib/filter.php';
include_once '../delegation/delegation.php';
if($logged_in == 1 && strcmp($user->username,"") != 0) {

	$usrSett = new UsrSettings($user->username,$user->db_name);
	$inboxView = $usrSett->get('inboxView');
	if($inboxView == NULL) {
		$inboxView = 0;
	}

	$res_per_pageOrig = 0;
	if(!isSet($_GET['results'])) {
		$res_per_page = $usrSett->get( 'results_per_page' );
		$res_per_pageOrig = $res_per_page;
		if(!$res_per_page) {
			$res_per_page = 25;
		}
	} else {
		$res_per_page = $_GET['results'];
	}
	if ($res_per_pageOrig != $res_per_page) {
		$usrSett->set('results_per_page',$res_per_page);
	}	
	$cur_page = (isSet($_GET['page'])) ? $_GET['page'] : 1;

	$inboxPath = $user->getRootPath()."/personalInbox/";
	$delegateObj = new delegate($inboxPath,$user->username,$db_object,1,$cur_page,$res_per_page);
	$delegateList = $delegateObj->getDelegateList();
	$count = 1;
	if(isSet($_GET['deleteInboxDelegation'])) {
		$delegateObj->deleteFromInboxDelegation($_POST['delFolder']);
		$delegateObj = new delegate($inboxPath,$user->username,$db_object);
	} else if(isSet($_GET['moveInboxDelegation'])) {
		$delegateObj->moveFromInbox($user,$db_doc,$_POST['delFolder']);	
		$delegateObj = new delegate($inboxPath,$user->username,$db_object);
	}

	$resArr = array('10','25','50','75','100');
	$ct = $delegateObj->countDelegateList();
	$pages = ($ct) ? ceil($ct / $res_per_page) : 0;
	$delegateList = $delegateObj->getDelegateList();

	//need list of usernames in department
	$DO_user = DataObject::factory('users',$db_doc);
	$userlist = $DO_user->getUsersByDepartments(array($user->db_name));
	$userlistStr = implode(',',$userlist);
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Delegated Files</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script src="../delegation/wz_dragdrop.js" type="text/javascript"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/behaviour.js"></script>
<script type="text/javascript" src="../lib/inbox1.js"></script>
<script type="text/javascript" src="delegation.js"></script>
<script type="text/javascript">	
	var inboxView = "<?php echo $inboxView; ?>";
	var folderOpened = false;
	var totalPages = "<?php echo $pages; ?>";
	var selRow = "";
	var prevID;
	var bgColor;
	var funcPtr;
	var nameVal,ext,nameChange,delegatedVal,statusVal,commentsVal,owner,fname;
	var userlistStr = '<?php echo $userlistStr; ?>';
	var userlist = userlistStr.split(','); 

	parent.document.getElementById('mainFrameSet').setAttribute('cols','100%,*');
    parent.sideFrame.window.location = '../energie/left_blue_search.php';

	function createKeyAndValue(xmlDoc,root,key,value) {
		var entry = xmlDoc.createElement('ENTRY');
		root.appendChild(entry);

		var k = xmlDoc.createElement('KEY');
		k.appendChild(xmlDoc.createTextNode(key));
		entry.appendChild(k);
		
		var v = xmlDoc.createElement('VALUE');
		v.appendChild(xmlDoc.createTextNode(value));
		entry.appendChild(v);
	}

	function someSelected () {
		var someSelect = false;
		var i = 1;
		while(el = getEl('fileCheck:'+i)) {
			if(el.checked == true) {
				someSelect = true;
				break;
			}
			i++;
		}
		return someSelect;
	}
		
	function receiveXML(req) {
		//alert(req.responseText);
		if(req.responseXML) {
			var XML = req.responseXML;
			var mess = XML.getElementsByTagName('MESSAGE');
			if(mess.length > 0) {
				var msg = mess[0].firstChild.nodeValue;
				var errMsg = getEl('errMsg');
				clearDiv(errMsg);
				errMsg.appendChild(document.createTextNode(msg));
			}
			nameVal = nameChange;
			if(mess[0].getAttribute('restore') == '1') {
				restoreDelegatedItem(prevID);
			}
		}
	}

	function postXML(xmlStr) {
		//alert(xmlStr);
		var newAjax = new Ajax.Request( 'delegationPostRequest.php',
									{   method: 'post',
										postBody: xmlStr,
										onComplete: receiveXML} );	
	}

	function saveDelegatedFile(ID) {
		var xmlDoc = createDOMDoc();
		var root = xmlDoc.createElement('ROOT');
		xmlDoc.appendChild(root);
		createKeyAndValue(xmlDoc,root,'function','updateDelegatedFile');

		if(getEl('fileCheck:'+ID)) {
			var delegateID = getEl('fileCheck:'+ID).value;
			createKeyAndValue(xmlDoc,root,'delegateID',delegateID);
		}
		
		nameChange = getEl('edit-name-'+ID).value;
		if(ext) {
			tmpName = nameChange+'.'+ext;
		} else {
			tmpName = nameChange;
		}
		createKeyAndValue(xmlDoc,root,'name',tmpName);

		var delTo = getEl('edit-delegatedTo-'+ID);
		delegatedVal = delTo.options[delTo.selectedIndex].value;
		createKeyAndValue(xmlDoc,root,'delegatedTo',delegatedVal);
		
		var st = getEl('edit-status-'+ID);
		statusVal = st.options[st.selectedIndex].value;
		createKeyAndValue(xmlDoc,root,'status',statusVal);
		
		commentsVal = getEl('edit-comments-'+ID).value;
		createKeyAndValue(xmlDoc,root,'comments',commentsVal);

		postXML(domToString(xmlDoc));
	}

	function onEnter(e) {
		var evt = (e) ? e : event;
		var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;

		if(charCode == 13) {
			saveDelegatedFile(prevID);	
			return false;
		}
	}

	function submitPage(e) {
		var evt = (e) ? e : event;
		var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
		var pool = "1234567890";
		if(code == 13) {
			total = parseInt(getEl('newPage').value);
			changePage(total);
			return true;
		}

		var character = String.fromCharCode(code);
		if(pool.indexOf(character) != -1
			|| (code == 8) || (code == 37)
			|| (code == 39) || (code == 46)){
			return true;
		}
		return false;
	}

  function maximizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','260,*');
	parent.searchPanel.getEl('minView').style.visibility='visible';
	getEl('maxView').style.visibility='hidden';
  }

function minimizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','0,*');
	parent.mainFrame.getEl('maxView').style.visibility='visible';
}
function toggleInboxView(toggle) {
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', '../secure/inboxMove.php?inboxView='+toggle, true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseText) {
				if(toggle) {	
					minimizeView();
				} else {
					maximizeView();
				}
			}
		}
	};
}

function adjustInboxView() {
	if(inboxView == 1) {
		minimizeView();
	} else {
		maximizeView();
	}	
}
</script>
<style type="text/css">
	.delegatedTable {
		font-size: 9pt;
		margin-left: auto; 
		margin-right: auto;		
		background-color: #ebebeb;
		border-collapse : collapse;
		width: 100%;
	}

	.delegatedTable tr{
		cursor: pointer;
	}
	
	.delegatedTable td {
		text-align: center;
		border: 1px solid #ffffff;
	}

	.delegatedTable td,th {
		white-space : nowrap;
	}

	.delegatedFolderTable {
		font-size		: 9pt;
		margin-left		: auto; 
		margin-right	: auto;		
		background-color: #ebebeb;
		border-collapse : collapse;
		width			: 100%;
	}

	.delegatedFolderTable tr {
		cursor: pointer;
	}
	
	.delegatedFolderTable td {
		text-align: center;
		border: 0px solid #ffffff;
	}

	div.addNewFolderDiv {	
		position: absolute; 
		top: 10%; 
		left: 10%; 
		background: #ffffff; 
		display: none 
	}
	
	iframe.addNewFolder { 
		width: 500px; 
		height: 250px 
	}
	
	.headerImg {
		font-size: 9pt;
		cursor: pointer;
		padding-left: 5px;
        padding-right: 5px;
		background-color: #ebebeb;	
	}

	.headerImgSelect {
		font-weight: bold;
		font-size: 9pt;
		color: white;
		cursor: pointer;
		padding-left: 5px;
        padding-right: 5px;
		background-color: #003b6f;	
	}
  div.addNewActionDiv {	display: none;
						padding: 10px;
						background: #ffffff }
</style>
</head>
<body onload="adjustInboxView()" style="margin:0px">
	<div style="text-align:left;height:20px">
		<img src="../images/right.GIF" 
			id="maxView"
			style="cursor:pointer;vertical-align:middle" 
			title="Maximize" 
			alt="Maximize"
			onclick="toggleInboxView(0)"
		/>
	</div>
<div id="addNewDocumentDiv" class="addNewActionDiv" style="text-align:center">
<?php displayAddDocument() ?>
</div>
	<div style="width:100%;float:left">
		<div style="float:left;padding-left:10%;width:70%;text-align:center">
			<table style="border: 0; margin-left: auto; margin-right: auto" cellspacing="1" cellpadding="0">
				<tr>
					<td>
			<span class="headerImg" 
				onclick="window.location='../secure/inbox1.php'">Public</span>
					</td>
					<td>
			<span class="headerImg"
				onclick="window.location='../secure/inbox1.php?type=1'">Personal</span>
					</td>
					<td>
			<span class="headerImgSelect" 
				onclick="window.location='../delegation/viewDelegation.php'">Delegated</span>
					</td>
				</tr>
			</table>
		</div>
		<div style="float:right;width:15%;text-align:right">
			<select id="results" name="results" onchange="changeResults()" style="font-size:9pt">
				<?php foreach($resArr AS $num): ?>
					<?php if($num == $res_per_page): ?>
						<option selected="selected" value="<?php echo $num; ?>"><?php echo $num; ?></option>
					<?php else: ?>
						<option value="<?php echo $num; ?>"><?php echo $num; ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php if($pages > 1): ?>
	<div style="width:100%;height:30px">
		<div id="paging" style="text-align:center">
			<table align="center" style="font-size:9pt">
				<tr>
					<td>
						<img style="cursor:pointer;vertical-align:middle" 
							alt="First"
							title="First"
							width="20" 
							src="../energie/images/begin_button.gif" 
							onclick="changePage('1')"
						/>
					</td>
					<td>
						<img style="cursor:pointer;vertical-align:middle" 
							alt="Previous"
							title="Previous"
							width="20" 
							src="../energie/images/back_button.gif" 
							onclick="changePage('<?php echo ($cur_page - 1); ?>')"
						/>
					</td>
					<td>
						<input style="height:15px;font-size:9pt" 
							id="newPage"
							type="text" 
							size="2" 
							value="<?php echo $cur_page; ?>" 
							onkeypress="return submitPage(event)"
						/>
						<input id="pageNum" 
							type="hidden" 
							value="<?php echo $cur_page; ?>" 
						/>
						<span style="vertical-align:middle"><?php echo " of ".$pages; ?></span>
					</td>
					<td>
						<img style="cursor:pointer;vertical-align:middle" 
							alt="Next"
							title="Next"
							width="20" 
							src="../energie/images/next_button.gif" 
							onclick="changePage('<?php echo ($cur_page + 1); ?>')"
						/>
					</td>
					<td>
						<img style="cursor:pointer;vertical-align:middle" 
							alt="Last"
							title="Last"
							width="20" 
							src="../energie/images/end_button.gif" 
							onclick="changePage('<?php echo $pages; ?>')"
						/>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php endif; ?>
	<div id="addNewFolderDiv" class="addNewFolderDiv">
		<iframe id="addNewFolder" class="addNewFolder" src="../lib/IEByPass.htm"></iframe>
	</div>
	<div style="float:left;width:100%;height:25px">
		<div id="errMsg" class="error" style="float:left;width:80%;text-align:center"></div>
		<div style="float:right;width:15%;text-align:right">
			<input type="button"
				id="removeDelegation" 
				name="removeDelegation"
				value="Remove Delegation" 
				style="font-size:9pt"
				onclick="removeInboxDelegation()" 
			/>
		</div>
	</div>
	<div style="float:left;width:100%">
	<form name="filename" method="post" action="viewDelegation.php" style="margin:0px">
	<table class="delegatedTable" cellspacing="0" cellpadding="0" style="padding:0px;margin:0px;font-size:9pt">
	<tr class="tableheads" style="font-size:9pt;cursor:default">
		<th style="width:3%">&nbsp;</th>
		<th style="width:3%">&nbsp;</th>
		<th style="width:3%">
			<input type="checkbox" name="selectdelfolder" onclick="toggleAllDelegation(this)" />
		</th>
		<th>Name</th>
		<th>Delegated by</th>
		<th>Delegated to</th>
		<th>Date Delegated</th>
		<th>Status</th>
		<th>Comments</th>
		<th>Info</th>
	</tr>
	<?php foreach($delegateList AS $id => $folderArr):
			$owner = $folderArr[0]['delegate_owner'];
			$folder = $folderArr[0]['folder'];
			$uname = $folderArr[0]['delegate_username'];
			$time = $folderArr[0]['dtime'];
			$comments = $folderArr[0]['comments'];
			$status = $folderArr[0]['status'];
			$list_id = $folderArr[0]['list_id'];
		?>
		<?php if($folder != ""): ?>
			<tr onmouseover="this.style.backgroundColor='#888888'" 
				onmouseout="this.style.backgroundColor='#ebebeb'" >
				<td style="width:3%" 
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')"
				>
					<img id="file-del<?php echo $count; ?>"
						src="../images/foldr_16.gif"
						alt="Folder"
						title="Folder"
						border="0"
					/>
				</td>
				<td style="width:3%">
					<img id="edit-del<?php echo $count; ?>" 
						src="../energie/images/file_edit_16.gif"
						alt="Edit Folder"
						title="Edit Folder"
						height="16"
						border="0"
						onclick="editDelegatedFile('<?php echo $count; ?>','folder')"
					/>
				</td>
				<td style="width:3%;padding:2px">
					<input type="checkbox" 
						id="fileCheck:<?php echo $count; ?>" 
						value="<?php echo $list_id; ?>" 
						name="delFolder[]" 
						onclick="selectDelegatedFolder('<?php echo $owner; ?>',
														'<?php echo $folder; ?>',
														'<?php echo $count; ?>')"
					/>
				</td>
				<td style="width:15%;text-align:left;text-indent:5px"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="name-del<?php echo $count; ?>"><?php echo $folder; ?></span>
				</td>
				<td style="width:10%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="delegatedBy-del<?php echo $count; ?>"><?php echo $owner; ?></span>
				</td>
				<td style="width:10%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="delegatedTo-del<?php echo $count; ?>"><?php echo $uname; ?></span>
				</td>
				<td style="width:20%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="date-del<?php echo $count; ?>"><?php echo str_replace(".000000","",$time); ?></span>
				</td>
				<td style="width:6%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="status-del<?php echo $count; ?>"><?php echo $status; ?></span>
				</td>
				<td style="width:20%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="comments-del<?php echo $count; ?>"><?php echo h($comments); ?></span>
				</td>
				<td style="width:10%"
					onclick="toggleDelegatedFolder('<?php echo $owner; ?>',
													'<?php echo $folder; ?>')">
					<span id="info-del<?php echo $count++; ?>"><?php echo sizeof($folderArr)." File(s)"; ?></span>
				</td>
			</tr>
			<tr style="height:0px">
				<td colspan="10">
					<div id="<?php echo $owner.'-'.$folder; ?>" style="display:none;width:100%;padding:0px;margin:0px">
						<table class="delegatedFolderTable" cellspacing="0" cellpadding="0" style="padding:0px;margin:0px">
		<?php endif; ?>
		<?php foreach($folderArr AS $info): ?>
		<?php displayInboxDelegationRow($info,$count,$folder); ?>
		<?php endforeach; ?>
		<?php if($folder != ""): ?>
						</table>
					</div>
				</td>
			</tr>
		<?php endif; ?>
	<?php endforeach; ?>
	</table>
	</form>
	</div>
<script type="text/javascript">
<!--
	SET_DHTML(CURSOR_MOVE);
//-->
</script>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
