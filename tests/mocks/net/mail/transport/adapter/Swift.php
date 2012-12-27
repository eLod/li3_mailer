<?php

namespace li3_mailer\tests\mocks\net\mail\transport\adapter;

use li3_mailer\tests\mocks\net\mail\transport\adapter\SwiftTransport;

class Swift extends \li3_mailer\net\mail\transport\adapter\Swift {

	protected function _transport(array $options = array()) {
		return new SwiftTransport();
	}
}

?>