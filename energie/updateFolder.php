<?php
include_once ('../classuser.inc');
include_once '../lib/quota.php';
include_once '../lib/tabFuncs.php';
include_once '../check_login.php';
include_once '../lib/cabinets.php' ;
include_once '../search/search.php' ;

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
  $needField           = $trans['Must enter at least one field'];
  $successFolder       = $trans['Folder successfully added'];
  $gblStt = new GblStt ($user->db_name, $db_doc);

	if (isset ($_SESSION['lastURL'])) {
		$URL = $_SESSION['lastURL']; 
	} else {
		'';
	}
  $cab = $_GET['cab'];
	
	if($user->checkSecurity($cab) == 2) {
  //if addFolder request comes from inbox popup
	if (isset ($_GET['parent'])) {
		$parent = $_GET['parent'];
	} else {
		$parent = ''; 
	}

	if (isset ($_GET['table'])) {
		$table = $_GET['table'];
	} else {
		$table = ''; 
	}

	if (isset ($_GET['search'])) {
		$value = $_GET['search'];
	} else {
		$value = ''; 
	}
  $fieldnames = getCabinetInfo( $db_object, $cab );
  $mess = '';
  //SHOULD THERE BE A TABLE LOCK HERE? PROBABLY -- POTENTIAL RACE CONDITION.
  if(checkQuota($db_doc, 4096, $user->db_name)) {
	// make a new random folder for this new one
  
	// make the comma separated list of new indicies
	$indiceArr = array ();
	$info = array ();

	if (isset ($_POST[$fieldnames[0]])) {
		foreach ($fieldnames AS $indice) {
			$indiceArr[] = $_POST[$indice];
		}
	} else {
		$xmlStr = file_get_contents ('php://input');

		if (substr (PHP_VERSION, 0, 1) == '4') {
			$domDoc = domxml_open_mem($xmlStr);
			$domArr = $domDoc->get_elements_by_tagname ('FOLDER');
			foreach ($domArr as $dom) {
				$value = $dom->get_elements_by_tagname ('VALUE');
				$indiceArr[] = $value[0]->get_content ();
			}
		} else {
			$domDoc = new DOMDocument ();
			$domDoc->loadXML($xmlStr);
			$domArr = $domDoc->getElementsByTagName ('FOLDER');
			for ($i = 0; $i < $domArr->length; $i++) {
				$dom = $domArr->item($i);
				$value = $dom->getElementsByTagName ('VALUE');
				$tmp =$value->item(0);
				$indiceArr[] = $tmp->nodeValue;
			}
		}
	}
  } else
	$mess = "This Operation Will Exceed Quota Limit";

  if( $mess ) {
	if( strcmp($parent, "search") == 0 ) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$domDoc = domxml_new_doc("1.0");
			$entry = $domDoc->create_element("ENTRY");
			$domDoc->append_child($entry);
			$message = $domDoc->create_element("MESSAGE");
			$entry->append_child($message);
			$message->set_attribute('return', 1);
			$message->append_child($domDoc->create_text_node($mess));
			$xmlStr = $domDoc->dump_mem (true);
		} else {
			$domDoc = new DOMDocument ();
			$entry = $domDoc->createElement("ENTRY");
			$domDoc->appendChild($entry);
			$message = $domDoc->createElement("MESSAGE");
			$entry->appendChild($message);
			$message->setAttribute('return', 1);
			$message->appendChild($domDoc->createTextNode($mess));
			$xmlStr = $domDoc->saveXML ();
		}
		header('Content-type: text/xml');
		echo $xmlStr;
	} elseif( strcmp($parent, "inbox") == 0 ) {//parent
    	echo "<script>\n";
    	echo "window.location = \"addFolder.php?cab=$cab&mess=$mess&parent=$parent&table=$table&search=$value\"\n";
    	echo "</script>\n";    
	} elseif( strcmp($parent,"movefiles") == 0 ) {
		$curDoc_id = $_GET['doc_id'];
		$tab_id = $_GET['tab_id'];
		echo "<script>";
    	echo "window.location = \"addFolder.php?cab=$cab&doc_id=$curDoc_id&tab_id=$tab_id&mess=$mess&parent=$parent\"\n";
		echo "</script>";
	} else {
    	echo "<script>\n";
		echo "parent.mainFrame.window.location = \"addFolder.php?cab=$cab&mess=$mess\" \n";
    	echo "</script>\n";    
	}
  } elseif( !$indiceArr ) {
	if( strcmp($parent, "search") == 0 ) { 
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$domDoc = domxml_new_doc("1.0");
			$entry = $domDoc->create_element("ENTRY");
			$domDoc->append_child($entry);

			$message = $domDoc->create_element("MESSAGE");
			$entry->append_child($message);

			$message->set_attribute('return', 2);
			$message->append_child($domDoc->create_text_node($needField));
			$xmlStr = $domDoc->dump_mem(true);
		} else {
			$domDoc = new DOMDocument (); 
			$entry = $domDoc->createElement("ENTRY");
			$domDoc->appendChild($entry);

			$message = $domDoc->createElement("MESSAGE");
			$entry->appendChild($message);

			$message->setAttribute('return', 2);
			$message->appendChild($domDoc->createTextNode($needField));
			$xmlStr = $domDoc->saveXML ();
		}

		header('Content-type: text/xml');
		echo $xmlStr;
	} elseif( strcmp($parent, "inbox") == 0 ) {//parent
    	echo "<script>\n";
    	echo "window.location = \"addFolder.php?cab=$cab&mess=$needField&parent=$parent&table=$table&search=$value\"\n";
    	echo "</script>\n";    
	} elseif( strcmp($parent,"movefiles") == 0 ) {
		$curDoc_id = $_GET['doc_id'];
		echo "<script>";
    	echo "window.location = \"addFolder.php?cab=$cab&doc_id=$curDoc_id&mess=$needField&parent=$parent\"\n";
		echo "</script>";
	} else {
    	echo "<script>\n";
		echo "parent.mainFrame.window.location = \"addFolder.php?cab=$cab&mess=$needField\" \n";
   	 	echo "</script>\n";    
	}
  } else {
  	if(isset($_POST['needInsertAC']) and $_POST['needInsertAC'] == 1) {
  		$needInsertAC = true;
  	} else {
  		$needInsertAC = false;
  	}

	//this needs to be re-thought
	if( strcmp($parent,"movefiles") == 0) {
		$doc_id = createFolderInCabinet( $db_object, $gblStt, $db_doc, $user->username, $user->db_name, $cab, $indiceArr, $fieldnames, $table, $needInsertAC);
	} else {
		$doc_id = createFolderInCabinet( $db_object, $gblStt, $db_doc, $user->username, $user->db_name, $cab, $indiceArr, $fieldnames, $table);
	}

	if( strcmp($parent, "search") == 0 ) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$domDoc = domxml_new_doc("1.0");
			$entry = $domDoc->create_element("ENTRY");
			$domDoc->append_child($entry);

			$message = $domDoc->create_element("MESSAGE");
			$entry->append_child($message);

			$message->set_attribute('return', 3);
			$message->set_attribute('doc_id', $doc_id);
			$message->set_attribute('temp_table', $table);
			$message->append_child($domDoc->create_text_node($successFolder));
			$xmlStr = $domDoc->dump_mem (true);
		} else {
			$domDoc = new DOMDocument ();
			$entry = $domDoc->createElement("ENTRY");
			$domDoc->appendChild($entry);

			$message = $domDoc->createElement("MESSAGE");
			$entry->appendChild($message);

			$message->setAttribute('return', 3);
			$message->setAttribute('doc_id', $doc_id);
			$message->setAttribute('temp_table', $table);
			$message->appendChild($domDoc->createTextNode($successFolder));
			$xmlStr = $domDoc->saveXML ();
		}

		header('Content-type: text/xml');
		echo $xmlStr;

	} elseif( strcmp($parent, "inbox") == 0 ) {
		echo "<script>";
        //echo "window.location = '$URL';";
        echo "top.searchPanel.window.location = '../secure/inboxSelect1.php?cab=$cab&table=$table&search=$value&doc_id=$doc_id';";
        //needed to hide the div that creates a folder
        echo "top.mainFrame.document.getElementById('addNewFolderDiv').style.display = 'none'";
        echo "</script>";
	} elseif( strcmp($parent,"movefiles") == 0 ) {
		$origDoc_id = $_GET['doc_id'];
		$origCab = $_GET['original'];
		echo "<script>";
		echo "window.location = '../movefiles/departmentContents.php?cab=$origCab&doc_id=$origDoc_id&temp_table=$table&dispCab=$cab&dispFolder=$doc_id'";
		echo "</script>";
	} else {
		echo "<script>";
		echo "parent.mainFrame.window.location = 'addFolder.php?mess=$successFolder&cab=$cab'";
		echo "</script>";
	}
  }
	} else {
		logUserOut();
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
