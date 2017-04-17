<?php
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
//create unique folders before moving files
$dept='client_files808';
$deptID=substr($dept,12);
$cabinet = "Employees";
$cabinetID=1;
$userName="admin";
$db_doc = getDbObject ('docutron');
$db_dept = getDbObject ($dept);
$db_deptTwo = getDbObject ($dept);
$db_deptThree = getDbObject ($dept);
$fileSize=4096;

		  $select = "select * from [". $cabinet."_files] where subfolder is not null and subfolder!='' and [filename] is not null and deleted = 0";
		
	      $Files = $db_dept->queryAll($select);
		
		
			
			//loop through all file records that are in a document type
			for ($x=0; $x<count($Files);$x++){
					$doc_id = $Files[$x]['doc_id'];
					$subfolderName = $Files[$x]['subfolder'];
					$dateCreated=$Files[$x]['date_created'];
					//remove random numbers from end of subfolder name
					$docType=str_replace("_"," ",$subfolderName);
					$docType=substr($docType,0,strlen($docType)-1);
					
					echo $docType."\n";
					$select = "select * from [document_type_defs] where [document_type_name]= '".$docType."'";
					$documentNames = $db_deptThree->queryAll($select);
					$docName = $documentNames[0]['document_table_name'];
					 echo $docName."\n";
						$Test_doc_id = $documentNames[0]['id'];
						
					//this will make sure we get back results
					if($Test_doc_id<1){
						$docType=str_replace("_"," ",$subfolderName);
						$docType=substr($docType,0,strlen($docType)-8);
						echo $docType."\n";
						$select = "select * from [document_type_defs] where [document_type_name]= '".$docType."'";
						$documentNames = $db_deptThree->queryAll($select);
						$docName = $documentNames[0]['document_table_name'];
					    echo $docName."\n";
					}
					//-----------------see if header already exists------------------
					$headerselect = "select * from [". $cabinet."_files] where subfolder='".$subfolderName."' and [filename] is null and doc_id=".$doc_id;
		
					$HeaderRow = $db_dept->queryAll($headerselect);
					$HeaderID=$HeaderRow[0]['id'];
					//---------------------------------------------------
					if($HeaderID<1){
						$insert="insert into ".$cabinet."_files(doc_id,subfolder,date_created,file_size,document_table_name)values (".$doc_id.", '".$subfolderName."','".$dateCreated."',".$fileSize.", '".$docName."')";
						echo $insert;
						$insertHeader = $db_deptTwo->queryAll($insert);	
					}else{
							echo $HeaderID."\n";
					}					
			}
			
			
			
				  
					





?>
