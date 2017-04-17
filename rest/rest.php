<?php
require_once '../db/db_common.php';
require_once '../lib/utility.php';
require_once '../lib/crypt.php';
require_once '../lib/mime.php';
require_once '../lib/webServices.php';
//require_once '../lib/treenoServices.php';
require_once '../lib/settings.php';
require_once '../lib/xmlObj.php';
require_once '../lib/ldap.php';

/*
	ERROR RETURN:

	<element name="errors" xmlns="http://relaxng.org/ns/structure/1.0"
			datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">   
		<oneOrMore>
			<element name="error">
				<attribute name="message">
					<data type="string" />
				</attribute>
			</element>
		</oneOrMore>
	</element>
*/
if (!empty ($_GET['method'])) {
	$args =& $_GET;
} elseif (!empty ($_POST['method'])) {
	$args =& $_POST;
} else {
	header ('Content-type: text/xml');
	die ('<errors><error message="REQUESTMETHOD"/></errors>');
}


$db = getDbObject ('docutron');

switch ($args['method']) {
	case 'login':
		doLogin ($db, $args['userName'], $args['md5Pass']);
		break;
	case 'getTodos':
		doGetTodos ($db, $args['passKey']);
		break;
	case 'getCabinetIndices':
		doGetCabinetIndices ($db, $args['passKey'], $args['department'],
				$args['cabinet']);
		break;
	case 'getCabinetSavedTabs':
		doGetCabinetSavedTabs ($db, $args['passKey'], $args['department'],
				$args['cabinet']);
		break;
	case 'isDocumentView':
		doIsDocumentView ($db, $args['passKey'], $args['department'],
				$args['cabinet']);
		break;
	case 'getDocumentTypeList':
		doGetDocumentTypeList ($db, $args['passKey'], $args['department'], $args['cabinet']);
		break;
	case 'getGenericDocumentTypeList':
		doGenericDocumentTypeList ($db, $args['passKey'], $args['department'], $args['cabinet'], $args['docID']);
		break;
	case 'getDepartmentList':
		doGetDepartmentList ($db, $args['passKey']);
		break;
	case 'getUploadCreds':
		doGetUploadCreds ($db, $args['passKey'], $args['department']);
		break;
	case 'getPersonalInboxBarcode':
		doGetPersonalInboxBarcode ($db, $args['passKey'], $args['department']);
		break;
	case 'getUniqueFolderFileCount':
        	doGetUniqueFolderFileCount($db, $args['passKey'], $args['department'],$args['cabinet']);
        	break;
	case 'getZipFile':
		doGetZipFile ($db, $args['passKey'], $args['department'], $args['cabinet'], $args['filename'], $args['sleepInUSec']);
		break;
	case 'getAttachment':
		doGetAttachment ($db, $args['passKey'], $args['department'], $args['cabinetID'], $args['fileID'], $args['sleepInUSec']);
		break;
	case 'getCabinetStructure':
		doGetCabinetStructure ($db, $args['passKey'], $args['xmlInfo']);
		break;
	case 'getMapping':
		doGetMapping ($args['passKey'], $args['department']);
		break;
	case 'tripleDesEncrypt':
		doTripleDesEncrypt ($args['str']);
		break;
	case 'getBarcodeFolder':
		$db = getDbObject ($args['department']);
		doGetBarcodeFolder ($db, $args['passKey'], $args['barcode']);
		break;
//	case 'tripleDesDecrypt':
//		doTripleDesDecrypt ($args['str']);
//		break;
	default:
		break;
}

function doLogin ($db, $userName, $md5Pass) {
	$userName = strtolower ($userName);
	$usersInfo = getTableInfo($db, 'users', array(), array('username'
				=> $userName));
	$retXML = '';
	if($row = $usersInfo->fetchRow()) {
		if ($row['ldap_id'] != 0) {
			$myPass = tdDecrypt ($md5Pass);
			if (checkLDAPPassword ($db, $row['ldap_id'], $userName, $myPass)) {
				$key = $userName.','.(time() + 300000);
				$message = weakEncrypt($key);
				$retXML = '<loggedin key="'.$message.'"/>';
			} else {
				$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
			}
		} else {
			if(strtoupper($row['password']) == strtoupper($md5Pass)) {
				$key = $userName.','.(time() + 300000);
				$message = weakEncrypt($key);
				$retXML = '<loggedin key="'.$message.'"/>';
			} else {
				$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
			}
		}
	} else {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	}
	header ('Content-type: text/xml');
	echo $retXML;
}

