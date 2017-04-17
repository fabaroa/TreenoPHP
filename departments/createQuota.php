<?php
// $Id: createQuota.php 14178 2011-01-04 14:50:00Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
include_once '../lib/quota.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser()) {
	$message = '';
	if( $user->isDepAdmin() ) {
		$totalAllowed = getMaxQuota($db_doc,'max_size');
		$total = adjustQuota($totalAllowed);
		$mess = "Cannot exceed $total";
		if( isset($_POST['B1']) ) {
			$pool = $user->characters(2);
			$qAllowed = getTableInfo($db_doc, 'licenses', array('SUM(quota_allowed)'), array(), 'queryOne');
			$tmp = adjustQuota($qAllowed);
			$size = $_POST['quotaLimit'];
			for($i=0;$i<strlen($size);$i++) {
				$status = strrpos( $pool, $size{$i} );
				if( $status === false )
					break;
			}
			if( $status !== false ) {
				$exp = $_POST['size'];
				if( $exp == "TB" )
					$size = $size * 1099511627776;
				elseif( $exp == "GB" )
			  		$size = $size * 1073741824;
				elseif( $exp == "MB" )
			  		$size = $size * 1048576;
				elseif( $exp == "KB" )
					$size = $size * 1024;
				if( $size >= $qAllowed && $size <= $totalAllowed ) {
					$updateArr = array('size_used'=>(double)$size);
					updateTableInfo($db_doc,'quota',$updateArr,array());
					$message = "Quota Successfully Changed";
				} elseif( $size < $qAllowed ) {
					$message = "Quota Too Small, Must Be > $tmp";
				} elseif( $size > $totalAllowed ) {
					$message = "Quota Too Large, Must Be < ".$total;
				}
			} else
				$message = "Invalid Character";
		}
		$quota = getMaxQuota($db_doc,'size_used');
		$quota = adjustQuota( $quota );
		
		echo "<html>
			   <head>
  				<link REL='stylesheet' TYPE='text/css' HREF='../lib/style.css'>
				<script type='text/javascript' src='../lib/settings.js'></script>
			   </head>
			   <body class='centered'>
				<div class='mainDiv'>
				<div class='mainTitle'><span>Set Quota Limit</span></div>
				<form name='editQuota' method='post' action='createQuota.php'>
   				<center>
				 <span>$mess</span>
				 <p>
			     <table class='inputTable'>
			      <tr>
				   <th style='text-align:center'>Current Size</th>
				   <th style='text-align:center'>New Size</th>
			      </tr>
			      <tr>
				   <td style='text-align:center'>$quota</td>
				   <td style='text-align:center'>
					<input type='text' name='quotaLimit'>
					<select name='size'>
					 <option selected value='KB'>KB</option>
					 <option value='MB'>MB</option>
					 <option value='GB'>GB</option>
					 <option value='TB'>TB</option>
					</select>
				   </td>
			      </tr>
			      <tr>
			 	   <td align='right' colspan='2'>";
			
					if( $message )
						echo "<div class=\"error\">$message\n";
					else
						echo "<div>\n";

		echo "		<input type='submit' name='B1' value='Save'></div></td>
			      </tr>
			     </table>
   				</center>
				</form>
				</div>
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
