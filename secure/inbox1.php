<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/inbox.php';
include_once '../lib/mime.php';
include_once '../lib/padding.php';
include_once '../energie/energiefuncs.php';
include_once '../lib/delegate.php';
if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
		if( isSet( $_GET['selectRow'] )) {
			$theRow=$_GET['selectRow'];
		} else {
			$theRow='';
		}

    if(isset($_GET['sortField']) and $_GET['sortField']) {
        $sortField = $_GET['sortField'];//fieldname the user wants to sort
    } else {
	$sortField = '';
    }
	if (!isset($_SESSION['ibxFolder'])) {
		$_SESSION['ibxFolder'] = '';
	}
    
	if(isset($_SESSION['ibxSortingArr']) and $_SESSION['ibxSortingArr']) {
		$sortDirArr = $_SESSION['ibxSortingArr'];
	} else {
		$sortDirArr = array(
			'name'      			=> 'ASC',
			'size'      			=> 'ASC',
			'realTime'  			=> 'ASC',
			'delegate_owner'		=> 'ASC',
			'delegate_username'		=> 'ASC'
		);
	}
                                                                                                                             
    if($sortField) {
		$sortDir = $sortDirArr[$sortField];
		if($sortDir == 'ASC') {
			$sortDirArr[$sortField] = 'DESC';
		} else {
			$sortDirArr[$sortField] = 'ASC';
		}
		$sortDir = $sortDirArr[$sortField];
		$_SESSION['ibxSortingArr'] = $sortDirArr;
		$_SESSION['ibxSelectedField'] = $sortField;
    } else {
		if(isset($_SESSION['ibxSelectedField']) and $_SESSION['ibxSelectedField']) {
			$sortField = $_SESSION['ibxSelectedField']; 
		} else {
			$sortField = 'name';
		}
		$sortDir = $sortDirArr[$sortField];
		}
	$_SESSION['lastURL'] = getRequestURI();
    //receives message
	if(isset($_GET['mess'])) {
	    $mess = $_GET['mess'];
	} else {
		$mess = '&nbsp;';
	}
	//gets the current page
	if( isSet( $_GET['page'] ) ) {
    	$page = $_GET['page'];
		if( isSet($_GET['folder']) && $_GET['folder'] ) {
			$_SESSION['ibxFolderPage'] = $page;
			$_SESSION['ibxFolder'] = $_GET['folder'];
			$currFolder = '';
		} else {
			$_SESSION['ibxGeneralPage'] = $page;
			$_SESSION['ibxFolderPage'] = 0;
			$currFolder = $_SESSION['ibxFolder'];
			$_SESSION['ibxFolder'] = '';
		}
	} elseif( isSet($_POST['page']) ) {
    	$page = $_POST['page'];
		if( isSet($_GET['folder']) && $_GET['folder'] ) {
			$_SESSION['ibxFolderPage'] = $page;
			$_SESSION['ibxFolder'] = $_GET['folder'];
			$currFolder = '';
		} else {
			$_SESSION['ibxGeneralPage'] = $page;
			$_SESSION['ibxFolderPage'] = 0;
			$currFolder = $_SESSION['ibxFolder'];
			$_SESSION['ibxFolder'] = '';
		}
		if( $page != NULL )
			$_SESSION['lastURL'] .= "&amp;page=$page";
	} else {
		if( isSet($_GET['folder']) && $_GET['folder'] ) {
			$page = $_SESSION['ibxFolderPage'];
			$_SESSION['ibxFolder'] = $_GET['folder'];
			$currFolder = '';
		} else {
			if(isset($_GET['new'])) {
				$page = 0;
				$_SESSION['ibxGeneralPage'] = 0;
			} else {
				$page = $_SESSION['ibxGeneralPage'];
			}
			$_SESSION['ibxFolderPage'] = 0;
			$currFolder = $_SESSION['ibxFolder'];
			$_SESSION['ibxFolder'] = '';
			if(isset($_GET['new']) and $_GET['new'] == 1) {
				$currFolder = '';
			}
		}
	}
	if(!empty($_GET['currItem'])) {
		$currFolder = $_GET['currItem'];
	}

	//get user settings for whether or not to display delete cabinets
	$userSettings = new Usrsettings($user->username, $user->db_name);
	//retrieves whether or not to be able to delete and warn
    $glbSettings = new GblStt( $user->db_name, $db_doc);

	//retrive user settings to view how many results per page   
	$pagedisplay = $userSettings->get( 'results_per_page' );
	if( $pagedisplay == NULL ) 
		$pagedisplay = 25;
		
	if(isset($_GET['res'])) {
		$userSettings->set('results_per_page',$_GET['res']);
	}

	$showNew = $userSettings->get ('showNewInbox');

	if ($showNew == '1') {
		$showNew = true;
	} else {
		$showNew = false;
	}

    //This will determine type of inbox either regular or personal
		$userList = array();
		$completeUserList = array ();	
		$username = '';
	if( isset($_GET['type']) && $_GET['type'] == 1 ) {//personal

		$personal = true;
		$personalPath = $user->getRootPath()."/personalInbox";
		$URL = "inbox1.php?type=1";
/*		if( isSet($_GET['path']) ) {
			$path = $_GET['path'];
			$username = $user->username;
		} else*/if(isset($_GET['username']) and $_GET['username']) {
			$path = $personalPath."/".$_GET['username']."/";
			$username = $_GET['username'];
			$URL .= "&amp;username=".$_GET['username'];
		} else {
			$path = $personalPath."/".$user->username."/";
			$username = $user->username;
		}
    	$user->audit("viewed personal inbox", "viewed personal inbox");
		$type = 1;

		$list = getTableInfo($db_object,'access',array('username'),array(),'queryCol');
		$viewAll = 0;
		$viewAll = ($glbSettings->get('inboxAccess')) ? 1 : 0;
		if(!$viewAll) {
		    $viewAll = ($userSettings->get('inboxAccess')) ? 1 : 0;
		}

		$viewGroup = 0;
		$viewGroup = ($glbSettings->get('inboxGroupAccess')) ? 1 : 0;
		if(!$viewGroup) {
		    $viewGroup = ($userSettings->get('inboxGroupAccess')) ? 1 : 0;
		}

		foreach($list AS $uname) {
			if($user->greaterThanUser($uname)) {
				$userList[] = $uname; 
			}
			$completeUserList[] = $uname;
		}
		usort($userList,'strnatcasecmp');
		usort($completeUserList,'strnatcasecmp');
		if($viewAll) {
			$userList = $completeUserList;
		} elseif($viewGroup) {
		    $groupList = getGroupsForUser($db_object,$user->username);
		    $completeGroupList = array();
		    foreach($groupList AS $g) {
			$gList = getUsersFromGroup($db_object,$g);
			foreach($gList AS $u) {
			    if(!in_array($u,$completeGroupList)) {
				$completeGroupList[] = $u;
			    }
			}
		    }
		    $userList = $completeGroupList;
		}
	} else {//regular
		$personalPath = '';
		$personal = false;
/*		if( isSet($_GET['path']) )
			$path = $_GET['path'];
		else
*/    		$path = $user->getRootPath()."/inbox/";
		$URL = "inbox1.php";
    	$user->audit("viewed public inbox", "viewed public inbox");
		$type = 0;
	}
	if(is_dir($path)) {
		$dh = opendir ($path);
		$delFiles = array ();
		$myEntry = readdir ($dh);
		while ($myEntry !== false) {
			if (is_file ($path.'/'.$myEntry) and getExtension($myEntry) ==
					'DAT') {
				$delFiles[] = $path.'/'.$myEntry;
			}
			$myEntry = readdir ($dh);
		}
		closedir ($dh);
		foreach ($delFiles as $myFile) {
			unlink ($myFile);
		}
	}
	//Viewing a delegate file or folder
	if (isset($_GET['delegateID'])) {
		$delegateID = $_GET['delegateID'];
	} else {
		$delegateID = '';
	}
	//type==1 if personal inbox, create the delegate object
	$delegateObj = new delegate( $user->getRootPath()."/personalInbox/",$username, $db_object );

	//This will determine if a folder has been selected to view contents
	if(isset($_GET['folder']) and $_GET['folder']) {
		$myFolder = $_GET['folder'];
		if($delegateID) {
			$path = $user->getRootPath()."/personalInbox/".$delegateObj->getFullPathByID($delegateID)."/";
			$URL .= "?delegateID=$delegateID";
		} elseif( is_dir($path.urldecode($_GET['folder'])) ) {
			$path .= urldecode( $_GET['folder'] )."/";
		} else {
			$path .= "../".urldecode( $_GET['folder'] )."/";
		}
	} else {
		$myFolder = '';
	}
	
	if( isSet( $_GET['delete'] ) && isset ($_POST['check1'])) {
		$mess = deleteFromInbox ($path, $user, $db_doc);
	} elseif( isSet($_POST['delegated_user']) ) {
		$mess = delegateFromInbox($user, $delegateObj, $username);
	} elseif( isSet( $_GET['move'] ) ) {
		//This checks to see if delegated files were selected to be moved
		if( isSet($_GET['inboxFiles']) && isSet($_POST['check1'])  && ((int) $_GET['doc_id']) > 0) {
			$mess = moveFromInbox( $path, $user, $db_doc, $_POST['check1'], $db_object );
		}
	}
	//We need to re-acquire the delegate object, just in case we delegated
	//anything, or if anything changed.
	$delegateObj = new delegate( $user->getRootPath()."/personalInbox/",$username, $db_object );
	$filelist = array();
    $folderlist = array();
	$extraFolders = array();
	$delegateFolders = array(); //Holds the folders that should be in the delegated section
	$delegateFiles = array(); //Holds the files that should be in the delegated section
	$delegateArr = array();
