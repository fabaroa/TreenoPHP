fromPanel = false;
bookNum = 0;
cabinet = "";
docType = ""; 
fieldsArr = "";
xmlhttp = "";
currBookmark = "";
fileID = "";
showAddNote = false;
advancedArr = new Array('subfolder', 'file', 'context',
						'contextbool', 'date', 'who', 'notes');
deletedBM = false;
bookArr = new Object ();

function onTLSKeyPress(e) {
	var myEvent = e ? e : window.event;
	if (myEvent.keyCode == 13) {
		document.tlsForm.submit();
	}
}

function formKeyPress(e) {
	var myEvent = e ? e : window.event;
	if (myEvent.keyCode == 13) {
		$('submitBtn').click();
	}
}

function setCabinet(newCabinet, fields, secLevel, fromBookmarks, dataTypes) {
	if(cabinet == newCabinet && !fromBookmarks) {
		return;
	}
	fieldsArr = fields;
	cabinet = newCabinet;
	if(secLevel == 2) {
		showAddNote = true;
	}
	var depSelect = $('DepartmentID');
	var selectedOption = depSelect.options[depSelect.selectedIndex];
	if(selectedOption.value == "__chooseCab") {
		depSelect.removeChild(selectedOption);
	}
	for(var i = 0; i < depSelect.length; i++) {
		if(depSelect.options[i].value != newCabinet) {
			depSelect.options[i].selected = false;
		} else { 
			depSelect.options[i].selected = true;
		}
	}
	var cabTxt = depSelect.options[depSelect.selectedIndex].text;
	var newTxt = document.createTextNode(cabTxt);
	var cabLabel = $('cabLabel');
	if(cabLabel.childNodes.length > 1) {
		cabLabel.replaceChild(newTxt, cabLabel.lastChild);
	} else {
		cabLabel.appendChild(newTxt);
	}
	cabLabel.style.display = 'block';
	var searchFields = $('searchFields');
	while (searchFields.hasChildNodes()) {
		searchFields.removeChild(searchFields.lastChild);
	}
	currShowing = new Object();
	var mainDiv, labelDiv, labelSpan, fieldDiv, txtNode;
	var tmpImg, myField;
	for(var i = 0; i < fieldsArr.length; i++) {
		myField = fieldsArr[i].toUpperCase();
		mainDiv = document.createElement('div');
		labelDiv = document.createElement('div');
		fieldDiv = document.createElement('div');
		
		txtNode = document.createTextNode(fieldsArr[i].replace(/_/g, ' '));

		labelDiv.appendChild(txtNode);
		mainDiv.appendChild(labelDiv);
		if( dataTypes[fieldsArr[i]] && dataTypes[fieldsArr[i]].length > 0 ) {
			var fieldSelect = document.createElement('select');
			fieldSelect.name = myField;
			fieldSelect.id = 'field-' + myField;
			fieldDiv.appendChild(fieldSelect);
			fieldDiv.appendChild(document.createElement('br'));

			var fieldOption = document.createElement('option');
			fieldSelect.appendChild(fieldOption);

			for(var j=0;j<dataTypes[fieldsArr[i]].length;j++) {
				var fieldOption = document.createElement('option');
				fieldOption.value = '"' + dataTypes[fieldsArr[i]][j] + '"';
				fieldOption.appendChild(document.createTextNode(dataTypes[fieldsArr[i]][j]));
				fieldSelect.appendChild(fieldOption);
			}
		} else {
			var fieldInput = document.createElement('input');
			fieldInput.onkeypress = formKeyPress;
		fieldInput.type = 'text';
		fieldInput.className = 'textBox';
		fieldInput.name = myField;
		fieldInput.id = 'field-' + myField;
		fieldDiv.appendChild(fieldInput);
		if(dateFunctions && (fieldsArr[i].search(/date/i) != -1 || fieldsArr[i].search(/DOB/) != -1)) {
			tmpImg = document.createElement('img');
			tmpImg.src = '../images/edit_16.gif';
			tmpImg.style.cursor = 'pointer';
			tmpImg.style.verticalAlign = 'middle';
			tmpImg.input = fieldInput;
			tmpImg.whereID = 'searchFields';
			tmpImg.onclick = dispCurrMonth;
			fieldDiv.appendChild(tmpImg);
			var tmpInput = document.createElement('input');
				var dateDiv = document.createElement('div');
			tmpInput.type = 'checkbox';
				tmpInput.onkeypress = formKeyPress;
				tmpInput.id = myField + '-range';
				tmpInput.field = myField;
				tmpInput.onclick = toggleRangeSearch;
				dateDiv.appendChild(tmpInput);
			labelSpan = document.createElement('span');
			labelSpan.style.fontSize = '8pt';
			txtNode = document.createTextNode('Search By Date Range');
			labelSpan.appendChild(txtNode);
				dateDiv.appendChild(labelSpan);
		}
	}
		mainDiv.appendChild(fieldDiv);
		mainDiv.id = myField + '-div';
		searchFields.appendChild(mainDiv);
		if(dateDiv) {
			searchFields.appendChild(dateDiv);
			dateDiv = null;
		}
	}
	for(var i = 0; i < advancedArr.length; i++) {
		if(document.cabSearchForm[advancedArr[i]]) {
			document.cabSearchForm[advancedArr[i]].value = "";
		}
	}
	var dateRangeBtn = $('zzdate-range');
	if(dateRangeBtn) {
		if(dateRangeBtn.checked == true) {
			dateRangeBtn.onclick();
			dateRangeBtn.checked = false;
		}
	}
	$('cabSearchDiv').style.display = "block";
	$('advancedBtn').style.display = "block";
	$('submitDiv').style.display = 'block';
	var myAction = "searchResults.php?cab=" + newCabinet;
	document.cabSearchForm.action = myAction;
	if (loadingBookmark) {
		loadingBookmark = false;
		if(!deletedBM) {
			deletedBM = false;
			if(bookArr[bookNum].fields) {
				for(var field in bookArr[bookNum].fields) {
					tmpArr = field.match(/(.*)-dRng/);
					if (tmpArr != null) {
						tmpField = tmpArr[1].toUpperCase();
						if($(tmpField + '-range')) {
							$(tmpField + '-range').onclick();
							$(tmpField + '-range').checked = true;
							$('field-dRng-' + tmpField).value = bookArr[bookNum].fields[field];
						}
					} else {
						if($('field-' + field.toUpperCase()).nodeName == "SELECT" ) {
							var selArr = $('field-' + field.toUpperCase());
							for(var j=0;j<selArr.length;j++) {
								if( selArr[j].value == bookArr[bookNum].fields[field] ) {
									selArr[j].selected = true;
								}
							}
						} else {
							if($('field-' + field.toUpperCase())) {
								$('field-' + field.toUpperCase()).value = bookArr[bookNum].fields[field];
							}
						}
					}
				}
			}
			for(var i = 0; i < advancedArr.length; i++) {
				if(document.cabSearchForm[advancedArr[i]]) {
					document.cabSearchForm[advancedArr[i]].value = "";
				}
			}
			if(bookArr[bookNum].advanced) {
				showAdvanced();
				for(var adv in bookArr[bookNum].advanced) {
					if (adv == 'date2') {
						$('zzdate-range').onclick();
						$('zzdate-range').checked = true;
						$('field-dRng-zzdate').value =
							bookArr[bookNum].advanced['date2'];
					} else {
						if(document.cabSearchForm[adv]) {
					document.cabSearchForm[adv].value =
						bookArr[bookNum].advanced[adv];
				}
					}
				}
			} else {
				hideAdvanced();
			}
			document.cabSearchForm.submit();
		}

	}
}

