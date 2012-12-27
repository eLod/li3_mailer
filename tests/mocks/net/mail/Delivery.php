<?php

namespace li3_mailer\tests\mocks\net\mail;

use li3_mailer\tests\mocks\net\mail\Transport;

class Delivery extends \li3_mailer\net\mail\Delivery {

	protected static $_configurations = array(
		'default' => array('from' => 'default@config'),
		'test' => array('from' => 'adapter@config')
	);

	public static function adapter($name = null) {
		$validName = in_array($name, array('default', 'test'));
		return  $validName ? new Transport() : null;
	}
}

?>