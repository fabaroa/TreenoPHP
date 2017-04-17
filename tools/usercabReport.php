<?php
// $Id: usercabReport.php 14237 2011-01-04 16:41:41Z acavedon $

/*-----------------------------------------
 * userAccess.php
 * This page is accessed by:
 *  -choosing the "Change Permissions" item
 *  on the "User Functions" menu in settings
 *  -upon creating a new user (after giving
 *  a name and password, you are sent here)
 *---------------------------------------*/
include_once '../classuser.inc';
include_once '../groups/groups.php';
require_once '../check_login.php';
function getArbGroupsForUser($db_object,$username) {
	$query = "SELECT arb_groupname FROM groups,users_in_group,access ";
	$query .= "WHERE access.username='$username' AND ";
	$query .= "access.uid=users_in_group.uid AND ";
	$query .= "groups.id=users_in_group.group_id ORDER BY arb_groupname ASC";
	$res = $db_object->queryCol($query);
	dbErr($res);
	return $res;
}
function queryAllGroupAccessRORW(&$db_object,$username) {
	$query = "SELECT departmentname,real_groupname,group_access.access,arb_groupname ";
	$query .= "FROM group_access,groups,departments,users_in_group,access WHERE ";
	$query .= "groups.id=group_access.group_id AND DepartmentID=cabID ";
	$query .= "AND groups.id=users_in_group.group_id ";
	$query .= "AND users_in_group.uid=access.uid AND group_access.access!='none' AND access.username='$username' ORDER BY arb_groupname ASC";
	$results = $db_object->queryAll($query);
	dbErr($results);
	return $results;
}

if ($logged_in and $user->username and $user->isDepAdmin()) {
    //variables whose contents may have to be translated
    $noCabsMessage = $trans['noCabsMessage'];
    $tableTitle = $trans['Change User Permissions'];
    $roCabsMessage = $trans['roCabinetsMessage'];
    $selectUser = $trans['Select User'];
    $updateButton = $trans['Update'];
    $cabLabel = $trans['Cabinet'];
    $r_w = $trans['read_write'];
    $r_o = $trans['read_only'];
    $none = $trans['no_permissions'];
    $submit = $trans['Submit'];
    $adminLabel = $trans['Admin'];

    $user->setSecurity();
    $db_object = $user->getDbObject();

    if (isset ($_GET['u'])) {
        $uid = $_GET['u'];
    } else {
        $uid = '';
    }

    if (isset ($_GET['username'])) {
        $usernameTest = $_GET['username'];
        $uid = getTableInfo($db_object,'access',array('uid'),array('username'=>$usernameTest),'queryOne');
    } else {
        $usernameTest = '';
    }

    if (isset ($_GET['admin'])) {
        $admin = $_GET['admin'];
    } else {
        $admin = '';
    }

    if (isset ($_GET['mess'])) {
        $message = $_GET['mess'];
    } else {
        $message = '';
    }

    if (isset ($_GET['guest']) && $_GET['guest']==1) {
        $guest = $_GET['guest'];
    } else {
        $guest = '';
        $users = getTableInfo($db_object,'access', array(), array (), 'query', array
                ('username' => 'ASC'));
        $userArr = array ();
        $uidArr = array ();
        while ($result = $users->fetchRow()) {
            $userArr[] = $result['username'];
            $uidArr[$result['username']] = $result['uid'];
        }
    }

    if ($uid) {
        $accessInfo = getTableInfo($db_object,'access',array(),array('uid'=>(int)$uid));
        $access = $accessInfo->fetchRow();
        $rights = unserialize(base64_decode($access['access']));
    }

    echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<title>$tableTitle</title>
<style type="text/css">
div.error2 {
    font-weight: bold;
}
div.error {
    margin-left: auto;
    margin-right: auto;
}
</style>
</head>
<body class="centered">
<div class="mainDiv" style="width:500px">
	<div class="mainTitle">
		<span>User Group Cabinet Rights</span>
	</div>
	<table class="inputTable2" style="width:500px">
HTML;
    if ($user->noCabinets()) {
        echo '<div class="error error2">';
        //if there are no cabinets currently in the database:
        echo $roCabsMessage;
        echo '</div>';
    } else {
        if (!$guest) {
			echo '<tr style=\"width:100%\"><th style=\"width:30%\">Users</th>';
			echo '<th style=\"width:60%\">Cabinets</th><th style=\"width:10%\">Access</th></tr>';
            foreach ($userArr as $uname) {
                $id = $uidArr[$uname];
                if ($user->greaterThanUser($uname) and $user->username != $uname ) {
						echo '<tr><td>'.$uname.'</td></tr>';
										    if ($id) {
										        $accessInfo = getTableInfo($db_object,'access',array(),array('uid'=>(int)$id));
										        $access = $accessInfo->fetchRow();
										        $rights = unserialize(base64_decode($access['access']));
										    }
//this gets a list of cabinets withing groups that have ro or rw
        								$groupList = queryAllGroupAccessRORW($db_object,$uname);
        								$oldGroup='';
												foreach( $groupList AS $groupInfo ) {
													$groups = $groupInfo['arb_groupname'];
		                      if ($oldGroup!=$groups) {
		                      	echo '<tr><td></td><td><b>Group Membership:</b> '.$groups.'</td><td></td></tr>';
		                      	}
		                      $oldGroup=$groups;
												}
						            foreach($user->cabArr as $cabname => $dispname) {
														$cabinet = '';
														$groups = '';
						                if (isset ($rights[$cabname])) {
						                    $cabRights = $rights[$cabname];
						                 } else {
						                    $cabRights = '';
						                }
														foreach( $groupList AS $groupInfo ) {
															$cabinet = $groupInfo['departmentname'];
															if($cabinet == $dispname && $cabRights!='rw') {
																	$cabRights = $groupInfo['access'];
																	$groups = $groupInfo['arb_groupname'];
															}
														}
						                
        
						                if ($cabRights == 'rw'|| $cabRights == 'ro') {
                echo<<<HTML
<tr><td></td><td>$dispname</td><td>$cabRights</td><td>$groups</td></tr>
HTML;
					                } else {
					                }
					            }
					      }
                }
            }
            echo<<<HTML
</p>
HTML;
    echo '</table></div></body></html>';
    setSessionUser($user);
	}
} else {
    logUserOut();
}
?>
