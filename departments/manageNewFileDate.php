<?php
/**
 * User: Enosis
 * Date: 4/19/2016
 * Time: 3:44 PM
 */

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';

if($logged_in == 1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ) {
    $message = getValueByKey( $_GET, 'message');
    $department = getValueByKey( $_GET, 'department');

    $arbList = getLicensesInfo( $db_doc, 'real_department', 'arb_department', 1 );
    uasort( $arbList, "strnatcasecmp" );
    $deptList = array_keys( $arbList );

    if( isset( $_POST['creationDateSettings'] ) ){
        $creationDateSetting = $_POST['creationDateSettings'];
        $department = $_POST['DepartmentList'];
        setSettingsForCreationDate( $department, $db_doc, $creationDateSetting );
    }

    showHtmlMarkup();

    setSessionUser($user);
} else {
    logUserOut();
}

function getValueByKey( $arr, $key ){
    if ( isset ($arr[$key]) ) {
        $val = $arr[$key];
    } else {
        $val = '';
    }
    return $val;
}

function setSettingsForCreationDate($selectedDept, $db_doc, $creationDateSetting){
    $CreationDateSettingsMsg = "Settings Successfully Changed";

    $settings = new GblStt( $selectedDept, $db_doc );
    $settings->set( 'newFileCreationDate',  $creationDateSetting );
    echo<<<ENERGIE
<script>
	onload = parent.mainFrame.window.location = "manageNewFileDate.php?message=$CreationDateSettingsMsg";
</script>
ENERGIE;
}

function showHTMLMarkup() {
    global $user;
    global $db_doc;
    global $message;
    global $deptList;
    global $arbList;
    global $department;
    echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script>
   function getSelected() {
		val = document.manageNewFileDate.DepartmentList[document.manageNewFileDate.DepartmentList.selectedIndex].value;
		if( val != "default") {
			onload = parent.mainFrame.window.location = 'manageNewFileDate.php?department='+val;
		}
		else {
			onload = parent.mainFrame.window.location = 'manageNewFileDate.php';
		}
   }
  </script>
  <style>
    .margin
    {
          margin:5px;
    }
  </style>
 </head>
<body class="centered">
  <form name="manageNewFileDate" method="POST" action="manageNewFileDate.php">
  <div class="mainDiv" style="width:300px">
  <div class="mainTitle"><span>Manage Creation Date for New File</span></div>
    <div class="margin">
     <select name="DepartmentList" onchange="getSelected()">
      <option value="default">Choose Department</option>
ENERGIE;
    for($i=0;$i<sizeof($deptList);$i++) {
        $tmp = $deptList[$i];
        $var = $arbList[$tmp];
        if( $user->isUserDepAdmin( $user->username, $tmp ) ) {
            if($tmp != $department )
                echo"\n           <option value=\"$tmp\">$var</option>\n";
            else
                echo"\n           <option selected value=\"$tmp\">$var</option>\n";
        }
    }
    echo<<<ENERGIE
      </select>
     </div>
ENERGIE;
    if($department) {
        $selectedOption = "currentDate";

        $gblSettings = new GblStt($department, $db_doc);
        $option = $gblSettings->get('newFileCreationDate');
        if($option) {
            $selectedOption = $option;
        }
        ?>
        <table class="inputTable" style="padding:10px;">
            <tr>
                <td><input id='currentDateACD' type="radio" name="creationDateSettings" value="currentDate"
                        <?php echo ($selectedOption == 'currentDate') ? 'checked' : '';?>></td>
                <td><label>Use Current date as Creation Date</label></td>
            </tr>
            <tr>
                <td><input id='uploadDateACD' type="radio" name="creationDateSettings" value='uploadedDate'
                        <?php echo ($selectedOption == 'uploadedDate') ? 'checked' : '';?>></td>
                <td><label>Use Uploaded date as Creation Date</label></td>
            </tr>
        </table>
        <div class="margin">
            <input id="save" type="submit" name="update" value="Update"'>
        </div>
    <?php }
    if( $message != null ) {
        echo<<<ENERGIE
        <div class="margin">
	  	  <div class="error">
	  	    $message
	  	  </div>
	  	</div>
ENERGIE;
    }
    echo<<<ENERGIE
    </div>
  </form>
 </body>
</html>
ENERGIE;
}
?>
