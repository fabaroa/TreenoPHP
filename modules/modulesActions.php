<?php
include_once '../check_login.php';
include_once '../classuser.inc';

function xmlUpdateModules($dep) {
	$xmlStr = file_get_contents('php://input');
	
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem($xmlStr);
	
		$modList = array();
		$mods = $domDoc->get_elements_by_tagname('MODULE');
		foreach($mods AS $mod) {
			$modList[] = $mod->get_content();
		}
		updateModules($dep,$modList);
	
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('ROOT');
		$xmlDoc->append_child($root);
	
		$m = $xmlDoc->create_element('MESSAGE');
		$root->append_child($m);
	
		$mess = "Modules successfully updated";
		$text = $xmlDoc->create_text_node($mess);
		$m->append_child($text);
	
		header('Content-type: text/xml');
		echo $xmlDoc->dump_mem(false);
	}
	else
	{
		$domDoc = DomDocument::loadXML ($xmlStr);
 		$mods = $domDoc->getElementsByTagName ('MODULE');
 		
 		$modList = array();	

		for ($i = 0; $i < $mods->length; $i++) {
			$myMod = $mods->item($i);
 			$modList[] = $myMod->nodeValue;
 		}
		updateModules($dep,$modList);
			
		$doc = new DomDocument();
		$root = $doc->createElement( "ROOT" );
		$doc->appendChild( $root );
		
		$m = $doc->createElement('MESSAGE');
		$root->appendChild($m);
	
		$mess = "Modules successfully updated";
		$text = $doc->createTextNode($mess);
		$m->appendChild($text);
	
		header('Content-type: text/xml');
		//error_log($doc->savexml());
		echo $doc->savexml();	
	}
}

function updateModules($dep,$modList) {
	$db_doc = getDbObject('docutron');

	$uArr = array('enabled' => 0);
	$wArr = array('department' => $dep);
	updateTableInfo($db_doc,'modules',$uArr,$wArr);

	$uArr = array('enabled' => 1);
	$modList[] = "Administration";
	foreach($modList AS $mod) {
		$wArr = array(	'department'=> $dep,
				'arb_name'	=> $mod);
		updateTableInfo($db_doc,'modules',$uArr,$wArr);
	}
}

if($logged_in ==1 && strcmp($user->username,"")!=0) {
	if(isSet($_GET['updateModules'])) {
		xmlUpdateModules($user->db_name);	
	}

	setSessionUser($user);
} else {
	logUserOut();
}
?>
