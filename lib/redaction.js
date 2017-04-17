function mouseDown(e) {
	if(myFunc != 'delete' && !moveStuff) {
		var event = e ? e : window.event;
		tempDiv = newEl('div');
		if(myFunc == 'redact') {
			tempDiv.className = 'redact';
			tempDiv.style.backgroundColor = redactColor;
		} else {
			tempDiv.className = 'highlight';
			tempDiv.style.backgroundColor = highlightColor;
		}
		var tempX = event.clientX + readScroll.scrollLeft;
		var tempY = event.clientY + readScroll.scrollTop;
		coord.x = tempX;
		coord.y = tempY;
		tempDiv.style.position = 'absolute';
		tempDiv.style.top = tempY - drawAreaOffTop + 'px';
		tempDiv.style.left = tempX - drawAreaOffLeft + 'px';
		tempDiv.style.height = '5px';
		tempDiv.style.width = '5px';
		tempDiv.onclick = boxFuncs;
		addMoveEvents(tempDiv);
		tempDiv.style.cursor = 'pointer';
		drawArea.appendChild(tempDiv);
		drawing = true;
	}
}

function mouseMove(e) {
	if(myFunc != 'delete' && drawing) {
		var event = e ? e : window.event;
		var tempX = event.clientX + readScroll.scrollLeft;
		var tempY = event.clientY + readScroll.scrollTop;
		if(coord.x == tempX) {
			tempDiv.style.width = '5px';
			tempDiv.style.left = coord.x - drawAreaOffLeft + "px";
		} else {
			var tempWidth = Math.abs(coord.x - tempX);
			if(tempWidth < 5) {
				tempWidth = 5;
			}
			tempDiv.style.width = tempWidth + 'px';
			if(coord.x > tempX) {
				tempDiv.style.left = tempX - drawAreaOffLeft + "px";
			} else {
				tempDiv.style.left = coord.x - drawAreaOffLeft + "px";
			}
		}
		if(coord.y == tempY) {
			tempDiv.style.height = '5px';
			tempDiv.style.top = coord.y - drawAreaOffTop + "px";
		} else {
			var tempHeight = Math.abs(coord.y - tempY);
			if(tempHeight < 5) {
				tempHeight = 5;
			}
			tempDiv.style.height = tempHeight + 'px';
			if(coord.y < tempY) {
				tempDiv.style.top = coord.y - drawAreaOffTop + "px";
			} else {
				tempDiv.style.top = tempY - drawAreaOffTop + "px";
			}
		}
	}
	return false;
}

function mouseUp(e) {
	if(myFunc != 'delete' && drawing) {
		drawing = false;
		tempDiv = null;
	}
}

function changeToggle() {
	myFunc = getEl('selToggle').value;
	var textBtn = getEl('drawTextOverlay');
	if(tempDiv) {
		drawArea.removeChild(tempDiv);
		tempDiv = null;
	}
	if(myFunc == 'delete') {
		drawArea.style.cursor = 'default';
	} else {
		drawArea.style.cursor = 'crosshair';
	}
	if(myFunc == 'highlight') {
		showColorToggle();
	} else {
		hideColorToggle();
	}
	if(myFunc == 'stamp') {
		showStampToggle();
	} else {
		hideStampToggle();
	}
	if(myFunc == 'stamp') {
		moveStuff = false;
		if(document.addEventListener) {
			drawArea.removeEventListener('mousemove', mouseMove, false);
			drawArea.removeEventListener('mouseup', mouseUp, false);
			drawArea.removeEventListener('mousedown', mouseDown, false);
			drawArea.addEventListener('mousedown', addStamp, false);
		} else {
			drawArea.onmousemove = null;
			drawArea.onmouseup = null;
			drawArea.onmousedown = addStamp;
		}		
	} else if(myFunc == 'move') {
		moveStuff = true;
		addRedactEvents(drawArea);
	} else if(myFunc == 'text') {
		toggleTextOverlay();
		var textBox = document.getElementsByName('dynamicText').item(0);
		
		textBtn.onclick = processTextOverlay;
	} else {
		moveStuff = false;
		addRedactEvents(drawArea);
	}
}
//make the box invisible or visible
function toggleTextOverlay()
{
	var textDiv = document.getElementById('textDiv');
	textDiv.style.display = textDiv.style.display == 'block' ? 'none' : 'block';
	textDiv.style.zIndex = textDiv.style.zIndex == '1' ? '500' : '1';
}

