<?php
// $Id: createWorkflow.php 14417 2011-07-01 18:34:26Z fabaroa $

// workflow/createWorkflow.php
// Page for creating and editing a workflow definition.
require_once 'workFlowDefs.inc';
require_once 'wfDisp.inc';
require_once '../check_login.php';
require_once '../classuser.inc';
if ($logged_in and $user->username and $user->isDepAdmin()) {
	$wfDisp = new wfDisp();
	//Actions right now are add or edit, do we want to open the 'Delete
	//Workflow' can of worms? If a workflow gets deleted that is referenced
	//by an active document, or if a workflow is useful and gets deleted when
	//it should be used again, I do not want to be responsible for the ensuing
	//chaos.
	$actions = array ('addWF' => 'Add Workflow Definition', 'editWF' => 'Edit Workflow Definition'
		//		'delWF'		=> 'Delete Workflow Definition'
	);

	$wfDisp->setActions($actions);

  	$accessUsers = getTableInfo($db_object,'access');
	$userArr = array ();
	while ($row = $accessUsers->fetchRow()) {
		if ($user->greaterThanUser($row['username'])) {
			$userArr[] = $row['username'];
		}
	}

	$wfDisp->setUsers($userArr);

	//defsAction - add or edit, as defined by the 'actions' array
	$defsAction = '';
	if (isset ($_POST['defsAction'])) {
		$defsAction = $_POST['defsAction'];
	}
	elseif (isset ($_GET['defsAction'])) {
		$defsAction = $_GET['defsAction'];
	}

	$wfDisp->setDefsAction($defsAction);
	//defsName - a user definable name for the workflow definition, is unique
	$defsName = '';
	if (isset ($_POST['defsName'])) {
		$defsName = trim($_POST['defsName']);
	}
	elseif (isset ($_GET['defsName'])) {
		$defsName = $_GET['defsName'];
	}
	//We store the defsName in the database with the spaces replaced by
	//underscores
	$defsName = str_replace('\"', '', $defsName);
	$defsName = str_replace("\'", '', $defsName);
	//Coming from some places, it may still have underscores
	$dispName = quotemeta(stripslashes(str_replace('_', ' ', $defsName)));
	$wfDisp->setDefsName($defsName, $dispName);

	//The session variable 'wfDefs' is used for simplicity -- it enables the
	//page to be reloaded on every node edit, delete, add, or property change 
	//and not have to pass the array back and forth -- this is much quicker.
	if (isset ($_SESSION['wfDefs'])) {
		$wfDefs = $_SESSION['wfDefs'];
	}

	if (isset ($_POST['wfOwner'])) {
		$wfOwner = $_POST['wfOwner'];
	} else {
		$wfOwner = $user->username;
	}

	if (!isset ($wfDefs) or $wfDefs->getDefsName() != $defsName) {
		$wfDefs = new workFlowDefs();
		$wfDefs->setDefsName($defsName);
		$wfDefs->setOwner($wfOwner);
	}

	//The database object may be stale, so it must be reset on every page
	//reload to ensure freshness.
	$wfDefs->setDbObj($db_object);
	$wfDefs->setUser($user);

	if ($wfOwner != $wfDefs->getOwner()) {
		$wfDefs->setOwner($wfOwner);
	}

	$defsList = $wfDefs->getDefsList();

	if (!$defsList) {
		//There are no definitions yet.
	}

	$dispDefs = array ();
	foreach ($defsList as $myDefs) {
		$dispDefs[$myDefs] = str_replace('_', ' ', h($myDefs));
	}

	$wfDisp->setDefsList($defsList, $dispDefs);

	if ($defsAction == 'editWF') {
		//If there is only one workflow definition, auto-select it.
		if (sizeof($defsList) == 1) {
			$defsName = $defsList[0];
			$wfDisp->setDefsName($defsName, str_replace('_', ' ', $defsName));
		}

		//If a workflow definition name was passed in, we need to reset wfDefs
		//and have it be autopopulated with information from that definition.
		if ($defsName) {
			$wfDefs = new workFlowDefs($defsName, $db_object);
			$wfDefs->setUser($user);
		}
	}
	$wfDisp->setOwner($wfDefs->getOwner());
	/*	if(isset($_POST['deleteWF'])) {
			$wfDefs->deleteWorkflow($defsName);
		}
	*/

	if (isset ($_POST['addState'])) {
		if (!$wfDefs->createStateNode()) {
			//Unable to create state Node
		}
	}
	if (isset ($_POST['addNode'])) {
		if (!$wfDefs->createRegNode($_POST['addNodeToState'])) {
			//Unable to create node
		}
	}
	$allNodes = $wfDefs->getStateLines();
	if ($allNodes === false) {
		//unable to get workflow properties
	} else {
		for ($i = 0; $i < sizeof($allNodes); $i ++) {
			$allNodes[$i]['node_name'] = str_replace('_', ' ', $allNodes[$i]['node_name']);
			foreach ($allNodes as $myNode) {
				if ($allNodes[$i]['prev'] == $myNode['id']) {
					$allNodes[$i]['prevName'] = str_replace('_', ' ', $myNode['node_name']);
				}
				if ($allNodes[$i]['next'] == $myNode['id']) {
					$allNodes[$i]['nextName'] = str_replace('_', ' ', $myNode['node_name']);
				}
			}
			if (!isset ($allNodes[$i]['nextName'])) {
				$allNodes[$i]['nextName'] = $allNodes[$i]['node_name'];
			}
			if (!isset ($allNodes[$i]['prevName'])) {
				$allNodes[$i]['prevName'] = $allNodes[$i]['node_name'];
			}
		}
	}
	$numStates = $wfDefs->getStates();
	$_SESSION['workflow_nodes'] = $allNodes;
	$_SESSION['workflow_states'] = $numStates;
	$wfDisp->setNodes($allNodes, $numStates);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<?php echo $wfDisp->printHead() ?>
	<body class="centered">
		<div class="mainDiv">
			<div class="mainTitle">
				<span>Add/Edit Workflow Definition</span>
			</div>
			<div id="contentDiv">
				<form
					id="wfForm" 
					action="<?php echo $_SERVER['PHP_SELF']?>"
					method="post"
				>
					<div>
						<table id="formTable" class="inputTable">
							<?php echo $wfDisp->printActions() ?>
							<?php if($defsAction):?>
								<?php echo $wfDisp->printWorkflows() ?>
								<?php if($defsName): ?>
									<?php /* echo $wfDisp->printWFOwner() */ ?>
								<?php endif; ?>
							<?php endif; ?>
						</table>
						<?php if (isset ($_SESSION['wfErr'])): ?>
							<div class="error"><?php echo $_SESSION['wfErr'] ?></div>
							<?php unset ($_SESSION['wfErr']) ?>
						<?php endif ?>
						<?php if($defsAction): ?>
							<?php if($defsAction == 'addWF' and !$defsName):?>
								<div class="btnDiv">
									<input type="submit" value="Save" />
								</div>
							<?php endif; ?>
						<?php endif; ?>
						<?php if($defsName):?>
							<div class="btnDiv">
								<input
									name="addState" 
									type="submit" 
									value="Add State" 
								/>
							</div>
						<?php endif; ?>
					</div>
					<?php if($numStates):?>
						<?php echo $wfDisp->printAddButtons() ?>
						<?php echo $wfDisp->printStates() ?>
<!--						<a href="drawNodes2.php">Click Me</a> -->
					<?php endif; ?>
				</form>
			</div>
			<div id="errDiv" class="error">&nbsp;</div>
		</div>
	</body>
</html>
<?php

	$wfDefs->close();
	$_SESSION['wfDefs'] = $wfDefs;
	setSessionUser ($user);
} else {
	logUserOut ();
}
?>
