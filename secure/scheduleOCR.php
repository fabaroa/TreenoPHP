<?php

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser() ) {
//check values and make file if selections are valid
	$db_doc = getDbObject ('docutron');
	$settings = new GblStt( $user->db_name, $db_doc );
	if(isset($_POST['change'])) {

		$start_time =  date( "H:i", strtotime($_POST['start_time_hr'].":".$_POST['start_time_min']." ".$_POST['start_mid']));
		$end_time =  date( "H:i", strtotime($_POST['end_time_hr'].":".$_POST['end_time_min']." ".$_POST['end_mid']));

		//display message if value is 00
		if($_POST['start_time_hr'] == $_POST['end_time_hr'] && 
			$_POST['start_time_min'] == $_POST['end_time_min'] && 
			$_POST['start_mid'] == $_POST['end_mid'] )
			$message="Start Time and End Time Cannot Be The Same";
		else {
			$settings->set( 'ocr_schedule', $start_time."-".$end_time);
			$displayStart = $_POST['start_time_hr'].":".$_POST['start_time_min']." ".$_POST['start_mid'];
			$displayEnd = $_POST['end_time_hr'].":".$_POST['end_time_min']." ".$_POST['end_mid'];
			$message="OCR will run from $displayStart to $displayEnd";
		}
	} else if(!empty ($_POST['auto']) and $_POST['auto']=="off") {
		$settings->removeKey( 'ocr_schedule');
	} else if(!empty ($_POST['auto']) and $_POST['auto']=="on") {
		$settings->set( 'ocr_schedule', "-");
	}

	$times = $settings->get( 'ocr_schedule' );
	if( $times != "-" and $times != '' )
		$times = explode( "-", $times );
		
	//if ocr_scheduler exists, read from it to display current settings
	//read the values from the file if it is set, if not display 00's
	if( is_array( $times ) ) {
		$start_time=explode(":",$times[0]);
		$end_time=explode(":",$times[1]);

		//determine am/pm for start time
		if( $start_time[0] < 11 ) {
			$start_am = "selected";
		} else {
			$start_pm = "selected";
		}
		//format time into 12 hour blocks for start time
		if( $start_time[0] > 12 ) {
			$start_time[0] -= 12;
		} else if( $start_time[0] == 0 ) {
			$start_time[0] = 12;
		}
		
		//determine am/pm for end time
		if( $end_time[0] < 11 ) {
			$end_am = "selected";
		} else {
			$end_pm = "selected";
		}
		//format time into 12 hour blocks for end time
		if( $end_time[0] > 12 ) {
			$end_time[0] -= 12;
		} else if( $end_time[0] == 0 ) {
			$end_time[0] = 12;
		}

	}
	if( array_key_exists( 'ocr_schedule', $settings->settings ) ) {
		$on = "checked";
		$off = '';
	} else {
		$off = "checked";
		$on = '';
	}

	echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>System Preferences</title>
  <script>
 </script>       
 </head>
 <body>\n
ENERGIE;
	//display message if a change was successfully made
	if(isset($message)) {
	   echo "  <center><b>$message</b></center><br>";
	}
	echo<<<ENERGIE
  <form name="schedule" method="POST" action="scheduleOCR.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="2" class="tableheads">Schedule OCR</td>
    </tr>
    <tr>
     <td class="admin-tbl" align="left">Automated OCR Processing</td>
     <td class="admin-tbl" align="center">\n
	<input type="radio" name="auto" value="off" onchange="submit()" $off/>OFF
	&nbsp;
	<input type="radio" name="auto" value="on" onchange="submit()" $on/>ON
	</td>
    </tr>
ENERGIE;
	if( $on ) {
echo<<<ENERGIE
    <tr>
        <td class="admin-tbl" align="left">Start Time</td>
	<td class="admin-tbl" align="center">
		<select name="start_time_hr">
ENERGIE;
		foreach(range(1,12) AS $num ) {
			if( $num < 10 ) {
				$num = "0".$num;
			}
			if( $num == $start_time[0] ) {
				echo "<option value=\"$num\" selected>$num</option>\n";
			} else {
				echo "<option value=\"$num\">$num</option>\n";
			}
		}
			echo<<<ENERGIE
		</select>
		:
		<select name="start_time_min">
ENERGIE;
		foreach(range(0,59) AS $num ) {
			if( $num < 10 ) {
				$num = "0".$num;
			}
			if( $num == $start_time[1] ) {
				echo "<option value=\"$num\" selected>$num</option>\n";
			} else {
				echo "<option value=\"$num\">$num</option>\n";
			}
		}
			echo<<<ENERGIE
		</select>
		&nbsp;
		<select name="start_mid">
			<option value="AM" $start_am>AM</option>
			<option value="PM" $start_pm>PM</option>
		</select>
	</td>
    </tr>
    <tr>
	<td class="admin-tbl" align="left">End Time</td>
	<td class="admin-tbl" align="center">
		<select name="end_time_hr">
ENERGIE;
		foreach(range(1,12) AS $num ) {
			if( $num < 10 ) {
				$num = "0".$num;
			}
			if( $num == $end_time[0] ) {
				echo "<option value=\"$num\" selected>$num</option>\n";
			} else {
				echo "<option value=\"$num\">$num</option>\n";
			}
		}
			echo<<<ENERGIE
		</select>
		:
		<select name="end_time_min">
ENERGIE;
		foreach(range(0,59) AS $num ) {
			if( $num < 10 ) {
				$num = "0".$num;
			}
			if( $num == $end_time[1] ) {
				echo "<option value=\"$num\" selected>$num</option>\n";
			} else {
				echo "<option value=\"$num\">$num</option>\n";
			}
		}
			echo<<<ENERGIE
		</select>
		&nbsp;
		<select name="end_mid">
			<option value="AM" $end_am>AM</option>
			<option value="PM" $end_pm>PM</option>
		</select>
	</td>
    </tr>
    <tr>
	<td colspan="2" align="right"><input type="submit" name="change" value="Set Time"/></td>
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
} else {
	logUserOut();
}
?>
