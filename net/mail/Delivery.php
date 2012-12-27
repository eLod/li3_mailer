<?php

namespace li3_mailer\net\mail;

/**
 * The `Delivery` class provides a consistent interface for configuring
 * email delivery. Apart from the options for the adapter(s) the configuration
 * can hold values for constructing the message (e.g. `'to'`, `'bcc'` and other
 * supported options, see `Message`).
 *
 * A simple example configuration (**please note**: you'll need the
 * SwiftMailer library for the `'Swift'` adapter to work):
 *
 * {{{Delivery::config(array(
 *     'local' => array('adapter' => 'Simple', 'from' => 'you@example.com'),
 *     'debug' => array('adapter' => 'Debug'),
 *     'default' => array(
 *         'adapter' => 'Swift',
 *         'from' => 'you@example.com',
 *         'bcc' => 'bcc@example.com',
 *         'transport' => 'smtp',
 *         'host' => 'example.com'
 *     )
 * ));}}}
 *
 * @see li3_mailer\net\mail\Message
 * @see li3_mailer\net\mail\transport\adapter\Simple
 * @see li3_mailer\net\mail\transport\adapter\Debug
 * @see li3_mailer\net\mail\transport\adapter\Swift
 */
class Delivery extends \lithium\core\Adaptable {
	/**
	 * A dot-separated path for use by `Libraries::locate()`.
	 * Used to look up the correct type of adapters for this class.
	 *
	 * @var string
	 */
	protected static $_adapters = 'adapter.net.mail.transport';
}

?>