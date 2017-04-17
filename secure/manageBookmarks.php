<?php
// $Id: manageBookmarks.php 14220 2011-01-04 16:21:50Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';

//check for user
if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$user->setSecurity();
	$db_object = $user->getDbObject();
	$settings = new Usrsettings($user->username, $user->db_name);
	$bookmarks = unserialize(base64_decode($settings->get('bookmarks')));

	// Looking for a Rename submittal
	if (isset($_GET['submittwo']) and $_GET['submittwo'] == "Rename") {
		$booknum = $_GET['booknum'];
		$bookmarks[$booknum]['name'] = $_GET['newname']; // put newname in
		// now put new info in database
		$tmpbook = base64_encode(serialize($bookmarks));
		$settings->set('bookmarks', $tmpbook, $user->db_name);
	}
	echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <title>Manage Bookmarks</title>
 </head>
  <body class="centered">
  <div class="mainDiv" style="width:500px">
   <div class="mainTitle">
    <span>Manage Bookmarks</span>
   </div>
   <div id="mainForDiv">
    <table class="settings" width="100%">
     <tr>
      <td colspan="3" align="center">
ENERGIE;

	// Check if they chose a bookmark to rename
	if (isset($_GET['submit']) and $_GET['submit'] == "Rename" and isset($_GET['booknum']) and $_GET['booknum'] >= 0) {
		$booknum = $_GET['booknum'];
		$mark = $bookmarks[$booknum];
		$thename = stripslashes($mark['name']);
		echo<<<RENAMEFORM
	<div>Renaming: <b>$thename</b></div><br>
	<form action="manageBookmarks.php">
	  Edit Bookmark Name: <input type="text" name="newname" value="$thename">
	  <input type="hidden" name="booknum" value="$booknum">
	  <input type="submit" name="submittwo" value="Save">
	</form>
	<br>
RENAMEFORM;

	}
	// Make sure there are bookmarks to manage, otherwise say there are none
	else
		if (sizeof($bookmarks) > 0) {
			if (isset ($_GET['submit']) and $_GET['submit'] == "Delete" and isset($_GET['booknum']) and $_GET['booknum'] >= 0) {
				$booknum = $_GET['booknum'];
				if (isset ($bookmarks[$booknum])) {
					$mark = $bookmarks[$booknum];
				} else {
					$mark = array ('name' => '');
				}
				echo "<div class=\"error\">Bookmark <b>".stripslashes($mark['name'])."</b> has been deleted</div>";

				// We make a new copy of bookmarks array without the deleted
				// then reset it.
				$shift = 0; // this is for when we skip over the deleted one
				for ($i = 1; isset ($bookmarks[$i]) && $mark = $bookmarks[$i]; $i ++) {
					if ($i == $booknum) { // the one we want to delete, skip
						$shift = 1;
					} else {
						$newbookmarks[$i - $shift] = $mark;
					}
				}

				// Reset to have new list
				$bookmarks = $newbookmarks;
				$newbookmarks = base64_encode(serialize($bookmarks));
				$settings->set('bookmarks', $newbookmarks, $user->db_name);
			}
			//print_r($bookmarks) ; // ERROR
			echo<<<ENERGIE
	  <form action="manageBookmarks.php">
      <table>
       <tr>
        <td>
         <select name="booknum" multiple="true" size="10" style="width:200">
ENERGIE;

			for ($i = 0; $i < sizeof($bookmarks); $i ++)
				echo "\t\t<option value=\"$i\">".stripslashes($bookmarks[$i]['name'])."</option>";

			echo<<<ENERGIE
         </select>
        </td>
        <td style="border:0px">
         <table>
          <tr>
           <td align="center" style="border:0px">
            <input type="submit" name="submit" value="Rename">
           </td>
          </tr>
          <tr>
           <td align="center" style="border:0px">
            <input type="submit" name="submit" value="Delete">
           </td>
          </tr>
         </table>
		 </form>
ENERGIE;
		} else { // Say there are no bookmarks to manage
			echo "<br><div class=\"error\">There are currently no bookmarks to manage</div><br>";
		}

	echo<<<ENERGIE
        </td>
       </tr>
      </table>
     </td>
    </tr>
   </table>
   </div>
  </center>
 </body>
ENERGIE;
}
//redirect to login
else {
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

