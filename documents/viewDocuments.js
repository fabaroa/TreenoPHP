var thistype = '';
function postXML(xmlStr, URL) {
	var tmpUrl = '../documents/documentPostRequest.php';
	if(URL) {
		tmpUrl = URL;
	}
	//alert(xmlStr);
	var newAjax = new Ajax.Request( tmpUrl,
								{   method: 'post',
									postBody: xmlStr,
									onComplete: receiveXML,
									onFailure: reportError} );	
}

function receiveXML(req) {
	//alert(req.responseText);
	if(req.responseXML) {
		var XML = req.responseXML;
		var log = XML.getElementsByTagName('LOGOUT');
		if(log.length > 0) {
			top.window.location = '../logout.php';
		}

		var mess = XML.getElementsByTagName('MESSAGE');
		if(mess.length) {
			addMessage(mess[0].firstChild.nodeValue);
		}

		var func = XML.getElementsByTagName('FUNCTION');
		if(func.length > 0) {
			eval(func[0].firstChild.nodeValue);
		}

		var dl = XML.getElementsByTagName('DOWNLOAD');
		if(dl.length) {
			addBackButton();
			var file = dl[0].firstChild.nodeValue;
			parent.viewFileActions.window.location = "../energie/displayExport.php?file=/"+file;
			parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
		}

		var rl = XML.getElementsByTagName('RELOAD');
		if(rl.length) {
			var mess = rl[0].firstChild.nodeValue;
			var cab = rl[0].getAttribute('cabinet');
			var doc_id = rl[0].getAttribute('doc_id');
			var tab_id = rl[0].getAttribute('tab_id');
			
			parent.sideFrame.window.location = "viewDocuments.php?cab="+cab
										+"&doc_id="+doc_id+"&tab_id="+tab_id;
		}
		enableToolbars();
	}
}

function reportError() {

}

function disableToolbars(reorder) {
	if(fAct = getEl('fileActions')) {
		var fileAct = fAct.getElementsByTagName('img');	
		for(i=0;i<fileAct.length;i++) {
			if(reorder) {
				if(fileAct[i].id != 'reorder') {
					fileAct[i].disabled = true;
					fileAct[i].style.cursor = 'text';
				}
			} else {
				fileAct[i].disabled = true;
				fileAct[i].style.cursor = 'wait';
			}
		}
		if(!reorder) {
			getEl('fsFileActions').style.cursor = 'wait';
		}
	}

	if(sFileAct = getEl('singleFileActions')) {
		var singleFileAct = sFileAct.getElementsByTagName('img');	
		for(i=0;i<singleFileAct.length;i++) {
			if(reorder) {
				if(singleFileAct[i].id != 'reorder') {
					singleFileAct[i].disabled = true;
					singleFileAct[i].style.cursor = 'text';
				}
			} else {
				singleFileAct[i].disabled = true;
				singleFileAct[i].style.cursor = 'wait';
			}
		}
		if(!reorder) {
			getEl('fsSingleFileActions').style.cursor = 'wait';
		}
	}

	if(pageEl = getEl('paging')) {
		var paging = pageEl.getElementsByTagName('img');	
		for(i=0;i<paging.length;i++) {
			paging[i].disabled = true;
			if(!reorder) {
				paging[i].style.cursor = 'wait';
			} else {
				paging[i].style.cursor = 'text';
			}
		}
		getEl('newPage').disabled = true;
		if(!reorder) {
			getEl('newPage').style.cursor = 'wait';
			getEl('paging').style.cursor = 'wait';
		}
	}
}

