<?php

namespace li3_mailer\net\mail\transport\adapter;

use RuntimeException;

/**
 * The `Mailgun` adapter sends email through Mailgun's REST API. 
 * _NOTE: This transport requires an API account (and associated 
 * key) from Mailgun, as well as the `curl` extension for PHP._
 *
 * An example configuration:
 * {{{Delivery::config(array('simple' => array(
 *     'adapter' => 'Mailgun',
 *     'from' => 'my@address',
 *     'key' => 'mysecretkey'
 * )));}}}
 * Additional methods specific to Mailgun are included but not 
 * mandatory for sending messages.
 *
 * @see http://php.net/curl
 * @see li3_mailer\net\mail\Delivery
 * @see li3_mailer\net\mail\transport\adapter\Mailgun::deliver()
 * @see http://documentation.mailgun.net
 */
class Mailgun extends \li3_mailer\net\mail\Transport {
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
	 * Deliver a message with Mailgun's REST API via curl.
	 *
	 * @see http://php.net/curl
	 * @see http://documentation.mailgun.net/quickstart.html#sending-messages
	 * @param object $message The message to deliver.
	 * @param array $options Additional Mailgun-specific options supported.
	 * @return mixed The return value of the `deliver` function.
	 */
	public function deliver($message, array $options = array()) {
		$config = $this->_config;
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

		// Setup and execute via CURL extension
		$ch = curl_init($config['url'] . 'messages');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $config['key']);
		$data = array(
			'from' => $message->from,
			'to' => $to,
			//'o:campaign' => '860s', // ADD THIS FOR A CAMPAIGN
			'subject' => $message->subject
		);
		// USE THIS FOR TEXT EMAIL
		$text = $message->body('text');
		if(isset($text)){
			$data['text'] = $text;
		}
		// USE THIS FOR HTML EMAIL
		$html = $message->body('html');
		if(isset($html)){
			$data['html'] = $html;
		}
		// USE THIS FOR CAMPAIGNS
		if(isset($message->campaign)){
			$data['o:campaign'] = $message->campaign;
		}
		//TAGS is always an array
		if(isset($message->tags) && is_array($message->tags) && count($message->tags)){
			$i = 1;
			foreach($message->tags as $tag){
				$data["o:tag[$i]"] = $tag;
				$i++;
			}
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_exec($ch);
		curl_close($ch);
	}

?>