//	$delegateObj = new delegate( $user->getRootPath()."/personalInbox/",$username, $db_object );
	//Tests if viewing a folder
	if(isset($_GET['folder']) and $_GET['folder']) 
		//Holds the delegate files for the user from the db
		$delegateList = $delegateObj->getDelegateList();
	if( !is_dir( $path ) ) {
		mkdir( $path, 0777 );
	}
	error_log("*******path:".$path);
    $handle = opendir($path);
	//$delPath = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$username;
	while (false !== ($file = readdir($handle))) {
        // Check if file, then count or add it
        if(!is_dir($path."/".$file)) {
			if( !$delegateObj->findInDelegateList($path.$file) ) 
            	$filelist[] = $file;
        } elseif(!isSet($_GET['folder']) || !$_GET['folder']) {// Else, check if it is a folder
			if( !$delegateObj->findInDelegateList($path.$file)  
				&& $file != "." && $file != "..") {
                $folderlist[] = $file;
            }
        } elseif(isSet($_GET['folder'])) {
            if($file != "." && $file != "..") {
				$extraFolders[] = $file;
			}
		}
    }

	moveNestedFolders($path,$extraFolders,$filelist);
//	$folderlist = checkFolders( $folderlist,$db_doc );
	$allFolders = getFolderStats($folderlist, $path); //Returns an array even if empty
    $allFiles = getFileStats($filelist, $path);
    sortFileList($allFiles, $sortField, $sortDir);
    sortFileList($allFolders, $sortField, $sortDir);
	$completelist = array_merge( $allFolders, $allFiles );
	$parentURL = $URL;
	
	$inboxView = $userSettings->get('inboxView');
	if($inboxView == NULL) {
		$inboxView = 0;
	}

	$res = $userSettings->get('results_per_page');
	if (!$res) $res=25;
	$pool = $user->characters(4);
	if (isset ($_GET['type'])) {
		$myType = $_GET['type'];
	} else {
		$myType = '';
	}
echo<<<ENERGIE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<head>
  <title>Inbox</title>
 <link rel="stylesheet" type="text/css" href="../lib/style.css">
 <style type="text/css">
 form {
 	margin: 0;
 }
  div.addNewFolderDiv {	position: absolute; 
						top: 10%; 
						left: 10%; 
						background: #ffffff; 
						display: none }

  iframe.addNewFolder { width: 500px; 
						height: 250px }

  div.addNewActionDiv {	display: none;
						padding: 10px;
						background: #ffffff }
	
	.headerImg {
		font-size: 9pt;
        padding-left: 5px;
        padding-right: 5px;
        cursor: pointer;
        background-color: #ebebeb;
    }

	.headerImgSelect {
        font-weight: bold;
        font-size: 9pt;
        color: white;
        padding-left: 5px;
        padding-right: 5px;
        cursor: pointer;
        background-color: #003b6f;
    }

	#addNewDocumentDiv {
		font-size: 9pt;
	}
 </style>
 <script type="text/javascript" src="../lib/prototype.js"></script>
 <script src="../delegation/wz_dragdrop.js" type="text/javascript"></script>
 <script type="text/javascript" src="../lib/behaviour.js"></script>
 <script type="text/javascript" src="../lib/settings.js"></script>
 <script type="text/javascript" src="../lib/inbox1.js"></script>
