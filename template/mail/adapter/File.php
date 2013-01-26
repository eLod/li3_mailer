<?php

namespace li3_mailer\template\mail\adapter;

use lithium\core\Object;
use RuntimeException;
use lithium\core\Libraries;
use lithium\core\ClassNotFoundException;

/**
 * The `File` adapter for mail messages is a modified view adapter of the same
 * name that is suitable for rendering mail messages instead of http responses.
 *
 * @see lithium\template\view\adapter\File
 */
class File extends \lithium\template\view\adapter\File {
	/**
	 * These configuration variables will automatically be assigned to their
	 * corresponding protected properties when the object is initialized.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'classes' => 'merge', 'message', 'context',
		'strings', 'handlers', 'view', 'compile', 'paths'
	);

	/**
	 * Context values that exist across all templates rendered in this context.
	 * These values are usually rendered in the layout template after all other
	 * values have rendered.
	 *
	 * @var array
	 */
	protected $_context = array(
		'content' => '', 'scripts' => array(),
		'styles' => array(), 'head' => array()
	);

	/**
	 * `File`'s dependencies. These classes are used by the output handlers to
	 * generate URLs for dynamic resources and static assets, as well as
	 * compiling the templates.
	 *
	 * @see Renderer::$_handlers
	 * @var array
	 */
	protected $_classes = array(
		'compiler' => 'li3_mailer\template\mail\Compiler',
		'router' => 'lithium\net\http\Router',
		'media'  => 'li3_mailer\net\mail\Media',
		'http_media'  => 'lithium\net\http\Media'
	);

	/**
	 * The `Message` object instance, if applicable.
	 *
	 * @var object The message object.
	 */
	protected $_message = null;

	/**
	 * Renderer constructor.
	 *
	 * Accepts these following configuration parameters:
	 * - `view`: The `View` object associated with this renderer.
	 * - `strings`: String templates used by helpers.
	 * - `handlers`: An array of output handlers for string template inputs.
	 * - `message`: The `Message` object associated with this renderer.
	 * - `context`: An array of the current rendering context data,
	 *   like `content`.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'message' => null,
			'context' => array(
				'content' => '', 'scripts' => array(),
				'styles' => array(), 'head' => array()
			)
		);
		parent::__construct((array) $config + $defaults);
	}

	/**
	 * Sets the default output handlers for string template inputs.
	 *
	 * Please note: skips lithium\template\view\Renderer::_init()
	 * to skip setting handlers.
	 *
	 * @return void
	 */
	protected function _init() {
		Object::_init();

		$classes =& $this->_classes;
		$message =& $this->_message;
		if (!$this->_request && $message) {
			$media = $classes['media'];
			$this->_request = $media::invokeMethod('_request', array($message));
		}
		$request =& $this->_request;
		$context =& $this->_context;
		$h = $this->_view ? $this->_view->outputFilters['h'] : null;

		$this->_handlers += array(
			'url' => function($url, $ref, array $options = array())
			use ($classes, &$request, $h) {
				$router = $classes['router'];
				$options += array('absolute' => true);
				$url = $router::match($url ?: '', $request, $options);
				return $h ? str_replace('&amp;', '&', $h($url)) : $url;
			},
			'path' => function($path, $ref, array $options = array())
			use ($classes, &$request, &$message) {
				$embed = isset($options['embed']) && $options['embed'];
				unset($options['embed']);
				if ($embed) {
					return 'cid:' . $message->embed($path, $options);
				} else {
					$type = 'generic';

					if (is_array($ref) && $ref[0] && $ref[1]) {
						list($helper, $methodRef) = $ref;
						list($class, $method) = explode('::', $methodRef);
						$type = $helper->contentMap[$method];
					}
					$httpMedia = $classes['http_media'];
					$base = $request ? $request->env('base') : '';
					$options += compact('base');
					$path = $httpMedia::asset($path, $type, $options);
					if ($path[0] === '/') {
						$host = '';
						if ($request) {
							$https = $request->env('HTTPS') ? 's' : '';
							$host .= "http{$https}://";
							$host .= $request->env('HTTP_HOST');
						}
						$path = $host . $path;
					}
					return $path;
				}
			},
			'options' => '_attributes',
			'title'   => 'escape',
			'scripts' => function($scripts) use (&$context) {
				return "\n\t" . join("\n\t", $context['scripts']) . "\n";
			},
			'styles' => function($styles) use (&$context) {
				return "\n\t" . join("\n\t", $context['styles']) . "\n";
			},
			'head' => function($head) use (&$context) {
				return "\n\t" . join("\n\t", $context['head']) . "\n";
			}
		);

		unset($this->_config['view']);
	}

	/**
	 * Returns the `Message` object associated with this rendering context.
	 *
	 * @return object Returns an instance of `li3_mailer\net\mail\Message`,
	 *         which provides the i.e. the encoding for the document being
	 *         the result of templates rendered by this context.
	 */
	public function message() {
		return $this->_message;
	}

	/**
	 * Brokers access to helpers attached to this rendering context, and loads
	 * helpers on-demand if they are not available.
	 *
	 * @param string $name Helper name
	 * @param array $config
	 * @return object
	 */
	public function helper($name, array $config = array()) {
		if (isset($this->_helpers[$name])) {
			return $this->_helpers[$name];
		}
		try {
			$config += array('context' => $this);
			$path = 'helper.mail';
			$helper = Libraries::instance($path, ucfirst($name), $config);
			return $this->_helpers[$name] = $helper;
		} catch (ClassNotFoundException $e) {
			throw new RuntimeException("Mail helper `{$name}` not found.");
		}
	}

	/**
	 * Shortcut method used to render elements and other nested templates from
	 * inside the templating layer.
	 *
	 * @param string $type The type of template to render, usually either
	 *               `'element'` or `'template'`. Indicates the process used to
	 *               render the content.
	 *               See `lithium\template\View::$_processes` for more info.
	 * @param string $template The template file name. For example, if
	 *               `'header'` is passed, and `$type` is set to `'element'`,
	 *               then the template rendered will be
	 *               `views/elements/header.html.php`
	 *               (assuming the default configuration).
	 * @param array $data An array of any other local variables that should be
	 *              injected into the template. By default, only the values used
	 *              to render the current template will be sent. If `$data` is
	 *              non-empty, both sets of variables will be merged.
	 * @param array $options Any options accepted by `template\View::render()`.
	 * @return string Returns a the rendered template content as a string.
	 */
	protected function _render($type, $template, array $data = array(), array $options = array()) {
		$data += $this->_data;
		$options = compact('template') + $options;
		return $this->_view->render($type, $data, $options);
	}
}

?>