function cabinetDeleted(myCab) {
	var depSelect = $('DepartmentID');
	for(var i = 0; i < depSelect.length; i++) {
		if(depSelect.options[i].value == myCab) {
			depSelect.removeChild(depSelect.options[i]);
			break;
		}
	}
}

function toggleRangeSearch() {
	var fieldName = this.field;
	var fieldNameDiv;
	if(this.fieldDiv) {
		fieldNameDiv = this.fieldDiv;
	} else {
		fieldNameDiv = fieldName;
	}
	var searchFields = $('searchFields');
	var toDiv = $(fieldNameDiv + '-dRngDiv');
	if (toDiv) {
		toDiv.parentNode.removeChild(toDiv);
	} else {
		var fromDiv = $(fieldNameDiv + '-div');
		var toDiv = document.createElement('div');
		var labelDiv = document.createElement('div');
		toDiv.id = fieldNameDiv + '-dRngDiv';
		var inputDiv = document.createElement('div');
		var input = document.createElement('input');
		input.onkeypress = formKeyPress;
		input.type = 'text';
		input.className = 'inputBox';
		input.name = fieldName + '-dRng';
		input.id = 'field-dRng-' + fieldNameDiv;
		txtNode = document.createTextNode('to');
		labelDiv.appendChild(txtNode);
		labelDiv.style.fontWeight = 'bold';
		labelDiv.style.fontSize = '8pt';
		toDiv.appendChild(labelDiv);
		inputDiv.appendChild(input);
		toDiv.appendChild(inputDiv);
		tmpImg = document.createElement('img');
		tmpImg.src = '../images/edit_16.gif';
		tmpImg.style.cursor = 'pointer';
		tmpImg.style.verticalAlign = 'middle';
		tmpImg.input = input;
		tmpImg.whereID = 'searchFields';
		tmpImg.onclick = dispCurrMonth;
		inputDiv.appendChild(tmpImg);
		fromDiv.parentNode.insertBefore(toDiv, fromDiv.nextSibling);
	}
}

