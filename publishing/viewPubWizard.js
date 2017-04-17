Sandbox					= new Object();
Sandbox.name			= "";
Sandbox.id				= "";
Sandbox.expire			= 0;
Sandbox.defaultExpire	= 0;
Sandbox.maxExpire		= 0;
Sandbox.forceExpire		= 0;
Sandbox.forceDefExpire	= 0;
Sandbox.userList		= new Array();
Sandbox.userAssocList	= new Array();
Sandbox.itemList		= new Object();
Sandbox.itemCount		= 0;

initLoad = false;

function selectUser() {
	if(getPubName()) {
		username = this.name;
		highlightRow(username);

		//disableWindow();
		var xmlArr = {	"include" : "publishing/publishingFuncs.php",
						"function" : "xmlAddPublishUser",
						"publish_user" : username,
						"publish_id" : getUsernameID(username) }; 
		postXML(xmlArr);
	} else {
		var mess = "A published search has not been selected";
		printMessage(mess);
	}
}

function deleteSelectedUser() {
	username = this.name;
	unhighlightRow(username);
	
	//disableWindow();
	if(getPubName()) {
		var xmlArr = {	"include" : "publishing/publishingFuncs.php",
						"function" : "xmlRemovePublishUser",
						"publish_id" : getUsernameID(username) }; 
		postXML(xmlArr);
	} else {
		var mess = "A published search has not been selected";
		printMessage(mess);
	}
}

function resetSelectedUsers() {
	var list = getUserList();
	for(var i=0;i<list.length;i++) {
		unhighlightRow(list[i]);
	}
}