function parseTextOverlay(dynText)
{
	dynText = dynText.replace(/\r\r/gi, "\r");
	dynText = dynText.replace(/\r/gi, "\n");
	dynText = dynText.replace(/\n\n/gi, "\n");
	return dynText;
}

function processTextOverlay(e)
{
	var textBox = document.getElementsByName('dynamicText').item(0);
	var fontSize = document.getElementById('fontSize').value;
	var dynText = textBox.value;
	//insert a carriage return
	var imgText = parseTextOverlay(dynText);

	//dynText = longText;
	textBox.value = '';
	
	var event = e ? e : window.event;
	var tempX = event.clientX + readScroll.scrollLeft;
	var tempY = event.clientY + readScroll.scrollTop;
	
	//clear the text box.
	toggleTextOverlay();
	
	tempImg = newEl('img');
	
	tempImg.src = '../lib/textRedaction.php?echo=1&fontSize='+fontSize+'&text='+encodeURIComponent(imgText);
	tempImg.style.cursor = 'pointer';
	tempImg.style.visible = "hidden";
	tempImg.onclick = boxFuncs;
	tempImg.text = imgText;
	tempImg.size = fontSize;
	tempImg.className = 'textOver';
	addTextMoveEvents(tempImg);
	drawArea.appendChild(tempImg);
	
	tempImg.onload = function () {
		tempImg.style.position = 'absolute';
		tempImg.style.top = '0px';
		tempImg.style.left = tempX - bubbleOffsetLeft(drawArea) - tempImg.offsetWidth/2 + 'px';
		tempImg.style.visibility = 'visible';
		tempImg.style.zIndex = '10000';
	}
}

function addStamp(e) {
	if(myFunc != 'delete' && currStamp) {
		var event = e ? e : window.event;
		tempImg = newEl('img');
		var tempX = event.clientX + readScroll.scrollLeft;
		var tempY = event.clientY + readScroll.scrollTop;
		
		var time = getCurrTime();
		var urlStr = stampImg + '&time=' + time;
		tempImg.time = time;
		if(getEl('showTimestamp').checked) {
			urlStr += '&doTime=1';
			tempImg.timestamp = 1;
		} else {
			tempImg.timestamp = 0;
		}
		tempImg.src = urlStr;
		tempImg.stamp = currStamp;
		tempImg.user = user;
		tempImg.onclick = boxFuncs;
		tempImg.className = 'stamp';
		tempImg.style.cursor = 'pointer';
		tempImg.style.visibility = 'hidden';
		addMoveEvents(tempImg);
		drawArea.appendChild(tempImg);
		tempImg.onload = function () {
			tempImg.style.position = 'absolute';
			tempImg.style.top = tempY - bubbleOffsetTop(drawArea) - tempImg.offsetHeight/2 + 'px';
			tempImg.style.left = tempX - bubbleOffsetLeft(drawArea) - tempImg.offsetWidth/2 + 'px';
			tempImg.style.visibility = 'visible';
		}
	}
}

function addRedactEvents(drawArea) {
	if(document.addEventListener) {
		drawArea.addEventListener('mousemove', mouseMove, false);
		drawArea.addEventListener('mouseup', mouseUp, false);
		drawArea.addEventListener('mousedown', mouseDown, false);
		drawArea.removeEventListener('mousedown', addStamp, false);
	} else {
		drawArea.onmousemove = mouseMove;
		drawArea.onmouseup = mouseUp;
		drawArea.onmousedown = mouseDown;
	}		
}

function showColorToggle() {
	chooseColorBtn = newEl('button');
	chooseColorBtn.appendChild(newTxt('Choose A Color'));
	chooseColorBtn.onclick = drawColorSelector;
	otherSel.appendChild(chooseColorBtn);
}