<!-- <script type="text/javascript" src="../search/searchResults.js"></script> -->
 <script type="text/javascript">
 var selRow = "$currFolder";
	var inboxView = $inboxView;
	var URL,fpath,filename,num_id,ftype,ext,delID;
  	parent.document.getElementById('mainFrameSet').setAttribute('cols','100%,*');
  	parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
  	parent.sideFrame.window.location = '../energie/left_blue_search.php';
 messTimer();
	function reInitialize() {
		//SET_DHTML(CURSOR_MOVE,"addNewActionDiv");
		//Draggable.prototype.initialize("addNewActionDiv");
		if(inboxView == 1) {
			minimizeView();
		} else {
			maximizeView();
		}
	}

  function mOver( cur ) {
	var myEl;
	if(mOver.arguments.length > 1) {
		myEl = document.getElementById(mOver.arguments[1] + cur);
	} else {
		myEl = document.getElementById('folder:' + cur);
	}

  	myEl.style.cursor = 'pointer';
  	if(cur == selRow) {
  		myEl.style.backgroundColor = '#8779e0';
  	} else {
		myEl.style.backgroundColor = '#888888';
  	}
  }
  function mOut( cur ) {
	var myEl;
	if(mOut.arguments.length > 1) {
		myEl = document.getElementById(mOut.arguments[1] + cur);
	} else {
		myEl = document.getElementById('folder:' + cur);
	}

  	if(cur == selRow) {
  		myEl.style.backgroundColor = '#8799e0';
  	} else {
		myEl.style.backgroundColor = '#ebebeb';
  	}
  }
  
  function selectRow(row) {
	var newEl;

  	if(selRow && getEl('delFolder:' + selRow)) {
		getEl('delFolder:' + selRow).style.backgroundColor = '#ebebeb';
	} else if(selRow && getEl('folder:' + selRow)) {
  		getEl('folder:' + selRow).style.backgroundColor = '#ebebeb';
  	}

	if(selectRow.arguments.length > 1) {
		newEl = getEl(selectRow.arguments[1] + row);
	} else {
		newEl = getEl('folder:' + row);
	}
    selRow = row;
	if(row && newEl) {
    	newEl.style.backgroundColor = '#8799e0'; 
	}
  }
  
  function viewFile( urlName, ct) {
	var fileID = "folder:" + unescape(urlName);
	var myURL = '';
	var getStr = '?inbox=1&name=' + urlName
	             + '&user={$type}&foldname=' + escape('{$myFolder}') + '&username={$username}'
				 + '&delegateID=$delegateID&tmp=0/' + urlName;
	if ($('showNew') && $('showNew').checked) {
		var inboxPage=0;
		if (top.mainFrame.document.getElementById('page')){
			inboxPage=top.mainFrame.document.getElementById('page').value - 1;
		}
		var pagedisplay=$res;
		var pPage=ct;
		var ct = (inboxPage * pagedisplay)+(pPage);

	}
		var pgStr = getStr + '&page=' + ct; 
	

    var domDoc = createDOMDoc();
    var root = domDoc.createElement('ROOT');
    domDoc.appendChild(root);

    var fname = domDoc.createElement('FILENAME');
    fname.appendChild(domDoc.createTextNode(urlName));
    root.appendChild(fname);

    var fold = domDoc.createElement('FOLDER');
    fold.appendChild(domDoc.createTextNode('$myFolder'));
    root.appendChild(fold);

    var uname = domDoc.createElement('USERNAME');
    uname.appendChild(domDoc.createTextNode('$username'));
    root.appendChild(uname);

    var inboxType = domDoc.createElement('FTYPE');
    inboxType.appendChild(domDoc.createTextNode('$type'));
    root.appendChild(inboxType);

	xmlStr = domToString(domDoc);
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', '../secure/inboxMove.php?fileType=1', true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(xmlStr);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			var type = xmlhttp.responseText;
			if(type == "image/jpeg") {
				myURL = '../energie/display2.php'+getStr; 
			} else {
				//myURL = 'displayInbox.php'+getStr;
				myURL = 'inboxView.php'+pgStr;
			}
			if ($('showNew') && $('showNew').checked) {
				var height = Math.ceil(0.9 * window.screen.availHeight);
				var width = Math.ceil(0.9 * window.screen.availWidth);
				window.open (myURL, 'inbox_open', config='height=' + height + ',width=' + width + 
					',toolbar=no,status=no,directories=no,location=no,menubar=no,' + 
					'resizeable=yes,fullscreen=no');
			} else {
//				parent.document.getElementById('rightFrame').setAttribute('rows', '*,25');
				parent.document.getElementById('mainFrameSet').setAttribute('cols', '40%,60%');
//				parent.bottomFrame.location='back.php'+pgStr;
				parent.sideFrame.location = myURL;
			}
		}
	};

  }

  function viewDelegatedFile( urlName, delegateID ) {
	var fileID = "folder:" + unescape(urlName);
	var myURL = 'displayInbox.php';
	myURL = myURL + '?inbox=1&name=' + urlName
			+ '&user={$type}&foldname=' + escape('{$myFolder}') + '&username={$username}'
			+ '&delegateID=' + delegateID + '&tmp=0/' + urlName;

    parent.bottomFrame.location='back.php';
	parent.sideFrame.location=myURL;
  }

  function setInboxLocation() {
    var domDoc = createDOMDoc();

    var root = domDoc.createElement('ROOT');
    domDoc.appendChild(root);

    var entry = domDoc.createElement('ENTRY');
    var key = domDoc.createElement('KEY');
    key.appendChild(domDoc.createTextNode('include'));
    entry.appendChild(key);
    var value = domDoc.createElement('VALUE');
    value.appendChild(domDoc.createTextNode('secure/uploadFuncs.php'));
    entry.appendChild(value);
    root.appendChild(entry);

    var entry = domDoc.createElement('ENTRY');
    var key = domDoc.createElement('KEY');
    key.appendChild(domDoc.createTextNode('function'));
    entry.appendChild(key);
    var value = domDoc.createElement('VALUE');
    value.appendChild(domDoc.createTextNode('setUploadPath'));
    entry.appendChild(value);
    root.appendChild(entry);

    var entry = domDoc.createElement('ENTRY');
    var key = domDoc.createElement('KEY');
    key.appendChild(domDoc.createTextNode('username'));
    entry.appendChild(key);
    var value = domDoc.createElement('VALUE');
    value.appendChild(domDoc.createTextNode('{$username}'));
    entry.appendChild(value);
    root.appendChild(entry);
    
	var entry = domDoc.createElement('ENTRY');
    var key = domDoc.createElement('KEY');
    key.appendChild(domDoc.createTextNode('type'));
    entry.appendChild(key);
    var value = domDoc.createElement('VALUE');
    value.appendChild(domDoc.createTextNode('{$myType}'));
    entry.appendChild(value);
    root.appendChild(entry);

    var entry = domDoc.createElement('ENTRY');
    var key = domDoc.createElement('KEY');
    key.appendChild(domDoc.createTextNode('folder'));
    entry.appendChild(key);
    var value = domDoc.createElement('VALUE');
    value.appendChild(domDoc.createTextNode('{$myFolder}'));
    entry.appendChild(value);
    root.appendChild(entry);

	xmlStr = domToString(domDoc);
  
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', '../lib/ajaxPostRequest.php', true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(xmlStr);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseXML) {
				window.location = 'uploadInbox2.html';
			}
		}
	};
  }

  function viewUpload() {
	setInboxLocation();
  }
  function selectAll(field) {
  	var el;
  	i = 1;
	if(field.name == "selectfolder") {
		var type = 'fileCheck:';
	} else {
		var type = 'delFileCheck:';
	}

	if( field.checked == true ) {
      while(el = getEl(type + i)) {
      	el.checked = true;
      	i++;
      }
	} else {
      while(el = getEl(type + i)) {
      	el.checked = false;
      	i++;
      }
	}
  }
  function someSelected () {
	var someSelect = false;
	var i = 1;
	while(el = getEl('fileCheck:'+i)) {
		if(el.checked == true) {
			someSelect = true;
			break;
		}
		i++;
	}
	return someSelect;
  }
  function askUser() {
	var someSelect = someSelected ();
	if(someSelect) {
		message = "All files and folders selected will be removed";
		answer = window.confirm(message);
		if(answer == true)
		
			deleteInbox();
	} else {
		var errMsg = document.getElementById('errMsg');
		while(errMsg.hasChildNodes()) {
			errMsg.removeChild(errMsg.firstChild);
		}
		var myTxt = document.createTextNode('Please Select Files or Folders To Delete');
		errMsg.appendChild(myTxt);
	}
  }
  function deleteInbox() {
    document.filename.action += '&delete=1';
	document.filename.submit();
  }

  function switchUser() {
    var otherUser = document.getElementById('otherPersonal');
    window.location = otherUser[otherUser.selectedIndex].value;
  }

  function moveInboxFiles() {
	if(xmlStr = createInboxXML()) {
		var xmlhttp = getXMLHTTP();
		xmlhttp.open('POST', 'inboxMove.php?movefiles=1', true);
		xmlhttp.setRequestHeader('Content-Type',
							'application/x-www-form-urlencoded');
		xmlhttp.send(xmlStr);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				if(xmlhttp.responseText) {
					window.location = 'inbox1.php?type=1&page=$page'
									+ '&username=$username&folder={$myFolder}'
									+ '&mess=Files Successfully Moved';
				} else {
					clearDiv(getEl('errMsg'));
					getEl('errMsg').appendChild(document.createTextNode('Error Moving Files'));
				}
			}
		};
	} else {
		clearDiv(getEl('errMsg'));
		getEl('errMsg').appendChild(document.createTextNode('No files have been selected'));
	}
  }

  function createInboxXML() {
	var isFileChecked = false;
    var domDoc = createDOMDoc();

    var root = domDoc.createElement('ROOT');
    domDoc.appendChild(root);

    var curUser = domDoc.createElement('CURRENT_USER');
    curUser.appendChild(domDoc.createTextNode('$username'));
    root.appendChild(curUser);

    var destUsername = getEl('movePersonal');
    var du = destUsername[destUsername.selectedIndex].value;
    var destUser = domDoc.createElement('DESTINATION_USER');
    destUser.appendChild(domDoc.createTextNode(du));
    root.appendChild(destUser);

    var path = domDoc.createElement('PATH');
    path.appendChild(domDoc.createTextNode('$personalPath'));
    root.appendChild(path);

    var folder = domDoc.createElement('FOLDER');
    folder.appendChild(domDoc.createTextNode('{$myFolder}'));
    root.appendChild(folder);

    var formEl = document.filename;
	var i = 1;
	while(el = getEl('fileCheck:'+i)) {
		if(el.checked == true && el.name != 'selectfolder') {
			isFileChecked = true;
            var selectedFile = domDoc.createElement('FILE');
            selectedFile.appendChild(domDoc.createTextNode(el.value));
            root.appendChild(selectedFile);
		}
		i++;
	}
	if(isFileChecked) {
		return domToString(domDoc);
	} else {
		return false;
	}
  }

  function editFolder(curURL,path,fname,num,fileType) {
	if(num_id) {
		cancelFolder(num_id);	
	}
	URL = curURL;
	fpath = path;
	filename = fname;
	num_id = num;
	ftype = fileType;
	var nameArr = fname.split('.');
	if(editFolder.arguments.length > 5) {
		delID = editFolder.arguments[5];
	} else {
		delID = 0;
	}

	if(ftype != 'folder') {
		ext = nameArr.pop(); 
	} else {
		ext = '';
	}
	var name = nameArr.join('.');
	var pnode = getEl('file-'+num).parentNode; 
	pnode.onclick = function() {return true};
	fnode = pnode.removeChild(getEl('file-'+num));
	var saveImg = new Image();
	saveImg.id = 'file-'+num;
	saveImg.src = "../energie/images/save.gif";
	saveImg.style.borderWidth = "0";
	saveImg.alt = "Save";
	saveImg.onclick = function() { saveFolder(path,fname,num,fileType,delID) };
	pnode.appendChild( saveImg );

	pnode = getEl('edit-'+num).parentNode; 
	enode = pnode.removeChild(getEl('edit-'+num));
	var cancelImg = new Image();
	cancelImg.id = 'edit-'+num
	cancelImg.src = "../energie/images/cancl_16.gif";
	cancelImg.style.borderWidth = "0";
	cancelImg.alt = "Cancel";
	cancelImg.onclick = function(){ cancelFolder(num, delID) };
	pnode.appendChild( cancelImg );

	pnode = getEl('name-'+num); 
	pnode.onclick = function () {return true} ;
	clearDiv(pnode);
	var newInput = document.createElement("input");
	newInput.type = "text";
	newInput.size = '50';
	newInput.id = 'newname-'+num;
	newInput.value = name;
	newInput.name = 'newFilename';
	newInput.onkeypress = checkEnter;
	pnode.appendChild( newInput );

	formAction = document.filename.action;
	document.filename.action = '';
  }

  function saveFolder(path,fname,num,fileType,delID) {
    var domDoc = createDOMDoc();
    var root = domDoc.createElement('ROOT');
    domDoc.appendChild(root);

    var p = domDoc.createElement('PATH');
    p.appendChild(domDoc.createTextNode(path));
    root.appendChild(p);

    var d = domDoc.createElement('DELEGATE_ID');
    d.appendChild(domDoc.createTextNode(delID));
    root.appendChild(d);

    var of = domDoc.createElement('ORIGINAL_FILENAME');
    of.appendChild(domDoc.createTextNode(fname));
    root.appendChild(of);

	var nfile = '';
	nfile += getEl('newname-'+num).value;
	if(ext) {
		nfile += '.'+ext;
	}

	var i = 0;
	var filenameArr = new Array();
	filenameArr = nfile.split('&');
	nfile = '';	
	for( i = 0; i < filenameArr.length; i++ ) {
		nfile += filenameArr[i];
	}
	nfile = nfile.replace(/^\s*|\s*$/g,"");
	getEl('name-'+num).fname2 = nfile;
	
	var nf = domDoc.createElement('NEW_FILENAME');
	nf.appendChild(domDoc.createTextNode(nfile));
	root.appendChild(nf);

	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', 'inboxMove.php?rename=1', true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(domToString(domDoc));
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseText) {
				clearDiv(getEl('errMsg'));
				cancelFolder(num, delID);
				if(xmlhttp.responseText == '1') {
					getEl('errMsg').appendChild(document.createTextNode('Name successfully changed')); 
					getEl('name-'+num).firstChild.data = nfile;
 					getEl('name-'+num).fname = nfile;
					getEl('edit-'+num).onclick = function() {editFolder(URL,path,nfile,num,fileType, delID)};
					if(delID > 0) {
						getEl('check-'+num).firstChild.value = delID;
						getEl('check-'+num).parentNode.id = 'delFolder:'+delID;
						getEl('delFolder:'+delID).onmouseover = function() {mOver(delID, 'delFolder:')};
						getEl('delFolder:'+delID).onmouseout = function() {mOut(delID, 'delFolder:')};
					} else {
						getEl('check-'+num).firstChild.value = nfile;
						getEl('check-'+num).parentNode.id = 'folder:'+nfile;
						getEl('folder:'+nfile).onmouseover = function() {mOver(nfile)};
						getEl('folder:'+nfile).onmouseout = function() {mOut(nfile)};
					}
				} else {
					getEl('errMsg').appendChild(document.createTextNode(xmlhttp.responseText)); 
				}
					
				if(fileType == 'folder') {
					URL = URL.replace('folder='+encodeURI(fname),'folder='+encodeURI(nfile));
					getEl('file-'+num).parentNode.onclick = function() {location = URL};
					getEl('name-'+num).onclick = function() {location = URL};
					getEl('date-'+num).onclick = function() {location = URL};
					getEl('info-'+num).onclick = function() {location = URL};
				} else if(delID > 0) {
					getEl('file-'+num).parentNode.onclick = function() {	selectRow(delID, 'delFolder');
																			viewDelegatedFile(encodeURI(nfile), delID)};
					getEl('name-'+num).onclick = function() {	selectRow(delID, 'delFolder');
																			viewDelegatedFile(encodeURI(nfile), delID)};
					getEl('date-'+num).onclick = function() {	selectRow(delID, 'delFolder');
																viewDelegatedFile(encodeURI(nfile), delID)};
					getEl('info-'+num).onclick = function() {	selectRow(delID, 'delFolder');
																viewDelegatedFile(encodeURI(nfile), delID)};
				} else {
					getEl('file-'+num).parentNode.onclick = function() {	selectRow(nfile);
																			viewFile(encodeURI(nfile))};
					getEl('name-'+num).onclick = openFile;					
					getEl('date-'+num).onclick = function() {	selectRow(nfile);
																viewFile(encodeURI(nfile))};
					getEl('info-'+num).onclick = function() {	selectRow(nfile);
																viewFile(encodeURI(nfile))};
				}
			}
		}
	};
	num_id=0;
  }

  function openFile() {
	selectRow(this.fname2);
	viewFile(encodeURI(this.fname2));
  }

  function cancelFolder(num, delID) {
	var pnode = getEl('file-'+num).parentNode; 
	pnode.removeChild(getEl('file-'+num));
	pnode.appendChild(fnode);

	pnode = getEl('name-'+num); 
	clearDiv(pnode);
	pnode.appendChild(document.createTextNode(pnode.fname));

	if(ftype == 'folder') {
		fnode.parentNode.onclick = function() {location = URL};			
		pnode.onclick = function() {location = URL};
	} else if(delID > 0) {
		fnode.parentNode.onclick = function() {	selectRow(delID, 'delFolder');
												viewDelegatedFile(encodeURI(filename), delID)};			
		pnode.onclick = function() {	selectRow(delID, 'delFolder');
												viewDelegatedFile(encodeURI(filename), delID)};			
	} else {
		fnode.parentNode.onclick = function() {	selectRow(filename);
												viewFile(encodeURI(filename))};			
		pnode.onclick = function() {	selectRow(filename);
												viewFile(encodeURI(filename))};			
	}
	
	pnode = getEl('edit-'+num).parentNode; 
	pnode.removeChild(getEl('edit-'+num));
	pnode.appendChild(enode);
		
	document.filename.action = formAction;
  }

  function checkEnter(evt) {
    evt = (evt) ? evt : event;
    var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;

	var pool = "$pool"; 
    if (charCode == 13 || charCode == 3) {
        saveFolder(fpath,filename,num_id,ftype,delID);
		return false;
	} else if(pool.indexOf(String.fromCharCode(charCode)) == -1 
			&& charCode != 46 && charCode != 8 && (charCode != 37) && (charCode != 39)) {
		return false;	
	}
	return true;
  }

  function changeResults() {
  	var myUrl = document.getElementById('displayResults').value;
  	myUrl += "&currItem=" + selRow;
	window.location =  myUrl;
  }

  function removeInboxDelegation() {
    var formEl = document.filename;
	var i = 1;
	while(el = getEl('delFileCheck:'+i)) {
		if(el.checked == true && el.name != 'selectdelfolder') {
			document.filename.action += "&deleteInboxDelegation=1"; 
			document.filename.submit();
			return;
		}
		i++;
	}
	
	clearDiv(getEl('errMsg'));
	getEl('errMsg').appendChild(document.createTextNode('No files have been selected'));
  }

  function messTimer() {
	var t = setTimeout("removeInboxMess()", 7000);
  }

  function removeInboxMess() {
	var errMsg = document.getElementById('errMsg');
	while(errMsg.hasChildNodes()) {
		errMsg.removeChild(errMsg.firstChild);
	}
  }

  function maximizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','260,*');
	parent.searchPanel.getEl('minView').style.visibility='visible';
	getEl('maxView').style.visibility='hidden';
  }

