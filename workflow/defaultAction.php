<?php
echo<<<ENERGIE
<style>
</style>
 </head>
 <body>
  <center>
ENERGIE;
	if( $actionNeeded ) {
		if( $mess ) {
echo<<<ENERGIE
	<div class="error" align="center">$mess</div>
ENERGIE;
		}
		if( $nodeMess ) {
echo<<<ENERGIE
	<div class="lnk_black" align="center">$nodeMess</div>
ENERGIE;
		}
   echo <<<HTML
<form name="workflow" method="POST" action="getSignature.php?cab=$cab&doc_id=$doc_id&file_id=$file_id">
HTML;
		if ($reassignList) {
			echo <<<HTML
<!--<div class="mainDiv" style="position: relative; top: 2em">-->
<div class="mainDiv" style="margin-bottom: 2em;">
<div class="mainTitle">
<span>Reassign Owner</span>
</div>
<select name="reassigned_owner">
<option value="__default">Choose User</option>
HTML;
			foreach ($reassignList as $myUserName) {
				echo '<option value="'.$myUserName.'">'.$myUserName.'</option>';
			}
			echo <<<HTML
</select>
<p>
<input type="submit" name="reassign_now" value="Reassign Now" />
</p>
</div>
HTML;
		}
echo<<<ENERGIE
  <div class="mainDiv" style="width:450px">
   <div class="mainTitle">
    <span>Workflow Name::$workflowName Workflow Node::$nodeName</span>
   </div>
   <div class="inputForm" style="padding:5px" id="movefiles">
<table style="margin-right:auto;margin-left:auto">
 <tr class="tableheads">
  <td colspan="2"></td>
 </tr>
 <tr class="tableheads">
  <td colspan="2">{$nodeObj->header}</td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <textarea onFocus="removeInit()" name="notes" cols="40" rows="5">Enter notes here</textarea>
      </td>
     </tr>
ENERGIE;
	if( $nodeClass != "VALUENode" ) { 
echo<<<ENERGIE
     <tr>
      <td colspan="2" align="center">
   <input type="submit" name="Submit" value="Accept">
   <input type="submit" name="Submit" value="Reject">
	  </td>
     </tr>
ENERGIE;
	} else {
echo<<<ENERGIE
     <tr>
      <td colspan="2" align="center">
   <input type="submit" name="Submit" value="Accept">
	  </td>
     </tr>
ENERGIE;
	}
	$nodeObj->getExtraAction();
echo<<<ENERGIE
    </table>
   <br>\n
ENERGIE;
	$sArr = array('id');
	$wArr = array("cab='$cab'",
				'doc_id='.$doc_id,
				"(file_id=$file_id OR file_id=-2)",
				"status='IN PROGRESS'");
	$wfIds = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryCol');
	$selArr = array('username','date_time','notes','action');
	$wfHistory =
		getTableInfo ($db_object, 'wf_history', $selArr,
				array('wf_document_id'=>(int)$documentInfo['id']), 'queryAll', array('id' =>
					'DESC'));
		if( $wfHistory ) {
			echo "<div style=\"overflow:auto;height:250px\">\n";
		echo "<table>\n";
		echo "<tr class=\"tableheads\">\n";
		echo "<td colspan=\"4\">Document History</td>\n";
		echo "</tr>\n";
		echo "<tr class=\"tableheads\">\n";
		echo "<td style=\"border:0px;\">Username</td>\n";
		echo "<td style=\"border:0px;\">Date</td>\n";
		echo "<td style=\"border:0px;\">Action</td>\n";
		echo "<td style=\"border:0px;\">Notes</td>\n";
		echo "</tr>\n";
		foreach( $wfHistory AS $history ) {
			echo "<tr>\n";
			echo "<td>".str_replace(","," ",$history['username'])."</td>\n";
			echo "<td>".$history['date_time']."</td>\n";
			echo "<td>".$history['action']."</td>\n";
			echo "<td>".h($history['notes'])."</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";	
		echo "</div>\n";
		}
	    echo "</div>\n";
	   	echo "</div>\n";
		echo '</form>';
	} else {
echo<<<ENERGIE
	<div class="error" align="center">{$nodeObj->noActionMsg}</div>
ENERGIE;
//	<div class="error" style="font-size:24px;padding-top:25px" align="center">{$alertMessage}</div>
	}
echo<<<ENERGIE
  </center>
   </body>
</html>
ENERGIE;
?>
