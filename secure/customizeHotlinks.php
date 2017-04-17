<?php
// $Id: customizeHotlinks.php 14297 2011-03-21 17:35:34Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
    //variables to translate
    $customize    = $trans['Customize Hotlinks'];
    $hlschanged   = $trans['Hotlink Settings Have Changed'];
    $selectUser   = $trans['Select User']; 
    $changeSett   = $trans['Change Setting'];
    $enable       = $trans['Enable'];
    $disable      = $trans['Disable'];
	$message = '';
	$selectedUser = '';
	$enabledLinks = array ();
	$disabledLinks = array ();

    $user->setSecurity();
    $db_object = $user->getDbObject();

	// Fill usrArr and uidArr with all needed information
	$user->getUserSortInfo($usrArr, $uidArr, 'uid') ;
	$uid = '';
	if (isset($_GET['u']) and $_GET['u']) {
		$uid = $_GET['u'];
	}
    //check post to see if this page posted from the submit button
    if(isset($_POST['Update'])) {
        //get the username of the user we just changed settings for
	    $pastUser = $_POST['pastUser'];
        $settings = new Usrsettings( $pastUser, $user->db_name  );
		if (isset ($_POST['enabled'])) {
			$settings->set('enabledLinks',implode("!_DELIMITER_!", $_POST['enabled']));
		} else {
			$settings->removeKey('enabledLinks');
		}

		if (isset($_POST['disabled'])) {
			$settings->set('disabledLinks',implode("!_DELIMITER_!", $_POST['disabled']));
		} else {
			$settings->removeKey('disabledLinks');
		}
		
        $enabled = "none";
        if($enabledLinks != null) {
	    	$enabled = implode(",", $_POST['enabled']);
		}
		$user->audit("Hotlinks Settings Changed", $user->username." changed settings of $pastUser to $enabled");
        $message = $hlschanged;
    }
echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
 <head>
  <link rel="stylesheet" type="text/css" href="../lib/style.css" />
  <title>$customize</title>
  <script type="text/javascript">
    //-------------------------------
    // move the selected options from
    // the DL to the enabled list
    //-------------------------------
    function enableLink() {
        for( var i=0; i < document.hotlinksf.elements["disabled[]"].length; i++ ) {
            if(document.hotlinksf.elements["disabled[]"].options[i].selected) {
                document.hotlinksf.elements["disabled[]"].options[i].selected = false;
                document.hotlinksf.elements["enabled[]"].appendChild(document.hotlinksf.elements["disabled[]"].options[i], 0);
                i--;
            }
        }
    }

    //--------------------------------
    // moves the selected options
    // from the enabled list to the DL
    //--------------------------------
    function disableLink() {
        for( var i=0; i < document.hotlinksf.elements["enabled[]"].length; i++ ) {
            if(document.hotlinksf.elements["enabled[]"].options[i].selected) {
                document.hotlinksf.elements["enabled[]"].options[i].selected = false;
                document.hotlinksf.elements["disabled[]"].appendChild(document.hotlinksf.elements["enabled[]"].options[i]);
                i--;
            }
        }
    }

    //--------------------------------
    // shuffles the selected enabled
    // links up one spot
    //--------------------------------
    function shuffleUp() {
        //this variable keeps track of whether we've encountered
        //an unselected entry yet; we don't shuffle up unless
        //there was one previous unselected thing to shuffle through
        var noShuffle = true;

        for( var i=0; i < document.hotlinksf.elements["enabled[]"].length; i++ ) {
            if( document.hotlinksf.elements["enabled[]"].options[i].selected ) {
                if( noShuffle==false ) {
                    document.hotlinksf.elements["enabled[]"].insertBefore(document.hotlinksf.elements["enabled[]"].options[i],
                                                                     document.hotlinksf.elements["enabled[]"].options[i-1] );
                }
            } else {
                noShuffle = false;
            }
        }
    }

    //--------------------------------
    // shuffles the selected enabled
    // links down one spot
    //--------------------------------
    function shuffleDown() {
        //this variable keeps track of whether we've encountered
        //an unselected entry yet; we don't shuffle up unless
        //there was one previous unselected thing to shuffle through
        var noShuffle = true;

        for( var i=document.hotlinksf.elements["enabled[]"].length-1; i > -1; i-- ) {
            if( document.hotlinksf.elements["enabled[]"].options[i].selected ) {
                if( noShuffle==false ) {
                    document.hotlinksf.elements["enabled[]"].insertBefore(document.hotlinksf.elements["enabled[]"].options[i],
                                                                 document.hotlinksf.elements["enabled[]"].options[i+2] );
                }
            } else {
                noShuffle = false;
            }
        }
    }

    //----------------------------------------
    // selects all elements of all lists, 
    // allowing them to be passed in the post
    // (otherwise only the currently selected
    // elements are passed)
    //----------------------------------------
    function selectAll() {
        for( var i=0; i < document.hotlinksf.elements["enabled[]"].length; i++ )
            document.hotlinksf.elements["enabled[]"].options[i].selected = true;

        for( var i=0; i < document.hotlinksf.elements["disabled[]"].length; i++ )
            document.hotlinksf.elements["disabled[]"].options[i].selected = true;            
    }

  </script>
 </head>
 <body class="centered">
 <div class="mainDiv" style="width: 500px">
 <div class="mainTitle">
 <span>$customize</span>
 </div>
	<form name="userf" method="post" action="customizeHotlinks.php">
   <table class="inputTable">
	<tr>
 		<td class="label">
		<label for="usersSel">$selectUser</label>
		</td>
 		<td>
		<select name="users" id="usersSel" onchange="location=document.userf.users[document.userf.users.selectedIndex].value">