/* Behaviour Functions */
function initialize() {
	var behaviors = {
		'#pubName'	: function (element) {
			element.onkeypress = pubNameOnEnter;
		},
		'#pubAdd'	: function (element) {
			element.onclick = createSandbox;
		},
		'#noExp'	: function (element) {
			element.disabled = true;
		},
		'#defExp'	: function (element) {
			element.disabled = true;
		},
		'#custExp'	: function (element) {
			element.disabled = true;
		},
		'#expireTime'	: function (element) {
			element.disabled = true;
			element.onkeypress = pubExpireOnEnter;
		},
		'#pubList'	: function (element) {
			element.onchange = getSandbox;
		},
		'#newUser'	: function (element) {
			element.onclick = createNewUser;
			element.onmouseover = mOver;
			element.onmouseout = mOut;
		},
		'#search'	: function (element) {
			element.onkeypress = pubSearchUserOnEnter;
		},
		'#publishUser'	: function (element) {
			element.onkeypress = pubUserOnEnter; 
		},
		'#searchUser'	: function (element) {
			element.onclick = searchUserList;
		},
		'#saveUser'	: function (element) {
			element.onclick = saveCreateNewUser; 
		},
		'#cancelUser'	: function (element) {
			element.onclick = cancelCreateNewUser; 
		},
		'#item'	: function (element) {
			element.onkeypress = pubSearchItemOnEnter; 
		},
		'#searchItems'	: function (element) {
			element.onclick = function () {};
		},
		'#finishBtn'	: function (element) {
			element.onclick = publishSandbox; 
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();

	if(!document.all) {
		$('listContainer').style.position = 'relative';		
		$('listContainer').style.left = '-40px';		
		$('listContainer').style.width = ($('userList').offsetWidth-25)+'px';	
	}

	if (window.XMLHttpRequest) {
		$('itemDiv').style.paddingBottom = '10px';	
	}
}

function publishSandbox() {
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlPublishSandbox" }; 
	postXML(xmlArr);
}

function closeSandbox() {
	self.close();
}

function disableWindow() {
	var behaviors = {
		'#pubName'	: function (element) {
			element.disabled = true;
		},
		'#pubAdd'	: function (element) {
			element.disabled = true;
		},
		'#pubList'	: function (element) {
			element.disabled = true;
		},
		'#noExp'	: function (element) {
			element.disabled = true;
			element.onclick = function () {};
		},
		'#defExp'	: function (element) {
			element.disabled = true;
			element.onclick = function () {};
		},
		'#custExp'	: function (element) {
			element.disabled = true;
			element.onclick = function () {};
		},
		'#newUser'	: function (element) {
			element.onclick = function () {};
			element.onmouseover = function () {};
			element.onmouseout = function () {};
		},
		'#search'	: function (element) {
			element.disabled = true;
		},
		'#publishUser'	: function (element) {
			element.disabled = true;
		},
		'#searchUser'	: function (element) {
			element.disabled = true;
		},
		'#saveUser'	: function (element) {
			element.onclick = function () {}; 
		},
		'#cancelUser'	: function (element) {
			element.onclick = function () {}; 
		},
		'#item'	: function (element) {
			element.disabled = true;
		},
		'#searchItems'	: function (element) {
			element.disabled = true;
		},
		'#finishBtn'	: function (element) {
			element.disabled = true;
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();
}

function enableWindow() {
	var behaviors = {
		'#pubName'	: function (element) {
			element.disabled = false;
		},
		'#pubAdd'	: function (element) {
			element.disabled = false;
		},
		'#pubList'	: function (element) {
			element.disabled = false;
		},
		'#noExp'	: function (element) {
			if(!getForceExpire() && !getForceDefExpire()) {
				if(getPubName()) {
					element.disabled = false;
					element.onclick = toggleExpire;
				}
			}
		},
		'#defExp'	: function (element) {
			if(getPubName()) {
				element.disabled = false;
				element.onclick = toggleExpire;
			}
		},
		'#custExp'	: function (element) {
			if(!getForceDefExpire()) {
				if(getPubName()) {
					element.disabled = false;
					element.onclick = toggleExpire;
					if(element.checked) {
						$('expireTime').disabled = false;
					}
				}
			}
		},
		'#newUser'	: function (element) {
			if(getPubName()) {
				element.onclick = createNewUser;
			} else {
				var mess = "A published search has not been selected";
				element.onclick = function () { printMessage(mess) };
			}
			element.onmouseover = mOver;
			element.onmouseout = mOut;
		},
		'#search'	: function (element) {
			if(getPubName()) {
				element.disabled = false;
			}
		},
		'#publishUser'	: function (element) {
			element.disabled = false;
		},
		'#searchUser'	: function (element) {
			if(getPubName()) {
				element.disabled = false;
			}
		},
		'#saveUser'	: function (element) {
			element.onclick = saveCreateNewUser; 
		},
		'#cancelUser'	: function (element) {
			element.onclick = cancelCreateNewUser; 
		},
		'#item'	: function (element) {
			if(getPubName()) {
				element.disabled = false;
			}
		},
		'#searchItems'	: function (element) {
			if(getPubName()) {
				element.disabled = false;
			}
		},
		'#finishBtn'	: function (element) {
			element.disabled = false;
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();
}
/* End of Behaviour Functions */

/* Object Functions */
function updatePubName(n) {
	if(getPubName()) {
		var func = 'xmlEditPublishName';
	} else {
		var func = 'xmlAddPublishName';
	}
	setPubName(n);
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : func,
					"publish_name" : getPubName() };
	postXML(xmlArr);
}

function setPubName(n) {
	Sandbox.name = n;
}

function setPubId(id) {
	Sandbox.id = id;
}

function getPubId() {
	return Sandbox.id;
}

function getPubName() {
	return Sandbox.name;
}

function getExpireTime() {
	return Sandbox.expire;
}

function getDefaultExpireTime() {
	return Sandbox.defaultExpire;
}

function getForceExpire() {
	return Sandbox.forceExpire;
}

function getForceDefExpire() {
	return Sandbox.forceDefExpire;
}

function getMaxExpireTime() {
	return Sandbox.maxExpire;
}

function setMaxExpireTime(hours) {
	Sandbox.maxExpire = hours;
}

function setDefaultExpireTime(hours) {
	Sandbox.defaultExpire = hours;
}

function setForceExpire(boolCheck) {
	Sandbox.forceExpire = boolCheck;
}

function setForceDefExpire(boolCheck) {
	Sandbox.forceDefExpire = boolCheck;
}

function setExpireTime(hours) {
	Sandbox.expire = hours;
	if(getMaxExpireTime()) {
		if(hours > getMaxExpireTime()) {
			Sandbox.expire = getMaxExpireTime();
		}
	}
}

function updateExpireTime(hours) {
	if(!hours) {
		hours = $('expireTime').value;
	}
	setExpireTime(hours);
	$('expireTime').value = getExpireTime();

	disableWindow();
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlSetPublishedExpireTime",
					"expire" : getExpireTime() }; 
	postXML(xmlArr);
	enableWindow();
}

function getUserList() {
	return Sandbox.userList;
}

function getUsernameID(username) {
	return Sandbox.userAssocList[username];
}

function setUsernameID(username,id) {
	Sandbox.userAssocList[username] = id;
}

function getItemList() {
	return Sandbox.itemList;
}

function setItemList(dep,cab,doc_id,file_id,desc) {
	if(!Sandbox.itemList[dep]) {
		Sandbox.itemList[dep] = new Object();
	}

	if(!Sandbox.itemList[dep][cab]) {
		Sandbox.itemList[dep][cab] = new Object();
	}

	if(!Sandbox.itemList[dep][cab][doc_id]) {
		Sandbox.itemList[dep][cab][doc_id] = new Object();
	}

	if(file_id) {
		Sandbox.itemList[dep][cab][doc_id][file_id] = desc;
	} else {
		Sandbox.itemList[dep][cab][doc_id]['folder'] = desc;
	}
}

function getItemCount() {
	return Sandbox.itemCount;
}

function setItemCount(ct) {
	Sandbox.itemCount++;
}

function saveItem(cab,doc_id,file_id) {
	if(!file_id) {
		file_id = 0;
	}

	newWindow = 1;
	if(getPubId()) {
		newWindow = 0;
	}

	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlAddPublishingItem",
					"cabinet" : cab,
					"doc_id" : doc_id,
					"file_id" : file_id,
					"new" : newWindow}; 
	postXML(xmlArr);
}

function createCabinetLI(dep,cab,dispName) {
	if(!$(dep+'-'+cab)) {
		var li = document.createElement('li');
		li.id = dep+'-'+cab;
		li.independent = 0;
		li.style.color = "blue";
		li.className = "outerli";

		var img = document.createElement('img');
		img.id = dep+"-toggle-"+cab;
		img.src = "../images/add_16.gif";
		img.department = dep;
		img.cab = cab;
		img.height = 10;
		img.width = 10;
		img.title = "Click to Open";
		img.onclick = toggleCabinet;
		img.style.cursor = "pointer";
		img.style.visibility = 'hidden';
		li.appendChild(img);
		
		var sp = document.createElement('span');
		sp.department = dep;
		sp.cab = cab;
		sp.title = "Click to Remove";
		sp.onmouseover = mOver;
		sp.onmouseout = mOut;
		sp.style.cursor = "pointer";
		sp.style.paddingLeft = "2px";
		sp.onclick = removeItem;
		sp.appendChild(document.createTextNode(cabname));
		li.appendChild(sp);

		ul = document.createElement('ul');	
		ul.id = dep+"-cCont-"+cab;
		ul.className = "itemClosed";
		if(!document.all) {
			ul.style.position = 'relative';
			ul.style.left = '-40px';
		}
		li.appendChild(ul);
		$('itemContainer').appendChild(li);

		if(!document.all) {
			$('itemContainer').style.position = 'relative';		
			$('itemContainer').style.left = '-40px';		
			$('itemContainer').style.width = ($('itemList').offsetWidth-25)+'px';	
		}
	}
}

function createFolderLI(dep,cab,doc_id,foldername,independent) {
	if(!$(dep+'-'+cab+'-'+doc_id)) {
		var li = document.createElement('li');
		li.id = dep+'-'+cab+'-'+doc_id;
		li.independent = 0;
		li.style.color = "blue";
		li.className = "innerli";

		var img = document.createElement('img');
		img.id = dep+"-"+cab+"-toggle-"+doc_id;
		img.src = "../images/add_16.gif";
		img.department = dep;
		img.cab = cab;
		img.doc_id = doc_id;
		img.height = 10;
		img.width = 10;
		img.title = "Click to Open";
		img.onclick = toggleFolder;
		img.style.cursor = "pointer";
		img.style.visibility = 'hidden';
		li.appendChild(img);
		
		var sp = document.createElement('span');
		sp.id = "span-"+dep+"-"+cab+"-"+doc_id;
		sp.department = dep;
		sp.cab = cab;
		sp.doc_id = doc_id;
		sp.title = "Click to Remove";
		sp.onmouseover = mOver;
		sp.onmouseout = mOut;
		sp.style.cursor = "pointer";
		sp.style.paddingLeft = "2px";
		sp.onclick = removeItem;
		sp.appendChild(document.createTextNode(foldername));
		li.appendChild(sp);

		ul = document.createElement('ul');	
		ul.id = dep+'-'+cab+'-fCont-'+doc_id;
		ul.className = "itemClosed";
		li.appendChild(ul);
		$(dep+'-cCont-'+cab).appendChild(li);
		if($(dep+'-toggle-'+cab).style.visibility == 'hidden') {
			$(dep+'-toggle-'+cab).style.visibility = 'visible';
		}

		if(!document.all) {
			ul.style.position = 'relative';		
			ul.style.left = '-40px';		
			ul.style.width = ($('itemList').offsetWidth-25)+'px';	
		}
	}
}

function createDocumentLI(dep,cab,doc_id,file_id,desc) {
	if(!$(dep+'-'+cab+'-'+doc_id+'-'+file_id)) {
		var	ul = $(dep+'-'+cab+'-fCont-'+doc_id);
		var newli = document.createElement('li');
		newli.id = dep+'-'+cab+'-'+doc_id+'-'+file_id;
		newli.independent = 0;
		newli.style.color = "blue";
		newli.className = "innerli";
		
		var sp = document.createElement('span');
		sp.id = "span-"+dep+"-"+cab+"-"+doc_id+"-"+file_id;
		sp.department = dep;
		sp.cab = cab;
		sp.doc_id = doc_id;
		sp.file_id = file_id;
		sp.onmouseover = mOver;
		sp.onmouseout = mOut;
		sp.style.cursor = "pointer";
		sp.title = "Click to Remove";
		sp.onclick = removeItem;
		sp.appendChild(document.createTextNode(desc));
		newli.appendChild(sp);
		ul.appendChild(newli);
		if($(dep+'-'+cab+'-toggle-'+doc_id).style.visibility == 'hidden') {
			$(dep+'-'+cab+'-toggle-'+doc_id).style.visibility = 'visible';
		}
	}
}

function displayItem(XML) {
	var itemInfo = XML.getElementsByTagName('ITEM');
	if(itemInfo.length) {
		var dep,cab,doc_id;
		for(i=0;i<itemInfo.length;i++) {
			var desc = itemInfo[i].firstChild.nodeValue;
			if(itemInfo[i].getAttribute('dep')) {
				dep = itemInfo[i].getAttribute('dep');
				cab = itemInfo[i].getAttribute('cab');
				cabname = itemInfo[i].getAttribute('cabname');
				doc_id = itemInfo[i].getAttribute('doc_id');
				independent = parseInt(itemInfo[i].getAttribute('independent'));
			}

			createCabinetLI(dep,cab,cabname);
			createFolderLI(dep,cab,doc_id,desc,independent);
			var file_id = 0;
			if(itemInfo[i].getAttribute('file_id')) {
				file_id = itemInfo[i].getAttribute('file_id');
				createDocumentLI(dep,cab,doc_id,file_id,desc);
			}
			setItemList(dep,cab,doc_id,file_id,desc);
			setItemCount();
		}
	}

	var rt = XML.getElementsByTagName('ROOT');
	if(rt[0].getAttribute('id')) {
		setPubName(rt[0].getAttribute('name'));
		$('pubName').value = getPubName();

		verifyPubName(rt[0].getAttribute('id'));
	}

	if(mess = rt[0].getAttribute('message')) {
		printMessage(mess);
	}
}

function removeItem() {
	var dep = this.department;
	var cab = this.cab;
	var doc_id = 0;
	var file_id = 0;
	if(this.doc_id) {
		var doc_id = this.doc_id;
		if(this.file_id) {
			file_id = this.file_id;
			var elID = dep+'-'+cab+'-'+doc_id+'-'+file_id;
		} else {
			var elID = dep+'-'+cab+'-'+doc_id;
		}
	} else {
		var elID = dep+'-'+cab;
	}
	var parentEl = $(elID).parentNode;
	parentEl.removeChild($(elID));
	//removeDependencies(parentEl,dep,cab,doc_id,file_id);

	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlRemovePublishedItem",
					"department" : dep,
					"cabinet" : cab,
					"doc_id" : doc_id,
					"file_id" : file_id }; 
	postXML(xmlArr);
}

function removeDependencies(el,dep,cab,doc_id,file_id) {
	var parentCont = "";
	if(el.id.indexOf('cCont') != -1) {
		parentCont = $(dep+'-'+cab);
		parentEl = $('itemContainer');
	} else if(el.id.indexOf('fCont') != -1) {
		parentCont = $(dep+'-'+cab+'-'+doc_id); 
		parentEl = $(dep+'-cCont-'+cab);
	}

	if(el.childNodes.length == 0 && parentCont.independent == 0) {
		if(parentEl) {
			parentEl.removeChild(parentCont);
			removeDependencies(parentEl,dep,cab,doc_id,file_id);
		} else {

		}
	}
}

function toggleCabinet() {
	var id = this.department+'-cCont-'+this.cab;
	if(el = $(id)) {
		if(el.className == "itemOpen") {
			el.className = "itemClosed";
			this.src = "../images/add_16.gif";
			this.title = "Click to open";
		} else {
			el.className = "itemOpen";
			this.src = "../images/remov_16.gif";
			this.title = "Click to close";
		}
	}
}

function toggleFolder() {
	var id = this.department+'-'+this.cab+'-fCont-'+this.doc_id;
	if(el = $(id)) {
		if(el.className == "itemOpen") {
			el.className = "itemClosed";
			this.src = "../images/add_16.gif";
			this.title = "Click to open";
		} else {
			el.className = "itemOpen";
			this.src = "../images/remov_16.gif";
			this.title = "Click to close";
		}
	}
}

function toggleExpire(initialSet) {
	if(getForceDefExpire()) {
		$('noExp').disabled = true;	
		$('custExp').disabled = true;	
		$('expireTime').disabled = true;	

		$('defExp').disabled = false;	
		$('defExp').checked = true;	
		setExpireTime(getDefaultExpireTime());
	} else {
		if(this.id == "custExp") {
			$('expireTime').disabled = false;	
			$('expireTime').focus();	
			$('custExp').checked = true;	
		} else {
			$('expireTime').disabled = true;	
			var exp = 0;
			if(this.id == "defExp") {
				exp = getDefaultExpireTime();
			}

			$('expireTime').value = exp;
			if(!initialSet) {
				updateExpireTime(exp);
			}
		}
	}
}

function addToUserList(username,id) {
	if(!checkForDuplicate(username)) {
		Sandbox.userList.push(username);
		Sandbox.userList = Sandbox.userList.sort();

		if(id) {
			setUsernameID(username,id);
		}
		return true;
	}
	return false;
}

function checkForDuplicate(username) {
	var list = getUserList();
	for(var i=0;i<list.length;i++) {
		if(username == list[i]) {
			return true;
		}
	}
	return false;
}
/* End of Object Functions */

/* On-Enter Functions */
function pubNameOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		createSandbox();				
	}
}

function pubUserOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		saveCreateNewUser();				
	}
}

function pubSearchUserOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		searchUserList();				
	}
}

function pubSearchItemOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		searchItemList();				
	}
}

function pubExpireOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		updateExpireTime();				
		return true;
	}

	if (((charCode >= 48 && charCode <= 57) || charCode == 8) || (charCode == 37) || (charCode == 39)) {    
		return true;
	}
	return false;
}
/* End of On-Enter Functions */

/* Standard Functions */
function mOver() {
	this.style.backgroundColor = "blue";
	this.style.color = "#ebebeb";
}

function mOut () {
	this.style.backgroundColor = "#ebebeb";
	this.style.color = "blue";
}

function printMessage(mess) {
	removeElementsChildren($('error'));
	if(mess) {
		$('error').appendChild(document.createTextNode(mess));
	}
}

function getNodePlace(username) {
	var list = getUserList();
	for(var i=0;i<list.length;i++) {
		if(username == list[i]) {
			if((i+1) > list.length) {
				return false;
			} else {
				var uname = list[(i+1)];
				return $('email-'+getUsernameID(uname));
			}			
		}
	}
}
/* End of Standard Functions */

/* Initial Load Functions */
function getPublishingData() {
	disableWindow();
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlLoadPublishingData" }; 
	postXML(xmlArr);
}