function minimizeView() {
	parent.document.getElementById('afterMenu').setAttribute('cols','0,*');
	parent.mainFrame.getEl('maxView').style.visibility='visible';
}
function toggleInboxView(toggle) {
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', 'inboxMove.php?inboxView='+toggle, true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseText) {
				if(toggle) {	
					minimizeView();
				} else {
					maximizeView();
				}
			}
		}
	};
}

 </script>
</head>
<body onload="reInitialize()" style="text-align: center;margin:0px">
	<div style="text-align:left;height:20px">
		<img src="../images/right.GIF" 
			id="maxView"
			style="cursor:pointer;vertical-align:middle" 
			title="Maximize" 
			alt="Maximize"
			onclick="toggleInboxView(0)"
		/>
	</div>
 <div style='width:100%'>
  <table style='width:100%'>
   <tr style='white-space:nowrap'>
    <td onclick='viewUpload()'>
	 <div style='white-space:nowrap;padding-left:5px;padding-right:5px;width:75px' class='uploadBtn'>
      <span style='white-space:nowrap;font-size:9pt;padding-left:5px;padding-right:5px'>Upload Files</span>
	 </div>
	</td>
    <td style="width: 70%; text-align: center">
<table class="lnk_black" style="border: 0; margin-left: auto; margin-right: auto;font-size:9pt" cellspacing="1" cellpadding="0">
ENERGIE;
	if(!check_enable('lite', $user->db_name)) {
		echo '<tr>';
		if( $type != 1 ) {//public
			echo<<<ENERGIE
  <td align="center" onclick="window.location='inbox1.php'">
	<span class="headerImgSelect">Public</span>
  </td>
  <td align="center" onclick="window.location='inbox1.php?type=1'">
	<span class="headerImg">Personal</span>
  </td>
ENERGIE;
		} else {//personal
			echo<<<ENERGIE
  <td align="center" onclick="window.location='inbox1.php'">
	<span class="headerImg">Public</span>
  </td>
  <td align="center" onclick="window.location='inbox1.php?type=1'">
	<span class="headerImgSelect">Personal</span>
  </td>
ENERGIE;
		}
		echo<<<ENERGIE
  <td onclick="window.location='../delegation/viewDelegation.php'">
	<span class="headerImg">Delegated</span>
  </td>
 </tr>
ENERGIE;
	}
	echo<<<ENERGIE
