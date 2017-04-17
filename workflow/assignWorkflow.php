<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'node.inc.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0) {
	$cab 		= $_GET['cab'];
	$doc_id 	= (int)$_GET['doc_id'];
	if (isset($_GET['file_id'])) {
		$file_id 	= (int)$_GET['file_id'];
	} else {
		$file_id = 0;
	}

	$needAction = 0;
	$message = '';
	$db_object = $user->getDbObject();
	$defsList =  getTableInfo($db_object,'wf_defs',array('DISTINCT(defs_name)'),array(),'queryCol',array('defs_name'=>'ASC'));
	if( !$defsList ) {
		$message = "There is no workflow setup";
		$needAction = 0;
	}

	$URL = "?cab=$cab&amp;doc_id=$doc_id&amp;file_id=$file_id";

	if( isSet( $_POST['B1'] ) ) {
		if(isset($_POST['wfEntireFolder'])) {
			$file_id = -2;
		} elseif($file_id) {
			$file_id = (int)$file_id;
		} else {
			$file_id = -1;
		}
		$arr            = getWFDefsInfo( $db_object, $_POST['workflow'] );
		$wf_def_id      = (int)$arr[1];
		$wf_doc_id		= (int)addToWorkflow( $db_object, $wf_def_id, $doc_id, $file_id, $cab, $user->username );
		if($wf_doc_id != "-1") {
			$cabDispName = $user->cabArr[$cab];
			$stateNodeObj   = new stateNode($db_object,$user->db_name,$user->username,$wf_doc_id,$wf_def_id,$cab,$cabDispName,$doc_id,$db_doc,$file_id);
			$stateNodeObj->notify();
		} else {
			$sArr = array('id');
			$wArr = array(	"cab='".$cab."'",
							"doc_id=".(int)$doc_id,
							"status!='COMPLETED'");
			if(isSet($_POST['wfEntireFolder'])) {
				$wArr[] = 'file_id='.-2;
			} elseif($file_id) {
				$wArr[] = 'file_id='.(int)$file_id;
			} else {
				$wArr[] = 'file_id='.-1;
			}
			$wf_doc_id = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne');
		}
		
		$userList = getUsers( $db_object, $wf_doc_id );
		$owner = getWFOwner($db_object,$wf_doc_id);	
		if(sizeof($userList) > 0) {
			if(in_array( $user->username, $userList )) {
				$needAction = 2;
			} else if($owner == $user->username && in_array('Workflow Owner',$userList)) {
				$needAction = 2;
			} else {
				$needAction = 1;
			}
		} else {
			$needAction = 1;
		}
		$message = "Workflow successfully assigned";
	}
echo<<<ENERGIE
<html>
 <head>
  <script>
  	var needAction = "$needAction"; 
    function redirect() {
		if( needAction == 1 ) {
			if(parent.sideFrame.addWorkflowHistoryIcon) {
				parent.sideFrame.addWorkflowHistoryIcon();
				parent.sideFrame.removeWorkflowIcon();
			} else {
				workflow1 = parent.sideFrame.document.getElementById('workflow1');
				while(workflow1.childNodes[0]) {
					workflow1.removeChild( workflow1.childNodes[0] );
				}

				var wfImage = parent.sideFrame.document.createElement("img");
				wfImage.src = '../images/addbk_16.gif';
				wfImage.title = 'Workflow History';
				wfImage.alt = 'Workflow History';
				wfImage.onclick = parent.sideFrame.viewWorkflowHistory; 
				workflow1.appendChild( wfImage );
			}
		} else if(needAction == 2){
			if(parent.sideFrame.addWorkflowHistoryIcon) {
				parent.sideFrame.addWorkflowHistoryIcon();
				parent.sideFrame.addWorkflow();
			} else {
				workflow1 = parent.sideFrame.document.getElementById('workflow1');
				while(workflow1.childNodes[0]) {
					workflow1.removeChild( workflow1.childNodes[0] );
				}

				var td = parent.sideFrame.document.getElementById('workflowHistory');
				if(td) {
					while(td.childNodes[0]) {
						td.removeChild(td.childNodes[0]);
					}
				} else {
					var td = parent.sideFrame.document.createElement('td');
					td.id = 'workflowHistory';
					workflow1.parentNode.insertBefore(td,workflow1);
				}
									
				var wfImage = parent.sideFrame.document.createElement("img");
				wfImage.src = '../images/addbk_16.gif';
				wfImage.title = 'Workflow History';
				wfImage.alt = 'Workflow History';
				wfImage.onclick = parent.sideFrame.viewWorkflowHistory; 
				td.appendChild( wfImage );

				var wfImage = parent.sideFrame.document.createElement("img");
				wfImage.id = 'workflow2';
				wfImage.src = '../images/edit_24.gif';
				wfImage.title = 'Sign Folder/Files';
				wfImage.alt = 'Sign Folder/Files';
				wfImage.onclick = parent.sideFrame.enterWorkflow; 
				workflow1.appendChild( wfImage );
			}
			window.location = 'getSignature.php?cab=$cab&doc_id=$doc_id&file_id=$file_id';
		}
    }
  </script>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 </head>
ENERGIE;
  	if( $message ){
		echo "<body onload=\"redirect()\">";
		echo "<div align=\"center\" class=\"error\">$message</div>\n";
	} else {
echo<<<ENERGIE
 <body class="centered">
  <div class="mainDiv">
   <div class="mainTitle">
    <span>Assign Workflow</span>
   </div>
   <div class="inputForm" style="margin-bottom: 0px;">
    <form name="assignWF" method="post" action="assignWorkflow.php$URL">
	 <table>
	  <tr>
	   <td style="border-width:0px" align="right">Select Workflow</td>
	   <td style="border-width:0px">
	    <select name="workflow">
ENERGIE;
		foreach( $defsList AS $wfname ) {
			$wfname = h($wfname);
			echo "<option value=\"$wfname\">".str_replace("_"," ",$wfname)."</option>";
		}
echo<<<ENERGIE
		</select>
	   </td>
	  </tr>
	  <tr>
ENERGIE;
	if($user->checkSetting('documentView', $cab)) {
		echo<<<ENERGIE
	   <td style="border-width:0px">
		<input type="checkbox" name="wfEntireFolder" value="1" />
		<span>Entire folder</span>
		</td>
		<td style="border-width:0px" align="right">
		 <input type="submit" name="B1" value="Assign">
		</td>
ENERGIE;
	} else {
		echo<<<ENERGIE
		<td style="border-width:0px" colspan="2" align="right">
		 <input type="submit" name="B1" value="Assign">
		</td>
ENERGIE;
	}
echo<<<ENERGIE
	  </tr>
	 </table>
    </form>
   </div>
  </div>
ENERGIE;
	}
echo<<<ENERGIE
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