function doGetTodos ($db, $passKey) {
	list($retVal, $userName) = checkKey($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		$arbCabList = array ();
		$arbDeptList = array ();
		$allIndexNames = array ();
		$todoArr = getUserWorkflowTodoList ($db, $userName,
				$arbDeptList, $arbCabList, $allIndexNames);
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$xmlDoc = domxml_new_doc ('1.0');
			$root = $xmlDoc->create_element ('userTodos');
			$xmlDoc->append_child ($root);
			foreach ($todoArr as $department => $deptArr) {
				$deptEl = $xmlDoc->create_element ('department');
				$root->append_child ($deptEl);
				$deptEl->set_attribute ('name', $department);
				$deptEl->set_attribute ('displayName', $arbDeptList[$department]);
				foreach ($deptArr as $cabinet => $todoArr) {
					$cabEl = $xmlDoc->create_element ('cabinet');
					$deptEl->append_child ($cabEl);
					$indices = $allIndexNames[$department][$cabinet];
					$cabEl->set_attribute ('name', $cabinet);
					$cabEl->set_attribute ('displayName',
							$arbCabList[$department][$cabinet]);
					$indEl = $xmlDoc->create_element ('indices');
					foreach ($indices as $index) {
						$tmpEl = $xmlDoc->create_element ('index');
						$tmpEl->append_child ($xmlDoc->create_text_node ($index));
						$indEl->append_child ($tmpEl);
					}
					$cabEl->append_child ($indEl);
					foreach ($todoArr as $myTodo) {
						$todoEl = $xmlDoc->create_element ('todo');
						$todoEl->set_attribute ('notified', $myTodo['notified']);
						$todoEl->set_attribute ('nodeName', $myTodo['nodeName']);
						$todoEl->set_attribute ('nodeType', $myTodo['nodeType']);
						$todoEl->set_attribute ('link', $myTodo['link']);
						foreach ($indices as $index) {
							$todoEl->set_attribute ($index,
									$myTodo['folder'][$index]);
						}
						$cabEl->append_child ($todoEl);
					}
				}
			}
			$xmlStr = $xmlDoc->dump_mem (false);
		} else {
			$xmlDoc = new DOMDocument ('1.0');
			$root = $xmlDoc->createElement ('userTodos');
			$xmlDoc->appendChild ($root);
			foreach ($todoArr as $department => $deptArr) {
				$deptEl = $xmlDoc->createElement ('department');
				$root->appendChild ($deptEl);
				$deptEl->setAttribute ('name', $department);
				$deptEl->setAttribute ('displayName', $arbDeptList[$department]);
				foreach ($deptArr as $cabinet => $todoArr) {
					$cabEl = $xmlDoc->createElement ('cabinet');
					$deptEl->appendChild ($cabEl);
					$indices = $allIndexNames[$department][$cabinet];
					$cabEl->setAttribute ('name', $cabinet);
					$cabEl->setAttribute ('displayName',
							$arbCabList[$department][$cabinet]);
					$indEl = $xmlDoc->createElement ('indices');
					foreach ($indices as $index) {
						$tmpEl = $xmlDoc->createElement ('index');
						$tmpEl->appendChild ($xmlDoc->createTextNode ($index));
						$indEl->appendChild ($tmpEl);
					}
					$cabEl->appendChild ($indEl);
					foreach ($todoArr as $myTodo) {
						$todoEl = $xmlDoc->createElement ('todo');
						$todoEl->setAttribute ('notified', $myTodo['notified']);
						$todoEl->setAttribute ('nodeName', $myTodo['nodeName']);
						$todoEl->setAttribute ('nodeType', $myTodo['nodeType']);
						$todoEl->setAttribute ('link', $myTodo['link']);
						foreach ($indices as $index) {
							$todoEl->setAttribute ($index,
									$myTodo['folder'][$index]);
						}
						$cabEl->appendChild ($todoEl);
					}
				}
			}
			$xmlStr = $xmlDoc->saveXML ();
		}
		header ('Content-type: text/xml');
		echo $xmlStr;
	}
}
/*
 <?xml version="1.0"?>
<response>
  <passKey>2M3Z1dCRnpypmZ2flJmhnQ==</passKey>
  <DocumentTypeList>
    <department>client_files</department>
    <user>admin</user>
    <cabinet>
      I_Love_My_Job<docID>1</docID><document_type>
        <real_document_name>document4</real_document_name>
        <arbitrary_document_name>Appraisal</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document9</real_document_name>
        <arbitrary_document_name>Cancellation Reinstatement</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document5</real_document_name>
        <arbitrary_document_name>Correspondence</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document6</real_document_name>
        <arbitrary_document_name>Email</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>to</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f2</real_index_name>
            <arbitrary_index_name>from</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f3</real_index_name>
            <arbitrary_index_name>subject</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f4</real_index_name>
            <arbitrary_index_name>date</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f5</real_index_name>
            <arbitrary_index_name>cc</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f6</real_index_name>
            <arbitrary_index_name>bcc</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document8</real_document_name>
        <arbitrary_document_name>Endorsement</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
          <document_index>
            <real_index_name>f2</real_index_name>
            <arbitrary_index_name>status</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document13</real_document_name>
        <arbitrary_document_name>Estimate</arbitrary_document_name>
        <document_indices />
        <definitions />
      </document_type><document_type>
        <real_document_name>document3</real_document_name>
        <arbitrary_document_name>Financials</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document14</real_document_name>
        <arbitrary_document_name>IIS-test</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>desc</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document2</real_document_name>
        <arbitrary_document_name>Loss Run</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document12</real_document_name>
        <arbitrary_document_name>Medical Bill</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document16</real_document_name>
        <arbitrary_document_name>Papers</arbitrary_document_name>
        <document_indices />
        <definitions />
      </document_type><document_type>
        <real_document_name>document1</real_document_name>
        <arbitrary_document_name>Photos</arbitrary_document_name>
        <document_indices />
        <definitions />
      </document_type><document_type>
        <real_document_name>document11</real_document_name>
        <arbitrary_document_name>Police Report</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document7</real_document_name>
        <arbitrary_document_name>Signed Application</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document10</real_document_name>
        <arbitrary_document_name>Signed Forms</arbitrary_document_name>
        <document_indices>
          <document_index>
            <real_index_name>f1</real_index_name>
            <arbitrary_index_name>description</arbitrary_index_name>
          </document_index>
        </document_indices>
        <definitions />
      </document_type><document_type>
        <real_document_name>document17</real_document_name>
        <arbitrary_document_name>Work Order</arbitrary_document_name>
        <document_indices />
        <definitions />
      </document_type><document_type>
        <real_document_name>tab_fileId_68</real_document_name>
        <arbitrary_document_name></arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_82</real_document_name>
        <arbitrary_document_name>LoooooooongTabName</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_77</real_document_name>
        <arbitrary_document_name>test</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_69</real_document_name>
        <arbitrary_document_name>test_AddTab</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_106</real_document_name>
        <arbitrary_document_name>test_split</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_78</real_document_name>
        <arbitrary_document_name>test3</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_79</real_document_name>
        <arbitrary_document_name>test4</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_80</real_document_name>
        <arbitrary_document_name>test5</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_81</real_document_name>
        <arbitrary_document_name>test6</arbitrary_document_name>
      </document_type><document_type>
        <real_document_name>tab_fileId_98</real_document_name>
        <arbitrary_document_name>will</arbitrary_document_name>
      </document_type>
    </cabinet>
  </DocumentTypeList>
</response>

 */