function showStampToggle() {
	chooseStampSel = newEl('select');
	chooseStampSel.onchange = chooseStamp;
	var el = newEl('option');
	el.appendChild(newTxt('Choose A Stamp'));
	chooseStampSel.appendChild(el);
	p.open('GET', 'processRedactions.php?func=getStamps', true);
	var stamps = new Array ();
	try {
		p.send(null);
		redactAlert('Fetching Stamps From Server...', false);
		p.onreadystatechange = function () {
			if(p.readyState != 4) {
				return;
			}
			var xmlDoc = p.responseXML;
			var stampArr = xmlDoc.getElementsByTagName('stamp');
			for(var i = 0; i < stampArr.length; i++) {
				stamps[i] = stampArr[i].getAttribute('name');
			}
			stamps.sort();
			var newStamps = ['APPROVED', 'DENIED'];
			stamps = newStamps.concat(stamps);
			var myDiv;
			for(var i = 0; i < stamps.length; i++) {
				myDiv = newEl('option');
				myDiv.appendChild(newTxt(stamps[i]));
				myDiv.stamp = stamps[i];
				chooseStampSel.appendChild(myDiv);
			}
			otherSel.appendChild(chooseStampSel);
			var input = newEl('input');
			input.id = 'showTimestamp';
			input.type = 'checkbox';
			otherSel.appendChild(input);
			var lbl = newEl('label');
			lbl.htmlFor = 'showTimestamp';
			lbl.id = 'timeLbl';
			lbl.appendChild(newTxt('Show Time'));
			otherSel.appendChild(lbl);
			document.body.removeChild(errDiv);
			errDiv = null;
		}
	} catch(e) {
	}
}

function hideStampToggle() {
	if(chooseStampSel) {
		otherSel.removeChild(chooseStampSel);
		otherSel.removeChild(getEl('showTimestamp'));
		otherSel.removeChild(getEl('timeLbl'));
		chooseStampSel = null;
	}
}

function hideColorToggle() {
	if(chooseColorBtn) {
		otherSel.removeChild(chooseColorBtn);
		chooseColorBtn = null;
	}
	if(colorDiv) {
		document.body.removeChild(colorDiv);
		colorDiv = null;
	}
}

function boxFuncs() {
	if(myFunc == 'delete') {
		drawArea.removeChild(this);
	}
}

function registerEvents() {
	drawArea = getEl('drawArea');
	changeToggle();
	redactImg = getEl('redactImg');
	toolBox = getEl('toolBox');
	drawArea.style.height = redactImg.offsetHeight + 'px';
	drawArea.style.width = redactImg.offsetWidth + 'px';
	drawArea.style.left = bubbleOffsetLeft(redactImg) + 'px';
	drawArea.style.top = bubbleOffsetTop(redactImg) + 'px';
	drawArea.style.position = 'absolute';
	drawAreaOffTop = bubbleOffsetTop(drawArea);
	drawAreaOffLeft = bubbleOffsetLeft(drawArea);
	otherSel = getEl('otherSel');
	fetchOldRedaction();
}

function bubbleOffsetLeft(myElem) {
	var tmpVal = myElem.offsetLeft;
	var el = myElem;
	while((el = el.offsetParent) != null) {
		tmpVal += el.offsetLeft;
	}
	return tmpVal;
}

function bubbleOffsetTop(myElem) {
	var tmpVal = myElem.offsetTop;
	var el = myElem;
	while((el = el.offsetParent) != null) {
		tmpVal += el.offsetTop;
	}
	return tmpVal;
}

