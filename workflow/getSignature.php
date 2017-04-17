<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/filter.php';
include_once 'signode.php';

if($logged_in and $user->username) {
	$actionNeeded = false;
	$wfFinished = false;
	$ownerActionNeeded = false;
	$mess = '';

	$submit = "";
	if(isSet($_POST['Submit'])) {
		$submit = $_POST['Submit'];
	}

	$cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$file_id = !empty($_GET['file_id']) ? $_GET['file_id'] : -1;

	$sArr = array('count(id)');
	$wArr = array(	"cab = '$cab'",
					'doc_id ='.(int)$doc_id,
					'file_id ='.(int)$file_id,
					"status != 'COMPLETED'");
	$ct = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne'); 
	if(!$ct) {
		$file_id = -2;	
	}

	$documentInfo = getWorkflowIDs( $db_object, $cab, $doc_id, $file_id );
	if ($documentInfo) {
		$nodeClass = getWFNodeType($db_object, $documentInfo['id']).'Node';
		$cabDispName = getTableInfo($db_object, 'departments', array('departmentname'),
			array('real_name' => $cab), 'queryOne');
		$nodeObj = new $nodeClass($db_object, $user->db_name, $user->username, $documentInfo['id'], $documentInfo['state_wf_def_id'], $cab, $cabDispName, $doc_id,$db_doc,$file_id);
		//Check that username is one of the users that can sign off the wf
		if( $nodeObj->isAssignedUser($user->username, $user->db_name, $db_doc) ) {
			$gblStt = new GblStt ($user->db_name, $db_doc);
			if( $submit == "Accept" ){
				$nodeObj->accept();
				$documentInfo = getWorkflowIDs( $db_object, $cab, $doc_id,$file_id );
				$nodeClass = getWFNodeType($db_object, $documentInfo['id']).'Node';
				$nodeObj = new $nodeClass($db_object, $user->db_name, $user->username, $documentInfo['id'], $documentInfo['state_wf_def_id'], $cab, $cabDispName,$doc_id,$db_doc,$file_id);
				if ($nodeClass != "FINALNode" && isset ($_POST['reassigned_owner']) && 
					$_POST['reassigned_owner'] != '__default') {
		
					if ($nodeObj->reassignNode ($_POST['reassigned_owner'], $db_doc)) {
						$nodeObj = new $nodeClass($db_object, 
							$user->db_name, $user->username, $documentInfo['id'],
							$documentInfo['state_wf_def_id'], $cab, $cabDispName,$doc_id,$db_doc,$file_id);
					}

				}
			} elseif( $submit == "Reject" 
				&& $_POST['notes'] != "Enter notes here" 
				&& $_POST['notes'] != NULL ) {
				$nodeObj->reject();
				$documentInfo = getWorkflowIDs( $db_object, $cab, $doc_id,$file_id );
				$nodeClass = getWFNodeType($db_object, $documentInfo['id']).'Node';
				$nodeObj = new $nodeClass($db_object, $user->db_name, $user->username, $documentInfo['id'], $documentInfo['state_wf_def_id'], $cab, $cabDispName,$doc_id,$db_doc,$file_id);
				if (isset ($_POST['reassigned_owner']) && 
					$_POST['reassigned_owner'] != '__default') {

	
					if ($nodeObj->reassignNode ($_POST['reassigned_owner'], $db_doc)) {
						$nodeObj = new $nodeClass($db_object, 
							$user->db_name, $user->username, $documentInfo['id'],
							$documentInfo['state_wf_def_id'], $cab, $cabDispName,$doc_id,$db_doc,$file_id);
					}

				}
			} elseif( $submit == "Reject" ) {
				$mess = "Must include note to reject document"; 
			} elseif (isset ($_POST['reassign_now'])) {
				if ($_POST['reassigned_owner'] != '__default') {
					if ($nodeObj->reassignNode ($_POST['reassigned_owner'], $db_doc)) {
						$nodeObj = new $nodeClass($db_object, $user->db_name, $user->username, $documentInfo['id'], $documentInfo['state_wf_def_id'], $cab, $cabDispName,$doc_id,$db_doc,$file_id);
					} else {
						$mess = 'Error Reassigning Node';
					}
				}	
			}
			$reassignList = array ();
			if ($gblStt->get ('reassign_owner')) {
				$whereArr = array (
					'state_wf_def_id = wf_defs.id',
					'node_id = wf_nodes.id',
					'user_list_id = user_list.list_id',
					'wf_documents.id = ' . $documentInfo['id']
				);
				$userList = getTableInfo ($db_object, array ('wf_documents', 
					'wf_defs', 'wf_nodes', 'user_list'), array ('username',
					'wf_documents.owner AS owner'), $whereArr, 'queryAll');
				if ((count ($userList) == 1 && 
					$userList[0]['username'] == 'Workflow Owner' &&
					$userList[0]['owner'] == $user->username) or count ($userList) == 0) {
				
					$reassignList = getTableInfo ($db_object, 'access',
						array ('username'),
						array ("username != '".$user->username."'"),
						'queryCol', array ('username' => 'ASC'));

				}
			}	
			if(strtolower($nodeClass) == "finalnode") {
				$wfFinished = true;
			}
			
			$workflowName = h(getWFDefsName($db_object,$documentInfo['id']));
			$nodeName = h($nodeObj->getNodeName());
			$nodeMess = $nodeObj->message;
			//$user->audit( "Workflow", $nodeObj->auditAction );
			$whereArr = array('wf_document_id'=>(int)$documentInfo['id'],'department'=>$user->db_name,'username'=>$user->username);
			if( getTableInfo($db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne')) {
				if( getWFStatus($db_object,$documentInfo['id']) == "PAUSED" ) {
					if( getWFOwner($db_object,$documentInfo['id']) == $user->username ) {
						$ownerActionNeeded = true;
					}
				} else {
					$actionNeeded = true;
				}
			}
		}
	}
echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
 <head>
<title>Indexing Node</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
  <script type="text/javascript" src="workflow.js"></script>
  <script type="text/javascript" src="../lib/settings.js"></script>
  <script type="text/javascript">
	function restartWorkflow() {
		if(parent.sideFrame.addWorkflowStart) {
			parent.sideFrame.addWorkflowStart();
		} else {
			var wfImg = parent.sideFrame.getEl('workflow2');
			wfImg.alt = "Assign Workflow";
			wfImg.title = "Assign Workflow";
			wfImg.src = "../images/email.gif"
			wfImg.onclick = function () { parent.sideFrame.assignWorkflow('$cab','$doc_id') }; 
			if(document.all) {
				parent.sideFrame.getEl('workflow1').style.display = 'block';
			} else {
				parent.sideFrame.getEl('workflow1').style.display = 'table-cell';
			}
		}
	}

	function removeInit() {
		if( document.workflow.notes.value == "Enter notes here" )
			document.workflow.notes.value = "";	
	}
	
	function redirect() {
		if(parent.sideFrame.removeWorkflowIcon) {
			parent.sideFrame.removeWorkflowIcon();
		} else {
			parent.sideFrame.document.getElementById('workflow2').style.display = "none";
		}
	}

	function redirectOwner() {
		if(parent.sideFrame.assignOwner) {
			parent.sideFrame.assignOwner();
		} else {
			var wfIcon = parent.sideFrame.document.getElementById('workflow2');
			wfIcon.onclick = new Function(parent.sideFrame.editWorkflow('$cab',$doc_id));
		}
		window.location = "ownerAction.php?cab=$cab&doc_id=$doc_id&file_id=$file_id";
	}
ENERGIE;
	if($wfFinished) {
		echo "restartWorkflow();";
	} elseif( $ownerActionNeeded ) {
		echo "redirectOwner();";
	} else if( !$actionNeeded ) {
		echo "redirect();";
	}
	echo '</script>';
	include $nodeObj->actionURL;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
