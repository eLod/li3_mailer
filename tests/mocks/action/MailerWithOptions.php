<?php

namespace li3_mailer\tests\mocks\action;

class MailerWithOptions extends \li3_mailer\action\Mailer {
	protected static $_messages = array(
		array('data' => array('additional' => 'data')),
		'with_extra_options' => array('data' => array('extra' => 'data'))
	);

	public static function options($message, array $options = array()) {
		return static::_options($message, $options);
	}
}

?>