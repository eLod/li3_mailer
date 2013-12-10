<?php

namespace li3_mailer\template;

use lithium\core\Object;
use lithium\core\Libraries;
use lithium\g11n\Message;
/**
 * The `Mail` is a special `View` class that is responsible for rendering
 * (mail) message bodies and providing helpers.
 *
 * @see lithium\template\View
 */
class Mail extends \lithium\template\View {

	/**
	 * Holds a reference to the `Message` object that will be delivered.
	 * Allows headers and other message attributes to be assigned in the
	 * templating layer.
	 *
	 * @see li3_mailer\net\mail\Message
	 * @var object `Message` object instance.
	 */
	protected $_message = null;

	/**
	 * Auto-configuration parameters.
	 *
	 * @var array Objects to auto-configure.
	 */
	protected $_autoConfig = array(
		'message', 'processes' => 'merge', 'steps' => 'merge'
	);

	/**
	 * Perform initialization.
	 *
	 * @return void
	 */
	protected function _init() {
		Object::_init();
		extract(Message::aliases());

		$type = isset($this->_config['type']) ? $this->_config['type'] : null;
		if ($type === 'text') {
			$h = function($data) { return $data; };
		} else {
			$encoding = 'UTF-8';
			if ($this->_message) {
				$encoding =& $this->_message->charset;
			}
			$h = function($data) use (&$encoding) {
				return htmlspecialchars((string) $data, ENT_QUOTES, $encoding);
			};
		}
		$t = function($data, array $options = array()) use ($t) {
			echo $t((string) $data, $options);
		};
		$this->outputFilters += compact('h', 't') + $this->_config['outputFilters'];

		foreach (array('loader', 'renderer') as $key) {
			if (is_object($this->_config[$key])) {
				$this->{'_' . $key} = $this->_config[$key];
				continue;
			}
			$class = $this->_config[$key];
			$config = array('view' => $this) + $this->_config;
			$path = 'adapter.template.mail';
			$instance = Libraries::instance($path, $class, $config);
			$this->{'_' . $key} = $instance;
		}
	}
}

?>