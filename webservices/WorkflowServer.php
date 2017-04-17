<?php
/**
 * This file contains all that is needed for the WorkflowServer.
 * It contains the class itself, as well as all the code needed
 * to drive the server 
 * @package SOAPServer
 */
	require_once 'SOAP/Server.php' ;
	include_once '../lib/SOAPfuncs.php';
	include_once '../lib/utility.php' ;
	include_once '../lib/filter.php' ;
	include_once '../lib/settings.php' ;
    include_once '../workflow/node.inc.php';
	include_once '../workflow/mas500Node.inc.php';
	include_once '../db/db_common.php';

// WorkflowServer class
/**
 * Web service to check in files and validate that they can be checked in
 * or that they have been checked in properly.  
 *
 * WorkflowServer is a soap service that allows an outside application
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
class WorkflowServer{
	var $__dispatch_map = array() ;
	
	function WorkflowServer(){
		// Define the signature of the dispatch map
		$this->__dispatch_map['getDeps'] =
			array('in' => array('username' => 'string'),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['getDepWorkflows'] =
			array('in' => array('username' => 'string',
								'department' => 'string',
								'nodetype' => 'string'),
				  'out' => array('outputString'=>'xml')
				);
		$this->__dispatch_map['finalizeWorkflow'] =
			array('in' => array('username' => 'string',
								'department' => 'string',
								'cabinet' => 'string',
								'doc_id' => 'string',
								'indices' => 'string',
								'nodetype' => 'string'),
				  'out' => array('outputString'=>'xml')
				);
	}

	function __dispatch($methodname){
		if(isset($this->__dispatch_map[$methodname]))
			return $this->__dispatch_map[$methodname] ;
		return NULL ;
	}

	/** 
	 * Gets a list of departments accessable by a given user
	 * @param string $username Username to look for in the workflows
	 * @return string Returns a list of departments
	 */
	function getDeps($username)
	{
		$ret = "";
		$db_object = getDbObject('docutron') ;
		// There should already be a function for this
        if ( PEAR::isError($db_object) )
			return $ret;
        
		$query = "SELECT db_name FROM users, db_list";
		$query .= " WHERE users.username='$username'";
		$query .= " AND db_list_id = list_id";
//            $query = "select db_name from users where username='$username'";
		$result = $db_object->query($query);
		if ( PEAR::isError($result) )
			return $ret;

		while( $dep = $result->fetchRow() )
			$ret .= "<d>".h($dep['db_name'])."</d>";
		
		return $ret;
	}

	/** 
	 * Gets a list of workflows for a given user in a department
	 * @param string $department Department to look for the workflows
	 * @param string $username Username to look for in the workflows
	 * @return string Returns a list of open workflows for user
	 */
	function getDepWorkflows($username, $department, $nodetype)
	{
	    // get the department db object
		$db_object = getDbObject($department) ;
		// This query gets a list of all the user is an explicit user for
		$query = "select wf_documents.doc_id, wf_documents.cab from wf_documents, wf_defs, wf_nodes, user_list where state_wf_def_id=wf_defs.id and node_id=wf_nodes.id and user_list_id=user_list.list_id and username='$username' and wf_documents.status='$nodetype' and wf_documents.status != 'COMPLETED'";
//$query = "select wf_documents.doc_id, wf_documents.cab from wf_documents, wf_defs, wf_nodes where state_wf_def_id=wf_defs.id and node_id=wf_nodes.id and wf_documents.status='$nodetype' and wf_documents.status != 'COMPLETED'";
		$result1 = $db_object->query($query) ;
		$id_cab_arr = array();
		while( $row = $result1->fetchrow() ){
			$id_cab_arr[$row['doc_id']] = $row['cab'];
		}

		// This gets a list of all workflows with groups, go through by hand.
        // This query will also return all of the doc_ids that the above query
        // returns.
		$query = "select wf_documents.doc_id, wf_documents.cab from wf_documents, wf_defs, wf_nodes, group_list, groups,access,users_in_group where state_wf_def_id=wf_defs.id and node_id=wf_nodes.id and group_list_id=group_list.list_id and groupname=groups.real_groupname and wf_documents.status='$nodetype' and wf_documents.status != 'COMPLETED' AND groups.id=users_in_group.group_id AND users_in_group.uid=access.uid AND access.username='$username'";
//$query = "select wf_documents.doc_id, wf_documents.cab from wf_documents, wf_defs, wf_nodes where state_wf_def_id=wf_defs.id and node_id=wf_nodes.id and wf_documents.status='$nodetype' and wf_documents.status != 'COMPLETED'";
		$result2 = $db_object->query($query) ;
		while($row = $result2->fetchrow()){
			$id_cab_arr[$row['doc_id']] = $row['cab'];
		}
        
		foreach( $id_cab_arr as $key => $row ){
			$resultStr .= "<wf><id>".h($key)."</id><cab>";
			$resultStr .= h($row)."</cab></wf>";	
		}
		return $resultStr ;
	}

	/** 
	 * Gets a list of workflows for a given user in a department
	 * @param string $department Department to look for the workflows
	 * @param string $username Username to look for in the workflows
	 * @param string $indices List of new indices
	 */
	function finalizeWorkflow($username, $department, $cabinet, $doc_id, $indices, $nodetype)
	{
/*$fd = fopen("/tmp/wtf", "w+");
fwrite($fd, "finalizeWorkflow\n");
fwrite($fd, "username: $username\n");
fwrite($fd, "dep: $department\n");
fwrite($fd, "cab: $cabinet\n");
fwrite($fd, "doc_id: $doc_id\n");
fwrite($fd, "indices: ".print_r($indices, true));
fwrite($fd, "\nnodeType: $nodetype\n");
*/      //create the db object
        $db_object = getDBObject($department);
        $db_doc = getDBObject('docutron');
		$indNames = getCabinetInfo($db_object, $cabinet);
        $indValues = explode(",,,", $indices);
//fwrite($fd, "db indices: ".print_r($indNames, true));        
      //build the update query
        $query = "update $cabinet set ";
        for( $i = 0; $i < sizeof($indValues) - 1; $i++ )
            $query .= $indNames[$i]."='".$indValues[$i]."', ";
        $query .= $indNames[$i]."='".$indValues[$i]."' ";
        $query .= "where doc_id=$doc_id";
      //update the indice values
//fwrite($fd, "\nquery: $query\n");
        $result = $db_object->query($query);
        if ( !PEAR::isError($result) )
        {
        //create a MAS500 node for the document
        //get fields needed to build the node
          $query = "SELECT id, state_wf_def_id FROM wf_documents";
			$query .= " WHERE status NOT LIKE 'COMPLETED' AND doc_id=" . $doc_id;
          $result = $db_object->query($query);
          if( PEAR::isError($result) ){
              return "<return>Error: Unable to retrieve wf data</return>";
          }
          $row = $result->fetchRow();
//fwrite($fd, "row: ".print_r($row, true));
          $wf_document_id = $row['id'];
          $state_wf_def_id = $row['state_wf_def_id'];
	$cabDispName = getTableInfo($db_object, 'departments', array('departmentname'),
		array('real_name' => $cabinet), 'queryOne');

        //build the node depending on the type
          if( $nodetype == "MAS500" )
            $node = new mas500Node($db_object, $department, $username, $wf_document_id, $state_wf_def_id, $cabinet, $cabDispName, $doc_id, $db_doc);
          else
            $node = new node($db_object, $department, $username, $wf_document_id, $state_wf_def_id, $cabinet, $cabDispName, $doc_id, $db_doc);
        //goto next state
          $node->accept();
//fwrite($fd, "\nnode accept\n");
//fclose($fd);
		  return "<return>Success</return>" ;
        }
        else
        {
//fwrite($fd, "error update indices failed\n");
//fclose($fd);
          return "<return>Error: Update indices failed</return>";
        }
	}
}

// Fire up PEAR::SOAP_Server
$server = new SOAP_Server();
                                                                                
// Fire up your class
$webService = new WorkflowServer();
                                                                                
// Add your object to SOAP server (note namespace)
$server->addObjectMap($webService,'urn:WorkflowServer');
                                                                                
// Handle SOAP requests coming is as POST data
if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD']=='POST') {
    $server->service($HTTP_RAW_POST_DATA);
} else {
    // Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';
                                                                                
    // Create the Disco server
    $disco = new SOAP_DISCO_Server($server,'WorkflowServer');
    header("Content-type: text/xml");
    if (isset($_SERVER['QUERY_STRING']) &&
        strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
        echo $disco->getWSDL(); // if we're talking http://www.example.com/index.php?wsdl
    } else {
        echo $disco->getDISCO();
    }
    exit;
}
