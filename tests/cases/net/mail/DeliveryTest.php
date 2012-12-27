<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\tests\mocks\net\mail\DeliveryWithPath;
use li3_mailer\net\mail\Transport;

class DeliveryTest extends \lithium\test\Unit {

	public function testUsesGoodAdapters() {
		$params = array(
			array('adapter' => 'Simple'), DeliveryWithPath::adaptersPath()
		);
		$class = DeliveryWithPath::invokeMethod('_class', $params);
		$adapter = new $class();
		$this->assertTrue($adapter instanceof Transport);
	}
}

?>