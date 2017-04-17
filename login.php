<?php
//$Id: login.php 15016 2013-06-21 15:58:09Z cz $

require_once 'loginDisp.php';
require_once 'check_login.php';
require_once 'loginFuncs.php';
require_once 'lib/ldap.php';
require_once 'lib/filter.php';
require_once 'lib/licenseFuncs.php';
require_once 'modules/modules.php';

if (!empty ($DEFS['CUSTOM_LIB'])) {
	require_once $DEFS['CUSTOM_LIB'];
}

//creates an array for the URL to be passed
$getArr = array();
if(isset($_GET['autosearch'])) {
	$getArr[] = "autosearch={$_GET['autosearch']}";
	if(isset($_GET['cabinet'])) {
		$getArr[] = "cabinet={$_GET['cabinet']}";
	}
} else if(isset($_GET['MASSearch'])) {
	$getArr[] = "MASSearch={$_GET['MASSearch']}";
	if( isset($_GET['cabinet'] ) ){
		$getArr[] = "cabinet={$_GET['cabinet']}";
	}
} else if(isset($_GET['link'])) {
	$getArr[] = "department={$_GET['department']}";
	$getArr[] = "cab={$_GET['cab']}";
	$getArr[] = "doc_id={$_GET['doc_id']}";
	$getArr[] = "fileID={$_GET['fileID']}";
	$getArr[] = "link={$_GET['link']}";
	$getArr[] = "wf={$_GET['wf']}";
	if (isset ($_GET['todoID'])) {
		$getArr[] = "todoID={$_GET['todoID']}";
	}
} else if(isset($_GET['legint'])) {
	$getArr[] = "legint={$_GET['legint']}";
	$data = file_get_contents('php://input');

	if(isset($_GET['NewUiPrintBC']))
	{
		$getArr[] = "NewUiPrintBC={$_GET['NewUiPrintBC']}";
		//error_log("login.php - PrintBC is set: ".$_GET['NewUiPrintBC'] );
		
		$xmlStr_urlenc = substr($data , strpos($data, "gotoPrintBarcodePage=")+ strlen("gotoPrintBarcodePage="));
		$xmlStr_urlenc2 = substr($xmlStr_urlenc , 0, strpos($xmlStr_urlenc, "%3C%2FsearchDocutron%3E")+ strlen("%3C%2FsearchDocutron%3E"));
		//error_log("NewUiPrintBC xmlStr_urlenc posted to login.php: ".$xmlStr_urlenc);

		$xmlstr = urldecode($xmlStr_urlenc2);
		if(empty($xmlstr)) {
			return;
		}
		//error_log("NewUiPrintBC xmlStr posted to login.php: ".$xmlstr );

		//cz 10-13
	/*	$getArr[] = "NewUiPrintBC={$_GET['NewUiPrintBC']}";
		//error_log("login.php - PrintBC is set: ".$_GET['NewUiPrintBC'] );
		
		$xmlStr_urlenc = substr($data , strpos($data, "gotoPrintBarcodePage=")+ strlen("gotoPrintBarcodePage="));
		$xmlStr_urlenc2 = substr($xmlStr_urlenc , 0, strpos($xmlStr_urlenc, "%26lt%3B%2FsearchDocutron%26gt%3B")+ strlen("%26lt%3B%2FsearchDocutron%26gt%3B"));
		error_log("NewUiPrintBC xmlStr_urlenc posted to login.php: ".$xmlStr_urlenc);

		$xmlstr_urldec = urldecode($xmlStr_urlenc2);
		error_log("NewUiPrintBC xmlstr_urldec posted to login.php: ".$xmlstr_urldec );
		$xmlstr = html_entity_decode($xmlstr_urldec);
		if(empty($xmlstr)) {
			error_log("NewUiPrintBC xmlStr empty.");
			die();
			//return;
		}
		error_log("NewUiPrintBC xmlStr posted to login.php: ".$xmlstr );	*/
		//cz 10-13
				
		//$decJson = json_decode($data, true);
		////error_log("login.php - decJson: ".print_r($decJson, true) );
		//$legUser = $decJson["username"];
		//$legPasswd = $decJson["password"];
		//$department = $decJson["department"];
		//$cab = $decJson["cabinet"];
		//$docIDs = $decJson["docIDs"];

		//$getArr["docIDs"] = $docIDs;
	}
	else if(isset($_GET['NewUI']) && $_GET['NewUI']==1)
	{
		$getArr[] = "NewUI={$_GET['NewUI']}";

		$xmlStr_urlenc = substr($data , strpos($data, "gotoAdminPage=")+ strlen("gotoAdminPage="));
		//error_log("NewUI xmlStr_urlenc posted to login.php: ".$xmlStr_urlenc);

		$xmlstr = urldecode($xmlStr_urlenc);
		if(empty($xmlstr)) {
			return;
		}
				
		//$legUser = "admin";
		//$legPasswd = "21232f297a57a5a743894a0e4a801fc3";
		//$department = "client_files";
		//$cab = "Legacy_Integrator";
	}
	else
	{
		$xmlstr = urldecode($data);
	}

 	$searchArr = array();
 	if (substr (PHP_VERSION, 0, 1) == '4') {
 		$domDoc = domxml_open_mem ($xmlStr);
		$username = $domDoc->get_elements_by_tagname('username');
		$legUser = strtolower($username[0]->get_content());
		$password = $domDoc->get_elements_by_tagname('password');
		$legPasswd = $password[0]->get_content();
		
		$department = $domDoc->get_elements_by_tagname('department');
		if($department) {
			$department = $department[0]->get_content();
		} else {
 			unset ($department);
		}
	
		$cab = $domDoc->get_elements_by_tagname('cabinet');
		if(count($cab)) {
			$cab = $cab[0]->get_content();	
		}
	
		$term = $domDoc->get_elements_by_tagname('term');
		foreach($term as $index) {
			$indexName = strtolower($index->get_attribute('index'));
			$searchArr[$indexName] = '"'.trim($index->get_attribute('value')).'"';
		}

		$fn = $domDoc->get_elements_by_tagname('filename');
		if(count($fn)) {
			$searchArr['file'] = $fn[0]->get_content();	
		}
	} else {
		if(!empty($xmlstr))
		{
 		$domDoc = new DOMDocument ();

		$domDoc->loadXML ($xmlstr);
		//$domDoc->loadXML ('<searchDocutron></searchDocutron>');

		$username = $domDoc->getElementsByTagName('username');
		$tmp = $username->item(0);
		$legUser = strtolower($tmp->nodeValue);
		$password = $domDoc->getElementsByTagName('password');
		$tmp = $password->item(0);
		$legPasswd = $tmp->nodeValue;
		
		$department = $domDoc->getElementsByTagName('department');
		if($department) {
			$tmp = $department->item(0);
			$department = $tmp->nodeValue;
		} else {
 			unset ($department);
		}
	
		$cab = $domDoc->getElementsByTagName('cabinet');
		if($cab->length > 0) {
			$tmp = $cab->item(0);
			$cab = $tmp->nodeValue;
		}
		//cz 091211
		else {
			unset ($cab);
		}
	
		$docIDs = $domDoc->getElementsByTagName('docIDs');
		if($docIDs->length > 0) {
			$tmp = $docIDs->item(0);
			$docIDs = $tmp->nodeValue;
		}
		else {
			unset ($docIDs);
		}
		
		//cz 10-05
		$subFolders = $domDoc->getElementsByTagName('subFolders');
		if($subFolders->length > 0) {
			$tmp = $subFolders->item(0);
			$subFolders = $tmp->nodeValue;
		}
		else {
			unset ($subFolders);
		}
		//fa 2013-06-18
		$tabID = $domDoc->getElementsByTagName('tabID');
		if($tabID->length > 0) {
			$tmp = $tabID->item(0);
			$tabID = $tmp->nodeValue;
		}
		else {
			unset ($tabID);
		}

		$term = $domDoc->getElementsByTagName('term');
		for ($i = 0; $i < $term->length; $i++) {
			$index = $term->item ($i);
			$indexName = strtolower($index->getAttribute('index'));
			$searchArr[$indexName] = '"'.trim($index->getAttribute('value')).'"';
		}

		//cz 06-08-11
		//$reqPage = $domDoc->getElementsByTagName('reqPage');
		//if($reqPage->length > 0) {
		//	$tmp = $reqPage->item(0);
		//	$getArr['reqPage'] = $tmp->nodeValue;
		//	error_log("reqPage: ".$getArr['reqPage']);
		//}	

		$fn = $domDoc->getElementsByTagName('filename');
		if($fn->length > 0) {
			$tmp = $fn->item(0);
			$searchArr['file'] = $tmp->nodeValue;	
		}
		}else{
			error_log( "no xml string passed to login" );
		}
 	}

	if (function_exists ('customIntegratorFix')) {
		customIntegratorFix($department, $cab, $searchArr);
	}
 	$getArr[] = "department={$department}";

 	//cz 091211
	if(isset($cab) && $cab != '') {
		//error_log("login: cab - ".$cab);
 		$getArr[] = "cab={$cab}";
	}
	if(isset($docIDs) && $docIDs != '') {
		//error_log("login: docIDs - ".$docIDs);
 		$getArr[] = "docIDs={$docIDs}";
	}
	
	//error_log("login: getArr: ".print_r($getArr, true));

	if(isset($_GET['NewUiPrintBC'])) 
	{		
		//$headerPrintBC = 'Location: barcodeTestfile1.php';
		$headerPrintBC = 'Location: barcode/getBarcode.php?NewUiPrintBC='.$_GET['NewUiPrintBC'].'&dept='.$department.'&cabinet='.$cab.'&docIDs='.$docIDs;
		if(isset($subFolders))
		{
			$headerPrintBC= $headerPrintBC.'&subFolders='.$subFolders;
		}
		if(isset($tabID))
		{
			$headerPrintBC= $headerPrintBC.'&tabID='.$tabID;
		}
	}
 
}
//this section is used to redirect if already logged in
if($logged_in and $user->username) {

	if(isset($_GET['NewUiPrintBC'])) 
	{		
		$header = $headerPrintBC;
	}
	else if(isset($_GET['NewUI']))
	{
		if($_GET['NewUI']==1) {
			//error_log("login.php already logged in. going to adminPage.php.");
			$header = 'Location: energie/adminPage.php?NewUI='.$_GET['NewUI'].'&department='.$department;
		}else {
			die();
		}
	}
	else
	{
		$header = 'Location: energie/energie.php';
		if(!empty($getArr)) {
			$header .= "?".implode("&",$getArr);
		}
	
		if(!empty($searchArr)) {
			$_SESSION['integrationSearch'] = $searchArr;
		} else {
			$_SESSION['integrationSearch'] = array ();
		}
	}
	setSessionUser($user);
	//error_log("login.php already logged in. going to ".$header);
	die(header($header));
}