</table>
</td>
    <td style="width: 25%; text-align: right">
     <select id='displayResults' name='displayResults' onchange='changeResults()' style='font-size:9pt'>
ENERGIE;
	if( $type == 1) {
		$newURL = $URL."&amp;folder=".$myFolder;
	} else {
		$newURL = $URL."?folder=".$myFolder;
	}
	$searchRes = array(10,25,50,75,100);
	foreach($searchRes AS $num) {
		if($res == $num) {
			echo "<option selected='selected' value='$newURL&amp;res=$num'>$num</option>\n";
		} else {
			echo "<option value='$newURL&amp;res=$num'>$num</option>\n";
		}
	}
echo<<<ENERGIE
     </select>
    </td>
   </tr>
   <tr>
    <td colspan='3' style="width: 100%; text-align: center">
ENERGIE;
    // This information is for displaying the inbox paged
	//$userSettings->set('results_per_page', $_GET['res']);
	if(!$res) {
		$pagedisplay = 25;
	} else {
		$pagedisplay = $res;
	}

	//$pagedisplay = 25 ; // number of files to display per page
	$lastpage = ceil( sizeof( $completelist ) / $pagedisplay) ;
	if(!isset($page) || $page < 1)
		$page = 1;
	else if ($page > $lastpage){
		$page = $lastpage;
	}

	$total = sizeof( $completelist ) - ( ( $page - 1 ) * $pagedisplay );
	$pagestart = $pagedisplay * ($page-1) ; // page is one off (start at 1)
	if( $total > $pagedisplay )
		$pagefinish = $pagestart + $pagedisplay;
	else
		$pagefinish = $pagestart + $total;
	// -- Display Arrows for pages if needed --
	if( $type == 1 && $myFolder) {
		$newURL = $URL."&amp;folder=".$myFolder;
	} elseif($myFolder) {
		$newURL = $URL."?folder=".$myFolder;
	}
	$URL = $newURL;
    if($lastpage > 1) {
        $pageb = $page - 1;
        $pagef = $page + 1;
echo<<<ARROWS
    <form method="POST" action="$newURL">
    <table style="border: 0;font-size:9pt"><tr>
    <td>
        <a href="$newURL&amp;page=0">
        <img src="../energie/images/begin_button.gif" style="border: 0" alt="Beginning" title=""></a>
                                                                                                                             
        <a href="$newURL&amp;page=$pageb">
        <img src="../energie/images/back_button.gif" style="border: 0" alt="Back" title=""></a>
    </td>
    <td>
        Page
    <td>
        <input type="text" onKeyPress="return allowDigi(event);" id="page" name="page" value="$page" size="2" style="font-size:9pt">
    </td>
    <td>
         of $lastpage
    </td>
    <td>
        <a href="$newURL&amp;page=$pagef">
        <img src="../energie/images/next_button.gif" style="border: 0" alt="Next" title=""></a>
                                                                                                                             
        <a href="$newURL&amp;page=$lastpage">
        <img src="../energie/images/end_button.gif" style="border: 0" alt="End" title=""></a>
    </td>
    </tr></table>
    </form>
ARROWS;
    }
