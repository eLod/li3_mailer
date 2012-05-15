<?php

namespace li3_mailer\tests\mocks\template\mail\adapter;

use lithium\util\String;
use lithium\template\TemplateException;

class FileLoader extends \lithium\template\view\adapter\File {

	protected function _paths($type, array $params) {
		if (!isset($this->_paths[$type])) {
			throw new TemplateException("Invalid template type '{$type}'.");
		}
		return array_map(function($path) use ($params) {
			return String::insert($path, $params);
		}, (array) $this->_paths[$type]);
	}
}

?>