<?php
require_once 'HTTP/Client.php';
require_once '../lib/settings.php';
if (!empty ($DEFS['CUSTOM_LIB'])) {
	require_once $DEFS['CUSTOM_LIB'];
}

function getSagRow($cabinet, $value, $department) {
	global $DEFS;
	$sysSettings = getSagWsSettings ($department, $DEFS);
	if (isset ($sysSettings['translated cabinets'])) {
		$transCabs = array_flip($sysSettings['translated cabinets']);
	} else {
		$transCabs = array ();
	}
	if(isset ($transCabs[$cabinet])) {
		$cabinet = $transCabs[$cabinet];
	} else {
		return array();
	}

	$keptValues = array ();
	$sagRecord = array ();

	$xmlPart = getQueryPart($cabinet, $value);
	if (isset ($_SESSION['SAG_WS_MAPPING'])) {
		if (isset ($_SESSION['SAG_WS_MAPPING'][$department])) {
			$mapArr = $_SESSION['SAG_WS_MAPPING'][$department];
		} else {
			$mapArr = array ();
		}
	} else {
		if (isset ($DEFS['SAG_MAP_INI'])) {
			$_SESSION['SAG_WS_MAPPING'] = parse_ini_file($DEFS['SAG_MAP_INI'], true);
			$mapArr = $_SESSION['SAG_WS_MAPPING'][$department];
		} else {
			$mapArr = array ();
		}
	}
	$xmlResponse = performQuery($xmlPart, $mapArr);
	if($xmlResponse) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$xmlDoc = domxml_open_mem($xmlResponse);
		} else {
			$xmlDoc = new DOMDocument ();
			$xmlDoc->loadXML($xmlResponse);
		}
		performTrans($xmlDoc, $sysSettings[$cabinet], $keptValues, $sagRecord);
		while(count($keptValues)) {
			$newKept = array ();
			foreach($keptValues as $nextSection => $value) {
				$tmpArr = explode('//', $nextSection);
				$nextCab = $tmpArr[1];
				$xmlPart = getQueryPart($nextCab, $value);
				$xmlResponse = performQuery($xmlPart, $mapArr);
				if($xmlResponse) {
					if (substr (PHP_VERSION, 0, 1) == '4') {
						$xmlDoc = domxml_open_mem($xmlResponse);
					} else {
						$xmlDoc = new DOMDocument ();
						$xmlDoc->loadXML($xmlResponse);
					}
					performTrans($xmlDoc, $sysSettings[$nextSection],
						$newKept, $sagRecord);
				}
			}
			$keptValues = $newKept;
		}
		if (function_exists('customFixSagWsRow')) {
			return customFixSagWsRow ($department, $cabinet, $sagRecord);
		} else {
			return $sagRecord;
		}
	} else {
		return array ();	
	}
}

function getSagWsSettings($department, $DEFS) {
	$sysSettings = array ();
	if (isset ($DEFS['MAPPING_DIR'])) {
		$sagIni = $DEFS['MAPPING_DIR'] . '/' . $department .
			'/sagWS.ini';
		if (file_exists ($sagIni)) {
			$sysSettings = parse_ini_file ($sagIni, true);
		}
	}
	if (!$sysSettings) {
		if (isset ($DEFS['SAG_WS_INI'])) {
			$sysSettings = parse_ini_file($DEFS['SAG_WS_INI'], true);
		}
	}
	return $sysSettings;
}

