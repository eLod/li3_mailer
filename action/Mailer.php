<?php

namespace li3_mailer\action;

use lithium\util\Inflector;
use BadMethodCallException;

/**
 * The `Mailer` class is the fundamental building block for sending mails within your application.
 * It may be subclassed to group options (with `$_messages`) and/or templates (view templates can
 * reside in a folder named after their `Mailer` subclass) together.
 *
 * @see li3_mailer\net\mail\Media
 * @see li3_mailer\net\mail\Delivery
 * @see li3_mailer\action\Mailer::deliver()
 */
class Mailer extends \lithium\core\StaticObject {

	/**
	 * Holds extra configurations per message (and a default
	 * for every message at key `0` if set). See `Mailer::_options()`.
	 *
	 * @see li3_mailer\action\Mailer::_options()
	 * @var array
	 */
	protected static $_messages = array();

	/**
	 * The option names that should not be treated as data when parsing
	 * options. See `Mailer::_options()`.
	 *
	 * @see li3_mailer\action\Mailer::_options()
	 * @var array
	 */
	protected static $_short_options = array('from', 'cc', 'bcc', 'subject', 'delivery');

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'media' => 'li3_mailer\net\mail\Media',
		'delivery' => 'li3_mailer\net\mail\Delivery',
		'message' => 'li3_mailer\net\mail\Message'
	);

	/**
	 * Create a message object with given options.
	 *
	 * @see li3_mailer\net\mail\Message
	 * @param array $options Options, apart from `Message`'s options if `'class'`
	 *        is presented it will be used for creating the instance, otherwise
	 *        defaults to `'message'` (see `_instance()` and `$_classes`).
	 * @return li3_mailer\net\mail\Message A message instance.
	 */
	public static function message(array $options = array()) {
		return static::_filter(__FUNCTION__, compact('options'), function($self, $params) {
			$options = $params['options'];
			$class = isset($options['class']) ? $options['class'] : 'message';
			unset($options['class']);
			return $self::invokeMethod('_instance', array($class, $options));
		});
	}

	/**
	 * Deliver a message. Creates a message with given options,
	 * renders it with `Media` and delivers it via the transport
	 * adapter. This method is filterable and the filter receives
	 * the (cleared) options, data, message (object), transport
	 * adapter and transport options as parameters.
	 *
	 * @see li3_mailer\action\Mailer::message()
	 * @see li3_mailer\net\mail\Message
	 * @see li3_mailer\net\mail\Media::render()
	 * @see li3_mailer\net\mail\Transport::deliver()
	 * @param string $messageName Name of the message to send.
	 * @param array $options Options may be:
	 *        - options for creating the message (see `message()`),
	 *        - options for rendering the message (see `Media::render()`),
	 *          sets `'mailer'` (to `Mailer` subclass' short name) and
	 *          `'template'` (to `$message_name`) by default,
	 *        - `'transport'`: transport options (see `Transport::deliver()`),
	 *        - `'data'`: binded data for rendering (see `Media::render()`),
	 *        - `'delivery'`: the delivery configuration and adapter to use
	 *          (configuration will be merged into options for creating the
	 *          the message).
	 * @return mixed Return value of the transport adapter's deliver method.
	 */
	public static function deliver($messageName, array $options = array()) {
		$options = static::_options($messageName, $options);

		$delivery = static::$_classes['delivery'];
		$deliveryName = isset($options['delivery']) ? $options['delivery'] : 'default';
		unset($options['delivery']);

		$messageOptions = $options + $delivery::config($deliveryName);
		$message = static::message($messageOptions);
		$transport = $delivery::adapter($deliveryName);

		$transportOptions = isset($options['transport']) ? (array) $options['transport'] : array();
		unset($options['transport']);

		$data = isset($options['data']) ? $options['data'] : array();
		unset($options['data']);
		$class = get_called_class();
		$name = preg_replace('/Mailer$/', '', substr($class, strrpos($class, "\\") + 1));

		$options += array(
			'mailer' => ($name == '' ? null : Inflector::underscore($name)),
			'template' => $messageName
		);
		$media = static::$_classes['media'];
		$params = compact('options', 'data', 'message', 'transport', 'transportOptions');

		return static::_filter(__FUNCTION__, $params, function($self, $params) use ($media) {
			extract($params);
			$media::render($message, $data, $options);
			return $transport->deliver($message, $transportOptions);
		});
	}

	/**
	 * Allows the use of syntactic-sugar like `Mailer::deliverTestWithLocal()` instead of
	 * `Mailer::deliver('test', array('delivery' => 'local'))`.
	 *
	 * @see li3_mailer\action\Mailer::deliver()
	 * @link http://php.net/manual/en/language.oop5.overloading.php PHP Manual: Overloading
	 *
	 * @throws BadMethodCallException On unhandled call, will throw an exception.
	 * @param string $method Method name caught by `__callStatic()`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed Results of dispatched `Mailer::deliver()` call.
	 */
	public static function __callStatic($method, $params) {
		$found = preg_match('/^deliver(?P<message>\w+)With(?P<delivery>\w+)$/', $method, $args);

		if (!$found) {
			preg_match('/^deliver(?P<message>\w+)$/', $method, $args);
		}
		if (!$args) {
			$class = get_called_class();
			$class = substr($class, strrpos($class, "\\") + 1);
			$message = "Method `{$method}` not defined or handled in class `{$class}`.";
			throw new BadMethodCallException($message);
		}
		$message = Inflector::underscore($args['message']);

		if (isset($params[0]) && is_array($params[0])) {
			$params = $params[0];
		}
		if (isset($args['delivery'])) {
			$params['delivery'] = Inflector::underscore($args['delivery']);
		}
		return static::deliver($message, $params);
	}

	/**
	 * Get options for a given message. Allows shorter options syntax
	 * where the first item does not have an associative key (e.g. the
	 * key is `0`)
	 * like `('message', array('foo@bar', 'subject' => 'my subject', 'my' => 'data'))`
	 * which will be translated to
	 * `('message', array('to' => 'foo@bar', 'subject' => 'my subject',
	 * 'data' => array('my' => 'data'))`.
	 * Uses `$_short_options` to detect options that should be extracted
	 * (unless a value for key `'data'` is already set),
	 * also merges in the settings from `$_messages`.
	 *
	 * @see li3_mailer\action\Mailer::$_messages
	 * @see li3_mailer\action\Mailer::$_short_options
	 * @param string $message The message identifier (name).
	 * @param array $options Options.
	 * @return array Options.
	 */
	protected static function _options($message, array $options = array()) {
		if (isset($options[0])) {
			$to = $options[0];
			unset($options[0]);

			if (isset($options['data'])) {
				$options += compact('to');
			} else {
				$data = $options;
				$blank = array_fill_keys(static::$_short_options, null);
				$shorts = array_intersect_key($data, $blank);
				$data = array_diff_key($data, $shorts);
				$options = compact('to', 'data') + $shorts;
			}
		}
		if (array_key_exists($message, static::$_messages)) {
			$options = array_merge_recursive((array) static::$_messages[$message], $options);
		}
		if (isset(static::$_messages[0])) {
			$options = array_merge_recursive((array) static::$_messages[0], $options);
		}
		return $options;
	}
}

?>