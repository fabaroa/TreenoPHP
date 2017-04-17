<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	//gets values from url from energiefuncs.php
	$redirect = $_SESSION['allThumbsURL'];
	$orderSentry = $_GET['orderSentry'];
	$_SESSION['docuSignView'] = (isset($_GET['docusign']))? $_GET['docusign'] : 0;
	$userOrder = new Usrsettings($user->username, $user->db_name);
	if ($orderSentry) //if $orderSentry is 1, set to 0
		$userOrder->set('order', '0');
	else
		$userOrder->set('order', '1');

	echo<<<ENERGIE
<script>
	var selectedRow = parent.sideFrame.selectedRow;
	var count = parent.sideFrame.tmp_count;
	if( selectedRow ) //if something has already been selected
	{
		parent.sideFrame.location = "$redirect&selected=" + selectedRow + "&count=" + count;
	}
	else //if nothing has been selected
	{
		parent.sideFrame.location = "$redirect";
	}
</script>
ENERGIE;

	setSessionUser($user);
} else { //we want to log them out
	logUserOut();
}
?>
