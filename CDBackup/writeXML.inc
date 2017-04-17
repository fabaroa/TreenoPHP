<?php
require_once 'XML/Util.php';
class writeXML
{
	var $fd;
	var $parser;
	var $discInfo;
	var $currDisc;
	var $totalDiscs;
	var $discFD;
	
	function writeXML($fd, $discInfo, $totalDiscs)
	{
		$this->fd = $fd;
		$this->discInfo = $discInfo;
		$this->totalDiscs = $totalDiscs;
	}

	function _createParser()
	{
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($this->parser, '_startElement', '_endElement');
		xml_set_character_data_handler($this->parser, '_cData');
	}

	function writeDiscXML($currDisc, $discFD)
	{
		rewind($this->fd);
		$this->currDisc = $currDisc;
		$this->_createParser();
		$this->discFD = $discFD;
		while($data = str_replace("\n", "", fread($this->fd, 4096))) {
			xml_parse($this->parser, $data);
		}
		xml_parser_free($this->parser);
	}

	function _startElement($parser, $tag, $attributes)
	{
		switch($tag) {
		case 'department':
			$tmpArr = array(
				'this_disk'		=> $this->currDisc,
				'total_disks'	=> $this->totalDiscs
			);
			$attributes = array_merge($attributes, $tmpArr); 
			break;
		case 'folder':
			if(isset($this->discInfo[$attributes['location']])) {
				$discNum = $this->discInfo[$attributes['location']];
			}
			if(!isset($discNum) or !$discNum) $discNum = 1;
			$attributes = array_merge($attributes, array('disk' => $discNum));
			break;
		}
		fwrite($this->discFD, XML_Util::createStartElement($tag, $attributes));
	}

	function _endElement($parser, $tag)
	{
		fwrite($this->discFD, XML_Util::createEndElement($tag));
	}

	function _cData($parser, $data)
	{
		$newData = XML_Util::reverseEntities($data);
		fwrite($this->discFD, XML_Util::replaceEntities($newData));
	}
}
?>
