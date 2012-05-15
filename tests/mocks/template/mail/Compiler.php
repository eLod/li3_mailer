<?php

namespace li3_mailer\tests\mocks\template\mail;

class Compiler extends \li3_mailer\template\mail\Compiler {

	public function renderer() {
		return $this->_renderer;
	}

	public function message() {
		return $this->_message;
	}

	public function render($process, array $data = array(), array $options = array()) {
		return 'fake rendered message';
	}
}

?>