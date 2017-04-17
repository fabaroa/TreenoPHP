<?php
// $Id: changePermissions.php 14177 2011-01-04 14:49:19Z acavedon $
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
include_once '../updates/updatesFuncs.php';
include_once '../lib/quota.php';
include_once '../lib/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser()) {
	if (isset ($_GET['message'])) {
		$mess = $_GET['message'];
	} else {
		$mess = '';
	}
	 
  	$DBPermissions = "Permissions Successfully Changed";
  	//get list of departments --> function found in depfuncs.php
 	$depList = getDatabases( $db_doc );
  	//get arbitrary names for each department
	$arbList = getLicensesInfo( $db_doc, 'real_department', 'arb_department', 1 );
	uasort( $arbList, "strnatcasecmp" );
	$depOrder = array_keys( $arbList );
  	//get list of users --> function found in depfuncs.php
  	$uNames = getUsernames( $db_doc );
  	//get the rights for each user in each database --> function found in depfuncs.php
  	$rights = getUserDepartmentInfo( $db_doc, $depList );
	if (isset ($_GET['department'])) {
	  	$department = $_GET['department'];
	} else {
		$department = '';
	}
  	if( isset($_POST['B1']) ) {
		//creates a two dimensional array of each username and their databases
		//add database to usernames selected
		for($i=0;$i<sizeof($uNames);$i++) {
			$DO_user = DataObject::factory('users', $db_doc);
			$DO_user->get('username', $uNames[$i]);
			if( $DO_user->username != "admin" ) {
				$editDB = $_POST['DepartmentList'];
				$tmp = $uNames[$i];
				$tmpName = $_POST[$DO_user->username];

				//get the default DB for the user
				
	  			#$defaultDB = getDefaultDB( $db_doc, $tmp );
				//have to remove the first character from each department
				#$userDepartments = explode( ":", $userDBInfo[$i]['db_name'] );
				#unset($userDepartments[sizeof($userDepartments) - 1]); //The last one will always be blank
				
				//connects to each department in case a user needs to be added
				//to the access table
				$currentDB = getDbObject($editDB);
				
				#$userDeps = array();
				#for($x=0;$x<sizeof($userDepartments);$x++)
				#	$userDeps[$x] = substr($userDepartments[$x],1);
				if( $tmpName == "yes" ) {
					if( !in_array( $editDB, array_keys($DO_user->departments) ) ) {
						
						#$sqlValue = dbConcat(array('db_name', "'N$editDB:'"));
						
						if( $DO_user->defaultDept == '' ) {
							$DO_user->changeDepartmentAccess($editDB, 'N', 1);
						} else {
							$DO_user->changeDepartmentAccess($editDB, 'N');
						}

						$rightsInfo = getTableInfo($currentDB,'access',array(),array('username'=>'admin'));
						$row = $rightsInfo->fetchRow();
						$access = unserialize(base64_decode($row['access']));
						if(!is_array($access)) {
							$access = array();
						}
						$cabRights = current($access);
						while($cabRights) {
							$access[key($access)] = str_replace('rw', 'none', $cabRights);
							$cabRights = next($access);
						}
						
						if(getTableInfo($currentDB,'access',array('COUNT(uid)'),array('username'=>$tmp),'queryOne') == 0 ) {
							$insertArr = array('username'=>$tmp,'access'=>base64_encode(serialize($access)));
							$res = $currentDB->extended->autoExecute('access',$insertArr);
						} else {
							$updateArr = array('access'=>base64_encode(serialize($access)));
							$whereArr = array('username'=>$tmp);
							updateTableInfo($currentDB,'access',$updateArr,$whereArr);
						}

						if( !file_exists( $DEFS['DATA_DIR']."/".$editDB."/personalInbox/$tmp" ) ) {
							$path = $DEFS['DATA_DIR']."/".$editDB."/personalInbox/".$tmp;
   							lockTables($db_doc, array('licenses'));
							$updateArr = array('quota_used'=>'quota_used+4096');
							$whereArr = array('real_department'=> $editDB);
							updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
							unlockTables($db_doc);
							mkdir($path);
						}
						$auditStr = "$tmp granted access to ".$arbList[$editDB];
						$auditStr .= " [$editDB], ".$user->username." currently in department ".$user->db_name;
						$user->audit("Department Permissions Changed", $auditStr, $currentDB);
					}
				} else {
					if( in_array( $editDB, array_keys($DO_user->departments) ) ) {
						$newUsrDB = array ();
						//creates a new list of the databases for each user
						$newDefault = '';
						foreach($DO_user->departments as $deptName => $priv) {
							if( $deptName != $editDB ) {
								$newDefault = $deptName;
								break;
							}
						}
						if($newDefault) {
							$DO_user->changeDepartmentAccess($newDefault, $DO_user->departments[$newDefault], 1);
						}
						$DO_user->deleteDeptFromUser($editDB);
						$whereArr = array('username'=>$tmp);
						deleteTableInfo($currentDB,'access',$whereArr);
						if( file_exists( $DEFS['DATA_DIR']."/".$editDB."/personalInbox/$tmp" ) ) {
							$userpath = $DEFS['DATA_DIR']."/".$editDB."/personalInbox/".$tmp;
							$adminpath = $DEFS['DATA_DIR']."/".$editDB."/personalInbox/admin/";
							rename($userpath, $adminpath.'/'.$tmp);
						}
						$auditStr = "$tmp access removed from ".$arbList[$editDB];
						$auditStr .= " [$editDB], ".$user->username." currently in department ".$user->db_name;
						$user->audit("Department Permissions Changed", $auditStr, $currentDB);
					
					}
				}
				$currentDB->disconnect ();
			}
		}
echo<<<ENERGIE
<script>
	onload = parent.mainFrame.window.location = "changePermissions.php?message=$DBPermissions";
</script>
ENERGIE;
die();
  	}  
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <title>Change Department Permission</title>
  <script>
   function selectRights(type) {
	var checkList = document.getElementsByTagName('input');
        for(var i=0;i<checkList.length;i++) {
                if(checkList[i].type == 'radio' && checkList[i].value == type) {
                        checkList[i].checked = true;
                }
        }
   }
   function getSelected() {
     val = document.editDep.DepartmentList[document.editDep.DepartmentList.selectedIndex].value;
     if( val != "default") {
		onload = parent.mainFrame.window.location = 'changePermissions.php?department='+val;
	 }
   }
  </script>
 </head>
 <body class="centered">
  <div class="mainDiv">
  <div class="mainTitle">
   <span>Change Department Permissions</span>
  </div>
  <form name="editDep" method="POST" action="changePermissions.php">
  <table class="inputTable">
   <tr>
    <td class="label">	
     <label for="usersSel">Select Department</label>
    </td>
    <td>
     <select id="usersSel" name="DepartmentList" onchange="getSelected()">
      <option value="default">Choose Department</option>
