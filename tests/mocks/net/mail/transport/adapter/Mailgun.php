<?php

namespace li3_mailer\tests\mocks\net\mail\transport\adapter;

class Mailgun extends \li3_mailer\net\mail\transport\adapter\Mailgun {
	protected $_classes = array(
		'curl' => 'li3_mailer\tests\mocks\net\socket\Curl'
	);

	protected function _parameters($message, array $options = array()) {
		return array('mock URL', 'mock key', 'mock parameters');
	}
}

?>