$settings = new GblStt('client_files', $db_doc);
$language = (isset($_POST['lang'])) ? $_POST['lang'] : $settings->get('i18n');

$trans = getLang($db_doc, strtolower($language));
$_SESSION['lang'] = $trans;


//if the form has been submitted
if(!isset($_GET['newLogin']) and (isset($_POST['submitted']) or
	isset($_GET['autosearch']) or isset($_GET['legint']))) {
	
	$newUsername = (isset($_POST['uname']) ? trim(strtolower($_POST['uname'])) : NULL);
	$newPassword = (isset($_POST['passwd']) ? $_POST['passwd'] : NULL);
	
	$decryptPass = false;
   	//autosearch expected to be base64_encoded
	if(isset( $_GET['autosearch'])) {
		$temp = str_replace("\x00","",base64_decode($_GET['autosearch']));
		$temp = explode(",", $temp);
		if(count($temp) == 3) {
			$newUsername = $temp[0];
			$newPassword = strtolower($temp[1]);//this won't work if you have capital letters
		} else if(isset($user)) {
			$newUsername = $user->username;
			$newPassword = $user->password;
		}
	} elseif(isset($_GET['legint'])) {
		$newUsername = $legUser;
		$newPassword = $legPasswd;
		$decryptPass = true;

		//cz 06-08-11
		//error_log("newUsername: ".$newUsername."; newPassword: ".$newPassword);
	}
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->get('username', $newUsername);
	if ($newPassword !== '' and !$DO_user->ldap_id) {
		if (!$decryptPass) {
			$newPassword = md5 ($newPassword);
		}
		$newPassword = strtolower ($newPassword);
	}
	$date = time();
	if(!$newUsername or !$newPassword) {
		$getArr[] = 'message=Missing Field';
		$auditMessage = "$newUsername Missing Field(s)";
		loginFailed($getArr,$auditMessage,$newUsername);
	} elseif(!$DO_user->db_list_id) {
		$getArr[] = 'message=Incorrect Username or Password';
		$auditMessage = "$newUsername Username does not exist";
		loginFailed($getArr,$auditMessage,$newUsername);
	} else if((!$DO_user->ldap_id and $newPassword != $DO_user->password) or
			($DO_user->ldap_id and !checkLDAPPassword ($db_doc,
			$DO_user->ldap_id, $newUsername, $newPassword, $decryptPass))) {
		$getArr[] = 'message=Incorrect Username or Password';
		$auditMessage = "$newUsername Typed Incorrect Username or Password";
		loginFailed($getArr,$auditMessage,$newUsername);
	} elseif(!$DO_user->defaultDept) {
		$getArr[] = 'message=No Department Access';
		$auditMessage = "$newUsername Tried To Log In With no Department Access";
		loginFailed($getArr,$auditMessage,$newUsername);
	} elseif($DO_user->guest == 1 && unixTimeStampFromSQLDateTime($DO_user->exp_time) <= $date) {
		//delete user settings
		$sttArr = new Usrsettings( $newUsername, $user->db_name  );
		$sttArr->removeKey($newUsername);
		//reassign users workflow to admin
		$updateArr = array('username'=>'admin');
		$whereArr = array('username'=> $newUsername);
		updateTableInfo($db_doc,'wf_todo',$updateArr,$whereArr);
		//delete user from user_security and user_polls
		$whereArr = array('username'=>$newUsername);
		deleteTableInfo($db_doc,'user_security',$whereArr);
		deleteTableInfo($db_doc,'user_polls',$whereArr);
		//move personal inbox files to admin's inbox

		foreach($DO_user->departments AS $dep => $p) {
			$db_dept = getDbObject($dep);
			
			//delete user from groups
			$uid = getTableInfo($db_dept,'access',array('uid'),array('username'=>$newUsername),'queryOne');				
			$whereArr = array('uid'=>(int)$uid);
			deleteTableInfo($db_dept,'users_in_group',$whereArr);
			
			//delete user from workflow lists
			$whereArr = array('username'=>$newUsername);
			deleteTableInfo($db_dept,'user_list',$whereArr);
			deleteTableInfo($db_dept,'access',$whereArr);

			$curInboxPath = $DEFS['DATA_DIR']."/".$dep."/personalInbox/".$newUsername;
			$destInboxPath = $DEFS['DATA_DIR']."/".$dep."/personalInbox/admin/".$newUsername;
			$inboxList = array();
			if( file_exists( $destInboxPath ) ) {
				$handle = opendir($curInboxPath);
				while (false !== ($file = readdir($handle))) {
					if($file != "." && $file != "..") {
						pad( $curInboxPath.$file."/", $user );
						$inboxList[] = $file;
					}
				}
				closedir($handle);
				
				foreach( $inboxList AS $fname ) {
					$newName = $fname;
					$i=1;
					while( file_exists( $destInboxPath."/".$newName) ) {		
						$newName = $i."-".$fname;
						$i++;
					}
					rename( $curInboxPath."/".$fname, $destInboxPath."/".$newName );
				}
				rmdir( $curInboxPath );
			} else {
				if( is_dir($curInboxPath) ) {
					@rename( $curInboxPath, $destInboxPath );
				}
			}
			$db_dept->disconnect ();
			
		}
		$DO_user->delete();

		$getArr[] = 'message=Guest Account Expired';
		$auditMessage = "$newUsername Deleted Guest Account Expired";
		loginFailed($getArr,$auditMessage,$newUsername);
	} else {
		$shared = "";
		//lock tables
		$wf=0;
		if(systemFullCheck($newUsername,$db_doc,$shared)) {
			$getArr[] = 'message=System is Full';
			$auditMessage = "$newUsername Not Logged In Because System Is Full";
			loginFailed($getArr,$auditMessage,$newUsername);
		} else {
			// Start creating the user object
			$user = new user();
			$user->username = $newUsername;
			$user->password = $newPassword;
			if(!empty($_GET['wf']) and !empty ($_GET['department'])) {
				$wfClientDB = getDbObject($_GET['department']);
				$accessRights = getTableInfo($wfClientDB,'access',array('access'),array('username'=>$user->username),'queryOne');
				$acc = unserialize(base64_decode($accessRights));
				if( $acc[$_GET['cab']] != "rw" ) {								
					$groupAccessList = queryAllGroupAccess($wfClientDB,$user->username);
					$hasAccess = false;
					foreach($groupAccessList AS $gInfo) {
						if($gInfo['real_name'] == $_GET['cab'] && $gInfo['access'] == "rw") {
							$hasAccess = true;
							break;
						}
					}

					if(!$hasAccess) {
						$whereArr = array('department'=>$_GET['department'],'username'=>$user->username);
						$todoList =  getTableInfo($db_doc,'wf_todo',array(),$whereArr,'queryAll',array('username'=>'ASC'));
						foreach( $todoList AS $wfTodo ) {
							$wf_document_id = $wfTodo['wf_document_id'];
							$whereArr = array('id'=>(int)$wf_document_id,'cab'=>$_GET['cab'],'doc_id'=>(int)$_GET['doc_id']);
							if(getTableInfo($wfClientDB,'wf_documents',array('COUNT(id)'),$whereArr,'queryOne') > 0) {
								$wf = 1;
								break;
							} else {
								$wf = 0;
							}
						}
					
						if( $wf == 0 ) {
							unset( $getArr );
							unset($_GET['department']);
						}
					}
				}
				$wfClientDB->disconnect ();
			}

			//if the todoID is passed, do not fill in the user access array
			if( isSet($_GET['todoID']) ) {
				$user->fillUser(null,$_GET['department']);
			} else {
				if (isset($_GET['department'])) {
					$user->fillUser($wf,$_GET['department']);
				} else {
					$user->fillUser($wf, null);
				}
			}
			$gblStt = new GblStt($user->db_name, $db_doc);
			$_SESSION['redirectLogin'] = $gblStt->get('redirectLogin');
			$defaultDB = $user->db_name;
			$user->createUserTempDir();
			if(isset($_GET['wf']) and $wf == 0) {
				$user->audit("ILLEGAL ACCESS","typed in an invalid link");
			}

			$whereArr = array('username'=>$user->username);
//			if($user->username!='admin' && getTableInfo($db_doc,'user_security',array('COUNT(uid)'),$whereArr,'queryOne') > 0) { //Multiadmin
			if(getTableInfo($db_doc,'user_security',array('COUNT(uid)'),$whereArr,'queryOne') > 0) {
				deleteTableInfo($db_doc,'user_security',$whereArr);
				deleteTableInfo($db_doc,'user_polls',$whereArr);
				$user->audit("logout", "duplicate user ".$user->username);
			}
			//updates last_login for user	
			$date = date('Y-m-d h:i:s');
			$DO_userOrig = DataObject::factory('users', $db_doc, $DO_user);
			$DO_user->last_login = $date;
			$DO_user->update($DO_userOrig);
			//inserts user entry in user_security
			$licenseType = ($shared == 'shared' ? 1 : 0);
			$licenseDept = ($licenseType == 1 ? $defaultDB : $shared);
			//insert new information into user security database
			$hash_length = 128;
			$hash_id = $user->getrandstring($hash_length);
			$user->hash_id = $hash_id;
			$insertArr = array(
				"hash_code"	=> $user->hash_id,
				"username"	=> $newUsername,
				"department"	=> $defaultDB
					  );
			$res = $db_doc->extended->autoExecute('user_security',$insertArr);
			dbErr($res);
			//inserts user entry in user_polls
			$insertArr = array(
				"username"		=> $user->username,
				"ptime"			=> time(),
				"department"		=> $licenseDept,
				"shared"		=> (int)$licenseType,
				"current_department"	=> $defaultDB
					  );
			$res = $db_doc->extended->autoExecute('user_polls',$insertArr);
			dbErr($res);

			//error_log("login.php: setSessionUser, user - ".$user->username);
			setSessionUser($user, false);

			if(isset($searchArr)) {
				$_SESSION['integrationSearch'] = $searchArr;
			} else {
				$_SESSION['integrationSearch'] = array ();
			}
			$remoteIPAddress = $_SERVER['REMOTE_ADDR'];
			$user->audit("login","logged into system, from $remoteIPAddress");

			//error_log("login.php: setSessionUser, user - ".$user->username);
			setSessionUser($user);

			if(isset($_GET['NewUiPrintBC'])) {				
				//cz 10-05-11
				//error_log("login.php going to ".$headerPrintBC);
				header($headerPrintBC);

			}
			else if(isset($_GET['NewUI']))
			{
				if( $_GET['NewUI']==1)	{
					//header("Location: energie/adminPage.php");
					//error_log("login.php going to adminPage.php. docId = ");
					//cz 09-12-11
					header('Location: energie/adminPage.php?NewUI='.$_GET['NewUI'].'&department='.$department);
				}
				else{
					echo("NewUI logged in.");
				}
			}
			else
			{
				$passHdr = (($getArr) ? '?'.implode('&',$getArr) : '');		
				//cz
				//error_log("Parameters passed to energoe.php:".$passHdr);

				header("Location: energie/energie.php$passHdr");
			}
			die();
		}
		//unlock tables
	}
} else {
	$securityLevel			= $trans['Security Level'];
	$secureSSL			= $trans['Secure SSL'];
	$Login				= $trans['Login'];
	$uName				= $trans['Username'];
	$passwd				= $trans['Password'];
	$langField			= $trans['Language'];
	$standard			= $trans['Standard'];

	$invalidLicense1 = "";
	$invalidLicense2 = "";
	if(!isValidLicense($db_doc)) {
		$invalidLicense1 = "System License Expired";
		$invalidLicense2 = "System is in Read Only Mode";
	}
	$demoMode = "";
	if( check_enable( 'demo', 'client_files' ) ){
		$demoMode = "This is a demo product.<br>Use of this product for production purposes is strictly prohibited.";
	}

	$loginDisp = new loginDisp($trans);
	//check for logout message or url message
	$message = '';
	if(isset($_GET['message']) and $_GET['message']) {
		$message = h($_GET['message']);
	} elseif(isset($_SESSION['logout_message'])) {
		$message = h($_SESSION['logout_message']);
		unset($_SESSION['logout_message']);
	}
	if($message) {
		if(isset($trans[$message])) {
			$message = h($trans[$message]);
		}
	}

	if ($settings->get('langlogin') == 'on') {
		$langInfo = getTableColumnInfo ($db_doc, 'language');
		$availLangs = array();
		for($i = 2; $i < sizeof($langInfo); $i++) {
			$availLangs[] = $langInfo[$i];
		}
		$loginDisp->setAvailLangs($availLangs, $language);
	}
	$loginDisp->setTrans($trans, $settings);
	if(isset($_COOKIE[session_name()])) {
	   setcookie(session_name(), '', time()-42000, '/');
	}
	$_SESSION = array();
	session_destroy();
		
	if($getArr) {
		$getString = '&'.implode('&',$getArr);
	} else {
		$getString = '';
	}
	$serverName = $_SERVER['SERVER_NAME'];
	if( $serverName == 'docmgmt.bizds.com' ){
		$whiteLabel = 'Biz Data Solutions';
	}elseif( $serverName == 'saas.syndicit.com' ){
		$whiteLabel = 'SyndicIT - Tools for Asset Performance';
	}else{
		$whiteLabel = $settings->get('whiteLabel');
		if (!$whiteLabel) {
			$whiteLabel = 'Treeno Software';
		}
		$whiteLabel = addslashes($whiteLabel);
	}
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Login Screen</title>
<link rel="stylesheet" type="text/css" href="lib/style.css" />
<style type="text/css">
	body, b, strong, p, td, ul, li, select, option {
		text-align: center;
		font-family: Tahoma, Verdana, Arial, sans-serif;
		font-size: 11.5px;
	}
	body, html {
		margin: 0;
		height: 100%;
	}
	select,option {
		text-align: left;
	}
	div.error {
		text-align: right;
	}

	.licerror {
		width		: 100%;
		text-align	: center;
		font-size	: 12pt;
		color		: red;
	}
</style>
<script type="text/javascript">
	var nextURL = '';
	var loc = top.location;
	var whiteLabel = '<?php echo $whiteLabel; ?>';
	if(top.location.protocol == 'https:') {
		nextURL += 'http://';
	} else {
		nextURL += 'https://';
	}
	nextURL += loc.hostname + loc.pathname + loc.search;

	function focusUName() {
		top.document.title = whiteLabel;
		document.loginForm.uname.focus();
	}

	function toggleURL() {
		top.location.href = nextURL;
	}
</script>
</head>
<body onload="focusUName()">
 <table id="mainTable">
  <tr class="headFoot">
   <td>
    <img class="headFoot" src="./images/topscaler.gif" alt="Top Image" title="" />
   </td>
  </tr>
	<?php if($invalidLicense1): ?>
	<tr>
	 <td>
		<div class="licerror"><?php echo $invalidLicense1; ?></div>
		<div class="licerror"><?php echo $invalidLicense2; ?></div>
	 </td>
	</tr> 
	<?php endif; ?>
  <tr>
   <td>
    <div class="centered">
     <table id="midTable">
      <tr>
       <td style="padding-bottom:50px">
        <img src="energie/showLogo.php" alt="<?php echo $whiteLabel; ?>" title="" />
       </td>
       <td>
        <img id="divider" src="./images/divider.gif" alt="Divider" title="" />
       </td>
       <td>
        <form name="loginForm" method="post" action="login.php?form=1<?php echo $getString?>" target="_parent">
         <?php $loginDisp->printLoginBox() ?>
        </form>
        <?php $loginDisp->printSecurity() ?>
        <div class="error">
        <?php echo $message; ?>
        </div>
       </td>
      </tr>
	  <?php if( $demoMode ): ?>
      <tr>
       <td colspan="3">
        <div class="licerror"><?php echo $demoMode; ?></div>
       </td>
      </tr>
      <?php endif; ?>
     </table>
    </div>
   </td>
  </tr>
  <tr class="headFoot">
   <td>
    <img src="./images/bottomscaler.gif" class="headFoot" alt="Bottom Image" title="" />
   </td>
  </tr>
 </table>
</body>
</html>
<?php
}
?>
