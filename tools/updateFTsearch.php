<?php
//$Id: updateFTsearch.php 15013 2013-06-21 15:25:12Z fabaroa $

//chdir('/var/www/html/tools');
include '../db/db_common.php';
include '../lib/settings.php';
global $DEFS;
function file_exists_case($strUrl)
{
    $realPath = str_replace('\\','/',realpath($strUrl));
    
    if(file_exists($strUrl) && $realPath == $strUrl)
    {
        return 1;    //File exists, with correct case
    }
    elseif(file_exists($realPath))
    {
        return 2;    //File exists, but wrong case
    }
    else
    {
        return 0;    //File does not exist
    }
}
echo "start<br>\n";
$mtime = microtime(); 
$mtime = explode(' ', $mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime; 
$fp=fopen("/treeno/logs/updateFTsearch.log","a+");
fwrite($fp,date("Y-m-d H:i:s")."\n");
$db = getDbObject('docutron');
$select = "select * from licenses where real_department = 'client_files7' or real_department = 'client_files808' order by real_department";
$res = $db->queryAll( $select );
// for each department
foreach( $res as $row ){
	$department = $row['real_department'];
echo "*******".$department."**********\n";	
fwrite($fp,$department." ".date("Y-m-d H:i:s")."\n");
	$searchDept = $row['real_department']."_search";
	$DBDept = getDbObject($department);
	$DBSearch = getDbObject($searchDept);
	if ($row['real_department'] != 'client_files215') {	
		$select2 = "select * from departments where deleted=0 and real_name<>'Publishing' order by real_name";
		$res2 = $DBDept->queryAll( $select2 );
		foreach( $res2 as $row2 ){
			//check lastModify Key for cabinet, files, documentType
			$QueryS="select LastUpdated from LastModifiedKey where TableName='document_field_value_list'";
			$docTypeKeyArr=$DBSearch->queryAll($QueryS);
			//echo $QueryS."\n";
			//print_r($docTypeKeyArr);
			if (count($docTypeKeyArr)) {
				$docTypeKey=$docTypeKeyArr[0]['lastupdated'];
			} else {
				$docTypeKey='0x0000000000000000';
				$QueryS="insert into LastModifiedKey (TableName,LastUpdated,UpdateDate) VALUES ('document_field_value_list','".$docTypeKey."','".date('Y-m-d H:i:s')."')";
				$results=$DBSearch->query($QueryS);
			}
			//echo $QueryS."\n".$docTypeKey."\n";

			$QueryS="select LastUpdated from LastModifiedKey where TableName='".$row2['real_name']."_files'";
			$fileKeyArr=$DBSearch->queryAll($QueryS);
			//echo $QueryS."\n";
			//print_r($fileKeyArr);
			if (count($fileKeyArr)) {
				$fileKey=$fileKeyArr[0]['lastupdated'];
			} else {
				$fileKey='0x0000000000000000';
				$QueryS="insert into LastModifiedKey (TableName,LastUpdated,UpdateDate) VALUES ('".$row2['real_name']."_files','".$fileKey."','".date('Y-m-d H:i:s')."')";
				$results=$DBSearch->query($QueryS);
			}
			//echo $QueryS."\n".$fileKey."\n";
			$QueryS="select LastUpdated from LastModifiedKey where TableName='".$row2['real_name']."'";
			$cabKeyArr=$DBSearch->queryAll($QueryS);
			if (count($cabKeyArr)) {
				$cabKey=$cabKeyArr[0]['lastupdated'];
			} else {
				$cabKey='0x0000000000000000';
				$QueryS="insert into LastModifiedKey (TableName,LastUpdated,UpdateDate) VALUES ('".$row2['real_name']."','".$cabKey."','".date('Y-m-d H:i:s')."')";
				$results=$DBSearch->query($QueryS);
			}
			//echo $QueryS."\n".$cabKey."\n";

			$QueryMax="select Max([TimeStamp]) as ts from ".$row2['real_name'];
			//error_log("querying timestamp from: ".$row2['real_name']);
			$MaxCabKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxCabKeyArr);
			if (count($MaxCabKeyArr)) {
				$MaxCabKey='0x'.bin2hex($MaxCabKeyArr[0]['ts']);
			} else {
				$MaxCabKey='0x0000000000000000';
				echo "problem with ".$row2['real_name']."\n";
			}
			if ($MaxCabKey=='0x'){
				$MaxCabKey='0x0000000000000000';
			}
			//echo $QueryMax."\n*".$MaxCabKey."*\n";
			echo "//****************** Start of folders ******************************************\n";
			//getting count to avoid memory and timeout problems
			$queryA="select count(*) as cnt from ".$row2['real_name'];
			$queryA=$queryA." where ".$row2['real_name'].".TimeStamp>".$cabKey." and ".$row2['real_name'].".TimeStamp<=".$MaxCabKey."";
			$rescount=$DBDept->queryAll($queryA);
			echo($queryA."\nThe Count ".$rescount[0]['cnt']."\n");
			$IsDeletedFolder=array();
			$oldx=0;
			for ($x=0;$x<$rescount[0]['cnt'];$x+=100000) {
				$endX=$x+=100000;
				$queryA="select docid, location from (";
				$queryA=$queryA."select ROW_NUMBER() OVER(order by ".$row2['real_name'].".TimeStamp) RowNr,".$row2['real_name'].".doc_id as docid,location from ".$row2['real_name'];
				$queryA=$queryA." where ".$row2['real_name'].".TimeStamp>".$cabKey." and ".$row2['real_name'].".TimeStamp<=".$MaxCabKey.") t where RowNr BETWEEN ".$oldx." and ".$endX;
				$oldx=$x+1;
				//echo($queryA."\n");
				$folders=$DBDept->queryAll($queryA);
				//echo count($folders)."\n";
				//go through each folder and insert/update search table
				foreach ($folders as $folder){
					/*
				[docid] => 1
				[id] => 1
				[location] => client_files Accounts_Payable yrubpniztonl
				[subfolder] => 
				[filename] => 2.tif
					*/
					$metadataQry="select * from ".$row2['real_name']." where doc_id=".$folder['docid'];
					$metadataArr=$DBDept->queryAll($metadataQry);
					$IsDeleted = $metadataArr[0]['deleted'];
					unset($metadataArr[0]['doc_id']);
					unset($metadataArr[0]['location']);
					unset($metadataArr[0]['deleted']);
					unset($metadataArr[0]['timestamp']);
					$metadata=implode(";", $metadataArr[0]);
					echo "metadata:".$metadata."\n";
					$existQry="select * from search where type='folder' and cabinet_id=".$row2['departmentid']." and doc_id=".$folder['docid'];
					$existRecord=$DBSearch->queryAll($existQry);
					if (count($existRecord)>0) {
						//update
						//echo "update search\n";
						if ($IsDeleted) {
	echo "********DELETING*************\n";						
							$IsDeletedFolder[]=$folder['docid'];
							$updateQry="delete from search where cabinet_id=".$row2['departmentid']." and doc_id=".$folder['docid'];
	//echo $updateQry."\n";						
						} else {
							$updateQry="update search set fulltext_content='".$metadata."',lastupdated='".date('Y-m-d H:i:s')."', cabinet='".$row2['departmentname']."' where type='folder' and cabinet_id=".$row2['departmentid']." and doc_id=".$folder['docid'];
						}
//fwrite($fp,$updateQry."\n");
						$statis=$DBSearch->queryAll($updateQry);
					} else {
						//insert
						$insertQry="insert into search (type,cabinet_id,doc_id,document_id,file_id,lastupdated,cabinet,fulltext_content) values ('folder','".$row2['departmentid']."','".$folder['docid']."',-1,-1,'".date('Y-m-d H:i:s')."','".$row2['departmentname']."','".$metadata."')";
						$statis=$DBSearch->query($insertQry);
//fwrite($fp,$insertQry."\n");
					}
					
				}
			}
			$QueryMax="select Max([TimeStamp]) as ts from ".$row2['real_name'];
			$MaxCabKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxCabKeyArr);
			if (count($MaxCabKeyArr)) {
				$MaxCabKey='0x'.bin2hex($MaxCabKeyArr[0]['ts']);
			} else {
				$MaxCabKey='0x0000000000000000';
				echo "problem with ".$row2['real_name']."\n";
			}
			if ($MaxCabKey=='0x'){
				$MaxCabKey='0x0000000000000000';
			}
			$QueryS="update LastModifiedKey set LastUpdated='".$MaxCabKey."',UpdateDate='".date('Y-m-d H:i:s')."' where TableName='".$row2['real_name']."'";
			$results=$DBSearch->query($QueryS);
			//****************** end of folders ***********************************
			echo "//****************** start of files ***********************************\n";
			$QueryMax="select Max([TimeStamp]) as ts from ".$row2['real_name']."_files";
			$MaxFileKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxFileKeyArr);
			if (count($MaxFileKeyArr)) {
					$MaxFileKey='0x'.bin2hex($MaxFileKeyArr[0]['ts']);
			} else {
				$MaxFileKey='0x0000000000000000';;
				echo "problem with ".$row2['real_name']."_files\n";
			}
			if ($MaxFileKey=='0x'){
				$MaxFileKey='0x0000000000000000';
			}
			//echo $QueryMax."\n*".$MaxFileKey."*\n";
			//getting count to avoid memory and timeout problems
			$queryA="select count(*) as cnt from ".$row2['real_name']."_files";
			$queryA=$queryA." where ".$row2['real_name']."_files.TimeStamp>".$fileKey." and ".$row2['real_name']."_files.TimeStamp<=".$MaxFileKey."";
			$rescount=$DBDept->queryAll($queryA);
			echo($queryA."\nThe Count ".$rescount[0]['cnt']."\n");
			$oldx=0;
			for ($x=0;$x<$rescount[0]['cnt'];$x+=100000) {
				$endX=$x+=100000;
				$queryA="select docid,id,ocr_context,parent_filename from (";
				$queryA=$queryA."select ROW_NUMBER() OVER(order by ".$row2['real_name']."_files.TimeStamp) RowNr,doc_id as docid,id,ocr_context,parent_filename from ".$row2['real_name']."_files";
				$queryA=$queryA." where filename is not null and ".$row2['real_name']."_files.TimeStamp>".$fileKey." and ".$row2['real_name']."_files.TimeStamp<=".$MaxFileKey.") t where RowNr BETWEEN ".$oldx." and ".$endX;
				$oldx=$x+1;
				//echo($queryA."\n");
				$files=$DBDept->queryAll($queryA);
				//echo count($files)."\n";
				//go through each folder and insert/update search table
				foreach ($files as $file){
					$metadataQry="select * from ".$row2['real_name']."_files where id=".$file['id'];
					$metadataArr=$DBDept->queryAll($metadataQry);
					$metadata="Created by ".$metadataArr[0]['who_indexed']." Notes:".$metadataArr[0]['notes']."||".$metadataArr[0]['ocr_context']." || ".$file['parent_filename'];
					$dateCreated = $metadataArr[0]['date_created'];
					$IsDeleted = $metadataArr[0]['deleted'];
					$badchar = array("'", "\"");
					$metadata = str_replace($badchar, "", $metadata);
					echo "metadata:".$metadata."\n";
					$existQry="select * from search where type='file' and cabinet_id=".$row2['departmentid']." and doc_id=".$file['docid']." and file_id=".$file['id'];
					$existRecord=$DBSearch->queryAll($existQry);
					if (count($existRecord)>0) {
						//update
						//echo "update search: ".$existQry." Is Deleted???".$IsDeleted."\n";
						if ($IsDeleted || IsDeleted($file['docid'],$file['id'],$row2['real_name'],$DBDept)) {
							$updateQry="delete from search where type='file' and cabinet_id=".$row2['departmentid']." and doc_id=".$file['docid']." and file_id=".$file['id'];
						} else {
							$updateQry="update search set cabinet='".$row2['departmentname']."',fulltext_content='".$metadata."',info='".$file['parent_filename']."',lastupdated='".$dateCreated."' where type='file' and cabinet_id=".$row2['departmentid']." and doc_id=".$file['docid']." and file_id=".$file['id'];
						}
//fwrite($fp,$updateQry."\n");
						$statis=$DBSearch->queryAll($updateQry);
					} else {
						//insert
						$insertQry="insert into search (type,cabinet_id,doc_id,document_id,file_id,lastupdated,cabinet,info,fulltext_content) values ('file','".$row2['departmentid']."','".$file['docid']."',-1,'".$file['id']."','".$dateCreated."','".$row2['departmentname']."','".$file['parent_filename']."','".$metadata."')";
						$statis=$DBSearch->query($insertQry);
//fwrite($fp,$insertQry."\n");
					}
					
				}
			}
			$QueryMax="select Max([TimeStamp]) as ts from ".$row2['real_name']."_files";
			$MaxFileKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxFileKeyArr);
			if (count($MaxFileKeyArr)) {
				$MaxFileKey='0x'.bin2hex($MaxFileKeyArr[0]['ts']);
			} else {
				$MaxFileKey='0x0000000000000000';
				echo "problem with ".$row2['real_name']."_files\n";
			}
			if ($MaxFileKey=='0x'){
				$MaxFileKey='0x0000000000000000';
			}
			$QueryS="update LastModifiedKey set LastUpdated='".$MaxFileKey."',UpdateDate='".date('Y-m-d H:i:s')."' where TableName='".$row2['real_name']."_files'";
			$results=$DBSearch->query($QueryS);
			//************* end of files **********************************
			echo "//****************** start of DocType ***********************************\n";
			$QueryMax="select Max([TimeStamp]) as ts from document_field_value_list";
			$MaxdocTypeKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxdocTypeKeyArr);
			if (count($MaxdocTypeKeyArr)) {
					$MaxdocTypeKey='0x'.bin2hex($MaxdocTypeKeyArr[0]['ts']);
			} else {
				$MaxdocTypeKey='0x0000000000000000';;
				echo "problem with document_field_value_list\n";
			}
			if ($MaxdocTypeKey=='0x'){
				$MaxdocTypeKey='0x0000000000000000';
			}
			//echo $QueryMax."\n*".$MaxdocTypeKey."*\n";
			//getting count to avoid memory and timeout problems
			$queryA="select count(*) as cnt from document_field_value_list";
			$queryA=$queryA." where document_field_value_list.TimeStamp>".$docTypeKey." and document_field_value_list.TimeStamp<=".$MaxdocTypeKey."";
			$rescount=$DBDept->queryAll($queryA);
			echo($queryA."\nThe Count ".$rescount[0]['cnt']."\n");
			$oldx=0;
			for ($x=0;$x<$rescount[0]['cnt'];$x+=100000) {
				$endX=$x+=100000;
				$queryA="select id from (";
				$queryA=$queryA."select ROW_NUMBER() OVER(order by document_field_value_list.TimeStamp) RowNr,id from document_field_value_list";
				$queryA=$queryA." where document_field_value_list.TimeStamp>".$docTypeKey." and document_field_value_list.TimeStamp<=".$MaxdocTypeKey.") t where RowNr BETWEEN ".$oldx." and ".$endX;
				$oldx=$x+1;
				//echo($queryA."\n");
				$files=$DBDept->queryAll($queryA);
				//echo count($files)."\n";
				//go through each folder and insert/update search table
				foreach ($files as $file){
					//need to get all indexes associated with the one record that has changed starting in  document_field_value_list and grabbing the document_defs_list_id to get the proper table 
					$metadataQry="select * from document_field_value_list where id=".$file['id'];
					$docTypeIndex=$DBDept->queryAll($metadataQry);
					$metadataQry= "select * from document_field_value_list where document_id=".$docTypeIndex[0]['document_id']." and document_defs_list_id=".$docTypeIndex[0]['document_defs_list_id'];
					$metadataArr=$DBDept->queryAll($metadataQry);
					//echo $metadataQry."\n";
					//print_r($metadataArr);
					$metadata="";
					foreach ($metadataArr as $docTypeRecord) {
						$metadata.=$docTypeRecord['document_field_value']."; ";
					}
					$badchar = array("'", "\"");
					$metadata = str_replace($badchar, "", $metadata);
					//get doc_id file_id and document_id
					$document_defs_list_id=$metadataArr[0]['document_defs_list_id'];
					$metadataQry="select * from document".$document_defs_list_id." where cab_name='".$row2['real_name']."' and id = ".$metadataArr[0]['document_id'];
					//echo $metadataQry."\n";
					$metadataArr=$DBDept->queryAll($metadataQry);
					if (count($metadataArr)>0) {
						print_r($metadataArr);
						echo "metadata:".$metadata."\n";
						$IsDeleted = $metadataArr[0]['deleted'];
						//get the doctype name
						$docTypeNameQry="select document_type_name from document_type_defs where document_table_name='document".$document_defs_list_id."'";
						//echo $docTypeNameQry."\n";
						$docTypeNameArr=$DBDept->queryAll($docTypeNameQry);
						print_r($docTypeNameArr);
						$existQry="select * from search where type='docType' and cabinet_id=".$row2['departmentid']." and doc_id=".$metadataArr[0]['doc_id']." and file_id=".$metadataArr[0]['file_id']." and document_id=".$metadataArr[0]['id'];
						//echo $existQry."\n";
						$existRecord=$DBSearch->queryAll($existQry);
						if (count($existRecord)>0) {
							//update
							//echo "update search\nIsDeleted DocType:".$IsDeleted."\n";
							if ($IsDeleted || IsDeleted($metadataArr[0]['doc_id'],$metadataArr[0]['file_id'],$row2['real_name'],$DBDept)) {
								$updateQry="delete from search where  type='docType' and cabinet_id=".$row2['departmentid']." and doc_id=".$metadataArr[0]['doc_id']." and file_id=".$metadataArr[0]['file_id']." and document_id=".$metadataArr[0]['id'];
							} else {
								$updateQry="update search set info='".$docTypeNameArr[0]['document_type_name']."',cabinet='".$row2['departmentname']."',fulltext_content='"."Created by ".$metadataArr[0]['created_by']."||".$metadata."',lastupdated='".$metadataArr[0]['date_modified']."' where  type='docType' and cabinet_id=".$row2['departmentid']." and doc_id=".$metadataArr[0]['doc_id']." and file_id=".$metadataArr[0]['file_id']." and document_id=".$metadataArr[0]['id'];
							}
//fwrite($fp,$updateQry."\n");							
							$statis=$DBSearch->queryAll($updateQry);
						} else {
							//insert
							$insertQry="insert into search (type,cabinet_id,doc_id,document_id,file_id,lastupdated,cabinet,info,fulltext_content) values ('docType','".$row2['departmentid']."','".$metadataArr[0]['doc_id']."',".$metadataArr[0]['id'].",'".$metadataArr[0]['file_id']."','".$metadataArr[0]['date_modified']."','".$row2['departmentname']."','".$docTypeNameArr[0]['document_type_name']."','"."Created by ".$metadataArr[0]['created_by']."||".$metadata."')";
							$statis=$DBSearch->query($insertQry);
//fwrite($fp,$insertQry."\n");
						}
					}				
				}
			}
			//************* end of docType **********************************
		}
		echo "//*************** reset for docType *******************************\n";
			$QueryMax="select Max([TimeStamp]) as ts from document_field_value_list";
			$MaxdocTypeKeyArr=$DBDept->queryAll($QueryMax);
			//print_r($MaxdocTypeKeyArr);
			if (count($MaxdocTypeKeyArr)) {
				$MaxdocTypeKey='0x'.bin2hex($MaxdocTypeKeyArr[0]['ts']);
			} else {
				$MaxdocTypeKey='0x0000000000000000';
				//echo "problem\n";
			}
			if ($MaxdocTypeKey=='0x'){
				$MaxdocTypeKey='0x0000000000000000';
			}
			$QueryS="update LastModifiedKey set LastUpdated='".$MaxdocTypeKey."',UpdateDate='".date('Y-m-d H:i:s')."' where TableName='document_field_value_list'";
			$results=$DBSearch->query($QueryS);
		echo "//*************** end reset for docType *******************************\n";
	}
	$DBDept->disconnect();
	$DBSearch->disconnect();
}
echo "end";
fwrite($fp,"Ended"."\n");
$mtime = microtime(); 
$mtime = explode(" ", $mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 
$totalhours = $totaltime/3600;
fwrite($fp,'This ran in ' .duration($totaltime). ' .'."\n");
fclose($fp);
echo 'This ran in ' .duration($totaltime). ' .';
function duration($secs) 
{ 
    $vals = array('w' => (int) ($secs / 86400 / 7), 
                  'd' => $secs / 86400 % 7, 
                  'h' => $secs / 3600 % 24, 
                  'm' => $secs / 60 % 60, 
                  's' => $secs % 60); 

    $ret = array(); 

    $added = false; 
    foreach ($vals as $k => $v) { 
        if ($v > 0 || $added) { 
            $added = true; 
            $ret[] = $v . $k; 
        } 
    } 

    return join(' ', $ret); 
} 
function IsDeleted($doc_id,$file_id,$cab,$DBDept) {
	$IsDeleted=0;
	$query="select * from ".$cab." where doc_id=".$doc_id;
	$resultArr=$DBDept->queryAll($query);
	if (count($resultArr)>0) {
		$IsDeleted = $resultArr[0]['deleted'];
		//echo "is folder deleted?".$IsDeleted."\n".$query."\n";
		if ($IsDeleted) return $IsDeleted;
		if ($file_id > 0) {
			$query="select * from ".$cab."_files where id=".$file_id;
			$resultArr1=$DBDept->queryAll($query);
			if (isset($resultArr1[0])) $IsDeleted = $resultArr1[0]['deleted'];
			//echo "is file deleted?".$IsDeleted."\n".$query."\n";
			if ($IsDeleted) return $IsDeleted;
			//check document type
			$subfolder=$resultArr1[0]['subfolder'];
			if ($subfolder) {
				$query="select * from ".$cab."_files where filename is null and subfolder='".$resultArr1[0]['subfolder']."'";
				//echo $query."\n";
				$resultArr2=$DBDept->queryAll($query);
				if (isset($resultArr2[0]) && $resultArr2[0]['document_table_name']) {
					$query="select * from ".$resultArr2[0]['document_table_name']." where id=".$resultArr2[0]['document_id'];
					//echo $query;
					//print_r($DBDept);
					$resultArr3=$DBDept->queryAll($query);
					if (count($resultArr3)>0)
					{				
						$IsDeleted = $resultArr3[0]['deleted'];
					}
				} else {
					$IsDeleted = 1;
				}
			}
			
		}
	}
	return $IsDeleted;
}

?>
