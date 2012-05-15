<?php

namespace li3_mailer\tests\mocks\template;

use li3_mailer\tests\mocks\template\mail\adapter\FileLoader;

class Mail extends \li3_mailer\template\Mail {

	public function renderer() {
		return $this->_renderer;
	}

	public function loader() {
		$config = array('view' => $this) + $this->_config;
		return new FileLoader($config);
	}

	public function message() {
		return $this->_message;
	}

	public function render($process, array $data = array(), array $options = array()) {
		return 'fake rendered message';
	}
}

?>