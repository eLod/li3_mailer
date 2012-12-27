<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\net\mail\Message;
use li3_mailer\tests\mocks\net\mail\Message as MockMessage;
use lithium\action\Request;

class MessageTest extends \lithium\test\Unit {

	public function testConstruct() {
		$config = array(
			'subject', 'date', 'return_path', 'sender', 'from', 'reply_to',
			'to', 'cc', 'bcc', 'types', 'charset', 'headers', 'body'
		);
		$map = function($n) { return "test {$n}"; };
		$config = array_combine($config, array_map($map, $config));
		$message = new Message($config);

		foreach ($config as $prop => $expected) {
			$this->assertEqual($expected, $message->$prop);
		}
	}

	public function testInitGrammar() {
		$message = new MockMessage(array('grammar' => array('foo' => 'bar')));
		$this->assertEqual('bar', $message->grammar()->token('foo'));
	}

	public function testHeaders() {
		$message = new Message();
		$this->assertEqual(array(), $message->headers);
		$message->header('Foo', 'bar');
		$this->assertEqual(array('Foo' => 'bar'), $message->headers);
		$message->header('Foo', false);
		$this->assertEqual(array(), $message->headers);
	}

	public function testBody() {
		$message = new Message();
		$this->assertEqual(array(), $message->body);
		$message->body('foo', 'bar');
		$this->assertEqual(array('foo' => array('bar')), $message->body);
		$this->assertEqual('bar', $message->body('foo'));
		$this->assertEqual('', $message->body('bar'));
	}

	public function testBodyWithBuffer() {
		$message = new Message();
		$message->body('text', 'texttexttexttext');
		$result = $message->body('text', null, array('buffer' => 4));
		$this->assertEqual(array('text', 'text', 'text', 'text'), $result);
	}

	public function testTypes() {
		$message = new Message();
		$this->assertEqual(array('html', 'text'), $message->types);
		$expected = array('html' => 'text/html', 'text' => 'text/plain');
		$this->assertEqual($expected, $message->types());
	}

	public function testEnsureValidDate() {
		$message = new Message();
		$this->assertEqual(null, $message->date);
		$message->invokeMethod('ensureValidDate');
		$this->assertTrue(is_int($message->date));
		$message = new Message(array('date' => 'invalid'));
		$this->assertEqual('invalid', $message->date);
		$this->expectException('/Invalid date timestamp `invalid`/');
		$message->invokeMethod('ensureValidDate');
	}

	public function testEnsureValidFromWithInteger() {
		$message = new Message(array('from' => 42));
		$this->expectException(
			'/`\$from` field should be a string or an array/'
		);
		$message->invokeMethod('ensureValidFrom');
	}

	public function testEnsureValidFromWithEmpty() {
		foreach (array(null, false, 0) as $from) {
			$message = new Message(compact('from'));
			$this->expectException(
				'`Message` should have at least one `$from` address.'
			);
			$message->invokeMethod('ensureValidFrom');
		}
	}

	public function testEnsureValidFromWithValid() {
		$message = new Message(array('from' => 'valid@address'));
		$message->invokeMethod('ensureValidFrom');
	}

	public function testEnsureValidSender() {
		$message = new Message(array('from' => 'foo@bar'));
		$message->invokeMethod('ensureValidSender');
		$this->assertEqual(null, $message->sender);

		$message = new Message(array('from' => array('foo@bar', 'bar@foo')));
		$message->invokeMethod('ensureValidSender');
		$this->assertEqual(array('foo@bar'), $message->sender);

		$from = array('foo' => 'foo@bar', 'bar' => 'bar@foo');
		$message = new Message(compact('from'));
		$message->invokeMethod('ensureValidSender');
		$this->assertEqual(array('foo' => 'foo@bar'), $message->sender);

		$options = array('from' => 'foo@bar', 'sender' => 'foo@bar');
		$message = new Message($options);
		$message->invokeMethod('ensureValidSender');
		$this->assertEqual(null, $message->sender);

		$options = array('from' => 'foo@bar', 'sender' => 'bar@foo');
		$message = new Message($options);
		$message->invokeMethod('ensureValidSender');
		$this->assertEqual('bar@foo', $message->sender);

		$message = new Message(array('sender' => array('foo@bar', 'bar@foo')));
		$this->expectException(
			'`Message` should only have a single `$sender` address.'
		);
		$message->invokeMethod('ensureValidSender');
	}

	public function testEnsureStandardCompliance() {
		$from = array('foo@bar', 'bar@foo');
		$message = new Message(compact('from'));
		$message->ensureStandardCompliance();
		$this->assertTrue(is_int($message->date));
		$this->assertEqual($from, $message->from);
		$this->assertEqual(array('foo@bar'), $message->sender);
	}

