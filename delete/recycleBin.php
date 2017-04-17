<?php
// $Id: recycleBin.php 14636 2012-01-09 20:40:01Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'delete.php';
include_once '../lib/licenseFuncs.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
	if(!isValidLicense($db_doc)) {
?>
	<html>
		<head>
			<title>Recycle Bin</title>
		</head>
		<body style="color:red">
			<div>Invalid License</div>
			<div>Recycle Bin Operations Are Not Permitted</div>
		</body>
	</html>
<?php
	die();
	}
	if (isset ($_GET['cabinet'])) {
		$cabinet = $_GET['cabinet'];
	} else {
		$cabinet = '';
	}
	if (isset ($_GET['doc_id'])) {
		$doc_id = $_GET['doc_id'];
	} else {
		$doc_id = '';
	}
	if (isset ($_GET['tab'])) {
		$tab = $_GET['tab'];
	} else {
		$tab = '';
	}
	if (isset ($_GET['filename'])) {
		$filename = $_GET['filename'];
	} else {
		$filename = '';
	}
	if (isset ($_GET['fileID'])) {
		$fileID = $_GET['fileID'];
	} else {
		$fileID = '';
	}
	if(isset ($_POST['index']) ) {
		$index = $_POST['index'];
	} elseif (isset ($_GET['index'])) {
		$index = $_GET['index'];
	} else {
		$index = '';
	}
	if (isset ($_GET['restore'])) {
		$restore = $_GET['restore'];
	} else {
		$restore = '';
	}
	if (isset ($_GET['delete'])) {
		$delete = $_GET['delete'];
	} else {
		$delete = '';
	}

	$sett = new Usrsettings( $user->username, $user->db_name  );
 	$results = $sett->get("results_per_page" );	

	if(strcmp($results,"")==0) {
     	$perPage=25;
		$sett->set('results_per_page',25);
 	} else {
    	$results = explode( ",", $results );
	   	$perPage = $results[0];							  
  	}	

	$delObj = new filesToDelete($user->db_name,$cabinet,$doc_id,
			$tab,$filename,$fileID,$restore,$delete, $db_object, $db_doc);
	if( $delObj->restore ) {
		$delObj->restore();
		$user->setSecurity(true);
		$user->audit("Delete Files Restored",$delObj->auditMess);
	} elseif( $delObj->delete ) {
		$delObj->delete();
		$user->setSecurity(true);
		$user->audit("Delete Files Deleted",$delObj->auditMess);
	}

	$toDelete = $delObj->getList();
	$department = $delObj->getDBName();
echo <<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script type="text/javascript" src="../lib/settings.js"></script>
  <script>
   var inFunc = false;
   function selectedFile(cab, doc_id, tab) {
	  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id+'&tab='+tab;
   }
   function parentFolder(cab, doc_id, tab) {
		if( tab != "" )
	  		window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id+'&tab='+tab;
		else if( doc_id != "" )
	  		window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id;
		else if( cab != "" )	
	  		window.location = 'recycleBin.php?cabinet='+cab;
		else
	  		window.location = 'recycleBin.php';
			
   }
   function allowDigi(evt) {
  		evt = (evt) ? evt : event;
  		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
  		if (((charCode >= 48 && charCode <= 57) || 	charCode == 13 || charCode == 8) || (charCode == 37) || (charCode == 39)) 
    		return true;
  		else
    		return false;
   }
   function firstpage( tab, doc_id, cab ) {
	  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&index=1';
   }
   function prevpage( tab, doc_id, cab, index ) {
	  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&index='+index;
   }
   function nextpage( tab, doc_id, cab, index ) {
	  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&index='+index;
   }
   function lastpage( tab, doc_id, cab, last ) {
	  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&index='+last;
   }
   function restoreFile( cab, doc_id, tab, filename, fileID ) {
		if (!inFunc) {
			inFunc = true;
		  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&filename='+filename+'&fileID='+fileID+'&restore=1';
		}
   }
   function removeFile( cab, doc_id, tab, filename,fileID ) {
		if (!inFunc) {
			inFunc = true;
		  	window.location = 'recycleBin.php?cabinet='+cab+'&doc_id='+doc_id
							+'&tab='+tab+'&filename='+filename+'&fileID='+fileID+'&delete=1';
		}
   }
  </script>
 </head>
 <body class='centered'>
  <div class='mainDiv' style='width:700px'>
   <div class='mainTitle'>
    <span>Recycle Bin</span>
   </div>
   <div id='mainFormDiv'>
ENERGIE;
	$rowCount = sizeof( $toDelete );
	$rowCount = ceil( $rowCount / $perPage );
	if( $index == NULL || $index < 1 )
		$index = 1;	
	elseif( $index > $rowCount )
		$index = $rowCount;
	
	if( $rowCount > 1 ) {
		$delObj->createArrows( $index, $rowCount );
	}
	$db_doc=getDbObject('docutron');
	$select="select * from settings where k='cleanRecycleBin' and department='".$user->db_name."'";
	$result = $db_doc->queryAll( $select );
	if ($result){
		if (isset ($_POST['qty']) && $_POST['Auto']) {
			$update="update settings set value='".$_POST['qty']." ".$_POST['timeInc']."' where k='cleanRecycleBin' and department='".$user->db_name."'";
			$result2 = $db_doc->queryAll( $update );
			$timeIncrement = array($_POST['qty'],$_POST['timeInc']);
			$auto="checked";
		} elseif (isset ($_POST['qty'])) {
			$delete="delete from settings where k='cleanRecycleBin' and department='".$user->db_name."'";
			$result2 = $db_doc->queryAll( $delete );
			$auto="";
			$timeIncrement = array("0","");
		} else {
			$auto="checked";
			$timeIncrement = explode(" ", $result[0]['value']);
		}
	} else {
		if (isset ($_POST['qty']) && $_POST['Auto']) {
			$insert="insert into settings (k,value,department) values('cleanRecycleBin','".$_POST['qty']." ".$_POST['timeInc']."','".$user->db_name."')";
			$result2 = $db_doc->queryAll( $insert );
			$auto="checked";
			$timeIncrement = array($_POST['qty'],$_POST['timeInc']);
		} else {
			$auto="";
			$timeIncrement = array("0","");
		}
	}
	$selected=array('','','','');
	switch ($timeIncrement[1]) {
		case 'm':
		  $selected[1] = "selected";
		  break;
		case 'w':
		  $selected[2] = "selected";
		  break;
		case 'd':
		  $selected[3] = "selected";
		  break;
		default:
		  $selected[0] = "selected";
	}
	
