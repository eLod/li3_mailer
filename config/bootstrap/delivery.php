<?php

/**
 * ### Configure email delivery
 *
 * The `Delivery` is a configurable service that provides transport adapters for sending emails
 * from your application. As with other `Adaptable`-based configurations, each delivery
 * configuration is defined by a name, and an array of information detailing extra configuration
 * for that delivery adapter. `Delivery` also supports environment-base configurations.
 *
 * By default the plugin provides 3 adapters:
 *
 *  - `'Simple'`: uses `PHP`'s built-in `mail` function to send messages,
 *  - `'Debug'`: does not send mails, but logs them to a file,
 *  - `'Swift'`: uses the `SwiftMailer` library to send messages.
 *
 * For configuration options please see the adapters' documentation.
 *
 * Apart from the adapter options the configuration may hold settings for the messages that should
 * be sent with this adapter, like (but not limited to) `'from'`, `'cc'` and other fields. The
 * `Mailer` will retrieve the configuration when constructing the `Message` for delivery and set
 * applicable values.
 *
 * @see li3_mailer\net\mail\Delivery
 * @see lithium\core\Adaptable
 * @see li3_mailer\net\mail\Transport
 * @see li3_mailer\net\mail\transport\adapter\Simple
 * @see li3_mailer\net\mail\transport\adapter\Debug
 * @see li3_mailer\net\mail\transport\adapter\Swift
 * @see li3_mailer\net\mail\Message
 * @see li3_mailer\net\mail\Mailer
 * @see li3_mailer\net\mail\Mailer::deliver()
 */
use li3_mailer\net\mail\Delivery;

/**
 * Sample configuration for `'Simple'` adapter
 */
// Delivery::config(array('default' => array(
// 	'adapter' => 'Simple',
// 	'from' => array('My App' => 'my@email.address')
// )));

/**
 * Sample configuration for `'Swift'` adapter
 */
// Delivery::config(array('default' => array(
// 	'adapter' => 'Swift',
// 	'transport' => 'smtp',
// 	'from' => array('Name' => 'my@address'),
// 	'host' => 'example.host',
// 	'encryption' => 'ssl'
// )));

/**
 * ### SwiftMailer support
 *
 * To use the `'Swift'` adapter with the plugin the SwiftMailer library must be register first.
 * To install the library execute
 * `git submodule add https://github.com/swiftmailer/swiftmailer.git libraries/swiftmailer`
 * and uncomment the following lines.
 */
// use lithium\core\Libraries;
// Libraries::add('swiftmailer', array(
// 	'prefix' => 'Swift_',
//	'bootstrap' => 'lib/swift_required.php'
// ));

?>