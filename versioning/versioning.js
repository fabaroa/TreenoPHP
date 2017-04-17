function mOver(sender)
{
	document.getElementById(sender).style.background = "#888888";
}

function mOut(sender)
{
	document.getElementById(sender).style.background = "#ebebeb";
}

function toggleCheckin()
{
	var currObj = document.getElementById('checkinDiv');
	var myButton = document.getElementById('checkinButton');
	var fileObj = document.getElementById('userfile');
	var versionDiv = document.getElementById('versionDiv');
	if(currObj.style.visibility == 'visible') {
		currObj.style.visibility = 'hidden';
		myButton.value = "Check-In File"; 
	} else {
		currObj.style.visibility = 'visible';
		if(document.getElementById('versionDiv')) {
			if(versionDiv.style.visibility == 'visible') {
				versionDiv.style.visibility = 'hidden';
			}
		}
		myButton.value = "Cancel Check-In"; 
	}	
}

function toggleEdit(sender, fileID)
{
	var currObj = document.getElementById('versionDiv');
	var titleP = document.getElementById('chgTitle');
	var myInput = document.getElementById('chgVer');
	var currVerP = document.getElementById(sender);
	var myErrMsg = document.getElementById('errMsg');
	var checkinDiv = document.getElementById('checkinDiv');
	document.getElementById('myID').value = fileID; 
	if(currObj.style.visibility == 'visible') {
		myErrMsg.style.visibility = 'hidden';
		currObj.style.visibility = 'hidden';
	} else {
		myErrMsg.style.visibility = 'hidden';
		var oldVerNum = currVerP.firstChild.nodeValue;
		titleP.firstChild.data = 'Changing Version ' + oldVerNum;
		myInput.value = oldVerNum;
		currObj.style.visibility = 'visible';
		myInput.focus();
		if(checkinDiv.style.visibility == 'visible') {
			checkinDiv.style.visibility = 'hidden';
			document.getElementById('checkinButton').value = "Check-In File"; 
		}
	}	
}

function submitEdit() {
	var newVer = document.getElementById('chgVer').value;
	var myErrMsg = document.getElementById('errMsg');
	var badVersion = false;
	var usedVersions = document.getElementById('usedVersions').value;
	var splitArr;
	var newT;
	var count;
	var tempArr;
	myErrMsg.style.visibility = 'hidden';
	if(myErrMsg.lastChild != myErrMsg.firstChild) 
		myErrMsg.removeChild(myErrMsg.lastChild);
	
	if(isNaN(newVer)) badVersion = true;
	else if(parseFloat(newVer) < 1.0) {
			badVersion = true;
	} else {
		splitArr = newVer.split(".");
		count = 0;
		tempArr = splitArr[0].split("");
		while(tempArr[count] == '0') {
			count++;
		}
		if(count > 0) {
			tempArr = tempArr.slice(count);
			splitArr[0] = tempArr;
			newVer = splitArr.join(".");
		}
		if(splitArr.length == 2) {
			tempArr = splitArr[1].split("");
			if(tempArr[0] == '0' && tempArr[1]) badVersion = true;
		} else newVer = newVer + ".0";
	}
	if(badVersion) {
		myErrMsg.firstChild.data = newVer + ' is not a valid version number.';
		myErrMsg.style.visibility = 'visible';
	} else {
		var usedArr = usedVersions.split(",");
		for(count = 0; count < usedArr.length; count++) {
			if(usedArr[count] == newVer) {
				myErrMsg.firstChild.data = newVer + ' is already used.';
				myErrMsg.style.visibility = 'visible';
				badVersion = true;
				break;
			}
		}
		if(!badVersion) {
			document.getElementById('chgVer').value = newVer;
			document.getElementById('vForm').submit();
		}
	}
	return false;
}

function askfreeze()
{
	document.getElementById('buttonDiv').style.display = "none";
	document.getElementById('freezeDiv').style.display = "block";
	var delCol = document.getElementById('delCol');
	var rbCol = document.getElementById('rbCol');
	var remCol;
	var i = 1;
	var saveCol = document.getElementById('saveCol');
	if(saveCol) {
		document.getElementById('viewCol').style.display = "none";
		saveCol.style.display = "none";
	}
	if(delCol) {
		delCol.style.display = "none";
		rbCol.style.display = "none";
		remCol = true;
	}
	while(document.getElementById('row' + i)) {
		removeActions(remCol, i);
		i++;
	}
}

function highlightRow(rowID)
{
	var i;
	for(i = 0; i < 6; i++) {
		if(document.getElementById(rowID + '-' + i)) {
			document.getElementById(rowID + '-' + i).style.background = '#ff9999';
		}
	}
}

