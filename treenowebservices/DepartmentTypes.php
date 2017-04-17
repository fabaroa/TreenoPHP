<?php
// $Id: DepartmentTypes.php 14326 2011-04-11 20:31:25Z fabaroa $
/*
 * This file contains the department level types
 */

// 
class DepartmentItem {
	var $realName;
	var $arbName;
	
	function DepartmentItem($realName = NULL, $arbName = NULL) {
		$this->realName = $realName;
		$this->arbName  = $arbName;
	}
}

?>