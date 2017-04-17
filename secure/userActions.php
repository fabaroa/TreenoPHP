<?php
// $Id: userActions.php 14280 2011-03-17 16:49:45Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../groups/groups.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/xmlObj.php';
include_once '../lib/mime.php';
include_once '../modules/modules.php';

function createUserCheck($enArr,$user) {
	global $DEFS;
	global $trans;
	$message = '';

	$newUsername = $enArr['username'];
	$passwd = $enArr['password'];
	$email = "";
	if(isSet($enArr['email'])) {
		$email = $enArr['email'];
	}
	$admin = $enArr['admin'];
	$guest = $enArr['guest'];
	$hours = $enArr['exp_time'];

	$newUsername = strip_tags($newUsername);
	$newUsername = strtolower($newUsername);
	$passwd = strip_tags($passwd);
	$db_object = $user->getDbObject();
	$linkDesc = '';

	$message = '';
	if (!get_magic_quotes_gpc())
        	$newUsername = addslashes($newUsername);
	if($user->invalidJscriptNames($newUsername)) {
		$message = $newUsername." ".$trans['Reserved js Word'];
	} elseif(($user->invalidCharacter($newUsername, '.') === true ) && $newUsername!='..' && $newUsername!='.' ) {
		$message = $trans['Invalid Char in User Name'];	
	} elseif(substr_count($passwd,"\\") > 0) {
		$message = $trans['Invalid Character in Password'];	
	} else { 
		$db_doc = getDbObject('docutron');
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->get('username', $newUsername);
		if($DO_user->username) {
			$date = time();
			$time2 = unixTimeStampFromSQLDateTime($DO_user->exp_time);
			if ($DO_user->guest == 1 && $time2 <= $date) {
				// if guest expired, delete from system
				$uid =getTableInfo($db_object,'access',array('uid'),array('username'=>$_POST['uname']),'queryOne'); 
				$whereArr = array('uid'=>(int)$uid);
				deleteTableInfo($db_object,'users_in_group',$whereArr);
				
				$DO_user->delete();

				$whereArr = array('username'=>$_POST['uname']);
				deleteTableInfo($db_object,'user_list',$whereArr);

				$whereArr = array('username'=>$_POST['uname']);
				deleteTableInfo($db_object,'access',$whereArr);
			} else {
				$message = $trans['Username Taken'];
			}
		}

		if($message === '') {
			$DO_user = DataObject::factory('users', $db_doc);
			$DO_user->username = $newUsername;
			$DO_user->password = md5($passwd);
			$DO_user->regdate = date('Y-m-d G:i:s');
			$DO_user->last_login = 'Never';
			$DO_user->email = $email;
			$dbAdminCode = (($admin == 1) ? 'C' : 'N');
			if($guest == 1) {
				$DO_user->guest = 1;
				$DO_user->exp_time = date('Y-m-d G:i:s',mktime(date('H') + $hours,date('i'),date('s'),date('m'),date('d'),date('Y')));
			}
			$DO_user->insertUser($user->db_name, $dbAdminCode); 
			if(!file_exists($DEFS['DATA_DIR'].'/'.$user->db_name.'/personalInbox/'.$newUsername)) {
				$userPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/personalInbox/'.$newUsername;
				mkdir($userPath);
				//allowWebWrite($userPath,$DEFS);
			}
			if ($guest == 1)
				$user->audit("guest user created", "user $newUsername created");
			else
				$user->audit("new user created", "user $newUsername created");
				
			//check for required password change on first login (set by dept)	
			if (getdbType() == 'mssql') {
				// mssql
				$sArr = array('min(cast(value as varchar(30)))');
			} else {
				// mysql & pgsql
				$sArr = array('min(value)');
			}
			$changePasswordSettings = getTableInfo($db_doc, 'settings', $sArr, array("k='requireChange' AND department='".$user->db_name."'"), 'queryOne');
			//$user_settings = new Usrsettings($user->username, $user->db_name);
			//if the setting exists and is 1, add/update it in the user_settings table
			if($changePasswordSettings && $changePasswordSettings == '1') {
				$res = $db_doc->extended->autoExecute('user_settings',array('username'=>$newUsername, 'k'=>'change_password_on_login',
					'value'=>'true', 'department'=>$user->db_name));
				dbErr($res);
			}
			
			$rights = array();
			foreach($user->access AS $cab => $arb) {
				$rights[$cab] = 'none';
			}
			$db_object = $user->getDbObject();
			$insertArr = array('username'=>$newUsername,'access'=>base64_encode(serialize($rights)));
			$res = $db_object->extended->autoExecute('access',$insertArr);
			dbErr($res);

			$groupCt = getTableInfo($db_object, 'groups', array('COUNT(*)'), array(), 'queryOne');
			
			if($groupCt) {
				$location = "../groups/editGroups.php?display=1&username=$newUsername&admin=$admin&guest=$guest";
			} elseif (count($user->cabArr)) {
				$location = "../secure/userAccess.php?username=$newUsername&admin=$admin&guest=$guest";
			} else {
				$location = "../secure/NewAccount.php?noCabinets=$newUsername";
			}
 
 			$linkDesc = $location;	
 		}
 	}
 
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
 	if($linkDesc) {
		$xmlObj->createKeyAndValue("LINK",$linkDesc);
	}

 	if($message) {
		$xmlObj->createKeyAndValue("MESSAGE",$message);
	}
	$xmlObj->setHeader();
}

