<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/filter.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0)
{
    $db_object = $user->getDbObject();
	$workflowList =  getTableInfo($db_object,'wf_defs',array('DISTINCT(defs_name)'),array(),'queryCol',array('defs_name'=>'ASC'));

echo<<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
 <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
 <script>
  	function selectWF()
	{
		var wfElement = document.wfhistory.wfnames;
		location = wfElement[wfElement.selectedIndex].value;
	}
  </script>
 </head>
 <body>
ENERGIE;
	if( !isSet( $_POST['wfnames'] ) )
	{
		echo<<<ENERGIE
  <div align="center">
   <form name="wfhistory" method="POST" action="viewWFHistory.php">
    <table class="settings" width="350">
     <tr class="tableheads">
	  <td colspan="2">Search Workflow History</td>
     </tr>
     <tr>
	  <td align="left">Workflow</td>
	  <td align="center">
       <select name="wfnames">
	    <option value="all">All</option>
ENERGIE;
	for($i=0;$i<sizeof($workflowList);$i++)
	{
		$wfname = h($workflowList[$i]);
		echo "<option value=\"$wfname\">";
		echo str_replace( "_", " ", $wfname );
		echo "</option>\n";
	}
echo<<<ENERGIE
	  </select>
     </td>
    </tr>
	<tr>
	 <td align="left">ID</td>
	 <td><input type="input" name="ID"></td>
	</tr>
	<tr>
	 <td align="left">Date</td>
	 <td><input type="input" name="date"></td>
	</tr>
	<tr>
	 <td align="left">Owner</td>
	 <td><input type="input" name="owner"></td>
	</tr>
	<tr>
	 <td align="left">Location</td>
	 <td><input type="input" name="location"></td>
	</tr>
	<tr>
	 <td align="left">Username</td>
	 <td><input type="input" name="username"></td>
	</tr>
	<tr>
	 <td align="left">State</td>
	 <td><input type="input" name="state"></td>
	</tr>
	<tr>
	 <td align="left">Node Type</td>
	 <td><input type="input" name="nodeType"></td>
	</tr>
	<tr>
	 <td align="left">Node Name</td>
	 <td><input type="input" name="nodeName"></td>
	</tr>
	<tr>
	 <td align="left">Action</td>
	 <td><input type="input" name="action"></td>
	</tr>
	<tr>
	 <td align="left">Status</td>
	 <td><input type="input" name="status"></td>
	</tr>
	<tr>
	 <td align="left">Notes</td>
	 <td><input type="input" name="notes"></td>
	</tr>
	<tr>
	 <td colspan="2" align="right">
	  <input type="submit" name="search" value="Search">
	 </td>
	</tr>
   </table>
   </form>
  </div>
ENERGIE;
	}
	else
	{
		$whereClause = "";
		if( $_POST['wfnames'] != "all" )
			$whereClause .= " AND LOWER(wf_defs.defs_name) " . LIKE . " '%".strtolower($_POST['wfnames'])."%'";
	
		if( $_POST['ID'] != NULL )
			$whereClause .= " AND CAST(wf_history.id AS CHAR) " . LIKE . " '%{$_POST['ID']}%'";

		if( $_POST['date'] != NULL )
			$whereClause .= " AND wf_history.date_time " . LIKE . " '%".$_POST['date']."%'";

		if( $_POST['owner'] != NULL )
			$whereClause .= " AND LOWER(wf_documents.owner) " . LIKE . " '%".strtolower( $_POST['owner'] )."%'";

		$searchDep = false;

		if( $_POST['location'] != NULL ) {
			$searchDep = true;
			$whereClause .= " AND wf_documents.cab=departments.real_name";
			$whereClause .= " AND (LOWER(departments.departmentname) " . LIKE . " " .
					"'%". strtolower ($_POST['location']) . "%'";
			
			if (is_numeric($_POST['location'])) {
				$whereClause .= " OR wf_documents.doc_id = " .
					$_POST['location'];
			}

			$whereClause .= ")";
		}
			
		if( $_POST['username'] != NULL )
			$whereClause .= " AND LOWER(wf_history.username) " . LIKE . " '%".strtolower( $_POST['username'] )."%'";

		if( $_POST['state'] != NULL )
			$whereClause .= " AND wf_history.state " . LIKE . " '%{$_POST['state']}%'";

		if( $_POST['nodeType'] != NULL )
			$whereClause .= " AND LOWER(wf_nodes.node_type) " . LIKE . " '%".strtolower( $_POST['nodeType'] )."%'";

		if( $_POST['nodeName'] != NULL )
			$whereClause .= " AND LOWER(wf_nodes.node_name) " . LIKE . " '%".strtolower( $_POST['nodeName'] )."%'";

		if( $_POST['action'] != NULL )
			$whereClause .= " AND wf_history.action " . LIKE . " '%".strtolower( $_POST['action'])."%'";

		if( $_POST['status'] != NULL )
			$whereClause .= " AND LOWER(wf_documents.status) " . LIKE . " '%".strtolower( $_POST['status'] )."%'";

		if( $_POST['notes'] != NULL )
			$whereClause .= " AND wf_history.notes " . LIKE . " '%". $_POST['notes']."%'";

		$wfInfo =getWFlowHistory($db_object, $whereClause, $searchDep);
		echo "<table style=\"border-collapse: collapse\" class=\"settings\" width=\"100%\">\n";
		echo " <tr class=\"tableheads\">\n";
		echo "  <td>ID</td>\n";
		echo "  <td>Date</td>\n";
		echo "  <td>Owner</td>\n";
		echo "  <td>Location</td>\n";
		echo "  <td>Username</td>\n";
		echo "  <td>State</td>\n";
		echo "  <td nowrap=\"yes\">Node Type</td>\n";
		echo "  <td>Node Name</td>\n";
		echo "  <td>Workflow Name</td>\n";
		echo "  <td>Action</td>\n";
		echo "  <td>Status</td>\n";
		echo "  <td>Notes</td>\n";
		echo " </tr>\n";
		while( $result = $wfInfo->fetchRow() )
		{
                        $id = $result['id'];
			
		// to get CLOB data in DB2 
			$wfres = getTableInfo($db_object,'wf_history',array(),array('id'=>(int)$id));
                        $row = $wfres->fetchRow();
                        
			$nodeName = str_replace("_", " ", $result['node_name']);
			echo " <tr>\n";
			echo "  <td>".$result['id']."</td>\n";
			echo "  <td nowrap=\"yes\">".$result['date_time']."</td>\n";
			echo "  <td>".$result['owner']."</td>\n";
			echo "  <td nowrap=\"yes\">Cabinet: ".$user->cabArr[$result['cab']];
			echo "  <br>Folder: ".$result['doc_id']."</td>\n";
			echo "  <td>".$result['username']."</td>\n";
			echo "  <td>".h($result['state'])."</td>\n";
			echo "  <td>".h($result['node_type'])."</td>\n";
			echo "  <td nowrap=\"yes\">".h($nodeName)."</td>\n";
			echo "  <td>".h($result['defs_name'])."</td>\n";
			echo "  <td>".$row['action']."</td>\n";
			echo "  <td nowrap=\"yes\">".$result['status']."</td>\n";
			echo "  <td>".h($row['notes'])."</td>\n";
			echo " </tr>\n";
		}
		echo "</table>\n";
	}
echo<<<ENERGIE
 </body>
</html>
ENERGIE;

    setSessionUser($user);
}
else
{
echo<<<ENERGIE
<html>
<body bgcolor="#FFFFFF">
<script>
document.onload = top.window.location = "../logout.php"
</script>
</body>
</html>
ENERGIE;
}
?>
