<?php
include '../db/db_common.php';
include '../centera/centera.php';
$db_dep = getDbObject( 'client_files' );
$cabArr = $db_dep->queryAll( 'select real_name from departments');
//print_r( $cabArr );
foreach( $cabArr as $cab ){
	$s = "select doc_id, location from {$cab['real_name']}";
	$folderArr = $db_dep->queryAll($s);
	foreach( $folderArr as $folder ){
		//print_r( $folder );
		$s = "select subfolder,filename,ca_hash from {$cab['real_name']}_files ";
		$s.= "where ca_hash is not null and ca_hash!='' and doc_id = {$folder['doc_id']}";
		$fileArr = $db_dep->queryAll( $s );
//print_r( $fileArr );
		$filebase = '/quorum'."/".implode( '/', explode( ' ', $folder['location']));
//		echo $filebase."\n";
		foreach( $fileArr as $file ){
			//print_r( $file );
			if( centerr( $file['ca_hash'] )){
				echo "HASH ERROR with '{$file['ca_hash']}'\n";
			}else{
		//		echo "HASH IS GOOD {$file['ca_hash']}\n";
//echo $filebase."/".$file['filename']."\n";
				if( file_exists( $filebase.'/'.$file['filename'] ) ){
					echo $filebase."/".$file['filename']." needs to be deleted\n";
					centeraunlink($filebase.'/'.$file['filename'],$file['ca_hash'],'client_files',$cab['real_name']);
				}else{
//					echo "FILE: {$file['filename']} is correctly on the centera\n";
				}
			}
//echo "\n";
		}
	}
}
?>
