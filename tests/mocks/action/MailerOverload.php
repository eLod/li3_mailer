<?php

namespace li3_mailer\tests\mocks\action;

class MailerOverload extends \li3_mailer\action\Mailer {
	public static function deliver($message_name, array $options = array()) {
		return array($message_name, $options);
	}
}

?>