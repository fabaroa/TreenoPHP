<?php
include_once '../classuser.inc';
include_once '../check_login.php';
include_once '../lib/energie.php';
include_once '../groups/groups.php';

$badPass = '';

echo<<<ENERGIE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title></title>
<link rel="icon" href="../images/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
ENERGIE;
if($logged_in==0) {
    echo "\n<frameset cols=\"100%\">";
    if( isset($_GET['incorrect_password']) and $_GET['incorrect_password'] != "" ) {
        $badPass .= "incorrect_password=".$_GET['incorrect_password'];
    }
    if (isset($_GET['message']))
	$badPass .="?message=".$_GET['message'];
    if(isset($_GET['autosearch'])) {
		if(strpos($badPass, '?') !== false) {
			$badPass .= "&";
		} else {
			$badPass .= "?";
		}
		$badPass .= "autosearch={$_GET['autosearch']}";
		if(isset($_GET['cabinet'])) {
			$badPass .= "&cabinet={$_GET['cabinet']}";
		}
		$badPass .= '&newLogin=1';
    } elseif( isSet( $_GET['link'] ) ) {
		if(strpos($badPass, '?') !== false)
			$badPass .= "&";
		else
			$badPass .= "?";
	
		if (isset ($_GET['department'])) {
			$badPass .= "department={$_GET['department']}";
		}
		if (isset ($_GET['cab'])) {
			$badPass .= "&cab={$_GET['cab']}";
		}
		if (isset ($_GET['doc_id'])) {
			$badPass .= "&doc_id={$_GET['doc_id']}";
		}
		if (isset ($_GET['fileID'])) {
			$badPass .= "&fileID={$_GET['fileID']}";
		}
		$badPass .= "&link={$_GET['link']}";
		$badPass .= "&wf={$_GET['wf']}";
    } elseif( isset($_GET['MASSearch']) ) {
		if(strpos($badPass, '?') !== false) {
			$badPass .= "&";
		} else {
			$badPass .= "?";
		}
        $badPass .= "MASSearch={$_GET['MASSearch']}";
		if(isset($_GET['cabinet'])) {
            $badPass .= "&cabinet={$_GET['cabinet']}";
        }
    }
    echo "\n   <frame src=\"../login.php$badPass\">";
    echo "</frameset>";
} else {

		echo <<<ENERGIE
<frameset rows="25,100%,*" cols="*" border="0" frameborder="0" framespacing="0">
  <frame src="menuSlide_NewUI.php" name="topMenuFrame" scrolling="no" noresize>
  <frameset id="afterMenu" rows="*" cols="260,*" frameborder="1" framespacing="5" border="5">
    	<frame src="../secure/leftAdmin.php" id="searchPanel" name="searchPanel">
  	<frameset id="mainFrameSet" rows="*" cols="100%,*" frameborder="0" framespacing="0" border="0">
    		<frameset id="folderViewSet" rows="100%,*" frameborder="0" framespacing="0" border="0">
			<frame src="../modules/userInfo.php" name="mainFrame">
		</frameset>  
   		<frameset id="rightFrame" rows="*,20" cols="*" framespacing="0" frameborder="0" border="0">
	   		<frame src="bottom_white.php" id="fileFrame" name="sideFrame" scrolling="no">
	   		<frame src="bottom_white.php" name="bottomFrame" scrolling="no" >
	 	</frameset> 
 	</frameset>
  </frameset>
  <frameset cols="*,*,*" rows="*">
	<frame src="blue_bar.php" name="topFrame">
	<frame src="main_menu.php" name="menuFrame">
	<frame src="blue_bar.php" id="leftFrame1" name="leftFrame1">
  </frameset>
</frameset>	
ENERGIE;

}
echo "</html>\n";
if(isset($user)) {
	if(isset($_GET['NewUI'])) {	
		$user->access = array();
		$user->cabArr = array();
		$user->fillUser(null,$_GET['department']);
	}

	setSessionUser($user);
}
?>