//This function is called when the RollBack TD is clicked in viewFileHistory.
//'reloadArgs' reloads the mainFrame, rbString is created to call RollBack,
//rowID is the number of the row. Each row which is to be deleted is highlighted
//so that the user can see visually which files are going to be deleted.
function clickRollBack(cabinetID, fileID, reloadArgs, rowID, currVer)
{
	hideError();
	if(rowID == 1) {
		displayError('Cannot rollback to current version.');
	} else if(document.getElementById('checkinButton')) {
		displayError('A locked file cannot be rolled back.');
	} else {
		var delCol = document.getElementById('delCol');
		var rbCol = document.getElementById('rbCol');
		var remCol;
		document.getElementById('myaction').value = "rollback";
		document.getElementById('delID').value = fileID;
		document.getElementById('delVer').value = currVer;
		if(delCol) {
			document.getElementById('viewCol').style.display = "none";
			document.getElementById('saveCol').style.display = "none";
			delCol.style.display = "none";
			rbCol.style.display = "none";
			remCol = true;
		}
		i = 1;
		while(i < rowID) {
			highlightRow('row' + i);
			removeActions(remCol, i);
			i++;
		}
		showDelDiv();
		while(document.getElementById('row' + i)) {
			hideRow('row' + i);
			i++;
		}
	}
}

function reloadMainFrame(reloadArgs, allThumbsArgs)
{
	parent.vfhMainFrame.location = reloadArgs;
	top.sideFrame.location = allThumbsArgs;
	
}

function clickDelete(cabinetID, fileID, reloadArgs, rowID, currVer)
{
	hideError();
	var i = 1;
	while(document.getElementById('row' + i)) {
		i++;
	}
	var numVers = i - 1;
	if (numVers == 1) {
		displayError("File could not be deleted.");
	} else if(document.getElementById('checkinButton')) {
		displayError("A locked file cannot be deleted.");
	} else {
		var delCol = document.getElementById('delCol');
		var rbCol = document.getElementById('rbCol');
		var remCol;
		highlightRow('row' + rowID);
		document.getElementById('myaction').value = "delete";
		document.getElementById('delID').value = fileID;
		document.getElementById('delVer').value = currVer;
		if(delCol) {
			document.getElementById('viewCol').style.display = "none";
			document.getElementById('saveCol').style.display = "none";
			delCol.style.display = "none";
			rbCol.style.display = "none";
			remCol = true;
		}
		showDelDiv();
		removeActions(remCol, rowID);
		i = 1;
		while(document.getElementById('row' + i)) {
			if(('row' + i) != ('row' + rowID)) {
				hideRow('row' + i);
			}
			i++;
		}
	}
}

function removeActions(remCol, rowID)
{
	var myImg = document.getElementById('row' + rowID + '-versEdit');
	if(myImg) {
		myImg.style.display = "none";
	}
	if(remCol) {
		document.getElementById('row' + rowID + '-rb').style.display = "none";
		document.getElementById('row' + rowID + '-delete').style.display = "none";
	}
	document.getElementById('row' + rowID + '-0').style.display = "none";
	document.getElementById('row' + rowID + '-1').style.display = "none";
}

function hideRow(rowID) 
{
	document.getElementById(rowID).style.display = "none";
}

function showDelDiv() 
{
	document.getElementById('buttonDiv').style.display = "none";
	document.getElementById('delDiv').style.display = "block";
}

function clickDL(cabinetID, parentID)
{
    var currStr;
    currStr = "downloadVersion.php?parentID=" + parentID + "&cabinetID=";
    currStr = currStr + cabinetID;
    parent.vfhTransFrame.location = currStr;
}

function saveVers(cabinetID, fileID, pAudit, vers)
{
	var currStr;
    currStr = "downloadVersion.php?fileID=" + fileID + "&cabinetID=";
    currStr = currStr + cabinetID;
	parent.vfhTransFrame2.location = "../versioning/auditDisp.php?pAudit=" + pAudit + "&vers=" + vers;
    parent.vfhTransFrame.location = currStr;
}

function displayError(myError)
{
	var currObj = parent.vfhMainFrame.document.getElementById('pageErrMsg');
	currObj.firstChild.data = myError;
	currObj.style.visibility = 'visible';
}

function hideError()
{
	var currObj = parent.vfhMainFrame.document.getElementById('pageErrMsg');
	currObj.style.visibility = "hidden";
}

function reloadVersioning(reloadArgs)
{
	top.viewFileActions.vfhMainFrame.window.location = reloadArgs;
}

function showVersNotes()
{
	document.getElementById('noteDiv').style.display = "block";
	document.getElementById('clkMore').src = "../energie/images/down.gif";
	document.getElementById('noteBar').onclick = hideVersNotes;
}

function hideVersNotes()
{
	document.getElementById('noteDiv').style.display = "none";
	document.getElementById('clkMore').src = "../energie/images/next.gif";
	document.getElementById('noteBar').onclick = showVersNotes;
}

function displayCurrVersNotes(recentID)
{
	top.searchPanel.showNotes(recentID);	
}
