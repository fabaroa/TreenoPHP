<?php

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ){
    //variables to translate
    $systemATIconsTitle = $trans['System AllThumbs Icons'];
    $changeSett         = $trans['Change Setting'];
    $getPDF             = $trans['Get As PDF File'];
    $getZIP             = $trans['Get As ZIP File'];
    $delFile            = $trans['Delete File'];
    $mvFile             = $trans['Move File'];
    $crNewTab           = $trans['Add/Edit Tabs'];
    $upload             = $trans['Upload'];
    $fileOptionName     = $trans['File Option Name'];
    $doNotShow          = $trans['Do Not Show'];
    $adminOnly          = $trans['Admin Only'];
    $allUsers           = $trans['All Users'];
    $changed            = $trans['File Option Settings Have Changed'];
	$systemPref    		= $trans['System Preferences'];
    $mvFileSettsCh 		= $trans['MV File Setts Changed'];
    $mvFileSetts   		= $trans['MV File Settings'];
    $selectSetts   		= $trans['Select Settings'];
    $enable        		= $trans['Enable'];
    $disable       		= $trans['Disable'];
	$delFileOpts 		= $trans['Delete File Options'];
    $dfsChanged  		= $trans['Delete File Settings Have Been Changed'];
    $confirm    	 	= $trans['Confirm on Delete'];
    $delFiles    		= $trans['Delete Files'];
    $delFolders  		= $trans['Delete Folders'];
	$getOrder			= "Change Thumbnail View";
    $delCabinets  		= "Delete Cabinets";

    $db_doc = getDbObject ('docutron');
    $settings = new GblStt( $user->db_name, $db_doc );
    /////////Delete File Options//////////// 
	$setFiles = $settings->get( 'deleteFiles' );
    $filesWarn = $settings->get( 'filesWarn' );
    $setFolders = $settings->get( 'deleteFolders' );
    $foldersWarn = $settings->get( 'foldersWarn' );
    $setCabinets = $settings->get( 'deleteCabinets' );
    $cabinetsWarn = $settings->get( 'cabinetsWarn' );

    //if these are null, give them initial values
    if($setFiles == null) {
        $settings->set( 'deleteFiles','0');
        $setFiles = $settings->get( 'deleteFiles' );
    }
                                                                                                                             
    if($filesWarn == null) {
        $settings->set( 'filesWarn','true');
        $filesWarn = $settings->get( 'filesWarn' );
    }
                                                                                                                             
    if($setFolders == null) {
        $settings->set( 'deleteFolders','0');
        $setFolders = $settings->get( 'deleteFolders');
    }
                                                                                                                             
    if($foldersWarn == null) {
        $settings->set( 'foldersWarn','true');
        $foldersWarn = $settings->get( 'foldersWarn' );
    }
                                                                                                                             
    if($setCabinets == null) {
        $settings->set( 'deleteCabinets','0');
        $setCabinets = $settings->get( 'deleteCabinets');
    }

	if($cabinetsWarn == null) {
        $settings->set( 'cabinetsWarn','true');
        $cabinetsWarn = $settings->get( 'cabinetsWarn' );
    }
                                                                                                                             
    //check post to see if this page posted
    if(isset($_POST['changeDelete'])) {
        $setFiles = $_POST['filesToggle'];
        $settings->set( 'deleteFiles', $setFiles);
                                                                                                                             
        $setFolders = $_POST['foldersToggle'];
        $settings->set( 'deleteFolders', $setFolders);
                                                                                                                             
        $setCabinets = $_POST['cabinetsToggle'];
        $settings->set( 'deleteCabinets', $setCabinets);
                                                                                                                             
        $filesWarn = $_POST['filesWarnChecked'];
        $settings->set( 'filesWarn', $filesWarn);
                                                                                                                             
        $foldersWarn = $_POST['foldersWarnChecked'];
        $settings->set( 'foldersWarn', $foldersWarn);

        $cabinetsWarn = $_POST['cabinetsWarnChecked'];
        $settings->set( 'cabinetsWarn', $cabinetsWarn);
                                                                                                                             
        $message2 = $dfsChanged;
    }

	//based on post, set what things are selected
    if($setFiles) {
        $filesEnable = "checked";
        $filesCheck = "false";
    } else {
        $filesDisable = "checked";
        $filesCheck = "true";
    }
                                                                                                                             
    if($setFolders) {
        $foldersEnable = "checked";
        $foldersCheck = "false";
    } else {
        $foldersDisable = "checked";
        $foldersCheck = "true";
    }
                                                                                                                             
    if($setCabinets) {
        $cabinetsEnable = "checked";
        $cabinetsCheck = "false";
    } else {
        $cabinetsDisable = "checked";
        $cabinetsCheck = "true";
    }
                                                                                                                             
    if($filesWarn == 'true' ) {
        $filesTic = "checked";
    }

	if($foldersWarn == 'true' ) {
        $foldersTic = "checked";
    }
                                                                                                                             
    if($cabinetsWarn == 'true' ) {
        $cabinetsTic = "checked";
    }
   	/////////End of Delete Options/////////// 

	/////////File Option Settings////////////
	$setIcons = $settings->get( 'sysATIcons' );

    //if these are null, give them initial values
    if($setIcons == null) {
        $settings->set( 'sysATIcons', "all,all,all,all,all,all,all,all" );
        $setIcons   = $settings->get( 'sysATIcons' );
    }

    //check post to see if this page posted
    if(isset($_POST['changeSettings'])) {
		$pdfIcon    = $_POST['pdfIcon'];
        $zipIcon    = $_POST['zipIcon'];
        $deleteIcon = $_POST['deleteIcon'];
        $moveIcon   = $_POST['moveIcon'];
        $uploadIcon = $_POST['uploadIcon'];
        $tabsIcon   = $_POST['tabsIcon'];
		$orderIcon	= $_POST['orderIcon'];
		$delCabIcon	= $_POST['delCabIcon'];

        $toImplode = array( $pdfIcon, $zipIcon, $deleteIcon, $moveIcon, $tabsIcon, $uploadIcon, $orderIcon, $delCabIcon );

        $newIconSettings = implode( ",", $toImplode );
        $settings->set( 'sysATIcons', $newIconSettings );
        $setIcons = $settings->get( 'sysATIcons' );

        $message3 = $changed;
    }

    //take the settings and put them into appropriate variables
    $values = explode( ",", $setIcons );
    if( strcmp($values[0],"none")==0 )
        $pdfnone = 'checked';
    else if( strcmp( $values[0],"admin" )==0 )
        $pdfadmin = 'checked';
    else
        $pdfall = 'checked';

    if( strcmp($values[1],"none")==0 )
        $zipnone = 'checked';
    else if( strcmp( $values[1],"admin" )==0 )
        $zipadmin = 'checked';
    else
        $zipall = 'checked';

    if( strcmp($values[2],"none")==0 )
        $deletenone = 'checked';
    else if( strcmp( $values[2],"admin" )==0 )
        $deleteadmin = 'checked';
    else
        $deleteall = 'checked';

    if( strcmp($values[3],"none")==0 )
        $movenone = 'checked';
    else if( strcmp( $values[3],"admin" )==0 )
        $moveadmin = 'checked';
    else
        $moveall = 'checked';

    if( strcmp($values[4],"none")==0 )
        $tabnone = 'checked';
    else if( strcmp( $values[4],"admin" )==0 )
        $tabadmin = 'checked';
    else
        $taball = 'checked';

    if( strcmp($values[5],"none")==0 )
        $uploadnone = 'checked';
    else if( strcmp( $values[5],"admin" )==0 )
        $uploadadmin = 'checked';
    else
        $uploadall = 'checked';

	if( strcmp($values[6],"none")==0 )
        $ordernone = 'checked';
    else if( strcmp( $values[6],"admin" )==0 )
        $orderadmin = 'checked';
    else
        $orderall = 'checked';

	if( strcmp($values[7], "all" ) ==0 )
		$delCaball = 'checked';
	elseif( strcmp($values[7],"none")==0 )
        $delCabnone = 'checked';
    else
        $delCabadmin = 'checked';
	///////End of File Options////////////

	///////HTML that contains all javascript functions for///////////
	///////all three settings/////////////////
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <title>$systemATIconsTitle</title>

  <script>
   function selectRights(type) {
	var num = 2;
    if(type == "NONE")
		num = 0;
	else if(type == "ADMIN")
		num = 1;
	else
		num = 2;

	eval( 'document.getElementById(\'sysf\').pdfIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').zipIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').deleteIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').moveIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').tabsIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').uploadIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').orderIcon[num].checked = true' );
	eval( 'document.getElementById(\'sysf\').delCabIcon[num].checked = true' );
   }

   function mOver( type ) {
	cur = type.toLowerCase();
	document.getElementById(cur).style.cursor = 'pointer';
	document.getElementById(cur).bgColor = '#888888';
   }
   function mOut( type ) {
	cur = type.toLowerCase();
	document.getElementById(cur).bgColor = '#FFFFFF';
   }

   function initChecks() { 
    document.deletef.filesCheck.disabled = $filesCheck;
    document.deletef.foldersCheck.disabled = $foldersCheck;
    document.deletef.cabinetsCheck.disabled = $cabinetsCheck;
   }
                                                                                                                             
   function filesRadioClicked() {
    if(document.deletef.filesToggle[0].checked == true)
        document.deletef.filesCheck.disabled = false;
    if(document.deletef.filesToggle[1].checked == true)
        document.deletef.filesCheck.disabled = true;
   } 
                                                                                                                             
   function foldersRadioClicked(deleteform) {
    if(document.deletef.foldersToggle[0].checked == true)
        document.deletef.foldersCheck.disabled = false;
    if(document.deletef.foldersToggle[1].checked == true)
        document.deletef.foldersCheck.disabled = true;
   }
                                                                                                                             
   function cabinetsRadioClicked(deleteform) {
    if(document.deletef.cabinetsToggle[0].checked == true)
        document.deletef.cabinetsCheck.disabled = false;
    if(document.deletef.cabinetsToggle[1].checked == true)
        document.deletef.cabinetsCheck.disabled = true;
   }
                                                                                                                             
   function filesCheckClicked() {
    document.deletef.filesWarnChecked.value = document.deletef.filesCheck.checked;
   }

   function foldersCheckClicked() {
    document.deletef.foldersWarnChecked.value = document.deletef.foldersCheck.checked;
   }
                                                                                                                             
   function cabinetsCheckClicked() {
    document.deletef.cabinetsWarnChecked.value = document.deletef.cabinetsCheck.checked;
   }
  </script>
 </head>
 <body onload="initChecks()">