ENERGIE;
       for($i=0;$i<sizeof($depOrder);$i++) {
         $tmp = $depOrder[$i]; 
         $var = $arbList[$tmp];
         if($tmp != $department )
           echo"\n           <option value=\"$tmp\">$var</option>\n";
		 else
           echo"\n           <option selected value=\"$tmp\">$var</option>\n";
       }
echo<<<ENERGIE
      </select>
     </td>
    </tr>
   </table>
ENERGIE;
	if( $department ) {
        echo<<<ENERGIE
         <div class="inputForm">
         <table>
		  <tr>
           <th align="center" width="80%">Username</th>
           <th align="center" colspan="2" nowrap="yes">
             Access<br>
             <a
              onmouseover="javascript:style.backgroundColor='888888'"
              onmouseout="javascript:style.backgroundColor='ffffff'"
              onclick="selectRights('yes');">Grant </a> 
             <a
              onmouseover="javascript:style.backgroundColor='888888'"
              onmouseout="javascript:style.backgroundColor='ffffff'"
              onclick="selectRights('no');"> Deny</a>
           </th>
          </tr>
		<tr>
	</tr>
ENERGIE;

		for($i=0;$i<sizeof($uNames);$i++) {	
            $var = $uNames[$i];
			if( $var != "admin" ) {
				echo "<tr>\n";
				echo " <td align='center'>$var</td>\n";
				if( $rights[$department][$var] == "yes" ) {
					echo " <td align='center'>\n";
					echo "  <input type=\"radio\" name=\"$var\" value=\"yes\" checked></td>\n";
            		echo " <td align='center'>\n";
					echo "  <input type=\"radio\" name=\"$var\" value=\"no\"></td>\n";
				} else {
					echo " <td align='center'>\n";
					echo "  <input type=\"radio\" name=\"$var\" value=\"yes\"></td>\n";
            		echo " <td align='center'>\n";
					echo "  <input type=\"radio\" name=\"$var\" value=\"no\" checked></td>\n";
				}
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
		echo "</div>\n";
		echo "<div style='float: right'>\n";
		echo "  <input type='submit' name='B1' value='Save'>\n";
		echo "</div>\n";
	}
	
	if( $mess != null ) {
		echo "<div style='float: center'>\n";
	  	echo " <span class='error' id='DBmess'>$mess</span>\n";
		echo "</div>\n";
	}
	echo "</form>\n";
	echo "</div>\n";
	echo "</body>\n";
	echo "</html>\n";

	setSessionUser($user);
} else {
	logUserOut();
} 

?>
