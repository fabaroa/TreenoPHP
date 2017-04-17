<?php
// $Id: editWFNodeDisp.inc 15098 2014-05-23 13:58:05Z fabaroa $

include_once '../lib/utility.php';
class editWFNodeDisp {
	var $nodeInfo;
	var $allNames;
	var $allGroups;
	var $allUsers;
	var $groups;
	var $users;
	var $nodeTypes;
	function redirectBack($defsName) {
		$slashedDefs = addslashes($defsName);
		echo <<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Edit Node</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<script type="text/javascript" src="workflow.js"></script>
	</head>
	<body>
		<script type="text/javascript">
			document.onload = loadEditDefs('$slashedDefs');
		</script>
	</body>
</html>

ENERGIE;

	}
	function printHead() {
		echo <<<ENERGIE
		
<head>
	<title>Edit Node</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<script type="text/javascript" src="../energie/func.js"></script>
	<script type="text/javascript" src="workflow.js"></script>
	<script type="text/javascript">
		function setFocus() {
			document.myForm.changeName.focus();
			document.myForm.changeName.select();
		}
	</script>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<link rel="stylesheet" type="text/css" href="../workflow/workflow.css" />
</head>

ENERGIE;
	}

	function printActions() {
	if ($this->nodeInfo['node_type'] == 'STATE' and $this->nodeInfo['wfid'] == $this->nodeInfo['parent_id'])
		{
		echo <<<ENERGIE
<div class="fieldSetDiv">
	<fieldset>
		<legend>Node Action</legend>
		<table class="inputTable">
			<tr>
				<td>
					<input
						name="nodeAction"
						id="editNode"
						value="edit"
						checked="checked"
						type="radio"
						onchange="whatAction()"
					/>
				</td>
				<td>
					<label for="editNode">Edit Node</label>
				</td>
			</tr>
		</table>
	</fieldset>
</div>

ENERGIE;
		}
		else
	{
		echo <<<ENERGIE
<div class="fieldSetDiv">
	<fieldset>
		<legend>Node Action</legend>
		<table class="inputTable">
			<tr>
				<td>
					<input
						name="nodeAction"
						id="editNode"
						value="edit"
						checked="checked"
						type="radio"
						onchange="whatAction()"
					/>
				</td>
				<td>
					<label for="editNode">Edit Node</label>
				</td>
			</tr>
			<tr>
				<td>
					<input
						name="nodeAction"
						id="delNode"
						value="delete"
						type="radio"
						onchange="whatAction()"
					/>
				</td>
				<td>
					<label for="delNode">Delete Node</label>
				</td>
			</tr>
		</table>
	</fieldset>
</div>

ENERGIE;
		}
	}

