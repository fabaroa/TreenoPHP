<?php
include_once '../classuser.inc';
include_once '../check_login.php';
include_once '../lib/energie.php';
include_once '../groups/groups.php';

if( isset($_GET['custfunc'])){
	if( file_exists($DEFS['CUSTINC'])){	
		include_once $DEFS['CUSTINC'.$i];
		$class_methods = get_class_methods( 'CUSTINC' );
		if( in_array($_GET['custfunc'], $class_methods)){
			CUSTINC::$_GET['custfunc']();
		}
	}
}

$xmlStr = file_get_contents('php://input');
//error_log("Data posted to energie: ".$xmlStr);

$badPass = '';
if( $logged_in != 0 ) {
	if( isSet( $_GET['link'] ) ) {
		if( isset($_GET['todoID']) AND $_GET['todoID'] != NULL ) {
			$whereArr = array('id'=>(int)$_GET['todoID']);
			$todoList =  getTableInfo($db_doc,'wf_todo',array(),$whereArr,'queryRow');

			if($todoList and $user->greaterThanUser($todoList['username'])) {
				$wf = 0;
				if($user->db_name == $todoList['department']) {
					$doDisconnect = false;
					$wfClientDB = $db_object;
				} else {
					$doDisconnect = true;
					$wfClientDB = getDbObject($todoList['department']);
				}
				$whereArr = array('id'=>(int)$todoList['wf_document_id']);
				$wfInfo = getTableInfo($wfClientDB,'wf_documents',array(),$whereArr,'queryRow');
				if($doDisconnect) {
					$wfClientDB->disconnect ();
				}
				if($wfInfo) {
					$wf = 1;				
					$_GET['cab'] = $wfInfo['cab'];
					$_GET['doc_id'] = $wfInfo['doc_id'];
					$_GET['fileID'] = $wfInfo['file_id'];
					if($wfInfo['file_id'] > 0) {
						$_GET['documentView'] = 1;
					}
				}

				if($user->db_name != $todoList['department']) { 
					$user->fillUser(false,$todoList['department']);
				}
				
				$user->cab = $wfInfo['cab'];
				$user->doc_id = $wfInfo['doc_id'];
				$user->file_id = $wfInfo['file_id'];
				if($user->access[$wfInfo['cab']] != 'rw') {
					$user->access = array();
					$user->cabArr = array();
					$user->fillUser($wf,$todoList['department']);
					$user->setSettings();
					$user->restore = 1;
					
					$settArr = array('deleteFolders','editFolder','moveFiles','deleteFiles',
									 'redactFiles','versioning','viewNonRedact');
					foreach($user->userSettings[$wfInfo['cab']] AS $k => $v) {
						if(in_array($v,$settArr)) {
							unset($user->userSettings[$wfInfo['cab']][$k]);
						}
					}
					$user->userSettings[$wfInfo['cab']] = array_values($user->userSettings[$wfInfo['cab']]);
				}
				$user->todoID = $_GET['todoID'];
			} else {
				unset($todoList['department']);
				$user->audit("ILLEGAL ACCESS","typed in an invalid link");
			}
		} else {
			$user->access = array ();
			$user->cabArr = array ();
			if ($_GET['department']) {
				$user->fillUser (true, $_GET['department']);
			}
		}
	} elseif(isset($_GET['legint'])) {
		$user->access = array();
		$user->cabArr = array();
		$user->fillUser(null,$_GET['department']);
	}
	setSessionUser($user, false);
}
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

echo "\n <frameset rows=\"25,100%,*\" cols=\"*\" border=\"0\" frameborder=\"0\" framespacing=\"0\">\n";
echo "   <frame src=\"menuSlide.php\" name=\"topMenuFrame\" scrolling=\"no\" noresize>\n";
echo "\n <frameset id=\"afterMenu\" rows=\"*\" cols=\"260,*\" frameborder=\"1\" framespacing=\"5\" border=\"5\">\n";
echo "   <frame src=\"searchPanel.php\" id=\"searchPanel\" name=\"searchPanel\">\n";
echo "   <frameset id=\"mainFrameSet\" rows=\"*\" cols=\"100%,*\" frameborder=\"0\" framespacing=\"0\" border=\"0\">\n";
echo "   <frameset id=\"folderViewSet\" rows=\"100%,*\" frameborder=\"0\" framespacing=\"0\" border=\"0\">\n";

