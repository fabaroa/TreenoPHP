<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/mime.php';

if($logged_in ==1 && strcmp($user->username,"")!=0 && $user->isAdmin()) {
  $autoCompleteEnabled = "Auto Complete Indexing Is Enabled For ->";
  $autoCompleteAppended = "Appended Auto Complete Indexing For ->";

  $cab = $_GET['cab'];
  if (isset ($_GET['mess'])) {
	  $mess = $_GET['mess'];
  } else {
	  $mess = '';
  }

  //connected to docutron database
  $db_doc = getDbObject ('docutron');
  $gblStt = new GblStt($user->db_name, $db_doc);
  $value = $gblStt->get('indexing_'.$cab);
   
  //connect to department database
  $db_dept = $user->getDbObject();

  $indexNames = getTableColumnInfo ($db_dept, $value);

  if(isset ($_POST['B1']) and $_POST['B1'] == "Submit") {
      if( sizeof(array_unique( $_POST )) != sizeof( $_POST ) )  {   
	    $mess = "Duplicate Field Name Values";
		echo<<<ENERGIE
		<script>
		  onload = parent.mainFrame.window.location = "verifyUpload.php?"
				 + "cab=$cab&mess=$mess";  
		</script>
ENERGIE;
		die();
	  }
	$changeIndices = array();
	for($i = 0; $i < sizeof( $indexNames ); $i++) {  
		$newIndice = $_POST['cabinetList'.$i];
		$changeIndices[] = $newIndice;
    }
	alterUploadTable($db_dept, 'auto_complete_'.$cab, $changeIndices, $indexNames);
	echo<<<ENERGIE
	<script>
	  onload = parent.mainFrame.window.location = "uploadIndexFile.php?"
             + "mess=$autoCompleteEnabled $cab";
	</script>
ENERGIE;
  } else {
	//$fields = getCabinetInfo( $db_dept, $cab );
	$indexInfo = getTableInfo($db_dept, $value, array(), array(), 'queryAll', array($indexNames[0] => 'ASC'), 25);
?>
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 </head>
 <body>
  <center><?php echo $mess; ?></center>
  <center>
   <form name="verify" method="post" action="verifyUpload.php?cab=<?php echo $cab; ?>">
    <table cellspacing="1" cellpadding="0" border="0" class="lnk_black">
     <tr class="tableheads">
	 <?php for($j=0;$j<sizeof($indexNames);$j++): ?>
	  <td>
	   <select name="cabinetList<?php echo $j; ?>">
		<?php for($i=0;$i<sizeof($indexNames);$i++): ?>
			<option 
			<?php if($j == $i): ?>
			selected
			<?php endif; ?>
			value="<?php echo $indexNames[$i]; ?>"><?php echo str_replace("_"," ",$indexNames[$i]); ?></option>
		<?php endfor; ?>
	   </select>
	  </td>
	 <?php endfor; ?>
	 </tr>
	 <?php if($indexInfo): ?>
		<?php foreach($indexInfo AS $results): ?>
			<tr bgcolor = "#ebebeb">
			<?php foreach($indexNames AS $name): ?>
				<td width="300" noWrap="yes"><?php echo $results[$name]; ?></td>
			<?php endforeach; ?>
			</tr>  	
		<?php endforeach; ?>
	<?php endif; ?>
     <tr>
	  <td colspan="2" align="right">
	   <input type="submit" name="B1" value="Submit">
      </td>
	 </tr>
    </table>
   </form>
  </center>
 </body>
</html>
<?php
  }
	setSessionUser($user);
} else {
	logUserOut();
}
?>
