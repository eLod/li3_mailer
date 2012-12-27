<?php

namespace li3_mailer\tests\cases\net\mail\transport\adapter;

use li3_mailer\net\mail\transport\adapter\Mailgun;
use li3_mailer\tests\mocks\net\mail\transport\adapter\Mailgun as MailgunMock;
use li3_mailer\net\mail\Message;

class MailgunTest extends \lithium\test\Unit {
	public function skip() {
		$cURLAvailable = function_exists('curl_init');
		$this->skipIf(!$cURLAvailable, 'cURL not available.');
	}

	public function testDeliver() {
		$mailgun = new MailgunMock();
		$message = new Message();
		$curl = $mailgun->deliver($message);

		$expected = CURLAUTH_BASIC;
		$this->assertEqual($expected, $curl->options[CURLOPT_HTTPAUTH]);
		$this->assertEqual('mock key', $curl->options[CURLOPT_USERPWD]);
		$this->assertEqual(1, $curl->options[CURLOPT_RETURNTRANSFER]);

		$this->assertEqual('POST', $curl->options[CURLOPT_CUSTOMREQUEST]);
		$this->assertEqual('mock URL', $curl->options[CURLOPT_URL]);
		$expected = 'mock parameters';
		$this->assertEqual($expected, $curl->options[CURLOPT_POSTFIELDS]);

		$this->assertTrue($curl->closed);
	}

	public function testParametersTo() {
		$options = array('url' => false, 'key' => false);
		$message = array('to' => 'foo@bar');
		list($url, $key, $parameters) = $this->_parameters($options, $message);
		$this->assertEqual('foo@bar', $parameters['to']);
	}

	public function testParametersMessage() {
		$options = array('url' => false, 'key' => false);
		$body = array('html' => 'foo html bar', 'text' => 'foo text bar');
		$message = compact('body');
		list($url, $key, $parameters) = $this->_parameters($options, $message);
		$this->assertPattern('/MIME-Version: 1.0/', $parameters['message']);
		$this->assertPattern('/foo html bar/', $parameters['message']);
		$this->assertPattern('/foo text bar/', $parameters['message']);
	}

	public function testParametersURLExplicit() {
		$options = array('url' => 'explicit URL', 'key' => false);
		list($url, $key, $parameters) = $this->_parameters($options);
		$this->assertEqual('explicit URL', $url);
	}

	public function testParametersURLFromApiAndDomain() {
		$options = array(
			'api' => 'http://foo.bar', 'domain' => 'baz.qux', 'key' => false
		);
		list($url, $key, $parameters) = $this->_parameters($options);
		$this->assertEqual('http://foo.bar/baz.qux/messages.mime', $url);
	}

	public function testParametersNoDomainNorUrl() {
		$error = "No `domain` (nor `url`) configured ";
		$error .= "for `Mailgun` transport adapter.";
		$this->expectException($error);
		$this->_parameters();
	}

	public function testParametersApiEndsWithSlash() {
		$this->expectException("API endpoint should not end with '/'.");
		$options = array('api' => 'http://foo.bar/', 'domain' => 'foo.bar');
		$this->_parameters($options);
	}

	public function testParametersDomainStartsWithSlash() {
		$this->expectException("Domain should not start with '/'.");
		$this->_parameters(array('domain' => '/foo.bar'));
	}

	public function testParametersDomainEndsWithSlash() {
		$this->expectException("Domain should not end with '/'.");
		$this->_parameters(array('domain' => 'foo.bar/'));
	}

	public function testExtraParameters() {
		$tags = array('tag1', 'tag2');
		$extras = array(
			'campaign' => 'test_campaign',
			'dkim' => true,
			'deliverytime' => 'deliverytime',
			'testmode' => true,
			'tracking' => true,
			'tracking-clicks' => true,
			'tracking-opens' => true
		);
		$defaults = array('url' => false, 'key' => false);
		$options = $extras + $defaults + array('tag' => $tags);
		list($url, $key, $parameters) = $this->_parameters($options);
		foreach ($extras as $name => $expected) {
			$this->assertEqual($expected, $parameters["o:{$name}"]);
		}
		foreach ($tags as $idx => $expected) {
			$key = "o:tag[" . ($idx + 1) . "]";
			$this->assertEqual($expected, $parameters[$key]);
		}
	}

	public function testVariables() {
		$variables = array('foo' => 'bar', 'baz' => 'qux');
		$defaults = array('url' => false, 'key' => false);
		$options = compact('variables') + $defaults;
		list($url, $key, $parameters) = $this->_parameters($options);
		$this->assertEqual('bar', $parameters['v:foo']);
		$this->assertEqual('qux', $parameters['v:baz']);
	}

	protected function _parameters($options = array(), $message = array()) {
		$mailgun = new Mailgun();
		$message = new Message($message);
		return $mailgun->invokeMethod('_parameters', array($message, $options));
	}
}

?>