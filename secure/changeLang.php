<?php
// $Id: changeLang.php 14869 2012-07-03 13:06:25Z fabaroa $

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../modules/modules.php';
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser() ) {
		//variables that may need to be translated
		 $tableTitle					= $trans['Change Language']; 
		 $selectLang					= $trans['Select Default Language']; // 
		 $changeLang					= $trans['Change Language']; 
		 $systemPreferences	 			= $trans['System Preferences'];
		 $default						= $trans['Default'];
echo<<<ENERGIE
<html>
 <head>
ENERGIE;
	$db_doc = getDbObject ('docutron');
	 $settings=new GblStt( $user->db_name, $db_doc );		//establish the system preferences object
	//change language was clicked to change the language settings
	if(isset($_POST['changeLang'])) {		
		$settings->set('i18n', $_POST['lang']);
		if(isset($_POST['langlogin']) && $_POST['langlogin'] == "on") {
			$settings->set('langlogin', "on") ;
		} else {
			$settings->set('langlogin', "off") ;
		}
		$message = "Language settings have been changed";
// Does anyone think this redirect should be done? I think it should stay
// here and inform the user of any change.
/*
echo<<<ENERGIE
<script>
			 document.onload = parent.mainFrame.window.location = "../modules/modulesWeb.php";
			 document.onload = parent.searchPanel.window.location = "leftAdmin.php";
			 document.onload = parent.topMenuFrame.window.location = "../energie/menuSlide_NewUI.php";
</script>
ENERGIE;
*/
	}	
echo<<<ENERGIE
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>$systemPreferences</title>
 </head>
 <body>
	<form name="preferences" method="POST" target = "mainFrame" action="changeLang.php">
  <center>
	 <table class="settings" width="566">
		<tr>
		 <td colspan="2" class="tableheads">$tableTitle</td>
		</tr>
		<tr>
		 <td class="admin-tbl" align="left">$selectLang:</td>
		 <td align = "center">	&nbsp;&nbsp	&nbsp;&nbsp;	&nbsp;&nbsp;	<select name="lang" >	
ENERGIE;

$db_raw = getDbObject('docutron');
$language = $settings->get('i18n' );
$colsArr= getTableColumnInfo ($db_raw, 'language');
for($i=0; $i<sizeof($colsArr); $i++) {
	if ($i>1) {
		$name = $colsArr[$i];
		
		if($colsArr[$i]==$language) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		echo "                  <option $selected value=\"{$colsArr[$i]}\">".$trans[strtolower($colsArr[$i])]."</option>\n";
	}
}
echo<<<ENERGIE
		</select>
		 			</td>
				</tr>
				<tr>
					<td class="admin-tbl">
						Display Language Choice on Login:
					</td>
					<td>
						<input type="checkbox" name="langlogin"
ENERGIE;
// check the language on login setting if it is on
$langset = $settings->get('langlogin' ) ;
if($langset == "on"){
	echo " checked" ;
}
echo<<<ENERGIE
>
					</td>
				</tr>	
				<tr>
		 			<td colspan="2">
ENERGIE;
	
	//display message if a change was successfully made
	if( isSet($message) ) {
		echo "<div class=\"error\">$message\n";
	} else {
		echo "<div>\n";
	}
echo<<<ENERGIE
					<input name="changeLang" type="submit" value="Save"></div>
					</td>
				</tr>
			</table>
  </center>
		</form>
	</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {	//log them out
	logUserOut();
}
?>
