<?php

class wfDisp {
	var $actions;
	var $defsAction;
	var $defsName;
	var $dispName;
	var $defsList;
	var $dispDefs;
	var $allNodes;
	var $numStates;
	var $userArr;
	var $owner;
	function setActions($myActions) {
		$this->actions = $myActions;
	}
	
	function setOwner($myOwner) {
		$this->owner = $myOwner;
	}
	
	function setUsers($myArr) {
		$this->userArr = $myArr;
	}
	
	function setDefsAction($myDefs) {
		$this->defsAction = $myDefs;
	}

	function setDefsName($myName, $myDisp) {
		$this->defsName = $myName;
		$this->dispName = $myDisp;
	}

	function setDefsList($myDefsList, $myDispList) {
		$this->defsList = $myDefsList;
		$this->dispDefs = $myDispList;
	}

	function setNodes($myNodes, $states) {
		$this->allNodes = $myNodes;
		$this->numStates = $states;
	}

	function printHead() {
		echo <<<ENERGIE
<head>
	<title>Create Workflow</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<link rel="stylesheet" type="text/css" href="../workflow/workflow.css" />
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript" src="../energie/func.js"></script>
	<script type="text/javascript" src="workflow.js"></script>
</head>

ENERGIE;
	}

//Prints the action select box, and selects the current action if defsAction is
//set.
	function printActions() {
		echo <<<ENERGIE

<tr>
	<td class="label">
		<label for="defsAction">Action</label>
	</td>
	<td>
		<select id="defsAction" name="defsAction" onchange="submitAction()">
	
ENERGIE;
		if (!$this->defsAction):
			echo <<<ENERGIE
			<option>Select an Action</option>

ENERGIE;
		endif;
	
		foreach ($this->actions as $myAbbrev => $myFullAction):
			if ($this->defsAction and $this->defsAction == $myAbbrev):
				echo <<<ENERGIE
			<option value="$myAbbrev" selected="selected">
				$myFullAction
			</option>

ENERGIE;
			else:
				echo <<<ENERGIE
			<option value="$myAbbrev">
				$myFullAction
			</option>

ENERGIE;
			endif;
		endforeach;
		echo <<<ENERGIE
		</select>
	</td>
</tr>

ENERGIE;
	}

	function printWorkflows()
	{
		echo <<<ENERGIE

<tr>
	<td class="label">
		<label for="defsName">Workflow</label>
	</td>
	<td>

ENERGIE;
		if ($this->defsAction == 'addWF'):
			if ($this->defsName):
				echo <<<ENERGIE
		<input id="dispName" type="text" readonly="readonly" value="$this->dispName" />
		<input id="defsName" name="defsName" type="hidden" value="$this->defsName" />

ENERGIE;
			else:
				echo <<<ENERGIE
		<input id="defsName" name="defsName" type="text" onkeypress="return inputFilter(event)"/>

ENERGIE;
			endif;
		else:
			echo <<<ENERGIE
		<select id="defsName" name="defsName" onchange="submitForm()">

ENERGIE;
			if (!$this->defsName):
				echo <<<ENERGIE
			<option>Select a Workflow</option>

ENERGIE;
			endif;
			foreach ($this->defsList as $myDef):
				if ($myDef == $this->defsName):
					echo <<<ENERGIE
			<option selected="selected" value="$myDef">{$this->dispDefs[$myDef]}</option>

ENERGIE;
				else:
					echo <<<ENERGIE
			<option value="$myDef">{$this->dispDefs[$myDef]}</option>

ENERGIE;
				endif;
			endforeach;
			echo <<<ENERGIE
		</select>
			
ENERGIE;
			if($this->defsName):
				echo<<<ENERGIE
			<span
				class="link"
				onclick="setupRenameWorkflow(this)"
			>
				Rename Workflow
			</span>
ENERGIE;
		endif;
			
		endif;
		echo <<<ENERGIE
	</td>
</tr>

ENERGIE;
	}
	
	function printWFOwner()
	{
		echo <<<ENERGIE

<tr>
	<td class="label">
		<label for="wfOwner">Workflow Owner</label>
	</td>
	<td>

ENERGIE;
		echo <<<ENERGIE
		<select id="wfOwner" name="wfOwner" onchange="submitForm()">

ENERGIE;
		foreach ($this->userArr as $myUser):
			if ($myUser == $this->owner):
				echo <<<ENERGIE
			<option selected="selected" value="$myUser">$myUser</option>

ENERGIE;
			else:
				echo <<<ENERGIE
			<option value="$myUser">$myUser</option>

ENERGIE;
			endif;
		endforeach;
		echo <<<ENERGIE
		</select>
	</td>
</tr>

ENERGIE;
	}

	function printAddButtons()
	{
		echo <<<ENERGIE

<div class="btnDiv">
	<input name="addNode" type="submit" value="Add Node" />
	<span>to state</span>
	<select name="addNodeToState">

ENERGIE;
		//If just added state, put highest state in box, else put the
		//last state in box, else put whatever.
		for ($i = 0; $i < sizeof($this->allNodes); $i++):
			if ( $this->allNodes[$i]['node_type'] == "STATE" ):
				$myNodeName = $this->allNodes[$i]['node_name'];
				$stateNum = $this->allNodes[$i]['state'];
				if (isset($_POST['addState']) and $i == sizeof($this->allNodes) - 1):
					echo <<<ENERGIE
		<option selected="selected" value="$stateNum">$myNodeName</option>

ENERGIE;
				elseif (!isset($_POST['addState']) and 
						isset($_POST['addNodeToState']) and 
						$stateNum == $_POST['addNodeToState'] ):

					echo <<<ENERGIE

		<option selected="selected" value="$stateNum">$myNodeName</option>

ENERGIE;
				else:
					echo <<<ENERGIE
		<option value="$stateNum">$myNodeName</option>

ENERGIE;
				endif;
			endif;
		endfor;
		echo <<<ENERGIE
	</select>
</div>

ENERGIE;
	}

	function printStates()
	{
		$j = 0;
		for ($i = 1; $i <= $this->numStates; $i++):
			if ($this->allNodes[$j]['state'] == $i):
				echo <<<ENERGIE

	<div class="state">

ENERGIE;
				while (isset($this->allNodes[$j]) and $this->allNodes[$j]['state'] == $i):
					$myNodeID = $this->allNodes[$j]['node_id'];
					$myPrevNode = h($this->allNodes[$j]['prevName']);
					$myNextNode = h($this->allNodes[$j]['nextName']);
					$myNodeName = h($this->allNodes[$j]['node_name']);
					echo <<<ENERGIE
		<div
			onclick="editNode($myNodeID)"
			title="Reject: $myPrevNode, Accept: $myNextNode"
			class="node"
		>
			$myNodeName
		</div>


ENERGIE;
					$j++;
				endwhile;
				echo <<<ENERGIE
		<div class="spacer">&nbsp;</div>
	</div>

ENERGIE;
			endif;
		endfor;
	}
}
?>
