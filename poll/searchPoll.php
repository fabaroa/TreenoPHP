<?php

include_once '../classuser.inc';
include_once '../check_login.php';
include_once '../search/fileSearch.php';

if( $logged_in==1)
{
$resFound = $trans['Results Found'];
$temp_table = $_GET['temp_table'];
$resPerPage = $_GET['resPerPage'];
$pageNum = $_GET['pageNum'];
$cab = $_GET['cab'];
//$searchObj = $_GET['search'];
$needrefresh = $_GET['needrefresh'] ; // Comes from self, need to refresh mainFrame
//$rate=$_GET['rate'];	//if it reaches a maximum number of refreshes without looking, kill the polling page
echo "running";

if(!isset($resPerPage))
  $resPerPage = 10;

$db_object = $user->getDbObject();
$numResults = getTableInfo($db_object, $temp_table, array('COUNT(*)'), array(), 'queryOne');
$totalPages = floor(($numResults - 1)/$resPerPage) + 1;

if($pageNum == $totalPages)
  $to = $numResults;
$from = (($pageNum - 1)*$resPerPage)+1;
$to = $from + $resPerPage - 1;

//-=-=-=-=-=-=-=-=-=-=-=-=-
// Error stuff

//-=-=-=-=-=-=-=-=-=-=-=-=-

$gbl = new Usrsettings($user->username, $user->db_name );
$context = "done" ; // if there is nothing to grab, want it to be over
$context = $gbl->get("context" ) ;

// Set up the searching message
if($context != "done"){
	$searchmess = "Searching..." ;
}

echo<<<ENERGIE
<html>
 <head>
  <script LANGUAGE="JavaScript">
   var t = 3000;
   thelocation = String(parent.mainFrame.location) ;

   if(thelocation.indexOf("file_search_results") > 0){
ENERGIE;
	

	// If less than a page of results, then we need a refresh
	if(($numResults <= $resPerPage || $needrefresh == "yes") && $numResults != 0 && $context != "stop"){
      	//parent.mainFrame.window.location= '../energie/file_search_results.php?cab=$cab&search=$searchObj&numResults=$numResults&resPerPage=$resPerPage&pageNum=$pageNum&poll1=stop&searchmess=Searching...';
		echo<<<ENERGIE
      	parent.mainFrame.window.location= '../energie/file_search_results.php?cab=$cab&temp_table=$temp_table&numResults=$numResults&resPerPage=$resPerPage&pageNum=$pageNum&poll1=stop&searchmess=$searchmess';
ENERGIE;
	}

	// setup for next time
	$needrefresh = "no" ;
	if($numResults <= $resPerPage)
		$needrefresh = "yes" ;


	//check database if we should update the page
	if($context==""){
		// dont do anything
	}
	else if($context=="update" || $context=="stop"){
	//setTimeout("location.href='../poll/searchPoll.php?temp_table=$temp_table&resPerPage=$resPerPage&pageNum=$pageNum&cab=$cab&search=$searchObj&needrefresh=$needrefresh'",t);
		echo<<<ENERGIE
	setTimeout("location.href='../poll/searchPoll.php?temp_table=$temp_table&resPerPage=$resPerPage&pageNum=$pageNum&cab=$cab&needrefresh=$needrefresh'",t);

	parent.mainFrame.document.getElementById('errorbox').innerHTML = "$searchmess" ;
	parent.mainFrame.document.getElementById('totalresfound').innerHTML = $numResults ;

	if(parent.mainFrame.document.getElementById('totalpagefound'))
		parent.mainFrame.document.getElementById('totalpagefound').innerHTML = $totalPages ;
	if(parent.mainFrame.document.getElementById('totalpagefoundb'))
		parent.mainFrame.document.getElementById('totalpagefoundb').innerHTML = $totalPages ;

ENERGIE;
	}
	else if($context=="done"){ //user has killed the page or was logged out
		$gbl->removeKey("context");
		$finalmess = "&nbsp;" ;
		if($numResults == 0)
			$finalmess = "There were no results found" ;
			
		echo<<<ENERGIE
	parent.mainFrame.document.getElementById('totalresfound').innerHTML = $numResults ;
	parent.mainFrame.document.getElementById('errorbox').innerHTML = "$finalmess" ;
	if(parent.mainFrame.document.getElementById('totalpagefound'))
		parent.mainFrame.document.getElementById('totalpagefound').innerHTML = $totalPages ;
	if(parent.mainFrame.document.getElementById('totalpagefoundb'))
		parent.mainFrame.document.getElementById('totalpagefoundb').innerHTML = $totalPages ;
ENERGIE;
	}
		
echo<<<ENERGIE
  }
  </script>

 </head>
 <body bgcolor=WHITE>
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
   document.onload = top.window.location = "../logout.php";
  </script>
 </body>
</html>
ENERGIE;
}

?>
