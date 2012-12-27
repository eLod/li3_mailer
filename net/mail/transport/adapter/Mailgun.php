<?php

namespace li3_mailer\net\mail\transport\adapter;

use RuntimeException;

/**
 * The `Mailgun` adapter sends email through Mailgun's HTTP REST API.
 * _NOTE: This transport requires an API account (and associated key)
 * from Mailgun, as well as the `cURL` extension for PHP._
 *
 * A minimal example configuration:
 * {{{Delivery::config(array('mailgun' => array(
 *     'adapter' => 'Mailgun',
 *     'from' => 'my@address',
 *     'key' => 'mysecretkey',
 *     'domain' => 'my.domain'
 * )));}}}
 * Apart from message parameters (like `'from'`, `'to'`, etc.) for supported
 * options see `_parameters()` and `deliver()`.
 *
 * @see http://documentation.mailgun.net
 * @see http://php.net/curl
 * @see li3_mailer\net\mail\transport\adapter\Mailgun::_parameters()
 * @see li3_mailer\net\mail\transport\adapter\Mailgun::deliver()
 * @see li3_mailer\net\mail\Delivery
 * @author Mitch Pirtle (https://github.com/spacemonkey)
 * @author Francesco Cogoni (https://github.com/fcogoni)
 */
class Mailgun extends \li3_mailer\net\mail\transport\adapter\Simple {
	/**
	 * Extra parameters supported by the Mailgun API.
	 *
	 * @see http://documentation.mailgun.net/api-sending.html
	 * @see li3_mailer\net\mail\transport\adapter\Mailgun::_parameters()
	 * @see li3_mailer\net\mail\Message
	 * @var array
	 */
	protected $_extraParameters = array(
		'tag' => 'array', 'campaign', 'dkim', 'deliverytime', 'testmode',
		'tracking', 'tracking-clicks', 'tracking-opens'
	);

	/**
	 * Classes used by `Mailgun`.
	 *
	 * @var array
	 */
	protected $_classes = array('curl' => 'lithium\net\socket\Curl');

	/**
	 * Deliver a message with Mailgun's HTTP REST API via curl.
	 *
	 * _NOTE: Uses the `messages.mime` API endpoint, not the
	 * `messages` API endpoint (because a, if embedded attachments
	 * were used Mailgun would alter the `Content-ID` for them, and
	 * b, cURL needs to have a local file to send as file, but
	 * not all attachments have a path), see `_parameters()`._
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Mailgun::_parameters()
	 * @see http://documentation.mailgun.net/api-sending.html
	 * @see http://php.net/curl
	 * @param object $message The message to deliver.
	 * @param array $options Options (see `_parameters()`).
	 * @return mixed The return value of the `curl_exec` function.
	 */
	public function deliver($message, array $options = array()) {
		list($url, $key, $parameters) = $this->_parameters($message, $options);

		$curl = new $this->_classes['curl']();
		$curl->open();

		$curl->set(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$curl->set(CURLOPT_USERPWD, $key);
		$curl->set(CURLOPT_RETURNTRANSFER, 1);

		$curl->set(CURLOPT_CUSTOMREQUEST, 'POST');
		$curl->set(CURLOPT_URL, $url);
		$curl->set(CURLOPT_POSTFIELDS, $parameters);

		$result = $curl->read();
		$curl->close();

		return $result;
	}

	/**
	 * Translate the message, configuration and given options into
	 * supported parameters for the Mailgun API.
	 *
	 * Returns an array including the following:
	 *  - the API URL: can be configured two ways
	 *    - with option keys `'api'` and `'domain'` the URL will be
	 *      `$api/$domain/messages.mime`
	 *      (`'api'` defaults to `'https://api.mailgun.net/v2'`)
	 *    - with the option key `'url'` it will be final URL
	 *  - the API key: can be configured with the option key `'key'`
	 *  - parameters:
	 *    - `'to'` address
	 *    - generated message (with headers, in MIME format)
	 *    - extra parameters, see `$_extraParameters`
	 *    - variables with the option key `'variables'`
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Mailgun::$_extraParameters
	 * @see http://documentation.mailgun.net/api-sending.html
	 * @param object $message The message to deliver.
	 * @param array $options Given options.
	 * @return array An array including the API URL, secret key and parameters.
	 */
	protected function _parameters($message, array $options = array()) {
		$defaults = array('api' => 'https://api.mailgun.net/v2');
		$config = $this->_config + $options + $defaults;

		if (isset($config['url'])) {
			$url = $config['url'];
		} else {
			if (!isset($config['domain'])) {
				$error = "No `domain` (nor `url`) configured ";
				$error .= "for `Mailgun` transport adapter.";
				throw new RuntimeException($error);
			}
			if (substr($config['api'], -1) === '/') {
				$error = "API endpoint should not end with '/'.";
				throw new RuntimeException($error);
			}
			if (substr($config['domain'], 0, 1) === '/') {
				$error = "Domain should not start with '/'.";
				throw new RuntimeException($error);
			}
			if (substr($config['domain'], -1) === '/') {
				$error = "Domain should not end with '/'.";
				throw new RuntimeException($error);
			}
			$url = array($config['api'], $config['domain'], 'messages.mime');
			$url = join($url, "/");
		}

		$parameters = array('to' => $this->_address($message->to));
		list($headers, $body) = $this->_generate($message);
		$parameters['message'] = $headers . "\r\n" . $body;
		foreach ($this->_extraParameters as $name => $type) {
			if (is_int($name)) {
				$name = $type;
				$type = null;
			}
			if (isset($config[$name])) {
				if ($type === "array") {
					$list = array_values($config[$name]);
					foreach ($list as $idx => $val) {
						$key = 'o:' . $name . '[' . ($idx + 1) . ']';
						$parameters[$key] = $val;
					}
				} else {
					$parameters['o:' . $name] = $config[$name];
				}
			}
		}
		if (isset($config['variables'])) {
			foreach ((array) $config['variables'] as $name => $val) {
				$parameters['v:' . $name] = $val;
			}
		}

		return array($url, $config['key'], $parameters);
	}

}

?>