function doGenericDocumentTypeList($db, $passKey, $department, $cabinet, $docID) {
	list($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$xmlDoc =& setupReturnXML ($passKey);

			$root = $xmlDoc->documentElement;
			$docTypeList = $xmlDoc->createElement ('DocumentTypeList');
			$root->appendChild ($docTypeList);
			$el = $xmlDoc->createElement ('department');
			$el->appendChild ($xmlDoc->createTextNode ($department));
			$docTypeList->appendChild ($el);
			$el = $xmlDoc->createElement ('user');
			$el->appendChild ($xmlDoc->createTextNode ($userName));
			$docTypeList->appendChild ($el);
			$elCab = $xmlDoc->createElement ('cabinet');
			$elCab->appendChild ($xmlDoc->createTextNode ($cabinet));
			$docTypeList->appendChild ($elCab);
			$elDocID = $xmlDoc->createElement ('docID');
			$elDocID->appendChild ($xmlDoc->createTextNode ($docID));
			$elCab->appendChild ($elDocID);
			
			$resSet = getGenericDocumentTypeList($department, $cabinet, $userName, $docID);
			foreach ($resSet as $myResult) {
				$docTypeEl = $xmlDoc->createElement ('document_type');
				$elCab->appendChild($docTypeEl);
				$docNameEl = $xmlDoc->createElement ('real_document_name');
				$docNameEl->appendChild ($xmlDoc->createTextNode ($myResult['realName']));
				$docTypeEl->appendChild ($docNameEl);
				$arbNameEl = $xmlDoc->createElement ('arbitrary_document_name');
				$arbNameEl->appendChild ($xmlDoc->createTextNode ($myResult['arbName']));
				$docTypeEl->appendChild ($arbNameEl);

		    	if (array_key_exists('indices', $myResult)) {				
					$docIndices = $xmlDoc->createElement('document_indices');
					$docTypeEl->appendChild ($docIndices);
					foreach($myResult['indices'] as $real => $arb) {
						$docIndex = $xmlDoc->createElement('document_index');
						$el = $xmlDoc->createElement('real_index_name');
						$el->appendChild($xmlDoc->createTextNode($real));
						$docIndex->appendChild($el);
						$el = $xmlDoc->createElement('arbitrary_index_name');
						$el->appendChild($xmlDoc->createTextNode($arb));
						$docIndex->appendChild($el);
						$docIndices->appendChild($docIndex);
					}
		    	}
		    	if (array_key_exists('definitions', $myResult)) {		    	
					$docDefEl = $xmlDoc->createElement('definitions');
					$docTypeEl->appendChild ($docDefEl);
					foreach ($myResult['definitions'] as $realName => $listArr) {
						$defIdx = $xmlDoc->createElement ('def_index');
						$docDefEl->appendChild ($defIdx);
						$defIdx->setAttribute ('name', $realName);
						foreach ($listArr as $myDef) {
							$res = $xmlDoc->createElement ('definition');
							$res->setAttribute ('name', $myDef);
							$defIdx->appendChild($res);
						}
					}
		    	}
			}
			$retXML = $xmlDoc->saveXML();
		
		} else {
			$retXML = '<errors><error message="INVALID"/></errors>';
		}
	}
	returnXML ($retXML);
}

