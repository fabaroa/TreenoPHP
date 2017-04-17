<?php
/**
 * This file contains all that is needed for the UtilityServer.
 * It contains the class itself, as well as all the helper functions
 * needed and the code needed to drive the server.
 * @package SOAPServer
 */

require_once 'SOAP/Server.php';
require_once 'SOAP/Value.php';
include_once '../lib/versioning.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../lib/utility.php';
include_once '../lib/quota.php' ;
include_once '../lib/SOAPfuncs.php' ;
include_once '../lib/filter.php' ;


// UtilityServer class
/**
* Web service to check out files and retreive information from repository.
*
* UtilityServer is a soap service that provides integrating
* applications useful functions for accessing the repository.
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
* @author Brad Tetu <btetu@docutronsystems.com>
* @version 1.0
*/
class UtilityServer {
    var $__dispatch_map = array();

    function UtilityServer() {
        // Define the signature of the dispatch map
        $this->__dispatch_map['getDefaultDep'] =
            array('in' => array('username' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getDepArbName'] =
            array('in' => array('realname' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getDepRealName'] =
            array('in' => array('arbname' => 'string'),
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['login'] =
            array('in' => array('username' => 'string',
								'password' => 'string',
                                'department' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getRWCabinets'] =
            array('in' => array('username' => 'string',
                                'department' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getDepartments'] =
            array('in' => array('username' => 'string',
                                'password' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getUsers'] =
            array('in' => array('department' => 'string'), 
                  'out' => array('outputString' => 'xml')
                  );
        $this->__dispatch_map['getDataDir'] =
            array('in' => array('bogus'=> 'string'),
                  'out'=> array('outputString' => 'xml')
                  ); 
        $this->__dispatch_map['getNextBatch'] =
            array('in' => array('scanner'=> 'string',
                                'cabinet'=> 'string',
				'department' => 'string'),
                  'out'=> array('outputString' => 'xml')
                  );
        $this->__dispatch_map['addFileContext'] =
            array('in' => array('department'=> 'string',
                                'cabinet'=> 'string',
                                'docid'=> 'string',
                                'subfolder'=> 'string',
                                'filename'=> 'string',
                                'context'=> 'string'),
                  'out'=> array('outputString' => 'xml')
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
    function login($username, $password, $department)
    {
        $db_object = getDbObject( $department );

        $DO_user = DataObject::factory('users');
        $DO_user->get('username', $username);
        $passwd = $DO_user->password;
        if(strtolower($DO_user->password) == strtolower($password))
        {
	        //send OK to login
	        return "ok";
        }
        else
        {
	        //send error, password incorrect
	        return "invalid login";
        }
    }

    /**
     * This function returns the data directory of the docutron server.
     * @return string Returns the common response XML structure with the value being the data dir
     */
    function getDataDir($bogus)
    {
        global $DEFS;
        return XMLPass(h($DEFS['DATA_DIR']));
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
        return XMLPASS(h($DO_user->defaultDept));
    }
    
    /**
     * This function returns the arbitrary name of the specified real name.
     * @param string $realname The realname of the department
     * @return string Returns the arbitrary name of the department
     */
    function getDepArbName($realname)
    {
        $department = "docutron";
        $db_object = getDBObject($department);
        if(PEAR::isError($db_object))
        {
            return XMLError( $db_object->getMessage(). " " .$department );
        }
        $arb_name = getTableInfo($db_object, 'licenses', array('arb_department'), 
		array('real_department' => $realname), 'queryOne');
        if($arb_name)
        {
	        return XMLPass( h($arb_name) );
        }
        else
        {
            return XMLError( "Error->Department($realname) does not exist" );
        }
    }
    
    /**
     * This function returns the real name of the specified arbitrary name.
     * @param string $arbname The arbname of the department
     * @return string Returns the real name of the department
     */
    function getDepRealName($arbname)
    {
        $department = "docutron";
        $db_object = getDBObject($department);
        if(PEAR::isError($db_object))
        {
            return XMLError( $db_object->getMessage(). " " .$department );
        }
        $result = getLicensesInfo($db_object,NULL,$arbname);
        if(!PEAR::isError($result))
        {
	        $result = $result->fetchRow();
	        $arb_name = $result['real_department'];
	        return XMLPass( h($arb_name) );
        }
        else
        {
            return XMLError( "Error->Department($arbname) does not exist" );
        }
    }

	/**
	 * This function returns a list of
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
	    foreach($accessRights as $cabinet => $rights) 
           {
		    if(strcmp($rights, "rw") == 0 and $deleted[$cabinet] != 1)
		    {
			    $cabinetRealList[] = $cabinet;
		    }
	    }
	    usort( $cabinetRealList, "strnatcasecmp" );
           foreach($cabinetRealList as $cabinet)
           {
               $xmlString .= "<f id=\"" . h($cabinet) . "\">" . h($arbList[$cabinet]) . "</f>"; 
           }
           $db_object->disconnect();
           return XMLPass($xmlString);
    }

    /**
     * This function returns a list of all the departments the user
     * has access to. 
	 * <pre>
	 *  &lt;f&gt;
	 *   &lt;id&gt;<f id="depRealName">depArbname</f>&lt;/id&gt;
	 *  &lt;/f&gt;
	 *  ...
	 * </pre>
     * @param string $username The name of the user
     * @param string $password The password for validation
     * @return string The list of departments in the standard xml structure
     */
     
    function getDepartments($username, $password)
    {
        $db_object = getDbObject( 'docutron' );
        $DO_user = DataObject::factory('users', $db_object);
        $DO_user->get('username', $username);
        $arbArr = getLicensesInfo($db_object, 'real_department', 'arb_department', 1);
        $depArr = array();
        $xmlString = '';
        foreach($DO_user->departments as $depName => $priv) {
            $xmlString .= "<f id=\"" . h($depName) 
                            . "\">" . h($arbArr[$depName]) 
                            . "</f>";
        }
        return XMLPass($xmlString);
    }

    /**
     * This function returns a list of users for a department.
     * @param string $department The department from which to list users
     * @return string The list of users in standard xml format
     */
    function getUsers($department)
    {
        $db_object = getDbObject( $department );
        if( PEAR::isError($db_object))
        {
            return XMLError($db_object->getMessage(). " " .$department );
        }
  	$results = getTableInfo($db_object,'access');
        if( !PEAR::isError($results) )
        {
	        while($user = $results->fetchRow())
	        {
		        $xml .= "<u>"
                      . h($user['username'])
                      . "</u>";
	        }
	        return XMLPass($xml);
        }
        else
        {
            return XMLError("Error Cabinet Listing - getUsers.php");
        }
    }

    /**
     * This function returns the next batch number to 
     * be scanned.
     * @param string $scanner The id of the scanner
     * @param string $cabinet The cabinet being scanned to
     * @return string The next batch number in xml format
     */
    function getNextBatch($scanner, $cabinet, $department)
    {
	    $db_doc = getDbObject ('docutron');
	$gblStt = new GblStt($department, $db_doc);
	
        if($batch_num = $gblStt->get($cabinet.'_batch'))
        {
            if($batch_num < 1)
                $batch_num = 1;
	$gblStt->set($cabinet.'_batch', $batch_num + 1);
        }
        else
        {
	$gblStt->set($cabinet.'_batch', 2);
            $batch_num = 1;
        }
        $formatted = $batch_num;
        while( strlen($formatted) < 7 )
            $formatted = '0' . $formatted;
        $formatted = $scanner . $formatted;
        return XMLPass($formatted);
    }

    /**
     * This function inserts the OCR context of the file specified into the DB.
     * @param string $department The deparment to upload to.
     * @param string $cabinet The cabinet to upload to.
     * @param string $docid The docid in the cab_files table.
     * @param string $subfolder The subfolder where the file resides
     * @param string $filename The name of the file
     * @param string $context The ocr context from the file.
     */
    function addFileContext($department, $cabinet, $docid, $subfolder, $filename, $context)
    {
        $db_object = getDBObject( $department );
        $queryArr = array('OCR_context' => $context);
        $res = $db_object->extended->autoExecute($cabinet."_files", $queryArr, MDB2_AUTOQUERY_UPDATE, 
                                                "doc_id = $docid and filename = '$filename'");
        return XMLPass("true");
    }
}

// Fire up PEAR::SOAP_Server
$server = new SOAP_Server();

// Fire up your class
$webService = new UtilityServer();

// Add your object to SOAP server (note namespace)
$server->addObjectMap($webService,'urn:UtilityServer');

// Handle SOAP requests coming is as POST data
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service($HTTP_RAW_POST_DATA);
} else {
    // Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';

    // Create the Disco server
    $disco = new SOAP_DISCO_Server($server,'UtilityServer');
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