function performTrans($xmlDoc, $transList, &$keptValues, &$sagRecord) {
	if (substr(PHP_VERSION, 0, 1) == '4') {
		$files = $xmlDoc->get_elements_by_tagname('File');
		if(count($files) > 0) {
			if($files[0]->get_attribute('sagfile') == 'WEBSERVICE.ERRORS') {
				return;
			}
		} else {
			return;
		}
		foreach($transList as $xmlTag => $docField) {
			if($docField != '/PRIOR/') {
				$tmpEls = $xmlDoc->get_elements_by_tagname($xmlTag);
				$control = explode('//', $docField);
				if(count($tmpEls)) {
					if(count($control) == 3) {
						if($control[0] == 'KEEP') {
							$keptValues[$control[1].'//'.$control[2]] = 
								$tmpEls[0]->get_content();
						}
					} elseif($xmlTag == 'Item') {
						$sagRecord[$docField] = $tmpEls[0]->get_attribute('sagitem');
					} elseif(strpos($xmlTag, 'Dt') !== false or strpos ($docField, 'date') !== false) {
						$sagRecord[$docField] = makeSagittaWSDate($tmpEls[0]->get_content());
					} else {
						$sagRecord[$docField] = $tmpEls[0]->get_content();
					}
				} elseif($control[0] != 'KEEP') {
					$sagRecord[$docField] = '';
				}
			}
		}
	} else {
		$files = $xmlDoc->getElementsByTagName('File');
		if(count($files) > 0) {
			$files = $files->item(0);
			if($files->getAttribute('sagfile') == 'WEBSERVICE.ERRORS') {
				return;
			}
		} else {
			return;
		}
		foreach($transList as $xmlTag => $docField) {
			if($docField != '/PRIOR/') {
				$tmpEls = $xmlDoc->getElementsByTagName($xmlTag);
				$control = explode('//', $docField);
				if($tmpEls->length > 0) {
					$tmp = $tmpEls->item(0);
					if(count($control) == 3) {
						if($control[0] == 'KEEP') {
							$keptValues[$control[1].'//'.$control[2]] = 
								$tmp->nodeValue;
						}
					} elseif($xmlTag == 'Item') {
						$sagRecord[$docField] = $tmp->getAttribute('sagitem');
					} elseif(strpos($xmlTag, 'Dt') !== false) {
						$sagRecord[$docField] = makeSagittaWSDate($tmp->nodeValue);
					} else {
						$sagRecord[$docField] = $tmp->nodeValue;
					}
				} elseif($control[0] != 'KEEP') {
					$sagRecord[$docField] = '';
				}
			}
		}
	}
}

function getQueryPart($cabinet, $value) {
	global $DEFS; //added from molyneaux Insurance
	$xmlPart = '';
	$value = htmlentities($value);
	switch($cabinet) {
		case 'CLIENTS':
			$xmlPart = <<<XML
<Access statement="SELECT CLIENTS *CRITERIA* WITH A2 = \\$value\\"/>
XML;
			break;
		case 'CLIENTS2':
			$xmlPart = <<<XML
<Files>
	<File name="CLIENTS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'POLICIES':
			$xmlPart = <<<XML
<Files>
	<File name="POLICIES">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'INSURORS':
			$xmlPart = <<<XML
<Files>
	<File name="INSURORS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;

		case 'INSURORS2': //added from code at Molyneuax Insurance
			$xmlPart = <<<XML
		<Access statement="SELECT INSURORS *CRITERIA* WITH A2 = \\$value\\"/>
XML;
		break;

		case 'COVERAGES':
			$xmlPart = <<<XML
<Files>
	<File name="COVERAGES">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'MEMOS':
			$xmlPart = <<<XML
<Files>
	<File name="MEMOS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'LOSSES':
			$xmlPart = <<<XML
<Files>
	<File name="LOSSES">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'ADDITIONAL.INTERESTS':
			$xmlPart = <<<XML
<Files>
	<File name="ADDITIONAL.INTERESTS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
		case 'APP.SUBMISSIONS':
			$xmlPart = <<<XML
<Files>
	<File name="APP.SUBMISSIONS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
			break;
//from here to the end case was taken from sagWS.php on MolyNeaux
		case 'PAYABLES2':
			$value = "3*L^W*398026";
			//      $value = htmlentities($value);
			$xmlPart = <<<XML
<Access statement="SELECT PAYABLES *CRITERIA* WITH A0 = \\[3*L]\\"/>
XML;
/*
<Files>
	<File name="PAYABLES">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
*/
		break;

		case 'PAYABLES':
			$xmlPart = <<<XML
<Files>
	<File name="PAYABLES">
		<Items>
 			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
		break;

		case 'VENDORS':
		$xmlPart = <<<XML
<Files>
	<File name="VENDORS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
		break;

		case 'RECEIPTS':
			if(isSet($DEFS['SAG_WS_RECEIPTS'])) {
				$select = str_replace("TRN_CLAUSE",$value,$DEFS['SAG_WS_RECEIPTS']);
				$select = str_replace("EQUALS","=",$select);
				$xmlPart = "<Access statement=\"$select\"/>";
			} else {
				$xmlPart = <<<XML
XML;
/*
<Access statement="SELECT VENDORS *CRITERIA* WITH A2 = \\ACF\\"/>
<Files>
	<File name="INSURORS">
		<Items>
			<Item key="ISD"/>
		</Items>
	</File>
</Files>
*/
			}
		break;

		case 'INSURER.DISBURSEMENTS':
			$xmlPart = <<<XML
<Files>
	<File name="INSURER.DISBURSEMENTS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
		break;

		case 'INSURER.DISBURSEMENTS2':
			$xmlPart = <<<XML
<Files>
	<File name="INSURER.DISBURSEMENTS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
		break;

		case 'PAYMENTS':
		$xmlPart = <<<XML
<Files>
	<File name="PAYMENTS">
		<Items>
			<Item key="$value"/>
		</Items>
	</File>
</Files>
XML;
		break;
	}
	return $xmlPart;
}

