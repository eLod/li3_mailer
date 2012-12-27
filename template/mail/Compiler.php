<?php

namespace li3_mailer\template\mail;

use lithium\core\Libraries;

/**
 * The template compiler for mail messages differ from the original view
 * template compiler only it that stores compiled templates in a different
 * directory (under `'/tmp/cache/mails'` relative to application's resources
 * directory).
 *
 * @see lithium\template\view\Compiler
 * @see li3_mailer\template\mail\Compiler::template()
 */
class Compiler extends \lithium\template\view\Compiler {
	/**
	 * Override compile to have a different cache path by default.
	 *
	 * @see lithium\template\view\Compiler::template()
	 * @param string $file The full path to the template that will be compiled.
	 * @param array $options Options, see `Compiler::template()`.
	 * @return string The compiled template.
	 */
	public static function template($file, array $options = array()) {
		$path = Libraries::get(true, 'resources') . '/tmp/cache/mails';
		$options += compact('path');
		return parent::template($file, $options);
	}
}

?>