	function printNodeTypes() {
		if($this->nodeInfo['node_type'] == 'STATE'):
			echo <<<ENERGIE

<tr>
	<td class="label">Node Type</td>
	<td>STATE</td>
</tr>

ENERGIE;
		else:
			echo <<<ENERGIE

<tr>
	<td class="label">
		<label for="newType">Node Type</label>
	</td>
	<td>
		<select id="newType" name="newType" onchange="changeNodeType()">
	
ENERGIE;
			foreach($this->nodeTypes as $nodeType):
				if($nodeType == $this->nodeInfo['node_type']):
					echo <<<ENERGIE
			<option selected="selected" value="$nodeType">$nodeType</option>
ENERGIE;
				else:
					echo <<<ENERGIE
			<option value="$nodeType">$nodeType</option>

ENERGIE;
				endif;
			endforeach;
			echo <<<ENERGIE
		</select>
	</td>
</tr>

ENERGIE;
		endif;
	}
	function printPrevNext() {
		echo <<<ENERGIE
		
<tr>
	<td class="label">
		<label for="prev2">Reject Node</label>
	</td>
	<td>
ENERGIE;
		if($this->nodeInfo['node_type'] == 'VALUE' || $this->nodeInfo['node_type'] == 'FINAL'):
			echo '<select id="prev2" disabled="disabled" name="prev2">';	
		else:
			echo '<select id="prev2" name="prev2">';
		endif;

		foreach($this->allNames as $nodeID => $myDispName):
			$myDispName = h($myDispName);
			if($nodeID == $this->nodeInfo['prev']):
				echo <<<ENERGIE
			<option value="$nodeID" selected="selected">$myDispName</option>

ENERGIE;
			else:
				echo <<<ENERGIE
			<option value="$nodeID">$myDispName</option>

ENERGIE;
			endif;
		endforeach;
		echo <<<ENERGIE
		</select>
	</td>
</tr>
<tr>
	<td class="label">
		<label for="next2">Accept Node</label>
	</td>
	<td>
ENERGIE;
		if($this->nodeInfo['node_type'] == 'VALUE' || $this->nodeInfo['node_type'] == 'FINAL'):
			echo '<select id="next2" disabled="disabled" name="next2">';
		else:
			echo '<select id="next2" name="next2">';
		endif;

		foreach($this->allNames as $nodeID => $myDispName):
			$myDispName = h($myDispName);
			if($nodeID == $this->nodeInfo['next']):
				echo <<<ENERGIE
			<option value="$nodeID" selected="selected">$myDispName</option>

ENERGIE;
			else:
				echo <<<ENERGIE
			<option value="$nodeID">$myDispName</option>

ENERGIE;
			endif;
		endforeach;
		echo <<<ENERGIE
		</select>
	</td>
</tr>

ENERGIE;
	}

	function printGroups() {
		echo <<<ENERGIE
	<fieldset class="listFields">
		<legend>Groups</legend>
		<table class="inputTable">

ENERGIE;
		$i = 0;
		foreach($this->allGroups as $realName => $arbName):
			echo <<<ENERGIE
			<tr>

ENERGIE;
			if(in_array($realName, $this->groups)):
				echo <<<ENERGIE
				<td>
					<input
						id="group$i"
						type="checkbox"
						value="$realName"
						checked="checked"
						name="groupsYes[]"
					/>
				</td>

ENERGIE;
			else:
				echo <<<ENERGIE
				<td>
					<input
						id="group$i"
						type="checkbox"
						value="$realName"
						name="groupsNo[]"
					/>
				</td>

ENERGIE;
			endif;
			echo <<<ENERGIE
				<td>
					<label for="group$i">$arbName</label>
				</td>
			</tr>

ENERGIE;
			$i++;
		endforeach;
		echo <<<ENERGIE
		</table>
	</fieldset>

ENERGIE;
	}
	
	function printUsers() {
		$userArr = explode(",","Workflow Owner,".implode(",",$this->allUsers));
		echo <<<ENERGIE
	<fieldset class="listFields">
		<legend>Users</legend>
		<table class="inputTable">

ENERGIE;
		$i = 0;
		foreach($userArr as $realName):
			echo <<<ENERGIE
			<tr>

ENERGIE;
			if(in_array($realName, $this->users)):
				echo <<<ENERGIE
				<td>
					<input
						id="user$i"
						type="checkbox"
						value="$realName"
						checked="checked"
						name="usersYes[]"
					/>
				</td>

ENERGIE;
			else:
				echo <<<ENERGIE
				<td>
					<input
						id="user$i"
						type="checkbox"
						value="$realName"
						name="usersNo[]"
					/>
				</td>

ENERGIE;
			endif;
			if($realName == 'Workflow Owner'):
			echo <<<ENERGIE
				<td>
					<label style="font-weight: bold" for="user$i">$realName</label>
				</td>
ENERGIE;
			else:
				echo <<<ENERGIE
				<td>
					<label for="user$i">$realName</label>
				</td>
ENERGIE;
			endif;
			echo '</tr>';
			$i++;
		endforeach;
		echo <<<ENERGIE
		</table>
	</fieldset>

ENERGIE;
	}

