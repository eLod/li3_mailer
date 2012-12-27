<?php

namespace li3_mailer\tests\cases\template\mail;

use li3_mailer\template\mail\Compiler;
use lithium\core\Libraries;

class CompilerTest extends \lithium\test\Unit {
	protected function _checkWritesTo($writeDir, array $options = array()) {
		$writable = is_writable($writeDir);
		$this->skipIf(!$writable, "Path `{$writeDir}` is not writable.");

		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");

		$file = $path . DIRECTORY_SEPARATOR . 'mail_template.html.php';
		file_put_contents($file, 'test mail template');

		$compiler = new Compiler();
		$template = $compiler->template($file, $options);

		$this->assertEqual(0, strpos($template, $writeDir));
		$this->assertTrue(is_file($template));

		$result = file_get_contents($template);
		$this->assertEqual('test mail template', $result);

		unlink($template);
	}

	public function testSetsPath() {
		$writePath = Libraries::get(true, 'resources') . '/tmp/cache/mails';
		$writeDir = realpath($writePath);
		$this->_checkWritesTo($writeDir);
	}

	public function testDoesNotOverridePath() {
		$basePath = Libraries::get(true, 'resources') . '/tmp/cache/mails';
		$baseDir = realpath($basePath);
		$writable = is_writable($baseDir);
		$this->skipIf(!$writable, "Path `{$baseDir}` is not writable.");
		$writeDir = $baseDir . DIRECTORY_SEPARATOR . 'test_override_path';
		if (!is_dir($writeDir)) {
			mkdir($writeDir);
		}
		$this->_checkWritesTo($writeDir, array('path' => $writeDir));
		rmdir($writeDir);
	}
}

?>