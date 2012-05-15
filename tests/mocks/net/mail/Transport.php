<?php

namespace li3_mailer\tests\mocks\net\mail;

class Transport extends \li3_mailer\net\mail\Transport {

	public function deliver($message, array $options = array()) {
		return true;
	}
}

?>