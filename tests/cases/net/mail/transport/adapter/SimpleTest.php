<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Simple;
use li3_mailer\net\mail\Message;

class SimpleTest extends \lithium\test\Unit {
	public function testPlainMessage() {
		$simple = $this->_adapter();
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

		$expected = '/(^|\r\n)From: valid@address(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$expected = '/(^|\r\n)MIME-Version: 1.0(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$type = "Content-Type: text\/plain;charset=\"{$message->charset}\"";
		$expected = '/(^|\r\n)' . $type . '(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$expected = '/(^|\r\n)Custom: foo(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);
	}

	public function testHtmlMessage() {
		$simple = $this->_adapter();
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

		$expected = '/(^|\r\n)From: valid@address(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$expected = '/(^|\r\n)MIME-Version: 1.0(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$type = "Content-Type: text\/html;charset=\"{$message->charset}\"";
		$expected = '/(^|\r\n)' . $type . '(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);
	}

	public function testMultipartMessage() {
		$simple = $this->_adapter();
		$message = new Message(array(
			'to' => 'foo@bar', 'from' => 'valid@address',
			'subject' => 'test subject'
		));
		$message->body('text', 'test text body');
		$message->body('html', '<b>test html body</b>');
		$params = $simple->deliver($message);
		extract($params);
		$charset = $message->charset;

		$this->assertEqual('foo@bar', $to);
		$this->assertEqual('test subject', $subject);

		$expected = '/^This is a multi-part message in MIME format.\n\n/';
		$this->assertPattern($expected, $body);

		$type = "Content-Type: text\/plain;charset=\"{$charset}\"";
		$textBody = 'test text body';
		$expected = '/\n' . $type . '\n\n' . $textBody . '\n/';
		$this->assertPattern($expected, $body);

		$type = "Content-Type: text\/html;charset=\"{$charset}\"";
		$htmlBody = '<b>test html body<\/b>';
		$expected = '/\n' . $type . '\n\n' . $htmlBody . '\n/';
		$this->assertPattern($expected, $body);

		$expected = '/(^|\r\n)From: valid@address(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$expected = '/(^|\r\n)MIME-Version: 1.0(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);

		$type = 'Content-Type: multipart\/alternative;boundary="[^"]+"';
		$expected = '/(^|\r\n)' . $type . '(\r\n|$)/';
		$this->assertPatternRaw($expected, $headers);
	}

	public function testAttachments() {
		$simple = $this->_adapter();
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
		$params = $simple->deliver($message);
		extract($params);

		$type = 'Content-Type: text\/plain; name="cool.txt"';
		$disposition = 'Content-Disposition: attachment; filename="cool.txt"';
		$data = 'my data';
		$content = $type . '\n' . $disposition . '\n\n' . $data;
		$expected = '/\n' . $content . '\n/';
		$this->assertPattern($expected, $body);

		$type = 'Content-Type: text\/plain; name="file.txt"';
		$disposition = 'Content-Disposition: attachment; filename="file.txt"';
		$id = 'Content-ID: \<foo@bar\>';
		$data = 'file data';
		$content = $type . '\n' . $disposition . '\n' . $id . '\n\n' . $data;
		$expected = '/\n' . $content . '\n/';
		$this->assertPattern($expected, $body);

		unlink($path);
	}

	public function testAttachmentErrorNoFile() {
		$simple = $this->_adapter();
		$message = new Message(array('attach' => array(
			'/foo/bar' => array('filename' => 'file.txt', 'check' => false)
		)));
		$this->expectException('/^Can not attach path `\/foo\/bar`\.$/');
		$simple->deliver($message);
	}

	public function testAttachmentWithoutFilename() {
		$simple = $this->_adapter();
		$message = new Message(array('attach' => array(
			array('data' => 'my data', 'content-type' => 'text/plain')
		)));
		$message->body('text', 'text body');
		$message->body('html', 'html body');
		$params = $simple->deliver($message);
		extract($params);

		$type = 'Content-Type: text\/plain';
		$disposition = 'Content-Disposition: attachment';
		$data = 'my data';
		$content = $type . '\n' . $disposition . '\n\n' . $data;
		$expected = '/\n' . $content . '\n/';
		$this->assertPattern($expected, $body);
	}

	/**
	 * Assert with pattern without replacing \r.
	 *
	 * @see lithium\test\Unit::assertPattern()
	 * @see lithium\test\Unit::_normalizeLineEndings()
	 */
	public function assertPatternRaw($expected, $result, $message = '{:message}') {
		$pregResult = !!preg_match($expected, $result);
		$this->assert($pregResult, $message, compact('expected', 'result'));
	}

	protected function _adapter() {
		$dependencies = array(
			'mail' => function($to, $subject, $body, $headers) {
				return compact('to', 'subject', 'body', 'headers');
			}
		);
		return new Simple(compact('dependencies'));
	}
}

?>