function changeCabSearch() {
	var cabSelect = $('DepartmentID');
	var selectedCab = cabSelect.options[cabSelect.selectedIndex].value;
	hideAdvanced();
	clearBookmarks();
	top.mainFrame.location = 'searchResults.php?cab=' + selectedCab;
	for(myNode in cabSelect.childNodes) {
		if(cabSelect.childNodes[myNode].value == '__chooseCab') {
			cabSelect.removeChild(cabSelect.childNodes[myNode]);
			break;
		}
	}
}

function changeDocSearch() {
	var docSelect = $('docType');
	var selectedDoc = docSelect.options[docSelect.selectedIndex].value;
	removeDefault(docSelect);
	loadDocument(selectedDoc);

	var myAction = "../documents/searchDocumentView.php";
	if(selectedDoc) {
		myAction += "?docType="+selectedDoc;
	}
	document.docSearchForm.action = myAction;
	docType = selectedDoc;
}

function filterSearch() {
	removeAllThumbs();
	if(cabinet != '' && $("searchCab").checked) {
		var myAction = "&cabinet="+cabinet;
		document.docSearchForm.action += myAction;
		for(var i=0;i<fieldsArr.length;i++) {
			if(el = $('field-'+fieldsArr[i])) {
				var val = el.value;
				if(val) {
					var myAction = "&"+fieldsArr[i]+"="+val;
					document.docSearchForm.action += myAction;
				}
			}
		}
	}
}

function loadDocument(documentName) {
	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "getDocumentFields",
					"document_table_name" : documentName,
					"noCallBackFunction" : 1};
	postXML(xmlArr);
}

function setDocument(XML) {
	var fields = XML.getElementsByTagName('FIELD');
	if(fields.length > 0) {
		removeElementsChildren($('docSearchFields'));
		for(var i=0;i<fields.length;i++) {
			var mainDiv = document.createElement('div');
			mainDiv.style.width = '100%';

			var labelDiv = document.createElement('div');
			txtNode = document.createTextNode(fields[i].firstChild.nodeValue);	
			labelDiv.appendChild(txtNode);
			mainDiv.appendChild(labelDiv);

			var fieldDiv = document.createElement('div');
			var defsArr = fields[i].getElementsByTagName('DEFINITION');
			if(defsArr.length > 0) {
				var fieldInput = document.createElement('select');
				
				var opt = document.createElement('option');
				fieldInput.appendChild(opt);
				for(var j=0;j<defsArr.length;j++) {
					var opt = document.createElement('option');
					opt.value = defsArr[j].firstChild.nodeValue;
					opt.appendChild(document.createTextNode(defsArr[j].firstChild.nodeValue));
					fieldInput.appendChild(opt);
				}
			} else {
				var fieldInput = document.createElement('input');
				//fieldInput.onkeypress = formKeyPress;
				fieldInput.type = 'text';
				fieldInput.className = 'textBox';
			}
			fieldInput.name = fields[i].getAttribute('name');
			fieldInput.id = 'field-' + fields[i].getAttribute('name');
			fieldDiv.appendChild(fieldInput);
			mainDiv.appendChild(fieldDiv);
			$('docSearchFields').appendChild(mainDiv);
		}

		var mainDiv = document.createElement('div');
		var labelSpan = document.createElement('span');
		labelSpan.style.fontSize = '8pt';
		var chkbox = document.createElement('input');
		chkbox.type = "checkbox";
		chkbox.id = "searchCab";
		chkbox.name = "searchCabFirst";
		chkbox.value = "yes";
		labelSpan.appendChild(chkbox);
		labelSpan.appendChild(document.createTextNode("Search Cabinet Fields First"));
		mainDiv.appendChild(labelSpan);

		$('docSearchFields').appendChild(mainDiv);
		chkbox.checked = true;//"checked";
		
		$('docSearchDiv').style.display = 'block';
		$('docSubmitDiv').style.display = 'block';
	}
}

