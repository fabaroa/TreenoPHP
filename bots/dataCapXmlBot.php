<?php 
	
/******************************
 * This File Is Designed to Scan a
 * Direcotry for an XML file.  This file should match
 * a pdf or tif file with the same name.
 * The XML contains the meta information for
 * the cabinet, department, and folder values.
 * Classical View Only At Time Of Creation.
 * Brian Charles 03-03-10
 */
/**
 * Stolen From RouteDocuments3:
 * *****************************************************
 */


/**
 * Report all errors
 */
error_reporting(E_ALL);

chdir(dirname(__FILE__));
/**
 * PEAR::DB Connection
 */
require_once '../classuser.inc';
require_once '../db/db_common.php';
require_once '../lib/synchronizeBots.php';

/**
 * DataObject_user
 */
require_once '../DataObjects/DataObject.inc.php';

/**
 * $DEFS
 */
require_once '../lib/settings.php';

/**
 * getTableInfo(), deleteTableInfo(), addToWorkflow(), and getCabinetInfo()
 */
require_once '../lib/utility.php';

/**
 * copyFiles()
 */
require_once '../lib/indexing2.php';

/**
 * createFolderInCabinet()
 */
require_once '../lib/cabinets.php';

/**
 * class stateNode
 */
require_once '../workflow/node.inc.php';

/**
 * delDir()
 */
require_once '../lib/fileFuncs.php';

/**
 * Barcode::getRealCabinetName(), Barcode::getRealDepartmentName()
 */
require_once '../lib/barcode.inc.php';

/**
 * Indexing::makeUnique()
 */
require_once '../lib/indexing.inc.php';

/**
 * GLBSettings
 */
require_once '../settings/settings.php';

/**
 * Documents
 */
require_once '../documents/documents.php';

//parse text file for name and dept.
$xml_monitor = parse_ini_file("xml_monitor.ini", true);

$db_doc = getDbObject('docutron');
$userName = 'admin';
$user = new user();
$user->username = "$userName";