function setPublishingData(XML) {
	var searchList = XML.getElementsByTagName('SEARCH');	
	if(searchList.length) {
		var selBox = $('pubList');
		var opt = document.createElement('option');
		opt.value = '__default';
		opt.appendChild(document.createTextNode('Choose One'));
		selBox.appendChild(opt);
		for(var i=0;i<searchList.length;i++) {
			var opt = document.createElement('option');
			opt.value = searchList[i].getAttribute('id');
			opt.appendChild(document.createTextNode(searchList[i].firstChild.nodeValue));

			selBox.appendChild(opt);
		}
	}
	
	var userList = XML.getElementsByTagName('USER');
	if(userList.length) {
		var listBox = $('listContainer');
		var uname, liEl;
		for(var i=0;i<userList.length;i++) {
			uname = userList[i].firstChild.nodeValue;
			liEl = document.createElement('li');
			liEl.id = 'email-'+userList[i].getAttribute('id');
			liEl.name = uname;
			liEl.title = "Click to Add";
			liEl.style.color = "blue";
			liEl.onmouseover = mOver;
			liEl.onmouseout = mOut;
			liEl.onclick = selectUser;
			liEl.appendChild(document.createTextNode(uname));

			listBox.appendChild(liEl);
			addToUserList(uname,userList[i].getAttribute('id'));
		}
	}

	var rt = XML.getElementsByTagName('ROOT');	
	setMaxExpireTime(parseInt(rt[0].getAttribute('maxExpire')));
	setDefaultExpireTime(parseInt(rt[0].getAttribute('defExpire')));
	setForceExpire(parseInt(rt[0].getAttribute('forceExpire')));
	setForceDefExpire(parseInt(rt[0].getAttribute('forceDefExpire')));
	toggleExpire(true);

	if(getForceExpire()) {
		$('noExp').disabled = true;	
		$('defExp').checked = true;	
	}

	removeElementsChildren($('maxDesc'));
	if(getMaxExpireTime()) {
		var mess = '(max:'+getMaxExpireTime()+' hours)';
		$('maxDesc').appendChild(document.createTextNode(mess));
	}
	
	enableWindow();
	setTimeout(function() {$('pubName').focus()},20);
	initLoad = true;
}
/* End of Initial Load Functions */

