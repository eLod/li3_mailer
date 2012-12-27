<?php

namespace li3_mailer\net\mail\transport\adapter;

use RuntimeException;

/**
 * The `Simple` adapter sends email messages with `PHP`'s built-in
 * function `mail`.
 *
 * An example configuration:
 * {{{Delivery::config(array('simple' => array(
 *     'adapter' => 'Simple', 'from' => 'my@address'
 * )));}}}
 * Apart from message parameters (like `'from'`, `'to'`, etc.) no options
 * supported.
 *
 * @see http://php.net/manual/en/function.mail.php
 * @see li3_mailer\net\mail\transport\adapter\Simple::deliver()
 * @see li3_mailer\net\mail\Delivery
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
	protected $_messageAddresses = array(
		'returnPath' => 'Return-Path', 'sender', 'from',
		'replyTo' => 'Reply-To', 'to', 'cc', 'bcc'
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
		foreach ($this->_messageAddresses as $property => $header) {
			if (is_int($property)) {
				$property = $header;
				$header = ucfirst($property);
			}
			$headers[$header] = $this->_address($message->$property);
		}
		$headers['Date'] = date('r', $message->date);
		$headers['MIME-Version'] = "1.0";

		$types = $message->types();
		$attachments = $message->attachments();
		$charset = $message->charset;
		if (count($types) === 1 && count($attachments) === 0) {
			$type = key($types);
			$contentType = current($types);
			$headers['Content-Type'] = "{$contentType};charset=\"{$charset}\"";
			$body = wordwrap($message->body($type), 70);
		} else {
			$boundary = uniqid('LI3_MAILER_SIMPLE_');
			$contentType = "multipart/alternative;boundary=\"{$boundary}\"";
			$headers['Content-Type'] = $contentType;
			$body = "This is a multi-part message in MIME format.\n\n";
			foreach ($types as $type => $contentType) {
				$body .= "--{$boundary}\n";
				$contentType .= ";charset=\"{$charset}\"";
				$body .= "Content-Type: {$contentType}\n\n";
				$body .= wordwrap($message->body($type), 70) . "\n";
			}
			foreach ($attachments as $attachment) {
				if (isset($attachment['path'])) {
					$local = $attachment['path'][0] === '/';
					if ($local && !is_readable($attachment['path'])) {
						$content = false;
					} else {
						$content = file_get_contents($attachment['path']);
					}
					if ($content === false) {
						$error = "Can not attach path `{$attachment['path']}`.";
						throw new RuntimeException($error);
					}
				} else {
					$content = $attachment['data'];
				}
				$body .= "--{$boundary}\n";
				if (isset($attachment['filename'])) {
					$filename =  $attachment['filename'];
				} else {
					$filename = null;
				}
				if (isset($attachment['content-type'])) {
					$contentType = $attachment['content-type'];
					if ($filename && !preg_match('/;\s+name=/', $contentType)) {
						$contentType .= "; name=\"{$filename}\"";
					}
					$body .= "Content-Type: {$contentType}\n";
				}
				if (isset($attachment['disposition'])) {
					$disposition = $attachment['disposition'];
					$pattern = '/;\s+filename=/';
					if ($filename && !preg_match($pattern, $disposition)) {
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
		$to = $this->_address($message->to);
		return mail($to, $message->subject, $body, $headers);
	}
}

?>