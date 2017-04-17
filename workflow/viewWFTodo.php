<?php
// $Id: viewWFTodo.php 14304 2011-03-21 17:43:12Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../departments/depfuncs.php';
include_once '../lib/email.php';
include_once '../lib/settings.php';
include_once '../departments/depfuncs.php';
include_once 'workflow.php';
include_once 'node.inc.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0)
{
	$Grab = 'Grab';

	if($user->restore) {
		$user->access = array();
		$user->cabArr = array();
		$user->fillUser();
		$user->restore = 0;
?>
<script>
	if(parent.topMenuFrame.document.getElementById('userObjTD')) {
		parent.topMenuFrame.restoreDefault(0);	
	}
</script>
<?php
		setSessionUser($user);
	}

	$reassignArr = array();
	if(isSet($_POST['reassign'])) {
		$reassignArr = $_POST['reassign'];
	}
	if(isset($_GET['username'])) {
		$uname = $_GET['username'];
	} else {
		$uname = $user->username;
	}
	$todoList = array();
	$db_doc = getDbObject('docutron');
	if(!$user->todoID) {
		$user->todoID = 0;
	}
	if(isset($_GET['delete']) and $_GET['delete']) {
		if($reassignArr) {
			$todoList =  getTableInfo($db_doc,'wf_todo',array(),array(),'queryAll',array('username'=>'ASC', 'id'=>'ASC'));
			foreach( $todoList AS $todoArr ) {
				$todoID = $todoArr['id'];
				if( in_array( $todoID, $reassignArr ) ) {
					deleteTableInfo($db_doc, 'wf_todo', array('id' => (int) $todoID));
	
					$clientDB = getDbObject( $todoArr['department'] );
					$wf_document_id = $todoArr['wf_document_id'];
					
					$sArr = array('cab','doc_id');
					$wArr = array('id'=>(int)$wf_document_id);
					$auditInfo = getTableInfo($clientDB,'wf_documents',$sArr,$wArr,'queryRow');
					$foldername = getCabIndexArr($auditInfo['doc_id'],$auditInfo['cab'], $clientDB);
					$info = "Cabinet: ".$auditInfo['cab']." Folder: ".implode(" ",$foldername);
					$action = "Workflow deleted";
					$insertArr = array(	"username"	=> $user->username,
										"datetime"	=> $user->getTime(),
										"info"		=> $info,
										"action"	=>$action );
					$res = $clientDB->extended->autoExecute('audit',$insertArr);
					dbErr($res);
					deleteTableInfo($clientDB, 'wf_documents', array('id' => (int) $wf_document_id));
				}
			}
		}
	} else if(isset($_GET['assign']) and $_GET['assign']) {
		$assignee = $_POST['assignee'];
		if($reassignArr) {
			foreach( $reassignArr AS $todoID ) {
				$selArr = array('username','department','wf_document_id');
				$todoInfo = getTableInfo($db_doc,'wf_todo',$selArr,array('id'=>(int)$todoID),'queryRow');
                $clientDB = getDbObject( $todoInfo['department'] );
			
				$wf_document_id = $todoInfo['wf_document_id'];
                $tableArr = array('wf_documents','wf_defs');
                $selArr = array("state","node_id");
                $whereArr = array(  "wf_documents.id=".(int)$wf_document_id,
                                    "state_wf_def_id=wf_defs.id" );
                $auditInfo = getTableInfo($clientDB,$tableArr,$selArr,$whereArr,'queryRow');
                $insertArr = array( "wf_document_id"    => (int)$wf_document_id,
                                    "wf_node_id"        => (int)$auditInfo['node_id'],
                                    "username"          => $assignee,
                                    "date_time"         => $user->getTime(),
                                    "action"            => "Workflow reassigned",
                                    "state"             => (int)$auditInfo['state']);
                $res = $clientDB->extended->autoExecute('wf_history',$insertArr);
				dbErr($res);
                updateTableInfo($db_doc, 'wf_todo', array('username' => $assignee), array('id' => (int) $todoID));	
			}
		}
	}

	$showGrab = 0;
	$gblStt = new GblStt ($user->db_name,$db_doc);
	$userStt = new Usrsettings ($user->username, $user->db_name);
	if ($gblStt->get('wfGrab') == 1) {
		$showGrab = 1;
	} else {
		if ($userStt->get('wfGrab') == 1) {
			$showGrab = 1;
		}
	}
	$header = "Tab/Document";
echo<<<ENERGIE
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Workflow Todolist</title>
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css" />
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/calendar.css" />
	<style type="text/css">
		th { 
			padding-left: 5px;
			padding-right: 5px;
		}

		td { 
			padding-left: 5px;
			padding-right: 5px;
		}
	</style>
	<script type="text/javascript" src="../lib/prototype.js"></script> 
	<script type="text/javascript" src="../lib/behaviour.js"></script> 
	<script type="text/javascript" src="../lib/windowTitle.js"></script>
	<script type="text/javascript" src="../lib/settings2.js"></script>
	<script type="text/javascript" src="../lib/calendar.js"></script>
	<script type="text/javascript">
    var orderField = "";
	var orderDirection = "";
	var total = 0;
	var page = 1;
	var username = "$uname";
    var tID = $user->todoID;
	var numOfPriorities = 0;
	var workflowID = 0;
	var todoIDs = new Array();
	var todoInfo = new Array();
	var userArr = new Array();
	var todoUser = "$uname";
	var filterName = "";
	var filterSearch = "";
	var filterExact = 0;
	var ifGrab = $showGrab;

	var headerArr = new Array(	"Grab","Priority","Notes", "Date Due", "Date Notified","Department","Cabinet",
								"Folder","$header","Workflow","Node Name","Node Type","Select");
	setTitle(1, "Workflow");
  	parent.document.getElementById('afterMenu').setAttribute('cols','260,*');
	parent.searchPanel.location.href = "../energie/searchPanel.php";
	parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
	parent.sideFrame.window.location = '../energie/left_blue_search.php';

	function setMessage(mess) {
		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		eMsg.appendChild(document.createTextNode(mess));
	}

	function showRow() {
		this.style.backgroundColor = '#6a78af';
	}

	function hideRow() {
		if("todo-"+tID != this.id) {
			this.style.backgroundColor = '#ffffff';
		}
	}

	function getLink(link) {
		top.window.location = link;	
	}

 	function allowDigi(evt) {
		evt = (evt) ? evt : event;
        var code = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		if( code == 34 ) { //quotes break the search
			return false;
		}
    	if ( code == 13 ) { // is enter or backspace key
			applyFilter();
		}
        return true;
	}

	function disableElements() {
		$('searchButton').disabled = true;
		$('actionButton').disabled = true;
		$('actionButton').onclick = function() {};
		$('reassignList').disabled = true;

		$('deleteWorkflow').onclick = function() {};
		$('editNotes').onclick = function() {};
		if(el = $('userTodo')) {
			el.disabled = true;
		}
	}

	function userConfirm() {
		message = "This will delete the selected workflow item(s)";
		answer = window.confirm(message);
		if(answer == true) {
			return true;
		}
		return false;
	}

	function enableEditElements() {
        var chk = $('todoTable').getElementsByTagName('input');
		var todoID = 0;
		for(var i=0; i<chk.length;i++) {
			if(chk[i].checked == true && chk[i].type == 'checkbox') {
				todoID = chk[i].value;
				break;
			}	
		}

		if(todoID) {
			//for notes textbox
			var elName = 'notes-todo-'+todoID;
			var notesDiv = $(elName);
			notesDiv.onclick = function() { };
			var notes = notesDiv.firstChild.nodeValue;
			removeElementsChildren(notesDiv);
			var txtBox = document.createElement('input');
			txtBox.type = 'text';
			txtBox.id = 'text-' + elName;
			txtBox.size = 20;
			txtBox.value = notes;
			txtBox.onkeypress = submitNotes;
			notesDiv.appendChild(txtBox);
			txtBox.select();

			//for hidden textbox with original value
			var hiddenTxt = document.createElement('hidden');
			hiddenTxt.id = 'hidden-' + todoID;
			hiddenTxt.value = notes;
			notesDiv.appendChild(hiddenTxt);

			//for cancel button
			var cancelButton = new Image(15, 15);
			cancelButton.id = 'cancelButton-' + todoID;
			cancelButton.setAttribute("todoID", todoID);
			cancelButton.src = "../energie/images/cancl_16.gif";
			cancelButton.onclick = cancelClick;
			notesDiv.appendChild(cancelButton);
		} else {
			var eMsg = $('errMsg');
			removeElementsChildren(eMsg);
			eMsg.appendChild(document.createTextNode('Must have atleast one workflow selected'));
		}
	}

	function cancelClick(e) {
		var todoID = this.getAttribute("todoID");
		var notesDiv = this.parentNode;
		var notesNode = document.createTextNode( $('hidden-'+todoID).value );
		removeElementsChildren(notesDiv);
		notesDiv.appendChild(notesNode);
		notesDiv.onclick = function() {getLink(notesDiv.getAttribute('wfLink')) };
	}

	function submitNotes(e) {
		evt = (e) ? e : event;
		var charCode = (evt.charCode) ? evt.charCode :
			((evt.which) ? evt.which : evt.keyCode);
		if (charCode == 13 || charCode == 3) {
			updateNotes(this.id);
		}
		return true;
	}

	function updateNotes(id) {
		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		document.body.style.cursor = 'wait';
		eMsg.appendChild(document.createTextNode('Please Wait....'));

		var notesDiv = $(id).parentNode;
		var notesNode = document.createTextNode($(id).value);
		
		var searchStr = "";
		if($('searchText')) {
			searchStr = $('searchText').value;
		} else if($('searchSelect')) {
			searchStr = $('searchSelect').value;
		}
		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "updateTodoNotes",
						"todoID" : notesDiv.getAttribute("wfTodoID"),
						"note" : $(id).value }; 
		postXML(xmlArr);
	}

	function setTodoNotes(XML,todoID) {
		document.body.style.cursor = 'default';
		removeElementsChildren($('errMsg'));

		notesDiv = $('text-notes-todo-'+todoID).parentNode;
		notesNode = document.createTextNode($('text-notes-todo-'+todoID).value);
		removeElementsChildren(notesDiv);
		notesDiv.appendChild(notesNode);
		notesDiv.onclick = function() {getLink(notesDiv.getAttribute('wfLink')) };
		$('errMsg').appendChild(document.createTextNode('Notes successfully set'));
	}

	function updateDueDate(WFTodoID) {
		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		document.body.style.cursor = 'wait';
		eMsg.appendChild(document.createTextNode('Please Wait....'));

		var elementID = 'dateInput-' + WFTodoID;
		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "updateTodoDueDate",
						"todoID" : WFTodoID,
						"dueDate" : $(elementID).value }; 
		postXML(xmlArr);
	}

	function setDueDate(XML) {
		document.body.style.cursor = 'default';
		removeElementsChildren($('errMsg'));
		$('errMsg').appendChild(document.createTextNode('Due Date successfully set'));
	}

	function updatePriority(value, WFTodoID) {
		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		document.body.style.cursor = 'wait';
		eMsg.appendChild(document.createTextNode('Please Wait....'));

		var selBox = $('dropBlock-'+WFTodoID);
		if(selBox.options[0].value == 0) {
			selBox.remove(0);
		}

		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "updateTodoPriority",
						"todoID" : WFTodoID,
						"priority" : value }; 
		postXML(xmlArr);
	} 

	function setPriority(XML) {
		document.body.style.cursor = 'default';
		removeElementsChildren($('errMsg'));
		$('errMsg').appendChild(document.createTextNode('Priority successfully set'));
	}

	function enableElements() {
		$('searchButton').disabled = false;
		if(el = $('userTodo')) {
			var action2 = 'viewWFTodo.php?delete=1&username='+el.value;
			$('deleteWorkflow').onclick = function() {  document.todo.action = action2;
															if(userConfirm()) {
																checkForSelected();
															} };
			el.disabled = false;

		} else {
			$('deleteWorkflow').style.visibility = 'hidden';
		}
		$('editNotes').onclick = function() { enableEditElements(); };

		if ($('reassignList').options.length >= 1) {
			$('reassignList').disabled = false;
			$('actionButton').disabled = false;

			var action1 = 'viewWFTodo.php?assign=1&username='+todoUser;
			$('actionButton').onclick = function() {	document.todo.action = action1;
														checkForSelected() };
		}
	}

	function checkForSelected() {
        var chk = $('todoTable').getElementsByTagName('input');
        var ct = 0;
        for(var i=0;i<chk.length;i++) {
            if(chk[i].checked == true) {
                document.todo.submit();
                return;
            }
        }

        var eMsg = $('errMsg');
        removeElementsChildren(eMsg);
        eMsg.appendChild(document.createTextNode('Must have atleast one workflow selected'));
    }

	function searchTodoXML(p) {
		if(p) {
			page = p;
		}
		disableElements();

		var uname = "";
		if(username) {
            uname = username;
            username = "";
		} else if(el = $('userTodo')) {
			uname = el.value;
		}
		todoUser = uname;
		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		document.body.style.cursor = 'wait';
		eMsg.appendChild(document.createTextNode('Please Wait....'));

		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "searchWFTodo",
						"uname" : uname,
						"orderBy" : orderField,
						"orderDir" : orderDirection,
						"page" : page,
						"filter" : filterName,
						"search" : filterSearch,
						"exact" : filterExact }; 
		postXML(xmlArr);

		filterName = "";
		filterSearch = "";
		filterExact = 0;
	}

 	function applyFilter() {
 		if(el = $('searchText')) {
 			filterSearch = el.value;
 		} else if(el = $('searchSelect')) {
 			filterSearch = el.value;
 			filterExact = 1;
 		}
 
 		var fSel = $('filterSelect');
 		var fText = fSel.options[fSel.selectedIndex].text;
 		filterName = fSel.options[fSel.selectedIndex].value;
 		var appSel = $('appliedFilters');
 		if(filterSearch != "") {
 			var opt = document.createElement('option');
 			opt.filter = filterName;
 			opt.search = filterSearch;
 			opt.exact = filterExact;
 			opt.appendChild(document.createTextNode(fText+":"+filterSearch));
 
 			var length = appSel.length;
 			if( length && appSel.options[length-1].filter == "All") {
 				appSel.insertBefore(opt,appSel.item(length-1));
 			} else {
 				$('appliedFilters').appendChild(opt);
 			}
 		} else {
 			if(filterName == "All") {	
 				while(fSel.item(1)) {
 					fSel.remove(1);
 				}
 			}
 		}
 
 		if($('appliedFilters').length > 1) {
 			$('appliedFilters').style.display = 'inline';
 		} else {
 			$('appliedFilters').style.display = 'none';
 		}
 		searchTodoXML(1);
 	}
 
 	function removeFilter() {
 		var fSel = $('appliedFilters');
		var filterID = fSel.options[fSel.selectedIndex].id;
 		fSel.remove(fSel.selectedIndex);
 
 		if(fSel.length == 1) {
 			fSel.style.display = 'none';
 		}

		var uname = "";
		if(username) {
			uname = username;
			username = "";
		} else if(el = $('userTodo')) {
			uname = el.value;
		}
	
		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "removeFilter",
						"uname" : uname,
						"orderBy" : orderField,
						"orderDir" : orderDirection,
						"page" : 1,
						"filter_id" : filterID }; 
		postXML(xmlArr);
 	}
 
	function setWorkflowTodo(XML) {
		removeElementsChildren($('inputDiv'));
		numOfPriorities = XML.getElementsByTagName('NUMOFPRIORITIES')[0].getAttribute ('value');
		var userlist = XML.getElementsByTagName('USERNAME');
		if(userlist.length > 0) {
			populateReassignList($('reassignList'),userlist);
			if (XML.getElementsByTagName('SHOWUSERDIV')[0].getAttribute ('value') == '1') {
				createUserTodoDiv(userlist);
			} else {
				var myEl = XML.createElement ('temp');
				myEl.appendChild (XML.createTextNode (username));
				createUserTodoDiv(new Array (myEl));
			}
		} else {
			removeElementsChildren($('reassignList'));
		}

		var appSel = $('appliedFilters');
		while(appSel.length > 1) {
			appSel.remove(1);
		}

		var filterList = XML.getElementsByTagName('FILTER');
		if(filterList.length > 0) {
			for(var i=0;i<filterList.length;i++) {
				var opt = document.createElement('option');
				opt.id = filterList[i].getAttribute('id');
				opt.filter = filterList[i].getAttribute('name');
				if (filterList[i].firstChild) {
					opt.search = filterList[i].firstChild.nodeValue;
				} else {
					opt.search = '';
				}
				opt.exact = filterList[i].getAttribute('exact');
				var filterStr = opt.filter+': '+opt.search;
				opt.appendChild(document.createTextNode(filterStr));
	 
				var length = appSel.length;
				if( length && appSel.options[length-1].filter == "All") {
					appSel.insertBefore(opt,appSel.item(length-1));
				} else {
					$('appliedFilters').appendChild(opt);
				}
			}

			if($('appliedFilters').length > 1) {
				$('appliedFilters').style.display = 'inline';
			} else {
				$('appliedFilters').style.display = 'none';
			}
		}

		var entries = XML.getElementsByTagName('ENTRY');
		if(entries.length > 0) {
			for(var i=0;i<entries.length;i++) {
				var fields = entries[i].getElementsByTagName('FIELD');
				if(fields.length > 0) {
					var fieldArr = new Object();
					for(var j=0;j<fields.length;j++) {
						var attr = fields[j].getAttribute('name');
						var val;
						if(fields[j].firstChild) {
							val = fields[j].firstChild.nodeValue;
						} else {
							val = '';
						}
						fieldArr[attr] = val;

						if(attr == "workflow") {
							var wfID = fields[j].getAttribute('wfID');
							fieldArr['wfID'] = wfID;	
						}
					}
					if(!$('todo-'+fieldArr['username'])) {
						userArr[userArr.length] = fieldArr['username'];
					}
					addTodoRow(fieldArr);	
				}
			}
			if(el = $('resultDiv')) {
				var root = XML.getElementsByTagName('TODO');
				if(root.length > 0) {
					var viewing = root[0].getAttribute('viewing');
					total = parseInt(root[0].getAttribute('total'));
 					if(total <= 100) {
 						$('pageDiv').style.visibility = "hidden";
 					} else {
 						$('pageDiv').style.visibility = "visible";
 						removeElementsChildren(el);
 						el.appendChild(document.createTextNode('Viewing: '+viewing));
 					}

					if(total > 0) {
						removeElementsChildren($('totalDiv'));
						$('totalDiv').appendChild(document.createTextNode('Todo Items: '+total));
					}
				}
			}
		} else {
			if(pageEl = $('pageDiv')) {
				pageEl.style.visibility = 'hidden';
			}
			removeElementsChildren($('totalDiv'));
			$('totalDiv').appendChild(document.createTextNode('Todo Items: 0'));
		}

		document.body.style.cursor = 'default';

		var eMsg = $('errMsg');
		removeElementsChildren(eMsg);
		enableElements();
	}

	function toggleTodoPage(type) {
		if(type == "next") {
			page++;
			var t = Math.ceil(total/100);
			if(page > t) {
				page = t;
			}
		} else {
			page--;
			if(!page) {
				page = 1;
			}
		}
		searchTodoXML();
	}

	function createUserTodoDiv(userlist) {
		var userDiv = document.createElement('div');
		userDiv.id = 'todoDiv';
		userDiv.style.overflow = 'auto';

		var userTable = document.createElement('table');
		userTable.id = 'todoTable';
		userTable.style.whiteSpace = 'nowrap';
		userTable.paddingLeft = '35px';
		userDiv.appendChild(userTable);
		
		var row = userTable.insertRow(userTable.rows.length);
		var col = row.insertCell(row.cells.length);
		col.colSpan = headerArr.length;
		if(userlist.length > 1) {
			var selbox = document.createElement('select');
			selbox.id = 'userTodo';
			selbox.onchange = function() { searchTodoXML(1); }
			col.appendChild(selbox);

			for(var i=0;i<userlist.length;i++) {
				var opt = document.createElement('option');
				opt.value = userlist[i].firstChild.nodeValue;
				opt.appendChild(document.createTextNode(userlist[i].firstChild.nodeValue));
				if(userlist[i].getAttribute('selected') == "1") {
					opt.selected = true;	
				}
				selbox.appendChild(opt);
			}
		} else {
			col.appendChild(document.createTextNode(userlist[0].firstChild.nodeValue));
		}
		
		var row = userTable.insertRow(userTable.rows.length);
		row.style.fontWeight = 'bold';
		row.style.fontSize = '10px';
		for(var i=0;i<headerArr.length;i++) {
			if(headerArr[i] == 'Grab' && !ifGrab) {
				continue;
			}

			var col = row.insertCell(row.cells.length);	

			var sp = document.createElement('span');
			if(headerArr[i] == 'Date Due') {
				sp.onclick = function() { setOrderField('date') };	
			}
			sp.style.paddingRight = '2px';
			sp.style.cursor = 'pointer';
			sp.appendChild(document.createTextNode(headerArr[i]));
			col.appendChild(sp);
			
			if(headerArr[i] == 'Date Due') {
				if(orderField) {
					var sp = document.createElement('div');
					sp.onclick = function() { setOrderField('date') };	
					sp.style.position = 'relative';
					sp.style.display = 'inline';
					sp.style.fontSize = '0px'; 
					sp.style.lineHeight = '0%'; 
					sp.style.width = '0px';

					if(orderDirection == "ASC") {
						sp.style.top = '2px';
						sp.style.borderTop = '8px solid gray';
						sp.style.borderLeft = '4px solid white';
						sp.style.borderRight = '4px solid white';
					} else if(orderDirection == "DESC") {
						sp.style.top = '-8px';
						sp.style.borderBottom = '8px solid gray';
						sp.style.borderLeft = '4px solid white';
						sp.style.borderRight = '4px solid white';
					}
					sp.appendChild(document.createTextNode(' '));
					col.appendChild(sp);
				}
			}
		}
		$('inputDiv').appendChild(userDiv);
		adjustDivHeight();
	}

	function setOrderField(field) {
		if(orderField && field != orderField) {
			orderDirection = "";
		}

		orderField = field;
		if(orderDirection == 'ASC') {
			orderDirection = 'DESC';
		} else {
			orderDirection = 'ASC';
		}
		searchTodoXML(1);
	}

	function showPriorityTable(priorityDiv, wfTodoID,priority) {
			var dropBlock = document.createElement('select');
			dropBlock.id = 'dropBlock-' + wfTodoID;
			dropBlock.style.display = 'block';
			dropBlock.onchange = function () { updatePriority(this.value, wfTodoID) };

			for(var i = 0; i <= numOfPriorities; i++) {
				if((priority > 0 && i != 0) || (priority == 0)) {
					var dropOption = document.createElement('option');
					dropOption.value = i;
					dropOption.appendChild(document.createTextNode(i));
					if(i == priority) {
						dropOption.selected = true;
					}
					dropBlock.appendChild(dropOption);
				}
			}
			priorityDiv.appendChild(dropBlock);
	}

	function grabTodoItem() {
		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "xmlGrabWorkflowItem",
						"todo_id" : this.id };
		postXML(xmlArr);
	}

	function addTodoRow(fieldArr) {
		var userTable = $('todoTable'); 
		var row = userTable.insertRow(userTable.rows.length);
		var priority = 0;
		if(fieldArr['priority']) {
			priority = parseInt(fieldArr['priority']);
		}
		row.style.cursor = 'pointer';
		row.style.fontSize = '10px';
		row.onmouseover = showRow;
		row.workflow = fieldArr['workflow'];
		row.priority = priority;
		row.onmouseout = hideRow;

		if(ifGrab) {
			var col = row.insertCell(row.cells.length);
			col.id = fieldArr['id'];
			col.onclick = grabTodoItem;
			var sp = document.createElement('span');
			sp.style.color = 'blue';
			sp.style.textDecoration = 'underline';
			sp.appendChild(document.createTextNode('Grab'));
			col.appendChild(sp);
		}

		for( var k in fieldArr) {
			if(k == 'id') {
				row.id = 'todo-'+fieldArr[k];
				if(tID == fieldArr[k]) {
					row.style.backgroundColor = '#6a78af';
				}
			} else if(k == 'priority') {
				var col = row.insertCell(row.cells.length);
				var priorityDiv = document.createElement('div');
				priorityDiv.id = 'priority-' + fieldArr['id'];
				showPriorityTable(priorityDiv, fieldArr['id'],priority);
				col.appendChild(priorityDiv);
			} else if(k == 'notes') {
				var col = row.insertCell(row.cells.length);
				col.id = k + '-' +  row.id;
				col.setAttribute("wfTodoID", fieldArr['id']);
				col.setAttribute("wfLink", fieldArr['link']); 
				col.appendChild(document.createTextNode(fieldArr[k]));
				col.onclick = function() {getLink(fieldArr['link']) };
			} else if(k == 'date_due') {
				var col = row.insertCell(row.cells.length);
				var frag = document.createDocumentFragment();
				var dateInput = document.createElement('input');
				dateInput.id = 'dateInput-' + fieldArr['id'];
				dateInput.type = 'text';
				dateInput.size = 10;
				if(fieldArr[k] != "0000-00-00") {
					dateInput.value = fieldArr[k];
				}
				dateInput.readOnly = true;
				frag.appendChild(dateInput);
				var dateImage = new Image(15, 15);
				dateImage.workflowID = fieldArr['id'];
				dateImage.id = 'dateImage-' + fieldArr['id'];
				dateImage.src = "../images/edit_16.gif";
				dateImage.input = dateInput;
				dateImage.whereID = 'todoDiv';
				dateImage.onclick = dispCurrMonth;
				frag.appendChild(dateImage);
				col.appendChild(frag);
			} else if(k != 'link' && k != 'disable' && k != 'username' && k != 'wfID') {
				var col = row.insertCell(row.cells.length);	
				col.appendChild(document.createTextNode(fieldArr[k]));
				col.onclick = function() {getLink(fieldArr['link']) };
			}
		}

		var col = row.insertCell(row.cells.length);	
		var chkbox = document.createElement('input');
		chkbox.type = 'checkbox';
		chkbox.value = fieldArr['id'];
		chkbox.name = "reassign[]";
		col.appendChild(chkbox);
	}

	function hideTableRow() {
		var todoTbl = $('todoTable');
		for(i=0;i<todoTbl.rows.length;i++) {
			if(todoTbl.rows[i].cells[4]) {
				todoTbl.rows[i].cells[4].style.display = 'none';
				todoTbl.rows[i].cells[9].style.display = 'none';
				todoTbl.rows[i].cells[10].style.display = 'none';
			}
		}
		var togCol = $('toggleColumns');
		removeElementsChildren(togCol);
		togCol.onclick = displayTableRow;
		togCol.appendChild(document.createTextNode('Show Details'));
	}

	function displayTableRow() {
		var todoTbl = $('todoTable');
		for(i=0;i<todoTbl.rows.length;i++) {
			if(todoTbl.rows[i].cells[1]) {
				if(document.all) {
					todoTbl.rows[i].cells[4].style.display = 'block';
					todoTbl.rows[i].cells[9].style.display = 'block';
					todoTbl.rows[i].cells[10].style.display = 'block';
				} else {
					todoTbl.rows[i].cells[4].style.display = 'table-cell';
					todoTbl.rows[i].cells[9].style.display = 'table-cell';
					todoTbl.rows[i].cells[10].style.display = 'table-cell';
				}
			}
		}
		var togCol = $('toggleColumns');
		removeElementsChildren(togCol);
		togCol.onclick = hideTableRow;
		togCol.appendChild(document.createTextNode('Hide Details'));
	}

	function createTodoXML() {
		var xmlDoc = createDOMDoc();	
		var root = xmlDoc.createElement('ROOT');
        xmlDoc.appendChild(root);

		var searchStr = $('searchText').value;
		var key = xmlDoc.createElement('SEARCH');
		key.appendChild(xmlDoc.createTextNode(searchStr));
		root.appendChild(key);

		if($('userTodo') || username) {
            if(username) {
                var u = username;
                username = "";
            } else {
                var u = $('userTodo').value;
            }
			var uname = xmlDoc.createElement('USERNAME');
			uname.appendChild(xmlDoc.createTextNode(u));
			root.appendChild(uname);
		}
		return domToString(xmlDoc);
	}

	function populateReassignList(selectbox,userlist) {
		removeElementsChildren(selectbox);
		for(var i=0;i<userlist.length;i++) {
			var opt = document.createElement('option');
			opt.value = userlist[i].firstChild.nodeValue; 
			opt.appendChild(document.createTextNode(userlist[i].firstChild.nodeValue));
			selectbox.appendChild(opt);
		}

	}

	function selectFilter() {
		var fsel = $('filterSelect');
		var filter = fsel.options[fsel.selectedIndex].value;

		var xmlArr = {	"include" : "workflow/workflowActions.php",
						"function" : "getFilterInfo" ,
						"filter" : filter };
		postXML(xmlArr);
	}

	function setFilterInput(XML) {
		removeElementsChildren($('filterSpan'));
		var fList = XML.getElementsByTagName('FILTER');
		if(fList.length > 0) {
			var selBox = document.createElement('select');
			selBox.id = "searchSelect";
			selBox.name = "searchSelect";
			selBox.onchange = function () { applyFilter() };
			for(var i=0;i<fList.length;i++) {
				var opt = document.createElement('option');
				var val = 0;
				if(fList[i].firstChild) {
					val = fList[i].firstChild.nodeValue;
				}
				opt.value = val;
				opt.appendChild(document.createTextNode(val));

				selBox.appendChild(opt);
			}
			$('filterSpan').appendChild(selBox);
		} else {
			var txtBox = document.createElement('input');
			txtBox.type = "text";
			txtBox.id = "searchText";
			txtBox.name = "searchStr";
			txtBox.onkeypress = allowDigi;
			$('filterSpan').appendChild(txtBox);
		}
	}

	function adjustView() {
		var clienth = document.documentElement.clientHeight;
		$('outDiv').style.height = (clienth-30)+'px';

	}
	
	function adjustDivHeight() {
		var outh = $('outDiv').offsetHeight;

		var d = $('inputDiv');
		var inh = d.offsetTop;

		var newh = 0;
		newh = outh - inh;
		$('todoDiv').style.height = (newh-15)+'px';
	}

	Behaviour.addLoadEvent(
		function() {
			searchTodoXML();
			adjustView();
		}
	); 
	</script>
 </head>
 <body class="centered">
 <div id="outDiv" class="mainDiv" style="width:95%;height:100%">
 <div class="mainTitle">
 <span>Workflow Todo List</span>
 </div>
 <form name="todo" method="post" action="viewWFTodo.php">
 <div id='searchDiv' class="inputForm" style="margin-bottom: 0px;font-size:8pt">
	<div id="totalDiv" style="float:left;width:20%;font-weight:bold;text-align:left;text-indent:5px"></div>
   <div style="float:left;width:47%;text-align:right">
	<select id="filterSelect" name="filterSelect" onchange="selectFilter()">
		<option value="All">All</option>
		<option value="priority">Priority</option>
		<option value="notes">Notes</option>
		<option value="date_due">Date Due</option>
		<option value="date">Date Notified</option>
		<option value="department">Department</option>
		<option value="cabinet">Cabinet</option>
		<option value="foldername">Folder</option>
		<option value="tab">Tab/Document</option>
		<option value="workflow">Workflow</option>
		<option value="nodeName">Node Name</option>
		<option value="nodeType">Node Type</option>
	</select>
	<span id="filterSpan">
		<input id="searchText" type="text" name="searchStr" onkeypress="return allowDigi(event)" value="" />
	</span>
	<input id='searchButton' onclick="applyFilter()" type="button" name="search" value="Apply Filter" />
   </div>
	 <div id='reassignDiv' style='float:right;width:32%;text-align:right'>Reassign to 
	  <select id='reassignList' name='assignee'></select>
	  <input id='actionButton' type="button" name="B1" value="Submit" />
	  <img id='deleteWorkflow' src="../images/trash.gif" alt="Delete" 
		style="cursor:pointer" width="16" border="0" />
	 <img id='editNotes' src="../energie/images/file_edit_16.gif" alt="Edit Notes"
		style="cursor:pointer" width="16" border="0" />
	</div>
 </div>
 <div id="pageDiv" style="text-align:left;padding-left:5px;width:100%">
	<img src="../energie/images/back_button.gif" 
		alt="Previous"
		width="16"
		style="cursor:pointer;padding-right:5px"
		onclick="toggleTodoPage('back')" />
	<span id="resultDiv" style="vertical-align:top;font-size:8pt"></span>
	<img src="../energie/images/next_button.gif" 
		alt="Next"
		width="16"
		style="cursor:pointer;padding-left:5px" 
		onclick="toggleTodoPage('next')" />
 </div>
 <div style='height:30px;padding-top:5px'>
	 <div id='wfDiv' style='float:left; text-align:left;width:35%;padding-left:5px;white-space:nowrap'>
		<select id="appliedFilters" name="appliedFilters" onchange="removeFilter()" style="display:none">
			<option value="__default">Remove Filter</option>
		</select>
		<span id="toggleColumns" onclick="hideTableRow()" style="cursor:pointer;text-decoration:underline;color:blue;font-size:7pt">Hide Details</span>
	 </div> 
	 <div id='errMsg' class='error' style='float:left;text-align:center;width:25%'></div>
 </div>
  <div id='inputDiv' class="inputForm" style="margin-bottom: 0px;">
  </div>
 </form>
 </div>
 </body>
</html>
ENERGIE;
    setSessionUser($user);
} else {
	logUserOut();
}
?>
