<?php

namespace li3_mailer\net\mail\transport\adapter;

use lithium\core\Libraries;
use lithium\util\String;
use Closure;
use RuntimeException;

/**
 * The `Debug` adapter does not send email messages, but logs them to a file.
 *
 * An example configuration:
 * {{{Delivery::config(array('debug' => array(
 *     'adapter' => 'Debug',
 *     'from' => 'my@address',
 *     'log' => '/path/to/log',
 *     'format' => 'custom',
 *     'formats' => array(
 *         'custom' => 'Custom log for {:to}, {:subject}'
 *     )
 * )));}}}
 * Apart from message parameters (like `'from'`, `'to'`, etc.) for supported
 * options see `deliver()`.
 *
 * @see li3_mailer\net\mail\transport\adapter\Debug::deliver()
 * @see li3_mailer\net\mail\Delivery
 */
class Debug extends \li3_mailer\net\mail\Transport {
	/**
	 * Log a message.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_format()
	 * @param object $message The message to log.
	 * @param array $options Options supported:
	 *        - `'log'` _string_ or _resource_: Path to the log file or
	 *          directory. If points to a file entries will be appended to this
	 *          file, if points to directory every message will be logged to a
	 *          new file in this directory (with a unique name generated with
	 *          `time()` and `uniqid()`).
	 *          Alternatively it may be a resource. Defaults to
	 *          `'/tmp/logs/mail.log'` relative to application's resources.
	 *        - `'format'` _string_: formatter name, defaults to `'normal'`,
	 *          see `_format()`.
	 * @return boolean Returns `true` if the message was successfully logged,
	 *         `false` otherwise.
	 */
	public function deliver($message, array $options = array()) {
		$options = $this->_config + $options + array(
			'log' => Libraries::get(true, 'resources') . '/tmp/logs/mail.log',
			'format' => 'normal'
		);
		$entry = $this->_format($message, $options['format']);
		$entry = '[' . date('c') . '] ' . $entry . PHP_EOL;
		$log = $options['log'];
		if (!is_resource($log)) {
			if (is_dir($log)) {
				$log .= DIRECTORY_SEPARATOR . time() . uniqid() . '.mail';
			}
			$log = fopen($log , 'a+');
		}
		$result = fwrite($log, $entry);
		if (!is_resource($options['log'])) {
			fclose($log);
		}
		return $result !== false && $result === strlen($entry);
	}

	/**
	 * Format a message with formatter.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_formatters()
	 * @param object $message Message to format.
	 * @param string $format Formatter name to use.
	 * @return string Formatted log entry.
	 */
	protected function _format($message, $format) {
		$formatters = $this->_formatters();
		$formatter = isset($formatters[$format]) ? $formatters[$format] : null;
		switch (true) {
			case $formatter instanceof Closure:
				return $formatter($message);
			case is_string($formatter):
				$data = $this->_messageData($message);
				return String::insert($formatter, $data);
			default:
				$error = "Formatter for format `{$format}` " .
					"is neither string nor closure.";
				throw new RuntimeException($error);
		}
	}

	/**
	 * Helper method for getting log formatters indexed by name. Values may be
	 * `String::insert()` style strings (receiving the `Message`'s properties
	 * as data according to `_messageData()`) or closures (receiving the
	 * `Message` as the argument and should return a string that will be placed
	 * in the log).
	 * Additional formatters may be added with configuration key `'formats'`.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_messageData()
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_format()
	 * @see lithium\util\String::insert()
	 * @return array Available formatters indexed by name.
	 */
	protected function _formatters() {
		$config = $this->_config + array('formats' => array());
		return (array) $config['formats'] + array(
			'short' => 'Sent to {:to} with subject `{:subject}`.',
			'normal' => "Mail sent to {:to} from {:from}" .
				" (sender: {:sender}, cc: {:cc}, bcc: {:bcc})\n" .
				"with date {:date} and subject `{:subject}`" .
				" in formats {:types}," .
				" text message body:\n{:body_text}\n",
			'full' => "Mail sent to {:to} from {:from}" .
				" (sender: {:sender}, cc: {:cc}, bcc: {:bcc})\n" .
				"with date {:date} and subject `{:subject}`" .
				" in formats {:types}," .
				" text message body:\n{:body_text}\n" .
				"html message body:\n{:body_html}\n",
			'verbose' => function($message) {
				$properties = var_export(get_object_vars($message), true);
				return "Mail sent with properties:\n{$properties}";
			}
		);
	}

	/**
	 * Helper method to get message property data for `String::insert()`
	 * style formatters. Additional data may be added with the
	 * configuration key `'messageData'`, which should be an array of:
	 *
	 * - strings: property names (with integer keys) or the special
	 *   `'address'` value with the property name as the key (in which
	 *   case the property will be transformed with `address()`).
	 * - closures: the keys should be property names, the closure
	 *   receives the message's property as the first argument and
	 *   should return altered data. If the key is `''` the closure will
	 *   receive the message object as the first argument and should
	 *   return an array which will be merged into the data array.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_formatters()
	 * @see li3_mailer\net\mail\transport\adapter\Debug::_format()
	 * @see li3_mailer\net\mail\Message
	 * @param object $message Message.
	 * @return array Message data.
	 */
	protected function _messageData($message) {
		$config = $this->_config + array('messageData' => array());
		$map = (array) $config['messageData'] + array(
			'subject', 'charset', 'returnPath', 'sender' => 'address',
			'from' => 'address', 'replyTo' => 'address', 'to' => 'address',
			'cc' => 'address', 'bcc' => 'address',
			'date' => function($time) { return date('Y-m-d H:i:s', $time); },
			'types' => function($types) { return join(', ', $types); },
			'headers' => function($headers) { return join(PHP_EOL, $headers); },
			'body' => function($bodies) {
				return join(PHP_EOL, array_map(function($body) {
					return join(PHP_EOL, $body);
				}, $bodies));
			},
			'' => function($message) {
				return array_combine(
					array_map(function($type) {
						return "body_{$type}";
					}, $message->types),
					array_map(function($type) use ($message) {
						return $message->body($type);
					}, $message->types)
				);
			}
		);
		$data = array();
		foreach ($map as $prop => $config) {
			if ($prop === '') {
				continue;
			}
			if (is_int($prop)) {
				$prop = $config;
				$config = null;
			}
			$value = $message->$prop;
			if ($config instanceof Closure) {
				$value = $config($value);
			} else if ($config === 'address') {
				$value = $this->_address($value);
			}
			$data[$prop] = $value;
		}
		if (isset($map['']) && $map[''] instanceof Closure) {
			$data = $map['']($message) + $data;
		}
		return $data;
	}
}

?>