<?php
function getWhiteLabel($enArr,$user, $db_doc,$db_dept) {
	$gblStt = new GblStt('client_files', $db_doc);
	$whiteLabel = $gblStt->get('whiteLabel');
	if(!$whiteLabel) {
		$whiteLabel = 'Treeno Software';
		$gblStt->set('whiteLabel',$whiteLabel);
	}

	$xmlObj = new xml('ENTRY');
	$xmlObj->createKeyAndValue('FUNCTION','recXML(XML)');
	$xmlObj->createKeyAndValue('LABEL',$whiteLabel);
	$xmlObj->setHeader();
}
?>
