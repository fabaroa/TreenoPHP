function getPublishingExpiration() {
	disableWindow();
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlGetPublishingExpiration" };
	postXML(xmlArr);
}

function setPublishingExpiration(XML) {
	var dExp = XML.getElementsByTagName('DEFAULT');
	if(dExp.length && dExp[0].firstChild) {
		$('defExp').value = parseInt(dExp[0].firstChild.nodeValue);
	}

	var mExp = XML.getElementsByTagName('MAX');
	if(mExp.length && mExp[0].firstChild) {
		$('maxExp').value = parseInt(mExp[0].firstChild.nodeValue);
	}
	enableWindow();
}

function updatePublishingExpiration() {
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlSetPublishingExpiration",
					"defExp" : $('defExp').value,
					"maxExp" : $('maxExp').value };
	postXML(xmlArr);
}

/* Behaviour Functions */
function initialize() {
	var behaviors = {
		'#defExp'	: function (element) {
			element.onkeypress = setExpireOnEnter;
		},
		'#maxExp'	: function (element) {
			element.onkeypress = setExpireOnEnter;
		},
		'#saveExp'	: function (element) {
			element.onclick = updatePublishingExpiration;
		}
	};

	Behaviour.register(behaviors);
	Behaviour.apply();
}

function disableWindow() {
	var behaviors = {
		'#defExp'	: function (element) {
			element.disabled = true;
		},
		'#maxExp'	: function (element) {
			element.disabled = true;
		},
		'#saveExp'	: function (element) {
			element.disabled = true;
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();
}

function enableWindow() {
	var behaviors = {
		'#defExp'	: function (element) {
			element.disabled = false;
		},
		'#maxExp'	: function (element) {
			element.disabled = false;
		},
		'#saveExp'	: function (element) {
			element.disabled = false;
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();
}
/* End of Behaviour Functions */

/* On-Enter Functions */
function setExpireOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		updatePublishingExpiration();				
		return true;
	}

	if (((charCode >= 48 && charCode <= 57) || charCode == 8)) {    
		return true;
	}
	return false;
}
/* End of On-Enter Functions */

function printMessage(mess) {
	removeElementsChildren($('error'));
	if(mess) {
		$('error').appendChild(document.createTextNode(mess));
	}
}
