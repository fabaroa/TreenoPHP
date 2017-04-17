<?php //$Id: updateDocTypeSearch.php 15114 2015-01-22 20:19:58Z root $
echo "start\n";
require_once '../db/db_common.php';
echo "start<br>\n";
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
$i=0;
$u=0;
$fp=fopen("/treeno/logs/updateDocTypesearch.log","a");
fwrite($fp,"*******************************************************************************\n".date("Y-m-d H:i:s")."\n");
$db = getDbObject('docutron');
$whr = " where ";
if (file_exists('/treeno/logs/excludeFT.txt')) {
	$excludedepts = file('/treeno/logs/excludeFT.txt');
	foreach ($excludedepts as $excludedept)
	{
		$whr .= " real_department != '".trim($excludedept)."' and";
	}
	$select = "select * from licenses ".rtrim($whr,"and")." order by real_department";
} else {
	$select = 'select * from licenses order by real_department';
}
echo $select."\n";
fwrite($fp,$select." ".date("Y-m-d H:i:s")."\n");
$res = $db->queryAll( $select );
// for each department
foreach( $res as $row ){
	$department = $row['real_department'];
	echo "*******".$department."**********\n";
	fwrite($fp,$department." ".date("Y-m-d H:i:s")."\n");
	$searchDept = $row['real_department']."_search";
	$DBDept = getDbObject($department);
	$DBSearch = getDbObject($searchDept);
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
	$QueryS="select LastUpdated from LastModifiedKey where TableName='documentTypeValues'";
	$docTypeKeyArr=$DBSearch->queryAll($QueryS);
	//echo $QueryS."\n";
	//print_r($docTypeKeyArr);
	if (count($docTypeKeyArr)) {
		$docTypeKey=$docTypeKeyArr[0]['lastupdated'];
	} else {
		$docTypeKey='0x0000000000000000';
		$QueryS="insert into LastModifiedKey (TableName,LastUpdated,UpdateDate) VALUES ('documentTypeValues','".$docTypeKey."','".date('Y-m-d H:i:s')."')";
		$results=$DBSearch->query($QueryS);
	}
	echo "ready to create\n";
	// does docTypeValues exist?
	$findTable="SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'docTypeValues'";
	echo "table exist\n";
	$tableres = $DBSearch->queryOne($findTable);
	echo $tableres."table exist\n";
	if ($tableres == 0)
	{
		fwrite($fp,"\tNeeded to create docTypeValues in ".$searchDept."\n");
		$createTable = "CREATE TABLE [dbo].[docTypeValues]([docTypeValuesID] [int] IDENTITY(1,1) NOT NULL,[document_id] [int] NULL,[document_table_name] [varchar](255) NULL,[docTypeValues] [varchar](max) NULL,[timestamp] [timestamp] NULL) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]";
		echo "before table created\n";
		$created = $DBSearch->query($createTable);
		
		fwrite($fp,"\tCreated docTypeValues in ".$searchDept."\n".print_r(dbErr($created),true));
	}
	echo "table check\n";
	//get only doctypes with indexes
	$docTypeListQ="SELECT distinct [document_table_name],SUBSTRING(document_table_name,len('document')+1,len(document_table_name)-len('document')) as docdef_id FROM [document_field_defs_list]";
	echo $docTypeListQ."\n";
	$docTypeList=$DBDept->queryAll($docTypeListQ);
	foreach ($docTypeList as $docTypeTable)
	{
		$document_table_name = $docTypeTable["document_table_name"];
		$document_defs_list_id =  $docTypeTable["docdef_id"];
		//get the indexes
		$getfieldsQ = "SELECT id FROM document_field_defs_list where document_table_name='".$document_table_name."' order by ordering";
		$getfields=$DBDept->queryAll($getfieldsQ);
		$listOfFieldIDs = array();
		foreach ($getfields as $fieldID)
		{
			$listOfFieldIDs[] = $fieldID["id"];
		}
		print_r($listOfFieldIDs);
		$insert = "INSERT INTO [docTypeValues] ([document_table_name],[document_id],[docTypeValues]) VALUES ('".$document_table_name."','";
		// what values have been updated??? how do I get that
		$allrecordsQ = "select distinct ".$document_table_name.".id from ".$document_table_name.",document_field_value_list where document_id=".$document_table_name.".id and document_field_value_list.TimeStamp>".$docTypeKey." and document_field_value_list.TimeStamp<=".$MaxdocTypeKey;
		$allrecords = $DBDept->queryAll($allrecordsQ);
		//fwrite($fp,$allrecordsQ."\n");
		foreach ($allrecords as $record)
		{
			fwrite($fp,"\t".$document_table_name."\n");
			$docTypeValues="";
			$document_id = $record["id"];
			foreach ($listOfFieldIDs as $fieldID)
			{
				$select = "SELECT document_field_value FROM document_field_value_list where document_field_defs_list_id='".$fieldID."' and document_defs_list_id='".$document_defs_list_id."' and document_id=".$document_id;
				echo $select."\n";
				$value = $DBDept->queryOne($select);
				$docTypeValues .= $value."\t";
			}
			$setValues = $insert.$document_id."','".RTRIM($docTypeValues,"\t")."')";
			echo $setValues."\n";
			$findcurrentQ = "select docTypeValuesID from [docTypeValues] where document_table_name='".$document_table_name."' and document_id = '".$document_id."'";
			$findcurrent = $DBSearch->queryOne($findcurrentQ);
			if ($findcurrent)
			{
				echo "Update\n";
				++$u;
				$insertcurrent = "update [docTypeValues] set docTypeValues='".RTRIM($docTypeValues,"\t")."' where docTypeValuesID='".$findcurrent."'";
				$DBSearch->queryAll($insertcurrent);
			}
			else
			{
				echo "Insert\n";
				++$i;
				$DBSearch->queryAll($setValues);
			}
		}
	}
	$QueryS="update LastModifiedKey set LastUpdated='".$MaxdocTypeKey."',UpdateDate='".date('Y-m-d H:i:s')."' where TableName='documentTypeValues'";
	$results=$DBSearch->query($QueryS);
	$DBDept->disconnect();
	$DBSearch->disconnect();
	fwrite($fp,"\tinserted ".$i." and updated ".$u."\n");
	$i=0;
	$u=0;
}
echo "end\n";
fwrite($fp,"Ended"."\n");
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
$totalhours = $totaltime/3600;
fwrite($fp,"This ran in ".duration($totaltime).".\n");
fclose($fp);
echo 'This ran in ' .duration($totaltime). ' . and inserted '.$i.' and updated '.$u."\n";
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
?>