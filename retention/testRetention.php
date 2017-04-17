<?php
require_once('parseRetention.php');
$file = '/var/www/tools/retention.ini';
if(file_exists($file))
{	
	$content = file($file);
	$numlines = count($content);
	
	for($i=0; $i < $numlines; $i++)
	{
		$retention = new RetentionParser('client_files', $content[$i]);
		$retention->parse();
	}	
}