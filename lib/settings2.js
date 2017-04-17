var xmlDoc = "";
var rootEl = "";
var xmlStr = "";

function createDOMDoc() {
	if (document.implementation && document.implementation.createDocument) {
		xmlDoc = document.implementation.createDocument("", "", null);
	} else if(window.ActiveXObject) {
		xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
	}
}

function domToString() {
	try {
		var mySerial = new XMLSerializer();
		xmlStr = mySerial.serializeToString(xmlDoc);
	} catch (e) {
		xmlStr = xmlDoc.xml;
	}
}

function createRootElement() {
	rootEl = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(rootEl);
}

function createKeyAndValue(key,value) {
	var entry = xmlDoc.createElement('ENTRY');
	rootEl.appendChild(entry);

	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode(key));
	entry.appendChild(k);
	
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode(value));
	entry.appendChild(v);
}

function generateXML(xmlArr) {
	createDOMDoc();	
	createRootElement();
	for(var key in xmlArr) {
		createKeyAndValue(key,xmlArr[key]);
	}
	domToString();
}

function postXML(xmlArr) {
	generateXML(xmlArr);
//	alert(xmlStr);
	var newAjax = new Ajax.Request( "../lib/ajaxPostRequest.php",
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

		var func = XML.getElementsByTagName('FUNCTION');
		if(func.length > 0) {
			eval(func[0].firstChild.nodeValue);
		}
	} else {
		clearDiv(eMsg);
		eMsg.appendChild(document.createTextNode('An Error Occured Loading the XML'));
	}
}

function reportError(req) {

}

function removeElementsChildren(el) {
	if(el) {
		while( el.childNodes[0] ) {
			el.removeChild(el.childNodes[0]);
		}
	}
}

function addDefault(el) {
	if(el.value == '__default') {
		return;
	}
	var opt = document.createElement('option');
	opt.value = '__default';
	opt.appendChild(document.createTextNode('Choose One'));
	if(el.length) {
		el.insertBefore(opt, el.options[0]);
	} else {
		el.appendChild(opt);
	}

	if(el.options.length > 1) {
		el.options[0].selected = true;
	}
}

function removeDefault(el) {
	if(el.options) {
		var i, selVal = el.options[el.selectedIndex].value;
		for(i = 0; i < el.options.length; i++) {
			if(el.options[i].value == '__default') {
				el.removeChild(el.options[i]);
				break;
			}
		}
		for(i = 0; i < el.options.length; i++) {
			if(el.options[i].value == selVal) {
				el.options[i].selected = true;
				break;
			}
		}
	}
}
