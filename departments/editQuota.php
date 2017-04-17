<?php

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
include_once '../updates/updatesFuncs.php';
include_once '../lib/quota.php';
if( $logged_in ==1 && strcmp($user->username,"")!=0 && $user->isSuperUser() ) {
	if( $user->isDepAdmin() ) {
		$quotaCheck = getTableInfo($db_doc,'quota');
		$quotaInfo = $quotaCheck->fetchRow();
		$maxQuota = adjustQuota( $quotaInfo['size_used'] );
		if( isset( $_POST['B1'] ) ) {
			$pool = $user->characters(2);
			$quotaInfo = getLicensesInfo( $db_doc, NULL, NULL, NULL, 'order' );
			$message = "";
			while( $results = $quotaInfo->fetchRow() ) {
				$status = true;
			 	$qSize = $results['quota_used'];
                $arb_dep = $results['arb_department'];
                $real_dep = $results['real_department'];
				$newSize = $_POST[$real_dep];
				for($i=0;$i<sizeof($newSize);$i++) {
					$status = strrpos( $pool, $newSize[$i] );
					if( $status === false )
						break;
				}
				if( $status !== false ) {
					$exp = $_POST[$real_dep.'-Size'];
					
					if( $exp == "TB" )
					  $newSize = $newSize * 1099511627776;
					elseif( $exp == "GB" )
					  $newSize = $newSize * 1073741824;
					elseif( $exp == "MB" )
					  $newSize = $newSize * 1048576;
					elseif( $exp == "KB" )
					  $newSize = $newSize * 1024;
					if( $newSize >= $qSize ) {
						$quotaCheck = getSumfromQuota($db_doc,$newSize,$real_dep);
						
						if( $quotaCheck[0] <= $quotaCheck[1] ) {
							$updateArr = array('quota_allowed'=>$newSize);
							$whereArr = array('real_department'=> $real_dep);
							updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
							$message = "Department $arb_dep's Quota Successfully Updated<br>";
						} else {	
							$error = adjustQuota( $quotaCheck[0] - $quotaCheck[1] );
							$message .= "Quota For Department $arb_dep Exceeds Overall Limit By $error<br>";
						}
					}	
					elseif( $newSize != NULL )
			  			$message .= "Department $arb_dep Did Not Have Enough Space Allocated<br>";
				}
				else
					$message .= "Department $arb_dep had an Invalid Character<br>";
			}
		}
		echo "<html>
			   <head>
  				<link REL='stylesheet' TYPE='text/css' HREF='../lib/style.css'>
			   </head>
			   <body>
		   		<form name='addQuota' method='post' action='editQuota.php'>
   				<center>
				 <div>Cannot Exceed $maxQuota</div>
				 <table class='settings' width='566'>
				  <tr class='tableheads'>
				   <td align='center'>Department</td>
				   <td align='center'>Space Allowed</td>
				   <td align='center'>Space Used</td>
				   <td align='center'>New Allowed Size</td>
				  </tr>\n";
		$arbList = getLicensesInfo( $db_doc, 'real_department', 'arb_department', 1 );
		uasort( $arbList, "strnatcasecmp" );
		$depList = array_keys( $arbList );	

		for($i=0;$i<sizeof($depList);$i++) {
			$real_dep = $depList[$i];
			$arb_dep = $arbList[$real_dep];

			$quotaInfo = getLicensesInfo( $db_doc, $real_dep );
			$result = $quotaInfo->fetchRow();
			$totalUsed += $result['quota_used'];	
			$totalAllowed += $result['quota_allowed'];
			$qAllowed = adjustQuota($result['quota_allowed']);
			$qSize = adjustQuota($result['quota_used']);	
			
			echo " <tr>
				    <td align='center'>$arb_dep</td>
				    <td align='center'>$qAllowed</td>
				    <td align='center'>$qSize</td>
				    <td align='center'>
					 <input type='text' name='$real_dep' size='5'>
					 <select name='$real_dep-Size'>
					  <option selected value='KB'>KB</option>
					  <option value='MB'>MB</option>
					  <option value='GB'>GB</option>
					  <option value='TB'>TB</option>
					 </select>
					</td>
				   </tr>";
		}
			$totalAllowed = adjustQuota($totalAllowed);
			$totalUsed = adjustQuota($totalUsed);
		echo " 	<tr>
				    <td align='center'>Total</td>
				    <td align='center'>$totalAllowed</td>
				    <td align='center'>$totalUsed</td>
				    <td align='center'>&nbsp;</td>
				   </tr>";
		echo " 	<tr>
				 <td colspan='4' align='right'>";

					if( $message)
						echo "<div class=\"error\">$message\n";
					else
						echo "<div>\n";

		echo "	  <input type='submit' name='B1' value='UPDATE'></div>
				 </td>
			   	</tr>	
			   </table>
   				</center>
			  </form>
			 </body>
		    </html>";
	} else {
		logUserOut();
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