function repError() {

}

function removeCab(noClearTLS) {
	var depSelect = $('DepartmentID');
	if(depSelect) {
		var optionExists = false;
		//Have the choose cab option be selected
		for(var i = 0; i < depSelect.length; i++) {
			if(depSelect.options[i].value != "__chooseCab") {
				depSelect.options[i].selected = false;
			} else {
				depSelect.options[i].selected = true;
				optionExists = true;
			}
		}
		
		//if the choose cab selection does not exist, add and select	
		if( optionExists == false ) {
			var newOption = document.createElement('option');
			newOption.value = '__chooseCab';
			newOption.selected = true;
			newOption.appendChild(document.createTextNode('Choose a Cabinet'));
			depSelect.appendChild(newOption);
		}

		//if there is more than one selectable cabinet;
		//length > 2 because of the choose cab option
		if(depSelect.options.length > 2) {
			$('cabLabel').style.display = 'none';
			$('cabSearchDiv').style.display = 'none';
			$('submitDiv').style.display = 'none';
			fieldsArr = "";
			cabinet = "";
		}
		
		if(noClearTLS == null) {
			document.tlsForm.searchInput.value = '';
			$('radioAll').checked = true;
		}
		hideAdvanced();
	}
	if (loadingBookmark) {
		loadingBookmark = false;
	} else {
		clearBookmarks();
	}
	showAddNote = false;
}

function clearBookmarks() {
	var bkSelect = $('bookmark');
	if(bkSelect) {
		if(bkSelect.length == 0 || bkSelect.options[bkSelect.selectedIndex].value != '__bookmarks') {
			var newOption = document.createElement('option');
			newOption.selected = 'true';
			newOption.value = '__bookmarks';
			newOption.appendChild(document.createTextNode('Bookmarked Searches'));
			bkSelect.appendChild(newOption);
		}
	}
}

function clearForTLS() {
	removeCab(true);
	removeAllThumbs();
	if(parent.topMenuFrame.removeBackButton) {
		parent.topMenuFrame.removeBackButton();
	}
}

function removeAllThumbs() {
	parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	parent.sideFrame.window.location = '../energie/left_blue_search.php';
}

function clearForCabSearch() {
	var advSearchDiv = $('advancedSearch');	
	var inputEl = advSearchDiv.getElementsByTagName('input');
	var isValueExist = false;
	for(var i=0;i<inputEl.length;i++) {
		if(inputEl[i].type == 'text' && inputEl[i].value) {
			isValueExist = true;
			break;
		}
	}

	if($('advancedSearch').style.display == 'block') {
		if(isValueExist) {
			document.tlsForm.searchInput.value = '';
			$('radioAll').checked = true;
			clearBookmarks();
			removeAllThumbs();
			if(parent.topMenuFrame.removeBackButton) {
				parent.topMenuFrame.removeBackButton();
			}
			document.cabSearchForm.submit();
		} else {
				alert('Must fill in at least 1 advanced search term');
		}
	} else {
		document.cabSearchForm.submit();
	}
}

function showHideAdvanced() {
	var advancedSearch = $('advancedSearch');
	if(advancedSearch.style.display == 'none' || advancedSearch.style.display == '') {
		showAdvanced();
	} else {
		hideAdvanced();
	}
}

function showAdvanced() {
	$('advancedSearch').style.display = 'block';
	var myAction = "file_search_results.php?cab=" + cabinet;
	document.cabSearchForm.action = myAction;
}

function hideAdvanced() {
	if(el = $('advancedSearch')) {
		el.style.display = 'none';
	}
	if(form = document.cabSearchForm) {
		var myAction = "searchResults.php?cab=" + cabinet;
		form.action = myAction;
	}
}

function showBookmarks(show) {
	if($('bookmark') != null) {
		if(($('bookmark').options.length > 1) || show) {
			$('bkDiv').style.display = 'block';
		}
	}
}

function loadBookmarks(num, from) {
	bookNum = num;
	if(from) {
		fromPanel = true;
	}
	var xmlArr = {	"include" : "lib/panelFuncs.php",
					"function" : "getBookmarks"};
	postXML(xmlArr);
}