ENERGIE;
	/////////Delete File Options HTML///////////
echo<<<ENERGIE
  <form name="deletef" method="POST" action="sysAllThumbsIcons.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="4" class="tableheads">$delFileOpts</td>
    </tr>
    <tr>
     <td class="admin-tbl">
      $delFiles
     </td>
     <td>
      <input type="radio" name="filesToggle" value=1 $filesEnable onclick="filesRadioClicked()">
      $enable
     </td>
     <td>
      <input type="radio" name="filesToggle" value=0 $filesDisable onclick="filesRadioClicked()">
      $disable
     </td>
     <td>
      <input type="checkbox" name="filesCheck" value=0 $filesTic onclick="filesCheckClicked()">
      <input type="hidden" name="filesWarnChecked" value=$filesWarn >
      $confirm
     </td>
    </tr>
    <tr>
     <td class="admin-tbl">
      $delFolders
     </td>
     <td>
      <input type="radio" name="foldersToggle" value=1 $foldersEnable onclick="foldersRadioClicked()">
      $enable
     </td>
     <td>
      <input type="radio" name="foldersToggle" value=0 $foldersDisable onclick="foldersRadioClicked()">
      $disable
     </td>
     <td>
	  <input type="checkbox" name="foldersCheck" value=0 $foldersTic onclick="foldersCheckClicked()">
      <input type="hidden" name="foldersWarnChecked" value=$foldersWarn >
      $confirm
     </td>
    </tr>
    <tr>
     <td class="admin-tbl">
      $delCabinets
     </td>
     <td>
      <input type="radio" name="cabinetsToggle" value=1 $cabinetsEnable onclick="cabinetsRadioClicked()">
      $enable
     </td>
     <td>
      <input type="radio" name="cabinetsToggle" value=0 $cabinetsDisable onclick="cabinetsRadioClicked()">
      $disable
     </td>
     <td>
      <input type="checkbox" name="cabinetsCheck" value=0 $cabinetsTic onclick="cabinetsCheckClicked()">
      <input type="hidden" name="cabinetsWarnChecked" value=$cabinetsWarn >
      $confirm
     </td>
    </tr>
    <tr>
     <td colspan="4">
