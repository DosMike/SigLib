<?php
if (empty($fromIndex) && empty($fromAction)) die();

function xmlWriteArray($xml, $key, $value) {
	if (is_array($value)) {
		if (array_is_list($value)) {
			$xml->startElement($key.'List');
			$xml->writeAttribute('count', strval(count($value)));
			foreach ($value as $element) {
				xmlWriteArray($xml, $key, $element);
			}
			$xml->endElement();
		} else {
			$xml->startElement($key);
			foreach ($value as $subkey=>$element) {
				xmlWriteArray($xml, $subkey, $element);
			}
			$xml->endElement();
		}
	} else {
		$xml->writeElement($key, $value);
	}
}

function output($type, $data) {
	header ('Content-Type: application/xml');
	$xml = new XMLWriter();
	$xml->openMemory();
	$xml->startDocument("1.0");
	xmlWriteArray($xml, $type, $data);
	$xml->endDocument();
	echo $xml->outputMemory();
}