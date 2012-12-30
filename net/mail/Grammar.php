<?php

namespace li3_mailer\net\mail;

/**
 * Grammar rules for checking Message validity (particularly Content ID syntax),
 * implements the RFC 2822 (and friends) ABNF grammar definitions.
 *
 * @see http://tools.ietf.org/html/rfc2822
 * @see li3_mailer\net\mail\Message
 */
class Grammar extends \lithium\core\Object {
	/**
	 * Tokens and matching regular expression( part)s as key value pairs,
	 * defined in RFC 2822 (and some related RFCs).
	 *
	 * @var array
	 */
	protected $_grammar = array();

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('grammar' => 'merge');

	/**
	 * Adds config values to the public properties when a new object is created.
	 *
	 * @param array $config Options.
	 */
	public function __construct(array $config = array()) {
		$grammar = array();
		$grammar['NO-WS-CTL'] = '[\x01-\x08\x0B\x0C\x0E-\x19\x7F]';
		$grammar['text'] = '[\x00-\x08\x0B\x0C\x0E-\x7F]';
		$grammar['quoted-pair'] = "(?:\\\\{$grammar['text']})";
		$grammar['qtext'] = "(?:{$grammar['NO-WS-CTL']}|" .
			'[\x21\x23-\x5B\x5D-\x7E])';
		$grammar['atext'] = '[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_`\{\}\|~]';
		$grammar['dot-atom-text'] = "(?:{$grammar['atext']}+" .
			"(\.{$grammar['atext']}+)*)";
		$grammar['no-fold-quote'] = "(?:\"(?:{$grammar['qtext']}|" .
			"{$grammar['quoted-pair']})*\")";
		$grammar['dtext'] = "(?:{$grammar['NO-WS-CTL']}|" .
			'[\x21-\x5A\x5E-\x7E])';
		$grammar['no-fold-literal'] = "(?:\[(?:{$grammar['dtext']}|" .
			"{$grammar['quoted-pair']})*\])";
		$grammar['id-left'] = "(?:{$grammar['dot-atom-text']}|" .
			"{$grammar['no-fold-quote']})";
		$grammar['id-right'] = "(?:{$grammar['dot-atom-text']}|" .
			"{$grammar['no-fold-literal']})";
		$provided = isset($config['grammar']) ? $config['grammar'] : null;
		$grammar = array_merge_recursive($grammar, (array) $provided);
		parent::__construct(compact('grammar') + $config);
	}

	/**
	 * Set or retrieve grammar definition or definitons. If called
	 * without arguments (or both arguments are null) returns all
	 * the defined grammar rules. If only `$key` is provided returns
	 * the named definition only (if found, `null` otherwise). If
	 * called with both arguments set it sets the specified grammar
	 * rule to the given `$value` (and returns `null`).
	 *
	 * @see li3_mailer\net\mail\Grammar
	 * @param string $key Grammar definition key (token name).
	 * @param string $value Grammar definition (regular expression part).
	 * @return mixed All the rules (array), a specific rule (string), or `null`.
	 */
	public function token($key = null, $value = null) {
		if ($key) {
			if ($value) {
				$this->_grammar[$key] = $value;
				return;
			}
			return isset($this->_grammar[$key]) ? $this->_grammar[$key] : null;
		}
		return $this->_grammar;
	}

	/**
	 * Checks if the id passed comply with RFC 2822.
	 *
	 * @param string $id Id.
	 * @return boolean Result.
	 */
	public function isValidId($id) {
		$address = $this->token('id-left') . '@' . $this->token('id-right');
		$pattern = '/^' . $address . '$/D';
		return (boolean) preg_match($pattern, $id);
	}
}

?>