/* Publishing Search Name Functions */
function getSandbox() {
	disableWindow();
	var xmlArr = {	"include" : "publishing/publishingFuncs.php",
					"function" : "xmlGetSandbox",
					"publish_search_id" : this.value }; 
	postXML(xmlArr);
}

function setSandbox(XML) {
	resetSelectedUsers();	
	var ulist = XML.getElementsByTagName('USER');	
	if(ulist.length) {
		for(i=0;i<ulist.length;i++) {
			var uname = ulist[i].firstChild.nodeValue;
			var id = ulist[i].getAttribute('id');
			highlightRow(uname);
		}
	}	
	removeElementsChildren($('itemContainer'));
	displayItem(XML);

	var pubEl = $('pubList');
	var pubID = pubEl.options[pubEl.selectedIndex].value;
	var pubName = pubEl.options[pubEl.selectedIndex].firstChild.nodeValue;
	
	$('pubAdd').value = "Edit";
	$('pubAdd').onclick = editSandbox;
	$('pubName').value = pubName;
	$('pubName').onkeypress = pubNameOnEnter;
	setPubName(pubName);
	setPubId(pubID);

	$('search').value = "";
	$('item').value = "";

	var rt = XML.getElementsByTagName('ROOT');	
	setExpireTime(parseInt(rt[0].getAttribute('expire')));
	setSandboxExpiration();
	
	enableWindow();
	printMessage();
}

