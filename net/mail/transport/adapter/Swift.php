<?php

namespace li3_mailer\net\mail\transport\adapter;

use lithium\util\Inflector;
use Swift_MailTransport;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use RuntimeException;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;

/**
 * The `Swift` adapter sends email messages with the SwiftMailer library.
 *
 * An example configuration:
 * {{{Delivery::config(array('swift' => array(
 *     'adapter' => 'Swift',
 *     'from' => 'my@address',
 *     'transport' => 'smtp',
 *     'host' => 'example.host',
 *     'encryption' => 'ssl'
 * )));}}}
 * The adapter supports the `Swift_MailTransport`, `Swift_SendmailTransport` and
 * `Swift_SmtpTransport` transports (configured with `'transport'` set
 * to `'mail'`, `'sendmail'` and `'smtp'` respectively).
 * Apart from message parameters (like `'from'`, `'to'`, etc.) for supported
 * options see `$_transport` and `deliver()`.
 *
 * @see http://swiftmailer.org/
 * @see li3_mailer\net\mail\transport\adapter\Swift::$_transport
 * @see li3_mailer\net\mail\transport\adapter\Swift::deliver()
 * @see li3_mailer\net\mail\Delivery
 */
class Swift extends \li3_mailer\net\mail\Transport {
	/**
	 * Transport option names indexed by transport type.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::transport()
	 * @var array
	 */
	protected $_transport = array(
		'mail' => 'extra_params',
		'sendmail' => 'command',
		'smtp' => array(
			'host', 'port', 'timeout', 'encryption', 'sourceip', 'local_domain',
			'auth_mode', 'username', 'password'
		)
	);

	/**
	 * Message property names for translating a `li3_mailer\net\mail\Message`
	 * to `Swift_Message`.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::_message()
	 * @see li3_mailer\net\mail\Message
	 * @see Swift_Message
	 * @var array
	 */
	protected $_messageProperties = array(
		'subject', 'date', 'returnPath', 'sender' => 'address',
		'from' => 'address', 'replyTo' => 'address', 'to' => 'address',
		'cc' => 'address', 'bcc' => 'address', 'charset'
	);

	/**
	 * Message attachment configuration names for translating a
	 * `li3_mailer\net\mail\Message`'s attachments to `Swift_Attachment`s.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::_message()
	 * @see li3_mailer\net\mail\Message
	 * @see Swift_Attachment
	 * @var array
	 */
	protected $_attachmentProperties = array(
		'disposition', 'content-type', 'filename', 'id'
	);

	/**
	 * Deliver a message with the SwiftMailer library. For available
	 * options see `_transport()`.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::_transport()
	 * @see li3_mailer\net\mail\transport\adapter\Swift::_message()
	 * @param object $message The message to deliver.
	 * @param array $options Options (see `_transport()`).
	 * @return mixed The return value of the `Swift_Mailer::send()` method.
	 */
	public function deliver($message, array $options = array()) {
		$transport = $this->_transport($options);
		$message = $this->_message($message);
		return $transport->send($message);
	}

	/**
	 * Get a transport mailer. Creates a Swift transport with type
	 * `$_config['transport']` and applies options (see `$_transport`)
	 * to it from `$_config` and `$options`.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::$_transport
	 * @see Swift_Mailer
	 * @see Swift_SmtpTransport
	 * @see Swift_SendmailTransport
	 * @see Swift_MailTransport
	 * @param array $options Options, see `$_transport`.
	 * @return object A (`Swift_Mailer`) mailer transport for sending
	 *         (should respond to `send`).
	 */
	protected function _transport(array $options = array()) {
		if (isset($this->_config['transport'])) {
			$type = $this->_config['transport'];
		} else {
			$type = null;
		}
		switch ($type) {
			case 'mail':
				$transport = Swift_MailTransport::newInstance();
			break;
			case 'sendmail':
				$transport = Swift_SendmailTransport::newInstance();
			break;
			case 'smtp':
				$transport = Swift_SmtpTransport::newInstance();
			break;
			default:
				$error = "Unknown transport type `{$type}` " .
					"for `Swift` adapter.";
				throw new RuntimeException($error);
		}
		$blank = array_fill_keys((array) $this->_transport[$type], null);
		$options = array_intersect_key($options + $this->_config, $blank);
		foreach ($options as $prop => $value) {
			if (!is_null($value)) {
				$method = "set" . Inflector::camelize($prop);
				$transport->$method($value);
			}
		}
		return Swift_Mailer::newInstance($transport);
	}

	/**
	 * Create a `Swift_Message` from `li3_mailer\net\mail\Message`.
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Swift::$_messageProperties
	 * @see li3_mailer\net\mail\transport\adapter\Swift::$_attachmentProperties
	 * @see li3_mailer\net\mail\Message
	 * @see Swift_Message
	 * @param object $message The `Message` object to translate.
	 * @return object The translated `Swift_Message` object.
	 */
	protected function _message($message) {
		$swiftMessage = Swift_Message::newInstance();
		foreach ($this->_messageProperties as $prop => $translated) {
			if (is_int($prop)) {
				$prop = $translated;
			}
			if (!is_null($message->$prop)) {
				$value = $message->$prop;
				if ($translated === 'address') {
					$translated = $prop;
					if (is_array($value)) {
						$newvalue = array();
						foreach ($value as $name => $address) {
							if (is_int($name)) {
								$newvalue[] = $address;
							} else {
								$newvalue[$address] = $name;
							}
						}
						$value = $newvalue;
					}
				}
				$method = "set" . Inflector::camelize($translated);
				$swiftMessage->$method($value);
			}
		}
		$first = true;
		foreach ($message->types() as $type => $contentType) {
			if ($first) {
				$first = false;
				$swiftMessage->setBody($message->body($type), $contentType);
			} else {
				$swiftMessage->addPart($message->body($type), $contentType);
			}
		}
		$headers = $swiftMessage->getHeaders();
		foreach ($message->headers as $header => $value) {
			$headers->addTextHeader($header, $value);
		}
		foreach ($message->attachments() as $attachment) {
			if (isset($attachment['path'])) {
				$path = $attachment['path'];
				$swiftAttachment = Swift_Attachment::fromPath($path);
			} else {
				$data = $attachment['data'];
				$swiftAttachment = Swift_Attachment::newInstance($data);
			}
			foreach ($this->_attachmentProperties as $prop => $translated) {
				if (is_int($prop)) {
					$prop = $translated;
				}
				if (isset($attachment[$prop])) {
					$method = "set" . Inflector::camelize($translated);
					$swiftAttachment->$method($attachment[$prop]);
				}
			}
			$swiftMessage->attach($swiftAttachment);
		}
		return $swiftMessage;
	}
}

?>