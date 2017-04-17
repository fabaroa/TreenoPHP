<?php
chdir(dirname(__FILE__));
include_once '../db/db_common.php';
include_once '../workflow/node.inc.php';

$dept		= $argv[1];
$uname		= $argv[2];
$wf_doc_id	= $argv[3];
$wf_def_id	= $argv[4];
$cab		= $argv[5];
$doc_id		= $argv[6];
$file_id	= $argv[7];
$db_dept	= getDbObject($dept);
$db_doc		= getDbObject('docutron');

$sArr = array('departmentname');
$wArr = array('real_name' => $cab);
$cabDisp = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne'); 

$nodeObj = new fidelityNode($db_dept,$dept,$uname,$wf_doc_id,$wf_def_id,$cab,$cabDisp,$doc_id,$db_doc,$file_id); 
$nodeObj->processWorkflow();
?>
