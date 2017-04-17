<?php  
ini_set("log_errors", 1);
ini_set("error_log", "C:/Treeno/Logs/Dynamic_WF_Manager.log");
include_once '../check_login.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
require_once '../lib/cabinets.php';
include_once '../lib/mime.php';//for downloadfile function
/*
*/
$css= '<!DOCTYPE html><html><head>
			<style>
			* {font-family: Tahoma,Verdana,sans-serif;}
			h3 {color: #517CA3; display: inline-block; }
			h2 {font-size: 16px; color: #006A5A;  display: inline-block;}
			h1 {font-size: 26px; color:#003B6F; display: inline-block;}
			table {border-collapse: collapse;width: 60%;}
			table.center {margin-left:auto;margin-right:auto;}
			td, th {border: 1px solid #517CA3;text-align: center;padding: 8px; white-space:pre;}
			tr:nth-child(even) 
			{background-color: #CFDBE6;}
			input,select{
			border:1px solid #ccc;
			padding:3px;
			width:50%;
			font-size:14px;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px;
			border-radius: 5px;}
			.button {
				background-color:#003B6F; /* Blue */
				border: none;
				color: white;
				padding: 15px 32px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 20px;
			}
			
			</style></head>';

$dept=$user->db_name;
$userName="admin";
$db_doc = getDbObject ('docutron');
$db_dept = getDbObject ($dept);
//print_r($_POST);
if(array_key_exists('cabinet',$_POST)){
	$_SESSION['cabinet']=$_POST['cabinet'];
}

if($_POST['btn_view']){//view current folders
/*
below is a samlpe url to go to a specific folder in treeno
https://tr1.treenosoftware.com/Home.aspx?legint=Redirect&IntUseName=treenosupport306&IntPassword=098F6BCD4621D373CADE4E832627B4F6&IntDept=client_files867&IntCab=Claims&IntSearchTerms={"doc_id":"26"}
*/
echo $css;
	if($_POST['cabinet']==''){
		echo "<center><h1>No Cabinet Selected Please Start Over</h1>";
		echo '<br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a></center>';
		exit;
	}
	$cabinet=$_SESSION['cabinet'];
	//$cabinet=str_replace(" ","_",$cabinet);
	//---->get indexes
	$indexarray = getCabinetInfo($db_dept, $cabinet);
	$columns = getcolumnsfromarray($indexarray);
	//--->get folders
	$select="select doc_id,".$columns." from ".$cabinet." where deleted=0";
	error_log($select);
	$Folders = $db_dept->queryAll($select);
	echo "<body><form action='".$_SERVER['PHP_SELF']."' method = 'POST' >
	<center><h1>CABINET:".$cabinet."</h1></center>";//first row
	//headers
	echo "<table  class='center' ><tr><th>doc_id</th>";
	foreach($indexarray as $cabindex){
		echo "<th>".$cabindex."</th>";
	}
	echo "</tr>";
	//data rows   NOTE: we need to convert comma delimited to lf delimiter too
	foreach($Folders as $folder){	
		echo "<tr>";
		$fCount=0;
		foreach($folder as $index){
			$index=str_replace(",","\n",$index);
			if($fCount==0){
				//echo "<td><a href='https://tr1.treenosoftware.com/Home.aspx?legint=Redirect&IntUseName=".$user->username."&IntPassword=".$user->password."&IntDept=".$dept."&IntCab=".$cabinet.'&IntSearchTerms={"doc_id":"'.$index.'"}\' target="_blank">'.$index.'</a></td>';
				echo "<td>".$index."</td>";
				$fCount++;
			}else{
			echo "<td>".$index."</td>";
			}
		}
		echo "</tr>";
	}
	echo "</table>";
	echo '<center><br><input name="btn_export" class="button" type="submit" value="Export">';
	echo '<br><input name="btn_import" class="button" type="submit" value="Import">';
	echo '<br><br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a></center></form></body>';
}elseif($_POST['btn_import']){
	echo $css;
	echo'<form enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'" method="POST"><center><br/>
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	Choose a file to upload: <input name="uploadedfile" type="file" /><br/><br/>
	<input type="submit" name="btn_upload" class="button" value="Upload File" /></center>
	</form>';
	
}elseif($_POST['btn_export']){
	if($_SESSION['cabinet']==''){
		echo "<center><h1>No Cabinet Selected Please Start Over</h1>";
		echo '<br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a></center>';
		exit;
	}
//--------------create csv--------------------
	$csvdata='';
	//---->get indexes
	$cabinet=$_SESSION['cabinet'];
	$indexarray = getCabinetInfo($db_dept, $cabinet);
	$columns = getcolumnsfromarray($indexarray);
	//--->get folders
	$select="select doc_id,".$columns." from ".$cabinet." where deleted=0";
	error_log($select);
	$Folders = $db_dept->queryAll($select);
	//header row
	$csvdata='"doc_id"';
	foreach($indexarray as $cabindex){
			$csvdata=$csvdata.',"'.$cabindex.'"';
	}
	if($csvdata=='"doc_id"'){
		echo "<center><h1>Error Retrieving Column Names Please try again</h1>";
		echo '<br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a></center>';
		
	}else{
		//write folder data
		foreach($Folders as $folder){	
			//loop through each row
			$row="";
			foreach($folder as $index){
				$index=str_replace(",","\n",$index);
				if($row==''){
					$row='"'.$index.'"';
				}else{
					$row=$row.',"'.$index.'"';
				}	
			}
			if($row!=""){
			$csvdata=$csvdata."\r\n".$row;
			}
		}
		//write to csv
		$file_path="C:/Treeno/treeno/clientbots/cabinetCSVs/Exports/";
		$file=$cabinet.date("Y_m_d_H_i_s").".csv";
		file_put_contents($file_path.$file,$csvdata);
//-----------function downloadFile($path, $filename, $attach, $delete, $realFilename = '', $quickView=false)
		//downloadFile($file_path,$file,1,0,$file);
  header("Content-disposition: attachment;filename=$file");
  readfile($file_path.$file);
  //unlink($file_path.$file);<<----if you do not want the ability to backoff changes uncomment this
	}
}elseif($_POST['btn_upload']){
	echo $css;
	if($_SESSION['cabinet']==''){
		echo "<center><h1>No Cabinet Selected Please Start Over</h1>";
		echo '<br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a></center>';
		exit;
	}
	//print_r($_FILES);
	 $target_path = "C:/Treeno/treeno/clientbots/cabinetCSVs/Imports/";
	$target_path = $target_path .$dept."_". basename( $_FILES['uploadedfile']['name']); 
	//good upload
	if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
		echo "The file ".  basename( $_FILES['uploadedfile']['name']). 
		" has been uploaded. ";
		$ext = pathinfo($target_path, PATHINFO_EXTENSION);
		//make sure file is a csv
		if($ext!="csv"){
			echo " Bad Extension! Please Upload a csv file";
			//delete file from server
			unlink($target_path);
			exit;
		}
		$x=0;
		$msg="";
		$handle = fopen($target_path, "r");
		while (($data = fgetcsv($handle, 1000, ",",'"')) !== FALSE)
		{ 
			if($x==0){//process header rows
				//check to make sure column count matches cabinet
				$csvcount=count($data);
				$indexarray = getCabinetInfo($db_dept, $_SESSION['cabinet']);
				$columncount=count($indexarray)+1;//+1 is for doc_id   
				if($csvcount==$columncount||$csvcount==$columncount+1){// <<----+1 for deleted column
					echo " -good file-";
					//make sure columns are correct for cabinet
					$goodcsv=true;
					$CSVColumnNames=$data;
					foreach($data as $columnName){
						//two columns below allow use of a csv that may have been created in the cabinet GUI, export to excel
						$columnName=strtolower($columnName);
						$columnName=str_replace(" ","_",$columnName);
						if(in_array($columnName,$indexarray)||$columnName=='doc_id' ||$columnName=='deleted'){//<<---------add ||$columnName=='deleted' for mass delete
							//good	
						}else{
							$goodcsv=false;
						}
					}
					if(!$goodcsv){
							echo " Bad Column names in csv for ".$_SESSION['cabinet'];
							rename($target_path,$target_path.".colnames");
							exit;	
					}else{
						//get cabinet id if we have a good csv
						$cabinetID=$db_dept->queryOne("select departmentid from departments where real_name='".$_SESSION['cabinet']."'");
					}
					$x++;
				}else{
					echo " -CSV has Incorrect amount of columns for cabinet-";
					rename($target_path,$target_path.".colcount");
					exit;					
				}
			}else{//process data rows
				$x++;
				//populate update array
				$indiceArr=array();
				$i=0;
				$isNewFolder=false;
				foreach($data as $columndata){
					if($CSVColumnNames[$i]=='doc_id'){
						$doc_id=$columndata;
						if(!ctype_digit($doc_id)){//no doc id given
							$isNewFolder=true;
						}else{//check to see if doc_id is valid 
							$goodDocID= $db_dept->queryOne("select doc_id from ".$_SESSION['cabinet']." where deleted = 0 and doc_id=".$doc_id);
							if($goodDocID<1){
								$isNewFolder=true;
							}	
						}	
					}else{
						$columndata=str_replace("\n",",",$columndata);
						$indexName=strtolower($CSVColumnNames[$i]);
						$indexName=str_replace(" ","_",$indexName);
						$indiceArr[$indexName]=$columndata;
					}
						$i++;
				}
				if($isNewFolder){
					$doc_id=createCabinetFolder($dept, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept);
				}else{
				//update row
				$doc_id = updateCabinetFolder($db_dept, $cabinetID, $doc_id, $indiceArr, $userName);
				}
				if($doc_id<1){
					if($msg==''){
						$msg='Error updating/inserting from csv row:'.$x."<br>";
					}else{
						$msg=$msg.'Error updating/inserting from csv row:'.$x."<br>";
					}					
				}
					
			}
		
		
		//update each folder by row but record errors
		}
		echo $msg." -update complete-";
		echo'<br><a href="https://ws.treenosoftware.com/secure/Medtron_Dynamic_WF_Manager.php">Back</a>';
		
	} else{
		echo " -There was an error uploading the file, please try again!-";
	}
}else{//default - dropdown of cabinets submit to view,submit to export or submit to import 
echo $css;
//print_r($user);
echo "<body><form action='".$_SERVER['PHP_SELF']."' method = 'POST'>
	  <table  class='center' ><tr><th><h1>Manage Dynamic Workflow Tables</h1></th></tr>
	  <tr><td>Cabinet:<select name='cabinet'>";
	$Cabs =$db_dept->queryAll('select * from departments where deleted = 0 order by departmentname');
	echo '<option>';
	foreach($Cabs as $Cab){
		echo '<option value="'.$Cab['real_name'].'">'.$Cab['departmentname'].'</option>';
	} 
	echo "</select></td></tr>";
	echo'<tr><td><input name="btn_view" class="button" type="submit" value="View"></td></tr>
	<tr><td><input name="btn_import" class="button" type="submit" value="Import"></td></tr>
	<tr><td><input name="btn_export" class="button" type="submit" value="Export"></td></tr></table></form></body>';
}
function getcolumnsfromarray($indexarray){
	$columns='';
	foreach ($indexarray as $columnname){
		if($columns==''){
			$columns=$columnname;
		}else{
			$columns= $columns.", ".$columnname;		
		}	
	}
	return $columns;
}

?>