function performQuery($xmlPart, $mapArr) {
	$data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>

<SOAP-ENV:Envelope  xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
 xmlns:xsd="http://www.w3.org/2001/XMLSchema"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
 xmlns:ns4="http://amsservices.com/"
>
<SOAP-ENV:Body>

<PassThroughReq xmlns="http://amsservices.com/">
  <XMLinput>
    <INPUT>
      <Account value="{$mapArr['account']}"/>
      <Username value="{$mapArr['username']}"/>
      <Password value="{$mapArr['password']}"/>
      <Accesscode value="{$mapArr['access_code']}"/>
XML;
	if(isset( $mapArr['online_code'] ) and $mapArr['online_code'])  {
	$data .= <<<XML
      <Online value="{$mapArr['online_code']}"/>
XML;
	} elseif(isset( $mapArr['pool']) and $mapArr['pool']) {
	$data .= <<<XML
	<Serverpool value="{$mapArr['pool']}" />	
XML;
	} else {
	$data .= <<<XML
	<Serverpool value="websvc" />	
XML;
	
	}
	$data .= <<<XML
      $xmlPart
    </INPUT>
  </XMLinput>
</PassThroughReq>

</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
	$url = $mapArr['url'];

	$headers = array(
		'SOAPAction'	=> 'http://amsservices.com/PassThroughReq',
		'Content-type'	=> 'text/xml; charset=utf-8'
	);
	$client = new HTTP_Client;
	$client->post($url, $data, true, array(), $headers);
	$response = $client->currentResponse();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_mem($response['body']);
		$mainTag = $xmlDoc->get_elements_by_tagname('PassThroughReqResult');
		if(count($mainTag)) {
			return $mainTag[0]->get_content();
		} else {
			return false;
		}
	} else {
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML($response['body']);
		$mainTag = $xmlDoc->getElementsByTagName('PassThroughReqResult');
		if($mainTag->length) {
			$tmp = $mainTag->item(0);
			return $tmp->nodeValue;
		} else {
			return false;
		}
	}
}

function makeSagittaWSDate($daySince1968) {
	$begTillEpoch = 732;
	$newTime = ($daySince1968 - $begTillEpoch) * 60 * 60 * 24;
	return gmdate('Y-m-d', $newTime);
}

?>