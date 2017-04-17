function pressEnter(e)
{
	var keyPressed;
	if(document.all) {
		keyPressed = window.event.keyCode;
	} else {
		keyPressed = e.keyCode;
	}
	if(keyPressed == 13) {
		document.getElementById('f1').submit();
		return false;
	}
	return true;
}

function addBackButton() {
	var myObj = parent.topMenuFrame.document.getElementById('up');
	if(myObj.style.display == "none") {
		parent.topMenuFrame.addOnClick('file_search_results.php',
									   cabinet,
									   resPerPage,
									   pageNum,
									   numResults,
									   temp_table,
									   '');
		myObj.style.display = "block";
	}
}

function openAllThumbs(docid, pageNum, selected, tab, ordering, count, fileID,docView) {
	if(tab == "main") {
		tab = '';
	}
	if(docView) {
		var url = '../documents/viewDocuments.php?cab='+cabinet+'&doc_id='+docid+'&table='+temp_table+'&file_id='+fileID;
		if(tab) {
			url += '&tab='+tab;
		} else {
			url += '&tab_id=-1';
		}
		parent.sideFrame.window.location=url; 
		parent.document.getElementById('rightFrame').setAttribute('rows', '*,0');
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,215');

	} else {
		addBackButton();
		parent.sideFrame.window.location='allthumbs.php?doc_id=' + docid 
			+ '&referer=file_search_results.php&table=' + temp_table
			+ '&pageNum=' + pageNum + '&resPerPage=' + resPerPage + '&cab=' + cabinet
			+ '&selected=' +selected+ '&ID=' +ordering+ '&tab=' +tab
			+ '&count=' + count +'&numResults=' + numResults + '&fileID=' + fileID;
		parent.mainFrame.window.location = 'display.php?cab=' + cabinet + '&fileID=' + fileID;
	}
}

function getResPerPage(obj)
{
	parent.mainFrame.window.location='file_search_results.php?cab='
		+ cabinet + '&temp_table=' + temp_table + '&fromBack=1&pageNum=' + pageNum
		+ '&resPerPage=' + obj;
}

function allowDigi(evt,myPageNum) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : 
		((evt.which) ? evt.which : evt.keyCode);
	var uPageNum;

	if( (myPageNum > 0) && (myPageNum <= totalPages) ) {
		uPageNum = parseInt(myPageNum);
	} else {
		uPageNum = myPageNum;
	}
	if(charCode == 13) {
		parent.mainFrame.window.location = 'file_search_results.php?'
			+ 'cab=' + cabinet + '&temp_table=' + temp_table + '&fromBack=1&pageNum='
			+ uPageNum+'&resPerPage=' + resPerPage;
	}
	if (((charCode >= 48 && charCode <= 57) // is digit
		|| charCode == 8) || (charCode == 37) || (charCode == 39)) { // is enter or backspace key   
		
		return true;
	}
	else { // non-digit
		return false;
	}
}

function navArrowsUp() { 
	var upindex = parseInt(pageNum) + 1;
	if(upindex > totalPages) {
		upindex = parent.mainFrame.document.getElementById("totalPages").value;
	}
	window.location = "file_search_results.php?cab="+cabinet+"&pageNum="+upindex
		+"&resPerPage=" + resPerPage + "&fromBack=1&temp_table=" + temp_table+ "&"
		+ "numResults=" + numResults;
}

function navArrowsDown() {         
	var downindex = pageNum - 1;
	if(downindex < 1) {
		downindex = 1;
	}
	window.location = "file_search_results.php?cab="+ cabinet +"&pageNum=" +
		+ downindex +"&resPerPage=" + resPerPage + "&fromBack=1&" +
		+ "temp_table=" + temp_table + "&numResults=" + numResults;
}
function navArrowsBegin() {          
	window.location = "file_search_results.php?cab="
		+ cabinet +"&pageNum=1&" + "resPerPage=" + resPerPage+ "&fromBack=1&"
		+ "temp_table=" + temp_table + "&numResults=" + numResults;
}
function navArrowsEnd()
{
	var count = document.getElementById("totalPages").value;
	window.location = "file_search_results.php?cab="+cabinet
		+ "&pageNum="+count+"&" + "resPerPage=" + resPerPage + "&fromBack=1"
		+ "&temp_table=" + temp_table + "&numResults=" + numResults;
}
function dialog(str,fileID,tmpfile) {
	
    window.location = "../energie/editName.php?cab="+cabinet+"&str="+str
		+ "&fileID="+fileID+"&newFilenamePart="+tmpfile+"&table="
		+ temp_table+"&resPerPage="+resPerPage+"&fromBack=1" +
		+ "&pageNum="+pageNum+"&numResults="+numResults + "&mfURL=" + 
		window.location;
}

function askAdmin(id,name,tab) {
	var message = "Are you sure you want to delete this file?";
	var answer = window.confirm(message);
	if(answer) {
		window.location = "deleteFileSearch.php?doc_id="+id+"&cab="+cabinet
			+ "&name="+name+"&tab="+tab+"&temp_table="+temp_table;
	}
}

function booknameSpotClick() {
	var myInput = document.createElement('input');
	var allBtns = document.getElementById('allBtns');
	var bookmarkImg = document.getElementById('bookmarkImg');
	var saveImg = document.createElement('img');
	var cancelImg = document.createElement('img');
	saveImg.src = '../energie/images/save.gif';
	saveImg.style.cursor = 'pointer';
	saveImg.onclick = function() {
		document.getElementById('f1').submit();
	};
	allBtns.replaceChild(saveImg, bookmarkImg);
	cancelImg.src = '../energie/images/cancl_16.gif';
	cancelImg.style.cursor = 'pointer';
	cancelImg.onclick = function() {
		allBtns.replaceChild(bookmarkImg, cancelImg);
		allBtns.removeChild(myInput);
		allBtns.removeChild(saveImg);
	};
	allBtns.insertBefore(cancelImg, saveImg);
	myInput.type = 'text';
	myInput.onkeypress = pressEnter;
	myInput.name = 'newBookmarkName';
	myInput.value = "Bookmark Name";
	allBtns.insertBefore(myInput, cancelImg);
	myInput.focus();
	myInput.select();

//	var iHTML = '<input type="text" onkeypress="return pressEnter(event)" '
//		+ 'name="newBookmarkName" value="Bookmark Name"><input type="submit" '
//		+ 'name="submitBookmark" id="submitBookmark" value="Submit"/>';
//	document.getElementById('booknamespot').innerHTML = iHTML;
}

function submitBtn(type) {
	var myLoc = window.location.protocol;
	myLoc += "//" + window.location.hostname;
	myLoc += window.location.pathname;
	var myQuery =  window.location.search.replace('?', '');
	myQuery = myQuery.replace(/func=[a-zA-Z_]*&/, '');
	myQuery = '?func=' + type + '&' + myQuery;
	window.location.href = myLoc + myQuery;
}

function mOver(row) {
	if (row != mySelected) {
		document.getElementById(row).style.backgroundColor = '#888888';
	} else {
		document.getElementById(row).style.backgroundColor = '#8779e0';
	}
}

function mOut(row) {
	if (row != mySelected) {
		document.getElementById(row).style.backgroundColor = '#ebebeb';
	} else {
		document.getElementById(row).style.backgroundColor = '#8799e0';
	}
}
