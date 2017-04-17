<?php
require_once '../CDBackup/XMLCabinetFuncs.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/mime.php';
class cdXML
{
	var $backupDir;
	var $currDir;
	var $filesDir;
	var $cData;
	var $parser;
	var $symlinkArr;
	var $inFileTag;
	var $DEFS;
	
	function cdXML($backupDir, $filesDir,$symlinkArr, $DEFS)
	{
		$this->backupDir = $backupDir;
		$this->filesDir = $filesDir;
		$this->symlinkArr = $symlinkArr;
		$this->DEFS = $DEFS;
	}

	function _createParser()
	{
		$this->parser = xml_parser_create();
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, '_startElement', '_endElement');
		xml_set_character_data_handler($this->parser, '_cData');
	}

	function parse($fd)
	{
		rewind($fd);
		$this->_createParser();
		while ($data = str_replace("\n", "", fread($fd, 4096))) {
			xml_parse($this->parser, $data);
		}
		xml_parser_free($this->parser);
	}

	function _startElement($parser, $tag, $attributes)
	{
		switch($tag) {
		case 'tab':
			$this->currDir = $attributes['location'].'/';
			if($attributes['name'] != 'main') {
				$this->currDir .= $attributes['name'].'/';
			}
			makeAllDir($this->backupDir.$this->currDir);
			break;
		case 'file':
			$this->cData = '';
			$this->inFileTag = true;
			break;
		}
	}

	function _endElement($parser, $tag)
	{
		switch($tag) {
		case 'tab':
			$this->currDir = null;
			break;
		case 'file':
			$id = $this->symlinkArr[$this->filesDir.$this->currDir.$this->cData];
			$ext = getExtension ($this->cData);
			if(is_file($this->filesDir.$this->currDir.$this->cData)) {
				copy($this->filesDir.$this->currDir.$this->cData,
						 $this->backupDir.$this->currDir.$id.'.'.$ext);
				if (!file_exists ($this->backupDir.$this->currDir.$id.'.'.$ext)) {
					error_log ("copy bad: " . $this->backupDir.$this->currDir.$id.'.'.$ext);
					die ();
				}
			}
			$this->inFileTag = false;
			break;
		}
	}

	function _cData($parser, $data)
	{
		if($this->inFileTag)
			$this->cData .= $data;
	}
}
?>
