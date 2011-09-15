<?php

namespace li3_mailer\tests\mocks\action;

class Mailer extends \li3_mailer\action\Mailer {
	protected static $_classes = array(
		'media' => 'li3_mailer\net\mail\Media',
		'message' => 'li3_mailer\net\mail\Message',
		'delivery' => 'li3_mailer\tests\mocks\net\mail\Delivery'
	);

	protected static $_messages = array(array('delivery' => 'test'));
}

?>