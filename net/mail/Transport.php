<?php

namespace li3_mailer\net\mail;

/**
 * Base class for delivery (transport) adapters.
 *
 * @see li3_mailer\net\mail\Delivery
 * @see li3_mailer\net\mail\transport\adapter\Simple
 * @see li3_mailer\net\mail\transport\adapter\Debug
 * @see li3_mailer\net\mail\transport\adapter\Swift
 * @see li3_mailer\net\mail\transport\adapter\Mailgun
 */
abstract class Transport extends \lithium\core\Object {
	/**
	 * Deliver a message.
	 *
	 * @see li3_mailer\net\mail\Message
	 * @param object $message The message to deliver.
	 * @param array $options Options.
	 */
	abstract public function deliver($message, array $options = array());

	/**
	 * Format addresses.
	 *
	 * @param mixed $address Address to format, may be a string or array.
	 * @return string Formatted address list.
	 */
	protected function _address($address) {
		if (is_array($address)) {
			return join(", ", array_map(function($name, $address) {
				return is_int($name) ? $address : "{$name} <{$address}>";
			}, array_keys($address), $address));
		} else {
			return $address;
		}
	}
}

?>