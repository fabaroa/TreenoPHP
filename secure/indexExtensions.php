<?php
// $Id: indexExtensions.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../settings/settings.php';
include_once '../modules/modules.php';

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin())
{
   //variables that may have to be translated
   $tableTitle        = $trans['Index File Extensions'];
   $selectExtension   = $trans['Select an Extension'];   
   $addExtLabel       = $trans['Add File Extension'];
   $rmExtension       = $trans['Remove Extension'];
   $addExtButton      = $trans['Add Extension'];
   $fileExtMess       = $trans['File Extension'];
   $extMessage        = $trans['New Extension Message'];
   $rmExtMess         = $trans['Remove Extension Message'];
   $wasRemoved        = $trans['Extension Removed Message'];
   $specifyExtension  = $trans['Please Specify Extension'];
   $duplicateExtension= $trans['Duplicate Extension'];
   $spacesError       = $trans['Spaces Error'];

   $db_doc = getDbObject ('docutron');
   $settings=new GblStt( $user->db_name, $db_doc );    //establish the system preferences object

   //indexing allowable file extensions settings
   $extensions=$settings->get("indexing_file_filter" );

   if( !empty($_POST['addExtSubmit']) || !empty($_POST['addExt'])) //user is adding an extension
   {
	  $addExt = trim($_POST['addExt']);
      $splitArr = explode("\.", $addExt);  //cut off a '.' if present; if it's
      $cutExt = $splitArr[sizeOf($splitArr)-1];   //after string (jpg.) then error, and
      if(strcmp($cutExt,"")==0)                   //only last substring taken (a.b.c->c)
         $message="$specifyExtension";
      else if( sizeOf(explode(" ", $cutExt)) > 1 ||
               sizeOf(explode("\t", $cutExt)) > 1)
         $message="$spacesError";
      else
      {
         $lowExt = strtolower($cutExt);
         foreach ($extensions as $opt)
         {
	    if( strcmp(strtolower($opt),$lowExt)==0 )
               $message="$duplicateExtension";
         }
         if(!isSet($message))  //if message isn't set, we're good to add
         {
            $settings->addExtension($cutExt);
            $message="$fileExtMess \"$cutExt\" $extMessage"; 
         }
      }
   }
   else if(isset($_POST['removeExtSubmit']))	//user is removing an extension
   {
	if(strcmp($_POST['extensions'],$selectExtension)==0 || $_POST['extensions']=="")
		$message="$rmExtMess";
	else
	{
		$settings->removeExtension($_POST['extensions']);
		$message="$fileExtMess \"{$_POST['extensions']}\" $wasRemoved";
	}
   }

echo<<<ENERGIE
<html>
 <head>
	<script>
	function checkEnter(event)
	{
		var code = 0;
		if(document.all)
			code = event.keyCode;
		else
			code = event.which;
		
		if(code == 13)
		{
			document.preferences2.action = "indexExtensions.php";
			document.preferences2.submit();
		}
	}
  </script>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>System Preferences</title>
 </head>
 <body>

ENERGIE;

//refresh here so updates are reflected
$extensions=$settings->get("indexing_file_filter" );

echo<<<ENERGIE
  <form name="preferences2" method="POST" action="indexExtensions.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="2" class="tableheads">$tableTitle</td>
    </tr>
	<tr>
     <td class="admin-tbl">
	  Extension
      <input name="addExt" onKeyPress="checkEnter(event);">
     </td> 
     <td>
	  <input name="addExtSubmit" type="submit" value="$addExtButton">
     </td>
    </tr>
ENERGIE;

	if( (isSet($_POST['addExtSubmit']) || isSet($_POST['addExt']))
			&& $message != null )
	{
echo<<<ENERGIE
		<tr>
		 <td colspan='2'>
			<div class="error">$message</div>
		 </td>
		</tr>
ENERGIE;
	}

echo<<<ENERGIE
    </table>
  </center>
    </form>




  <form name="preferences" method="POST" action="indexExtensions.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="2" class="tableheads">$selectExtension</td>
    </tr>
    <tr>
     <td class="admin-tbl">
	  <select name="extensions">
       <option selected value="">$selectExtension</option>

ENERGIE;

//display each extension as an option in the drop-down menu
foreach ($extensions as $opt)
{
	if( $opt!="" )
	echo "       <option value=\"$opt\">$opt</option>\n";
}

echo<<<ENERGIE
      </select>
     </td>
	  <td>
      <input name="removeExtSubmit" type="submit" value="$rmExtension">
     </td>
	 </tr>
ENERGIE;

	if( isset($_POST['removeExtSubmit']) && $message != null )
	{
echo<<<ENERGIE
		<tr>
		 <td colspan='2'>
			<div class="error">$message</div>
		 </td>
		</tr>
ENERGIE;
	}

echo<<<ENERGIE
	</table>
  </center>
    </form>

	
   </body>
   </html>
ENERGIE;
	setSessionUser($user);
} else {  //log them out
	logUserOut();
}
?>
