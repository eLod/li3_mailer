<?php

namespace li3_mailer\tests\cases\template\mail;

use li3_mailer\template\mail\Compiler;
use lithium\core\Libraries;

class CompilerTest extends \lithium\test\Unit {
	protected function checkWritesTo($write_dir, array $options = array()) {
		$this->skipIf(!is_writable($write_dir), "Path `{$write_dir}` is not writable.");
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
		$file = $path . DIRECTORY_SEPARATOR . 'mail_template.html.php';
		file_put_contents($file, 'test mail template');
		$compiler = new Compiler();
		$template = $compiler->template($file, $options);
		$this->assertEqual(0, strpos($template, $write_dir));
		$this->assertTrue(is_file($template));
		$result = file_get_contents($template);
		$this->assertEqual('test mail template', $result);
		unlink($template);
	}

	public function testSetsPath() {
		$write_dir = realpath(Libraries::get(true, 'resources') . '/tmp/cache/mails');
		$this->checkWritesTo($write_dir);
	}

	public function testDoesNotOverridePath() {
		$base_dir = realpath(Libraries::get(true, 'resources') . '/tmp/cache/mails');
		$this->skipIf(!is_writable($base_dir), "Path `{$base_dir}` is not writable.");
		$write_dir = $base_dir . DIRECTORY_SEPARATOR . 'test_override_path';
		if (!is_dir($write_dir)) {
			mkdir($write_dir);
		}
		$this->checkWritesTo($write_dir, array('path' => $write_dir));
		rmdir($write_dir);
	}
}

?>