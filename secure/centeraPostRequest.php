<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser()) {
	$xmlStr = file_get_contents('php://input');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$domDoc = domxml_open_mem( $xmlStr );
		$domArr = $domDoc->get_elements_by_tagname( 'setting' );
		$settingsArr = array();
		foreach( $domArr as $dom ) {
			$value	= $dom->get_content();
			$k		= $dom->get_attribute('name');

			$settingsArr[$k] = $value;
		}
	} else {
		$domDoc = new DOMDocument ();
		$domDoc->loadXML( $xmlStr );
		$domArr = $domDoc->getElementsByTagName( 'setting' );
		$settingsArr = array();
		for($i = 0; $i < $domArr->length; $i++) {
			$dom = $domArr->item($i);
			$value	= $dom->nodeValue;
			$k		= $dom->getAttribute('name');
			$settingsArr[$k] = $value;
		}
	}
	header('Content-type: text/xml');
	echo xmlSetCenteraSettings($user,$settingsArr);
}

function xmlSetCenteraSettings($user,$settingsArr) {
	$db_doc = getDbObject ('docutron');
	setCenteraSettings($user,$settingsArr, $db_doc);
	
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$doc = domxml_new_doc("1.0");
		$root = $doc->create_element('ROOT');
		$doc->append_child($root);

		$mess = $doc->create_element('MESSAGE');
		$root->append_child($mess);

		$text = $doc->create_text_node('Settings Successfully Updated');
		$mess->append_child($text);
		$xmlStr = $doc->dump_mem(true);
	} else {
		$doc = new DOMDocument ();
		$root = $doc->createElement('ROOT');
		$doc->appendChild($root);

		$mess = $doc->createElement('MESSAGE');
		$root->appendChild($mess);

		$text = $doc->createTextNode('Settings Successfully Updated');
		$mess->appendChild($text);
		$xmlStr = $doc->saveXML ();
	}

	header('Content-type: text/xml');
	return $xmlStr;
}

function setCenteraSettings($user,$settingsArr, $db_doc) {
	$gblStt = new Gblstt($user->db_name, $db_doc);	
	foreach($settingsArr AS $k => $v) {
		$gblStt->set($k,$v);
	}
}
?>
