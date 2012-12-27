<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Swift;
use li3_mailer\tests\mocks\net\mail\transport\adapter\Swift as MockSwift;
use li3_mailer\net\mail\Message;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

class SwiftTest extends \lithium\test\Unit {
	public function skip() {
		$swiftAvailable = class_exists('Swift_Mailer');
		$this->skipIf(!$swiftAvailable, 'SwiftMailer library not available.');
	}

	public function testDeliver() {
		$swift = new MockSwift();
		$message = new Message(array('subject' => 'test subject'));
		$transport = $swift->deliver($message);
		$this->assertEqual(1, count($transport->delivered));
		$swiftMessage = $transport->delivered[0];
		$this->assertEqual('test subject', $swiftMessage->getSubject());
	}

	public function testBadTransport() {
		$swift = new Swift();
		$this->expectException(
			'Unknown transport type `` for `Swift` adapter.'
		);
		$transport = $swift->invokeMethod('_transport');

		$swift = new Swift(array('transport' => 'foo'));
		$this->expectException(
			'Unknown transport type `foo` for `Swift` adapter.'
		);
		$transport = $swift->invokeMethod('_transport');
	}

	public function testMailTransport() {
		$swift = new Swift(array('transport' => 'mail'));
		$mailer = $swift->invokeMethod('_transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_MailTransport);

		$options = array('transport' => 'mail', 'extra_params' => 'foo');
		$swift = new Swift($options);
		$mailer = $swift->invokeMethod('_transport');
		$this->assertEqual('foo', $mailer->getTransport()->getExtraParams());

		$swift = new Swift(array('transport' => 'mail'));
		$options = array('extra_params' => 'foo');
		$mailer = $swift->invokeMethod('_transport', array($options));
		$this->assertEqual('foo', $mailer->getTransport()->getExtraParams());
	}

	public function testSendmailTransport() {
		$swift = new Swift(array('transport' => 'sendmail'));
		$mailer = $swift->invokeMethod('_transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SendmailTransport);

		$options = array('transport' => 'sendmail', 'command' => 'foo');
		$swift = new Swift($options);
		$mailer = $swift->invokeMethod('_transport');
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());

		$swift = new Swift(array('transport' => 'sendmail'));
		$command = 'foo';
		$mailer = $swift->invokeMethod('_transport', array(compact('command')));
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());
	}

	public function testSmtpTransport() {
		$swift = new Swift(array('transport' => 'smtp'));
		$mailer = $swift->invokeMethod('_transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SmtpTransport);

		$options = array(
			'host' => 'test host', 'port' => 'test port',
			'timeout' => 'test timeout', 'encryption' => 'test encryption',
			'sourceip' => 'test sourceip', 'auth_mode' => 'test auth_mode',
			'local_domain' => 'test local_domain',
			'username' => 'test username', 'password' => 'test password'
		);
		$swift = new Swift(array('transport' => 'smtp') + $options);
		$mailer = $swift->invokeMethod('_transport');
		$transport = $mailer->getTransport();
		$this->assertEqual('test host', $transport->getHost());
		$this->assertEqual('test port', $transport->getPort());
		$this->assertEqual('test timeout', $transport->getTimeout());
		$this->assertEqual('test encryption', $transport->getEncryption());
		$this->assertEqual('test sourceip', $transport->getSourceip());
		$this->assertEqual('test local_domain', $transport->getLocalDomain());
		$this->assertEqual('test auth_mode', $transport->getAuthMode());
		$this->assertEqual('test username', $transport->getUsername());
		$this->assertEqual('test password', $transport->getPassword());

		$swift = new Swift(array('transport' => 'smtp'));
		$mailer = $swift->invokeMethod('_transport', array($options));
		$transport = $mailer->getTransport();
		$this->assertEqual('test host', $transport->getHost());
		$this->assertEqual('test port', $transport->getPort());
		$this->assertEqual('test timeout', $transport->getTimeout());
		$this->assertEqual('test encryption', $transport->getEncryption());
		$this->assertEqual('test sourceip', $transport->getSourceip());
		$this->assertEqual('test local_domain', $transport->getLocalDomain());
		$this->assertEqual('test auth_mode', $transport->getAuthMode());
		$this->assertEqual('test username', $transport->getUsername());
		$this->assertEqual('test password', $transport->getPassword());
	}

	public function testMessage() {
		$message = new Message(array(
			'to' => array('Foo' => 'foo@bar'), 'from' => 'valid@address',
			'subject' => 'test subject', 'charset' => 'ISO-8859-1',
			'types' => 'text', 'cc' => array('Bar' => 'bar@foo', 'baz@qux'),
			'headers' => array('Custom' => 'foo')
		));
		$message->body('text', 'test body');
		$swift = new Swift();
		$swiftMessage = $swift->invokeMethod('_message', array($message));

		$this->assertEqual(array('foo@bar' => 'Foo'), $swiftMessage->getTo());

		$expected = array('valid@address' => null);
		$this->assertEqual($expected, $swiftMessage->getFrom());

		$expected = array('bar@foo' => 'Bar', 'baz@qux' => null);
		$this->assertEqual($expected, $swiftMessage->getCc());

		$this->assertEqual('test subject', $swiftMessage->getSubject());

		$this->assertEqual('test body', $swiftMessage->getBody());

		$this->assertEqual('ISO-8859-1', $swiftMessage->getCharset());

		$header = $swiftMessage->getHeaders()->get('Custom');
		$this->assertEqual('foo', $header->getValue());
	}

	public function testMultipartMessage() {
		$message = new Message();
		$message->body('text', 'test text body');
		$message->body('html', '<b>test html body</b>');
		$swift = new Swift();
		$swiftMessage = $swift->invokeMethod('_message', array($message));
		$this->assertEqual('<b>test html body</b>', $swiftMessage->getBody());
		$children = $swiftMessage->getChildren();
		$this->assertEqual(1, count($children));
		$this->assertEqual('test text body', $children[0]->getBody());
	}

	public function testAttachments() {
		$path = tempnam('/tmp', 'li3_mailer_test');
		file_put_contents($path, 'file data');
		$message = new Message(array('attach' => array(
			array('data' => 'my data', 'filename' => 'cool.txt'),
			$path => array(
				'filename' => 'file.txt', 'id' => 'foo@bar',
				'content-type' => 'text/plain'
			)
		)));
		$message->body('text', 'text body');
		$message->body('html', 'html body');
		$swift = new Swift();
		$swiftMessage = $swift->invokeMethod('_message', array($message));
		$children = $swiftMessage->getChildren();
		$attachments = array_filter($children, function($child) {
			return in_array($child->getBody(), array('file data', 'my data'));
		});
		$this->assertEqual(2, count($attachments));
		list($file, $data) = array_values($attachments);
		if ($data->getBody() !== 'my data') {
			list($file, $data) = array($data, $file);
		}
		//swift wipes out body for files, maybe a bug?
		//$this->assertEqual('file data', $file->getBody());
		$this->assertEqual('file.txt', $file->getFilename());
		$this->assertEqual('text/plain', $file->getContentType());
		$this->assertEqual('foo@bar', $file->getId());

		$this->assertEqual('my data', $data->getBody());
		$this->assertEqual('cool.txt', $data->getFilename());
		$this->assertEqual('text/plain', $data->getContentType());
		unlink($path);
	}
}

?>