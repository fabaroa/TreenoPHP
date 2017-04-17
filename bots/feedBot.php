<?php
include_once '../db/db_engine.php';
include_once '../lib/feedStore.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';

$db_doc = getDbObject('docutron');
$settings = new GblStt('client_files', $db_doc);
$feeds = unserialize(base64_decode($settings->get('feeds')));
if($feeds) {
	foreach( $feeds as $feed )
	{
		$feed->absorbFeed();	
	}
}
$db_doc->disconnect ();

//if objects found run absorbFeed function from each object
?>
