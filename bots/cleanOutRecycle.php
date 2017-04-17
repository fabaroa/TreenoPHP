<?php
chdir('/var/www/html/bots');
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../delete/delete.php';
include_once '../lib/licenseFuncs.php';
$db_doc=getDbObject('docutron');
$select = 'select * from licenses';
$res = $db_doc->queryAll( $select );
foreach( $res as $row ){
	$dep = $row['real_department'];
	//get settings for cleaning out recycle bin k='cleanRecycleBin' value = "2 m" or "2 w" or "2 d" number + months(m), weeks(w), days(d)
	$select="select * from settings where k='cleanRecycleBin' and department='".$dep."'";
	$result = $db_doc->queryAll( $select );
	if (count($result)>0){
		$timeIncrement = explode(" ", $result[0]['value']);
		$today=time();
		if (isset($timeIncrement[1]))
		{
			switch ($timeIncrement[1]) {
				case 'm':
				  $time2empty = strtotime("-".$timeIncrement[0]." month");
				  break;
				case 'w':
				  $time2empty = strtotime("-".$timeIncrement[0]." week");
				  break;
				case 'd':
				  $time2empty = strtotime("-".$timeIncrement[0]." day");
				  break;
				default:
				  $time2empty = "";
			}
		}
		else
		{
		  $time2empty = "";
		}
		
		$db_object=getDbObject($dep);
		echo $dep;
		$select2 = 'select * from departments';
		$res2 = $db_object->queryAll( $select2 );
		foreach( $res2 as $row2 )
		{
			$cab=$row2['real_name'];
			/*
			check audit date and do a date compare with settings
			2010-07-09 11:00:08 Cabinet: HRH_load_test_classic Cabinet marked for deletion 
			*/		
			if ($row2['deleted']==1) 
			{
				$select="select * from audit where action='Cabinet marked for deletion' and info like '%".$cab."%' order by datetime DESC";
				$dateDeleted = $db_object->queryAll($select);
				if ($time2empty >= strtotime($dateDeleted[0]['datetime'])) 
				{
					echo "Delete ".$dateDeleted[0]['datetime']." cab:".$dep." : ".$cab."\n";
					$delObj = new filesToDelete($dep,$cab,"","", "","","",1, $db_object, $db_doc);
					$delObj->delete();
				}
			} 
			else 
			{
	
				$select="select doc_id from ".$cab." where deleted=1";
				$doc_ids = $db_object->queryAll($select);
				foreach($doc_ids as $doc_id)
				{
					echo "now looking at Doc_id:".$doc_id['doc_id']."\n";
					$select="select * from audit where action='deleted folder' and info like '%Cabinet: ".$cab."%Doc ID: ".$doc_id['doc_id']."%' order by datetime DESC";
					$dateDeleted = $db_object->queryAll($select);
					if (isset($dateDeleted[0]) and $time2empty >= strtotime($dateDeleted[0]['datetime'])) 
					{
						fwrite($fp, "Delete *".$dateDeleted[0]['datetime']."* folder:".$dep." : ".$cab." : ".$doc_id['doc_id']."\n");
						$delObj = new filesToDelete($dep,$cab,$doc_id['doc_id'],"", "","","",1, $db_object, $db_doc);
						$delObj->delete();
					}
				}
				$doc_id="";
				$select="select doc_id,id,subfolder,parent_filename,date_to_delete from ".$cab."_files where deleted=1";
				$ids = $db_object->queryAll($select);
				foreach($ids as $id){
					$fileID=$id['id'];
					$doc_id=$id['doc_id'];
					$tab=$id['subfolder'];
					$filename=$id['parent_filename'];
					if ($id['date_to_delete']=="0000-00-00 00:00:00") 
					{
						//add an update for today date time
						$select="update ".$cab."_files set date_to_delete='".date("Y-m-d G:i:s")."' where id=".$fileID;
						echo "updating date:".$select."\n";
						$db_object->queryAll($select);
					} 
					else 
					{
						if ($time2empty >= strtotime($id['date_to_delete'])) 
						{
							fwrite($fp, "Delete ".$id['date_to_delete']." file:".$dep." : ".$cab." : ".$doc_id['doc_id']." : ".$tab." : ". $filename." : ".$fileID."\n");
							$delObj = new filesToDelete($dep,$cab,$doc_id,$tab, $filename,$fileID,"",1, $db_object, $db_doc);
							$delObj->delete();
						}
					}
				}
			}
		}
		$db_object->disconnect();
	}
}

	echo "done for sure\n";
?>