<?php

namespace li3_mailer\tests\cases\template;

use li3_mailer\template\Mail;
use li3_mailer\template\mail\adapter\File;
use li3_mailer\net\mail\Message;
use li3_mailer\tests\mocks\template\Mail as MockMail;

class MailTest extends \lithium\test\Unit {
	public function testDefaultEncoding() {
		$string = "Joël";
		$mail = new Mail();
		$handler = $mail->outputFilters['h'];
		$this->assertTrue(mb_check_encoding($handler($string), 'UTF-8'));
	}

	public function testSetsEncoding() {
		$string = "Joël";
		$encoding = 'ISO-8859-1';
		$message = new Message(compact('encoding'));
		$mail = new Mail(compact('message'));
		$handler = $mail->outputFilters['h'];
		$this->assertTrue(mb_check_encoding($handler($string), $encoding));
	}

	public function testEscapeByType() {
		$expectedUnescaped = $string = '<p>Foo, Bar & Baz</p>';
		$expectedEscaped = '&lt;p&gt;Foo, Bar &amp; Baz&lt;/p&gt;';
		$mail = new Mail(array('type' => 'html'));
		$handler = $mail->outputFilters['h'];
		$this->assertEqual($expectedEscaped, $handler($string));
		$mail = new Mail(array('type' => 'text'));
		$handler = $mail->outputFilters['h'];
		$this->assertEqual($expectedUnescaped, $handler($string));
	}

	public function testLoadsMailAdapter() {
		$mail = new MockMail();
		$this->assertTrue($mail->renderer() instanceof File);
	}

	public function testSetsRenderer() {
		$renderer = new File();
		$mail = new MockMail(compact('renderer'));
		$this->assertEqual($renderer, $mail->renderer());
	}
}

?>