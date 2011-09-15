<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Debug;
use li3_mailer\net\mail\Message;
use lithium\core\Libraries;

class DebugTest extends \lithium\test\Unit {
	public function testDeliver() {
		$message = new Message(array('to' => 'foo@bar', 'subject' => 'test subject'));
		$debug = new Debug();
		$log = fopen('php://memory', 'r+');
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		rewind($log);
		$result = stream_get_contents($log);
		fclose($log);
		$this->assertPattern(
			'/^\[[\d:\+\-T]+\] Sent to foo@bar with subject `test subject`.\n$/',
			$result
		);
	}

	public function testDeliverLogToFile() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
		$log = $path . DIRECTORY_SEPARATOR . 'mail.log';
		file_put_contents($log, "initial content\n");
		$message = new Message(array('to' => 'foo@bar', 'subject' => 'test subject'));
		$debug = new Debug();
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		$result = file_get_contents($log);
		$this->assertPattern(
			'/^initial content\n\[[\d:\+\-T]+\] Sent to foo@bar with subject `test subject`.\n$/',
			$result
		);
		unlink($log);
	}

	public function testDeliverLogToDir() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
		$log = $path . DIRECTORY_SEPARATOR . 'mails';
		if (!is_dir($log)) {
			mkdir($log);
		}
		$glob = $log . DIRECTORY_SEPARATOR . '*.mail';
		$oldresults = glob($glob);
		$message = new Message(array('to' => 'foo@bar', 'subject' => 'test subject'));
		$debug = new Debug();
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		$results = array_diff(glob($glob), $oldresults);
		$this->assertEqual(1, count($results));
		$result_file = current($results);
		$result = file_get_contents($result_file);
		$this->assertPattern(
			'/^\[[\d:\+\-T]+\] Sent to foo@bar with subject `test subject`.\n$/',
			$result
		);
		unlink($result_file);
	}

	public function testFormatShort() {
		$message = new Message(array('to' => 'foo@bar', 'subject' => 'test subject'));
		$debug = new Debug();
		$result = $debug->invokeMethod('format', array($message, 'short'));
		$this->assertEqual('Sent to foo@bar with subject `test subject`.', $result);
	}

	public function testFormatNormal() {
		$time = time();
		$date = date('Y-m-d H:i:s');
		$message = new Message(array(
			'to' => 'to', 'from' => 'from', 'sender' => 'sender', 'cc' => 'cc',
			'bcc' => 'bcc', 'date' => $time, 'subject' => 'subject'
		));
		$message->body('text', 'text body');
		$debug = new Debug();
		$result = $debug->invokeMethod('format', array($message, 'normal'));
		$expected = "Mail sent to to from from (sender: sender, cc: cc, bcc: bcc)\n" .
			"with date {$date} and subject `subject` in formats html, text, text message body:\n" .
			"text body\n";
		$this->assertEqual($expected, $result);
	}

	public function testFormatFull() {
		$time = time();
		$date = date('Y-m-d H:i:s');
		$message = new Message(array(
			'to' => 'to', 'from' => 'from', 'sender' => 'sender', 'cc' => 'cc',
			'bcc' => 'bcc', 'date' => $time, 'subject' => 'subject'
		));
		$message->body('text', 'text body');
		$message->body('html', 'html body');
		$debug = new Debug();
		$result = $debug->invokeMethod('format', array($message, 'full'));
		$expected = "Mail sent to to from from (sender: sender, cc: cc, bcc: bcc)\n" .
			"with date {$date} and subject `subject` in formats html, text, text message body:\n" .
			"text body\nhtml message body:\nhtml body\n";
		$this->assertEqual($expected, $result);
	}

	public function testFormatVerbose() {
		$message = new Message();
		$debug = new Debug();
		$result = $debug->invokeMethod('format', array($message, 'verbose'));
		$formatter = function ($message) { //need to scope $message to hide protected vars
			return "Mail sent with properties:\n" . var_export(get_object_vars($message), true);
		};
		$expected = $formatter($message);
		$this->assertEqual($expected, $result);
	}

	public function testFormatNoData() {
		$message = new Message();
		$debug = new Debug();
		$result = $debug->invokeMethod('format', array($message, 'short'));
		$this->assertEqual('Sent to  with subject ``.', $result);
	}

	public function testFormatExtraFormatter() {
		$message = new Message();
		$debug = new Debug(array('formats' => array('foo' => function($message) {
			return 'foo';
		})));
		$result = $debug->invokeMethod('format', array($message, 'foo'));
		$this->assertEqual('foo', $result);
	}

	public function testFormatBadFormatter() {
		$message = new Message();
		$debug = new Debug();
		$this->expectException('Formatter for format `foo` is neither string nor closure.');
		$debug->invokeMethod('format', array($message, 'foo'));
	}
}

?>