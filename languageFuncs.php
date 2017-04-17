<?php
//this file contains functions used in getting the language of choice for the 
//application
include_once 'settings/settings.php';
//include_once 'db/db_engine.php';
include_once 'db/db_common.php';

//gets the language array from the database
if (!function_exists('getLang')) {
	function getLang(&$db_docutron, $lang) {
		$res = getTableInfo($db_docutron, 'language', array('k', $lang));
		while ($row = $res->fetchRow()) {
			$trans[$row['k']] = $row[$lang];
		}
		return $trans;
	}
}
?>