/*
	<element name="response" xmlns="http://relaxng.org/ns/structure/1.0"
			datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">   
		<element name="passKey">
			<data type="string" />
		</element>
		<element name="document_view">
			<element name="department">
				<data type="string">
					<param name="maxLength">100</param>
				</data>
			</element>
			<element name="user">
				<data type="string">
					<param name="maxLength">100</param>
				</data>
			</element>
			<zeroOrMore>
				<element name="document_type">
					<element name="real_document_name">
						<data type="string">
							<param name="maxLength">100</param>
						</data>
					</element>
					<element name="arbitrary_document_name">
						<data type="string">
							<param name="maxLength">100</param>
						</data>
					</element> 
					<element name="document_indices">
						<oneOrMore>
							<element name="document_index">
								<element name="real_index_name">
									<data type="string">
										<param name="maxLength">100</param>
									</data>
								</element>
								<element name="arbitrary_index_name">
									<data type="string">
										<param name="maxLength">100</param>
									</data>
								</element>
							</element>
						</oneOrMore>
					</element>
				</element>
			</zeroOrMore>
		</element>
	</element>
*/
function doGetDocumentTypeList($db, $passKey, $department, $cabinet) {
	list($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$xmlDoc =& setupReturnXML ($passKey);
			if (substr (PHP_VERSION, 0, 1) == '4') {
				$root = $xmlDoc->document_element();
				$isDoc = $xmlDoc->create_element ('document_view');
				$root->append_child ($isDoc);
				$el = $xmlDoc->create_element ('department');
				$el->append_child ($xmlDoc->create_text_node ($department));
				$isDoc->append_child ($el);
				$el = $xmlDoc->create_element ('user');
				$el->append_child ($xmlDoc->create_text_node ($userName));
				$isDoc->append_child ($el);
				$resSet = getDocumentTypeList($department, $cabinet, $userName);
				foreach ($resSet as $myResult) {
					$docTypeEl = $xmlDoc->create_element ('document_type');
					$isDoc->append_child($docTypeEl);
					$docNameEl = $xmlDoc->create_element ('real_document_name');
					$docNameEl->append_child ($xmlDoc->create_text_node ($myResult['realName']));
					$docTypeEl->append_child ($docNameEl);
					$arbNameEl = $xmlDoc->create_element ('arbitrary_document_name');
					$arbNameEl->append_child ($xmlDoc->create_text_node ($myResult['arbName']));
					$docTypeEl->append_child ($arbNameEl);
					$docIndices = $xmlDoc->create_element('document_indices');
					$docTypeEl->append_child ($docIndices);
					foreach($myResult['indices'] as $real => $arb) {
						$docIndex = $xmlDoc->create_element('document_index');
						$el = $xmlDoc->create_element('real_index_name');
						$el->append_child($xmlDoc->create_text_node($real));
						$docIndex->append_child($el);
						$el = $xmlDoc->create_element('arbitrary_index_name');
						$el->append_child($xmlDoc->create_text_node($arb));
						$docIndex->append_child($el);
						$docIndices->append_child($docIndex);
					}
					$docDefEl = $xmlDoc->create_element('definitions');
					$docTypeEl->append_child ($docDefEl);
					foreach ($myResult['definitions'] as $realName => $listArr) {
						$defIdx = $xmlDoc->create_element ('def_index');
						$defIdx->set_attribute ('name', $realName);
						$docDefEl->append_child ($defIdx);
						foreach ($listArr as $myDef) {
							$res = $xmlDoc->create_element ('definition');
							$res->set_attribute ('name', $myDef);
							$defIdx->append_child ($res);
						}
					}
				}
				$retXML = $xmlDoc->dump_mem(false);
			} else {
				$root = $xmlDoc->documentElement;
				$isDoc = $xmlDoc->createElement ('document_view');
				$root->appendChild ($isDoc);
				$el = $xmlDoc->createElement ('department');
				$el->appendChild ($xmlDoc->createTextNode ($department));
				$isDoc->appendChild ($el);
				$el = $xmlDoc->createElement ('user');
				$el->appendChild ($xmlDoc->createTextNode ($userName));
				$isDoc->appendChild ($el);
				$resSet = getDocumentTypeList($department, $cabinet, $userName);
				foreach ($resSet as $myResult) {
					$docTypeEl = $xmlDoc->createElement ('document_type');
					$isDoc->appendChild($docTypeEl);
					$docNameEl = $xmlDoc->createElement ('real_document_name');
					$docNameEl->appendChild ($xmlDoc->createTextNode ($myResult['realName']));
					$docTypeEl->appendChild ($docNameEl);
					$arbNameEl = $xmlDoc->createElement ('arbitrary_document_name');
					$arbNameEl->appendChild ($xmlDoc->createTextNode ($myResult['arbName']));
					$docTypeEl->appendChild ($arbNameEl);
					$docIndices = $xmlDoc->createElement('document_indices');
					$docTypeEl->appendChild ($docIndices);
					foreach($myResult['indices'] as $real => $arb) {
						$docIndex = $xmlDoc->createElement('document_index');
						$el = $xmlDoc->createElement('real_index_name');
						$el->appendChild($xmlDoc->createTextNode($real));
						$docIndex->appendChild($el);
						$el = $xmlDoc->createElement('arbitrary_index_name');
						$el->appendChild($xmlDoc->createTextNode($arb));
						$docIndex->appendChild($el);
						$docIndices->appendChild($docIndex);
					}
					$docDefEl = $xmlDoc->createElement('definitions');
					$docTypeEl->appendChild ($docDefEl);
					foreach ($myResult['definitions'] as $realName => $listArr) {
						$defIdx = $xmlDoc->createElement ('def_index');
						$docDefEl->appendChild ($defIdx);
						$defIdx->setAttribute ('name', $realName);
						foreach ($listArr as $myDef) {
							$res = $xmlDoc->createElement ('definition');
							$res->setAttribute ('name', $myDef);
							$defIdx->appendChild($res);
						}
					}
				}
				$retXML = $xmlDoc->saveXML();
			}
		} else {
			$retXML = '<errors><error message="INVALID"/></errors>';
		}
	}
	returnXML ($retXML);
}

/*
	<element name="response" xmlns="http://relaxng.org/ns/structure/1.0"
			datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">   
		<element name="passKey">
			<data type="string" />
		</element>
		<element name="document_view">
			<element name="department">
				<data type="string">
					<param name="maxLength">100</param>
				</data>
			</element>
			<element name="cabinet">
				<data type="string">
					<param name="maxLength">128</param>
				</data>
			</element>
			<element name="is_document_view">
				<data type="boolean" />
			</element>
		</element>
	</element>
*/
function doIsDocumentView($db, $passKey, $department, $cabinet) {
	list($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$db_dept = getDbObject($department);
			if($cabinet and checkCabinet($db_dept, $cabinet)) {
				$isDocViewRet = isDocumentView($userName, $department, 0, $cabinet);
				if($isDocViewRet) {
					$isDocView = 'true';
				} else {
					$isDocView = 'false';
				}
				$xmlDoc =& setupReturnXML ($passKey);
				if (substr (PHP_VERSION, 0, 1) == '4') {
					$root = $xmlDoc->document_element();
					$isDoc = $xmlDoc->create_element ('document_view');
					$root->append_child ($isDoc);
					$el = $xmlDoc->create_element ('department');
					$el->append_child ($xmlDoc->create_text_node ($department));
					$isDoc->append_child ($el);
					$el = $xmlDoc->create_element ('cabinet');
					$el->append_child ($xmlDoc->create_text_node ($cabinet));
					$isDoc->append_child ($el);
					$el = $xmlDoc->create_element ('is_document_view');
					$el->append_child ($xmlDoc->create_text_node ($isDocView));
					$isDoc->append_child ($el);
					$retXML = $xmlDoc->dump_mem (false);
				} else {
					$root = $xmlDoc->documentElement;
					$isDoc = $xmlDoc->createElement ('document_view');
					$root->appendChild ($isDoc);
					$el = $xmlDoc->createElement ('department');
					$el->appendChild ($xmlDoc->createTextNode ($department));
					$isDoc->appendChild ($el);
					$el = $xmlDoc->createElement ('cabinet');
					$el->appendChild ($xmlDoc->createTextNode ($cabinet));
					$isDoc->appendChild ($el);
					$el = $xmlDoc->createElement ('is_document_view');
					$el->appendChild ($xmlDoc->createTextNode ($isDocView));
					$isDoc->appendChild ($el);
					$retXML = $xmlDoc->saveXML ();
				}
			} else {
				$retXML = '<errors><error message="INVALID"/></errors>';
			}
			$db_dept->disconnect();
		} else {
			$retXML = '<errors><error message="INVALID"/></errors>';
		}
	}
	returnXML ($retXML);
}

