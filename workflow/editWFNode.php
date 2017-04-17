<?php
// $Id: editWFNode.php 15102 2014-06-02 21:03:54Z fabaroa $

require_once 'workFlowDefs.inc';
require_once 'wfNodes.php';
require_once 'editWFNodeDisp.inc';
require_once '../groups/groups.php';
require_once '../check_login.php';
require_once 'nodeTypes.php';
if($logged_in and $user->username and $user->isDepAdmin()) {
	$wfDisp = new editWFNodeDisp();
	$wfDisp->setNodeTypes($nodeTypes);
	$db_object = $user->getDbObject();
	$wfDefs = $_SESSION['wfDefs'];
	$wfDefs->setDbObj($db_object);
	$defsName = $wfDefs->getDefsName();
	$nodeID = $_GET['nodeID'];
	$nodeAction = "";
	$nodeInfo = $wfDefs->getNodeInfo($nodeID);
	$nodeName = h($nodeInfo['node_name']);
	$wfDisp->setNodeInfo($nodeInfo);
	$allNodes = $wfDefs->getStateLines();
	$allNames = array();
	$errMsg = '';
	if($allNodes != false) {
		foreach($allNodes as $myNode) {
          		$allNames[$myNode['node_id']] = $myNode['node_name'];
		}
		$allNames[0] = "Previous Node";
	}
	if(isset($_POST['nodeAction'])) {
		$nodeAction = $_POST['nodeAction'];
	}
	$canDelete = true;
	if(!isset($_POST['cancel'])) {
		if($nodeAction != 'delete') {
			$wfNodes = new wfNodes($db_object);
    	    $groupListID = $nodeInfo['group_list_id'];
            $userListID = $nodeInfo['user_list_id'];
			$groupsObj = new groups($db_object);
			$allGroups = $groupsObj->getGroups();
			$allGroupsNew = array();
			foreach($allGroups as $realName => $arbName) {
				$allGroupsNew[$realName] = str_replace('_', ' ', $arbName);
			}
			$allGroups = $allGroupsNew;
			unset($allGroupsNew);
  			$accessRights = getTableInfo($db_object,'access');
			//$allUsers = array('Workflow Owner');
			while($row = $accessRights->fetchRow()) {
     			$allUsers[] = $row['username'];
			}
			$groups = $wfNodes->getGroupsInList($groupListID);
			if(!$groups) $groups = array();
			$wfDisp->setGroups($allGroups, $groups);
			$users = $wfNodes->getUsersInList($userListID);
			if(!$users) $users = array();
			$workflowOwner = '';
			for($i = 0; $i < count($users); $i++) {
				if($users[$i] == 'Workflow Owner') {
					$workflowOwner = 'Workflow Owner';
					unset($users[$i]);
					break;
				}
			}
			if($workflowOwner) {
				$users = array_merge(array($workflowOwner), array_values($users));
			}
			usort($allUsers,'strnatcasecmp');
			$wfDisp->setUsers($allUsers, $users);
			$allNodes = $wfDefs->getStateLines();
			//$allNames = array();
			$wfDisp->setNames($allNames);
			if(isset($_POST['changeName'])) {
				$newName = trim($_POST['changeName']);
				if($newName != $nodeName) { 
					$usedName = false;
					foreach($allNodes as $myNode) {
                      if($myNode['node_name'] == $newName) {
							$usedName = true;
							break;
						}
					}
					if($usedName) {
						//The name is used
					} else {
						$wfDefs->setNodeName($nodeID, $newName);
						$user->audit("Node Name Changed", "old name: '$nodeName', new name: '$newName', Workflow Definition: '$defsName'");
						$nodeName = h($newName);
					}
				}
			}
			if(isset($_POST['prev2']) and $nodeInfo['prev'] != $_POST['prev2']) {
				$wfDefs->setPrev($nodeID, $_POST['prev2']);
				  $line = $wfDefs->getDefsLine($_POST['prev2']);
				  $user->audit("Reject Changed", "node: '$nodeName', Workflow Definition: '$defsName', new reject node: {$line['node_id']}");
				  $nodeInfo['prev'] = $_POST['prev2'];
			}
            if(isset($_POST['next2']) and $nodeInfo['next'] != $_POST['next2']) {
                $wfDefs->setNext($nodeID, $_POST['next2']);
				$line = $wfDefs->getDefsLine($_POST['next2']);
                $user->audit("Accept Changed", "node: '$nodeName', Workflow Definition: '$defsName', new accept node: {$line['node_id']}");
                $nodeInfo['next'] = $_POST['next2'];
            }
            if(isset($_POST['newType']) and $nodeInfo['node_type'] != $_POST['newType']) {
                $wfDefs->setNodeType($nodeID, $_POST['newType']);
                $user->audit("Type Changed", "node: '$nodeName', Workflow Definition: '$defsName', new node type: {$nodeInfo['node_type']}");
                $nodeInfo['node_type'] = $_POST['newType'];
            }
 			if(isset($_POST['desc']) && $nodeInfo['message'] != $_POST['desc']) {
 				$wfDefs->setNodeMessage( $nodeID, $_POST['desc'] );
                $user->audit("Node Message Changed", "node: '$nodeName', Workflow Definition: '$defsName', new node message: {$nodeInfo['message']}");
                $nodeInfo['message'] = $_POST['desc'];
 			}
 			if(isset($_POST['notes_required']) && $nodeInfo['notes_required'] != $_POST['notes_required']) {
 				$wfDefs->setNotesRequired( $nodeID, $_POST['notes_required'] );
        $nodeInfo['notes_required'] = $_POST['notes_required'];
        $user->audit("Notes Required Changed", "node: '$nodeName', Workflow Definition: '$defsName', new notes required: {$nodeInfo['notes_required']}");
 			}
			if( isset($_POST['userOption']) ){
				// Leave Random in place - even though it is no longer a selectable option, 
				// it may exist in an existing workflow.
				$choiceArr = array("All"=>0,"Any"=>1,"Random"=>2,"Force Access"=>3,"Round Robin"=>4);
				//$choiceArr = array("All"=>0,"Any"=>1,"Round Robin"=>4);
                $user->audit("Node User Assign Changed", "node: '$nodeName', Workflow Definition: '$defsName', new node assign: {$_POST['userOption']}");
				$choice = $choiceArr[$_POST['userOption']];
			}
			if(isset($_POST['userOption']) && $nodeInfo['which_user'] != $choice ) {
 				$wfDefs->setWhichUser( $nodeID, $choice );
                $user->audit("Node Which User Changed", "node: '$nodeName', Workflow Definition: '$defsName', new user: {$nodeInfo['which_user']}");
                $nodeInfo['which_user'] = $choice;
			}
			if(isset($_POST['email']) ) { 
				if($_POST['email'] == 1 && $_POST['ownEmail'] == 1) {
					$email = 3;
    	            $user->audit("Node email setting changed", "node: '$nodeName', Workflow Definition: '$defsName', new setting: email on for owner and user");
				} else if($_POST['email'] == 0 && $_POST['ownEmail'] == 1) {
					$email = 2;
	                $user->audit("Node email setting changed", "node: '$nodeName', Workflow Definition: '$defsName', new setting: email on for owner only");
				} else if($_POST['email'] == 1 && $_POST['ownEmail'] == 0) {
					$email = 1;
	                $user->audit("Node email setting changed", "node: '$nodeName', Workflow Definition: '$defsName', new setting: email on for user only");
				} else {
					$email = 0;
	                $user->audit("Node email setting changed", "node: '$nodeName', Workflow Definition: '$defsName', new setting: email off");
			}
 				$wfDefs->setNodeEmail( $nodeID, $email );
                $nodeInfo['email'] = $email;
			}

			if(isSet($_POST['message_alert'])) {
				$message_alert = $_POST['message_alert'];
				$wfDefs->setMessageAlert($nodeID,$message_alert);
				$nodeInfo['message_alert'] = $message_alert;
			}
				
			$valueID = $wfNodes->getValueID($nodeID);
			if(isset($_POST['nodeTxt0'])) {
				$wfNodes->deleteValueList($valueID);
				$valueNodeArr = array();
				$i = 0;
				while (isset($_POST['nodeTxt'.$i])) {
					if(!empty($_POST['nodeTxt'.$i])) {
						$nextNode = $_POST['nodeSel'.$i];
						$message = $_POST['nodeTxt'.$i];
						$nextValueID = getTableInfo($db_object,'wf_value_list',array('MAX(value_list_id)+1'),array(),'queryOne');
						if( !$nextValueID )
							$nextValueID = 1; 
						$valueNodeArr[] = array(
											"value_list_id" => (int)$nextValueID, 
											"next_node"		=> (int)$nextNode,
											"message"		=> $message
											 );
						$user->audit("Value list Changed", "node: '$nodeName', Workflow Definition: '$defsName', message: '$message', accept node: '{$allNames[$nextNode]}'");
					}
					$i++;
				}
				foreach( $valueNodeArr AS $valueNode )
					$res = $db_object->extended->autoExecute( 'wf_value_list', $valueNode );
					dbErr($res);
				if(isset($nextValueID)) {
				$wfNodes->setValueID( $nodeID, $nextValueID );
			}
			}
			makeLists('group', $wfNodes, $nodeID, $groups, $groupListID, $user);
			makeLists('user', $wfNodes, $nodeID, $users, $userListID, $user);
			$dispName = str_replace('_', ' ', $nodeName);
		} else {
			$prevInfo = $wfDefs->getDefsLine($nodeInfo['prev']);
			$nextInfo = $wfDefs->getDefsLine($nodeInfo['next']);
			if ($nodeInfo['next'] == $nodeID and 
				$nodeInfo['node_type'] != 'VALUE' and $nodeInfo['node_type'] != "CUSTOM") {

				$nextNode = $nodeInfo['prev'];
			} else {
				$nextNode = $nodeInfo['next']; 
			}
			$inValueLists = getTableInfo ($db_object,
				'wf_value_list', array ('value_list_id', 
				'COUNT(*)'), array ('next_node' => 
				(int) $nodeID), 'getAssoc', 
				array (), 0, 0, array ('value_list_id'));
			$allValueLists = getTableInfo ($db_object,
				'wf_value_list', array ('value_list_id',
				'COUNT(*)'), array (), 'getAssoc', array (),
				0, 0, array ('value_list_id'));
			foreach ($inValueLists as $myListID => $myCt) {
				if ($allValueLists[$myListID] == $myCt) {
					$_SESSION['wfErr'] = 
						'Unable to delete node ' .
						$nodeName .'. Please check ' .
						'if any value ' .
						'nodes depend on this node.';
					$canDelete = false;
					break;
				}
			}

			if ($canDelete) {
				deleteTableInfo($db_object, 'wf_value_list',
					array ('next_node' => 
					(int) $nodeID));
				$wfDefs->setNext($prevInfo['node_id'], $nextNode);
				$line = $wfDefs->getDefsLine($nodeInfo['next']);
				$linePrev = $wfDefs->getDefsLine($nodeInfo['prev']);			
				$user->audit("Accept Changed", "node: '{$allNames[$linePrev['node_id']]}', Workflow Definition: '$defsName', new accept node: {$allNames[$line['node_id']]}");
				$wfDefs->setPrev($nextInfo['node_id'], $nodeInfo['prev']);
				$user->audit("Reject Changed", "node: '{$allNames[$line['node_id']]}', Workflow Definition: '$defsName', new accept node: {$allNames[$linePrev['node_id']]}");
				$wfDefs->deleteNode($nodeID);
				$user->audit("Node deleted", "node: '$nodeName', Workflow Definition: '$defsName'");
			}
		}
	}
	if(isset($_POST['nodeAction'])) {
		$wfDisp->redirectBack($defsName);
		die();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<?php $wfDisp->printHead()?>
	<body class="centered" onload="setFocus()">
		<div class="mainDiv">
			<div class="mainTitle">
				<span><?php echo $dispName?></span>
			</div>
			<form
				id="myForm"
				name="myForm"
				action="<?php echo $_SERVER['PHP_SELF']?>?nodeID=<?php echo $nodeID?>"
				method="post"
			>
				<div style="margin-left: auto; margin-right: auto; width: 12em;padding-top:15px">
					<div style="float: right">
						<input name="submit" type="submit" value="Submit" />
					</div>
					<div style="float: right">
						<input name="cancel" type="submit" value="Cancel" />
					</div>
				</div>
				<div>
					<?php $wfDisp->printActions()?>
					<table class="inputTable">
						<tr>
							<td class="label">
								<label for="changeName">Name</label>
							</td>
							<td>
								<input
									id="changeName"
									type="text"
									onkeypress="return inputFilter(event)"
									name="changeName"
									value="<?php echo $dispName?>"
								/>
							</td>
						</tr>
						<?php $wfDisp->printNodeTypes()?>
						<?php $wfDisp->printPrevNext()?>
					 	<tr>
							<td class="label">
        						<label for="nodeDesc">Node Description</label>
    						</td>
							<td>
								<textarea
									id="nodeDesc"
									name="desc"
									rows="3"
									cols="25"
								><?php echo h($nodeInfo['message'])?></textarea>
							</td>
						</tr>
						<tr>
						 <td class="label">
						  <label for="prev2">Notes Required</label>
						 </td>
						 <?php if($nodeInfo['notes_required'] == 1):?>
						  <td>
						   <input checked="checked" type="radio" name="notes_required" value="1"/>Yes
						   <input type="radio" name="notes_required" value="0"/>No
						  </td>
						 <?php else:?>
						  <td>
						   <input type="radio" name="notes_required" value="1"/>Yes
						   <input checked="checked" type="radio" name="notes_required" value="0"/>No
						  </td>
						 <?php endif; ?>
						</tr>
						<tr>
						 <td class="label">
						  <label for="prev2">Message Notification</label>
						 </td>
						 <?php if($nodeInfo['message_alert'] == 1):?>
						  <td>
						   <input checked="checked" type="radio" name="message_alert" value="1"/>Yes
						   <input type="radio" name="message_alert" value="0"/>No
						  </td>
						 <?php else:?>
						  <td>
						   <input type="radio" name="message_alert" value="1"/>Yes
						   <input checked="checked" type="radio" name="message_alert" value="0"/>No
						  </td>
						 <?php endif; ?>
						</tr>
						<tr>
						 <td class="label">
						  <label for="prev2">Email Notification</label>
						 </td>
						 <?php if($nodeInfo['email'] == 1 
							|| $nodeInfo['email'] == 3 ):?>
						  <td>
						   <input checked="checked" type="radio" name="email" value="1"/>Yes
						   <input type="radio" name="email" value="0"/>No
						  </td>
						 <?php else:?>
						  <td>
						   <input type="radio" name="email" value="1"/>Yes
						   <input checked="checked" type="radio" name="email" value="0"/>No
						  </td>
						 <?php endif; ?>
						</tr>
						<tr>
						 <td class="label">
						  <label for="prev2">Email Owner Notification</label>
						 </td>
						 <?php if($nodeInfo['email'] >= 2):?>
						  <td>
						   <input checked="checked" type="radio" name="ownEmail" value="1"/>Yes
						   <input type="radio" name="ownEmail" value="0"/>No
						  </td>
						 <?php else:?>
						  <td>
						   <input type="radio" name="ownEmail" value="1"/>Yes
						   <input checked="checked" type="radio" name="ownEmail" value="0"/>No
						  </td>
						 <?php endif; ?>
						</tr>
						<?php $wfDisp->printValueNodes($db_object, $valueID)?>
						<?php $wfDisp->printUserOption( $nodeInfo['which_user'])?>
					  </table>
					</div>
					<div class="fieldSetDiv">
						<?php if($allGroups):?>
							<?php $wfDisp->printGroups()?>
						<?php endif; ?>
						<?php $wfDisp->printUsers()?>
					</div>
					<div style="margin-left: auto; margin-right: auto; width: 12em">
						<div style="float: right">
							<input name="submit" type="submit" value="Submit" />
						</div>
						<div style="float: right">
							<input name="cancel" type="submit" value="Cancel" />
						</div>
						<div style="clear: both">&nbsp;</div>
					</div>
			</form>
		</div>
	</body>
</html>
<?php
}

function makeLists($type, $wfNodes, $nodeID, &$arr, &$listID, $user) {
	global $allNames, $defsName;
	$postVar = $type.'sYes';
	$arrYes = array();
	$arrNo = array();
	if(isset($_POST['submit']) and !isset($_POST[$postVar])) {
		$wfNodes->deleteList($type, $listID);
		$wfNodes->setList($type, $nodeID, 0);
		$listID = 0;
		$arr = array();
	}
	if(isset($_POST[$postVar])) {
		$arrYes = $_POST[$postVar];
		$oldArr = array_diff($arr, $arrYes);
		foreach($oldArr as $myItem) {
			$count = $wfNodes->removeFromList($type, $listID, $myItem);
			$user->audit("$type list changed", "$myItem removed from list, node: {$allNames[$nodeID]}, defs: $defsName");   
			if(!$count) {
				$wfNodes->setList($type, $nodeID, 0);
				$listID = 0;
			}
		}
		$newArr = array_diff($arrYes, $arr);
		foreach($newArr as $myItem) {
			$newListID = $wfNodes->addToList($type, $listID, $myItem);
			$user->audit("$type list changed", "$myItem added to list, node: {$allNames[$nodeID]}, defs: $defsName");   
			if($newListID != $listID) {
				$wfNodes->setList($type, $nodeID, $newListID);
				$listID = $newListID;
			}
		}
	}
	$postVar = $type.'sNo';
	if(isset($_POST[$postVar])) {
		$arrNo = $_POST[$postVar];
		foreach($arrNo as $myItem) {
			$newListID = $wfNodes->addToList($type, $listID, $myItem);
			$user->audit("$type list changed", "$myItem added to list, node: {$allNames[$nodeID]}, defs: $defsName");   
			if($newListID != $listID) {
				$wfNodes->setList($type, $nodeID, $newListID);
				$listID = $newListID;
			}
		}
	}
	if(!$arrYes and !$arrNo and isset($_POST['submit'])) {
		$wfNodes->deleteList($type, $listID);
		$wfNodes->setList($type, $nodeID, 0);
		$listID = 0;
		$arr = array();
	}
	if($arrYes or $arrNo) {
		$arr = array_merge($arrYes, $arrNo);
	}
}
?>
