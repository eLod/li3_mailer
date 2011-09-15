<?php

namespace li3_mailer\tests\mocks\net\mail;

class DeliveryWithPath extends \li3_mailer\net\mail\Delivery {
	public static function _adaptersPath() {
		return static::$_adapters;
	}
}

?>