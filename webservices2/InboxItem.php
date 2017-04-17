<?php

class CheckedOutFileInfo {
	var $prefix;
	var $encFileData;
    function CheckedOutFileInfo($prefix = NULL, $encFileData = NULL) {
        $this->prefix = $prefix;
        $this->encFileData = $encFileData;
    }
}

class InboxItem {
	var $isFolder;
	var $filename;
	
    function InboxItem($isFolder = NULL, $filename = NULL) {
        $this->isFolder = $isFolder;
        $this->filename = $filename;
    }
}
?>