function unixTimeStampFromSQLDateTime($strSQLDateTime) {
        $arrDateTime = explode(" ", $strSQLDateTime);
        $arrDate = explode("-", $arrDateTime[0]);
        $arrTime = explode(":", $arrDateTime[1]);

        return mktime($arrTime[0], $arrTime[1], $arrTime[2], $arrDate[1], $arrDate[2], $arrDate[0]);
}

function xmlGetDeptUserList($user) {
	$db = $user->getDbObject();	
	$accessInfo = getTableInfo($db,'access', array ('username'), array (),
			'queryCol', array('username' => 'ASC'));
	$myUsers = array ();
	foreach ($accessInfo as $uname) {
		if ($user->greaterThanUser($uname)) {
			$myUsers[] = $uname;
		}
	}

	$xmlObj = new xml();
	foreach ($myUsers as $uname) {
		$xmlObj->createKeyAndValue("USER",$uname);
	}
	$xmlObj->setHeader();
}

function xmlGetUserList($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('username');
	$oArr = array('username' => 'ASC');
	$userList = getTableInfo($db_dept,'access',$sArr,array(),'queryCol',$oArr);

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","loadUserList(XML)");
	foreach($userList AS $u) {
		$xmlObj->createKeyAndValue("USERNAME",$u);
	}
	$xmlObj->setHeader();	
}

function xmlGetUserInfo($enArr,$user,$db_doc,$db_dept) {
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->get('username', $enArr['username']);
	$email = $DO_user->email;

	$sArr = array('id','name');
	$wArr = array('department' => $user->db_name);
	$conn_list = getTableInfo($db_doc,'ldap',$sArr,$wArr,'getAssoc');
	
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","loadUserInfo(XML)");
	if(!check_enable('lite', $user->db_name)) {
		$xmlObj->createKeyAndValue("EMAIL",$email);
	}
	$xmlObj->createKeyAndValue("USERNAME",$enArr['username']);
	
	if(check_enable('signix', $user->db_name)) {
		$xmlObj->createKeyAndValue("ISSIGNIXENABLED", "1");
		$DO_signix_user = DataObject::factory('signix_users', $db_doc);
		$DO_signix_user->get('id', $DO_user->id);
		$signix_username = $DO_signix_user->signix_userid;
		$signix_password = $DO_signix_user->signix_password;
		$signix_sponsor = $DO_signix_user->signix_sponsor;
		$signix_client = $DO_signix_user->signix_client;
		$xmlObj->createKeyAndValue("SIGNIXUSERNAME",$signix_username);
		$xmlObj->createKeyAndValue("SIGNIXPASSWORD",$signix_password);
		$xmlObj->createKeyAndValue("SIGNIXSPONSOR",$signix_sponsor);
		$xmlObj->createKeyAndValue("SIGNIXCLIENT",$signix_client);
	}
	
	foreach($conn_list AS $id => $name) {
		$attArr = array('conn_id' => $id);
		if($id == $DO_user->ldap_id) {
			$attArr['selected'] = 1;
		}
		$xmlObj->createKeyAndValue("CONNECTOR",$name,$attArr);
	}
	$xmlObj->setHeader();	
}