	public function testBaseURL() {
		$message = new Message(array('baseURL' => 'foo.local'));
		$this->assertEqual('http://foo.local', $message->baseURL);

		$message = new Message(array('baseURL' => 'http://foo.bar'));
		$this->assertEqual('http://foo.bar', $message->baseURL);

		$message = new Message(array('baseURL' => 'http://foo.bar/'));
		$this->assertEqual('http://foo.bar', $message->baseURL);

		$oldserver = $_SERVER;
		$_SERVER = array(
			'HTTP_HOST' => 'foo.bar', 'HTTPS' => true,
			'PHP_SELF' => '/foo/bar/index.php'
		) + $_SERVER;
		$message = new Message();
		$this->assertEqual('https://foo.bar/foo/bar', $message->baseURL);
		$_SERVER = $oldserver;
	}

	public function testRandomId() {
		$message = new Message(array('baseURL' => 'foo.local'));
		$this->assertPattern(
			'/^[^@]+@foo.local$/',
			$message->invokeMethod('_randomId')
		);

		$message = new Message();
		$this->assertPattern(
			'/^[^@]+@' . $this->_base() . '$/',
			$message->invokeMethod('_randomId')
		);

		$message = new Message(array('baseURL' => 'foo@local'));
		$this->assertPattern(
			'/^[^@]+@li3_mailer.generated$/',
			$message->invokeMethod('_randomId')
		);
	}

	public function testAttacAndDetach() {
		$message = new Message();
		$this->assertEqual(array(), $message->attachments());

		$message->attach(null, array('data' => 'my data'));
		$attachments = $message->attachments();
		$this->assertEqual(1, count($attachments));
		$this->assertEqual('my data', $attachments[0]['data']);
		$message->detach('my data');
		$this->assertEqual(array(), $message->attachments());
	}

	public function testAttachErrorNothingToAttach() {
		$message = new Message();
		$this->expectException(
			'/^Neither path nor data provided, cannot attach\.$/'
		);
		$message->attach(null);
	}

	public function testAttachErrorFileDoesNotExist() {
		$message = new Message();
		$this->expectException(
			'/^File at `foo\/bar` is not a valid asset, cannot attach\.$/'
		);
		$message->attach('foo/bar');
	}

	public function testAttachErrorDataIsInvalid() {
		$message = new Message();
		$this->expectException(
			'/^Data should be a string, `integer` given, cannot attach\.$/'
		);
		$message->attach(null, array('data' => 42));
	}

	public function testAttachRelativePath() {
		$message = new Message();
		$message->attach('foo/bar.png', array('check' => false));
		$attachments = $message->attachments();
		$this->assertEqual(1, count($attachments));
		$this->assertEqual(
			LITHIUM_APP_PATH . '/mails/_assets/foo/bar.png',
			$attachments[0]['path']
		);
		$this->assertEqual('image/png', $attachments[0]['content-type']);
		$this->assertEqual('bar.png', $attachments[0]['filename']);
		$message->detach('foo/bar.png');
		$this->assertEqual(array(), $message->attachments());
	}

	public function testAttachAbsolutePath() {
		$message = new Message();
		$message->attach('/foo/bar.png', array('check' => false));
		$attachments = $message->attachments();
		$this->assertEqual(1, count($attachments));
		$this->assertEqual('/foo/bar.png', $attachments[0]['path']);
		$this->assertEqual('image/png', $attachments[0]['content-type']);
		$this->assertEqual('bar.png', $attachments[0]['filename']);
		$message->detach('/foo/bar.png');
		$this->assertEqual(array(), $message->attachments());
	}

	public function testAttachData() {
		$message = new Message();
		$options = array('data' => 'test content', 'filename' => 'test.txt');
		$message->attach(null, $options);
		$attachments = $message->attachments();
		$this->assertEqual(1, count($attachments));
		$this->assertEqual('test content', $attachments[0]['data']);
		$this->assertEqual('text/plain', $attachments[0]['content-type']);
		$message->detach('test content');
		$this->assertEqual(array(), $message->attachments());
	}

	public function testAttachFromConstructor() {
		$message = new Message(array('attach' => array(
			array('data' => 'test content'),
			'foo/bar' => array('check' => false),
			__FILE__
		)));
		$attachments = $message->attachments();
		$this->assertEqual(3, count($attachments));
		$this->assertEqual('test content', $attachments[0]['data']);
		$this->assertEqual('foo/bar', $attachments[1]['attachPath']);
		$this->assertEqual(__FILE__, $attachments[2]['attachPath']);
		$message->detach(__FILE__);
		$this->assertEqual(2, count($message->attachments()));
		$message->detach(__FILE__);
		$this->assertEqual(2, count($message->attachments()));
		$message->detach('test content');
		$this->assertEqual(1, count($message->attachments()));
		$message->detach('foo/bar');
		$this->assertEqual(0, count($message->attachments()));
	}

	public function testEmbed() {
		$message = new Message();
		$result = $message->embed('foo/bar', array('check' => false));
		$this->assertPattern('/^[^@]+@' . $this->_base() . '$/', $result);
		$attachments = $message->attachments();
		$this->assertEqual(1, count($attachments));
		$this->assertEqual('foo/bar', $attachments[0]['attachPath']);
		$this->assertEqual('inline', $attachments[0]['disposition']);
	}

	protected function _base() {
		$request = new Request();
		return $request->env('HTTP_HOST') ?: 'li3_mailer.generated';
	}
}

?>