<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Debug;
use li3_mailer\net\mail\Message;
use lithium\core\Libraries;

class DebugTest extends \lithium\test\Unit {
	public function testDeliver() {
		$options = array('to' => 'foo@bar', 'subject' => 'test subject');
		$message = new Message($options);
		$debug = new Debug();
		$log = fopen('php://memory', 'r+');
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		rewind($log);
		$result = stream_get_contents($log);
		fclose($log);
		$pattern = '\[[\d:\+\-T]+\]';
		$info = ' Sent to foo@bar with subject `test subject`.\n';
		$expected = '/^' . $pattern . $info . '$/';
		$this->assertPattern($expected, $result);
	}

	public function testDeliverLogToFile() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
		$log = $path . DIRECTORY_SEPARATOR . 'mail.log';
		file_put_contents($log, "initial content\n");
		$options = array('to' => 'foo@bar', 'subject' => 'test subject');
		$message = new Message($options);
		$debug = new Debug();
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		$result = file_get_contents($log);
		$content = 'initial content\n';
		$pattern = '\[[\d:\+\-T]+\]';
		$info = ' Sent to foo@bar with subject `test subject`.\n';
		$expected = '/^' . $content . $pattern . $info . '$/';
		$this->assertPattern($expected, $result);
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
		$options = array('to' => 'foo@bar', 'subject' => 'test subject');
		$message = new Message($options);
		$debug = new Debug();
		$format = 'short';
		$delivered = $debug->deliver($message, compact('log', 'format'));
		$this->assertTrue($delivered);
		$results = array_diff(glob($glob), $oldresults);
		$this->assertEqual(1, count($results));
		$resultFile = current($results);
		$result = file_get_contents($resultFile);
		$pattern = '\[[\d:\+\-T]+\]';
		$info = ' Sent to foo@bar with subject `test subject`.\n';
		$expected = '/^' . $pattern . $info . '$/';
		$this->assertPattern($expected, $result);
		unlink($resultFile);
	}

	public function testFormatShort() {
		$options = array('to' => 'foo@bar', 'subject' => 'test subject');
		$message = new Message($options);
		$debug = new Debug();
		$result = $debug->invokeMethod('_format', array($message, 'short'));
		$expected = 'Sent to foo@bar with subject `test subject`.';
		$this->assertEqual($expected, $result);
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
		$result = $debug->invokeMethod('_format', array($message, 'normal'));
		$expected = "Mail sent to to from from " .
			"(sender: sender, cc: cc, bcc: bcc)\n" .
			"with date {$date} and subject `subject` in formats html, " .
			"text, text message body:\n" .
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
		$result = $debug->invokeMethod('_format', array($message, 'full'));
		$expected = "Mail sent to to from from " .
			"(sender: sender, cc: cc, bcc: bcc)\n" .
			"with date {$date} and subject `subject` in formats html, " .
			"text, text message body:\n" .
			"text body\nhtml message body:\nhtml body\n";
		$this->assertEqual($expected, $result);
	}

	public function testFormatVerbose() {
		$message = new Message();
		$debug = new Debug();
		$result = $debug->invokeMethod('_format', array($message, 'verbose'));
		//need to scope $message to hide protected vars
		$formatter = function ($message) {
			$properties = var_export(get_object_vars($message), true);
			return "Mail sent with properties:\n{$properties}";
		};
		$expected = $formatter($message);
		$this->assertEqual($expected, $result);
	}

	public function testFormatNoData() {
		$message = new Message();
		$debug = new Debug();
		$result = $debug->invokeMethod('_format', array($message, 'short'));
		$this->assertEqual('Sent to  with subject ``.', $result);
	}

	public function testFormatExtraFormatter() {
		$message = new Message();
		$foo = function($message) { return 'foo'; };
		$debug = new Debug(array('formats' => compact('foo')));
		$result = $debug->invokeMethod('_format', array($message, 'foo'));
		$this->assertEqual('foo', $result);
	}

	public function testFormatBadFormatter() {
		$message = new Message();
		$debug = new Debug();
		$this->expectException(
			'Formatter for format `foo` is neither string nor closure.'
		);
		$debug->invokeMethod('_format', array($message, 'foo'));
	}
}

?>