function xmlSetUserInfo($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	global $trans;

	extract($enArr);
	$ifUserExists = false;
	$message = "";
	if($user->invalidJscriptNames($new_uname)) {
		$message = $new_uname." ".$trans['Reserved js Word'];
		$ifUserExists = true;
	} elseif(($user->invalidCharacter($new_uname, '.') === true) && $new_uname!='..' && $new_uname!='.') {
		$message = $trans['Invalid Char in User Name'];	
		$ifUserExists = true;
	} elseif($new_uname=='') {
		$message = 'User Name Can Not Be Blank';	
		$ifUserExists = true;
	} else {
		$db_doc = getDbObject('docutron');
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->get('username', $new_uname);
		if($DO_user->username) {
			$date = time();
			if ($DO_user->guest == 1) {
				$time2 = unixTimeStampFromSQLDateTime($DO_user->exp_time);
				if($time2 <= $date) { 
					// if guest expired, delete from system
					$whereArr = array('username'=>$username);
					$uid = getTableInfo($db_dept,'access',array('uid'),$whereArr,'queryOne'); 
					$whereArr = array('uid'=> (int)$uid);
					deleteTableInfo($db_dept,'users_in_group',$whereArr);
					
					$DO_user->delete();

					$whereArr = array('username'=>$username);
					deleteTableInfo($db_dept,'user_list',$whereArr);
					deleteTableInfo($db_dept,'access',$whereArr);
				}
			} else {
				$message = $trans['Username Taken'];
				$ifUserExists = true;
			}
		} 
	}

	if(!$ifUserExists || ($username == $new_uname)) {
		$DO_users = DataObject::factory('users', $db_doc);
		$DO_users->get('username', $username);
		$depList = array_keys($DO_users->departments);

		$path = $DEFS['DATA_DIR'];
		foreach($depList AS $dept) {
			$p = "$path/$dept/personalInbox";	
			rename("$p/$username","$p/$new_uname");

			$db_dept = getDbObject($dept);
			$uArr = array('username' => $new_uname);
			$wArr = array('username' => $username);
			//updateTableInfo($db_dept,'access',$uArr,$wArr);
			//updateTableInfo($db_dept,'user_list',$uArr,$wArr);
		}

		$uArr = array(	'username' => $new_uname, 
						'ldap_id' => (int)$ldap_id);
		if(isset($new_email)) {
			$uArr['email'] = $new_email;
		}
		$wArr = array('username' => $username);
		updateTableInfo($db_doc,'users',$uArr,$wArr);
		$uArr = array('username' => $new_uname);
		updateTableInfo($db_doc,'wf_todo',$uArr,$wArr);
		updateTableInfo($db_doc,'user_settings',$uArr,$wArr);
		
		if(isset($signix_username) && isset($signix_password) && isset($signix_sponsor) && isset($signix_client)) {
			$signix_wArr = array('id' => $DO_users->id);
			$signixUserInfo = getTableInfo($db_doc, 'signix_users', array('id'), $signix_wArr, 'queryOne');
			if(!$signixUserInfo) {
				$DO_signix_user = DataObject::factory('signix_users', $db_doc);
				insertIntoSignixUsers($db_doc, 'signix_users', $DO_users->id, $signix_username, $signix_password, $signix_sponsor, $signix_client);
			} else {
				$signix_uArr = array('signix_userid' => $signix_username,
									'signix_password' => $signix_password,
									'signix_sponsor' => $signix_sponsor,
									'signix_client' => $signix_client,);
				updateTableInfo($db_doc, 'signix_users', $signix_uArr, $signix_wArr); 
			}
		}
		
		// If the checkbox for forcing the user to change their password on next 
		// login has been checked, write the setting to the database. 
		// NOTE: checked box JS return in IE = -1, Firefox = true
		if ($change_password == 'true' || $change_password == '-1')
		{
			$user_settings = new Usrsettings($new_uname, $depList[0]);
			$user_settings->set('change_password_on_login', 'true');
		}
		/*else
		{
			$user_settings = new Usrsettings($new_uname, $depList[0]);
			Debug::logVar($user_settings->get("change_password_on_login"));
		}*/
		$message = "User info updated successfully";
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$message);
	$xmlObj->setHeader();	
}

function xmlImportUsers($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	$indexArr = array(	'username VARCHAR(255) NULL',
						'password VARCHAR(255) NULL',
						'email VARCHAR(255) NULL');

	$importFile = $DEFS['TMP_DIR']."/importUsers-$user->username";
	if(is_file($importFile.".txt")) {
		$importFile .= ".txt";	
	} else {
		$importFile .= ".csv";	
	}
	$type = getExtension($importFile);
	$tempTable = createDynamicTempTable($db_dept,$indexArr);
	insertTempTable($db_dept,$tempTable);
	$_SESSION['importUsersTable'] = $tempTable;

	$insertArr = array();
	$hd = fopen($importFile,'r');
	$delimiter = ",";
	if($type == 'txt') {
		$delimiter = "\t";
	}

	while(false !== ($data = fgetcsv($hd,1000,$delimiter))) {
		$insertArr[] = array(	"username" => ($data[0]) ? strtolower($data[0]) : "", 
								"password" => isset($data[1]) ? $data[1] : "",
								"email" => isset($data[2]) ? $data[2] : "" );
	}
	fclose($hd);
	unlink($importFile);

	foreach($insertArr AS $ins) {
		$res = $db_dept->extended->autoExecute($tempTable,$ins);
		dbErr($res);
	}	

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","loadTempUserList(XML)");
	for($i=0;$i<count($insertArr) && $i<5;$i++) {
		$parentEl = $xmlObj->createKeyAndValue("USER");
		$xmlObj->createKeyAndValue("USERNAME",$insertArr[$i]['username'],array('key' => 'usename'),$parentEl);
		$xmlObj->createKeyAndValue("PASSWORD",$insertArr[$i]['password'],array('key' => 'password'),$parentEl);
		if(!check_enable('lite', $user->db_name)) {
			$xmlObj->createKeyAndValue("EMAIL",$insertArr[$i]['email'],array('key' => 'email'),$parentEl);
		}
	}
	$xmlObj->setHeader();	
}

