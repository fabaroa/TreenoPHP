<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
	if(isset($_GET['delete']) and  $_GET['delete'] == 1 ) {
		$cabinet = $_GET['cabinet'];
		$updateArr = array();
		$updateArr['deleted'] = 1;
		$whereArr = array();
		$whereArr['real_name'] = $cabinet;
		updateTableInfo($db_object,'departments',$updateArr,$whereArr);	
		$user->audit("Cabinet marked for deletion","Cabinet: $cabinet");

		$whereArr = array('cab'=>$cabinet);		
		$idList = getTableInfo($db_object,'wf_documents',array('id'),$whereArr,'queryCol');
		deleteTableInfo($db_object,'wf_documents',$whereArr);

		$whereArr = array('department'=>$user->db_name);
		foreach($idList AS $id) {
		    $whereArr['wf_document_id'] = (int)$id;
		    deleteTableInfo($db_doc,'wf_todo',$whereArr);
		}
		$user->setSecurity (true);
	}

	if(isset($_GET['message'])) {
		$message = $_GET['message'];
	}
?>
<html>
<head>
	<title>Untitled Document</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body onload="window.location='home.php'">
</body>
</html>
<?php
	setSessionUser($user);
}else{
	logUserOut();
}
?>
