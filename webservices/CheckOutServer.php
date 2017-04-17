<?php
/**
 * This file contains all that is needed for the CheckOutServer.
 * It contains the class itself, as well as all the helper functions
 * needed and the code needed to drive the server.
 * @package SOAPServer
 */

require_once 'SOAP/Server.php';
require_once 'SOAP/Value.php';
require_once '../db/db_common.php';
include_once '../lib/versioning.php';
include_once '../lib/settings.php';
include_once '../lib/utility.php';
include_once '../lib/filter.php';
include_once '../lib/quota.php' ;
include_once '../lib/SOAPfuncs.php' ;
include_once '../settings/settings.php' ;
include_once '../DataObjects/DataObject.inc.php';
include_once '../lib/webServices.php';

// CheckOutServer class
/**
* Web service to check out files and retreive information from repository.
*
* CheckOutServer is a soap service that allows integrating
* application to check out documents from the repository.
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
class CheckOutServer {
    var $__dispatch_map = array();

    function CheckOutServer() {
        // Define the signature of the dispatch map
        $this->__dispatch_map['getDefaultDep'] =
            array('in' => array('username' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getEmailCab'] =
            array('in' => array('department' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['login'] =
            array('in' => array('username' => 'string',
                                'password' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getRWCabinets'] =
            array('in' => array('username' => 'string',
                                'department' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getCabinetHeaders'] =
            array('in' => array('department' => 'string',
                                'cabinet' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getFilesSearch'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'doc_id' => 'string',
                                'subfolder' => 'string', 
                                'search' => 'string', 
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getFiles'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'doc_id' => 'string', 
                                'subfolder' => 'string', 
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getFoldersSearchIndices'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'search' => 'string',
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getFoldersSearch'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'search' => 'string',
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getFolders'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getSubfolders'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'doc_id' => 'string', 
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getSubfoldersSearch'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'doc_id' => 'string', 
                                'search' => 'string', 
                                'start' => 'string',
                                'amount' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getDocument'] = 
            array('in' => array('department' => 'string',
                                'cabinet' => 'string',
                                'file_id' => 'string',
                                'username' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
    }

    // Required function by SOAP_Server
    function __dispatch($methodname) {
        if (isset($this->__dispatch_map[$methodname]))
            return $this->__dispatch_map[$methodname];
        return NULL;
    }

	/**
	 * This function compares the username and password with those in the database. 
	 * If they do not match login fails, else login is successfull.
	 * @param string $username Username of the user trying to log in
	 * @param string $password Password of the given user
	 * @param string $department Department the user is a part of
     * @return string Returns the common response XML structure
	 */
    function login($username, $password)
    {
        $db_object = getDbObject( 'docutron' );
        $DO_user = DataObject::factory('users', $db_object);
        $DO_user->get('username', $username);
        if(strtolower($DO_user->password) == strtolower($password)) {
                //send OK to login
                return XMLPass("ok") ;
        }
        else
        {
                //send error, password incorrect
                $mess = "invalid login";
                return XMLError($mess) ;
        }
    }


	/**
     * This function returns the default department for the user.
	 * @param string $username Username of the user
	 * @return string Returns the common response XML structure with the value being the department
	 */
    function getDefaultDep($username)
    {
        $db_object = getDbObject( 'docutron');
        $DO_user = DataObject::factory('users', $db_object);
        $DO_user->get('username', $username);
        return XMLPass($DO_user->defaultDept);
    } 

    function getEmailCab($department)
    {
	    $db_doc = getDbObject ('docutron');
        $gblstt = new GblStt( $department, $db_doc );
        return XMLPass( $gblstt->get('email_cab'));
    }

	/**
	 * This function returns a triple comma delimited list of
	 * all the cabinets in the $department that the user has
	 * read and write access to.
	 * <pre>
	 *  &lt;f&gt;
	 *   &lt;id&gt;<f id="cabinetRealName">cabinetArbname</f>&lt;/id&gt;
	 *  &lt;/f&gt;
	 *  ...
	 * </pre>
	 * @param string $username Username of user looking for cabinets
	 * @param string $deparment Department in which user is looking
	 * @return string A list of the XML structures above
	 */
    function getRWCabinets($username, $department)
    {
        $db_object = getDbObject( $department );
        if( PEAR::isError($db_object))
        {
            $mess = $db_object->getMessage(). " " .$department;
			return XMLError($mess) ;
        }
        $query = "select real_name, DepartmentName,"; 
        $query .= " deleted from departments";
        $results = $db_object->query($query);
		$deleted = array();
        $arbList = array();
        while($del = $results->fetchrow())
        {
            $dep = $del['real_name'];
			$deleted[$dep] = $del['deleted'];
            $arbList[$dep] = $del['departmentname'];
        }
		$results = getTableInfo($db_object,'access',array(),array('username'=>$username));
	    $access = $results->fetchRow();
	    $accessRights = unserialize(base64_decode($access['access']));
		$accessRights = maskWebPermissions($db_object, $username, $accessRights);
	    foreach($accessRights as $cabinet => $rights) 
		{
			if(strcmp($rights, "rw") == 0 and $deleted[$cabinet] != 1)
			{
				$cabinetRealList[] = $cabinet;
			}
		}
		usort( $cabinetRealList, "strnatcasecmp" );
		$xmlString = "";
		foreach($cabinetRealList as $cabinet)
		{
			$xmlString .= "<f id=\"" . h($cabinet) . "\">" . h($arbList[$cabinet]) . "</f>"; 
		}
		$db_object->disconnect();
		return XMLPass($xmlString);
   }

	/**
	 * Supplies a list of files in a folder specified by cabinet and doc_id.
	 * <pre>
	 *  &lt;f&gt;
	 *   &lt;id&gt;<i>file id</i>&lt;/id&gt;
	 *   &lt;i&gt;<i>index</i>&lt;/i&gt;
	 *   ...
	 *  &lt;/f&gt;
	 *  ...
	 * </pre>
	 * @param string $department Department in which to look
	 * @param string $cabinet Cabinet in which to look
	 * @param string $doc_id The ID of the folder to list contents of
	 * @param string $start Number result to start at
	 * @param string $amount Number of results to return starting at $start
	 * @uses Paging $start and $amount define the point at which to start and $amount limits the number of results. These two paramaters are useful for paging.
	 * @return string A list of file structures like above
	 */
    function getFiles($department, $cabinet, $doc_id, $subfolder, $start, $amount)
    {
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess =  "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;	
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;


		// get all the files that arent deleted and order by doc id
		// limit and start are for paging on the client side
        $select = "select id, filename, parent_filename".
					" from {$cabinet}_files where";
        if( $subfolder != "" )
            $select .=" subfolder = '$subfolder' and";
        else
            $select .=" subfolder is NULL and";
		$select .=" deleted=0 and display=1 and doc_id=$doc_id order by ordering limit $start, $amount";

		return XMLPass(getFilesHelp($department, $select)) ;
    }

	/**
	 * Supplies a list of files in a folder specified by cabinet and doc_id
	 * that have a filename matching $search. 
	 * This function works just like {@link getFiles()} but with a search.
	 * @param string $department Department in which to look
	 * @param string $cabinet Cabinet in which to look
	 * @param string $doc_id The ID of the folder to list contents of
	 * @param string $search Value to have the results match
	 * @param string $start Number result to start at
	 * @param string $amount Number of results to return starting at $start
	 * @return string A list of file structures like {@link getFiles()}
	 */
    function getFilesSearch($department, $cabinet, $doc_id, $subfolder, $search, $start, $amount)
    {
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess = "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;

		// get all the folders that arent deleted and order by doc id
		// limit and start are for paging on the client side
        $select = "select id, filename, parent_filename from {$cabinet}_files".
					" where deleted=0 and display=1 and doc_id=$doc_id ";

		if ($subfolder == "")
            $select .= "and subfolder is NULL";
        else
            $select .= "and subfolder = '$subfolder'";

        $select .=" and filename " . LIKE . " '%$search%'".
				    " order by ordering limit $start, $amount";

		return XMLPass(getFilesHelp($department, $select)) ;
    }

	/**
	 * Supplies a list of all accessable folders in a given cabinet.
	 * <pre>
	 *  &lt;f&gt;
	 *   &lt;id&gt;<i>file id</i>&lt;/id&gt;
	 *   &lt;i&gt;<i>index</i>&lt;/i&gt;
	 *   ...
	 *  &lt;/f&gt;
	 *  ...
	 * </pre>
	 * @param string $department Department in which to look
	 * @param string $cabinet Cabinet to list contents of
	 * @param string $start Number result to start at
	 * @param string $amount Number of results to return starting at $start
	 * @uses Paging $start and $amount define the point at which to start and $amount limits the number of results. These two paramaters are useful for paging.
	 * @return string A list of file structures like above
	 */
    function getFolders($department, $cabinet, $start, $amount)
    {
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess = "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;

		// get all the folders that arent deleted and order by doc id
		// limit and start are for paging on the client side
	$db_object = getDbObject( $department );
	$result = getCabinetInfo($db_object, $cabinet);
	$db_object->disconnect() ;
        $select = "select * from $cabinet where deleted=0 order ";
        $select .= "by ". $result[0] .  " limit $start, $amount";

		return XMLPass(getFoldersHelp($department, $select,$cabinet)) ;
}

	/**
	 * Supplies a list of all accessable folders in a given cabinet that
	 * match the search list that is given.
	 * @param string $department Department in which to look
	 * @param string $cabinet Cabinet to list contents of
	 * @param string $search Triple comma delimited list of search paramaters for each indice (the results ANDed together)
	 * @param string $start Number result to start at
	 * @param string $amount Number of results to return starting at $start
	 * @return string A list of folder structures like {@link getFolders()}
	 */
    function getFoldersSearchIndices($department, $cabinet, $search, $start, $amount)
    {
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess = "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;

		// Break up all the search info
		$searchArr = explode(",,,", $search) ;

		// Build additional search statement
		$db_object = getDbObject( $department );
		
		$searchStr = "" ;
		//$result = $db_object->reverse->tableInfo($cabinet) ;
		$result = getCabinetInfo($db_object, $cabinet);
		for($i=0; $i < sizeof($result) ; $i++){
			if($searchArr[$i] != "")
				$searchStr .= "and ".$result[$i]." " . LIKE . " '%$searchArr[$i]%' ";
		}
		// If it got filled in, add on syntax stuff
		$db_object->disconnect() ;

		// get all the folders that arent deleted and order by doc id
		// limit and start are for paging on the client side
        $select = "select * from $cabinet where deleted=0 $searchStr order by ".$result[0]." limit $start, $amount";
		return XMLPass(getFoldersHelp($department, $select,$cabinet)) ;
	}

	/**
	 * Supplies a list of all accessable folders in a given cabinet that
	 * match the search that is given.
	 * @param string $department Department in which to look
	 * @param string $cabinet Cabinet to list contents of
	 * @param string $search Value of which one indice must match
	 * @param string $start Number result to start at
	 * @param string $amount Number of results to return starting at $start
	 * @return string A list of folder structures like {@link getFolders()}
	 */
    function getFoldersSearch($department, $cabinet, $search, $start, $amount)
    {
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
			$mess = "start not set correctly or not numeric start=$start";
	        return XMLError($mess) ;
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;

		// Build additional search statement
		$db_object = getDbObject( $department );
		
		$searchStr = "" ;
		$or = "" ; // only fill this in after the first
		//$result = $db_object->reverse->tableInfo($cabinet) ;
		$result = getCabinetInfo($db_object,$cabinet);
		for($i=0 ; $i < sizeof($result) ; $i++){
			$searchStr .= "$or ".$result[$i]." " . LIKE . " '%$search%' ";
			$or = "or" ;
		}
		// If it got filled in, add on syntax stuff
		if($searchStr != ""){ $searchStr = " and ($searchStr)" ;} 
		$db_object->disconnect() ;

		// get all the folders that arent deleted and order by doc id
		// limit and start are for paging on the client side
        $select = "select * from $cabinet where deleted=0 $searchStr order by " . $result[3] . " limit $start, $amount";

		return XMLPass(getFoldersHelp($department, $select,$cabinet)) ;
	}

    /**
     * This function returns a list of subfolders for a specified folder.
     * The result is return as an xml structure:
     * <pre>
     *  &lt;f&gt;
     *   &lt;id&gt;id of subfolder&lt;/id&gt;&lt;s&gt;subfolder name&lt;/s&gt;
     *   ...
     *  &lt;/f&gt;
     * </pre>
     * @param string $deparment Department to look in
     * @param string $cabinet Cabinet to look in
     * @param string $docid ID of the folder to list subfolders of
     * @param string $start the starting id for a limit query
     * @param string $amount the amount of subfolders to return
     * @return string XML containing a list of subfolders
     */
    function getSubfolders($department, $cabinet, $docid, $start, $amount)
	{
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess =  "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;	
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;
        
        $query = "select id, subfolder from {$cabinet}_files where filename is Null  and deleted = 0 and ".
                 "display = 1 and doc_id = {$docid} order by doc_id";
                 //" limit {$start}, {$amount}";
        return XMLPass(getSubfoldersHelp($department, $query));
    }
	
    /**
     * This function returns a list of subfolders for a specified folder,
     * based on the search string.
     * The result is return as an xml structure:
     * <pre>
     *  &lt;f&gt;
     *   &lt;id&gt;id of subfolder&lt;/id&gt;&lt;s&gt;subfolder name&lt;/s&gt;
     *   ...
     *  &lt;/f&gt;
     * </pre>
     * @param string $deparment Department to look in
     * @param string $cabinet Cabinet to look in
     * @param string $docid ID of the folder to list subfolders of
     * @param string $search value to be compared with during search
     * @param string $start the starting id for a limit query
     * @param string $amount the amount of subfolders to return
     * @return string XML containing a list of subfolders
     */
    function getSubfoldersSearch($department, $cabinet, $docid, $search, $start, $amount)
	{
		// Get default values if bad
        if( $start=='' || !is_numeric( $start ) || $start < 0 ){
	        $mess =  "start not set correctly or not numeric start=$start";
			return XMLError($mess) ;	
		}

        if( $amount=='' || !is_numeric( $amount ) || $amount < 1 )
	        $amount = 100;
        
        $query = "select id, subfolder from {$cabinet}_files where filename is NULL and ". 
                 "deleted = 0 and ".
                 "display = 1 and doc_id = {$docid} and subfolder " . LIKE . " '%$search%' ".
                 "order by doc_id"; //" limit {$start}, {$amount}";

        return XMLPass(getSubfoldersHelp($department, $query));
    }

	/**
	 * This function returns the headers of a given cabinet in the department.
	 * The result is returned as an XML structure:
	 * <pre>
	 *  &lt;header&gt;
	 *   &lt;h&gt;<i>indice name</i>&lt;/h&gt;
	 *   ...
	 *  &lt;/header&gt;
	 * </pre>
     * 
     * @param string $department Department to look in
	 * @param string $cabinet Cabinet to list the headers of
	 * @return string XML containing the header structure above
	 */
	function getCabinetHeaders($department, $cabinet)
	{
		$db_object = getDbObject($department) ;

        if (!PEAR::isError($db_object))
        {
            $result = getCabinetInfo($db_object, $cabinet);
            if (!PEAR::isError($result))
            {
                $header = "<header>" ;
                for($i = 0 ; $i < sizeof($result) ; $i++)
                {
                    $header .= "<h>".h($result[$i])."</h>" ;
                }
                $header .= "</header>" ;
                return XMLPass($header) ;
            }
            else
            {
                return XMLError("DB Error - CheckOutServer(getCabinetHeaders)");
            }
        }
        else
        {
            return XMLError("DB Error - CheckOutServer(getCabinetHeaders)");
        }
	}

	/**
	 * This function returns a given document in a cabinet specified by
	 * file_id as an attachment along with a filename is sent as a SOAP_Value.
	 * @param string $department Department that contains the document
	 * @param string $cabinet Cabinet containing the document
	 * @param string $file_id ID of the desired document
	 * @param string $username User checking out the document
	 * @return SOAP structure containing an element with the file name and a DIME attachment containing the document
	 */
	function getDocument($department, $cabinet, $file_id, $username)
    {
        global $DEFS;
		// Get needed info about file to send it and the name
		$db_object = getDBObject($department) ;

		// find file and check it out LOCKED
		$parentID = getParentID($cabinet, $file_id, $db_object);
		if($parentID == 0) {
		    makeVersioned($cabinet, $file_id, $db_object);
		    $parentID = $file_id;
		}
		$gotlock = checkAndSetLock($cabinet, $parentID, $db_object, $username);
		$file_id = getRecentID($cabinet, $parentID, $db_object);
		$fileRow = getTableInfo($db_object, $cabinet.'_files', array(), array('id' => (int) $file_id), 'queryRow');
	    	$whereArr = array('doc_id'=>(int)$fileRow['doc_id']);
	    	$result = getTableInfo($db_object,$cabinet,array(),$whereArr);
		$row = $result->fetchRow();

        //set the path to the actual file on the server
		$path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/{$row['location']}");
		if(isset($fileRow['subfolder']) and $fileRow['subfolder']) {
		    $path = $path."/".$fileRow['subfolder'];
		}
		$file = $path ."/".$fileRow['filename'];
		// If everything is good, get file for download
		if ( file_exists($file) && is_file($file) ){
			$who = whoLocked($cabinet, $parentID, $db_object);
			// Get information for the file name if check out for writing
			if($gotlock || ($who == $username)){
				$cabid = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cabinet), 'queryOne');
				$depid = str_replace("client_files", "", $department);
				$fileprefix = "[$depid-$cabid-$file_id]" ;
                $mess = "";
			}
			else
            {
                if ($who == "9FROZEN") 
                    $mess = "Frozen File: File Checked Out Read-Only";
                else
                    $mess = "File Locked: File Checked Out Read-Only\n\t(Locked by $who)";

				$fileprefix = "[Read Only]" ;
            }
			// add on version number and setup filename
			$versionnum = "-{$fileRow['v_major']}_{$fileRow['v_minor']} " ;
			$newfilename = getDisplayName($fileRow['parent_filename'], $fileRow['filename']) ;
			$newfilename = explode(".", $newfilename) ;
			$partnum = sizeof($newfilename)-2 ;
			$newfilename[$partnum] .= $versionnum ;
			$newfilename = implode(".", $newfilename) ;
			$newfilename = $fileprefix.$newfilename ;

            //build the xml to be sent back with the document
            $soapmess = new SOAP_Value("message", '',$mess);
			// now send filename and file
			$soapfile = new SOAP_Value("filename",'',$newfilename);
			$soapattach = new SOAP_Attachment("return","application/octet-stream", 
								"CheckOutServer",file_get_contents($file));
			return new SOAP_Value("getDocument", "text/plain", 
													array($soapmess, $soapfile, $soapattach)) ;
		}
		else
			return new SOAP_Fault("Could not obtain file for download") ;
    }	
}