foreach($xml_monitor as $dept=>$ini)
{	
	//db stuff
	$db_dept = getDbObject($dept);

	foreach($ini as $users)
	{
		foreach($users as $inboxname)
		{
			//where to listen
			$dirListen = $DEFS['DATA_DIR'] . "/$dept/personalInbox/$inboxname";
			//die($dirListen);
			//check for validity
			safeCheckDir ($dirListen);
			
			$myDirs = array();
			$xmlFiles = array();
			$allfiles = array();
			
			$dh = safeOpenDir($dirListen);
			//$deptID = getTableInfo($db_doc, 'licenses', array('id'), array("real_department='$dept'"), 'queryOne');
			$deptID = str_replace('client_files', '', $dept);
			//loop through the listen dir and catch all subdirectories and files
			while(($entry = readdir($dh)) !== false)
			{
				//add the the array
				$newFile = null;
				if ($entry != '.' and $entry != '..') 
				{
					$newFile = $dirListen.'/'.$entry;
					//if child is directory, add to array. 
					if(is_dir($newFile))
					{
						$myDirs[] = $dirListen.'/'.$entry;
					}
					else //is a file
					{
						//check for xml
						if(stristr(strtolower($entry), "xml"))
						{
							//add file to array.  nooch
							$xmlFiles[] = $newFile;	
						}
						else //otherwise
						{
							//otherfiles go in this array
							$allfiles[] = $newFile;
						}	
					}
				}		
			}
			closedir($dh);
			//sort the arrays
			$myDirs =& Indexing::orderByTime($myDirs);
			$xmlFiles =& Indexing::orderByTime($xmlFiles);
			
			$documenttypename = $cab = null;
			//loop through each xml file and get the indicies and image name
			foreach($xmlFiles as $xml)
			{
				$xmlArr = array();
				@chmod("$xml", 0777);
				$gblStt = new GblStt($dept, $db_doc);
				//secure the indice array
				$xmlArr = parseXMLIndices($xml);
				
				$indiceArr = array();
				//should be only one row.  more than that will break and not get counted.
				foreach($xmlArr as $key=>$document)
				{
					//cab display is key
					$cab = $key;
					//value should be an array of documenttype=>(index=>value) pairs
					foreach($document as $documentname=>$docfields)
					{
						$documenttypename = $documentname;
						$indiceArr = $docfields;
						break;
					}
					break;
				}
				//get the real cabinet name
				$cabinetInfo = getTableInfo($db_dept, 'departments', array('real_name', 'departmentid'), array('departmentname'=>$cab), 'queryRow');
				$cabinetName = $cabinetInfo['real_name'];
				$cabinetID = $cabinetInfo['departmentid'];
				
				$document_table_name = getTableInfo($db_dept, 'document_type_defs', array('document_table_name'),
					array("document_type_name like '%$documenttypename%'"), 'queryOne');

				if(!strlen($document_table_name))
				{
	                                $document_table_name = getTableInfo($db_dept, 'document_type_defs', array('document_table_name'),
        	                                array("document_type_name like '%Other%'"), 'queryOne');
				}
				
				$temptable = ''; //cause we gotta have something for this?
				//now we create folder, receive doc_id.
				$doc_id = (int) createFolderInCabinet($db_dept, $gblStt, $db_doc, $userName, $dept, $cabinetName, array_values($indiceArr), array_keys($indiceArr), $temp_table);
			
				$enArr = array(
					'cabinet'=>$cabinetName,
					'doc_id'=>$doc_id,
					'document_table_name'=>$document_table_name,
					'key0' => 'f1',
					'field0' => NULL,
					'field_count'=>1
				);
				
				
				
				$tabName = addDocumentToCabinet($enArr, $user, $db_doc, $db_dept);
				$tabID = $enArr['subfolderID'];
				
				//create temp dir here and a index.dat barcode string
				$tmpDir = getUniqueDirectory($dirListen);
			        chmod($tmpDir, 777);	
				//get the unique string
				$dirs = explode('/', $tmpDir);
				$unique = $dirs[count($dirs) - 2];
			
				$barcodeString = "$deptID $cabinetID $doc_id $tabID";
				//error_log("barcode: $barcodeString");
			  	//die();	
				//create dat file
				$index = fopen($tmpDir.'/INDEX.DAT', 'w');
				fwrite($index, $barcodeString);
				fclose($index);
				
				//find sister file.
				//get the filename base
				$filename = rtrim(basename($xml), '.xml');
			
				//loop through the remaining files for a matching string
				foreach($allfiles as $image)
				{
					if(strpos(strtolower($image), strtolower($filename)) )
					{
						$f = basename($image);
						//echo "found $image\n";
						@rename(addslashes("$image"), addslashes("$tmpDir/$f"));
						
						//if the new file exists, remove the xml file
						//echo $DEFS['DATA_DIR'].'/Scan/'.$unique;
						unlink($xml);
						$scan = "{$DEFS['DATA_DIR']}/Scan/$unique";
						@rename("$tmpDir", "$scan");
					}
				}
			}	
		}
	}
}

/**
 * 
 * @param $xml
 * @return array (filename=>indexvalues)
 */
function parseXMLIndices($xml)
{
	//load the xml file
	$xmlfile = array();
	$doc = new DOMDocument();
	$doc->load( "$xml" );
	
	$documenttype = null;

	$document = $doc->getElementsByTagName( "document" );

	foreach($document as $file)
	{
		$documentdetails = $file->getElementsByTagName( "documenttype" );
		$document = explode(' - ', $documentdetails->item(0)->nodeValue);
		$cabinet = $document[0];
		$documenttype = $document[1];

		//get the fields
		$fields = array();
		$indices = array();
		$fields = $file->getElementsByTagName( "field" );
		//loopage for fieldtype(index) and value(umm.. value)
		foreach($fields as $field)
		{
			$fieldtype = $field->getElementsByTagName("fieldtype");
			$indice = $fieldtype->item(0)->nodeValue;
			
			$fieldvalue = $field->getElementsByTagName("value");
			$value = $fieldvalue->item(0)->nodeValue;
			//add to indice array
			$indices[strtolower($indice)] = trim($value);
		}
	}

	$xmlfile[$cabinet] = array($documenttype=>$indices);
	return $xmlfile;
}

?>

