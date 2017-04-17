<?php
include_once '../check_login.php';
include_once '../lib/imageFuncs.php';
include_once '../lib/settings.php';
include_once '../lib/versioning.php';
include_once '../lib/mime.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$cab 	= $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$type 	= $_GET['type'];

	$check 	= $_POST['check'];

	$sArr = array('location');
	$wArr = array('doc_id' => (int)$doc_id);
	$loc = getTableInfo($db_object,$cab,$sArr,$wArr,'queryOne');
	$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
	foreach($check AS $id) {
		if(!isFileVersioned($cab,$id,$db_object)) {
			makeVersioned($cab,$id,$db_object);
		}
		$sArr = array('subfolder','filename','ordering','parent_id','parent_filename','file_size');
		$wArr = array('id' => (int)$id);
		$fileInfo = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryRow');

		$ext = strtolower(getExtension($fileInfo['filename']));
		if($ext == "tif" || $ext == "tiff") {
			$versInfo = getNewestVersion($cab,$doc_id,$fileInfo['parent_id'],$db_object);
			$filepath = $location."/".$fileInfo['subfolder']."/".$fileInfo['filename'];	
		
			$next = $versInfo['v_minor'] + 1;
			$fname = str_replace(".".$ext,"",$fileInfo['parent_filename']);
			$name = $fname."-".$versInfo['v_major']."_".$next.".".$ext;
			$newfilepath = $location."/".$fileInfo['subfolder']."/".$name;	
			if($type == "rotate") {
				rotate90($filepath,$newfilepath, $DEFS);	
			} elseif($type == "flip") {
				flip($filepath,$newfilepath, $DEFS);
			} elseif($type == "crop") {
			}
			$insertArr = array( 'doc_id' 			=> (int)$doc_id,
								'subfolder'			=> ($fileInfo['subfolder']) ? $fileInfo['subfolder'] : null,
								'filename'			=> $name,
								'parent_filename'	=> $fileInfo['parent_filename'],
								'parent_id'			=> (int)$fileInfo['parent_id'],
								'v_major'			=> (int)$versInfo['v_major'],
								'v_minor'			=> (int)$next,
								'display'			=> 1,
								'file_size'			=> (int)$fileInfo['file_size'],
								'date_created'		=> date('Y-m-d G:i:s'),
								'ordering'			=> (int)$fileInfo['ordering'] );
			$db_object->extended->autoExecute($cab.'_files',$insertArr);
		
			$wArr = array('id' => (int)$id);
			updateTableInfo($db_object,$cab.'_files',array('display' => 0),$wArr);
		}
	}
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$doc = domxml_new_doc("1.0");
		$root = $doc->create_element("LOCATION");
		$root = $doc->append_child($root);
		$text = $doc->create_text_node($_SESSION['allThumbsURL']);
		$root->append_child($text);
		$xmlStr = $doc->dump_mem(true);
	} else {
		$doc = new DOMDocument (); 
		$root = $doc->createElement("LOCATION");
		$root = $doc->appendChild($root);
		$text = $doc->createTextNode($_SESSION['allThumbsURL']);
		$root->appendChild($text);
		$xmlStr = $doc->saveXML ();
	}

	header('Content-type: text/xml');
	echo $xmlStr;

	setSessionUser($user);
} else {
	logUserOut();
}
?>