// This function takes the given query or the department and sends back
// the given string of results
function getFilesHelp($department, $query)
{
	$db_object = getDbObject( $department );
	// Check for DB error, return message
	if( PEAR::isError($db_object))
		return $db_object->getMessage(); 

	// Query for restults and build the string to send	
	$results = $db_object->query( $query );
	if( !PEAR::isError($results) )
	{
		$build = "";
		//$info = $results->reverse->tableInfo();
		while( $row = $results->fetchRow() )
		{
			// put on doc id, then rest of indicies
			$build .= "<f><id>{$row['id']}</id><i>".
				h(getDisplayName($row['parent_filename'], $row['filename'])).
				"</i></f>";
		}
		$db_object->disconnect();
		return $build;
	}

	// If error, send back message
	else
		return $results->getMessage().
				" DB=$department, table=$cabinet";
}

function getFoldersHelp($department, $query,$cabinet)
{
	$db_object = getDbObject( $department );
	if( PEAR::isError($db_object))
		return $db_object->getMessage(); 

	$results = $db_object->query( $query );
	if( PEAR::isError($results) )
		 return $results->getMessage().": $query";
	
	$build = "";	
	// get all the data
	while( $row = $results->fetchRow() )
	{
		$a = "<f>";
		foreach($row as $indiceName => $value)
		{
			if( strcmp($indiceName, "doc_id") == 0 )
				$a .= "<id>".$value."</id>";
			else if( (strcmp($indiceName, "location") != 0) 
				&& (strcmp($indiceName, "deleted") != 0) )
				$a .= "<i>".urlencode($value)."</i>";
		}
		// delimit with semi and build the result
		$build .= $a . "</f>" ;
	}
	
	$db_object->disconnect();
	return $build;
}

