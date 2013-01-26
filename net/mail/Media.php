<?php

namespace li3_mailer\net\mail;

use li3_mailer\net\mail\MediaException;
use lithium\core\Libraries;

/**
 * The `Media` class facilitates content-type mapping (mapping between
 * content-types and file extensions), handling static assets and globally
 * configuring how the framework handles output in different formats for
 * rendering emails.
 */
class Media extends \lithium\core\StaticObject {
	/**
	 * Maps file extensions to content-types.  Used to render content into
	 * message. Can be modified with `Media::type()`.
	 *
	 * @var array
	 * @see li3_mailer\net\mail\Media::type()
	 */
	protected static $_types = array();

	/**
	 * A map of media handler objects or callbacks, mapped to media types.
	 *
	 * @var array
	 */
	protected static $_handlers = array();

	/**
	 * Placeholder for class dependencies. This class' dependencies
	 * (i.e. templating classes) are typically specified through
	 * other configuration.
	 *
	 * @var array
	 */
	protected static $_classes = array('request' => 'lithium\action\Request');

	/**
	 * Maps a type name to a particular content-type (or multiple types) with a
	 * set of options, or retrieves information about a type that has been
	 * defined.
	 *
	 * Alternatively, can be used to retrieve content-type for a registered type
	 * (short) name:
	 * {{{
	 * Media::type('html'); // returns 'text/html'
	 * Media::type('text'); // returns 'text/plain'
	 * }}}
	 *
	 * @see li3_mailer\net\mail\Media::$_types
	 * @see li3_mailer\net\mail\Media::$_handlers
	 * @param string $type A file-extension-style type name,
	 *        i.e. `'text'` or `'html'`.
	 * @param mixed $content Optional. The content-type string
	 *        (i.e. `'text/plain'`). May be `false` to delete `$type`.
	 * @param array $options Optional. The handling options for this media type.
	 *        Possible keys are:
	 *        - `'layout'` _mixed_: Specifies one or more
	 *          `String::insert()`-style paths to use when searching for layout
	 *          files (either a string or array of strings).
	 *        - `'template'` _mixed_: Specifies one or more
	 *          `String::insert()`-style paths to use when searching for
	 *          template files (either a string or array of strings).
	 *        - `'view'` _string_: Specifies the view class to use when
	 *          rendering this content.
	 * @return mixed If `$content` and `$options` are empty, returns the
	 *         content-type. Otherwise returns `null`.
	 */
	public static function type($type, $content = null, array $options = array()) {
		$defaults = array(
			'view' => false,
			'template' => false,
			'layout' => false
		);

		if ($content === false) {
			unset(static::$_types[$type], static::$_handlers[$type]);
		}
		if (!$content && !$options) {
			return static::_types($type);
		}
		if ($content) {
			static::$_types[$type] = $content;
		}
		static::$_handlers[$type] = $options ? ($options + $defaults) : array();
	}