ENERGIE;
    //if no user has been selected yet, show "select user"
    if(!$uid)
    echo "\n          <option selected value=\"default\">$selectUser</option>\n";
	for($i=0;$i<sizeof($usrArr);$i++) {  
		$uname = $usrArr[$i];
		$id = $uidArr[$uname];
		// If you are greater than the given user, you can edit them.  Can't edit your self
		if( $user->greaterThanUser($uname) && $user->username != $uname ){
            //make sure the user you selected is selected now
            if(strcmp($uid, $id)==0) {
                echo "<option selected=\"selected\" value=\"customizeHotlinks.php?u=$id\">";
				echo "$uname</option>\n";
				$selectedUser = $uname;
                $settings = new Usrsettings( $uname, $user->db_name  );

                //These need to be made into arrays
                $tempLinks  = $settings->get( 'enabledLinks' );
                if($tempLinks != null) {
                    $enabledLinks = explode( "!_DELIMITER_!", $tempLinks );
				}
                $tempLinks = $settings->get( 'disabledLinks' );
                if($tempLinks != NULL) {
                    $disabledLinks = explode( "!_DELIMITER_!", $tempLinks );
				}
            } else { 
                echo "<option value=\"customizeHotlinks.php?u=$id\">";
				echo "$uname</option>\n";
            }
        }
    }
echo<<<ENERGIE
         </select>
		</td>
	</tr>
	</table>
	  </form>
ENERGIE;
	if( $message != null ) {
        echo "<div style=\"margin-left: auto; margin-right: auto\" class=\"error\">$message for User $pastUser</div>";
    }

if($uid && strcmp($selectedUser, "admin") ) {
    //check the user settings
    if( !$enabledLinks && !$disabledLinks ) {
        //if not present, add the three defaults as enabled
        $enabledLinks = array( 'todo', 'inbox', 'home', 'administration' );
        $disabledLinks = array();
    }
echo<<<ENERGIE
    <!-- This is the form for selecting user hotlinks -->
    <form name="hotlinksf" method="post" action="customizeHotlinks.php">
<table>
    <tr>
     <td align="center">
      <table>
       <tr>
        <td align="center" style="border:0px">
         Hide
        </td>
        <td>
        </td>
        <td align="center" style="border:0px">
         Display
        </td>
	   </tr>
       <tr>
        <td width="260" align="center" style="border:0px">
         <select name="disabled[]" multiple="multiple" size="8" style="width:200px">
ENERGIE;
/*-----------------------------------------
 * Put all the disabled hotlinks here.
 *---------------------------------------*/
    echo "\n";
    foreach( $disabledLinks as $dl ) {
        echo "          <option value=\"$dl\">$dl</option>\n";
    }
echo<<<ENERGIE
         </select>
        </td>
        <td align="center" style="border:0px">
         <table>
          <tr>
           <td align="center" style="border:0px">
            <button type="button" onclick="shuffleUp()" style="width:60px">Move<br/>Up</button>
           </td>
          </tr>
          <tr>
           <td align="center" style="border:0px">
            <button type="button" onclick="enableLink()" style="width:60px">---&gt;</button>
           </td>
          </tr>
          <tr>
           <td align="center" style="border:0px">
            <button type="button" onclick="disableLink()" style="width:60px">&lt;---</button>
           </td>
          </tr>
          <tr>
           <td align="center" style="border:0px">
            <button type="button" onclick="shuffleDown()" style="width:60px">Move<br/>Down</button>
           </td>
          </tr>
         </table>
        </td>
        <td width="260" align="center" style="border:0px">
         <select name="enabled[]" multiple="multiple" size="8" style="width:200px">
ENERGIE;
/*------------------------------------
 * Put all enabled hotlinks here.
 *----------------------------------*/
    echo "\n";
    foreach( $enabledLinks as $el ) {
        echo "          <option value=\"$el\">$el</option>\n";
    }
echo<<<ENERGIE
         </select>
        </td>
       </tr>
      </table>
     </td>
    </tr>
    <tr>
     <td colspan="3"><center>
      <input type="submit" name="Update" value="Save" onclick="selectAll()"/>
     </center></td>
    </tr>
    <!--  WE INCLUDE THIS SO WE CAN GET THE USER WHO WAS CHANGED -->
   </table>
	<div style="display: none">
    <input type="hidden" name="pastUser" value="$selectedUser" />
	</div>
    </form>
ENERGIE;
}
echo<<<ENERGIE
</div>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
