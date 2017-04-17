
function clearInputs() {
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		inputArr[i].value = '';
	}
}

function disableInputs(val) {
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		inputArr[i].disabled = val;
	}
}

function registerVars() {
	ptPassword = getEl('ptPassword');
	encPassword = getEl('encPassword');
	genBtn = getEl('genBtn');
	errDiv = getEl('errDiv');	
}

function clearEl(el) {
	while(el.hasChildNodes()) {
		el.removeChild(el.firstChild);
	}
}

function createGeneratedPassword()
{
	if(getEncryptedPassword())
		return true;
}

function getEncryptedPassword() {
	ptPassword.disabled = true;
	var URL = '../lib/settingsFuncs.php?func=getEncryptedPassword&v1='+ptPassword.value;
	p.open('GET', URL, true);
	alertBox('Generating Password...');
	try {
		p.send(null);
	} catch(e) {
		alertBox('oh no!');
	}
	p.onreadystatechange = function () {
		if(p.readyState != 4) {
			return;
		}
		try {
			clearAlert();
			var encPass = p.responseText;
			encPassword.value = encPass;
			encPassword.disabled = false;
			return true;
		} catch(e) {
			alertBox('on no2!');
			return false;
		}
	};
}

function clearAlert() {
	errDiv.firstChild.nodeValue = String.fromCharCode(160);
}

function alertBox(str) {
	errDiv.firstChild.nodeValue = str;
}



