<?php 
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../lib/mime.php';
include_once '../lib/versioning.php';

if ($logged_in == 1 && strcmp($user -> username, "") != 0) {
	if (isset ($_GET['ID'])) {
		$ID = $_GET['ID'];
	} else {
		$ID = '';
	}
	if (isset($_GET['pop'])) {
		$popup = $_GET['pop'];
	} else {
		$popup = ''; 
	}
	if (isset($_GET['fileID'])) {
		$fileID = $_GET['fileID'];
	} else {
		$fileID = '';
	}
	if (isset($_GET['download'])) {
		$download = $_GET['download'];
	} else {
		$download = '';
	}
	$cab = $_GET['cab'];
	if (isset($_GET['filename'])) {
		$filename = $_GET['filename'];
	} else {
		$filename = ''; 
	}
	if(isset($_GET['doc_id'])) {
		$doc_id = $_GET['doc_id'];
	} else {
		$doc_id = '';
	}
	if (isset ($_GET['tab'])) {
		$tab = $_GET['tab'];
	} else {
		$tab = '';
	}
	if(isset($_GET['docView']) and $_GET['docView'] == 1) {
		$inlineFrame = 'viewFileActions';
	} else {
		$inlineFrame = 'mainFrame';
	}

	//This is a problem. We need more consistency in the 'main' tab. If it is
	//main, is it an empty string, or is it 'main'? In this file, it is supposed
	// to be blank, but it is passed in as 'main'.
	if($tab == 'main') {
		$tab = '';
	}

	//if filename is null, get it
	$delete = 0;
	if($filename=="") {
		if($fileID) {
			$row = getTableInfo($db_object, $cab.'_files', array(), array('id' => (int) $fileID), 'queryRow');
			
		} else {
			$whereArr = array(
				"doc_id"	=> (int)$doc_id,
				"ordering"	=> (int)$ID,
				"display"	=> 1,
				"deleted"	=> 0,
				'filename'	=> 'IS NOT NULL'
					 );
			if($tab=="") {
				$whereArr["subfolder"] = 'IS NULL';
			} else {
				$whereArr["subfolder"] = $tab;
			}
			$row = getTableInfo($db_object,$cab."_files",array(),$whereArr,'queryRow');
		}
		$filename = $row['filename'];
	} else {
		$delete = 1;
	}
	
	$whereArr = array('doc_id'=>(int)$doc_id);
	$res = getTableInfo($db_object,$cab,array(),$whereArr);

	$row2 = $res->fetchRow();
	$loc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $row2['location']).'/';
	if($row['subfolder']) {
		$loc .= $row['subfolder'].'/';
	}


	$file_type=getMimeType($loc.$filename, $DEFS);	//get the mime file type
	if($file_type!="image/tiff") { 
		if( $file_type == "application/pdf" ) {
			if($download == 1) {
				echo "<script>";
				echo "parent.leftFrame1.window.location='readfile.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
				$tempFilename = urlencode($filename);
				echo "&filename=$tempFilename&delete=$delete&tmp=0/$tempFilename'";
				echo "</script>";
			} else {
				echo "<script>";
				echo "parent.$inlineFrame.window.location='readfile.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
				$tempFilename = urlencode($filename);
				echo "&filename=$tempFilename&delete=$delete&tmp=0/$tempFilename'";
				echo "</script>";
			}
			die();
		} elseif( $file_type == "application/x-zip" ) {
			echo "<script>";
			echo "parent.topFrame.window.location='readfile.php?doc_id=$doc_id";
			echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
			echo "&filename=$filename&delete=$delete';";
			echo "</script>";
			die();
		} else {
			if($download == 1) {
				echo "<script>";
				echo "parent.leftFrame1.window.location='readfile.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
				echo "&delete=$delete';";
				echo "</script>";
			} elseif($file_type == "audio/x-wav" || $file_type == "video/x-avi") {
				echo "<script>";
				echo "parent.topFrame.window.location='readfile.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=1";
				echo "&filename=$filename&delete=0';";
				echo "</script>";
			} elseif($file_type == "image/jpeg") {
				echo "<script>";
				echo "parent.$inlineFrame.window.location='display2.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
				echo "&delete=$delete';";
				echo "</script>";
			} else {
				$tempFilename = urlencode($filename);
				echo "<script>";
				echo "parent.$inlineFrame.window.location='readfile.php?doc_id=$doc_id";
				echo "&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download";
				echo "&delete=$delete&tmp=0/$tempFilename';";
				echo "</script>";
			}
			die();
		}
	} elseif($file_type == "image/tiff" && $download == 1) {
		echo "<script>";
		echo "parent.leftFrame1.window.location=\"readfile.php?doc_id=$doc_id&fileID=$fileID";
		echo "&tab=$tab&cab=$cab&ID=$ID&download=$download&delete=$delete\";";
		echo "</script>";
		die();
	}
	//get user restrictions
	$userStt = new Usrsettings($user->username, $user->db_name);
	$num = $userStt->get('viewRestrict');

	if($num) {
		$require_alt_user = $userStt->get('requireAlt');
	} else {
		$require_alt_user="";
	}

	//if user has RO access, check RO settings for restrictions
	if($user->checkSecurity($cab)==1) {
		$gblStt = new GblStt($user->db_name, $db_doc);
		$num = $gblStt->get('roViewRestrict');
	    if($num){
			$require_alt_ro = $gblStt->get('requireAlt');
		}
	}
	else	
		$require_alt_ro="";

    	echo<<<ENERGIE
	<html>
		<body topMargin=0 bottomMargin=0 leftMargin=0 rightMargin=0 scroll=no>
    	<script type="text/vbscript" language="VBScript">
    		On Error Resume Next
    		detected = IsObject(CreateObject("Alttiff.AlttiffCtl"))
				if detected Then
    			''alternatiff is installed, continue normally
