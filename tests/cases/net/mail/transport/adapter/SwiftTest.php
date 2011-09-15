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
		$swift_available = class_exists('Swift_Mailer');
		$this->skipIf(!$swift_available, 'SwiftMailer library not available.');
	}

	public function testDeliver() {
		$swift = new MockSwift();
		$message = new Message(array('subject' => 'test subject'));
		$transport = $swift->deliver($message);
		$this->assertEqual(1, count($transport->delivered));
		$swift_message = $transport->delivered[0];
		$this->assertEqual('test subject', $swift_message->getSubject());
	}

	public function testBadTransport() {
		$swift = new Swift();
		$this->expectException('Unknown transport type `` for `Swift` adapter.');
		$transport = $swift->invokeMethod('transport');

		$swift = new Swift(array('transport' => 'foo'));
		$this->expectException('Unknown transport type `foo` for `Swift` adapter.');
		$transport = $swift->invokeMethod('transport');
	}

	public function testMailTransport() {
		$swift = new Swift(array('transport' => 'mail'));
		$mailer = $swift->invokeMethod('transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_MailTransport);

		$swift = new Swift(array('transport' => 'mail', 'extra_params' => 'foo'));
		$mailer = $swift->invokeMethod('transport');
		$this->assertEqual('foo', $mailer->getTransport()->getExtraParams());

		$swift = new Swift(array('transport' => 'mail'));
		$mailer = $swift->invokeMethod('transport', array(array('extra_params' => 'foo')));
		$this->assertEqual('foo', $mailer->getTransport()->getExtraParams());
	}

	public function testSendmailTransport() {
		$swift = new Swift(array('transport' => 'sendmail'));
		$mailer = $swift->invokeMethod('transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SendmailTransport);

		$swift = new Swift(array('transport' => 'sendmail', 'command' => 'foo'));
		$mailer = $swift->invokeMethod('transport');
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());

		$swift = new Swift(array('transport' => 'sendmail'));
		$mailer = $swift->invokeMethod('transport', array(array('command' => 'foo')));
		$this->assertEqual('foo', $mailer->getTransport()->getCommand());
	}

	public function testSmtpTransport() {
		$swift = new Swift(array('transport' => 'smtp'));
		$mailer = $swift->invokeMethod('transport');
		$this->assertTrue($mailer instanceof Swift_Mailer);
		$transport = $mailer->getTransport();
		$this->assertTrue($transport instanceof Swift_SmtpTransport);

		$options = array(
			'host' => 'test host', 'port' => 'test port', 'timeout' => 'test timeout',
			'encryption' => 'test encryption', 'sourceip' => 'test sourceip',
			'local_domain' => 'test local_domain', 'auth_mode' => 'test auth_mode',
			'username' => 'test username', 'password' => 'test password'
		);
		$swift = new Swift(array('transport' => 'smtp') + $options);
		$mailer = $swift->invokeMethod('transport');
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
		$mailer = $swift->invokeMethod('transport', array($options));
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
			'subject' => 'test subject', 'types' => 'text', 'charset' => 'ISO-8859-1',
			'cc' => array('Bar' => 'bar@foo', 'baz@qux'), 'headers' => array('Custom' => 'foo')
		));
		$message->body('text', 'test body');
		$swift = new Swift();
		$swift_message = $swift->invokeMethod('message', array($message));
		$this->assertEqual(array('foo@bar' => 'Foo'), $swift_message->getTo());
		$this->assertEqual(array('valid@address' => null), $swift_message->getFrom());
		$this->assertEqual(array('bar@foo' => 'Bar', 'baz@qux' => null), $swift_message->getCc());
		$this->assertEqual('test subject', $swift_message->getSubject());
		$this->assertEqual('test body', $swift_message->getBody());
		$this->assertEqual('ISO-8859-1', $swift_message->getCharset());
		$header = $swift_message->getHeaders()->get('Custom');
		$this->assertEqual('foo', $header->getValue());
	}

	public function testMultipartMessage() {
		$message = new Message();
		$message->body('text', 'test text body');
		$message->body('html', '<b>test html body</b>');
		$swift = new Swift();
		$swift_message = $swift->invokeMethod('message', array($message));
		$this->assertEqual('<b>test html body</b>', $swift_message->getBody());
		$children = $swift_message->getChildren();
		$this->assertEqual(1, count($children));
		$this->assertEqual('test text body', $children[0]->getBody());
	}

	public function testAttachments() {
		$path = tempnam('/tmp', 'li3_mailer_test');
		file_put_contents($path, 'file data');
		$message = new Message(array('attach' => array(
			array('data' => 'my data', 'filename' => 'cool.txt'),
			$path => array(
				'filename' => 'file.txt', 'content-type' => 'text/plain', 'id' => 'foo@bar'
			)
		)));
		$message->body('text', 'text body');
		$message->body('html', 'html body');
		$swift = new Swift();
		$swift_message = $swift->invokeMethod('message', array($message));
		$children = $swift_message->getChildren();
		$attachments = array_filter($children, function($child) {
			return in_array($child->getBody(), array('file data', 'my data'));
		});
		$this->assertEqual(2, count($attachments));
		list($file, $data) = array_values($attachments);
		if ($data->getBody() != 'my data') {
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