function fetchOldRedaction() {
	redactAlert('Fetching Redactions From Server...', false);
	var xmlDoc;
	if (document.implementation && document.implementation.createDocument) {
		xmlDoc = document.implementation.createDocument("", "", null);
	} else if(window.ActiveXObject) {
		xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
	}
	var drawDoc = xmlDoc.createElement('drawDoc');
	var docProps = xmlDoc.createElement('properties');

	var cabinetProp = xmlDoc.createElement('cabinet');
	cabinetProp.appendChild(xmlDoc.createTextNode(cabinet));
	docProps.appendChild(cabinetProp);

	var fileNameProp = xmlDoc.createElement('fileName');
	fileNameProp.appendChild(xmlDoc.createTextNode(fileName));
	docProps.appendChild(fileNameProp);

	if(subfolder != '') {
		var sFolderProp = xmlDoc.createElement('subfolder');
		sFolderProp.appendChild(xmlDoc.createTextNode(subfolder));
		docProps.appendChild(sFolderProp);
	}
	
	var template = xmlDoc.createElement('docID');
	tempTxt = '' + docID;
	
	template.appendChild(xmlDoc.createTextNode(tempTxt));
	docProps.appendChild(template);

	drawDoc.appendChild(docProps);
	xmlDoc.appendChild(drawDoc);
	var myStr = domToString(xmlDoc);
	p.open('POST', 'processRedactions.php?func=getRedact', true);
	p.send(myStr);
	xmlDoc = null;
	p.onreadystatechange = function() {
		if(p.readyState == 4) {
			var xmlDoc = p.responseXML;
			if(xmlDoc.getElementsByTagName('node').length > 0) {
				drawBoxesFromXML(xmlDoc);
			}
			document.body.removeChild(errDiv);
			errDiv = null;
			drawArea.style.display = 'block';
		}
	}
}

function drawBoxesFromXML(xmlDoc) {
	var nodeDiv, tmp;
	var tmp = xmlDoc.getElementsByTagName('docID');
	if(tmp[0].firstChild.nodeValue == 0) {
		getEl('allDocs').checked = true;
	}
	var nodeArr = xmlDoc.getElementsByTagName('node');
	var nodeType, urlStr;
	for(var i = 0; i < nodeArr.length; i++) {
		tmp = nodeArr[i].getElementsByTagName('type');
		nodeType = tmp[0].firstChild.nodeValue;
		if(nodeType == 'stamp') {
			nodeDiv = newEl('img');
			tmp = nodeArr[i].getElementsByTagName('image');
			nodeDiv.stamp = tmp[0].firstChild.nodeValue;
			tmp = nodeArr[i].getElementsByTagName('timestamp');
			nodeDiv.timestamp = tmp[0].firstChild.nodeValue;
			tmp = nodeArr[i].getElementsByTagName('time');
			nodeDiv.time = tmp[0].firstChild.nodeValue;
			tmp = nodeArr[i].getElementsByTagName('user');
			nodeDiv.user = tmp[0].firstChild.nodeValue;
			nodeDiv.src = '../energie/chooseStamp.php?img=' + nodeDiv.stamp +
				'&time=' + nodeDiv.time + '&doTime=' + nodeDiv.timestamp + '&user=' + nodeDiv.user + '&h=.png';
		} else if(nodeType == 'textOver'){
			nodeDiv = newEl('img');
			tmp = nodeArr[i].getElementsByTagName('text');
			var source = tmp[0].firstChild.nodeValue;
			nodeDiv.text = source;
			tmp = nodeArr[i].getElementsByTagName('size');
			var fontsize = tmp[0].firstChild.nodeValue;
			nodeDiv.size = fontsize;
			nodeDiv.src = '../lib/textRedaction.php?echo=1&text='+encodeURIComponent(source)+'&fontSize='+fontsize;
			nodeDiv.style.zIndex = '10000';
		} else {
			nodeDiv = newEl('div');
			tmp = nodeArr[i].getElementsByTagName('color');
			nodeDiv.style.backgroundColor = tmp[0].firstChild.nodeValue;
		}
		tmp = nodeArr[i].getElementsByTagName('height');
		nodeDiv.style.height = tmp[0].firstChild.nodeValue + 'px';
		tmp = nodeArr[i].getElementsByTagName('width');
		nodeDiv.style.width = tmp[0].firstChild.nodeValue + 'px';
		if(nodeType == 'textOver') { addTextMoveEvents(nodeDiv); }
		else { addMoveEvents(nodeDiv); }
		nodeDiv.style.cursor = 'pointer';
		nodeDiv.className = nodeType;
		nodeDiv.style.position = 'absolute';
		tmp = nodeArr[i].getElementsByTagName('top');
		nodeDiv.style.top = tmp[0].firstChild.nodeValue + 'px';
		tmp = nodeArr[i].getElementsByTagName('left');
		nodeDiv.style.left = tmp[0].firstChild.nodeValue + 'px';
		nodeDiv.onclick = boxFuncs;
		drawArea.appendChild(nodeDiv);										
	}
}