if( isset($_GET['autosearch'])) {
	$search = $_GET['autosearch'];
	$cabinet = $_GET['cabinet'];
	if(isset($_GET['cabinet'])) {
		echo <<<ENERGIE
<frame src="searchResults.php?cab=$cabinet&barcode=%22$search%22" name="mainFrame">
ENERGIE;
	} else {
		$vbsearch = explode(",", $search);
		if (!isset($vbsearch))
			echo "         <frame src=\"search_frame.php\" name=\"mainFrame\">\n";
		else
			echo "         <frame src=\"topLevelSearch.php?autosearch=$search\" name=\"mainFrame\" >\n";
	}
} elseif( isset( $_GET['MASSearch'] ) ) {
	$search = $_GET['MASSearch'];
	$cabinet = $_GET['cabinet'];
	if(isset($_GET['cabinet'])) {
		echo <<<ENERGIE
<frame src="searchResults.php?cab=$cabinet&barcode=$search" name="mainFrame">
ENERGIE;
	} else {
			echo "         <frame src=\"topLevelSearch.php?autosearch=$search\" name=\"mainFrame\" >\n";
	}
} elseif( isSet( $_GET['link'] ) && !empty ($_GET['cab'])) {
	if (isset ($_GET['documentView'])) {
		$docView = $_GET['documentView'];
	} else {
		$docView = '';
	}
	echo "	<frame src=\"searchResults.php?link={$_GET['link']}&cab={$_GET['cab']}&";
	echo "doc_id={$_GET['doc_id']}&fileID={$_GET['fileID']}&documentView={$docView}\" name=\"mainFrame\" >\n";
}elseif(isset($_GET['legint']) && isset($_GET['cab'])) {
	if(isSet($_SESSION['integrationSearch']['file']))  {
		$_SESSION['fsrArray'] = $_SESSION['integrationSearch'];
		echo "	<frame src=\"file_search_results.php?cab={$_GET['cab']}\" name=\"mainFrame\">\n";
	} else {
		echo "	<frame src=\"searchResults.php?legint={$_GET['legint']}&cab={$_GET['cab']}\" name=\"mainFrame\">\n";
	}
} else {
	echo "            <frame src=\"search_frame.php\" name=\"mainFrame\" >\n";	
}
echo "	 <frame src='../documents/viewFile.php' name='viewFileActions'>\n";
echo "</frameset>";
echo "   <frameset id=\"rightFrame\" rows=\"*,20\" cols=\"*\" framespacing=\"0\" frameborder=\"0\" border=\"0\">\n ";
echo "   <frame src=\"bottom_white.php\" id=\"fileFrame\" name=\"sideFrame\" scrolling=\"no\">\n";
//should rename above set rightFrame
echo "   <frame src=\"bottom_white.php\" name=\"bottomFrame\" scrolling=\"no\" >\n";
echo " </frameset>\n";//closes frameset 4 lines above
echo " </frameset>\n";//closes frameset second frameset
echo " </frameset>\n";//closes the first frameset
echo "<frameset cols=\"*,*,*\">";
echo "<frame src=\"blue_bar.php\" name=\"topFrame\">\n";
echo "<frame src=\"main_menu.php\" name=\"menuFrame\">\n";
echo "<frame src=\"blue_bar.php\" name=\"leftFrame1\">\n";
echo "</frameset>";
echo " </frameset>\n";//closes the first frameset
}
echo "</html>\n";
if(isset($user)) {
	setSessionUser($user);
}
?>
