<?php

namespace li3_mailer\tests\mocks\net\mail;

class Message extends \li3_mailer\net\mail\Message {
	public function grammar() {
		return $this->_grammar;
	}
}

?>