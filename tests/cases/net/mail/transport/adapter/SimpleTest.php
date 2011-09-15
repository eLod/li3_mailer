<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\tests\mocks\net\mail\transport\adapter\Simple;
use li3_mailer\net\mail\Message;

class SimpleTest extends \lithium\test\Unit {
	public function testPlainMessage() {
		$simple = new Simple();
		$message = new Message(array(
			'to' => 'foo@bar', 'from' => 'valid@address',
			'subject' => 'test subject', 'types' => 'text',
			'headers' => array('Custom' => 'foo')
		));
		$message->body('text', 'test body');
		$params = $simple->deliver($message);
		extract($params);
		$this->assertEqual('foo@bar', $to);
		$this->assertEqual('test subject', $subject);
		$this->assertEqual('test body', $body);
		$this->assertPattern('/(^|\r\n)From: valid@address(\r\n|$)/', $headers);
		$this->assertPattern('/(^|\r\n)MIME-Version: 1.0(\r\n|$)/', $headers);
		$this->assertPattern(
			'/(^|\r\n)Content-Type: text\/plain;charset="' . $message->charset . '"(\r\n|$)/',
			$headers
		);
		$this->assertPattern('/(^|\r\n)Custom: foo(\r\n|$)/', $headers);
	}

	public function testHtmlMessage() {
		$simple = new Simple();
		$message = new Message(array(
			'to' => 'foo@bar', 'from' => 'valid@address',
			'subject' => 'test subject', 'types' => 'html'
		));
		$message->body('html', '<b>test body</b>');
		$params = $simple->deliver($message);
		extract($params);
		$this->assertEqual('foo@bar', $to);
		$this->assertEqual('test subject', $subject);
		$this->assertEqual('<b>test body</b>', $body);
		$this->assertPattern('/(^|\r\n)From: valid@address(\r\n|$)/', $headers);
		$this->assertPattern('/(^|\r\n)MIME-Version: 1.0(\r\n|$)/', $headers);
		$this->assertPattern(
			'/(^|\r\n)Content-Type: text\/html;charset="' . $message->charset . '"(\r\n|$)/',
			$headers
		);
	}

	public function testMultipartMessage() {
		$simple = new Simple();
		$message = new Message(array(
			'to' => 'foo@bar', 'from' => 'valid@address', 'subject' => 'test subject'
		));
		$message->body('text', 'test text body');
		$message->body('html', '<b>test html body</b>');
		$params = $simple->deliver($message);
		extract($params);
		$this->assertEqual('foo@bar', $to);
		$this->assertEqual('test subject', $subject);
		$this->assertPattern('/^This is a multi-part message in MIME format.\n\n/', $body);
		$charset = $message->charset;
		$this->assertPattern(
			'/\nContent-Type: text\/plain;charset="' . $charset . '"\n\ntest text body\n/',
			$body
		);
		$this->assertPattern(
			'/\nContent-Type: text\/html;charset="' . $charset . '"\n\n<b>test html body<\/b>\n/',
			$body
		);
		$this->assertPattern('/(^|\r\n)From: valid@address(\r\n|$)/', $headers);
		$this->assertPattern('/(^|\r\n)MIME-Version: 1.0(\r\n|$)/', $headers);
		$this->assertPattern(
			'/(^|\r\n)Content-Type: multipart\/alternative;boundary="[^"]+"(\r\n|$)/',
			$headers
		);
	}

	public function testAttachments() {
		$simple = new Simple();
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
		$params = $simple->deliver($message);
		extract($params);
		$c_type = 'Content-Type: text\/plain; name="cool.txt"';
		$c_disposition = 'Content-Disposition: attachment; filename="cool.txt"';
		$preg = '/\n' . $c_type . '\n' . $c_disposition . '\n\nmy data\n/';
		$this->assertPattern($preg, $body);
		$c_type = 'Content-Type: text\/plain; name="file.txt"';
		$c_disposition = 'Content-Disposition: attachment; filename="file.txt"';
		$c_id = 'Content-ID: \<foo@bar\>';
		$preg = '/\n' . $c_type . '\n' . $c_disposition . '\n' . $c_id . '\n\nfile data\n/';
		$this->assertPattern($preg, $body);
		unlink($path);
	}

	public function testAttachmentErrorNoFile() {
		$simple = new Simple();
		$message = new Message(array('attach' => array(
			'/foo/bar' => array('filename' => 'file.txt', 'check' => false)
		)));
		$this->expectException('/^Can not attach path `\/foo\/bar`\.$/');
		$simple->deliver($message);
	}
}

?>