function addMoveEvents(node) {
	if(document.addEventListener) {
		node.addEventListener('mousemove', moveMouseMove, false);
		node.addEventListener('mouseup', moveMouseUp, false);
		node.addEventListener('mousedown', moveMouseDown, false);
	} else {
		node.onmousemove = moveMouseMove;
		node.onmouseup = moveMouseUp;
		node.onmousedown = moveMouseDown;
	}
}

function addTextMoveEvents(node) {
        if(document.addEventListener) {
                node.addEventListener('mousemove', moveTextMouseMove, false);
                node.addEventListener('mouseup', moveMouseUp, false);
                node.addEventListener('mousedown', moveMouseDown, false);
        } else {
                node.onmousemove = moveTextMouseMove;
                node.onmouseup = moveMouseUp;
                node.onmousedown = moveMouseDown;
        }
}


function moveMouseDown(e) {
	if(moveStuff) {
		moveNow = true;
		currMove = this;
		var hello = currMove;
	}
}

function moveMouseMove(e) {
	if(!moveNow || !moveStuff) {
		return;
	}
	var event = e ? e : window.event;
	var tempX = event.clientX + readScroll.scrollLeft;
	var tempY = event.clientY + readScroll.scrollTop;
	currMove.style.top = tempY - bubbleOffsetTop(currMove.parentNode) - currMove.offsetHeight/2 + 'px';
	currMove.style.left = tempX - bubbleOffsetLeft(currMove.parentNode) - currMove.offsetWidth/2 + 'px';
	return false;
}

function moveTextMouseMove(e) {
        if(!moveNow || !moveStuff) {
                return;
        }
        var event = e ? e : window.event;
        var tempX = event.clientX + readScroll.scrollLeft;
        var tempY = event.clientY + readScroll.scrollTop;
        currMove.style.top = tempY - 85 + 'px';
        currMove.style.left = tempX + 'px';
        return false;
}


function moveMouseUp(e) {
	moveNow = false;
	currMove = null;
}