echo<<<ENERGIE
    </td>
   </tr>
   </table>
   <table style="width: 100%">
   <tr>
    <td style='width:50%; text-align: left; white-space:nowrap;font-size:9pt'>
ENERGIE;
	$switchUserUrl = "inbox1.php?type=$myType";
	if( $type == 1 && sizeof($userList) > 1) {
		echo "Switch To: <select style='margin:0;padding:0;font-size:9pt' id='otherPersonal' name='otherUsers' onchange='switchUser()'>\n";
		foreach($userList AS $uname) {
			if($uname == $username) {
				echo "<option selected value='$switchUserUrl&amp;username=$uname'>$uname</option>\n";
			} else {
				echo "<option value='$switchUserUrl&amp;username=$uname'>$uname</option>\n";
			}
		}
		echo "</select>\n";
	}

	if($type == 1 && sizeof($completeUserList)) {
        echo "Move To: <select style='margin:0;padding:0;font-size:9pt' id='movePersonal' name='completeUserList'>\n";
        foreach($completeUserList AS $uname) {
            if($uname == $username) {
                echo "<option selected value='$uname'>$uname</option>\n";
            } else {
                echo "<option value='$uname'>$uname</option>\n";
            }
        }
        echo "</select>";
        echo "<input type='button' name='movefiles' value='Move' onclick='moveInboxFiles()' style='font-size:9pt'>";
    }
    	if ($showNew) {
		echo '<input style="vertical-align:middle" type="checkbox" ' .
		'onchange="checkShowNew()" checked id="showNew">' .
		'<span>Open Documents In New Window</span>';
	} else {
		echo '<input style="vertical-align:middle" type="checkbox" ' .
		'onchange="checkShowNew()" id="showNew">' .
		'<span>Open Documents In New Window</span>';
	}
	$deletePostUrl = "inbox1.php?type=$myType&amp;folder=$myFolder&amp;page=$page&amp;delegateID=$delegateID";
	if(isset($_GET['username'])) {
		$deletePostUrl .= "&amp;username=".$_GET['username'];
	}
