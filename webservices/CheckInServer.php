<?php
/**
 * This file contains all that is needed for the CheckInServer.
 * It contains the class itself, as well as all the code needed
 * to drive the server 
 * @package SOAPServer
 */
	require_once 'SOAP/Server.php' ;
	include '../lib/versioning.php' ;
	include_once '../lib/SOAPfuncs.php';
	include_once '../lib/utility.php' ;
	include_once '../lib/quota.php' ;
	include_once '../lib/settings.php' ;
	include_once '../db/db_common.php';
	include_once '../lib/tabFuncs.php' ;
	include_once '../secure/tabChecks.php';
	include_once '../lib/webServices.php';

// CheckInServer class
/**
 * Web service to check in files and validate that they can be checked in
 * or that they have been checked in properly.  
 *
 * CheckInServer is a soap service that allows an outside application
 * to check in documents to the repository.
 * 
 * Some of these functions utilize a common response XML structure that is represented as the following:
 * <pre>
 *  &lt;ret&gt;
 *   &lt;pass&gt;<i>true/false</i>&lt;/pass&gt;
 *   &lt;value&gt;<i>returned value</i>&lt;/value&gt;
 *  &lt;/ret&gt;
 * </pre>
 *
 * @package SOAPServer
 * @author David Dillon <ddillon@docutronsystems.com>
 * @author Brad Tetu <btetu@docutronsystems.com>
 * @version 1.0
 */
class CheckInServer{
	var $__dispatch_map = array() ;
	
