<?php 
// $Id: complianceReport.php 14159 2010-12-07 15:59:06Z acavedon $
/*
 * complianceReport.php - This script generates the web page for configuring and 
 * producing Compliance Reports. By deault a report can be generated to show all 
 * folders that are missing required document types or show all folders that comply 
 * with the required document types. First step for the User is to select a cabinet 
 * and then click either Generate Report to display the report on screen -OR- 
 * Export Report to dump the results to a CSV file. Configure doesn't require to 
 * select an initial cabinet 
 * 
 * Author:  Al Cavedon
 * Created: 11/17/2010
 */

require_once '../check_login.php';
require_once '../db/db_common.php';

$complianceNoCab = "Configure a cabinet first";

// Acquire the cabinets list that contain portfolios
if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$db_dept = getDbObject($user->db_name);

	if (isset ($_GET['msg'])) {
		$msg = $_GET['msg'];
	} else {
		$msg = '';
	}
	
	$sArr = array('cabinet');
	$cabinetList = getTableInfo( $db_dept, 'compliance',
								 $sArr, array(), 'queryCol' );

	usort( $cabinetList, "strnatcasecmp" );
}
?>

<!-- HEAD -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Portfolio Report</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<style type="text/css">
	#cabinet {
		vertical-align: middle;
		cursor: pointer;
	}
	</style>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript">
	var dbName = '<?php echo $user->db_name; ?>';
	function registerFuncs() {
		var cabinet = getEl('cabinet');
		cabinet.input = getEl('cabinet');
	}

	// go out to the protfolio configuration screen
	function configure() {
		window.location = "complianceConfig.php";
	}
	
	// Generate and display portfolio data from the selected cabinet
	function displayReport() {
		var cabinet = getEl('cabinet').value;
		var missing;
		if(getEl('missComp1').checked == true) {
			missing = 0;
		} else {
			missing = 1;
		}
		if(cabinet != "") {
			window.location = 'complianceReportOutput.php?cab='+cabinet+'&missComp='+missing+'&export=0';
		} else {
		    window.location = 'complianceReport.php?default=$msg&message=$complianceNoCab';
		}
	}
	
	// Export the portfolio data from selected cabinet to a CSV file
	function exportReport() {
		var cabinet = getEl('cabinet').value;
		var missing;
		if(getEl('missComp1').checked == true) {
			missing = 0;
		} else {
			missing = 1;
		}
		//		window.location = "portfolioExportReport.php?cab="+cabinet.value+'&export=1';
		if(cabinet != "") {
			window.location = 'complianceReportOutput.php?cab='+cabinet+'&missComp='+missing+'&export=1';
		} else {
		    window.location = 'complianceReport.php?default=$msg&message=$complianceNoCab';
		}
	}
	
	</script>
</head>
<!--<body class="centered" onload="registerFuncs()">-->
<body class="centered">

<div class="mainDiv">
	<!-- page header -->
	<div class="subTitle">
		<span>Compliance Report</span>
	</div>
	
	<!-- Cabinet pulldown -->
	<div>
		<label for="cabinet">Cabinet to Report on:</label>
		<select id="cabinet" name="cabinet">
		<?php foreach($cabinetList AS $cabName): ?>
			<option value="<?php echo $cabName; ?>">
				<?php echo $cabName; ?>
			</option>
		<?php endforeach; ?>
		</select>
			<br></br>Report on folders with document types:&nbsp;
			<input type="radio" id="missComp1" name="missComp" value="0"/>complete
			<input type="radio" id="missComp2" name="missComp" value="1" checked="checked"/>missing<br></br>
	</div>
	<!-- Generate buttons: Configure, Display and Export -->
	<div>
		<br></br> <!-- spacing -->
		<input type="submit" name="outputCompliance" value="Configure" onclick="configure()"/>
		<input type="submit" name="outputCompliance" value="Display Report" onclick="displayReport(this.form)"/>
		<input type="submit" name="outputCompliance" value="Export Report" onclick="exportReport(this.form)"/>
	</div>
	<div id="repPlace">
	</div>
</div>
	<?php if( isset($_GET['msg'] )): ?>
		<div id='errorDiv' class="error"><?php echo $_GET['msg'] ?></div>
	<?php endif ?>
</body>
</html>