function setBookmarks(XML) {
	var bkSelect = $('bookmark');
	while (bkSelect.hasChildNodes()) {
		bkSelect.removeChild(bkSelect.lastChild);
	}
	bookArr = createBookmarkObject(XML);
	var newOption;
	for(var i in bookArr) {
		var selectedOption = false;
		if(i == bookNum) {
			selectedOption = true;
		}
		newOption = document.createElement('option');
		newOption.value = i;
		newOption.appendChild(document.createTextNode(bookArr[i].name));
		newOption.selected = selectedOption;
		bkSelect.appendChild(newOption);
	}
	if(fromPanel) {
		if(bookArr[bookNum].topLevel) {
			loadingBookmark = true;
			removeCab();
			document.tlsForm.searchInput.value = bookArr[bookNum].topLevel;
			if(bookArr[bookNum].exact == "1") {
				$('radioAll').checked = true;
			} else {
				$('radioAny').checked = true;
			}
			document.tlsForm.submit();
		} else {
			document.tlsForm.searchInput.value = '';
			loadingBookmark = true;
			loadFields(bookArr[bookNum].cabinet, bookNum);
		}
	}
	showBookmarks(true);
}

function setFields(XML) {
	var fieldList = XML.getElementsByTagName("FIELD");
	if(fieldList.length > 0) {
		var myFields = new Array();
		for(i=0;i<fieldList.length;i++) {
			myFields[i] = fieldList[i].firstChild.nodeValue;
		}
	}

	var security = XML.getElementsByTagName("SECURITY");
	if(sec = security[0].firstChild) {
		sec = parseInt(sec.nodeValue);
	}
	var cabEls = XML.getElementsByTagName('CABINET');
	var cabinet = cabEls[0].firstChild.nodeValue;

	var dataType = new Array();
	var dtList = XML.getElementsByTagName("DTYPE");
	if(dtList.length > 0) {
		for(i=0;i<dtList.length;i++) {
			var k = dtList[i].getAttribute('key');
			var v = dtList[i].firstChild.nodeValue;
			if(!dataType[k]) {
				dataType[k] = new Array();
			}
			dataType[k][i] = v;
		}
	}
	setCabinet(cabinet, myFields, sec, true, dataType);
}

function removeBookmark(XML) {
	var bkSelect = $('bookmark');

	var bookList = XML.getElementsByTagName("BOOKMARK");
	if(bookList.length > 0) {
		for(i=0;i<bkSelect.length;i++) {
			for(j=0;j<bookList.length;j++) {
				id = bookList[j].firstChild.nodeValue;
				if(bkSelect.options[i].value == id) {
					bkSelect.removeChild(bkSelect.options[i]);
				}
			}
		}
	}
	clearBookmarks();
	deletedBM = true;

}

function loadFields(cabinet, bNum) {
	var xmlArr = {	"include" : "lib/panelFuncs.php",
			"function" : "getFields",
			"cabinet" : cabinet };
	postXML(xmlArr);
}

function createBookmarkObject(XML) {
	var bookArr = new Object();
	var bookList = XML.getElementsByTagName('BOOKMARK');
	if(bookList.length > 0) {
		for(i=0;i<bookList.length;i++) {
			var newObj = new Object();
			var searchList = bookList[i].getElementsByTagName("SEARCH");
			if(searchList.length > 0) {
				for(j=0;j<searchList.length;j++) {
					var k = searchList[j].getAttribute('key');
					var v = searchList[j].firstChild.nodeValue;
					newObj[k] = v;	
				}
			}

			var fieldList = bookList[i].getElementsByTagName("FIELD");
			if(fieldList.length > 0) {
				var fieldObj = new Object();
				for(j=0;j<fieldList.length;j++) {
					var k = fieldList[j].getAttribute('key');
					var v = fieldList[j].firstChild.nodeValue;
					fieldObj[k] = v;	
				}
				newObj['fields'] = fieldObj;
			}
			var bNum = bookList[i].getAttribute('id');	
			bookArr[bNum] = newObj;
		}
	}
	return bookArr;
}

function selectBookmark() {
	var bkSelect = $('bookmark');
	for(myNode in bkSelect.childNodes) {
		if(myNode.value == '__bookmarks') {
			bkSelect.removeChild(myNode);
		}
	}
	var selBk = bkSelect.options[bkSelect.selectedIndex].value;
	loadBookmarks(selBk, true);
}