echo<<<ENERGIE
	</td>
    <td id='errMsg' align='left' class='error'>$mess</td>
    <td align='right'>
ENERGIE;
	if($personal) {
		$delSetting = 'deletePersonalInbox';
	} else {
		$delSetting = 'deletePublicInbox';
	}
	$canDel = '';
	$delInbox = $glbSettings->get($delSetting);
	if($delInbox === '1')
		$canDel = true;
		
	//get user settings for whether or not to display delete cabinets
	$uDelInbox = $userSettings->get($delSetting);
	if($uDelInbox === '1') {
		$canDel = true;
	} elseif($uDelInbox === '0') {
		$canDel = false;
	}
	if($canDel === '') {
		$canDel = false;
	}
	if($canDel) {
    	echo '<input onclick="askUser()" type="button" name="delete" value="Delete Selected" style="font-size:9pt">';
	}
	if($myType && !check_enable('lite', $user->db_name)) {
		echo '<input type="button" value="Delegate" name="add" onclick="toggleDelegateDiv()" style="font-size:9pt">';
	}
	echo<<<ENERGIE
</td>
   </tr>
  </table>
 </div>

<div id="addNewFolderDiv" class="addNewFolderDiv">
 <iframe id="addNewFolder" class="addNewFolder" src="../lib/IEByPass.htm"></iframe>
</div>
<form name="filename" method="POST" action="$deletePostUrl">
<div id="addNewActionDiv" class="addNewActionDiv">
ENERGIE;
	displayInboxDelegation($user,$completeUserList);		
echo<<<ENERGIE
</div>
<div id="addNewDocumentDiv" class="addNewActionDiv">
ENERGIE;
	displayAddDocument();
echo<<<ENERGIE
</div>
 <div style="clear:both; font-size: 0px; height: 0px">&nbsp;</div>
<div style="margin-left: auto; margin-right: auto">
 <table style="width: 100%; border: 0" cellspacing="1" cellpadding="0" class="lnk_black" style="font-size:9pt">
  <tr class="tableheads" style="font-size:9pt">
   <td style="width: 2%"><b>&nbsp;</b></td>
   <td style="width: 2%"><b>Edit</b></td>
   <td style="vertical-align: top; width:  2%; white-space: nowrap"><b><a>
ENERGIE;
                                                                                                                             
$checkbox = $trans['Select'];
$del = $trans['Delete'];
$name = $trans['Name'];
$dateCreated = $trans['Date Created'];
$info = $trans['Info'];
$inboxEmpty = $trans['inboxEmpty'];
                                                                                                                             