function setSandboxExpiration() {
	if(getForceDefExpire()) {
		updateExpireTime(getDefaultExpireTime());
	} else {
		if(getExpireTime()) {
			if(getExpireTime() == getDefaultExpireTime()) {
				$('defExp').checked = true;	
			} else {
				$('custExp').checked = true;	
				$('expireTime').disabled = false;	
			}
		} else {
			if(getForceExpire()) {
				$('defExp').checked = true;	
				updateExpireTime(getDefaultExpireTime());
			} else {
				$('noExp').checked = true;	
			}
		}
		$('expireTime').value = getExpireTime();

	}

}

function createSandbox() {
	if(!getPubName()) {
		if(el = $('pubName').value) {
			disableWindow();
			updatePubName(el);
		} else {
			printMessage("Publish name is empty");
		}
	} else {
		editSandbox();
	}
}

function verifyPubName(id,mess) {
	if(id) {
		if(!getPubId()) {
			var opt = document.createElement('option');
			opt.value = id;
			opt.selected = true;
			opt.appendChild(document.createTextNode(getPubName()));
		
			$('pubList').appendChild(opt);
			removeDefault($('pubList'));

			$('pubAdd').value = "Edit";
			$('pubAdd').onclick = editSandbox;
			$('pubName').onkeypress = pubNameOnEnter;
			setPubId(id);
		}
	} else {
		Sandbox.name = "";
	}
	enableWindow();
	if(mess) {
		printMessage(mess);
	}
}