	function setNodeInfo($nodeInfo) {
		$this->nodeInfo = $nodeInfo;
	}
	
	function setNodeTypes($nodeTypes) {
		$this->nodeTypes = $nodeTypes;
	}

	function setNames($allNames) {
		$this->allNames = $allNames;
	}

	function setGroups($allGroups, $groups) {
		$this->allGroups = $allGroups;
		$this->groups = $groups;
	}

	function setUsers($allUsers, $users) {
		$this->allUsers = $allUsers;
		$this->users = $users;
	}

	function printValueNodes( $db_object, $valueID ) {
		$nodeValueArr = getTableInfo($db_object,'wf_value_list',array(),array('value_list_id'=>(int)$valueID),'queryAll',array('id' => 'ASC'));
		echo "<tr><td colspan=\"2\">";
		if($this->nodeInfo['node_type'] == 'VALUE') {
			$disabled = "";
			echo "<div id=\"valueNodeDiv\">\n";
		} else {
			$disabled = "disabled=\"disabled\"";
			echo "<div id=\"valueNodeDiv\" style=\"display: none\">\n";
		}
		echo "<table class=\"centeredTable\">\n";
		for($i = 0; $i < count($nodeValueArr) + 10; $i++):
			if( isset( $nodeValueArr[$i] ) ):
				$textVal = h($nodeValueArr[$i]['message']);
				$selVal = $nodeValueArr[$i]['next_node'];
			else:
				$textVal = "";
				$selVal = "";
			endif;
			echo <<<ENERGIE
<tr>
<td><input name="nodeTxt$i" id="nodeTxt$i" value="$textVal" onkeypress="return inputFilter(event)" type="text" $disabled /></td>
<td><select $disabled name="nodeSel$i" id="nodeSel$i">

ENERGIE;
			foreach($this->allNames as $nodeID => $myDispName):
			$myDispName = h($myDispName);
				if( $nodeID == $selVal ):
					echo <<<ENERGIE
<option selected="selected" value="$nodeID">$myDispName</option>
ENERGIE;
				else:
					echo <<<ENERGIE
<option value="$nodeID">$myDispName</option>
ENERGIE;
				endif;
			endforeach;
			echo <<<ENERGIE
</select></td>
</tr>
ENERGIE;
		endfor;
		echo "</table></div></td></tr>\n";
	}

	function printUserOption( $choice )
	{
		$choiceAll = array("All");
		$choiceArr = array( "Any", "Force Access", "Random", "Round Robin" ); 
		//$choiceArr = array( "Any", "Round Robin" );Removed Force Access 5/23/12 per Will. CGD.
		//Removes the ALL option from value nodes - actually it added it to only the value and custom nodes...now it is removed from value and //indexing CGD 5/23/12
		//if($this->nodeInfo['node_type'] == 'VALUE' || $this->nodeInfo['node_type'] == 'CUSTOM') {
		if($this->nodeInfo['node_type'] == 'SIGNATURE' || $this->nodeInfo['node_type'] == 'ADD FILE') {
			$choiceArr = array_merge($choiceAll, $choiceArr );
		} else {
			$choice = $choice - 1;
		}
		if( $choice == -1 ){
			$choice = 0;
		}
		echo<<<ENERGIE
  <tr>
  <td class="label"><label>Assign to Users</label></td>
  <td>\n
ENERGIE;
	foreach( $choiceArr AS $ch){
		if( $ch == "Force Access" || $ch == "Random"){
			echo "<input type=\"radio\" style=\"display:none\" value=\"$ch\"/>\n";
			}
		
		else{
			if( $ch == $choiceArr[$choice] ){
				echo "<input checked=\"checked\" type=\"radio\" name=\"userOption\" value=\"$ch\"/>$ch\n";
			}
			else{
			echo "<input type=\"radio\" name=\"userOption\" value=\"$ch\"/>$ch\n";
			}
		}	
	}
echo<<<ENERGIE
	</td>
  </tr>
ENERGIE;
	}
}
?>
