<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\net\mail\Media;
use li3_mailer\net\mail\Message;
use li3_mailer\tests\mocks\template\Mail;
use lithium\core\Libraries;

class MediaTest extends \lithium\test\Unit {

	public function testType() {
		$this->assertEqual('text/plain', Media::type('text'));
		$this->assertEqual('text/html', Media::type('html'));
		$this->assertEqual(null, Media::type('foo'));
		Media::type('foo', 'bar');
		$this->assertEqual('bar', Media::type('foo'));
		$this->assertEqual(array(
			'text' => 'text/plain', 'html' => 'text/html', 'foo' => 'bar'
		), Media::invokeMethod('_types'));
		Media::type('foo', false);
		$this->assertEqual(null, Media::type('foo'));
	}

	public function testRender() {
		$message = new Message(array('from' => 'valid@address'));
		Media::render($message, 'body', array('template' => false));
		$this->assertEqual('body', $message->body('text'));
		$this->assertEqual('body', $message->body('html'));
	}

	public function testRenderWithView() {
		Media::type('foo', 'bar', array('view' => 'li3_mailer\tests\mocks\template\Mail'));
		$message = new Message(array('from' => 'valid@address', 'types' => 'foo'));
		Media::render($message);
		$this->assertEqual('fake rendered message', $message->body('foo'));
		Media::type('foo', false);
	}

	public function testBadHandler() {
		Media::type('foo', 'bar', array('view' => false, 'template' => false));
		$message = new Message(array('from' => 'valid@address', 'types' => 'foo'));
		$this->expectException('Could not interpret type settings for handler.');
		Media::render($message);
		Media::type('foo', false);
	}

	public function testView() {
		$message = new Message();
		Media::type('foo', 'bar', array('view' => 'li3_mailer\tests\mocks\template\Mail'));
		$view = Media::view('foo', array(), $message);
		$this->assertTrue($view instanceof Mail);
		$this->assertEqual($message, $view->message());
		Media::type('foo', false);
	}

	public function testTemplatePaths() {
		$message = new Message();
		Media::type('foo', 'bar', array(
			'view' => 'li3_mailer\tests\mocks\template\Mail',
			'template' => null
		));
		$base_handler = Media::invokeMethod('_handlers', array('foo'));
		$base_handler += Media::invokeMethod('_handlers', array('default'));
		$base_handler += array('compile' => false);

		$handler = $base_handler;
		$options = array(
			'library' => true, 'mailer' => null, 'template' => 'foo', 'type' => 'text'
		);
		$handler['paths'] = Media::invokeMethod(
			'_finalize_paths',
			array($handler['paths'], $options)
		);
		$view = Media::view($handler, array(), $message);
		$this->assertTrue($view instanceof Mail);
		$loader = $view->loader();
		$template = $loader->template('template', $options);
		$this->assertEqual(array(LITHIUM_APP_PATH . '/mails/foo.text.php'), $template);

		$handler = $base_handler;
		$options = array(
			'library' => true, 'mailer' => 'bar', 'template' => 'foo', 'type' => 'text'
		);
		$handler['paths'] = Media::invokeMethod(
			'_finalize_paths',
			array($handler['paths'], $options)
		);
		$view = Media::view($handler, array(), $message);
		$this->assertTrue($view instanceof Mail);
		$loader = $view->loader();
		$template = $loader->template('template', $options);

		$expected = array(
			LITHIUM_APP_PATH . '/mails/bar/foo.text.php',
			LITHIUM_APP_PATH . '/mails/foo.text.php'
		);
		$this->assertEqual($expected, $template);

		Media::type('foo', false);
	}

	public function testBadType() {
		$message = new Message(array('types' => 'foobar'));
		$this->expectException('Unhandled media type `foobar`.');
		Media::render($message);
	}

	public function testAsset() {
		$result = Media::asset('foo/bar');
		$this->assertEqual(LITHIUM_APP_PATH . '/mails/_assets/foo/bar', $result);
		Libraries::add('foo', array('path' => '/a/path'));
		$result = Media::asset('foo/bar', array('library' => 'foo'));
		$this->assertEqual('/a/path/mails/_assets/foo/bar', $result);
		Libraries::remove('foo');
		$result = Media::asset('/foo/bar');
		$this->assertEqual('/foo/bar', $result);
		$result = Media::asset('http://example.com/foo/bar');
		$this->assertEqual('http://example.com/foo/bar', $result);
		$result = Media::asset('foo/bar', array('check' => true));
		$this->assertFalse($result);
	}

	public function testRequest() {
		$request = Media::invokeMethod('_request', array(null));
		$this->assertEqual(null, $request->env('HTTP_HOST'));

		$tests = array(
			'foo.local' => array('HTTP_HOST' => 'foo.local'),
			'http://foo.local' => array('HTTP_HOST' => 'foo.local'),
			'https://foo.local' => array('HTTP_HOST' => 'foo.local', 'HTTPS' => true),
			'http://foo.local/base' => array('HTTP_HOST' => 'foo.local', 'base' => '/base')
		);

		foreach ($tests as $base_url => $expect) {
			$message = new Message(compact('base_url'));
			$request = Media::invokeMethod('_request', array($message));
			$expect += array('HTTPS' => false, 'base' => '');

			foreach ($expect as $key => $expected) {
				$result = $request->env($key);
				$msg = "`{$key}` failed for {$base_url} ({$message->base_url}),";
				$msg .= " expected: `{$expected}`, result: `{$result}`";
				$this->assertEqual($expected, $result, $msg);
			}
		}
	}
}

?>