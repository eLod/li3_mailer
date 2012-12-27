<?php

namespace li3_mailer\tests\mocks\net\socket;

class Curl extends \lithium\net\socket\Curl {
	public $closed = false;

	public function read() {
		return $this;
	}

	public function close() {
		$this->closed = parent::close();
		return true;
	}
}

?>