/*
	<element name="response" xmlns="http://relaxng.org/ns/structure/1.0"
			datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">   
		<element name="passKey">
			<data type="string" />
		</element>
		<element name="indices">
			<element name="department">
				<data type="string">
					<param name="maxLength">100</param>
				</data>
			</element>
			<element name="cabinet">
				<data type="string">
					<param name="maxLength">128</param>
				</data>
			</element>
			<oneOrMore>
				<element name="index">
					<data type="string">
						<param name="maxLength">255</param>
					</data>
				</element>
			</oneOrMore>
		</element>
	</element>
*/
function doGetCabinetIndices ($db, $passKey, $department, $cabinet) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$db_dept = getDbObject ($department);
			if ($cabinet and checkCabinet ($db_dept, $cabinet)) {
				$xmlDoc =& setupReturnXML ($passKey);
				if (substr (PHP_VERSION, 0, 1) == '4') {
					$root = $xmlDoc->document_element();
					$indices = $xmlDoc->create_element ('indices');
					$root->append_child ($indices);
					$el = $xmlDoc->create_element ('department');
					$el->append_child ($xmlDoc->create_text_node ($department));
					$indices->append_child ($el);
					$el = $xmlDoc->create_element ('cabinet');
					$el->append_child ($xmlDoc->create_text_node ($cabinet));
					$indices->append_child ($el);
					$allIndices = getCabinetInfo ($db_dept, $cabinet);
					foreach ($allIndices as $index) {
						$el = $xmlDoc->create_element ('index');
						$el->append_child ($xmlDoc->create_text_node ($index));
						$indices->append_child ($el);
					}
					$retXML = $xmlDoc->dump_mem (false);
				} else {
					$root = $xmlDoc->documentElement;
					$indices = $xmlDoc->createElement ('indices');
					$root->appendChild ($indices);
					$el = $xmlDoc->createElement ('department');
					$el->appendChild ($xmlDoc->createTextNode ($department));
					$indices->appendChild ($el);
					$el = $xmlDoc->createElement ('cabinet');
					$el->appendChild ($xmlDoc->createTextNode ($cabinet));
					$indices->appendChild ($el);
					$allIndices = getCabinetInfo ($db_dept, $cabinet);
					foreach ($allIndices as $index) {
						$el = $xmlDoc->createElement ('index');
						$el->appendChild ($xmlDoc->createTextNode ($index));
						$indices->appendChild ($el);
					}
					$retXML = $xmlDoc->saveXML ();
				}
			} else {
				$retXML = '<errors><error message="INVALID"/></errors>';
			}
		} else {
			$retXML = '<errors><error message="INVALID"/></errors>';
		}
	}
	returnXML ($retXML);
}

function doGetDepartmentList ($db, $passKey) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		$xmlDoc =& setupReturnXML ($passKey);
		$deptList = getDepartmentList($db, $userName);
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$root = $xmlDoc->document_element();
			foreach ($deptList as $real => $arb) {
				$el = $xmlDoc->create_element ('department');
				$root->append_child($el);
				$realEl = $xmlDoc->create_element ('real_name');
				$el->append_child($realEl);
				$realEl->append_child ($xmlDoc->create_text_node($real));
				$arbEl = $xmlDoc->create_element ('disp_name');
				$el->append_child($arbEl);
				$arbEl->append_child ($xmlDoc->create_text_node($arb));
			}
			$retXML = $xmlDoc->dump_mem (false);
		} else {
			$root = $xmlDoc->documentElement;
			foreach ($deptList as $real => $arb) {
				$el = $xmlDoc->createElement ('department');
				$root->appendChild($el);
				$realEl = $xmlDoc->createElement ('real_name');
				$el->appendChild($realEl);
				$realEl->appendChild ($xmlDoc->createTextNode($real));
				$arbEl = $xmlDoc->createElement ('disp_name');
				$el->appendChild($arbEl);
				$arbEl->appendChild ($xmlDoc->createTextNode($arb));
			}
			$retXML = $xmlDoc->saveXML ();
		}
	}
	returnXML ($retXML);
}

/*
	<element name="response" xmlns="http://relaxng.org/ns/structure/1.0"
			datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">   
		<element name="passKey">
			<data type="string" />
		</element>
		<element name="saved_tabs">
			<element name="department">
				<data type="string">
					<param name="maxLength">100</param>
				</data>
			</element>
			<element name="cabinet">
				<data type="string">
					<param name="maxLength">128</param>
				</data>
			</element>
			<oneOrMore>
				<element name="tab">
					<data type="string">
						<param name="maxLength">255</param>
					</data>
				</element>
			</oneOrMore>
		</element>
	</element>
*/
function doGetCabinetSavedTabs ($db, $passKey, $department, $cabinet) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$db_dept = getDbObject ($department);
			$cabinetID = checkCabinet ($db_dept, $cabinet);
			if ($cabinet and $cabinetID) {
				$savedTabs = getCabSavedTabs($userName, $department, $cabinetID, $db);
 				$xmlDoc =& setupReturnXML ($passKey);
				$root = $xmlDoc->documentElement;
				$indices = $xmlDoc->createElement ('saved_tabs');
				$root->appendChild ($indices);
				$el = $xmlDoc->createElement ('department');
				$el->appendChild ($xmlDoc->createTextNode ($department));
				$indices->appendChild ($el);
				$el = $xmlDoc->createElement ('cabinet');
				$el->appendChild ($xmlDoc->createTextNode ($cabinet));
				$indices->appendChild ($el);
				$el = $xmlDoc->createElement ('tab');
				$el->appendChild ($xmlDoc->createTextNode ('Main'));
				$indices->appendChild ($el);
				usort($savedTabs, 'strnatcasecmp');
				foreach ($savedTabs as $tab) {
					$el = $xmlDoc->createElement ('tab');
					$el->appendChild ($xmlDoc->createTextNode ($tab));
					$indices->appendChild ($el);
				}
				$retXML = $xmlDoc->saveXML ();
			} else {
				$retXML = '<errors><error message="INVALID"/></errors>';
			}
		} else {
			$retXML = '<errors><error message="INVALID"/></errors>';
		}
	}
	returnXML ($retXML);
}