echo<<<ENERGIE
	<form name="myform" action="recycleBin.php" method="POST">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr>
		<td rowspan="2"><input type="checkbox" name="Auto" value="1" $auto>Auto clean out items older than <input type="text" name="qty" value="$timeIncrement[0]" style="width:50px;height:23px;" /></td>
		<td><input type="button" value=" /\ " onclick="this.form.qty.value++;" style="font-size:7px;margin:0;padding:0;width:20px;height:13px;" ></td>
		<td rowspan="2">	<select name="timeInc">
			  <option value="" $selected[0]>Never</option>
			  <option value="m" $selected[1]>Months</option>
			  <option value="w" $selected[2]>Weeks</option>
			  <option value="d" $selected[3]>Days</option>
			</select> <input type="submit" value="Save">
		</td>
		</tr>
		<tr>
		<td><input type=button value=" \/ " onclick="this.form.qty.value--;" style="font-size:7px;margin:0;padding:0;width:20px;height:12px;" ></td>
		</tr>
		</table>
	</form>

   <table width="95%" class="results">
    <tr>
	 <td class='tableheads' align='center'>Restore</td>
	 <td class='tableheads' align='center'>Delete</td>
	 <td class='tableheads' align='center'>List</td>
    </tr>
	<tr class='TDresults1' onmouseover="this.className='TDresults2'"
	 onmouseout="this.className='TDresults1'">
	 <td align='center' width='5%'>&nbsp;</td>
	 <td align='center' width='5%'>&nbsp;</td>
	 <td onclick="parentFolder('','','');">$department</td>
	</tr>
ENERGIE;
	echo $delObj->displayParent();
	if( sizeof( $toDelete ) > 0 ) {
		$start = ( $index - 1 ) * $perPage;
		$finish = ( $index * $perPage ); 
		for($i=$start;$i<$finish && $i<sizeof($toDelete);$i++) {
			if( $delObj->tab != NULL ) {
				$deleteInfo = explode("~", $toDelete[$i] );
				$del = 1;
				$fileID = $deleteInfo[0];
				$files = $deleteInfo[1];
				$toDelete[$i] = $deleteInfo[1];
				$onclick = "removeFile('{$delObj->cabinet}','{$delObj->doc_id}','{$delObj->tab}','$files','$fileID')";
				$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
				$spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
			} elseif( $delObj->doc_id != NULL ) {
				$deleteInfo = explode("~", $toDelete[$i] );
				$del = $deleteInfo[0];
				$tab = $deleteInfo[1];
				$files = '';
				$toDelete[$i] = $deleteInfo[1];
				$onclick = "removeFile('{$delObj->cabinet}','{$delObj->doc_id}','$tab','')";
				$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
				$spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;"; 
			} elseif( $delObj->cabinet != NULL ) {
				$deleteInfo = explode("~", $toDelete[$i] );
				$del = $deleteInfo[0];
				$toDelete[$i] = $deleteInfo[1];
				$files = '';
				$doc_id = $deleteInfo[2];
				$onclick = "removeFile('{$delObj->cabinet}','$doc_id','','')";
				$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
			} else {
				$deleteInfo = explode("~", $toDelete[$i] );
				$del = $deleteInfo[0];
				$files = '';
				$cabinet = $deleteInfo[1];
				$toDelete[$i] = $deleteInfo[1];
				$onclick = "removeFile('$cabinet','','','')";
				$spaces = "&nbsp;&nbsp;&nbsp;&nbsp;"; 
			}
		
			echo "<tr class='TDresults1' onmouseover=\"this.className='TDresults2'\"";
			echo "onmouseout=\"this.className='TDresults1'\">";
			echo "<td align='center' width='5%'>\n";
			if( $del == 1 ) {
				echo "<img alt='Restore' src='../energie/images/save.gif' border='0' ";
				echo "onclick=\"restoreFile('$cabinet','$doc_id','$tab','$files','$fileID');\">\n";
			}
			echo "</td>\n";
			echo "<td align='center' width='5%'>\n";
			if( $del == 1 ) {
				echo "<img alt='Delete' src='../energie/images/trash.gif' border='0' ";
				echo "onclick=\"$onclick\">\n";
			}
			echo "</td>\n";
			if($delObj->tab != NULL) {
				echo "<td onclick=\"window.open('displayRecbin.php?cab=$cabinet&doc_id=$doc_id&fileID=$fileID',";
				echo "'fileWindow','height=600,width=800')\">";
			} else {
				echo "<td onclick=\"selectedFile('$cabinet','$doc_id','$tab')\">";
			}
			if( $delObj->cabinet != NULL ) {
				echo $spaces.h($toDelete[$i]);
			} else {
				echo $spaces.$delObj->cabArr[$toDelete[$i]];
			}
			echo "</td>\n";
			echo "</tr>\n";
		}
	} else {
		$delObj->printNoResults();
	}
echo<<<ENERGIE
   </table>
  </div>
  </div>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
