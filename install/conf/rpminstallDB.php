#!/usr/bin/php -q
<?php
$db_password='Doc-4tron';
$db_username='root';
$db_engine='mysql';
$db_host='localhost';

$t = date("D-M-j-G-i-s-Y");
$output = `mysqlshow -u $db_username --password="$db_password" docutron 2>&1`;
if(!$output || strpos($output, 'Unknown database') !== false) 
{
	`mysqladmin -u $db_username --password=$db_password create docutron`;
	`mysql -u $db_username --password=$db_password -e 'source /opt/docutron/share/sql/docutron.sql' docutron`;

	$output = `mount`;
	$str = explode("\n",trim($output));
	for($i=0;$i<sizeof($str);$i++)
	{
		$result = explode("[ ]",$str[$i]);
		if( substr_count($result[0],"/dev/") > 0 && $result[2] == "/var/opt/docutron"){
			$mapped_to = $result[0];
			break;
		}
		elseif( substr_count($result[0],"/dev/") > 0 && $result[2] == "/var/opt"){
			$drive = $result[2];
			$mapped_to = $result[0];
		}
		elseif( substr_count($result[0],"/dev/") > 0 && $result[2] == "/var"){
			$drive = $result[2];
			$mapped_to = $result[0];
		}
		elseif( substr_count($result[0],"/dev/") > 0 && $result[2] == "/" && $drive != "/var" ){
			$drive = $result[2];
			$mapped_to = $result[0];
		}
	} 
	$output = `df -l $mapped_to`;
	$str = explode( "[ ]",$output );
	$j =0;
	for($i=0;$i<sizeof($str);$i++)
	{
		if( trim($str[$i]) != "" )
		{
			$arr[$j] = $str[$i]; 
			$j++;
		}
	}
	$space = $arr[9];
	$space *= 1024;
	$space = round(($space*.95),-4);	
	`mysql -u $db_username --password=$db_password --database=docutron -e "INSERT INTO quota SET drive=\"$mapped_to\", max_size=$space, size_used=$space"`;
	`mysql -u $db_username --password=$db_password --database=docutron -e "UPDATE licenses SET quota_allowed=$space, quota_used=243837"`;
}
else
{
}
$output = `mysqlshow -u $db_username --password=$db_password "client\_files" 2>&1`;
if(!$output || strpos($output, 'Unknown database') !== false) 
{
	`mysqladmin -u $db_username --password=$db_password create client_files`;
	`mysql -u $db_username --password=$db_password -e 'source /opt/docutron/share/sql/client_files.sql' client_files`;
}
else
{
}
$output = `mysqlshow -u $db_username --password=$db_password docutron language 2>&1`;
if(strpos($output, 'Cannot list columns') !== false) 
{
	`mysql -u $db_username --password=$db_password -e 'source /opt/docutron/share/sql/langdump.sql' docutron`;
}
else
{
}
?>
