<?php
include '../db/db_common.php';
$department='client_files';
// set what cabinet to be extracted
$db_dept = getDbObject($department);

$query="SELECT [state]
      ,[defs_name]
      ,[prev]
      ,[next]
      ,[parent_id]
      ,[node_id]
      ,[owner]
      ,[node_type]
      ,[node_name]
  FROM [wf_defs],wf_nodes
  where [wf_defs].node_id = wf_nodes.id and parent_id = [wf_defs].id and defs_name='multiple test'";
$startNode=$db_dept->queryAll($query);
print_r($startNode);
$parentID = $startNode[0]['parent_id'];
while ($startNode[0]['next'] != $startNode[0]['node_id'])
{
	$query="SELECT [state]
		  ,[defs_name]
		  ,[prev]
		  ,[next]
		  ,[parent_id]
		  ,[node_id]
		  ,[owner]
		  ,[node_type]
		  ,[node_name]
	  FROM [wf_defs],wf_nodes
	  where [wf_defs].node_id = wf_nodes.id and parent_id = ".$parentID." and [wf_defs].id=".$startNode[0]['next'];
	$startNode=$db_dept->queryAll($query);
	print_r($startNode);
}	
  
$db_dept->disconnect();
?>