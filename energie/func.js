
function refresh(type){
        switch(type){
        case 1:
            		parent.menuFrame.window.location="main_menu.php";
			parent.bottomFrame.window.location="bottom_white.php";
			parent.sideFrame.window.location="bottom_white.php";
        break;

        case 2:
		        parent.menuFrame.window.location="main_menu.php";
			parent.bottomFrame.window.location="bottom_white.php";
			parent.sideFrame.window.location="bottom_white.php";
			if(parent.searchPanel.removeCab) {
				parent.searchPanel.removeCab();
			}
        break;
        case 3:
            		parent.menuFrame.window.location="main_menu.php";
			parent.bottomFrame.window.location="bottom_white.php";
			parent.sideFrame.window.location="bottom_white.php";
        break;
        case 4:
            		parent.menuFrame.window.location="main_menu.php";
			parent.bottomFrame.window.location="bottom_white.php";
			parent.sideFrame.window.location="bottom_white.php";
        break;
        }
    }


// This is in this function so that it could be called from the mainFrame, but 
// then the mainFrame gets destroyed, therefore all the objects get destroyed,
// including the onclick event attached to the div created below. It makes sense
// to put it in this file because the button that gets created is in this frame.
function dispVers(cabinetName, docID, orderingID, tabName, fileID, reloadArgs, pAudit, vers,docView)
{
	var myDiv = top.topMenuFrame.document.getElementById('vers');
	myDiv.onclick = Function("clickBackVers('" + reloadArgs + "','"+docView+"');");
	myDiv.style.display = "block";
	urlStrMF = "../energie/display.php?doc_id=" + docID + "&cab=" + cabinetName;
	urlStrMF = urlStrMF + "&ID=" + orderingID + "&tab=" + tabName + "&fileID=";
	urlStrMF = urlStrMF + fileID;
	top.leftFrame1.window.location = "../versioning/auditDisp.php?pAudit=" + pAudit + "&vers=" + vers;
	if(docView == "1") {
		top.viewFileActions.window.location = urlStrMF + '&docView=1';
	} else {
		top.mainFrame.window.location = urlStrMF + '&docView=0';
	}
}

function clickBackVers(reloadArgs,docView)
{
	var newArgs = reloadArgs.split('?');
	reloadArgs = "../versioning/vFHFrame.php?" + newArgs[1];
	if(docView == "1") {
		parent.viewFileActions.location = reloadArgs;
	} else {
		parent.mainFrame.location = reloadArgs;
	}
	removeVersButton();
}

function removeVersButton(noRemoveNotes)
{
	if (document.getElementById('vers')) {
		document.getElementById('vers').style.display = "none";
	}
	if(!noRemoveNotes) {
		if(top.searchPanel.hideNotes) {
			top.searchPanel.hideNotes();
			top.searchPanel.hideNotesButton();
		}
	}
}

//cz 2010-12-03
function dispSignedFile(folderLoc, subfolder, filename, reloadArgs, docView) //pAudit, vers,
{
	removeVersButton();
    var myDiv = top.topMenuFrame.document.getElementById('docusign');
    myDiv.onclick = Function("clickBackSignFiles('" + reloadArgs + "','" + docView + "');");
    myDiv.style.display = "block";

    var test = 'folder=' + folderLoc + '&subfolder=' + subfolder + '&filename=' + filename;
    //alert('dispSignedFile: ' + test)
    top.viewFileActions.window.location = 'dsViewFile.php?' + test;
    top.document.getElementById('folderViewSet').setAttribute('rows', '*,100%');
}

function clickBackSignFiles(reloadArgs, docView) {
    //alert('clickBackSignFiles(): reloadArgs - '+ window.name);
    var newArgs = reloadArgs.split('?');
    reloadArgs = "../docuSign/dsFHFrame.php?" + newArgs[1];
    if (docView == "1") {
        parent.viewFileActions.location = reloadArgs;
    } else {
        parent.mainFrame.location = reloadArgs;
    }
    removeBackToSignFileButton();
}

function removeBackToSignFileButton(noRemoveNotes) 
{	
    if (document.getElementById('docusign'))
    {
    	document.getElementById('docusign').style.display = "none";
    }
    if (!noRemoveNotes) {
        if (top.searchPanel.hideNotes) {
        top.searchPanel.hideNotes();
        top.searchPanel.hideNotesButton();
        }
    }
}

function removeBackButton()
{
	if(document.getElementById('up')) {
		var currObj = document.getElementById('up');
		currObj.style.display = "none";
		removeVersButton();
	} 
}

//cz 2010-12-03
function removeBackToResultsButton() 
{
    if (document.getElementById('up')) {
        var currObj = document.getElementById('up');
        currObj.style.display = "none";
    }
}

function removeBackButtons() 
{
    removeBackToResultsButton();
    removeVersButton();
    removeBackToSignFileButton();
}

function addOnClick(referer, cab, resPerPage, pageNum, numResults, temp_table, index)
{
	if(referer == "file_search_results.php") {
		document.getElementById('up').onclick = function () {
													removeBackButton();
													parent.mainFrame.window.location="../energie/file_search_results.php?cab="+cab + "&temp_table=" + temp_table + "&resPerPage=" + resPerPage + "&fromBack=1&pageNum=" + pageNum + "&numResults=" + numResults;
													};
	} else if(referer == "documentSearch") {
		document.getElementById('up').onclick = function () {
														removeBackButton();
														parent.mainFrame.window.location = '../documents/searchDocumentView.php?page='+pageNum;
															}
	} else {
		document.getElementById('up').onclick = new Function("removeBackButton();parent.mainFrame.window.location='../energie/searchResults.php?cab=" + cab + "&index=" + index + "&table=" + temp_table + "&allthumbs=1" + "';");
	}
}

function addBackButtonOnclick() {
	parent.topMenuFrame.getEl('up').style.display = 'block';
	parent.topMenuFrame.getEl('up').onclick = function() {
		parent.document.getElementById('folderViewSet').setAttribute('rows', '100%,*');
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
		parent.topMenuFrame.getEl('up').style.display = 'none';
		parent.viewFileActions.window.location = '../energie/bottom_white.php';
	};
}

function getNextSibByTag(Elem, tagName) {
	var tmpElem = Elem;
	while(tmpElem != null && tmpElem.tagName != tagName) {
		tmpElem = tmpElem.nextSibling;
	}
	return tmpElem;
}

function getPrevSibByTag(Elem, tagName) {
	var tmpElem = Elem;
	while(tmpElem != null && tmpElem.tagName != tagName) {
		tmpElem = tmpElem.previousSibling;
	}
	return tmpElem;
}

function inputFilter(e) {
	var code;
	if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	if(code == 34 || code == 39 || code == 92) {
		return false;
	}
	if(code == 13 && typeof(formToSubmit) != 'undefined') {
		eval('document.' + formToSubmit + '.submit();');
	}
	return true;
}
