<?php 
include_once '../check_login.php';
include_once ('../classuser.inc');

include_once '../lib/cabinets.php';//need for function readyToIndex
include_once '../lib/filename.php'; //need for function countFiles

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 )
{
	$settings = new GblStt ($user->db_name, $db_doc);

	$cab = $_GET['cab'];
	$ID = $_GET['ID'];
	$passString = $_GET['passString']; //passed from getImage.php, could be null
	
	$foldersLeft	= $trans['foldersLeft'];
	$of				= $trans['of'];
	$Submit			= $trans['Submit'];
	$Delete			= $trans['Delete'];
	$Skip			= $trans['Skip'];
	$from			= $trans['from'];

//	$key = "indexing_".$cab;
//	$table_name = $settings->get( $key, $user->db_name );

	$indiceNames = getCabinetInfo( $db_object, $cab );
	if( $indiceNames[0] == "social_security_number"  || $indiceNames[0] == 'ssn')
    		$isSSN = true;
	$fname = $indiceNames[0];	
	$name = str_replace("_", " ", $fname);


/*	// used for scrolling PART 1
	$query = getTableInfo($db_object,$cab."_indexing_table",array(),array('id'=>(int)$ID));
	$myrow = $query->fetchRow();
	$path = $myrow['path'];

	$path = substr($path, 0, strrpos($path, " ") );
	$path = str_replace(' ', '/', $path);
	$cabEnd = strpos($path, $cab) + strlen($cab) + 1;
	$endSlash = strpos($path, '/', $cabEnd);
	if($endSlash === false) {
		$endSlash = strlen($path);
	}
	$path = substr($path, 0, $endSlash); 
	
   
//   $path = substr($path, 0, strrpos($path, '/'));
   $path = $DEFS['DATA_DIR']."/".$path;
*/	
	//returns an array of sorted filenames
	//corresponding page-1 == element in array 
	$filesArray = $_SESSION['indexFileArray'];

 	$num_files = count ($filesArray); 

	//returns a pool of characters, numbers, and special characters
	$pool = $user->characters(4); 

 echo<<<ENERGIE
 <HTML><HEAD>
 <script src="../lib/prototype.js"></script> 
 <script src="../lib/windowTitle.js"></script> 
<script language="javascript">
var formToSubmit = 'submitBtn';
setTitle( 4, "$cab", "");
/* the mask script was produced by mordechai Sandhaus - 52action.com,
 and is copyrighted . If you like this script, we encourage you to use it,
 provided that  include this note, and link to 52action.com. */
 
/* script explanation for dummies */

/* NOTE: THE FIRST 2 ARGUMENTS SHOULD ALWAYS REMAIN THE SAME - this.value, this */
// the first argument in the function, accepts the "value" of the textbox to be masked
// the second argument is the name of the textbox 
	//although the first 2 could have been done in 1 argument I did it in 2
	// to make it easier to understand

//the third argument holds the locations of the separator,
// each location should be separated by a comma - (going from lower numbers to higher)

//the fourth holds the delimiter (or separator) character.

/* nothing in the function should be edited,
	to change a delimiter or character location,
	change it in the calling script
	- the following code should be inserted into the field(s) to be masked - */
	
		/*replace 'location1,location2' with the locations where you want the delimiter
			replace the 'delimiter' with the separating character you would like */
	// javascript:return mask(this.value,this,'location1,location2','delimiter')
	
	//-there is no limit to the amount of delimiters you can have added

function mask(str,textbox,loc,delim, evt){
	var locs = loc.split(',');
	var evt = (evt) ? evt : event;
  	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	if(charCode == 8)
		return true;  	

	for (var i = 0; i <= locs.length; i++){
		for (var k = 0; k <= str.length; k++){
		 if (k == locs[i]){
		  if (str.substring(k, k+1) != delim){
		    str = str.substring(0,k) + delim + str.substring(k,str.length);
		  }
		 }
		}
	 }
	textbox.value = str
}


</script>
<script>
	function setsFocus() {
		window.focus();
		document.indexing.banner.focus();
	}

	function passIndex(cab,ID,page,oldPage) 
	  {
		var sumVar;
		var passString = "&"; //string that is added to onload statement
		var verPage;

		if(page < 1)
			verPage = 1;
		else if(page > $num_files)
			verPage = $num_files;
		else if( (page > 0) && (page <= $num_files) )
			verPage = page;
		else 
			verPage = oldPage;\n

		sumVar = document.indexing.banner.value;
		var urlStr = '../secure/getImage.php?cab='+cab+'&type=auto_complete_indexing' + '&ID='+ID+'&page='+verPage+'&passString='+sumVar;
		document.onload = parent.IndexMainFrame.window.location=urlStr;
	  }

 </script>
 <script type="text/javascript" src="../energie/func.js"></script> 
 </HEAD>
ENERGIE;
	
	$setScroll = $settings->get( 'scroll' );
	if($setScroll == null) // for updating old db versions 
	{
		$settings->set( 'scroll', '1');
		$setScroll = $settings->get( 'scroll' );
	} 
		
	if($setScroll)
	{		
		if($_GET['page'])
			$page = $_GET['page'];
		else
			$page = 1;
	
		//this is for $previous which passes the previous page
		if($page == 1)
			$previous = 1;
		else
			$previous = $page-1;

		//this checks if $next has reached the last page
		if($page == $num_files)
			$next = $num_files;
		else
			$next = $page+1;

		$fileName = $filesArray[$page-1]; //returns the fileName at the page number
		$fileName = substr($fileName, (strpos($fileName, $cab) + strlen($cab) + 1));
	}	
	//end of scrolling PART 1 

if ($isSSN) {
  echo "<BODY onLoad=setsFocus();>\n";
  echo "<form name=\"indexing\" id=\"idxForm\" method=\"POST\"";
  echo " target=bottomFrame action=\"dblookup.php?cab=$cab&ID=$ID&name=$fname&page=$page\">\n";
  echo "<table>\n";
  echo "<tr>\n";
  echo "<td>$name:&nbsp;&nbsp;</td>\n";
  echo "<td><input type=\"textfield\" id=\"bannerField\" value=\"$passString\"";
  echo " onkeypress=\"return inputFilter(event);\" ";
  echo " onKeyUp=\"javascript:return mask(this.value,this,'3,6','-', event);\"";
  echo " onBlur=\"javascript:return mask(this.value,this,'3,6','-', event);\" ";
  echo "name=\"banner\"></td>\n";
  echo "<input type=\"hidden\" name=\"blanker\" value=\"1\">\n"; //always send value of one to show form has been submitted
  echo "<td>&nbsp;<input type=\"submit\" name=\"Submit\" value=\"$Submit\"></td>\n";
  echo "<td>&nbsp;<input type=\"submit\" name=\"Submit\" value=\"$Delete\"></td>\n";
  echo "<td>&nbsp;<input type=\"button\" value=\"$Skip\"";
  echo " onclick=\"javascript:top.mainFrame.IndexMainFrame.window.location.href='../secure/getImage.php?cab=$cab&type=auto_complete_indexing';\"";
  echo " maxlength=\"11\"></td>\n";
//  echo "<td>$count $foldersLeft<BR>";
  $whereArr = array('finished<total','flag=0','upforindexing=0');
  $count = getTableInfo($db_object,$cab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');
  echo "<td>\n$count $foldersLeft<br>\n";
 // echo "<td>&nbsp;<BR>";
  echo "Now viewing $fileName</td>\n";
  
  echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";

  //Beginning of scroll PART 2
	if($setScroll)
	{
		//for arrows to flip through tif images
		echo "\n\n";	
		echo "<table width=\"25%\" class=\"tealbg\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "<tr>\n";
		//button to go to beginning
		echo "    <td width=\"15%\" align=\"right\">";
		//parent.IndexMainFrame.window.location='getImage.php?cab=$cab&ID=$ID&page=1';
		echo "<a href=\"#\" onClick=\"javascript:passIndex('$cab','$ID','1','$page');\">";
		echo "<img src=\"../energie/images/begin_button.gif\" border=\"0\" ></a></td>\n";
		//button for previous image
		echo "    <td width=\"2%\" align=\"center\">";
		echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$previous','$page');\">";
		echo "<img src=\"../energie/images/back_button.gif\" border=\"0\" ></a></td>\n";
		echo "<td noWrap=\"yes\" align=\"center\">\n";
		echo "&nbsp;<input name=\"textfield\" value=\"";
		echo $page;
		echo "\" type=\"text\" size=\"3\" onkeypress=\"if(event.keyCode == 13){return passIndex('$cab','$ID',this.value,'$page');}\">&nbsp;\n";
		echo "</td><td noWrap=\"yes\" width=\"15%\" align=\"center\" class=\"lnk_black\">\n";
		echo " ".$of." ".$num_files."&nbsp;";
		echo "</td>\n";
		//button for next page
		echo "    <td width=\"2%\" align=\"center\">";
		echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$next','$page');\">";
		echo "<img src=\"../energie/images/next_button.gif\" border=\"0\" ></a></td>\n";
		//button for last image
		echo "    <td width=\"5%\" align=\"left\"><a href=\"#\"";
		echo "  onClick=\"javascript:passIndex('$cab','$ID','$num_files','$page');\">";
		echo "<img src=\"../energie/images/end_button.gif\" border=\"0\" ></a></td>\n";
		echo "	</tr>\n</table>\n";
	} //End of scrolling PART2

  echo " </BODY>\n";
  echo "</HTML>\n";

    } 
    else 
    {
echo<<<ENERGIE
 <BODY onLoad=setsFocus();>
 <form name="indexing" id="idxForm" method="POST" target=bottomFrame action="dblookup.php?cab=$cab&ID=$ID&name=$fname&page=$page">
 <table>
 <tr>
  <td>$name:</td>
  <td>&nbsp;&nbsp;<input type="textfield" id="bannerField" onkeypress="return inputFilter(event);" name="banner" value="$passString"></td>
  <input type="hidden" name="blanker" value="1">
  <td>&nbsp;<input type="submit" name="Submit" id="submitBtn" value="$Submit"></td>
  <td>&nbsp;<input type="submit" name="Submit" value="$Delete"></td>
  <td>&nbsp;<input type="button" value="$Skip" onclick="javascript:top.mainFrame.window.location='../secure/indexing.php?name=$cab&type=auto_complete_indexing';"></td>
  <td>&nbsp;<BR>
	Now Viewing $fileName</td>\n
 </tr>
 </table>
 </form>

ENERGIE;
		
	//Beginning of scroll PART 3
	if($setScroll)
	{
		//for arrows to flip through tif images
		echo "\n\n";	
		echo "<table width=\"25%\" class=\"tealbg\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "<tr>\n";
		//button to go to beginning
		echo "    <td width=\"15%\" align=\"right\">";
		echo "<a href=\"#\" onClick=\"javascript:passIndex('$cab','$ID','1','$page');\">";
		echo "<img src=\"../energie/images/begin_button.gif\" border=\"0\" ></a></td>\n";
		//button for previous image
		echo "    <td width=\"2%\" align=\"center\">";
		echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$previous','$page');\">";
		echo "<img src=\"../energie/images/back_button.gif\" border=\"0\" ></a></td>\n";
		echo "<td noWrap=\"yes\" align=\"center\">\n";
		echo "&nbsp;<input name=\"textfield\" value=\"";
		echo $page;
		echo "\" type=\"text\" size=\"3\" onkeypress=\"if(event.keyCode == 13){return passIndex('$cab','$ID',this.value,'$page');}\">&nbsp;\n";
		echo "</td><td noWrap=\"yes\" width=\"15%\" align=\"center\" class=\"lnk_black\">\n";
		echo " ".$of." ".$num_files."&nbsp;";
		echo "</td>\n";
		//button for next page
		echo "    <td width=\"2%\" align=\"center\">";
		echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$next','$page');\">";
		echo "<img src=\"../energie/images/next_button.gif\" border=\"0\" ></a></td>\n";
		//button for last image
		echo "    <td width=\"5%\" align=\"left\"><a href=\"#\"";
		echo "  onClick=\"javascript:passIndex('$cab','$ID','$num_files','$page');\">";
		echo "<img src=\"../energie/images/end_button.gif\" border=\"0\" ></a></td>\n";
		echo "	</tr>\n</table>\n";
	} //End of scroll code

echo<<<ENERGIE
 </BODY>
 </HTML>
ENERGIE;
	}

	setSessionUser($user);

} else { //we want to log them out

	echo <<<ENERGIE
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
