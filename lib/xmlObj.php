<?php
class xml {
	var $domDoc;
	var $rootNode;

	function xml($root = NULL) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$this->domDoc = domxml_new_doc("1.0");
		} else {
			$this->domDoc = new DOMDocument ();
		}
		if ($root) {
			$this->rootNode = $this->createRootElement($root);
		} else {
			$this->rootNode = $this->createRootElement();
		}
	}

	function createRootElement($root = 'ROOT') {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$el = $this->domDoc->create_element($root);
			$this->domDoc->append_child($el);
		} else {
			$el = $this->domDoc->createElement($root);
			$this->domDoc->appendChild($el);
		}

		return $el;
	}

	function setRootAttribute($key,$value) {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$this->rootNode->set_attribute($key,$value);	
		} else {
			$this->rootNode->setAttribute($key,$value);	
		}
	}

	function createKeyAndValue($key,$value = NULL,$attArr = array(), $parentEl = NULL) {
		if($parentEl) {
			$pEl = $parentEl;	
		} else {
			$pEl = $this->rootNode;
		}		
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$el = $this->domDoc->create_element($key);
			foreach($attArr AS $k => $val) {
				$el->set_attribute($k,$val);
			}
			$pEl->append_child($el);
		
			if($value !== false) {
				$text = $this->domDoc->create_text_node($value);
				$el->append_child($text);
			}
		} else {
			$el = $this->domDoc->createElement($key);
			foreach($attArr AS $k => $val) {
				$el->setAttribute($k,$val);
			}
			$pEl->appendChild($el);
		
			if($value !== false) {
				$text = $this->domDoc->createTextNode($value);
				$el->appendChild($text);
			}
		}
		return $el;
	}

	function createDOMString() {
		if (substr(PHP_VERSION, 0, 1) == '4') {
			return $this->domDoc->dump_mem(false, 'UTF-8');
		} else {
			$this->domDoc->encoding = 'UTF-8';
			return $this->domDoc->saveXML ();
		}
	}

	function setHeader() {
		header('Content-type: text/xml');
		echo $this->createDOMString();
	}
}
?>
