<?PHP
//$Id$
include_once '../db/db_common.php';
include_once '../lib/webServices.php';
include_once '../search/search.php';
require_once '../lib/indexing.inc.php';
require_once '../barcode/barcode.php';
require_once '../barcode/c128bobject.php';

//$barcode contains the barcode value
function generateBCImage($barcode,$username=null)
{
	global $DEFS;
	$tmp = $DEFS['TMP_DIR'];
	$uniqueFilename = Indexing::makeUnique("barcode$barcode");
	$baseOutput = $tmp."/".$uniqueFilename;
	/*
	if ($username=="tvs"){
		$w = 220;
		$h = 80;
		shell_exec("/usr/local/bin/barcode -E -n -m 0 -g " . $w . "x" . $h . " -e code128b -b \"$barcode\" -o $baseOutput.ps");
	} else {
		$w = 500;
		$h = 250;
		shell_exec("/usr/local/bin/barcode -E -m 0 -g " . $w . "x" . $h . " -e code128b -b \"$barcode\" -o $baseOutput.ps");
	}
	shell_exec($DEFS['PSTOPNM_EXE'] . " -pbm -xsize=".$h." -ysize=".$w." -xborder=0 -yborder=0 $baseOutput.ps");
	shell_exec($DEFS['PAMFLIP_EXE'] . " -rotate270 $baseOutput"."001.pbm > $baseOutput"."002.pbm");
	shell_exec($DEFS['PNMTOJPEG_EXE'] . " -quality=100 $baseOutput"."002.pbm > $baseOutput.jpg");

	if( file_exists("$baseOutput.jpg") ) {
		$st = stat( "$baseOutput.jpg" );
		$barcodeStr = file_get_contents("$baseOutput.jpg");
		unlink("$baseOutput.jpg");
	} else {
		$barcodeStr = "";
	}
	*/
	if ($username=="tvs"){
		$width = 220;
		$height = 80;
	} else {
		$width = 500;
		$height = 250;
	}
	$code=$barcode;
	//extract($_GET);

	$style   = 164;
	$xres    = 2;
	$font    = 4;
	$obj = new C128BObject($width, $height, $style, $code);

	if($obj) {
		$obj->SetFont($font);   
		$obj->DrawObject($xres);
		ImageJpeg($obj->mImg,"$baseOutput.jpg");
		//$obj->FlushObject();
		$obj->DestroyObject();
		unset($obj);  /* clean */
	}
	if( file_exists("$baseOutput.jpg") ) {
		$st = stat( "$baseOutput.jpg" );
		$barcodeStr = file_get_contents("$baseOutput.jpg");
		unlink("$baseOutput.jpg");
	} else {
		$barcodeStr = "";
	}
	 
	return base64_encode($barcodeStr);
}

class webServicesBarcode {
	var $loginName; //This is the username that logged in to the sys
	var $department;
	var $cabinetID;
	var $cabinet;
	var $searchArr;
	var $deleteBC; // 1 or 0
	var $scanFormat; // 'stif' 'mtif' 'pdf' 'asis'
	var $sendImage; // 1 or 0
	var $user; //user passed through xml
	var $compress; // 1 or 0
	var $searchType; // 'create' 'searchcreate' 'searchdonotcreate'
	var $getTabsBC; // 1 or 0
	var $retXMLStr;

	function webServicesBarcode($userName, $domDoc) {
		$this->retXMLStr = "";
		$this->loginName = $userName;
		$this->parseXml($domDoc);
		$doc_id = $this->goSearch();
		if($doc_id !== 0) {
			$this->retXMLStr = $this->generateRetXML($doc_id);
		}
	}

	//This is the pseudo accessor function to retrieve
	function getRetXML() {
		return $this->retXMLStr;
	}

