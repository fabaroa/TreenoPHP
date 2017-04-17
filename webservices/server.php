<?php
require_once 'SOAP/Server.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../DataObjects/DataObject.inc.php';

// DMSServer class
class DMSServer {
    var $__dispatch_map = array();

    function DMSServer() {
        // Define the signature of the dispatch map
        $this->__dispatch_map['login'] =
            array('in' => array('username' => 'string',
                                'password' => 'string',
                                'department' => 'string'), 
                  'out' => array('outputString' => 'string')
                  );
        $this->__dispatch_map['getDataDir'] = 
            array('in' => array(),
                  'out' => array('outputString' => 'string')
                 );
        $this->__dispatch_map['getDefaultDep'] = 
            array('in' => array('username' => 'string'),
                  'out' => array('outputString' => 'string')
                 );
        $this->__dispatch_map['getDepArbName'] = 
            array('in' => array('real_dep' => 'string'),
                  'out' => array('outputString' => 'string')
                 );
        $this->__dispatch_map['getDepRealName'] = 
            array('in' => array('arb_dep' => 'string'),
                  'out' => array('outputString' => 'string')
                 );
        $this->__dispatch_map['getRWCabinets'] =
            array('in' => array('username' => 'string',
                                'password' => 'string',
                                'department' => 'string'), 
                  'out' => array('outputString' => 'string')
                  );
        $this->__dispatch_map['getDepartments'] =
            array('in' => array('username' => 'string',
                                'password' => 'string'),
                  'out' => array('outputString' => 'string')
                  );
        $this->__dispatch_map['getScanID'] = 
            array('in' => array('department' => 'string'),
                  'out' => array('outputString' => 'string')
                 );
        $this->__dispatch_map['getBatchName'] = 
            array('in' => array('scanner' => 'string',
                                'department' => 'string',
                                'cabinet' => 'string'),
                  'out' => array('outputString' => 'string')
                 ); 
        $this->__dispatch_map['resetBatch'] = 
            array('in' => array('scanner' => 'string',
                                'department' => 'string',
                                'cabinet' => 'string'),
                  'out' => array()
                 ); 
    }

    // Required function by SOAP_Server
    function __dispatch($methodname) {
        if (isset($this->__dispatch_map[$methodname]))
            return $this->__dispatch_map[$methodname];
        return NULL;
    }

    //This function compare the username and password 
    //fields with those in the database. If they do not
    //match login fails, else login is successfull
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

    //This function returns the data directory
    function getDataDir()
    {
        return $DEFS['DATA_DIR'];
    }

    //This function returns the Default department
    //for the user
    function getDefaultDep($username)
    {
        $db_object = getDbObject( 'docutron');
        $DO_user = DataObject::factory('users', $db_object);
        $DO_user->get('username', $username);
        return $DO_user->defaultDept;
    } 

    //This function given a real name returns the arbitrary 
    //name fo the department
    function getDepArbName($real_dep)
    {
        $department = "docutron";
        $db_object = getDBObject($department);
        if(PEAR::isError($db_object))
        {
            return $db_object->getMessage(). " " .$department;
        }
	$result = getTableInfo($db_object, 'licenses', array('arb_department'), array('real_department' => $real_dep));
        if (!PEAR::isError($result) && $result = $result->fetchRow())
        {
	        $arb_name = $result['arb_department'];
	        echo returnResult($arb_name, "arb_name");
        }
        else
        {
         return "Error->Department($real_dep) does not exist";
        }
    }
    
    //This function given an arbitrary name returns
    //the real name of the department
    function getDepRealName($arb_name)
    {
        $department = "docutron";
        $db_object = getDbObject( $department );
        if( PEAR::isError($db_object))
        {
            return $db_object->getMessage(). " " .$department;
        }
        $results = getLicensesInfo($db_object,NULL,$arb_name);
        if( !PEAR::isError($results) )
        {
            $depStr = $results->fetchRow();
	        $depStr = $depStr['real_department'];
	        return $depStr;
        }
        else
        {
            return "Error Deparment Listing - getDeparments";
        }
    }
 
