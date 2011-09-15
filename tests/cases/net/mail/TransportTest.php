<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\tests\mocks\net\mail\Transport;

class TransportTest extends \lithium\test\Unit {
	public function testAddress() {
		$transport = new Transport();
		$this->assertEqual('foo@bar', $transport->invokeMethod('address', array('foo@bar')));
		$this->assertEqual('foo@bar', $transport->invokeMethod('address', array(array('foo@bar'))));
		$result = $transport->invokeMethod('address', array(array('Foo' => 'foo@bar')));
		$this->assertEqual('Foo <foo@bar>', $result);
		$result = $transport->invokeMethod('address', array(array(
			'Foo' => 'foo@bar', 'Bar' => 'bar@foo'
		)));
		$this->assertEqual('Foo <foo@bar>, Bar <bar@foo>', $result);
	}
}

?>