function enableToolbars() {
	if(fAct = getEl('fileActions')) { 
		var fileAct = fAct.getElementsByTagName('img');	
		for(i=0;i<fileAct.length;i++) {
			fileAct[i].disabled = false;
			fileAct[i].style.cursor = 'pointer';
		}
		getEl('fsFileActions').style.cursor = 'default';
	}

	if(sFileAct = getEl('singleFileActions')) {
		var singleFileAct = sFileAct.getElementsByTagName('img');	
		for(i=0;i<singleFileAct.length;i++) {
			singleFileAct[i].disabled = false;
			singleFileAct[i].style.cursor = 'pointer';
		}
		getEl('fsSingleFileActions').style.cursor = 'default';
	}

	if(pageEl = getEl('paging')) {
		var paging = pageEl.getElementsByTagName('img');	
		for(i=0;i<paging.length;i++) {
			paging[i].disabled = false;
			paging[i].style.cursor = 'pointer';
		}
		getEl('newPage').disabled = false;
		getEl('newPage').style.cursor = 'default';
		getEl('paging').style.cursor = 'default';
	}
}
function getSelectedFiles2(type){
	//turnOnZipPassword is a DMS.DEFS 
	if( turnOnZipPassword && (type =='createZIP' ) ){
		if( $('zip_skip')){
			$('zip_password').focus();
			$('zip_password').select();
			return;
		}
		//write to the errmsg div input, with two buttons
		var passwd = document.createElement('input');
		passwd.id='zip_password';
		passwd.value="Enter Zip Password";
		var newline = document.createElement('br');
		var skip = document.createElement('input');
		skip.type="button";
		skip.value="Skip";
		skip.id="zip_skip";
		var submit = document.createElement('input');
		submit.name="Submit";
		submit.value="Submit";
		submit.type="button";
		submit.id="zip_submit";
		$('errMsg').appendChild(passwd);
		$('errMsg').appendChild(newline);
		$('errMsg').appendChild(skip);
		$('errMsg').appendChild(submit);
		$('zip_password').focus();
		$('zip_password').select();
		skip.onclick= function(){$('zip_password').value='';getSelectedFiles(type)};
		submit.onclick=function(){getSelectedFiles(type)};
		
	} else {
		getSelectedFiles(type);
	}
}
function getSelectedFiles(type) {
	disableToolbars();
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);
	
	createKeyAndValue(xmlDoc,root,'function',type);
	createKeyAndValue(xmlDoc,root,'cabinet',cab);
	createKeyAndValue(xmlDoc,root,'doc_id',doc_id);
	if(tab_id) {
		createKeyAndValue(xmlDoc,root,'tab_id',tab_id);
	}

	if(el = getEl('exportNonRedact')) {
		if(el.checked) {
			createKeyAndValue(xmlDoc,root,'nonRedact','1');
		} else {
			createKeyAndValue(xmlDoc,root,'nonRedact','0');
		}
	}

	var i = 1;
	var j = 1;
	while( el = getEl('file-'+i)) {
		if(el.checked == true) {
			createKeyAndValue(xmlDoc,root,'file'+j,el.value);
			j++;
		}
		i++;
	}
	
	try{
		if( turnOnZipPassword && (type == 'createZIP')){
			if($('zip_password').value == '' || $('zip_password').value == 'Enter Zip Password' ){
				//do not set key value pair for the password
				createKeyAndValue(xmlDoc,root,'password', '' );
			}else{
				createKeyAndValue(xmlDoc,root,'password', $('zip_password').value );
			}
			$('errMsg').innerHTML = '';
		} else {
			createKeyAndValue(xmlDoc,root,'password', '' );
		}
	} catch( error ) {
		//do nothing...browser doesn't support this functionality
	}
	if(j == 1) {
		enableToolbars();
		addMessage('No Files Have Been Selected');
	} else {
		var answer = true;
		if(type == "deleteFiles") {
			ruSure = "Are you sure you want to delete " + (j-1) + " file(s)?";
			answer = window.confirm( ruSure );
		}

		if(answer) {
			createPDF = 1;
			postXML(domToString(xmlDoc));
		} else {
			enableToolbars();
		}
	}
}

function toggleSubfolder(name) {
	var toggle = getEl('subfolder-'+name).checked;
	var checklist = getEl('fieldset-'+name).getElementsByTagName('input');
	for(var i=0;i<checklist.length;i++) {
		if(checklist[i].type == "checkbox") {
			checklist[i].checked = toggle;	
		}
	}
}

function showThumbs(i) {
	var img;
	if(img = document.getElementById('img:'+i)) {
		var infoStr = getEl('imgInfo:'+i).firstChild.nodeValue;
		var infoArray = infoStr.split(',');
		var cabinet = infoArray[0];
		var fileID = infoArray[1];
		var urlStr = '../energie/readfileThumbs.php?cab='+cabinet+'&fileID='+fileID;
		i++;
		img.onload = function() {
			setTimeout("showThumbs("+i+")", 50);
		}
		img.src = urlStr;
	}
	return true;
}

