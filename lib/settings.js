function execFunc(funcName) //and unlimited arguments
{
	argStr = '../lib/settingsFuncs.php?func=' + funcName;
	for (var i = 1; i < arguments.length; i++) {
		argStr += '&v' + i + '=' + arguments[i];
	}
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('GET', argStr, false);
	xmlhttp.send(null);
	return xmlhttp.responseText;
}

function getXMLHTTP() {
	var p;
	if(window.XMLHttpRequest) {
		p = new XMLHttpRequest();
	} else if(window.ActiveXObject) {
		p = new ActiveXObject("Microsoft.XMLHTTP");
	}
	return p;			
}

function createDOMDoc() {
	var xmlDoc;
	if (document.implementation && document.implementation.createDocument) {
		xmlDoc = document.implementation.createDocument("", "", null);
	} else if(window.ActiveXObject) {
		xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
	}
	return xmlDoc;
}

function domToString(xmlDoc) {
	var myStr;
	try {
		var mySerial = new XMLSerializer();
		myStr = mySerial.serializeToString(xmlDoc);
	} catch (e) {
		myStr = xmlDoc.xml;
	}
	return myStr;
}

function clearDiv( div ) {
	if(div) {
		while( div.childNodes[0] ) {
			div.removeChild(div.childNodes[0]);
		}
	}
}

function getEl(id) {
	return document.getElementById(id);
}

function newEl(el) {
	return document.createElement(el);
}

function newTxt(str) {
	return document.createTextNode(str);
}

function createRadio(name) {
	var rv = -1;
	if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
	if(!document.all || rv >= 9.0) {
		var el = newEl('input');
		el.type = 'radio';
		el.name = name;
		return el;
	} else {
		return newEl('<input type="radio" name="' + name + '">');
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

function addDefault(el) {
	if(el.value == '__default') {
		return;
	}
	var opt = newEl('option');
	opt.value = '__default';
	opt.appendChild(document.createTextNode('Choose One'));
	el.insertBefore(opt, el.options[0]);
	el.options[0].selected = true;
}

function hideDivs(divArr) {
	for(var i = 0; i < divArr.length; i++) {
		divArr[i].style.display = 'none';
	}
}

function showDivs(divArr) {
	for(var i = 0; i < divArr.length; i++) {
		divArr[i].style.display = 'block';
	}
}

function assistRequestHelp(divObj,key,lang) {
    requestHelp(divObj,key,lang,self.name);
}

var passwordSettings = 'off';

function ShowRestrictions()
{
		
	if(passwordSettings == 'off')
	{
		try{

			var dispDiv = document.getElementById('passwordSettings');

			var xmlhttp = getXMLHTTP();
			xmlhttp.open('GET', '../lib/settingsFuncs.php?func=displayPasswordSettings', false);
			xmlhttp.send(null);
			var xmlDoc = xmlhttp.responseXML;
			var arr = xmlDoc.getElementsByTagName('Restrictions');
		
			for (i = 0; i < arr.length; i++) {
				var value = arr[i].getAttribute('value');
				var el = document.createElement('div');
				el.innerHTML = value;
				dispDiv.appendChild(el);
			}
		}
		catch(e)
		{
			var el = document.createElement('div');
			el.innerHTML = 'No password restrictions are set at this time.';
			dispDiv.appendChild(el);
		}
		dispDiv.style.display = 'block';
		passwordSettings = 'on';		
	}
	else {
		try {
			document.getElementById('passwordSettings').style.display = 'none';
			document.getElementById('passwordSettings').innerHTML = '';
			passwordSettings = 'off'
			return true;
		}catch(e) {
			return false;
		}
	}
}