function getSubfoldersHelp($department, $query)
{
    $db_object = getDBObject( $department );
    if ( PEAR::isError($db_object))
        return $db_object->getMessage();

    $results = $db_object->query( $query );
	if( !PEAR::isError($results) )
	{
		$build = "";
		//$info = $results->reverse->tableInfo();
		while( $row = $results->fetchRow() )
		{
			// build the XML string containing the subfolders
			$build .= "<f><id>".$row['id']."</id><s>".
				h($row['subfolder']).
				"</s></f>";
		}
		$db_object->disconnect();
        return $build; 
    }
    else
        return $results->getMessage().": $query";
}

// Fire up PEAR::SOAP_Server
$server = new SOAP_Server();

// Fire up your class
$webService = new CheckOutServer();

// Add your object to SOAP server (note namespace)
$server->addObjectMap($webService,'urn:CheckOutServer');

// Handle SOAP requests coming is as POST data
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service($HTTP_RAW_POST_DATA);
} else {
    // Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';

    // Create the Disco server
    $disco = new SOAP_DISCO_Server($server,'CheckOutServer');
    header("Content-type: text/xml");
    if (isset($_SERVER['QUERY_STRING']) &&
        strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
        echo $disco->getWSDL(); // if we're talking http://www.example.com/index.php?wsdl
    } else {
        echo $disco->getDISCO();
    }
    exit;
}
?>