function addBackButton() {
		parent.topMenuFrame.addBackButtonOnclick();
}

function viewFile(file_id,t) {
	if(boolSelect) {
		addBackButton();
		if(selectedFile) {
			getEl(selectedFile).style.backgroundColor = '';
		}
		t.style.backgroundColor = '#6A78AF';
		selectedFile = t.id;
		parent.searchPanel.showNotes(file_id);
		parent.viewFileActions.window.location = '../documents/viewFile.php?cab='+cab+'&fileID='+file_id;
		parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');

		elList = getEl('id-'+file_id).getElementsByTagName('input');
		for(var i=0;i<elList.length;i++) {
			if(elList[i].type == 'checkbox') {
				chkBox = elList[i].id;
			}
		}
		var pg = chkBox.replace("file-","");
		if(getEl('pageNum')) {
			getEl('pageNum').value = pg;
		}

		if(getEl('newPage')) {
			getEl('newPage').value = pg;
		}
	} else {
		boolSelect = true;
	}
}

function submitPage(e,total) {
	var evt = (e) ? e : event;
	var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
	var pool = "1234567890";
	if(code == 13) {
		changePage('',total);			
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

function changePage(type,total) {
	var pageNum = getEl('pageNum');
	var newPage = getEl('newPage');
	var page = parseInt(pageNum.value);
	if(type == "FIRST") {
		page = 1;
	} else if(type == "PREV") {
		if((page-1) < 1) {
			page = 1;
		} else {
			page--;
		}
	} else if(type == "NEXT") {
		if((page+1) > total) {
			page = total;
		} else {
			page++;
		}
	} else if(type == "LAST") {
		page = total;
	} else { 
		//this occurs when they type a number in the text box
		if(newPage.value < 1) {
			page = 1;
		} else if(parseInt(newPage.value) > parseInt(total)) {
			page = total;
		} else {
			page = newPage.value;
		}
	}

	newPage.value = page;
	pageNum.value = page; 
	var file_id = getEl('file-'+page).value;
	viewFile(file_id,getEl('id-'+file_id));
}

function uploadFile() {
	createPDF = 0;
	addBackButton();
	
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);
	
	createKeyAndValue(xmlDoc,root,'include','secure/uploadFuncs.php');
	createKeyAndValue(xmlDoc,root,'function','setUploadPath');
	createKeyAndValue(xmlDoc,root,'cabinet',cab);
	createKeyAndValue(xmlDoc,root,'doc_id',doc_id);
	if(tab_id) {
		createKeyAndValue(xmlDoc,root,'tab_id',tab_id);
	}
	postXML(domToString(xmlDoc),'../lib/ajaxPostRequest.php');
//	parent.viewFileActions.window.location = '../energie/uploadFile.php?doc_id='
//					+doc_id+'&tab_id='+tab_id+'&cab='+cab;
//	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function openUploadPage() {
	parent.viewFileActions.window.location = '../secure/uploadInbox2.html';
	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function moveFile() {
	createPDF = 0;
	if(findFirstSelected(1)) {
		addBackButton();
		parent.viewFileActions.window.location = '../movefiles/departmentContents.php?'
					+'cab='+cab+'&doc_id='+doc_id+'&tab_id='+tab_id;
		parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
	}
}

function editFile() {
	createPDF = 0;
	if(file_id = findFirstSelected()) {
		addBackButton();
		parent.viewFileActions.window.location = '../energie/editName2.php?cab='
					+cab+'&fileID='+file_id;
		parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
	}
}

function versionFile(cab_id) {
	createPDF = 0;
	if(file_id = findFirstSelected()) {
		addBackButton();
		parent.viewFileActions.window.location = '../versioning/vFHFrame.php?'
					+'fileID='+file_id+'&cabinetID='+cab_id;
		parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
	}
}

function allowOrdering(order) {
	parent.leftFrame1.window.location = '../energie/moveThumb.php?orderSentry='+order;
}

function saveFile() {
	createPDF = 0;
	if(file_id = findFirstSelected()) {
		parent.topFrame.window.location = '../energie/display.php?pop=1'
					+'&download=1&cab='+cab+'&doc_id='+doc_id
					+'&fileID='+file_id;
	}
}

function redactFile() {
	createPDF = 0;
	if(file_id = findFirstSelected()) {
		addBackButton();
		top.viewFileActions.window.location = '../energie/editRedaction.php?'
					+'cabinet='+cab+'&docID='+doc_id+'&fileID='+file_id
					+'&divID='+document.getElementById('currentFiles').scrollTop;
		parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
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
		var URL = '../energie/modifyImage.php?cab='+cab+'&doc_id='+doc_id+'&type='+type;
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
		addMessage('Must select a file');
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

function viewWFHistory() {
	createPDF = 0;
	addBackButton();
	parent.viewFileActions.window.location = '../workflow/viewHistory.php?cab='
					+cab+'&doc_id='+doc_id+'&file_id='+tab_id;
	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function assignWorkflow() {
	createPDF = 0;
	addBackButton();
	parent.viewFileActions.window.location = '../workflow/assignWorkflow.php?cab='
					+cab+'&doc_id='+doc_id+'&file_id='+tab_id;
	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function enterWorkflow() {
	createPDF = 0;
	addBackButton();
	parent.viewFileActions.window.location = '../workflow/getSignature.php?cab='
					+cab+'&doc_id='+doc_id+'&file_id='+tab_id;
	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function editWorkflow() {
	createPDF = 0;
	addBackButton();
	parent.viewFileActions.window.location = '../workflow/ownerAction.php?cab='
					+cab+'&doc_id='+doc_id+'&file_id='+tab_id;
	parent.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function reorderFiles() {
	createPDF = 0;
	disableToolbars(1);
	getEl('currentFiles').className= 'reorder';
	Position.includeScrollOffsets = true;
	Sortable.create("currentFiles",
		{scroll:'currentFiles',dropOnEmpty:true,containment:["currentFiles"]});
	getEl('reorder').onclick = function () {setOrderOfFiles()};
	toggleViewFile();
}

function toggleViewFile() {
	Behaviour.apply(myrules);
}

function setOrderOfFiles() {
	var liArr = getEl('currentFiles').getElementsByTagName('li');
	if(liArr.length) {
		var xmlDoc = createDOMDoc();
		var root = xmlDoc.createElement('ROOT');
		xmlDoc.appendChild(root);

		createKeyAndValue(xmlDoc,root,'function','reorderFiles');
		createKeyAndValue(xmlDoc,root,'cabinet',cab);
		createKeyAndValue(xmlDoc,root,'doc_id',doc_id);
		if(tab_id) {
			createKeyAndValue(xmlDoc,root,'tab_id',tab_id);
		}

		for(var i=0;i<liArr.length;i++) {
			createKeyAndValue(xmlDoc,root,'file'+(i+1),liArr[i].id.slice(3));	
		}
		postXML(domToString(xmlDoc));
	}
	getEl('currentFiles').className= 'regular';
	getEl('reorder').onclick = function () {reorderFiles()};
	Sortable.destroy("currentFiles");
	enableToolbars();
	toggleViewFile();
}

function findFirstSelected(multi) {
	var i = 1;
	var checked = new Array();
	var j = 0;
	while( el = getEl('file-'+i)) {
		if(el.checked == true) {
			checked[j] = el.value;
			j++;
		}
		i++;
	}

	if(checked.length > 0) {
		if(!multi) { 
			if(checked.length > 1) {
				addMessage('Multiple Files Selected. Using Top-Most File.');
			}
		}
		return checked[0];
	} else {
		addMessage('No Files Have Been Selected');
	}
}

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

function addMessage(mess) {
	var errMsg = getEl('errMsg');
	clearDiv(errMsg);
	var tNode = document.createTextNode(mess);
	errMsg.appendChild(tNode);
	showMessageBox();
}

function showMessageBox() {
	var el = getEl('alertBox');	
	if(el.offsetHeight <= 60) {
		el.style.display = 'block';
		el.style.height = (el.offsetHeight + 20) + 'px';
		if(el.offsetHeight <= 60) {
			setTimeout(showMessageBox, 40);
		} else {
			setTimeout(hideMessageBox, 10000);
		}
	}
	el.style.backgroundColor = '#eeeeee';
	el.style.borderStyle = 'double';
	el.style.borderColor = 'black';
	el.style.textAlign = 'center';
}

function hideMessageBox() {
	var el = getEl('alertBox');	
	if(el.offsetHeight) {
		el.style.height = (el.offsetHeight - 20) + 'px';
		if(el.offsetHeight > 20) {
			clearDiv(getEl('errMsg'));
			setTimeout(hideMessageBox, 40);
		} else {
			el.style.display = 'none';
		}
	}
}

function closeActions(type) {
	getEl(type).style.display = 'none';
	if(type == "singleFileActions") {
		getEl('singleTool').style.visibility = 'visible';
	} else {
		getEl('multiTool').style.visibility = 'visible';
	}
	getEl('toolBar').style.display = 'block';
	adjustHeight();
}

function openActions(type) {
	getEl(type).style.display = 'block';
	if(type == "singleFileActions") {
		getEl('singleTool').style.visibility = 'hidden';
		if(getEl('multiTool').style.visibility == 'hidden') {
			getEl('toolBar').style.display = 'none';
		}
	} else {
		getEl('multiTool').style.visibility = 'hidden';
		if(getEl('singleTool').style.visibility == 'hidden') {
			getEl('toolBar').style.display = 'none';
		}
	}
	adjustHeight();
}

function addWorkflowHistoryIcon() {
	if(document.all) {
		getEl('wfHist').style.display = 'block';
	} else {
		getEl('wfHist').style.display = 'table-cell';
	}
}

function removeWorkflowHistoryIcon() {
	getEl('wfHist').style.display = 'none';
}

function assignOwner() {
	getEl('wfImg').onclick = editWorkflow;
}

function addWorkflow() {
	var wfImg = getEl('wfImg');
	wfImg.alt = "View Workflow";
	wfImg.title = "View Workflow";
	wfImg.src = "../images/edit_24.gif"
	wfImg.onclick = enterWorkflow;
	if(document.all) {
		getEl('workflow2').style.display = 'block';
	} else {
		getEl('workflow2').style.display = 'table-cell';
	}
}

function addWorkflowStart() {
	var wfImg = getEl('wfImg');
	wfImg.alt = "Assign Workflow";
	wfImg.title = "Assign Workflow";
	wfImg.src = "../images/email.gif"
	wfImg.onclick = assignWorkflow;
	if(document.all) {
		getEl('workflow2').style.display = 'block';
	} else {
		getEl('workflow2').style.display = 'table-cell';
	}
	
}

function removeWorkflowIcon() {
	getEl('workflow2').style.display = 'none';
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

function selectCheck(num) {
	if(buttonPress == true && startID) {
		selectAllInBetween(num);
	} else {
		startID = num;
	}
}

function selectAllInBetween(num) {
	if(num != startID) {
		if(num < startID) {
			var st = num;
			var fn = startID;
		} else {
			var st = startID;
			var fn = num;
		}
		for(i=st;i<fn;i++) {
			getEl('file-'+i).checked = true;
		}
	}
}

function fullScreenMode() {
	if(selectedFile || createPDF) {
		top.document.getElementById('afterMenu').setAttribute('cols','0,*');
		top.document.getElementById('mainFrameSet').setAttribute('cols','100%,*');
		top.topMenuFrame.document.getElementById('fullScreen').style.display = 'none';
		top.topMenuFrame.document.getElementById('exitFullScreen').style.display = 'block';

		//top.topMenuFrame.document.getElementById('newPage').value = getEl('newPage').value;
		//top.topMenuFrame.document.getElementById('pageNum').value = getEl('pageNum').value;
		if(getEl('pageNum')) {
			tp = getEl('pageNum').getAttribute('totalPages');
			//clearDiv(top.topMenuFrame.document.getElementById('pageDetail'));
			//top.topMenuFrame.document.getElementById('pageDetail').appendChild(top.topMenuFrame.document.createTextNode(getEl('pageDetail').firstChild.nodeValue));
			top.topMenuFrame.document.getElementById('firstPage').onclick = function () { changePage('FIRST',tp) }; 
			top.topMenuFrame.document.getElementById('prevPage').onclick = function () { changePage('PREV',tp) }; 
			top.topMenuFrame.document.getElementById('nextPage').onclick = function () { changePage('NEXT',tp) }; 
			top.topMenuFrame.document.getElementById('lastPage').onclick = function () { changePage('LAST',tp) }; 
		}
	} else {
		addMessage('A file must be opened for Full Screen Mode');
	}
}