function returnXML ($xmlStr) {
	header ('Content-type: text/xml');
	echo $xmlStr;
}

function &setupReturnXML ($passKey) {
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('response');
		$xmlDoc->append_child ($root);
		$el = $xmlDoc->create_element ('passKey');
		$el->append_child ($xmlDoc->create_text_node ($passKey));
		$root->append_child ($el);
	} else {
		$xmlDoc = new DOMDocument (); 
		$root = $xmlDoc->createElement ('response');
		$xmlDoc->appendChild ($root);
		$el = $xmlDoc->createElement ('passKey');
		$el->appendChild ($xmlDoc->createTextNode ($passKey));
		$root->appendChild ($el);
	}
	return $xmlDoc;
}

function checkDepartment ($db, $department) {
	return getTableInfo ($db, 'licenses', array('COUNT(*)'), array
			('real_department' => $department), 'queryOne');
}

function checkCabinet ($db, $cabinet) {
	return getTableInfo ($db, 'departments', array ('departmentid'), array
			('real_name' => $cabinet), 'queryOne');
}

function doGetUploadCreds ($db, $passKey, $department) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			if (substr (PHP_VERSION, 0, 1) == '4') {
				$xmlDoc =& setupReturnXML ($passKey);
				$root = $xmlDoc->document_element();
				$el = $xmlDoc->create_element('upload_username');
				$root->append_child($el);
				$el->append_child($xmlDoc->create_text_node(getUploadUsername($userName, $department, $db)));
				$el = $xmlDoc->create_element('upload_password');
				$root->append_child($el);
				$el->append_child($xmlDoc->create_text_node(getUploadPassword($userName, $department, $db)));
				$retXML = $xmlDoc->dump_mem (false);
			} else {
				$xmlDoc =& setupReturnXML ($passKey);
				$root = $xmlDoc->documentElement;
				$el = $xmlDoc->createElement('upload_username');
				$root->appendChild($el);
				$el->appendChild($xmlDoc->createTextNode(getUploadUsername($userName, $department, $db)));
				$el = $xmlDoc->createElement('upload_password');
				$root->appendChild($el);
				$el->appendChild($xmlDoc->createTextNode(getUploadPassword($userName, $department, $db)));
				$retXML = $xmlDoc->saveXML ();
			}
		}
	}
	returnXML ($retXML);
}

function doGetPersonalInboxBarcode($db, $passKey, $department) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		if ($department and checkDepartment ($db, $department)) {
			$xmlDoc =& setupReturnXML ($passKey);
			if (substr (PHP_VERSION, 0, 1) == '4') {
				$root = $xmlDoc->document_element();
				$el = $xmlDoc->create_element('barcode');
				$root->append_child($el);
				$el->append_child($xmlDoc->create_text_node(
					getPersonalInboxBarcode($userName, $department, $db)
				));
				$retXML = $xmlDoc->dump_mem (false);
			} else {
				$root = $xmlDoc->documentElement;
				$el = $xmlDoc->createElement('barcode');
				$root->appendChild($el);
				$el->appendChild($xmlDoc->createTextNode(
					getPersonalInboxBarcode($userName, $department, $db)
				));
				$retXML = $xmlDoc->saveXML ();
			}
		}
	}
	returnXML ($retXML);
}

function doGetUniqueFolderFileCount($db, $passKey, $department,$cabinet) {
    list ($retVal, $userName) = checkKey ($passKey);
    if (!$retVal) {
        $retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
    } else {
        if ($department and checkDepartment ($db, $department)) {
            	$cabResArr = getUniqueFolderFileCount($department,$cabinet);
            	$xmlDoc =& setupReturnXML ($passKey);
				if (substr (PHP_VERSION, 0, 1) == '4') {
						$root = $xmlDoc->document_element();
						$el = $xmlDoc->create_element('CABINET');
						$root->append_child($el);
					foreach($cabResArr AS $k => $v) {
						$folder = $xmlDoc->create_element('FOLDER');
						$key = $xmlDoc->create_element('KEY');
						$key->append_child($xmlDoc->create_text_node($k));
						$folder->append_child($key);

						$val = $xmlDoc->create_element('VALUE');
						$val->append_child($xmlDoc->create_text_node($v));
						$folder->append_child($val);
						$el->append_child($folder);
						}
						$retXML = $xmlDoc->dump_mem (false);
				} else {
					$root = $xmlDoc->documentElement;
					$el = $xmlDoc->createElement('CABINET');
					$root->appendChild($el);
					foreach($cabResArr AS $k => $v) {
						$folder = $xmlDoc->createElement('FOLDER');
						$key = $xmlDoc->createElement('KEY');
						$key->appendChild($xmlDoc->createTextNode($k));
						$folder->appendChild($key);

						$val = $xmlDoc->createElement('KEY');
						$val->appendChild($xmlDoc->createTextNode($v));
						$folder->appendChild($val);
						$el->appendChild($folder);
					}
					$retXML = $xmlDoc->saveXML ();
				}
        }
    }
    returnXML ($retXML);
}