function postXML() {
	var xmlDoc = createDOMDoc();
	var drawDoc = xmlDoc.createElement('drawDoc');
	var docProps = xmlDoc.createElement('properties');

	var cabinetProp = xmlDoc.createElement('cabinet');
	cabinetProp.appendChild(xmlDoc.createTextNode(cabinet));
	docProps.appendChild(cabinetProp);

	var fileNameProp = xmlDoc.createElement('fileName');
	fileNameProp.appendChild(xmlDoc.createTextNode(fileName));
	docProps.appendChild(fileNameProp);

	var pFileNameProp = xmlDoc.createElement('parentFileName');
	pFileNameProp.appendChild(xmlDoc.createTextNode(pFileName));
	docProps.appendChild(pFileNameProp);

	if(subfolder != '') {
		var sFolderProp = xmlDoc.createElement('subfolder');
		sFolderProp.appendChild(xmlDoc.createTextNode(subfolder));
		docProps.appendChild(sFolderProp);
	}
	
	var height = xmlDoc.createElement('height');
	height.appendChild(xmlDoc.createTextNode(redactImg.offsetHeight));
	docProps.appendChild(height);
	
	var width = xmlDoc.createElement('width');
	width.appendChild(xmlDoc.createTextNode(redactImg.offsetWidth));
	docProps.appendChild(width);

	var template = xmlDoc.createElement('docID');
	var tempTxt;
	if(getEl('allDocs').checked) {
		tempTxt = '0';
	} else {
		tempTxt = '' + docID;
	}
	template.appendChild(xmlDoc.createTextNode(tempTxt));
	docProps.appendChild(template);
	template = xmlDoc.createElement('calledDocID');
	tempTxt = xmlDoc.createTextNode(docID);
	template.appendChild(tempTxt);
	docProps.appendChild(template);
	drawDoc.appendChild(docProps);

	var xmlNodes = xmlDoc.createElement('boxes');
	
	var drawnNodes = drawArea.childNodes;
	var myNode, myType, myColor, myHeight, myWidth, myTop, myLeft, myImg, myEl;
	var nodeType = '';
	var start=1;
	
	if(window.ActiveXObject) 
		var start=0;

	for(var i = start; i < drawnNodes.length; i++) {
		myNode = xmlDoc.createElement('node');
		nodeType = drawnNodes[i].className;

		if(nodeType == 'stamp') {
			myImg = xmlDoc.createElement('image');
			myImg.appendChild(xmlDoc.createTextNode(drawnNodes[i].stamp));
			myNode.appendChild(myImg);
			myEl = xmlDoc.createElement('timestamp');
			myEl.appendChild(xmlDoc.createTextNode(drawnNodes[i].timestamp));
			myNode.appendChild(myEl);
			myEl = xmlDoc.createElement('time');
			myEl.appendChild(xmlDoc.createTextNode(drawnNodes[i].time));
			myNode.appendChild(myEl);
			myEl = xmlDoc.createElement('user');
			myEl.appendChild(xmlDoc.createTextNode(drawnNodes[i].user));
			myNode.appendChild(myEl);
		} else if(nodeType == 'textOver') {
			myEl = xmlDoc.createElement('text');
			myEl.appendChild(xmlDoc.createTextNode(drawnNodes[i].text));
			myNode.appendChild(myEl);
			myEl = xmlDoc.createElement('size');
			myEl.appendChild(xmlDoc.createTextNode(drawnNodes[i].size));
			myNode.appendChild(myEl);
		} else {
			myColor = xmlDoc.createElement('color');
			myColor.appendChild(xmlDoc.createTextNode(drawnNodes[i].style.backgroundColor));
			myNode.appendChild(myColor);
		} 

		myType = xmlDoc.createElement('type');
		myType.appendChild(xmlDoc.createTextNode(nodeType));
		
		myHeight = xmlDoc.createElement('height');
		myHeight.appendChild(xmlDoc.createTextNode(drawnNodes[i].offsetHeight));
		myWidth = xmlDoc.createElement('width');
		myWidth.appendChild(xmlDoc.createTextNode(drawnNodes[i].offsetWidth));

		myTop = xmlDoc.createElement('top');
		myTop.appendChild(xmlDoc.createTextNode(drawnNodes[i].offsetTop));
		
		myLeft = xmlDoc.createElement('left');
		myLeft.appendChild(xmlDoc.createTextNode(drawnNodes[i].offsetLeft));

		myNode.appendChild(myType);
		myNode.appendChild(myHeight);
		myNode.appendChild(myWidth);
		myNode.appendChild(myTop);
		myNode.appendChild(myLeft);
		
		xmlNodes.appendChild(myNode);
	}
	drawDoc.appendChild(xmlNodes);
	xmlDoc.appendChild(drawDoc);
	var myStr;
	myStr = domToString(xmlDoc);
	p.open('POST', 'processRedactions.php?func=insert', true);
	p.send(myStr);
	p.onreadystatechange = function() {
		if(p.readyState == 4) {
			var myTxt;
			if(p.responseText == 'OK') {
				redactAlert('Redaction Added Successfully')
//				var myEl;
//				if(myEl = parent.sideFrame.document.getElementById('img:' + allThumbCt)) {
//					var myUrl = myEl.src;
//					myEl.src = '../images/thumb.jpg';
//					setTimeout("parent.sideFrame.document.getElementById('img:" + allThumbCt + "').src = '" + myUrl + "'", 1000);
//				} else {
//					alert('no go img:' + allThumbCt);
//				}
				var loc = parent.sideFrame.location + "&viewing=" + divID + "&currTab=" + subfolder;
				parent.sideFrame.location = loc;
//				parent.sideFrame.location.reload();;

			} else {
				redactAlert('Redaction Not Added Successfully!');
			}
		}
	}
}

