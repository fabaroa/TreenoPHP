<?PHP
include_once 'getBarcode.php';

//$xmlStr = "<barcode><department value='client_files' /><cabinetID value='1' /><cabinet value='Accounts_Payable' /><terms><term index='voucher' value='213999999999' /><term index='two_2' value='' /><term index='three_3' value='test' /><term index='four_4' value='' /><term index='date' value='' /><term index='la_de' value='' /><term index='more' value='' /><term index='than_8' value='' /><term index='adding_indices' value='' /><term index='hwllo' value='' /><term index='world' value='' /></terms><deletebc value='1' /><scanformat value='stif' /><sendimage value='1' /><user value='joe' /><compress value='0' /><searchtype value='searchcreate' /><getTabsBC value='1' /></barcode>";

$xmlStr = '<barcode><department value="client_files" /><cabinetID value="9" /><cabinet value="Membership" /><terms><term index="member_number" value="874221460" /><term index="last_name" value="Aguilar" /><term index="first_name" value="Alejandria" /><term index="status" value="A" /><term index="email_address" value="Aguilar4realestate@gmail.com" /></terms><deletebc value="1" /><scanformat value="MTIFF" /><sendimage value="1" /><user value="admin" /><compress value="0" /><searchtype value="searchcreate" /><getTabsBC value="1" /></barcode>';
$username = "admin";
$domDoc = new DOMDocument();
if($domDoc->loadXML($xmlStr)) {
	$barcodeObj = new webServicesBarcode($username, $domDoc);
	print_r($barcodeObj);
	echo "\n";
	print_r($barcodeObj->getRetXML());
}else{
	echo "xmlstr failed";
}

?>
