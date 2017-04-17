<?php
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once ( '../DataObjects/DataObject.inc.php');

if($logged_in ==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin())
{
	$uname = '';
	if (isset ($_POST['user_name'])) {
		$uname = $_POST['user_name'];
	} elseif (isset ($_GET['uid'])) {
		if ($_GET['uid'] == '0') {
			$uname = 'All Read Only Users';
		} else {
			$DO_user = DataObject::factory ('users', $db_doc);
			$DO_user->get ($_GET['uid']);
			$uname = $DO_user->username;
		}
	}

	$systemPref    = $trans['System Preferences'];
	$selectSetts   = $trans['Select Settings'];
	$changeSett    = $trans['Change Setting'];
	$enable        = $trans['Enable'];
	$disable       = $trans['Disable'];
	$viewSettsCh   = $trans['View Settings Changed'];
	$viewSetts     = $trans['View Options'];	

	$settings = new GblStt( $user->db_name, $db_doc );
	if ($uname) {
		$userStt = new Usrsettings( $uname, $user->db_name );
	}
    $setView = $settings->get( 'order' );
    if($setView == null) // for updating old db versions
    {
        $settings->set( 'order', '1');
        $setView = $settings->get( 'order' );
    }
                                                                                                                             
    if(isset($_POST['changeView']))
    {
        $enabled = $_POST['enable'];
        $settings->set( 'order', $enabled);
        $setView = $settings->get( 'order' );
        $message = $viewSettsCh;
    }
	
	if( $setView == 0 )
		$disabled = "disabled";
	else {
		$disabled = '';
	}
	if( isset( $_GET['uid'] ) )
		$URL = "?uid=".$_GET['uid'];
	else
		$URL = '';
	echo<<<ENERGIE
<html>
<head>
 <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 <title>Viewing Restrictions</title>
</head>
<body>
<form name="movef" method="POST" action="viewingRestrictions.php$URL">
  <center>
   <table class="settings" width="350">
    <tr>
     <td colspan="3" class="tableheads">$viewSetts</td>
    </tr>
    <tr>
     <td class="admin-tbl">$selectSetts:</td>
ENERGIE;
    if($setView) {
        $status1 = "checked";
	$status2 = '';
    } else {
	$status1 = '';
        $status2 = "checked";
    }
echo<<<ENERGIE
     <td>
    $enable: <input type=radio value=1 $status1 name=enable>
     </td>
     <td>
    $disable: <input type=radio value=0 $status2 name=enable>
     </td>
    </tr>
    <tr>
     <td colspan="3">
ENERGIE;
                                                                                                                             
    //display message if a change was successfully made
    if( isSet($message) )
        echo "<div class=\"error\">$message\n";
    else
        echo "<div>\n";
                                                                                                                             
echo<<<ENERGIE
    <input name="changeView" type="submit" value=$changeSett"></div>
    </td>
    </tr>
   </table>
  </center>
</form>
<br>
ENERGIE;
$message = NULL;
//check set restrictions submission
if (isset($_POST['set_restrictions'])) {

	$a=0;

	//increment the access variable according to restrictions set
	if($_POST['printing']=="on")
		$a+=4;
	if($_POST['saving']=="on")
		$a+=8;
	if($_POST['emailing']=="on")
		$a+=1024;

	//set require alternatiff setting
	$require=$_POST['require'];
	$csv=$_POST['csv'];
	$iso=$_POST['iso'];
	$book=$_POST['book'];
	
	//add to system settings if for all read only users
	if($uname=="All Read Only Users") {
		$settings->set("roViewRestrict",$a);
		$settings->set("requireAlt",$require);
		$settings->set("csvRestrict",$csv);
		$settings->set("isoRestrict",$iso);
		$settings->set("bookmarkRestrict",$book);
		
	}
	else {	//add to user settings
		$userStt->set('viewRestrict', $a);
		$userStt->set('requireAlt', $require);
		$userStt->set('csvRestrict', $csv);
		$userStt->set('isoRestrict', $iso);
		$userStt->set("bookmarkRestrict",$book);
	}
	$message="Viewing Restrictions have been set";
	$userStt = new Usrsettings( $uname, $user->db_name );
}
	if( isSet( $_GET['uid'] )  && !isSet( $_POST['set_restrictions'] ) )
	{
		$u=$_GET['uid'];
		if($u>0) {
  			$res = getTableInfo($db_object,'access',array(),array('uid'=>(int)$u));
			$row=$res->fetchRow();
			$uname=$row['username'];
		}
	}
	else 
		$uname = "";
echo<<<ENERGIE
<form name="getUser">
  <center>
<table class="admin-tbl" width="350" border="2" cellpadding="1" cellspacing="2" align="center">
  <tr>
   <td colspan="2" class="tableheads">
    Viewing Restrictions
   </td>
  </tr>\n
  <tr>
    <td>Select User:</td>
    <td align="center">
      <select name="u_id" onchange="location=document.getUser.u_id[document.getUser.u_id.selectedIndex].value" $disabled>
ENERGIE;
	if(!isSet($_GET['uid'])) {
         echo "<option value=\"default\">Select User</option>\n";
	}

	if( isSet( $_GET['uid'] ) && $_GET['uid'] == 0 ) {
         echo "<option selected value=viewingRestrictions.php?uid=0>All Read-Only</option>\n";
	} else {
         echo "<option value=viewingRestrictions.php?uid=0>All Read-Only</option>\n";
		}
	$user->getUserSortInfo( $usrArr, $uidArr, "uid" );
	for($i=0;$i<sizeof($usrArr);$i++)
	{
		$tmp = $usrArr[$i];
		$id = $uidArr[$tmp];
		if( $tmp != $uname )	
			echo"<option value=viewingRestrictions.php?uid=$id>$tmp</option>\n";
		else
			echo"<option selected value=viewingRestrictions.php?uid=$id>$tmp</option>\n";
	}
	echo<<<ENERGIE
      </select>
     </td>
   </tr>
ENERGIE;
	if( $message != NULL )
	{
   		echo "<tr>\n";
     	echo "<td colspan=\"2\" align=\"center\">\n";
		echo "<div class=\"error\">$message</div>\n";
   		echo "</tr>\n"; 
	}
echo<<<ENERGIE
  </form>
ENERGIE;

//display other table if user has been selected
if(isset($_GET['uid']) && !$disabled ) {
	$printing_off = '';
	$saving_off = '';
	$emailing_off = '';
	$printing_on = '';
	$saving_on = '';
	$emailing_on = '';
	$require_on = '';
	$require_off = '';
	$csv_on = '';
	$csv_off = '';
	$iso_on = '';
	$iso_off = '';
	$book_on = '';
	$book_off = '';

	//echo "$_POST[users]";
	$u=$_GET['uid'];
	if($u>0) {
  		$res = getTableInfo($db_object,'access',array(),array('uid'=>(int)$u));
		$row=$res->fetchRow();
		$uname=$row['username'];

		 if(!($num = $userStt->get('viewRestrict'))) {
			$printing_off="checked";
			$saving_off="checked";
			$emailing_off="checked";
		}
		else { //figure out settings from number in value

			if($num>=1024) {
				$emailing_on="checked";
				$num-=1024;
			}
			else
				$emailing_off="checked";

			if($num>=8) {
				$saving_on="checked";
				$num-=8;
			}
			else
				$saving_off="checked";

			if($num>=4) {
				$printing_on="checked";
			}
			else
				$printing_off="checked";
		}
		//get require alternatiff restriction
		 if(!($req = $userStt->get('requireAlt'))) {
			$require_off="checked";
		} else {
			if($req=="off")
				$require_off="checked";
			else
				$require_on="checked";
		}
		//get exporting restrictions
		 if(!($req = $userStt->get('csvRestrict'))) {
			$csv_off="checked";
		} else {
			if($req=="off")
				$csv_off="checked";
			else
				$csv_on="checked";
		}
		if(!($req = $userStt->get('isoRestrict')))
			$iso_off="checked";
		else {
			if($req=="off")
				$iso_off="checked";
			else
				$iso_on="checked";
		}
		if(!($req = $userStt->get('bookmarkRestrict')))
			$book_off="checked";
		else {
			if($req=="off")
				$book_off="checked";
			else
				$book_on="checked";
		}
	}
	else {
		$uname="All Read Only Users";
		//get current settings from settings table
		 if(!($num = $settings->get('roViewRestrict'))) {
			$printing_off="checked";
			$saving_off="checked";
			$emailing_off="checked";
		}
		else { //figure out settings from number in settings
			if($num>=1024) {
				$emailing_on="checked";
				$num-=1024;
			}
			else
				$emailing_off="checked";

			if($num>=8) {
				$saving_on="checked";
				$num-=8;
			}
			else
				$saving_off="checked";

			if($num>=4) {
				$printing_on="checked";
			}
			else
				$printing_off="checked";
		}
		 if(!($req = $settings->get('requireAlt')))
			$require_off="checked";
		else {
			if($req=="off")
				$require_off="checked";
			else
				$require_on="checked";
		}
		//get exporting restrictions
		 if(!($req = $settings->get('csvRestrict')))			
			$csv_off="checked";
		else {
			if($req=="off")
				$csv_off="checked";
			else
				$csv_on="checked";
		}
		 if(!($req = $settings->get('isoRestrict')))			
			$iso_off="checked";
		else {
			if($req=="off")
				$iso_off="checked";
			else
				$iso_on="checked";
		}
		 if(!($req = $settings->get('bookmarkRestrict')))			
			$book_off="checked";
		else {
			if($req=="off")
				$book_off="checked";
			else
				$book_on="checked";
		}
	}
	echo<<<ENERGIE
<form name="getRestrictions" method="POST" action="viewingRestrictions.php">
  <tr>
    <td align="left">Export Results to CSV</td>
    <td align="center">
       <input type="radio" name="csv" value="off" $csv_off>$disable
       &nbsp;
       <input type="radio" name="csv" value="on" $csv_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Export Results to ISO</td>
    <td align="center">
       <input type="radio" name="iso" value="off" $iso_off>$disable
       &nbsp;
       <input type="radio" name="iso" value="on" $iso_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Create Bookmark Searches</td>
    <td align="center">
       <input type="radio" name="book" value="off" $book_off>$disable
       &nbsp;
       <input type="radio" name="book" value="on" $book_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Require Alternatiff</td>
    <td align="center">
       <input type="radio" name="require" value="off" $require_off>$disable
       &nbsp;
       <input type="radio" name="require" value="on" $require_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Printing</td>
    <td align="center">
       <input type="radio" name="printing" value="off" $printing_off>$disable
       &nbsp;
       <input type="radio" name="printing" value="on" $printing_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Saving</td>
    <td align="center">
       <input type="radio" name="saving" value="off" $saving_off>$disable
       &nbsp;
       <input type="radio" name="saving" value="on" $saving_on>$enable
    </td>
  </tr>
  <tr>
    <td align="left">Emailing</td>
    <td align="center">
       <input type="radio" name="emailing" value="off" $emailing_off>$disable
       &nbsp;
       <input type="radio" name="emailing" value="on" $emailing_on>$enable
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right"><input type="submit" name="set_restrictions" value="Set Restrictions"/></td>
  </tr>
<input type="hidden" name="user_name" value="$uname"/>
</form>
ENERGIE;
}
	echo "</table>";
 	echo " </center>";
	echo "</body>";
	echo "</html>";

	setSessionUser($user);
} else {
	logUserOut();
}
?>
