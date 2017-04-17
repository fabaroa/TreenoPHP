<?php
if (file_exists('../lib/crypt.php')) {
        include_once '../lib/crypt.php';
        include_once '../lib/settings.php';
} else {
        include_once 'lib/crypt.php';
        include_once 'lib/settings.php';
}

function getLDAPUserListByID ($connID) {
	return array ('tmccann', 'mbemis', 'jwong', 'wthibodeau');
}

function getLDAPConnectionList () {
	return array ('1' => 'My Connection');
}

function checkLDAPPassword ($dbDoc, $ldapID, $myUserName, $myPassword, $decrypt = false) {
	global $DEFS;

        if ($decrypt) {
                $myPassword = tdDecrypt ($myPassword);
        }
		//error_log("checkLDAPPassword() ldapID:$ldapID, myUserName:$myUserName, myPassword:$myPassword");
	$ldapUser = getTableInfo ($dbDoc, 'users', array('ldap_user'), array('username' => $myUserName), 'queryOne');
	if ($ldapUser) {
		$myUserName = $ldapUser;
	}
	$ldapInfo = getTableInfo ($dbDoc, 'ldap', array (),
	array ('id' => (int) $ldapID), 'queryRow');
	//error_log("ldapInfo: ".print_r($ldapInfo,true));
	$ds = ldap_connect ($ldapInfo['host']);
	//error_log("ds: ".print_r($ds,true));
	//  ldap_set_option (NULL, LDAP_OPT_DEBUG_LEVEL, 7);
	if ($ds) {
		if ($ldapInfo['active_directory'] == '1') {
			if ($ldapInfo['suffix']) {
				$connStr = $myUserName . '@' . $ldapInfo['suffix'];
				$r = @ldap_bind ($ds, $connStr, $myPassword);
				if ($r) {
					return true;
				} else {
					return false;
				}
			} else {
				$connStr = $ldapInfo['query_user'] . ',' .
				$ldapInfo['connect_string'];
				$r = @ldap_bind ($ds, $connStr, $ldapInfo['query_password']);
				//error_log($r);
				if ($r) {
					$sr = ldap_list ($ds, $ldapInfo['connect_string'],
					'SamAccountName=' . $myUserName, array ('dn'));
					$info = ldap_get_entries ($ds, $sr);
					if ($info['count'] == 1) {
						$dnStr = $info[0]['dn'];
						$r = ldap_bind ($ds, $dnStr, $myPassword);
						if ($r) {
							return true;
						} else {
							return false;
						}
					} else {
						return false;
					}
				} else {
					return false;
				}
			}
		} else {
			$connStr = '';
			if($ldapInfo['connect_string']) {
				$bindType = "cn";
				if(isSet($DEFS['LDAP_BIND'])) {
					$bindType = $DEFS['LDAP_BIND'];
				}
				$connStr = $bindType.'=' . $myUserName . ',' .
				$ldapInfo['connect_string'];
			} else {
				$connStr = $myUserName;
			}
			$r = @ldap_bind ($ds, $connStr, $myPassword);
			//error_log($r);
			if ($r) {
				return true;
			} else {
				return false;
			}
		}
	} else {
		return false;
	}
}

function xmlSetLDAPConnector($enArr,$user,$db_doc,$db_dept) {
	if(!$enArr['query_user']) {
		$enArr['query_user'] = NULL;
	}

	if(!$enArr['query_password']) {
		$enArr['query_password'] = NULL;
	}
	$enArr['department'] = $user->db_name;

	if($enArr['conn_id'] == "__new") {
		unset($enArr['conn_id']);
		$res = $db_doc->extended->autoExecute('ldap',$enArr);
		if(PEAR::isError($res)) {
			$message = "LDAP connection failed to save";
		} else {
			$message = "LDAP connection added successfully";
		}
	} else {
		$wArr = array('id' => (int)$enArr['conn_id']);
		unset($enArr['conn_id']);
		if(updateTableInfo($db_doc,'ldap',$enArr,$wArr)) {
			$message = "LDAP connection updated successfully";
		} else {
			$message = "LDAP connection failed to update";
		}
	}
	
	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$message);
	$xmlObj->setHeader();
}

function testLDAPConnection($enArr,$user,$db_doc,$db_dept) {
	global $DEFS;

	$ifValid = false;
    $ds = ldap_connect($enArr['host']);
	if($ds) {
		if ($enArr['active_directory'] == '1') {
			if ($enArr['suffix']) {
				$connStr = $enArr['test_user'] . '@' . $enArr['suffix'];
				$r = @ldap_bind ($ds, $connStr, $myPassword);
				if ($r) {
					$ifValid = true;
				}
			} else {
				$connStr = $enArr['test_user'] . ',' .
				$enArr['connect_string'];
				$r = @ldap_bind ($ds, $connStr, $enArr['test_password']);
				if ($r) {
					$sr = ldap_list ($ds, $enArr['connect_string'],
					'SamAccountName=' . $myUserName, array ('dn'));
					$info = ldap_get_entries ($ds, $sr);
					if ($info['count'] == 1) {
						$dnStr = $info[0]['dn'];
						$r = ldap_bind ($ds, $dnStr, $myPassword);
						if ($r) {
							$ifValid = true;
						}
					}
				}
			}
		} else {
			$connStr = '';
			if($enArr['connect_string']) {
				$bindType = "cn";
				if(isSet($DEFS['LDAP_BIND'])) {
					$bindType = $DEFS['LDAP_BIND'];
				}
				$connStr = $bindType.'=' . $myUserName . ',' .
				$enArr['connect_string'];
			} else {
				$connStr = $myUserName;
			}
			$r = @ldap_bind ($ds, $connStr, $myPassword);
			if ($r) {
				$ifValid = true;
			}
		}
	}

	$message = "Test Failed";
	if($ifValid) {
		$message = "Test Passed";
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setMessage(XML)");
	$xmlObj->createKeyAndValue("MESSAGE",$message);
	$xmlObj->setHeader();
}

function xmlGetLDAPConnector($enArr,$user,$db_doc,$db_dept) {
	$sArr = array('id','name');
	$wArr = array('department' => $user->db_name);
	$connList = getTableInfo($db_doc,'ldap',$sArr,$wArr,'getAssoc');
	uasort($connList,"strnatcasecmp");

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setLDAPConnections(XML)");
	foreach($connList AS $id => $name) {
		$xmlObj->createKeyAndValue("CONNECTOR",$name,array('id' => $id));
	}
	$xmlObj->setHeader();
}

function xmlGetLDAPConnInfo($enArr,$user,$db_doc,$db_dept) {
	$wArr = array('id' => (int)$enArr['conn_id']);
	$connInfo = getTableInfo($db_doc,'ldap',array(),$wArr,'queryRow');

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setLDAPConnInfo(XML)");
	foreach($connInfo AS $k => $v) {
		$xmlObj->createKeyAndValue("KEY",$v,array('name' => $k));
	}
	$xmlObj->setHeader();
}
?>