function showNotes(newFileID) {
	if( newFileID ) {
		fileID = newFileID;
	}
	
	var xmlArr = {	"include" : "lib/panelFuncs.php",
			"function" : "getNotes",
			"cabinet" : cabinet,
			"fileID" : fileID };
	postXML(xmlArr);
}

function setNotes(XML) {
	var htmlStr = "";
	var notes = "";

	var htmlID = XML.getElementsByTagName("HTML_ID");
	if(htmlID.length > 0) {
		htmlStr = htmlID[0].firstChild.nodeValue;	
	}
	if(htmlStr != "" ) {
		showNotesImageInAllthumbs(htmlStr);
	}

	var noteStr = XML.getElementsByTagName("NOTE");
	if(noteStr.length > 0) {
		notes = noteStr[0].firstChild.nodeValue;	
	}
	$('oldNotes').value = notes;

	showNotesButton();
	showNotesDiv();
}

function showNotesImageInAllthumbs(str) {
	parent.sideFrame.document.getElementById(str).style.visibility = 'visible';
}

function showNotesButton() {
	if(el = $('toolsGifDiv')) {
		el.style.visibility = 'visible';
	}
}

function hideNotesButton() {
	if(el = $('toolsGifDiv')) {
		el.style.visibility = 'hidden';
	}
}

function showNotesDiv() {
	if(el = $('searchTab')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('searching')) {
		el.style.display = 'none';
	}

	if(el = $('documentTab')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('docSearching')) {
		el.style.display = 'none';
	}

	if(el = $('toolsGifDiv')) {
		el.className = 'panelImgDiv';
	}
	if(el = $('newNotes')) {
		el.style.display = 'block';
		var addNoteDiv = $('addNoteDiv');
		addNoteDiv.style.display = (showAddNote) ? 'block' : 'none';
	}
}

function showDocSearch() {
	if(el = $('searchTab')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('searching')) {
		el.style.display = 'none';
	}

	if(el = $('documentTab')) {
		el.className = 'panelImgDiv';
	}
	if(el = $('docSearching')) {
		el.style.display = 'block';
	}

	if(el = $('toolsGifDiv')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('newNotes')) {
		el.style.display = 'none';
	}

	var myAction = "../documents/searchDocumentView.php";
	if(docType) {
		myAction += "?docType="+docType;
	}
	document.docSearchForm.action = myAction;
}

function hideNotes() {
	if(el = $('searchTab')) {
		el.className = 'panelImgDiv';
	}
	if(el = $('searching')) {
		el.style.display = 'block';
	}

	if(el = $('documentTab')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('docSearching')) {
		el.style.display = 'none';
	}

	if(el = $('toolsGifDiv')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('newNotes')) {
		el.style.display = 'none';
	}
}

function showCabSearch() {
	if(el = $('searchTab')) {
		el.className = 'panelImgDiv';
	}
	if(el = $('searching')) {
		el.style.display = 'block';
	}

	if(el = $('documentTab')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('docSearching')) {
		el.style.display = 'none';
	}

	if(el = $('toolsGifDiv')) {
		el.className = 'panelImgDiv1';
	}
	if(el = $('newNotes')) {
		el.style.display = 'none';
	}

	var myAction = '';
	if ($('advancedSearch').style.display == 'block') {
		myAction += 'file_search_results.php';
	} else {
		myAction += 'searchResults.php';
	}
	if(cabinet) {
		myAction += '?cab=' + cabinet;
	}
	document.cabSearchForm.action = myAction;
}

function addNote() {
	var newNote = $('newNote').value;
	var xmlArr = {	"include" : "lib/notes.php",
			"function" : "addFileNote",
			"cabinet" : cabinet,
			"fileID" : fileID,
			"newNote" : newNote };
	postXML(xmlArr);

	$('newNote').value = '';
}

function reverseNotes() {
	var notesText = $('oldNotes').value;
	if(notesText) {
		var notesArray = notesText.split( "\n" );
		var notesReversed = "";
		for(var i = 0; i < notesArray.length; i++) {
			if( notesReversed ){
				if( notesArray[i] != "" )
				notesReversed = notesArray[i] + "\n" + notesReversed;
			} else {
				if( notesArray[i] != "" )
				notesReversed = notesArray[i] + notesReversed;
		}
		}
		$('oldNotes').value = notesReversed;
	}
}

function loadSearch (searchArr) {
	for (var key in searchArr) {
		var keyUpper = key.toUpperCase();
		if($('field-' + keyUpper)) {
			$('field-' + keyUpper).value = searchArr[key];
		}
	}
}