ENERGIE;
    if( $message2 != NULL )
        echo "<div class=\"error\">$message2\n";
    else
        echo "<div>\n";
echo<<<ENERGIE
      <input name="changeDelete" type="submit" value=$changeSett"></div>
     </td>
    </tr>
   </table>
  </center>
  </form>
ENERGIE;
	/////////////File Option Settings HTML/////////////////////
echo<<<ENERGIE
  <form id="sysf" name="sysf" method="POST" action="sysAllThumbsIcons.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="4" class="tableheads">$systemATIconsTitle</td>
    </tr>
    <!-- HEADER -->
    <tr>
     <td>
      $fileOptionName
     </td>
     <td id="none" onmouseover="mOver('NONE');" onmouseout="mOut('NONE');" onclick="selectRights('NONE');">
      $doNotShow
     </td>
     <td id="admin" onmouseover="mOver('ADMIN');" onmouseout="mOut('ADMIN');" onclick="selectRights('ADMIN');">
      $adminOnly
     </td>
     <td id="all" onmouseover="mOver('ALL');" onmouseout="mOut('ALL');" onclick="selectRights('ALL');">
      $allUsers
     </td>
    </tr>
    <!-- PDF ICON -->
    <tr>
     <td>
      $getPDF
     </td>
     <td>
      <input type="radio" name="pdfIcon" value="none" $pdfnone>
     </td>
     <td>
      <input type="radio" name="pdfIcon" value="admin" $pdfadmin>
     </td>
     <td>
      <input type="radio" name="pdfIcon" value="all" $pdfall>
     </td>
    </tr>
    <!-- ZIP ICON -->
    <tr>
     <td>
      $getZIP
     </td>
     <td>
      <input type="radio" name="zipIcon" value="none" $zipnone>
     </td>
     <td>
      <input type="radio" name="zipIcon" value="admin" $zipadmin>
     </td>
     <td>
      <input type="radio" name="zipIcon" value="all" $zipall>
     </td>
    </tr>
    <!-- DELETE ICON -->
    <tr>
     <td>
      $delFile
     </td>
     <td>
      <input type="radio" name="deleteIcon" value="none" $deletenone>
     </td>
     <td>
      <input type="radio" name="deleteIcon" value="admin" $deleteadmin>
     </td>
     <td>
      <input type="radio" name="deleteIcon" value="all" $deleteall>
     </td>
    </tr>
    <!-- MOVE FILE ICON -->
    <tr>
     <td>
      $mvFile
     </td>
     <td>
      <input type="radio" name="moveIcon" value="none" $movenone>
     </td>
     <td>
      <input type="radio" name="moveIcon" value="admin" $moveadmin>
     </td>
     <td>
      <input type="radio" name="moveIcon" value="all" $moveall>
     </td>
    </tr>
    <!-- NEW TAB ICON -->
    <tr>
     <td>
      $crNewTab
     </td>
     <td>
      <input type="radio" name="tabsIcon" value="none" $tabnone>
     </td>
     <td>
      <input type="radio" name="tabsIcon" value="admin" $tabadmin>
     </td>
     <td>
      <input type="radio" name="tabsIcon" value="all" $taball>
     </td>
    </tr>
    <!-- UPLOAD ICON -->
    <tr>
     <td>
      $upload
     </td>
     <td>
      <input type="radio" name="uploadIcon" value="none" $uploadnone>
     </td>
     <td>
      <input type="radio" name="uploadIcon" value="admin" $uploadadmin>
     </td>
     <td>
      <input type="radio" name="uploadIcon" value="all" $uploadall>
     </td>
    </tr>
	<!-- Order ICON -->
    <tr>
     <td>
      $getOrder
     </td>
     <td>
      <input type="radio" name="orderIcon" value="none" $ordernone>
     </td>
     <td>
      <input type="radio" name="orderIcon" value="admin" $orderadmin>
     </td>
     <td>
      <input type="radio" name="orderIcon" value="all" $orderall>
     </td>
    </tr>
	<!-- Delete Cabinets ICON -->
    <tr>
     <td>
      $delCabinets
     </td>
     <td>
      <input type="radio" name="delCabIcon" value="none" $delCabnone>
     </td>
     <td>
      <input type="radio" name="delCabIcon" value="admin" $delCabadmin>
     </td>
     <td>
      <input type="radio" name="delCabIcon" value="all" $delCaball>
     </td>
    </tr>
    <tr>
     <td colspan="4">
ENERGIE;
	if( $message3 != NULL )
        echo "<div class=\"error\">$message3\n";
	else
		echo "<div>\n";
echo<<<ENERGIE
      <input name="changeSettings" type="submit" value=$changeSett"></div>
     </td>
    </tr>
   </table>
  </center>
  </form>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else{
	logUserOut();
}
?>