function xmlSaveImportUsers($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;
	global $trans;
	$tempTable = $_SESSION['importUsersTable'];

	$userList = getTableInfo($db_dept,$tempTable,array(),array(),'queryAll');
	$message = array();
	$ldap_id = $enArr['ldap_id'];

	
	$keyArr = array();
	$keyArr[$enArr['username']] = 'username';
	$keyArr[$enArr['password']] = 'password';
	$keyArr[$enArr['email']] = 'email';

	foreach($userList AS $uInfo) {
		$uname = $uInfo[$keyArr['username']];
		$passwd = $uInfo[$keyArr['password']];
		$email = $uInfo[$keyArr['email']];
		$createUser = true;

		if($user->invalidJscriptNames($uname)) {
			$message[] = $uname." ".$trans['Reserved js Word']." - $uname";
		} elseif(($user->invalidCharacter($uname) === true) && $newUsername!='..' && $newUsername!='.' ) {
			$message[] = $trans['Invalid Char in User Name']." - $uname";	
		} elseif(substr_count($passwd,"\\") > 0) {
			$message[] = $trans['Invalid Character in Password']." - $uname";
		} else { 
			$DO_user = DataObject::factory('users', $db_doc);
			$DO_user->get('username', $uname);
			if($DO_user->username) {
				$date = time();
				if ($DO_user->guest == 1) {
					$time2 = unixTimeStampFromSQLDateTime($DO_user->exp_time);
				   	if($time2 <= $date) {
						// if guest expired, delete from system
						$wArr = array('username'=>$uname);
						$uid = getTableInfo($db_dept,'access',array('uid'),$wArr,'queryOne'); 
						$wArr = array('uid'=>(int)$uid);
						deleteTableInfo($db_dept,'users_in_group',$wArr);
						$DO_user->delete();

						$wArr = array('username'=>$uname);
						deleteTableInfo($db_dept,'user_list',$wArr);
						deleteTableInfo($db_dept,'access',$wArr);
					}
				} else {
					$message[] = $trans['Username Taken']." - $uname";
					$createUser = false;
				}
			} 

			if($createUser) {
				$DO_user = DataObject::factory('users', $db_doc);
				$DO_user->username = $uname;
				$DO_user->password = md5($passwd);
				$DO_user->regdate = date('Y-m-d G:i:s');
				$DO_user->last_login = 'Never';
				$DO_user->email = $email;
				$DO_user->ldap_id = $ldap_id;
				$dbAdminCode = 'N';
				$DO_user->insertUser($user->db_name, $dbAdminCode); 
				if(!file_exists($DEFS['DATA_DIR'].'/'.$user->db_name.'/personalInbox/'.$uname)) {
					$userPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/personalInbox/'.$uname;
					mkdir($userPath);
					//allowWebWrite($userPath,$DEFS);
				}
				$user->audit("new user created", "user $uname created");
				
				$rights = array();
				foreach($user->access AS $cab => $arb) {
					$rights[$cab] = 'none';
				}
				$insertArr = array('username'=>$uname,'access'=>base64_encode(serialize($rights)));
				$res = $db_dept->extended->autoExecute('access',$insertArr);
				dbErr($res);
			}
		}			
	}
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	if(count($message)) {
		foreach($message AS $mess) {
			$xmlObj->createKeyAndValue("MESSAGE",$mess);
		}
	} else {
		$xmlObj->createKeyAndValue("MESSAGE","Usernames imported successfully");
	}
	$xmlObj->setHeader();
}

function xmlAddCabinetFilter($enArr,$user,$db_doc,$db_dept) {
	$db_dept->extended->autoExecute('cabinet_filters',$enArr);	
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE","Filter successfully added");
	$xmlObj->setHeader();
}

function xmlRemoveCabinetFilter($enArr,$user,$db_doc,$db_dept) {
	$ct = 1;
    while(isSet($enArr['searchID-'.$ct])) {
		$wArr = array('id' => $enArr['searchID-'.$ct]);
		deleteTableInfo($db_dept,'cabinet_filters',$wArr);
        $ct++;
    }

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE","Filter(s) successfully deleted");
	$xmlObj->setHeader();
}

if(isset($_GET['getDeptUserList'])) {
	xmlGetDeptUserList($user);
}
?>
