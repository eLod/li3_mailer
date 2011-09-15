<?php

namespace li3_mailer\net\mail\transport\adapter;

use RuntimeException;

/**
 * The `Simple` adapter sends email messages with `PHP`'s built-in function `mail`.
 *
 * An example configuration:
 * {{{Delivery::config(array('simple' => array(
 *     'adapter' => 'Simple', 'from' => 'my@address'
 * )));}}}
 * Apart from message parameters (like `'from'`, `'to'`, etc.) no options
 * supported.
 *
 * @see http://php.net/manual/en/function.mail.php
 * @see li3_mailer\net\mail\Delivery
 * @see li3_mailer\net\mail\transport\adapter\Simple::deliver()
 */
class Simple extends \li3_mailer\net\mail\Transport {
	/**
	 * Message property names for translating a `li3_mailer\net\mail\Message`
	 * properties to headers (these properties are addresses).
	 *
	 * @see li3_mailer\net\mail\transport\adapter\Simple::deliver()
	 * @see li3_mailer\net\mail\Message
	 * @var array
	 */
	protected $_message_addresses = array(
		'return_path' => 'Return-Path', 'sender', 'from',
		'reply_to' => 'Reply-To', 'to', 'cc', 'bcc'
	);

	/**
	 * Deliver a message with `PHP`'s built-in `mail` function.
	 *
	 * @see http://php.net/manual/en/function.mail.php
	 * @param object $message The message to deliver.
	 * @param array $options No options supported.
	 * @return mixed The return value of the `mail` function.
	 */
	public function deliver($message, array $options = array()) {
		$headers = $message->headers;
		foreach ($this->_message_addresses as $property => $header) {
			if (is_int($property)) {
				$property = $header;
				$header = ucfirst($property);
			}
			$headers[$header] = $this->address($message->$property);
		}
		$headers['Date'] = date('r', $message->date);
		$headers['MIME-Version'] = "1.0";

		$types = $message->types();
		$attachments = $message->attachments();
		$charset = $message->charset;
		if (count($types) == 1 && count($attachments) == 0) {
			$type = key($types);
			$content_type = current($types);
			$headers['Content-Type'] = "{$content_type};charset=\"{$charset}\"";
			$body = wordwrap($message->body($type), 70);
		} else {
			$boundary = uniqid('LI3_MAILER_SIMPLE_');
			$headers['Content-Type'] = "multipart/alternative;boundary=\"{$boundary}\"";
			$body = "This is a multi-part message in MIME format.\n\n";
			foreach ($types as $type => $content_type) {
				$body .= "--{$boundary}\n";
				$body .= "Content-Type: {$content_type};charset=\"{$charset}\"\n\n";
				$body .= wordwrap($message->body($type), 70) . "\n";
			}
			foreach ($attachments as $attachment) {
				if (isset($attachment['path'])) {
					if ($attachment['path'][0] == '/' && !is_readable($attachment['path'])) {
						$content = false;
					} else {
						$content = file_get_contents($attachment['path']);
					}
					if ($content === false) {
						throw new RuntimeException("Can not attach path `{$attachment['path']}`.");
					}
				} else {
					$content = $attachment['data'];
				}
				$body .= "--{$boundary}\n";
				$filename = isset($attachment['filename']) ? $attachment['filename'] : null;
				if (isset($attachment['content-type'])) {
					$content_type = $attachment['content-type'];
					if ($filename && !preg_match('/;\s+name=/', $content_type)) {
						$content_type .= "; name=\"{$filename}\"";
					}
					$body .= "Content-Type: {$content_type}\n";
				}
				if (isset($attachment['disposition'])) {
					$disposition = $attachment['disposition'];
					if ($filename && !preg_match('/;\s+filename=/', $disposition)) {
						$disposition .= "; filename=\"{$filename}\"";
					}
					$body .= "Content-Disposition: {$disposition}\n";
				}
				if (isset($attachment['id'])) {
					$body .= "Content-ID: <{$attachment['id']}>\n";
				}
				$body .= "\n" . wordwrap($content, 70) . "\n";
			}
			$body .= "--{$boundary}--";
		}

		$headers = join("\r\n", array_map(function($name, $value) {
			return "{$name}: {$value}";
		}, array_keys($headers), $headers));
		$to = $this->address($message->to);
		return mail($to, $message->subject, $body, $headers);
	}
}

?>