<?php

namespace li3_mailer\tests\cases\action;

use li3_mailer\tests\mocks\action\Mailer;
use li3_mailer\tests\mocks\action\MailerOverload;
use li3_mailer\tests\mocks\action\TestMailer;
use li3_mailer\tests\mocks\action\MailerWithOptions;
use li3_mailer\tests\mocks\action\MailerWithoutDelivery;
use li3_mailer\net\mail\Message;

$filter = function($self, $params, $chain) {
	$result = $chain->next($self, $params, $chain);
	return $params + compact('result');
};
Mailer::applyFilter('deliver', $filter);
TestMailer::applyFilter('deliver', $filter);
MailerWithoutDelivery::applyFilter('deliver', $filter);

class MailerTest extends \lithium\test\Unit {

	public function testCreateMessage() {
		$to = 'foo@bar';
		$message = Mailer::message(compact('to'));
		$this->assertTrue($message instanceof Message);
		$this->assertEqual($to, $message->to);
	}

	public function testDeliver() {
		$params = Mailer::deliver('message_name', array(
			'to' => 'foo@bar',
			'data' => array('my' => 'data'),
			'transport' => array('extra' => 'data'),
			'view' => 'li3_mailer\tests\mocks\template\Mail'
		));
		extract($params);
		$this->assertTrue(array_key_exists('mailer', $options));
		$this->assertNull($options['mailer']);
		$this->assertTrue(isset($options['template']));
		$this->assertEqual('message_name', $options['template']);
		$this->assertEqual(array('my' => 'data'), $data);
		$this->assertEqual('foo@bar', $message->to);
		$this->assertEqual('adapter@config', $message->from);
		$this->assertEqual('fake rendered message', $message->body('html'));
		$this->assertEqual('fake rendered message', $message->body('text'));
		$this->assertFalse(is_null($transport));
		$this->assertEqual(array('extra' => 'data'), $transportOptions);
	}

	public function testDeliverDefaultDelivery() {
		$params = MailerWithoutDelivery::deliver('message_name', array(
			'to' => 'foo@bar', 'view' => 'li3_mailer\tests\mocks\template\Mail'
		));
		$this->assertEqual('default@config', $params['message']->from);
	}

	public function testSetsMailer() {
		$options = array('template' => false, 'data' => 'string');
		$params = Mailer::deliver('message_name', $options);
		extract($params);
		$this->assertEqual(null, $options['mailer']);

		$options = array('template' => false, 'data' => 'string');
		$params = TestMailer::deliver('message_name', $options);
		extract($params);
		$this->assertEqual('test', $options['mailer']);
	}

	public function testOverloading() {
		list($message, $options) = MailerOverload::deliverTest();
		$this->assertEqual('test', $message);
		$this->assertEqual(array(), $options);

		list($message, $options) = MailerOverload::deliverTestWithLocal();
		$this->assertEqual('test', $message);
		$this->assertEqual(array('delivery' => 'local'), $options);

		$data = array('foo' => 'bar');
		list($message, $options) = MailerOverload::deliverTestWithLocal($data);
		$expected = array('foo' => 'bar', 'delivery' => 'local');
		$this->assertEqual($expected, $options);

		$data = 'foo@bar';
		list($message, $options) = MailerOverload::deliverTestWithLocal($data);
		$expected = array('foo@bar', 'delivery' => 'local');
		$this->assertEqual($expected, $options);

		$data = array('foo@bar', 'foo' => 'bar');
		list($message, $options) = MailerOverload::deliverTestWithLocal($data);
		$expected = array('foo@bar', 'foo' => 'bar', 'delivery' => 'local');
		$this->assertEqual($expected, $options);
	}

	public function testOverloadException() {
		$this->expectException(
			'Method `foobar` not defined or handled in class `Mailer`.'
		);
		Mailer::foobar();
	}

	public function testOptions() {
		$options = array('foo@bar', 'subject' => 'my subject', 'my' => 'data');
		$expected = array(
			'to' => 'foo@bar',
			'subject' => 'my subject',
			'data' => array('my' => 'data', 'additional' => 'data')
		);
		$result = MailerWithOptions::options('without_extra_options', $options);
		$this->assertEqual($expected, $result);
		$expected = array(
			'to' => 'foo@bar', 'subject' => 'my subject',
			'data' => array(
				'my' => 'data', 'additional' => 'data', 'extra' => 'data'
			)
		);
		$result = MailerWithOptions::options('with_extra_options', $options);
		$this->assertEqual($expected, $result);
		$options = array('foo@bar', 'data' => array('my' => 'data'));
		$expected = array(
			'to' => 'foo@bar',
			'data' => array('my' => 'data', 'additional' => 'data')
		);
		$result = MailerWithOptions::options('without_extra_options', $options);
		$this->assertEqual($expected, $result);
	}
}

?>