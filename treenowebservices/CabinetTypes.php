<?php
// $Id: CabinetTypes.php 14326 2011-04-11 20:31:25Z fabaroa $
/*
 * This file contains the cabinet level types
 */

// 
class CabinetItem {
	var $index;
	var $value;
	
    function CabinetItem($index = NULL, $value = NULL) {
        $this->index = $index;
        $this->value = $value;
    }

    function &__to_soap($name = 'CabinetItem', $header = false, $mustUnderstand = 0, 
    					$actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('index', 'string', $this->index);
        $inner[] = new SOAP_Value('value', 'string', $this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:TreenoWebServices}CabinetItem', $inner,
            					   $mustUnderstand, $actor);
        }
        return new SOAP_Value($name, '{urn:TreenoWebServices}CabinetItem', $inner);
    }
}

// 
class CabinetInfo {
	var $cabinetID;
	var $cabinetName;
	var $cabinetRealName;
	
	function CabinetInfo($cabinetID = NULL, $cabinetName = NULL) {
		$this->cabinetID       = $cabinetID;
		$this->cabinetName     = $cabinetName['departmentname'];
		$this->cabinetRealName = $cabinetName['real_name'];
	}
}

class CabinetIndiceDefinitions {
	var $name;
	var $required;
	var $regex;
	var $display;
	
    function CabinetIndiceDefinitions($name = NULL, $required = 0, $regex = NULL, $display = NULL) {
        $this->name = $name;
        $this->required = (int)$required;
        $this->regex = $regex;
        $this->display = $display;
    }

    function &__to_soap($name = 'CabinetIndiceDefinitions', $header = false, $mustUnderstand = 0, 
    					$actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('name', 'string', $this->name);
        $inner[] = new SOAP_Value('required', 'int', $this->required);
        $inner[] = new SOAP_Value('regex', 'string', $this->regex);
        $inner[] = new SOAP_Value('display', 'string', $this->display);
        if ($header) {
            return new SOAP_Header($name, '{urn:TreenoWebServices}CabinetIndiceDefinitions',
            					   $inner, $mustUnderstand, $actor);
        }
        return new SOAP_Value($name, '{urn:TreenoWebServices}CabinetIndiceDefinitions', $inner);
    }

}


//class CabinetEntry {
//	var $objs;
//	
//	function CabinetEntry($objs = NULL) {
//		$this->objs = $objs;
//	}
//
//	function &__to_soap($name = 'CabinetEntry', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
//		foreach($this->objs as $key => $value) {
//        	$inner[] =& $value->__to_soap();
//		}
//        if ($header) {
//            return new SOAP_Header($name,'{urn:DocutronWebServices2}CabinetEntry',$inner,$mustUnderstand,$actor);
//        }
//        return new SOAP_Value($name,'{urn:DocutronWebServices2}CabinetEntry',$inner);
//	}
//}

?>