    //This function given a department name, a username, and
    //a password will return a list of the cabinets that the
    //the user has access to
    function getRWCabinets($username, $password, $department)
    {
        $db_object = getDbObject( $department );
        if( PEAR::isError($db_object))
        {
            return $db_object->getMessage(). " " .$department;
        }
        $delete = array();
	$results = getTableInfo($db_object, 'departments', array('departmentname', 'deleted'));

        while($del = $results->fetchrow())
        {
            $dep = $del['departmentname'];
	        $deleted[$dep] = $del['deleted'];
        }

  	$results = getTableInfo($db_object,'access',array(),array('username'=>$username));
	    $access = $results->fetchRow();
	    $accessRights = unserialize(base64_decode($access['access']));
	    foreach($accessRights as $cabinet => $rights) 
           {
		    if(strcmp($rights, "rw") == 0 and $deleted[$cabinet] != 1)
		    {
			    $cabinetList[] = $cabinet;
		    }
	    }
	    usort( $cabinetList, "strnatcasecmp" );
           return implode(",",$cabinetList);
    } 

    //This function given a username and password returns
    //a list of the departments the user has access to
    function getDepartments($username, $password)
    {
        $db_object = getDbObject( 'docutron' );
        $DO_user = DataObject::factory('users', $db_object);
        $DO_user->get('username', $username);
        $arbArr = getLicensesInfo($db_object, 'real_department', 'arb_department', 1);
        $depArr = array();
        foreach($DO_user->departments as $depName => $priv) {
        	$depArr[] = $arbArr[$depName];
        }
        return implode(',', $depArr);
    }
   
    //This function given a department returns the next
    //consecutive scanner id
    function getScanID($department)
    {
	    $db_doc = getDbObject ('docutron');
	$gblStt = new GblStt($department, $db_doc);
        if( $result = $gblStt->get('scanID'))
        {
	        $scanID = $result;
	        $result = $result + 1;
		$gblStt->set('scanID', $result);
	        if(strlen($scanID) == 1)
	        {
		        $scanID = "S00" . $scanID;
	        }
	        else if (strlen($scanID) == 2)
	        {
                $scanID = "S0" . $scanID;
	        }
	        else
	        {
		        $scanID = "S" . $scanID;
	        }
	        return $scanID;	
        }
        else
        {
		$gblStt->set('scanID', '2');
	        $scanID = "S001";
            return $scanID;
        }
    }
   
    //This function given a scanner id, a department, 
    //and a cabinet will return a batch name
    function getBatchName($scanner, $department, $cabID)
    {
        $db_object = getDbObject( "docutron" );
        if( PEAR::isError($db_object))
        {
            return $db_object->getMessage(). " " .$department;
        }
	    $db_doc = getDbObject ('docutron');
	$gblStt = new GblStt($department, $db_doc);
        if($batch_num = $gblStt->get($cabID.'_batch'))
        {
	        if($batch_num < 1)
		        $batch_num = 1;
		$gblStt->set($cabID.'_batch', $batch_num + 1);
        }
        else
        {
		$gblStt->set($cabID.'_batch', 2);
	        $batch_num = 1;
        }
	    $formatted = $batch_num;
	    while( strlen($formatted) < 7 )
		    $formatted = '0' . $formatted;

	    $formatted = $scanner . $formatted;
	    return $formatted;
    }
 
    //This function resets the value of the next batch
    //name of a cabinet.
    function resetBatch($scanner, $department, $cabID)
    {
	    $db_doc = getDbObject ('docutron');
	$gblStt = new GblStt($department, $db_doc);

        if($batch_num = $gblStt->get($cabID.'_batch'))
        {
            if($batch_num < 1)
  		        $batch_num = 1;
		$gblStt->set($cabID.'_batch', $batch_num - 1);
        }
        else
		$gblStt->set($cabID.'_batch', 1);
    }
}

// Fire up PEAR::SOAP_Server
$server = new SOAP_Server();

// Fire up your class
$webService = new DMSServer();

// Add your object to SOAP server (note namespace)
$server->addObjectMap($webService,'urn:DMSServer');

// Handle SOAP requests coming is as POST data
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service($HTTP_RAW_POST_DATA);
} else {
    // Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';

    // Create the Disco server
    $disco = new SOAP_DISCO_Server($server,'DMSServer');
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
