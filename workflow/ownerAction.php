<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'node.inc.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0) {
	$actionNeeded = false;
	$db_object = $user->getDbObject();
                                                                                                                             
    $cab = $_GET['cab'];
    $doc_id = $_GET['doc_id'];
	$file_id = ($_GET['file_id']) ? $_GET['file_id'] : -1;
	if(isSet($_POST['submit'])) {
		$documentInfo = getWorkflowIDs( $db_object, $cab, $doc_id,$file_id );
		if($_POST['submit'] == "Reset") {
			$db_doc = getDbObject('docutron');
			$whereArr = array('wf_document_id'=>(int)$documentInfo['id'],'department'=>$user->db_name,'username'=>$user->username);
			if( getTableInfo($db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne')) {
				
				$updateArr = array('status'=>'IN PROGRESS');
				$whereArr = array('id'=>(int)$documentInfo['id']);
				updateTableInfo($db_object,'wf_documents',$updateArr,$whereArr);
				$nodeClass = getWFNodeType($db_object, $documentInfo['id']).'Node';
				$cabDispName = getTableInfo($db_object, 'departments', array('departmentname'),
					array('real_name' => $cab), 'queryOne');
				$nodeObj = new $nodeClass($db_object, $user->db_name, $user->username, $documentInfo['id'], $documentInfo['state_wf_def_id'], $cab, $cabDispName, $doc_id,$db_doc,$file_id);
				$nodeObj->deleteFromTodo();
				$nodeObj->notify();
        		$actionNeeded = true;
    		}
		} elseif( $_POST['submit'] == "Delete" ) {
			deleteTableInfo($db_object,'wf_documents',array('id'=>(int)$documentInfo['id']));

			$wArr = array(	'wf_document_id'=>(int)$documentInfo['id'],
							'department' => $user->db_name );
			deleteTableInfo($db_doc,'wf_todo',$wArr);
		}
	}
	
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script>
    function redirect( type ) {
   		parent.topMenuFrame.document.getElementById('up').onclick();
		if( type == "delete" ) {
			if(parent.sideFrame.removeWorkflowIcon) {
				parent.sideFrame.removeWorkflowIcon();
				parent.sideFrame.removeWorkflowHistoryIcon();
			} else {
				parent.sideFrame.document.getElementById('workflow1').style.display = "none";
				parent.sideFrame.document.getElementById('workflow2').style.display = "none";
			}
		} else {
			if(parent.sideFrame.addWorkflow) {
				parent.sideFrame.addWorkflow();
			} else {
				parent.sideFrame.document.getElementById('workflow2').onclick = new Function("parent.mainFrame.window.location='../workflow/getSignature.php?cab=$cab&doc_id=$doc_id';parent.sideFrame.addBackButton();");	
			}
		}
    }
ENERGIE;
	if( isSet( $_POST['submit'] ) && $actionNeeded )
		echo "redirect( 'reset' )";
	elseif( isSet( $_POST['submit'] ) )
		echo "redirect( 'delete' )";
echo<<<ENERGIE
  </script>
 </head>
 <body>
  <center>
   <form name="owner" method="POST" action="ownerAction.php?cab=$cab&doc_id=$doc_id&file_id=$file_id">
   <table class="settings" width="350">
   <tr class="tableheads">
    <td colspan="2">Paused Workflow Document</td>
   </tr>
   <tr>
    <td><input type="submit" name="submit" value="Delete"></td>
    <td><input type="submit" name="submit" value="Reset"></td>
   </tr>
   </table>
   </form>
  </center>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
