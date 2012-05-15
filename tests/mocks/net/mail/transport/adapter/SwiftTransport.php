<?php

namespace li3_mailer\tests\mocks\net\mail\transport\adapter;

class SwiftTransport {

	public $delivered = array();

	public function send($message) {
		$this->delivered[] = $message;
		return $this;
	}
}

?>