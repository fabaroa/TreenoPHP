// $Id$
//
//this file handles the password restriction creation.  There are functions to grab settings from database, 
//return them in xml and parse them to a php file that saves them in the db.

function loadSettings()
{
	registerVars();
	var settings = getPasswordSettings();
	if(!settings)
	{
		
	}
}

function registerVars() {

	errDiv = getEl('errDiv');

}


function getPasswordSettings() 
{
	var URL = '../lib/settingsFuncs.php?func=getPasswordSettingsList';
	p.open('GET', URL, true);
	alertBox('Communicating with Server...');
	try {
		p.send(null);
	} catch(e) {
		alertBox('Could not establish connection.  Please try again');
	}
	p.onreadystatechange = function () {
		if(p.readyState != 4) {
			return;
		}
		try {
			clearAlert();
			var xmlDoc = p.responseXML;
			var arr = xmlDoc.getElementsByTagName('Setting');

			for(i = 0; i < arr.length; i++)
			{
				var id = arr[i].getAttribute('id');
				var objValue = arr[i].getAttribute('value');
				var obj = document.getElementById(id);
				if(obj.type == "checkbox")
				{
					obj.checked = objValue == '1' ? 'checked' : ''; 
				}
				if(obj.type == "select-one")
				{
					for(op = 0; op < obj.options.length; op++)
						obj.options[op].selected = objValue == obj.options[op].value ? 'selected' : '';
				}	
			}
		} catch(e) {
			alertBox('on no2!');
		}	
	};
}

function submitPasswordSettings()
{
	var xmlStr = createPasswordSettingsXML();
	var URL = '../lib/passwordSettingsFuncs.php?func=updatePasswordSettings';
	p.open('POST', URL, true);
	alertBox('Communicating with Server...');
	try {
		p.send(xmlStr);
	} catch(e) {
		alertBox('Could not establish connection.  Please try again');
	}
	p.onreadystatechange = function() {
		if(p.readyState != 4) {
			return;
		}
		var xmlDoc = p.responseXML;
		var retVal = xmlDoc.getElementsByTagName('return');
		alertBox(retVal[0].getAttribute('text'));
	};
	return;
}

function createPasswordSettingsXML()
{
	var passwordSettings = new Object();
	passwordSettings['passwordRestriction'] = document.passwordSettings.passwordRestriction.checked == true ? 1 : 0;
	passwordSettings['requireChange'] = document.passwordSettings.requireChange.checked == true ? 1 : 0;
	passwordSettings['alpha_character'] = document.passwordSettings.alpha_character.checked == true ? 1 : 0;
	passwordSettings['numeric_character'] = document.passwordSettings.numeric_character.checked == true ? 1 : 0;
	passwordSettings['special_character'] = document.passwordSettings.special_character.checked == true ? 1 : 0;
	passwordSettings['minLength'] = document.passwordSettings.minLength.options[document.passwordSettings.minLength.options.selectedIndex].value;
	passwordSettings['forcePassword'] = document.passwordSettings.forcePassword.options[document.passwordSettings.forcePassword.selectedIndex].value;	
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('root');
	xmlDoc.appendChild(root);

	
	for(var prop in passwordSettings) {
		el = xmlDoc.createElement('Setting');		
		el.setAttribute('id', prop);
		el.setAttribute('value', passwordSettings[prop]);
	
		root.appendChild(el);
	}
	var xmlStr = domToString(xmlDoc);
	return xmlStr;

}

function clearAlert() {
	errDiv.firstChild.nodeValue = String.fromCharCode(160);
}

function alertBox(str) {
	errDiv.innerHTML = str;
}