function doGetZipFile($db, $passKey, $department, $cabinet, $filename, $sleepInUSec) {
	global $DEFS;
    list ($retVal, $userName) = checkKey ($passKey);
    if (!$retVal) {
        $retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
		header ('Content-type: text/xml');
		echo $retXML;
    } else {
		//Set the php script execution to not time out for file transfer
		set_time_limit(0);

		$chunksize = 1024; // how many bytes per chunk
		$buffer = '';
		$path = $DEFS['DATA_DIR']."/$department/zipTemp/$cabinet/$filename";
//downloadFile ('', $filename, true, false);
		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return false;
		}
		header ('Content-type: application/octet-stream');
		header ('Content-Length: '.filesize($path));
		header ('Cache-Control:');
		header ('Pragma:');
		header ('Content-Disposition: attachment filename='.$filename);
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			ob_flush();
			flush();

			usleep($sleepInUSec);
		}
		$status = fclose($handle);


	}
}

function doGetAttachment($db, $passKey, $department, $cabinetID, $fileID, $sleepInUSec) {
	global $DEFS;
    list ($retVal, $userName) = checkKey ($passKey);
    if (!$retVal) {
        $retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
		header ('Content-type: text/xml');
		echo $retXML;
    } else {
		set_time_limit(0);

		$chunksize = 1024; // how many bytes per chunk
		$buffer = '';

		$db_dept = getDbObject($department);
		//$cabInfo = getCabinets($db_dept, '', $cabinetID);
		$cab = getTableInfo($db_dept, 'departments', array('real_name'), array('departmentid' => (int)$cabinetID), 'queryOne');
		$res = getFileQuery($db_dept, $cab, $fileID);
		$doc_id = $res['doc_id'];
		$fileName = $res['filename'];
		$tab = $res['subfolder'];

		$fileLocation = getFolderLocation($db_dept, $cab, $doc_id);
		$fileLocation = $DEFS['DATA_DIR'].'/'.str_replace(" ", "/", $fileLocation);
		$path = $fileLocation."/".$tab."/".$fileName;
//downloadFile ('', $filename, true, false);
		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return false;
		}
		header ('Content-type: application/octet-stream');
		header ('Content-Length: '.filesize($path));
		header ('Cache-Control:');
		header ('Pragma:');
		header ('Content-Disposition: attachment filename='.$filename);
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			ob_flush();
			flush();

			usleep($sleepInUSec);
		}
		$status = fclose($handle);
	}
}

function doGetCabinetStructure ($db, $passKey, $xmlInfo) {
	$retXML = "";
    list ($retVal, $userName) = checkKey ($passKey);
    if (!$retVal) {
        $retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
    } else {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			//Parses the incoming xml
			$domDoc = domxml_open_mem($xmlInfo);
			$depNode = $domDoc->get_elements_by_tagname("department");
			$department = $depNode[0]->get_content();
			$cabIDNode = $domDoc->get_elements_by_tagname("cabinetID");
			$cabinetID = $cabIDNode[0]->get_content();
			$numSearchIndices = $domDoc->get_elements_by_tagname("num_searchIndices");
			$numSearchIndices = $numSearchIndices[0]->get_content();
			$numOrderIndices = $domDoc->get_elements_by_tagname("num_orderIndices");
			$numOrderIndices = $numOrderIndices[0]->get_content();

			$searchArr = array();
			if($numSearchIndices > 0) {
				$searchNodes = $domDoc->get_elements_by_tagname("index");
				for($i = 0; $i < $numSearchIndices; $i++) {
					$indexNode = $searchNodes[$i];
					$indexName = $indexNode->get_elements_by_tagname("index_name");
					$indexName = $indexName[0]->get_content();
					$indexValue = $indexNode->get_elements_by_tagname("index_value");
					$indexValue = $indexValue[0]->get_content();
					$searchArr[$indexName] = $indexValue;
				}
			}

			$orderArr = array();
			if($numOrderIndices > 0) {
				$orderNodes = $domDoc->get_elements_by_tagname("order");
				for($i = 0; $i < $numOrderIndices; $i++) {
					$orderNode = $orderNodes[$i];
					$indexName = $orderNode->get_elements_by_tagname("index_name");
					$indexName = $indexName[0]->get_content();
					$orderBy = $orderNode->get_elements_by_tagname("order_by");
					$orderBy = $orderBy[0]->get_content();
					$orderArr[$indexName] = $orderBy;
				}
			}
		} else {
			$domDoc = new DOMDocument();
			if(!$domDoc->loadXML($xmlInfo)) {
        		$retXML = '<errors><error message="ERRORRECEIVINGXML"/></errors>';
				echo $retXML;
				die();
			}

			$depNode = $domDoc->getElementsByTagName("department");
			$depNode = $depNode->item(0);
			$department = $depNode->nodeValue;
			$cabIDNode = $domDoc->getElementsByTagName("cabinetID");
			$cabIDNode = $cabIDNode->item(0);
			$cabinetID = $cabIDNode->nodeValue;
			$numSearchIndices = $domDoc->getElementsByTagName("num_searchIndices");
			$numSearchIndices = $numSearchIndices->item(0);
			$numSearchIndices = $numSearchIndices->nodeValue;
			$numOrderIndices = $domDoc->getElementsByTagName("num_orderIndices");
			$numOrderIndices = $numOrderIndices->item(0);
			$numOrderIndices = $numOrderIndices->nodeValue;

			$searchArr = array();
			if($numSearchIndices > 0) {
				$searchNodes = $domDoc->getElementsByTagName("index");
				for($i = 0; $i < $numSearchIndices; $i++) {
					$indexNode = $searchNodes->item($i);
					$indexName = $indexNode->getElementsByTagName("index_name");
					$indexName = $indexName->item(0);
					$indexName = $indexName->nodeValue;
					$indexValue = $indexNode->getElementsByTagName("index_value");
					$indexValue = $indexValue->item(0);
					$indexValue = $indexValue->nodeValue;
					$searchArr[$indexName] = $indexValue;
				}
			}

			$orderArr = array();
			if($numOrderIndices > 0) {
				$orderNodes = $domDoc->getElementsByTagName("order");
				for($i = 0; $i < $numOrderIndices; $i++) {
					$orderNode = $orderNodes->item($i);
					$indexName = $orderNode->getElementsByTagName("index_name");
					$indexName = $indexName->item(0);
					$indexName = $indexName->nodeValue;
					$orderBy = $orderNode->getElementsByTagName("order_by");
					$orderBy = $orderBy->item(0);
					$orderBy = $orderBy->nodeValue;
					$orderArr[$indexName] = $orderBy;
				}
			}
		}

		$db_dept = getDbObject($department);
//		$cabinet = getTableInfo($db_dept, 'departments', array('real_name'), array('departmentid' => (int)$cabinetID), 'queryOne');
		$cabinet = hasAccess($db_dept, $userName, (int)$cabinetID, false);
		if($cabinet === false) {
        	$retXML = '<errors><error message="CABINETACCESSDENIED"/></errors>';
			echo $retXML;
			die();
		}

		$indicesArr = getCabinetInfo($db_dept, $cabinet);
		$searchArr['deleted'] = 0; //added deleted to be 0 ie whereArr
		$cabinetInfo = getTableInfo($db_dept, $cabinet, array(), $searchArr, 'getAssoc', $orderArr);
		$folderCount = sizeof($cabinetInfo);

		//Return xml
		$xmlObj = new xml('cabinet');
		$xmlObj->createKeyAndValue('folder_count', $folderCount, array(), NULL);
		foreach($cabinetInfo AS $doc_id => $folders) {
			$filesInfo = getTableInfo($db_dept, $cabinet."_files", 
				array('id', 'filename', 'file_size'), 
				array('doc_id='.$doc_id, 'filename IS NOT NULL', 'deleted=0', 'display=1'), 
				'getAssoc'
			);
			$fileCount = sizeOf($filesInfo);
			if($fileCount == NULL OR $fileCount == "") {
				$fileCount = 0;
			}

			$folderNode = $xmlObj->createKeyAndValue('folder');
			$xmlObj->createKeyAndValue('docID', $doc_id, array(), $folderNode);
			$xmlObj->createKeyAndValue('file_count', $fileCount, array(), $folderNode);
			$indicesNode = $xmlObj->createKeyAndValue('indices', NULL, array(), $folderNode);
			foreach($indicesArr AS $index) {
				$indexNode = $xmlObj->createKeyAndValue('index', NULL, array(), $indicesNode);
				$xmlObj->createKeyAndValue('index_name', $index, array(), $indexNode);
				$xmlObj->createKeyAndValue('index_value', $folders[$index], array(), $indexNode);
			}

			$filesNode = $xmlObj->createKeyAndValue('files', NULL, array(), $folderNode);
			foreach($filesInfo AS $fileID => $files) {
				$fileNode = $xmlObj->createKeyAndValue('file', NULL, array(), $filesNode);
				$xmlObj->createKeyAndValue('fileID', $fileID, array(), $fileNode);
				$xmlObj->createKeyAndValue('filename', $files['filename'], array(), $fileNode);
				$xmlObj->createKeyAndValue('file_size', $files['file_size'], array(), $fileNode);
			}
		}

		$retXML = $xmlObj->createDOMString();
	}
	returnXML($retXML);
}