ENERGIE;
	if($download != 1) {
		//write tag with restrictions
		if($num!=0) {
			echo<<<ENERGIE

		document.write( "<embed toolbar=top width=100% height=100% access=$num type=image/tiff align=center src='readfile.php?")
      		document.write( "doc_id=$doc_id&fileID=$fileID&tab=$tab&" )
      		document.write( "cab=$cab&ID=$ID&download=1&delete=$delete'>" )

ENERGIE;
		} else {
				echo<<<ENERGIE

	document.write( "<embed toolbar=top width=100% height=100% type=image/tiff align=center src='readfile.php?")
      document.write( "doc_id=$doc_id&fileID=$fileID&tab=$tab&" )
      document.write( "cab=$cab&ID=$ID&download=1&delete=$delete'>" )

ENERGIE;
		}
	} else {//new frame (clicked the disk)
		if(($require_alt_user=="on")||($user->checkSecurity($cab)==1&&$require_alt_ro=="on")) {
			echo <<<ENERGIE

		parent.$inlineFrame.window.location="needAT.php"

ENERGIE;
		} else {
echo<<<ENERGIE

	  parent.$inlineFrame.window.location="readfile.php?doc_id=$doc_id&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=1&delete=$delete"
   
ENERGIE;
		}
	}
	echo<<<ENERGIE

			else
				''redirect to alternatiff installation
				''MsgBox "loading install page - detected is " & detected
				parent.$inlineFrame.window.location = "installAT.php?refPage=0&doc_id=$doc_id&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download"		
				
			end If
    	</script>
		<script type="text/javascript">
			function displayOne() {
ENERGIE;
		if(($require_alt_user=="on")||($user->checkSecurity($cab)==2&&$require_alt_ro=="on")) {
			echo"parent.$inlineFrame.window.location='needAT.php';";
		} else {
			if($download != 1) {
			  //echo "parent.$inlineFrame.window.location=\"$filename\"";
			  echo "parent.$inlineFrame.window.location=\"readfile.php?doc_id=$doc_id&fileID=$fileID";
			  echo "&tab=$tab&cab=$cab&ID=$ID&download=$download&delete=$delete\";";
			} else {
			//dont allow download if it is restricted
				echo "window.location=\"readfile.php?doc_id=$doc_id&fileID=$fileID";
				echo "&tab=$tab&cab=$cab&ID=$ID&download=1&delete=$delete\";";
			}
		}
echo<<<ENERGIE
			}
			if(!document.all) {
				document.onload=displayOne();
			}
		</script>		
		</body>
	</html>
ENERGIE;
} else { //end of if that checks if you are logged in
	logUserOut();
}
?>
