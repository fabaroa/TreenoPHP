<?php

class CabinetFolder {
	var $docID;
	var $cabinetIndices;
	
    function CabinetFolder($docID = NULL, $cabinetIndices = NULL) {
        $this->docID = $docID;
        $this->cabinetIndices = $cabinetIndices;
    }
}
?>
