<?PHP
include_once '../check_login.php';
include_once ('../classuser.inc');

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 )
{
	//NOTE: position and ordering are different
	//$value contains the position that user wants to move to
	$value = $_GET['value'] - 1; //minus one because users get the normalized view
	//$currentPos holds the current position that file is ordered
	$currentPos = $_GET['currentPos'] - 1; //minus one because users get the normalized view
	$tab = $_GET['tab'];
	$orderNum = $_GET['orderNum']; //ordering column in db for file
	$fileID = $_GET['fileID']; //id column in db for file

	//$allthumbs contains the $_GET array of the allthumbs.php 
	$allthumbs = $_SESSION['allThumbsGET'];
	$cab = $allthumbs['cab'];
	$doc_id = $allthumbs['doc_id'];
	
	//redirect back to allthumbs.php
	$redirect = $_SESSION['allThumbsURL'];
	
	//if statements check if tab is main
	$whereArr = array('doc_id'=>(int)$doc_id,'filename'=>'IS NULL','deleted'=>0,'display'=>1);
	if( $tab ) { 
		$whereArr['subfolder'] = $tab;
	} else{ //tab == main
		$whereArr['subfolder'] = 'IS NULL';
		$tab1 = "main";
	}
	$whereArr = array('doc_id'=>(int)$doc_id,'filename'=>'IS NULL','deleted'=>0,'display'=>1);
	$orderArr = array('ordering'=>'ASC');
	$idObject = getTableInfo($db_object,$cab."_files",array('id','ordering'),$whereArr,'query',$orderArr);

	//checks for bounds for the user input
	if( $value === NULL ) //if field is blank, do not move file
		$value = $currentPos;
	if( $value < 0 )
		$value = 0;


	$i = -1;
	while( ($row = $idObject->fetchRow()) && ($i < $value) )
	{
		//gets the id and ordering of the db row that file is supposed to go
		$idDesired = $row['id'];
		$orderingDesired = $row['ordering'];
		$i++;
	}

	//User wants to move file down the order list
	if($value > $currentPos) {
   		$updateArr = array('ordering'=>'ordering-1');
		$whereArr = array(
			"ordering"	=> "<= $orderingDesired",
			"ordering"	=> "> $orderNum",
			"doc_id"	=> "=".(int)$doc_id,
				 );
		if($tab) { 
			$whereArr['subfolder'] = "=".$tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr,1,1);

   		$updateArr = array('ordering'=>(int)$orderingDesired);
		$whereArr = array("id"=>(int)$fileID);
		updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);

		$direction = "down";
	} elseif($value < $currentPos) {//user wants to move file up the order list
   		$updateArr = array('ordering'=>'ordering+1');
		$whereArr = array(
			"ordering"	=> ">= $orderingDesired",
			"ordering"	=> "< $orderNum",
			"doc_id"	=> "=".(int)$doc_id,
				 );
		if($tab) { 
			$whereArr['subfolder'] = "=".$tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr,1,1);

   		$updateArr = array('ordering'=>(int)$orderingDesired);
		$whereArr = array("id"=>(int)$fileID);
		updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);

		$direction = "up";
	} else {
		$orderingDesired = $orderNum;
		$direction = "neither";
	}

	$selected = "s-".$doc_id.":".$tab1.":".$orderingDesired;

echo<<<ENERGIE
<script>
	var selectedRow = parent.sideFrame.selectedRow;

	if( selectedRow ) //if there was a file selected in allthumbs
	{
		var newCt = 0;
		var curCt = parent.sideFrame.tmp_count;
		var selectedRow_array = selectedRow.split(":");
		
		//if the file selected was the one being moved
		if( selectedRow_array[2] == $orderNum ) 
		{	
			parent.sideFrame.window.location = "$redirect&selected=$selected&count=$i";
		}
		//if the file selected is in a position moved ordered minus one
		else if( (selectedRow_array[2] <= $orderingDesired) && (selectedRow_array[2] > $orderNum) )
		{	
			var oldOrder = parseInt(selectedRow_array[2])-1;
			newCt = curCt - 1;
			var mySelected = "s-$doc_id:$tab:" + oldOrder;
			parent.sideFrame.location = "$redirect&selected=" + mySelected + "&count=" + newCt;
		}
		//if the file selected is in a position moved ordered plus one
		else if( (selectedRow_array[2] >= $orderingDesired) && (selectedRow_array[2] < $orderNum) )
		{
			var oldOrder = parseInt(selectedRow_array[2])+1;
			newCt = curCt + 1
			var mySelected = "s-$doc_id:$tab:" + oldOrder;
			parent.sideFrame.location = "$redirect&selected=" + mySelected + "&count=" + newCt;
		}
		//the file selected remains selected
		else
		{
			parent.sideFrame.location = "$redirect&selected=" + selectedRow + "&count=" + curCt;
		}
	}
	else //if no files were selected
	{
		parent.sideFrame.location = "$redirect";
	}
</script>
ENERGIE;

	setSessionUser($user);
}
else
{
//we want to log them out
echo<<<ENERGIE
<html>
 <body bgcolor="#FFFFFF">
  <script>
   document.onload = top.window.location = "../logout.php";
  </script>
 </body>
</html>
ENERGIE;
}

?>