echo "<input type=\"checkbox\" name=\"selectfolder\" onclick=\"selectAll(this)\" title=\"$checkbox\"></a></b></td>";
if( substr_count( $URL, "?" ) > 0 ) { 
echo<<<ENERGIE
  <td style="cursor:pointer; width: 35%" onclick="window.location='$URL&amp;page=$page&amp;sortField=name'"><b>$name</b></td>
<td style="cursor:pointer; text-align: center; width: 30%" onclick="window.location='$URL&amp;page=$page&amp;sortField=realTime'"><b>$dateCreated</b></td>
<td style="cursor:pointer; text-align: center; width: 15%" onclick="window.location='$URL&amp;page=$page&amp;sortField=size'"><b>$info</b></td>
</tr>
ENERGIE;
} else {
echo<<<ENERGIE
  <td style="cursor:pointer; width: 35%" onclick="window.location='$URL?page=$page&amp;sortField=name'"><b>$name</b></td>
<td style="cursor:pointer; width: 30%; text-align: center" onclick="window.location='$URL?page=$page&amp;sortField=realTime'"><b>$dateCreated</b></td>
<td style="cursor:pointer; text-align: center; width: 15%" onclick="window.location='$URL?page=$page&amp;sortField=size'"><b>$info</b></td>
</tr>
ENERGIE;
}
	if( $myFolder) {
		echo "<tr onclick=\"window.location='$parentURL'\" bgcolor=\"#ebebeb\" id=\"folder:$parentURL\"";
		echo " onmouseover=\"mOver('$parentURL')\" onmouseout=\"mOut('$parentURL')\">\n";
		echo " <td colspan=\"6\" style=\"text-align: left\">\n";
		echo "  &nbsp;<img style=\"border: 0\" src=\"../energie/images/up.gif\" alt=\"Parent Folder\" title=\"\">\n";
		if(!empty($_SESSION['ibxFolder'])) {
			echo "<span style=\"font-size: 9pt; font-style: italic\">&nbsp;Current Folder: {$_SESSION['ibxFolder']}</span>";
		}
		echo " </td>\n";
		echo "</tr>\n";
	}

	$i = 0; //Initialize the variable for later use
	$j = 1;
    if( sizeof( $completelist ) > 0 ) {
		// See if we need folders on this page to display and stay below page limit
		if($pagestart < sizeof( $completelist ) ) {
			for($i=$pagestart;$i<$pagefinish;$i++ ) {
				$realName = $completelist[$i]['name'];
				$folderName = $completelist[$i]['urlName'];
				$time = $completelist[$i]['time'];
				$fileInfo = $completelist[$i]['size'];
				$fileType = $completelist[$i]['type'];

				if( $type == 1 && $completelist[$i]['type'] == 'folder' ) {
					$newURL = $parentURL."&amp;folder=$folderName";
				} elseif( $type == 0 && $completelist[$i]['type'] == 'folder' ) {
					$newURL = $parentURL."?folder=$folderName";
				} else {
					$newURL = $parentURL;
				}

				$folderRealName = $realName;
				$realName = addslashes($realName);
				echo "<tr onmouseover=\"mOver('$realName');\" onmouseout=\"mOut('$realName');\" id=\"folder:$folderRealName\" style=\"background-color: #ebebeb\">\n";
				if( $fileType == 'folder' ) { //is_dir( $path."/".$realName ) ) 
					echo " <td align='center' onclick=\"selectRow('$realName');window.location='$newURL'\">\n";
					echo "   <img id='file-$j' style=\"border: 0\" ";
					echo "src='../images/foldr_16.gif' alt=\"Folder\" title=\"\">\n";
					echo " </td>\n";
				} else {
					echo "<td align='center' onclick=\"selectRow('$realName');viewFile('$realName',$j)\">\n";
					echo "<img id='file-$j' style=\"border: 0\" src='../images/docs_16.gif'  alt=\"File\" title=\"\">\n";
					echo "</td>\n";
					if ($theRow==$realName){
						echo "<script type='text/javascript'>selectRow('$realName');viewFile('$realName',$j);</script>";
					}
				}
				//adding row in the table to be able to edit a folder
				echo "<td align='center'>\n";
				echo "<img id ='edit-$j' style=\"height: 16px; border: 0\" src='../energie/images/file_edit_16.gif' alt=\"Edit Folder\" title=\"\" ";
				echo "onclick='editFolder(\"$newURL\",\"$path\",\"$realName\",$j,\"$fileType\")'>\n";
				echo "</td>\n";

				echo " <td id='check-$j' align=\"center\">";
		echo "<input type=\"checkbox\" id=\"fileCheck:$j\" name=\"check1[]\" value=\"$folderName\" realName=\"$folderRealName\">\n";
		echo "</td>\n";
				if( $fileType == 'folder' ) {
					echo "<td id=\"name-$j\" style=\"text-align: left;white-space:nowrap\" onclick=\"selectRow('$realName');window.location='$newURL'\">$folderRealName</td>\n";
					echo "<td id='date-$j' style=\"text-align: left;white-space:nowrap\" onclick=\"selectRow('$realName');window.location='$newURL'\">$time</td>\n";
					echo "<td id='info-$j' style=\"white-space:nowrap\" onclick=\"selectRow('$realName');window.location='$newURL'\">$fileInfo</td>\n";
				} else {
					echo "<td id=\"name-$j\" style=\"text-align: left;white-space:nowrap\" onclick=\"selectRow('$realName');viewFile('$folderName',$j)\">$folderRealName</td>\n";
					echo "<td id='date-$j' style=\"text-align: left;white-space:nowrap\" onclick=\"selectRow('$realName');viewFile('$folderName',$j)\">$time</td>\n";
					echo "<td id='info-$j' style=\"white-space:nowrap\" onclick=\"selectRow('$realName');viewFile('$folderName',$j)\">$fileInfo</td>\n";
				}
				echo "</tr>\n";
				$j++;
			}
		}
	} else {
		if( $myFolder ) {
			echo "<tr bgcolor=\"#ebebeb\">\n";
			echo "<td colspan=\"6\">This folder is currently empty</td>\n";
			echo "</tr>\n";
		} elseif( $type == 1 ) {
			echo "<tr bgcolor=\"#ebebeb\">\n";
			echo "<td colspan=\"6\">Your personal inbox is currently empty</td>\n";
			echo "</tr>\n";
		} else {
			echo "<tr bgcolor=\"#ebebeb\">\n";
			echo "<td colspan=\"6\">Inbox is currently empty</td>\n";
			echo "</tr>\n";
		}		
	}
	echo "</table>\n";
echo<<<ENERGIE
</form>
</div>
<script type="text/javascript">
<!--
	SET_DHTML(CURSOR_MOVE);
if(selRow && getEl('folder:' + selRow)) {
	getEl('folder:' + selRow).style.backgroundColor = '#8799e0';
}
//-->
</script>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