	/**
	 * Renders data (usually the result of a mailer delivery action) and
	 * generates a string representation of it, based on the types of expected
	 * output. Also ensures the message's fields are valid according to the
	 * RFC 2822.
	 *
	 * @param object $message A reference to a Message object into which the
	 *        operation will be rendered. The content of the render operation
	 *        will be assigned to the `$body` property of the object.
	 * @param mixed $data
	 * @param array $options
	 * @return void
	 * @filter
	 */
	public static function render(&$message, $data = null, array $options = array()) {
		$params = array('message' => &$message) + compact('data', 'options');
		$handlers = static::_handlers();

		$filter = function($self, $params) use ($handlers) {
			$defaults = array(
				'template' => null, 'layout' => 'default', 'view' => null
			);
			$message =& $params['message'];
			$data = $params['data'];
			$options = $params['options'];

			foreach ((array) $message->types as $type) {
				if (!isset($handlers[$type])) {
					throw new MediaException("Unhandled media type `{$type}`.");
				}
				$handler = $options + $handlers[$type];
				$handler += $defaults + array('type' => $type);
				$filter = function($v) { return $v !== null; };
				$handler = array_filter($handler, $filter);
				$handler += $handlers['default'] + $defaults;
				$handler['paths'] = $self::invokeMethod(
					'_finalizePaths',
					array($handler['paths'], $options)
				);

				$params = array($handler, $data, $message);
				$message->body($type, $self::invokeMethod('_handle', $params));
			}
			$message->ensureStandardCompliance();
		};
		static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Called by `Media::render()` to render message content. Given a content
	 * handler and data, calls the content handler and passes in the data,
	 * receiving back a rendered content string.
	 *
	 * @see lithium\net\mail\Message
	 * @param array $handler Handler configuration.
	 * @param array $data Binded data.
	 * @param object $message A reference to the `Message` object for rendering.
	 * @return string Rendered content.
	 * @filter
	 */
	protected static function _handle($handler, $data, &$message) {
		$params = array('message' => &$message) + compact('handler', 'data');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$message = $params['message'];
			$handler = $params['handler'];
			$data = $params['data'];
			$options = $handler;

			switch (true) {
				case ($handler['template'] === false) && is_string($data):
					return $data;
				case $handler['view']:
					unset($options['view']);
					$view = $self::view($handler, $data, $message, $options);
					return $view->render('all', (array) $data, $options);
				default:
					$error = 'Could not interpret type settings for handler.';
					throw new MediaException($error);
			}
		});
	}

	/**
	 * Configures a template object instance, based on a
	 * media handler configuration.
	 *
	 * @see li3_mailer\net\mail\Media::type()
	 * @see lithium\template\View::render()
	 * @see li3_mailer\net\mail\Message
	 * @param mixed $handler Either a string specifying the name of a media type
	 *              for which a handler is defined, or an array representing a
	 *              handler configuration. For more on types and type handlers,
	 *              see the `type()` method.
	 * @param mixed $data The data to be rendered. Usually an array.
	 * @param object $message The `Message` object.
	 * @param array $options Any options that will be passed to the `render()`
	 *              method of the templating object.
	 * @return object Returns an instance of a templating object, usually
	 *                `lithium\template\View`.
	 * @filter
	 */
	public static function view($handler, $data, &$message = null, array $options = array()) {
		$params = array('message' => &$message);
		$params += compact('handler', 'data', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$data = $params['data'];
			$options = $params['options'];
			$handler = $params['handler'];
			$message =& $params['message'];

			if (!is_array($handler)) {
				$handler = $self::invokeMethod('_handlers', array($handler));
			}
			$class = $handler['view'];
			unset($handler['view']);

			$config = $handler + array('message' => &$message);
			return $self::invokeMethod('_instance', array($class, $config));
		});
	}

	/**
	 * Calculates the absolute path to a static asset when attaching. By default
	 * a relative path will be prepended with the given library's path and
	 * `'/mails/_assets/'` (e.g. the path `'foo/bar.txt'` with the default
	 * library will be resolved to
	 * `'/path/to/li3_install/app/mails/_assets/foo/bar.txt'`).
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @param string $path The path to the asset, relative to the given
	 *        library's path. If the path contains a URI Scheme (eg. `http://`)
	 *        or is absolute, no path munging will occur.
	 * @param array $options Contains setting for finding and handling the path,
	 *        where the keys are the following:
	 *        - `'check'`: Check for the existence of the file before returning.
	 *          Defaults to `false`.
	 *        - `'library'`: The name of the library from which to load the
	 *          asset. Defaults to `true`, for the default library.
	 * @return string Returns the absolute path to the static asset. If checking
	 *         for the asset's existence (`$options['check']`), returns `false`
	 *         if it does not exist.
	 * @filter
	 */
	public static function asset($path, array $options = array()) {
		$defaults = array(
			'check' => false,
			'library' => true
		);
		$options += $defaults;
		$params = compact('path', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			extract($params);

			if (preg_match('/^[a-z0-9-]+:\/\//i', $path)) {
				return $path;
			}
			if ($path[0] !== '/') {
				$base = Libraries::get($options['library'], 'path');
				$path = $base . '/mails/_assets/' . $path;
			}
			if ($options['check'] && !is_file($path)) {
				return false;
			}
			return $path;
		});
	}

	/**
	 * Helper method for listing registered media types. Returns all types,
	 * or a single content type if a specific type is specified.
	 *
	 * @param string $type Type to return.
	 * @return mixed Array of types, or single type requested.
	 */
	protected static function _types($type = null) {
		$types = static::$_types + array(
			'html'         => 'text/html',
			'text'         => 'text/plain'
		);
		if ($type) {
			return isset($types[$type]) ? $types[$type] : null;
		}
		return $types;
	}

	/**
	 * Helper method for listing registered type handlers. Returns all handlers,
	 * or the handler for a specific media type, if requested.
	 *
	 * @param string $type The type of handler to return.
	 * @return mixed Array of all handlers, or the handler for a specific type.
	 */
	protected static function _handlers($type = null) {
		$template = array(
			'{:library}/mails/{:mailer}/{:template}.{:type}.php' => 'mailer',
			'{:library}/mails/{:template}.{:type}.php'
		);
		$defaultPaths = compact('template') + array(
			'layout'   => '{:library}/mails/layouts/{:layout}.{:type}.php',
			'element'  => '{:library}/mails/elements/{:template}.{:type}.php'
		);

		$handlers = static::$_handlers + array(
			'default' => array(
				'view' => 'li3_mailer\template\Mail',
				'paths' => $defaultPaths
			),
			'html' => array(),
			'text' => array()
		);

		if ($type) {
			return isset($handlers[$type]) ? $handlers[$type] : null;
		}
		return $handlers;
	}

	/**
	 * Finalize paths according to available data. Paths defined as arrays
	 * may have the `String::insert()` style paths as the indexes and use
	 * a string or array of strings of keys that should be presented in the
	 * data to enable that path. This way conditional paths may be defined,
	 * and is used by the `'default'` handler to enable/disable `'template'`
	 * paths based on whether the `'mailer'` is available.
	 *
	 * @see li3_mailer\net\mail\Media::_handlers()
	 * @see li3_mailer\net\mail\Media::render()
	 * @param array $paths The paths configuration that should be finalized.
	 * @param array $data The data.
	 * @return array Finalized paths.
	 */
	protected static function _finalizePaths($paths, array $data) {
		$finalized = array();
		foreach ($paths as $type => $path) {
			if (!is_array($path)) {
				$finalized[$type] = $path;
				continue;
			}
			$subfinalized = array();
			foreach ((array) $path as $string => $needed) {
				$numKeys = is_int($string);
				if ($numKeys || is_null($needed) || ($needed === array())) {
					$subfinalized[] = $numKeys ? $needed : $string;
					continue;
				}
				foreach ((array) $needed as $var) {
					if (!isset($data[$var])) {
						continue 2;
					}
				}
				$subfinalized[] = $string;
			}
			$finalized[$type] = $subfinalized;
		}
		return $finalized;
	}

	/**
	 * Creates a Request that can be used for rendering (e.g. when constructing
	 * urls for example). Tries to set the request's `'HTTP_HOST'`, `'HTTPS'`
	 * and `'base'` variables according to message's `$baseURL`.
	 *
	 * @see li3_mailer\template\mail\adapter\File::_init()
	 * @see li3_mailer\net\mail\Message::$baseURL
	 * @param object $message Message.
	 * @return object Request.
	 * @filter
	 */
	protected static function _request($message) {
		$params = compact('message');
		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			extract($params);
			$config = array();
			if ($message && ($baseURL = $message->baseURL)) {
				list($scheme, $url) = explode('://', $baseURL);
				$parts = explode('/', $url, 2);
				$host = array_shift($parts);
				$base = array_shift($parts);
				$base = $base ? '/' . $base : '';
				$env = array('HTTP_HOST' => $host);
				if ($scheme === 'https') {
					$env['HTTPS'] = true;
				}
				$config += compact('env', 'base');
			}
			return $self::invokeMethod('_instance', array('request', $config));
		});
	}
}

?>