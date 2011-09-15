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