	function CheckInServer(){
		// Define the signature of the dispatch map
		$this->__dispatch_map['requestCheckIn'] =
			array('in' => array('filename' => 'string',
								'username' => 'string'),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['createFolder'] =
			array('in' => array('username' => 'string',
								'department' => 'string',
								'cabinet' => 'string',
								'indices' => 'string',),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['createSubFolder'] =
			array('in' => array('username' => 'string',
								'department' => 'string',
								'cabinet' => 'string',
								'docid' => 'string',
								'subfolder' => 'string',),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['requestNewCheckIn'] =
			array('in' => array('department' => 'string',
								'cabinet' => 'string',
								'folderid' => 'string',
								'subfolder' => 'string',
								'filename' => 'string',),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['validateNewCheckIn'] =
			array('in' => array('department' => 'string',
								'cabinet' => 'string',
								'folderid' => 'string',
								'subfolder' => 'string',
								'filename' => 'string',),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['validateCheckIn'] =
			array('in' => array('filename' => 'string'),
				  'out' => array('outputString'=>'xml')
				);
	}

	function __dispatch($methodname){
		if(isset($this->__dispatch_map[$methodname]))
			return $this->__dispatch_map[$methodname] ;
		return NULL ;
	}

	/** 
	 * Retruns true or false based on weather a given document, as specified
     * by the filename, is checked out by a given user and can be checked in.
	 * @param string $filename Desired file to check in
	 * @param string $username User wishing to check in the file
	 * @return string Returns the common response XML structure
	 */
	function requestCheckIn($filename, $username)
	{
		$filename = stripInvalidChars($filename);
		// Grab all file info
		$department = $cabinet = $fileid = $file = '';
		getfilenameinfo($filename, $department, $cabinet, $fileid, $file) ;
		$db_object = getDbObject($department) ;
		$cabinet = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinet), 'queryOne');
		$parentid = getParentID($cabinet, $fileid, $db_object) ;

		// Open up to department and check whoLocked file
		if(($who = whoLocked($cabinet, $parentid, $db_object)) == $username)
			return XMLPass("true") ;
        else if($who == "")
            return XMLError("File must be checked out before you can check in new changes");
		else
			return XMLError("File is already checked out by {$who}") ;
	}

	/** 
	 * Retruns the common response based on weather a new document, as specified
     * by the given information, already exists in the repository and can 
	 * be added as a new document.
	 * @param string $department Department file was checked into
	 * @param string $cabinet Cabinet to which the file was checked into
	 * @param string $folderid ID of the folder the file was checked into
	 * @param string $filename File that was checked in
	 * @return string Returns the common response XML structure
	 */
	function requestNewCheckIn($department, $cabinet, $folderid, $subfolder, $filename){
		// Grab all file info
		$db_object = getDbObject($department) ;
		$filename = stripInvalidChars($filename);

		// Open up to department and check if they can find a file that matches
		// this info, if so, it already exists and cant make it
		$whereArr = array(
			'parent_filename'	=> $filename,
			'doc_id'		=> (int) $folderid,
			'deleted'		=> 0
		);
		if(!empty($subfolder) and $subfolder != 'Main') {
			$whereArr['subfolder'] = $subfolder;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$fileid = getTableInfo($db_object, $cabinet.'_files',
			array('id'), $whereArr, 'queryOne',
			array('v_major' => 'ASC', 'v_minor' => 'ASC'), 1);
		if($fileid)
			return XMLError("File already exists in the repository") ;
		else
			return XMLPass("true") ;
	}

	/** 
	 * Retruns true or false based on weather a new document, as specified
     * by the given information, was checked in successfully.
	 * @param string $department Department file was checked into
	 * @param string $cabinet Cabinet to which the file was checked into
	 * @param string $folderid ID of the folder the file was checked into
	 * @param string $filename File that was checked in
	 * @return string Returns the common response XML structure
	 */
	function validateNewCheckIn($department, $cabinet, $folderid, $subfolder, $filename)
	{
		// Grab all file info
		$db_object = getDbObject($department) ;
		$filename = stripInvalidChars($filename);

		// Open up to department and check whoLocked file
		$whereArr = array(
			'parent_filename'	=> $filename,
			'doc_id'		=> (int) $folderid,
			'deleted'		=> 0
		);
		if(!empty($subfolder) and $subfolder != 'Main') {
			$whereArr['subfolder'] = $subfolder;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$fileid = getTableInfo($db_object, $cabinet.'_files',
			array('id'), $whereArr, 'queryOne',
			array('v_major' => 'ASC', 'v_minor' => 'ASC'), 1);
		if(whoLocked($cabinet, $fileid, $db_object) == "")
			return XMLPass("true") ;
		else
			return XMLError("File does not appear to have been added to the repository") ;
	}

	/** 
	 * Retruns true or false based on weather a given document, as specified
     * by the filename, was checked in properly.
	 * @param string $filename File that was checked in
	 * @return string Returns the common response XML structure
	 */
	function validateCheckIn($filename)
	{
		// Grab all file info
		$cabinet = $department = $fileid = $file = '';
		$filename = stripInvalidChars($filename);
		getfilenameinfo($filename, $department, $cabinet, $fileid, $file) ;
		$db_object = getDbObject($department) ;
		$cabinet = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinet), 'queryOne');

		// Open up to department and check whoLocked file
		if(whoLocked($cabinet, $fileid, $db_object) == "")
			return XMLPass("true") ;
		else
			return XMLError("File may not have checked in properly") ;
	}

	/**
	 * Creates a folder in the given department and cabinet with the given
     * indices. 
	 * @param string $username User creating the folder
	 * @param string $department Department to create the folder in
	 * @param string $cabinet Cabinet to create the folder in
	 * @param string $indices Triple comma delimited list of new indices
	 * @return string On success, the new folderid, or an error with message
	 * @todo Get rid of triple comma for XML!
	 */
    function createFolder($username, $department, $cabinet, $indices)
    {

//$fd = fopen("/tmp/wtf", "w+");
//fwrite($fd, "In createFolder\n");
		//include '../lib/settings.php' ;
		global $DEFS ;
		$db_doc = getDbObject('docutron');
		$db_object = getDbObject($department) ;
		// get the new directory on the system set up and such
		$user = makeTempUser($username, $department) ;

		// see if there is room to make the folder
		//LOCK PROBABLY NEEDED HERE
		if(checkQuota($db_doc, 4096, $user->db_name)){
			// make the new folder and get the location
			$newlocation = makeFolderInCabinet($department, $DEFS['DATA_DIR'], $cabinet);
			// make comma separated list of new indicies and query
			// if we upgrade to PHP5 you can sent count to str_replace
			// $indices = str_replace(",,,", "\", \"", $indices, $countSent) ;
			$indices = explode(",,,", $indices) ;
			$fieldnames = getCabinetInfo($db_object, $cabinet) ;
			if(sizeof($fieldnames) != sizeof($indices)) // check for bad data
				return "false" ;
			$fieldnames = implode(",", $fieldnames) ;
			$indices = implode(",", $indices);
			$newvalues = "$newlocation,$indices" ;
			$newfields = "location,$fieldnames" ;

			$newvalues = explode(",", $newvalues);
			$newfields = explode(",", $newfields);
/*fwrite($fd, "Indices: \n");
fwrite($fd, print_r($indices,true));
fwrite($fd, "\nfieldnames: \n");
fwrite($fd, print_r($fieldnames,true));
fwrite($fd, "\nnewvalues:\n");
fwrite($fd, print_r($newvalues,true));
fwrite($fd, "\nnewfields:\n");
fwrite($fd, print_r($newfields,true));
*/			$insertArr = array();
			for($i=0;$i<sizeof($newfields);$i++) {
				$insertArr[$newfields[$i]] = $newvalues[$i];
			}
//fwrite($fd, "\nsizeof(newfields): ".sizeof($newfields)."\n");
//fwrite($fd, print_r($insertArr, true));
			$res = $db_object->extended->autoExecute($cabinet,$insertArr);
			dbErr($res);
	
			// setup the audit info
			$info = "( ".addslashes(str_replace("\"","",$newlocation))." ) in Cabinet: $cabinet" ;
			$user->audit("folder added through webservice", $info) ;
			$docid = getTableInfo($db_object,$cabinet,array('MAX(doc_id)'),array(),'queryOne');
			$gblStt = new GblStt ($department, $db_doc);
			addTabsToFolder($cabinet, $gblStt, $db_doc, $docid, $db_object, $department) ;
			
			// now update all the database information
			lockTables($db_doc, array('licenses')); // need to lock tables
			$updateArr = array('quota_used'=>'quota_used+4096');
			$whereArr = array('real_department'=> $department);
			updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
			unlockTables($db_doc); // all done, unlock tables 
			return $docid ;
		}
//fclose($fd);
		return "false" ;
    }

	/**
	 * **This function is not implimented**
	 * Creates a subfolder in the given department, cabinet, folder 
	 * @param string $username User creating the subfolder
	 * @param string $department Department to create the subfolder in
	 * @param string $cabinet Cabinet to create the subfolder in
	 * @param string $docid ID of the folder to create the subfolder in
	 * @param string $subfolder The name of the subfolder to add
	 * @return string On success
	 * @todo Impliment this function
	 */
    function createSubFolder($username, $department, $cabinet, $docid, $subfolder)
    {
		global $DEFS ;
		$db_doc = getDbObject('docutron');
		$db_object = getDbObject($department) ;
		// get the new directory on the system set up and such
		$user = makeTempUser($username, $department) ;
		if( tabCheck($subfolder, $user) === false )
		{
			$whereArr = array('doc_id'=>(int)$docid);
			$folders = getTableInfo($db_object,$cabinet,array(),$whereArr);
            $row = $folders->fetchRow();
            $docLocation = $row['location'];
            $docLocation = str_replace(" ","/",$docLocation);
            $tabLoc = $DEFS['DATA_DIR']."/".$docLocation."/".$subfolder."/";
            if(file_exists($tabLoc))
            {
                return "False";
			}
			//BAD LOCK HERE
            elseif( checkQuota($db_doc, 4096, $department) )
            {
            	lockTables($db_doc, array('licenses'));
                mkdir($tabLoc, 0777);
				$updateArr = array('quota_used'=>'quota_used+4096');
				$whereArr = array('real_department'=> $department);
				updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
				$insertArr = array(
					"doc_id"	=> (int)$docid,
					"subfolder"	=> $subfolder
				);
				$res = $db_object->extended->autoExecute($cabinet."_files",$insertArr);
				$fileid = getTableInfo($db_object,$cabinet."_files",array('MAX(id)'),array(),'queryOne');
                $info = "Cabinet: ".$cabinet.", "."Tab Name: ".$subfolder;
                $user->audit("tab created", "$info");
                unlockTables($db_doc);
                return $fileid;
			}
            else
            {
                return "False";
            }
        }
        else
        {
            return "False";
        } 
    }
}

// Fire up PEAR::SOAP_Server
$server = new SOAP_Server();
                                                                                
// Fire up your class
$webService = new CheckInServer();
                                                                                
// Add your object to SOAP server (note namespace)
$server->addObjectMap($webService,'urn:CheckInServer');
                                                                                
// Handle SOAP requests coming is as POST data
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service($HTTP_RAW_POST_DATA);
} else {
    // Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';
                                                                                
    // Create the Disco server
    $disco = new SOAP_DISCO_Server($server,'CheckInServer');
    header("Content-type: text/xml");
    if (isset($_SERVER['QUERY_STRING']) &&
        strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
        echo $disco->getWSDL(); // if we're talking http://www.example.com/index.php?wsdl
    } else {
        echo $disco->getDISCO();
    }
    exit;
}
