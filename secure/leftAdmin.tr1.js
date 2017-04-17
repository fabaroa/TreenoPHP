ie = (document.all && document.getElementById);
ns = (!document.all && document.getElementById);
elements = new Array();

function sendURL(myURL) {
	parent.mainFrame.window.location = myURL;
}

function showItems() {
	var itemDiv = getNextSibByTag(this.nextSibling, 'DIV');
	var maxHeight;
	var tmpArr;
	if(itemDiv.id) {
		tmpArr = new String(itemDiv.id).split('-');
		maxHeight = elements[tmpArr[1]];
	} else {
		maxHeight = 0;
	}
	var dwnImgDiv = getNextSibByTag(this.firstChild, 'DIV');
	var dwnImg = getNextSibByTag(dwnImgDiv.firstChild, 'IMG');
	if(itemDiv.style.display == 'block') {
		scrollUp(this, itemDiv, maxHeight, maxHeight);
		fadeOut(itemDiv, 100);
	} else {
		itemDiv.style.display = 'block';
		if(maxHeight == 0) {
			maxHeight = itemDiv.offsetHeight;
			itemDiv.id = 'id-' + elements.length;
			elements[elements.length] = maxHeight;
		}
		dwnImg.src = '../images/up.gif';
		scrollDown(itemDiv, maxHeight, 0);
		fadeIn(itemDiv, 0);
	}
}

function scrollDown(itemDiv, maxHeight, myHeight) {
	if(myHeight <= maxHeight) {
		itemDiv.style.height = myHeight + "px";
		myHeight = myHeight + 40;
		var myFunction = function() {
			scrollDown(itemDiv, maxHeight, myHeight);
		}
		setTimeout(myFunction, 20);
	} else {
		itemDiv.style.height = maxHeight + "px";
	}
}

function scrollUp(titleDiv, itemDiv, maxHeight, myHeight) {
	var dwnImgDiv = getNextSibByTag(titleDiv.firstChild, 'DIV');
	var dwnImg = getNextSibByTag(dwnImgDiv.firstChild, 'IMG');
	if(myHeight > 0) {
		itemDiv.style.height = myHeight + "px";
		myHeight = myHeight - 40;
		var myFunction = function() {
			scrollUp(titleDiv, itemDiv, maxHeight, myHeight);
		}
		setTimeout(myFunction, 20);
	} else {
		itemDiv.style.display = "none";
		itemDiv.style.height = maxHeight + "px";
		dwnImg.src = '../images/down.gif';
	}
}

function fadeIn(itemDiv, myOpacity) {
	if(myOpacity < 100) {
		myOpacity += 10;
		if(ns) {
			itemDiv.style.MozOpacity = myOpacity/100;
		}
		if(ie) {
			var newOpacity = "alpha(opacity=" + myOpacity + ")";
			itemDiv.style.filter = newOpacity;
		}
		var myFunction = function() {
			fadeIn(itemDiv, myOpacity);
		};
		setTimeout(myFunction, 20);
	}
}

function fadeOut(itemDiv, myOpacity) {
	var titleDiv = getPrevSibByTag(itemDiv.previousSibling, 'DIV');
	var dwnImgDiv = getNextSibByTag(titleDiv.firstChild, 'DIV');
	var dwnImg = getNextSibByTag(dwnImgDiv.firstChild, 'IMG');
	if(myOpacity > 0) {
		myOpacity -= 10;
		if(ns) {
			itemDiv.style.MozOpacity = myOpacity/100;
		}
		if(ie) {
			var newOpacity = "alpha(opacity=" + myOpacity + ")";
			itemDiv.style.filter = newOpacity;
		}
		var myFunction = function() {
			fadeOut(itemDiv, myOpacity);
		}
		setTimeout(myFunction, 20);
	}
}

function registerEvents() {
	var adminDiv = getNextSibByTag(document.body.firstChild, 'DIV');
	while(adminDiv) {
		var titleDiv = getNextSibByTag(adminDiv.firstChild, 'DIV');
		if(titleDiv) {
			if(!titleDiv.onclick) {
				titleDiv.onclick = showItems;
			}
			var itemDiv = getNextSibByTag(titleDiv.nextSibling, 'DIV');
			if(itemDiv) {
				var funcDiv = getNextSibByTag(itemDiv.firstChild, 'DIV');
				while(funcDiv) {
					funcDiv.onmouseover = mOver;
					funcDiv.onmouseout = mOut;
					funcDiv = getNextSibByTag(funcDiv.nextSibling, 'DIV');
				}
			}
		}
		adminDiv = getNextSibByTag(adminDiv.nextSibling, 'DIV')
	}
}

function mOver() {
	this.style.backgroundColor = "#ffffff";
}

function mOut() {
	this.style.backgroundColor = "#cfdbe6";
	var myItem = getNextSibByTag(this.parentNode.firstChild, 'DIV');
	while(myItem) {
		myItem.style.background = '#cfdbe6';
		myItem = getNextSibByTag(myItem.nextSibling, 'DIV');
	}
}

