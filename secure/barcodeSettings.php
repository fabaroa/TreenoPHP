<?PHP
// $Id: barcodeSettings.php 14201 2011-01-04 15:32:33Z acavedon $

include_once '../check_login.php';
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../lib/settingsList.inc.php';
include_once '../modules/modules.php';

if($logged_in == 1 and $user->username) {
	$db_name = $user->db_name;
//	$setting = "showBarcode";
	$formatSetting = array(
		"bcFormat-mtif", "bcFormat-stif", "bcFormat-asis", "bcFormat-pdf"
	);
	$settings = array(
		"compress"			=> "Compress", 
		"deleteBC"			=> "Delete Barcode"
	);
	$settingsList = new settingsList($db_doc, $db_name, $db_object);
	
	if (!empty ($_POST['selectedCab'])) {
		$selectedCab = $_POST['selectedCab'];
	} else {
		$selectedCab = '';
	}
	if( isSet($_POST['submitBut']) ) {
		$postFormat = $_POST['format'];
		foreach($formatSetting AS $format) {
			if( strcmp($format, $postFormat) == 0 ) {
				$settingsList->markEnabled($selectedCab, $format);
			} else {
				$settingsList->markDisabled($selectedCab, $format);
			}
		}
		
		foreach($settings AS $setting => $displaySetting) {
			if( $_POST[$setting] ) {
				$settingsList->markEnabled($selectedCab, $setting);
			} else {
				$settingsList->markDisabled($selectedCab, $setting);
			}
		}

		$settingsList->commitChanges();
	}
	$settingsPermissions = array();
	$settingsPermissions = $settingsList->getSettingsList();

echo<<<ENERGIE
<html>
<head>
<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>Barcode settings</title>
<script>
	function submitForm()
	{
		document.bcSettingsForm.submit();
	}
</script>
</head>

<body>
	<form id="bcSettingsForm" name="bcSettingsForm" method="POST" action="barcodeSettings.php">
	<center>
		<table class="settings" width="100%">
		<tr>
		 <td colspan="4" class="tableheads">
			Barcode Settings
		 </td>
		</tr>
		<tr>
		 <td colspan="4">
			<select name="selectedCab" onchange="submitForm()">
			<option value="default">Select a cabinet</option>
ENERGIE;

	foreach($user->cabArr AS $cab => $displayCabName) {
		if( strcmp($selectedCab, $cab) == 0 )
			echo "<option selected value='$cab'>$displayCabName</option>\n"; 
		else
			echo "<option value='$cab'>$displayCabName</option>\n"; 
	}
	echo "</td>";
	echo "</select>\n</tr>";

	if( !empty($selectedCab) ) {
		if( isSet($settingsPermissions[$selectedCab]) ) {
			$cabSettings = $settingsPermissions[$selectedCab];
		} else {
			$cabSettings = array();
		}
		if( isSet($cabSettings['bcFormat-mtif']) AND $cabSettings['bcFormat-mtif'] == 1 ) {
			$checkedMtif = "checked";
			$checkedAsis = '';
			$checkedPdf = '';
			$checkedStif = '';
		} elseif( isSet($cabSettings['bcFormat-asis']) AND $cabSettings['bcFormat-asis'] == 1 ) {
			$checkedMtif = '';
			$checkedAsis = "checked";
			$checkedPdf = '';
			$checkedStif = '';
		} elseif( isSet($cabSettings['bcFormat-pdf']) AND $cabSettings['bcFormat-pdf'] == 1 ) {
			$checkedMtif = '';
			$checkedAsis = '';
			$checkedPdf = "checked";
			$checkedStif = '';
		} else {
			$checkedMtif = '';
			$checkedAsis = '';
			$checkedPdf = '';
			$checkedStif = "checked";
		}

		echo "<tr><td colspan=4>&nbsp;</td></tr>\n";
		echo "<tr>\n";
		echo "	<td colspan=4>$selectedCab</td>\n";
		echo "<tr>\n";
		echo "<tr>\n";
		echo "	<td>Barcode Format</td>\n";
		echo "	<td>Multi-Page TIFF";
		echo "		<input type=radio value=bcFormat-mtif $checkedMtif name=format>";
		echo "	</td>\n";
		echo "	<td>Single-Page TIFF";
		echo "		<input type=radio value=bcFormat-stif $checkedStif name=format>";
		echo "	</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td>&nbsp;</td>\n";
		echo "	<td>PDF";
		echo "		<input type=radio value=bcFormat-pdf $checkedPdf name=format>";
		echo "	</td>\n";
		echo "	<td>As is";
		echo "		<input type=radio value=bcFormat-asis $checkedAsis name=format>";
		echo "	</td>\n";
		echo "</tr>\n";
		foreach( $settings AS $setting => $displaySet ) {
			if( isSet($cabSettings[$setting]) AND !$cabSettings[$setting]) {
				$checkedDisable = "checked";
				$checkedEnable = "";
			} else {
				$checkedEnable = "checked";
				$checkedDisable = "";
			}
			echo "<tr>\n";
			echo "	<td>$displaySet</td>\n";
			echo "	<td>Enable";
			echo "		<input type=radio value=1 $checkedEnable name=$setting>";
			echo "	</td>\n";
			echo "	<td>Disable";
			echo "		<input type=radio value=0 $checkedDisable name=$setting>";
			echo "	</td>\n";
			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "	<td colspan=4>";
		echo "		<input name=submitBut type=submit value=Save>";
		echo "	</td>\n";
		echo "</tr>\n";
	}

echo<<<ENERGIE
		</table>
	</center>
	</form>
</body>
</html>
ENERGIE;
} else {
	//redirect them to login
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