function editSandbox() {
	if($('pubName').value != getPubName()) {
		var opts = $('pubList').options;
		for(var i=0;i<opts.length;i++) {
			if(opts[i].value == getPubId()) {
				removeElementsChildren(opts[i]); 
					
				opts[i].appendChild(document.createTextNode($('pubName').value));
				updatePubName($('pubName').value);
				break;
			}
		}
	}
}
/* End of Publishing Search Name Functions*/

/* Publishing Users Functions*/
function createNewUser() {
	$('newUser').onclick = function () {};
	var userDiv = document.createElement('div');
	userDiv.style.paddingTop = "0px";

	var sImg = document.createElement('img');
	sImg.src = '../energie/images/save.gif';
	sImg.id = 'saveUser';
	sImg.alt = 'Save';
	sImg.title = 'Save';
	sImg.onclick = saveCreateNewUser;
	userDiv.appendChild(sImg);
	
	var cImg = document.createElement('img');
	cImg.src = '../energie/images/cancl_16.gif';
	cImg.id = 'cancelUser';
	cImg.alt = 'Cancel';
	cImg.title = 'Cancel';
	cImg.style.paddingLeft = "5px";
	cImg.style.paddingRight = "5px";
	cImg.onclick = cancelCreateNewUser;
	userDiv.appendChild(cImg);

	var inputBox = document.createElement('input');
	inputBox.type = "text";
	inputBox.id = "publishUser";
	inputBox.name = "publishUser";
	inputBox.onkeypress = pubUserOnEnter;
	inputBox.style.width = "80%";
	userDiv.appendChild(inputBox);

	removeElementsChildren($('newUser'));
	$('newUser').appendChild(userDiv);
	
	setTimeout(function() {inputBox.focus()},500);
}

function cancelCreateNewUser() {
	var sp = document.createElement('div');
	sp.onmouseover = mOver;
	sp.onmouseout = mOut;
	sp.appendChild(document.createTextNode('Create New User'));

	removeElementsChildren($('newUser'));
	$('newUser').appendChild(sp);
	$('newUser').onclick = createNewUser;
}

function saveCreateNewUser() {
	var username = $('publishUser').value;
	if(username) {
		if(!addToUserList(username)) {	
			var mess = "User already exists";
			printMessage(mess);

			$('publishUser').select();
		} else {
			var xmlArr = {	"include" : "publishing/publishingFuncs.php",
							"function" : "xmlCreatePublishUser",
							"publish_user" : username }; 
			postXML(xmlArr);
		}
	} else {
		var mess = "Username is empty";
		printMessage(mess);

		$('publishUser').select();
	}
}

