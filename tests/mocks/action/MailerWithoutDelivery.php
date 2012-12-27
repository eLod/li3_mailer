<?php

namespace li3_mailer\tests\mocks\action;

class MailerWithoutDelivery extends \li3_mailer\action\Mailer {
	protected static $_classes = array(
		'media' => 'li3_mailer\net\mail\Media',
		'message' => 'li3_mailer\net\mail\Message',
		'delivery' => 'li3_mailer\tests\mocks\net\mail\Delivery'
	);
}

?>