	/*
	<barcode>
		<department value='' />
		<cabinetID value='' />
		<cabinet value='' />
		<terms>
			<term index='' value='' />
			<term index='' value='' />
		</terms>
		<deletebc value='1' />
		<scanformat value='stif' />
		<sendimage value='1' />
		<user value='' />
		<compress value='1' />
		<searchtype value='searchcreate' />
		<getTabsBC value='0' />
	</barcode>
	*/
	function parseXml($domDoc) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$department = $domDoc->get_elements_by_tagname('department');
			if($department) {
				$this->department = $department[0]->get_attribute('value');
			} else {
				unset($department);
			}
	
			$cabinetID = $domDoc->get_elements_by_tagname('cabinetID');
			$this->cabinetID = $cabinetID[0]->get_attribute('value');	
			$cabinet = $domDoc->get_elements_by_tagname('cabinet');
			$this->cabinet = $cabinet[0]->get_attribute('value');	
			$term = $domDoc->get_elements_by_tagname('term');
			$searchArr = array();
			foreach($term AS $index) {
				$indexName = strtolower($index->get_attribute('index'));
				$searchArr[$indexName] = '"'.trim($index->get_attribute('value')).'"';
			}
			$this->searchArr = $searchArr;

			$deleteBC = $domDoc->get_elements_by_tagname('deletebc');
			$this->deleteBC = $deleteBC[0]->get_attribute('value');
			$scanFormat = $domDoc->get_elements_by_tagname('scanformat');
			$this->scanFormat = $scanFormat[0]->get_attribute('value');	
			$sendImage = $domDoc->get_elements_by_tagname('sendimage');
			$this->sendImage = $sendImage[0]->get_attribute('value');	
			$user = $domDoc->get_elements_by_tagname('user');
			$this->user = $user[0]->get_attribute('value');	
			$compress = $domDoc->get_elements_by_tagname('compress');
			$this->compress = $compress[0]->get_attribute('value');	
			$searchType = $domDoc->get_elements_by_tagname('searchtype');
			$this->searchType = $searchType[0]->get_attribute('value');
			$getTabsBC = $domDoc->get_elements_by_tagname('getTabsBC');
			$this->getTabsBC = $getTabsBC[0]->get_attribute('value');
		} else {
			$department = $domDoc->getElementsByTagName('department');
			if($department) {
				$department = $department->item(0);
				$this->department = $department->getAttribute('value');
			} else {
				unset($department);
			}

			$cabinetID = $domDoc->getElementsByTagName('cabinetID');
			$cabinetID = $cabinetID->item(0);
			$this->cabinetID = $cabinetID->getAttribute('value');
			$cabinet = $domDoc->getElementsByTagName('cabinet');
			$cabinet = $cabinet->item(0);
			$this->cabinet = $cabinet->getAttribute('value');
			$term = $domDoc->getElementsByTagName('term');
			$searchArr = array();
			foreach($term AS $index) {
				$indexName = strtolower($index->getAttribute('index'));
				$searchArr[$indexName] = '"'.trim($index->getAttribute('value')).'"';
			}
			$this->searchArr = $searchArr;

			$deleteBC = $domDoc->getElementsByTagName('deletebc');
			$deleteBC = $deleteBC->item(0);
			$this->deleteBC = $deleteBC->getAttribute('value');
			$scanFormat = $domDoc->getElementsByTagName('scanformat');
			$scanFormat = $scanFormat->item(0);
			$this->scanFormat = $scanFormat->getAttribute('value');
			$sendImage = $domDoc->getElementsByTagName('sendimage');
			$sendImage = $sendImage->item(0);
			$this->sendImage = $sendImage->getAttribute('value');
			$user = $domDoc->getElementsByTagName('user');
			$user = $user->item(0);
			$this->user = $user->getAttribute('value');
			$compress = $domDoc->getElementsByTagName('compress');
			$compress = $compress->item(0);
			$this->compress = $compress->getAttribute('value');
			$searchType = $domDoc->getElementsByTagName('searchtype');
			$searchType = $searchType->item(0);
			$this->searchType = $searchType->getAttribute('value');
			$getTabsBC = $domDoc->getElementsByTagName('getTabsBC');
			$getTabsBC = $getTabsBC->item(0);
			$this->getTabsBC = $getTabsBC->getAttribute('value');
		}
	}

	/*
	<barcodeInfo>
		<department value=""/>
		<cabinetID value="" />
		<docID value="" />
		<barcode value="" />
		<barcodeStr value="" />
		<tabs>
			<tab id="" value="" barcode="" barcodeStr="" />
		</tabs>
	</barcodeInfo>
	*/
	function generateRetXML($doc_id) {
		$db_dept = getDBObject($this->department);
		$db_doc = getDbObject ('docutron');
		$retXML = "";
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$retXML = $this->genRetXML_PHP4($db_dept, $db_doc, $doc_id);
		} else {
			$retXML = $this->genRetXML_PHP5($db_dept, $db_doc, $doc_id);
		}
		return $retXML;
	}

	function genRetXML_PHP5($db_dept, $db_doc, $doc_id) {
		$xmlDoc = new DOMDocument();
		$root = $xmlDoc->createElement('barcodeInfo');
		$xmlDoc->appendChild($root);

		$department = $xmlDoc->createElement('department');
		$root->appendChild($department);
		$department->setAttribute('value', $this->department);

		$cabinetID = $xmlDoc->createElement('cabinetID');
		$root->appendChild($cabinetID);
		$cabinetID->setAttribute('value', $this->cabinetID);

		$docIDNode = $xmlDoc->createElement('docID');
		$root->appendChild($docIDNode);
		$docIDNode->setAttribute('value', $doc_id);
	
		if( $this->getTabsBC == 1 ) {
			$cabSavedTabs = GetTabList($db_dept, $this->cabinetID, $doc_id, $this->loginName);
			$savedTabs = $xmlDoc->createElement('tabs');
			$root->appendChild($savedTabs); //<tabs>
			foreach($cabSavedTabs AS $tabArr) {
				$tabID = $tabArr['id'];
				$tab = $tabArr['subfolder'];
//				if ($this->loginName=='tvs' && $this->sendImage == $tab) {
				if ($this->sendImage == $tab) {
					$tabBC = getFolderBarcode($this->loginName, $this->department, 
						$this->cabinetID, $doc_id, $tabID, $db_doc, $db_dept, $this->deleteBC, 
						$this->scanFormat, $this->compress, $this->user);
					$tabBCStr = "";
					if($this->sendImage === '1') { 
						$tabBCStr = generateBCImage($tabBC,$this->loginName);
					} else if($this->sendImage == $tab) { 
						$tabBCStr = generateBCImage($tabBC,$this->loginName);
					} 
	
					$savedTab = $xmlDoc->createElement('tab');
					$savedTabs->appendChild($savedTab); // <tab>
					$savedTab->setAttribute('id', $tabID);
					$savedTab->setAttribute('value', $tab);
					$savedTab->setAttribute('barcode', $tabBC);
					$savedTab->setAttribute('barcodeStr', $tabBCStr);
				}
			}
		} else {
			$barcode = getFolderBarcode($this->loginName, $this->department, $this->cabinetID, 
				$doc_id, 0, $db_doc, $db_dept, $this->deleteBC, $this->scanFormat, $this->compress, $this->user);	
			$barcodeNode = $xmlDoc->createElement('barcode');
			$root->appendChild($barcodeNode);
			$barcodeNode->setAttribute('value', $barcode);

			$barcodeStr = "";
			if($this->sendImage === '1') { 
				$barcodeStr = generateBCImage($barcode);
			}
			$bcStrNode = $xmlDoc->createElement('barcodeStr');
			$root->appendChild($bcStrNode);
			$bcStrNode->setAttribute("value", $barcodeStr);
		}
		$xmlStr = $xmlDoc->saveXML();
		return $xmlStr;
	}

	function genRetXML_PHP4($db_dept, $db_doc, $doc_id) {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('barcodeInfo');
		$xmlDoc->append_child($root);

		$department = $xmlDoc->create_element('department');
		$root->append_child($department);
		$department->set_attribute("value", $this->department);

		$cabinetID = $xmlDoc->create_element('cabinetID');
		$root->append_child($cabinetID);
		$cabinetID->set_attribute("value", $this->cabinetID);

		$docIDNode = $xmlDoc->create_element('docID');
		$root->append_child($docIDNode);
		$docIDNode->set_attribute("value", $doc_id);

		if( $this->getTabsBC == 1 ) {
			$cabSavedTabs = GetTabList($db_dept, $this->cabinetID, $doc_id, $this->loginName);
	       	$savedTabs = $xmlDoc->create_element('tabs');
	       	$root->append_child($savedTabs); //<tabs>
	       	foreach($cabSavedTabs AS $tabArr) {
				$tabID = $tabArr['id'];
				$tab = $tabArr['subfolder'];
				$tabBC = getFolderBarcode($this->loginName, $this->department, 
					$this->cabinetID, $doc_id, $tabID, $db_doc, $db_dept, $this->deleteBC, 
					$this->scanFormat, $this->compress, $this->user);
				$tabBCStr = "";
				if($this->sendImage === '1') { 
					$tabBCStr = generateBCImage($tabBC,$this->loginName);
				} else if($this->sendImage == $tab) { 
					$tabBCStr = generateBCImage($tabBC,$this->loginName);
				} 
				
    	       	$savedTab = $xmlDoc->create_element('tab');
	       	    $savedTabs->append_child($savedTab); //<tab>
    	   	   	$savedTab->set_attribute("id", $tabID);
				$savedTab->set_attribute("value", $tab);
				$savedTab->set_attribute("barcode", $tabBC);
				$savedTab->set_attribute("barcodeStr", $tabBCStr);
        	}
    	} else {
			$barcode = getFolderBarcode($this->loginName, $this->department, $this->cabinetID, 
				$doc_id, 0, $db_doc, $db_dept, $this->deleteBC, $this->scanFormat, $this->compress, $this->user);	
			$barcodeNode = $xmlDoc->create_element('barcode');
			$root->append_child($barcodeNode);
			$barcodeNode->set_attribute("value", $barcode);
	
			$barcodeStr = "";
			if($this->sendImage === '1') { 
				$barcodeStr = generateBCImage($barcode);
			}
			$bcStrNode = $xmlDoc->create_element('barcodeStr');
			$root->append_child($bcStrNode);
			$bcStrNode->set_attribute("value", $barcodeStr);
		}

		return $xmlDoc->dump_mem(true);
	}

	function goSearch() {
		$doc_id = 0;
		$db_object = getDBObject($this->department);
		$db_doc = getDbObject ('docutron');
		switch($this->searchType) {
			case 'create':
				$doc_id = createCabinetFolder($this->department, $this->cabinetID, $this->searchArr, $this->loginName, $db_doc, $db_object);
				break;
			case 'searchcreate':
				$searchInfo = searchCabinet($this->department, $this->cabinetID, $this->searchArr, $this->loginName, $db_doc, $db_object);
				if( $searchInfo[1] === false || $searchInfo[1] == 0 ) { //The count of results does not exist or is zero
					$doc_id = createCabinetFolder($this->department, $this->cabinetID, $this->searchArr, $this->loginName, $db_doc, $db_object);
				} else {
					$doc_id = getTableInfo($db_object, $searchInfo[0], array('result_id'), array('table_id'=>1), 'queryOne');
				}
				break;
			case 'searchdonotcreate':
				$searchInfo = searchCabinet($this->department, $this->cabinetID, $this->searchArr, $this->loginName);
				if( $searchInfo[1] === false || $searchInfo[1] == 0 ) { //The count of results does not exist or is zero
					$doc_id = 0;
				} else {
					$doc_id = getTableInfo($db_object, $searchInfo[0], array('result_id'), array('table_id'=>1), 'queryOne');
				}
				//return "" if does not exist
				break;
			default:
				$doc_id = 0;
				break;
		}
		$db_doc->disconnect ();
		$db_object->disconnect ();
		return $doc_id;
	}

}
?>