function doGetMapping ($passKey, $department) {
	global $DEFS;
	$mappingFile = $DEFS['MAPPING_DIR'] . '/' . $department . '/mapping.xml';
	if (file_exists ($mappingFile)) {
		returnXML (file_get_contents ($mappingFile));
	} else {
		returnXML ('<legacy />');
	}
}

function doTripleDesEncrypt ($str) {
	$encData = tdEncrypt ($str);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('encdata');
		$root->set_attribute ('value', $encData);
		$xmlDoc->append_child ($root);
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement ('encdata');
		$root->setAttribute ('value', $encData);
		$xmlDoc->appendChild ($root);
		$xmlStr = $xmlDoc->saveXML ();
	}
	returnXML ($xmlStr);
}

function doTripleDesDecrypt ($str) {
	$str = str_replace (' ', '+', $str);
	$encData = tdDecrypt ($str);
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc ('1.0');
		$root = $xmlDoc->create_element ('decdata');
		$root->set_attribute ('value', $encData);
		$xmlDoc->append_child ($root);
		$xmlStr = $xmlDoc->dump_mem (false);
	} else {
		$xmlDoc = new DOMDocument ();
		$xmlDoc->encoding = 'UTF-8';
		$root = $xmlDoc->createElement ('decdata');
		$root->setAttribute ('value', $encData);
		$xmlDoc->appendChild ($root);
		$xmlStr = $xmlDoc->saveXML ();
	}
	returnXML ($xmlStr);
}
function doGetBarcodeFolder ($db, $passKey, $barcode) {
	list ($retVal, $userName) = checkKey ($passKey);
	if (!$retVal) {
		$retXML = '<errors><error message="LOGINREQUIRED"/></errors>';
	} else {
		$whereArr = array('barcode_rec_id' => $barcode);
    $barcodeRec = getTableInfo($db,'barcode_history',array('barcode_rec_id','cab','username','barcode_info','barcode_field'),$whereArr, 'queryAll'); 
		$message="Barcode ID:".$barcodeRec[0]['barcode_rec_id']."~* Cab:".$barcodeRec[0]['cab']."~* User:".$barcodeRec[0]['username']."~* BarcodeInfo:".$barcodeRec[0]['barcode_info']."~* Details:".$barcodeRec[0]['barcode_field'];
		$retXML = '<barcodeInfo value="'.htmlspecialchars($message).'"/>';
	}    
		header ('Content-type: text/xml');
		echo $retXML;
//	returnXML ($retXML);
}

?>
