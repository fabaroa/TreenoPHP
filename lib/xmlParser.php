<?php

function xmlGetFuncArgs ($xmlStr, &$entryArr, &$func) {
	global $DEFS;
	if (substr (PHP_VERSION, 0, 1) == '4') {
		if(!$domDoc = domxml_open_mem($xmlStr)) {
			echo "error receiving XML";
			die();
		}

		$domArr = $domDoc->get_elements_by_tagname('ENTRY');
		$entryArr = array();
		foreach($domArr AS $dom) {
			$key = $dom->get_elements_by_tagname('KEY');
			$key = $key[0]->get_content();
			$value = $dom->get_elements_by_tagname('VALUE');
			$value = $value[0]->get_content();
			if($key == "include") {
				$temp = ltrim($value,'.\\');
				error_log($temp."\n");
				if (file_exists("\\treeno\\treeno\\".$temp))
				{
					include_once "\\treeno\\treeno\\".$temp;
				}				
			} elseif($key == 'function') {
				$func = $value;
			} else {
				$entryArr[$key] = $value;
			}
		}
	} else {
		$domDoc = new DOMDocument ();
		if(!$domDoc->loadXML ($xmlStr)) {
			echo "error receiving XML";
			die();
		}

		$domArr = $domDoc->getElementsByTagName('ENTRY');
		$entryArr = array();
                for ($i = 0; $i < $domArr->length; $i++) {
                        $dom = $domArr->item($i);
			$key = $dom->getElementsByTagName('KEY');
			$key = $key->item(0);
			$key = $key->nodeValue;
			$value = $dom->getElementsByTagName ('VALUE');
			$value = $value->item(0);
			$value = $value->nodeValue;
			if($key == "include") {
				$temp = ltrim($value,'.\\');
				error_log($temp."\n");
				if (file_exists("\\treeno\\treeno\\".$temp))
				{
					include_once "\\treeno\\treeno\\".$temp;
				}				
			} elseif($key == 'function') {
				$func = $value;
			} else {
				$entryArr[$key] = $value;
			}
		}
	}
}

?>
