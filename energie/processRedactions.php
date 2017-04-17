<?php
require_once '../check_login.php';
require_once '../lib/redactionObj.php';
require_once '../lib/mime.php';

if($logged_in and $user->username) {
	if($_GET['func'] == 'insert') {
		$parseRedact = new parseRedact();
		$myData = file_get_contents('php://input');
		$parseRedact->parse($myData);
			$parseRedact->insertIntoTable($db_object, $user, $myData, $user->db_name, $db_doc);
		echo "OK";
	} elseif($_GET['func'] == 'getRedact') {
		$myData = file_get_contents('php://input');
		$parseRedact = new parseRedact();
		header('Content-type: text/xml');
		echo $parseRedact->getRedaction($db_object, $myData);
	} elseif($_GET['func'] == 'getStamps') {
		$stampDir = $DEFS['DATA_DIR'].'/'.$user->db_name.'/stamps/';
		$allStamps = array ();
		if(is_dir($stampDir)) {
			$dh = opendir($stampDir);
			$el = '';
			while($file = readdir($dh)) {
				if(is_file($stampDir.$file)) {
					$mimeType = getMimeType($stampDir.$file, $DEFS);
					if(strpos($mimeType, 'image') !== false) {
						$allStamps[] = $file;
					}
				}
			}
		}
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$xmlDoc = domxml_new_doc ('1.0');
			$root = $xmlDoc->create_element ('stamps');
			$xmlDoc->append_child($root);
			foreach ($allStamps as $stamp) {
				$el = $xmlDoc->create_element('stamp');
				$el->set_attribute('name', $stamp);
				$root->append_child($el);
			}
			$xmlStr = $xmlDoc->dump_mem(false);
		} else {
			$xmlDoc = new DOMDocument ();
			$root = $xmlDoc->createElement ('stamps');
			$xmlDoc->appendChild($root);
			foreach ($allStamps as $stamp) {
				$el = $xmlDoc->createElement('stamp');
				$el->setAttribute('name', $stamp);
				$root->appendChild($el);
			}
			$xmlStr = $xmlDoc->saveXML();
		}
		header('Content-type: text/xml');
		echo $xmlStr; 
	}
}
?>