function addNewUser(userID,username) {
	setUsernameID(username,userID);

	var liEl = document.createElement('li');
	liEl.id = 'email-'+userID;
	liEl.name = username;
	liEl.style.color = "blue";
	liEl.appendChild(document.createTextNode(username));
	liEl.onclick = selectUser;

	var node = getNodePlace(username);
	if(node) {
		$('listContainer').insertBefore(liEl,node);
	} else {
		$('listContainer').appendChild(liEl);
	}
	$('publishUser').select();

	liEl.click();
	var mess = "User added successfully";
	printMessage(mess);
}

function searchUserList() {
	var search = $('search').value;
	if(search) {
		var list = getUserList();
		if(list.length) {
			var j = 1;
			var prevNode = $('listContainer').childNodes(j);
			for(var i=0;i<list.length;i++) {
				if(list[i].indexOf(search) != -1) {
					var username = list[i];
					var userID = getUsernameID(username);
					var pnode = $('email-'+userID).parentNode;
					if(prevNode.name != username) {
						var userNode = pnode.removeChild($('email-'+userID));			
						pnode.insertBefore(userNode,prevNode);
					}
					j++;
					if(el = $('listContainer').childNodes(j)) {
						prevNode = el;
					}
				}
			}
		} else {
			var mess = "There are no users created to search";
			printMessage(mess);
		}
	} else {
		var mess = "Search value is empty";
		printMessage(mess);
	}
}

function searchItemList() {
	var search = $('item').value;
	var found = false;
	if(search) {
		var list = getItemList();
		if(getItemCount()) {
			for(var dep in list) {
				var cabList = list[dep];
				for(var cab in cabList) {
					var folderList = cabList[cab];
					for(var folder in folderList) {
						var docList = folderList[folder];
						for(var doc in docList) {
							desc = docList[doc];	
							if(desc.indexOf(search) != -1) {
								found = true;
								var el = $(dep+'-cCont-'+cab);
								el.className = "itemOpen";
								$('span-'+dep+'-'+cab+'-'+folder).style.color = "green";
								$(dep+'-toggle-'+cab).src = "../images/remov_16.gif";
								$(dep+'-toggle-'+cab).title = "Click to close";
								if(doc != "folder") {	
									var el = $(dep+'-'+cab+'-fCont-'+folder);
									el.className = "itemOpen";
									$('span-'+dep+'-'+cab+'-'+folder+'-'+doc).style.color = "green";
									$(dep+'-'+cab+'-toggle-'+folder).src = "../images/remov_16.gif";
									$(dep+'-'+cab+'-toggle-'+folder).title = "Click to close";
								}	
							} else {
								$('span-'+dep+'-'+cab+'-'+folder).style.color = "blue";
								if(doc != "folder") {
									$('span-'+dep+'-'+cab+'-'+folder+'-'+doc).style.color = "blue";
								}
							}
						}
					}
				}
			}
			if(!found) {
				var mess = "no results found";
				printMessage(mess);
			}
		} else {
			var mess = "There are no published items to search";
			printMessage(mess);
		}
	} else {
		var mess = "Search value is empty";
		printMessage(mess);
	}
}

function highlightRow(username) {
	var userEl = $('email-'+getUsernameID(username));
	userEl.title = "Click to Remove";
	userEl.style.backgroundColor = "green";
	userEl.style.color = "yellow";
	userEl.onclick = deleteSelectedUser;
	userEl.onmouseover = function () {};
	userEl.onmouseout = function () {};
}

function unhighlightRow(username) {
	var userEl = $('email-'+getUsernameID(username));
	userEl.title = "Click to Add";
	userEl.style.backgroundColor = "#ebebeb";
	userEl.style.color = "blue";
	userEl.onclick = selectUser;
	userEl.onmouseover = mOver;
	userEl.onmouseout = mOut;
}
/* End of Publishing Users Functions*/
