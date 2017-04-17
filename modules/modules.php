<?php
/*******************************************************/
/*This Function determines whether the module audit is
  enabled*/
function check_enable($mod, $department) {
	$mod = strtolower($mod);
	if(isset($_SESSION) and isset($_SESSION['modules_'.$department])) {
		$modules = $_SESSION['modules_'.$department];
	} else {
		$db_doc = getDbObject('docutron');
		$en = getTableInfo($db_doc,'modules',array(),array('enabled'=>1,'department'=>$department));
		$modules = array();
		while($row = $en->fetchRow()) {
			
			$modules[strtolower($row['real_name'])] = $row['enabled'];
		}
		if(isset($_SESSION)) {
			$_SESSION['modules_'.$department] = $modules;
		}
		$db_doc->disconnect ();
	}
	if(isset($modules[$mod])) {
		if($modules[$mod] == 1) {
			return true;
		}
	}
	return false;
}
/*******************************************************/
function ridEmpty($str ) {
	$arr = array ();
	$j = 0;
	for( $i=0; $i<sizeof($str);$i++ ) {
		if( trim($str[$i])=="" ){}//do nothing
		else {
			$arr[$j] = $str[$i];
			$j++;
		}
			
		
	}
	return $arr;
}


//**********************************************************************
//function that returns the number of unique file names in a cabinet
//used in modulesWeb.php
function getNumFilesInCab($cabname, $db_object) {
    $whereArr = array('display'=>1,'deleted'=>0,'filename'=>'IS NOT NULL');
    $count = getTableInfo($db_object,$cabname."_files",array('COUNT(id)'),$whereArr,'queryOne');
    return $count;
}

/*****************************************************
function that returns the number of departments on the system
*/
function getNumDepartments($db_object) {
	$count = getTableInfo($db_object, 'departments', array('COUNT(*)'), array(), 'queryOne');
    return $count;
}
?>
