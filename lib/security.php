<?php
function checkUser($hashCode, $db_object) {
	if($hashCode) {
		if (!empty ($_SESSION['user_hash_time']) and (time () - $_SESSION['user_hash_time']) < 10) {
			return true;
		}
		$hash = getTableInfo($db_object,'user_security',array(),array('hash_code'=>$hashCode));
		$row = $hash->fetchRow();
		$hash->free();
		$hash_id = $row['hash_code'];
		$status	= $row['status'];

		if($status == 2 or $status == 1) {
			if (isset ($_SESSION['user_hash_time'])) {
				unset ($_SESSION['user_hash_time']);
			}
			return false;
		} elseif(!$hash_id) { 
			if (isset ($_SESSION['user_hash_time'])) {
				unset ($_SESSION['user_hash_time']);
			}
			return false;
		}
		$_SESSION['user_hash_time'] = time ();
		return true; 
	} 
	return false;
}

?>
