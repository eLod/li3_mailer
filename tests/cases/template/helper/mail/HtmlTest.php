<?php

namespace li3_mailer\tests\cases\template\helper\mail;

use li3_mailer\net\mail\Message;
use li3_mailer\template\mail\adapter\File;
use li3_mailer\template\helper\mail\Html;

class HtmlTest extends \lithium\test\Unit {
	public function testCharset() {
		$message = new Message();
		$context = new File(compact('message'));
		$html = new Html(compact('context'));
		$result = $html->charset();
		$this->assertTags($result, array('meta' => array(
			'charset' => $message->charset
		)));
	}

	public function testImage() {
		$message = new Message(array('baseURL' => 'foo.local'));
		$context = new File(compact('message'));
		$html = new Html(compact('context'));

		$result = $html->image('test.gif', array('check' => false));
		$this->assertTags($result, array('img' => array(
			'src' => 'regex:/cid:[^@]+@foo.local/', 'alt' => ''
		)));

		$result = $html->image('/foo/bar.gif', array('check' => false));
		$this->assertTags($result, array('img' => array(
			'src' => 'regex:/cid:[^@]+@foo.local/', 'alt' => ''
		)));

		$result = $html->image('test.gif', array('embed' => false));
		$this->assertTags($result, array('img' => array(
			'src' => 'http://foo.local/img/test.gif', 'alt' => ''
		)));

		$result = $html->image('http://example.com/logo.gif');
		$this->assertTags($result, array('img' => array(
			'src' => 'http://example.com/logo.gif', 'alt' => ''
		)));

		$result = $html->image('/foo/bar.gif', array('embed' => false));
		$this->assertTags($result, array('img' => array(
			'src' => 'http://foo.local/foo/bar.gif', 'alt' => ''
		)));
	}
}

?>