function redactAlert(txt, timeout) {
	if(errDiv) {
		if(errDiv.timeoutID) {
			clearTimeout(errDiv.timeoutID);
			errDiv.timeoutID = null;
		}
		errDiv.removeChild(errDiv.firstChild);
	} else {
		errDiv = newEl('div');
		errDiv.style.position = 'absolute';
		errDiv.style.backgroundColor = 'maroon';
		errDiv.style.color = 'white';
		errDiv.style.right = '10px';
		errDiv.style.top = bubbleOffsetTop(toolBox) + toolBox.offsetHeight + 'px';
		document.body.appendChild(errDiv);
	}				
	errDiv.appendChild(newTxt(txt));
	if(timeout) {
		errDiv.timeoutID = setTimeout(function() { document.body.removeChild(errDiv); errDiv = null; }, 5000);
	}
}

function drawColorSelector() {
	if(colorDiv) {
		document.body.removeChild(colorDiv);
		colorDiv = null;
	} else {
		colorDiv = newEl('div');
		colorDiv.style.cursor = 'pointer';
		var colors = ['aqua', 'blue', 'fuchsia', 'green', 'lime', 'maroon',
					  'navy', 'olive', 'purple', 'red', 'teal', 'yellow'];
		colorDiv.style.left = bubbleOffsetLeft(this) + 'px';
		colorDiv.style.top = bubbleOffsetTop(this) + this.offsetHeight + 'px';
		colorDiv.style.width = this.offsetWidth + 'px';
		colorDiv.style.position = 'absolute';
		var myDiv;
		for(var i = 0; i < colors.length; i++) {
			myDiv = newEl('div');
			myDiv.className = 'colorDivs';
			myDiv.style.backgroundColor = colors[i];
			myDiv.color = colors[i];
			myDiv.onclick = chooseColor;
			colorDiv.appendChild(myDiv);
		}
		colorDiv.style.height = '150px';
		colorDiv.style.overflow = 'scroll';
		colorDiv.style.backgroundColor = 'white';
		document.body.appendChild(colorDiv);
	}
}

function getCurrTime() {
	var myDate = new Date();
	return myDate.getTime() / 1000;
}

function chooseStamp() {
	currStamp = this.options[this.selectedIndex].stamp;
	stampImg = '../energie/chooseStamp.php?img=' + currStamp + '&user=' + user;
}

function chooseColor() {
	highlightColor = this.color;
	document.body.removeChild(colorDiv);
	colorDiv = null;
}

function printRedactedPage() {
	var redHtml = document.getElementById('redactionContainer').innerHTML;
	var bdEl = parent.bottomFrame.document.getElementById('printBody');
	if(bdEl) {
		bdEl.innerHTML = redHtml;
	}
	var elArr = bdEl.document.getElementsByTagName("div");
	if(elArr.length > 0) {
		for(i=0;i<elArr.length;i++) {
			if(elArr[i].className == "redact" || 
			elArr[i].className == "highlight" || 
			elArr[i].className == "stamp") {
				var tp = elArr[i].style.top;
				var px = tp.substring(0,tp.length-2);
				elArr[i].style.top = (px-70)+"px";	

				var lf = elArr[i].style.left;
				var px = lf.substring(0,lf.length-2);
				elArr[i].style.left = (px-10)+"px";	
			}

			if(elArr[i].className == "redact") {
				elArr[i].style.backgroundColor = 'white';
			}
		}
	}

	var elArr = bdEl.document.getElementsByTagName("img");
	if(elArr.length > 0) {
		for(i=0;i<elArr.length;i++) {
			if(elArr[i].className == "stamp") {
				var tp = elArr[i].style.top;
				var px = tp.substring(0,tp.length-2);
				elArr[i].style.top = (px-70)+"px";	

				var lf = elArr[i].style.left;
				var px = lf.substring(0,lf.length-2);
				elArr[i].style.left = (px-10)+"px";	
			}
		}
	}
	parent.bottomFrame.focus();
	parent.bottomFrame.window.print();
}

function clearDynamicText()
{
	document.getElementsByName('dynamicText').item(0).value = '';
}

function cancelTextOverlay()
{
	clearDynamicText();
	var textDiv = getEl('textDiv');
	textDiv.style.display = 'none';
}
