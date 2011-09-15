<?php

namespace li3_mailer\net\mail;

use RuntimeException;
use lithium\action\Request;

/**
 * A mail message object (RFC 2822).
 *
 * @see http://tools.ietf.org/html/rfc2822
 */
class Message extends \lithium\core\Object {
	/**
	 * Subject.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * (Origination) date of the message as UNIX timestamp.
	 *
	 * @var integer
	 */
	public $date;

	/**
	 * Return-path.
	 *
	 * @var string
	 */
	public $return_path;

	/**
	 * Sender (this should be set to a single address
	 * when multiple from fields are present as per the RFC).
	 *
	 * @see li3_mailer\net\mail\Message::ensureCleanSender()
	 * @var array
	 */
	public $sender;

	/**
	 * From adress(es).
	 *
	 * @var array
	 */
	public $from;

	/**
	 * Reply-to adress(es).
	 *
	 * @var array
	 */
	public $reply_to;

	/**
	 * Recipient(s).
	 *
	 * @var array
	 */
	public $to;

	/**
	 * Cc address(es).
	 *
	 * @var array
	 */
	public $cc;

	/**
	 * Bcc address(es).
	 *
	 * @var array
	 */
	public $bcc;

	/**
	 * Content-Types.
	 *
	 * @var array
	 */
	public $types = array('html', 'text');

	/**
	 * Character set.
	 *
	 * @var string
	 */
	public $charset = 'UTF-8';

	/**
	 * Headers.
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * The body of the message, indexed by content type.
	 *
	 * @var array
	 */
	public $body = array();

	/**
	 * Base URL for the message that should be used to generate
	 * URLs (and the host part is used to construct Content-IDs).
	 *
	 * @see li3_mailer\net\mail\Message::_init()
	 * @see li3_mailer\net\mail\Message::discoverURL()
	 * @see li3_mailer\net\mail\Media::_request()
	 * @see li3_mailer\net\mail\Message::randomId()
	 * @var string
	 */
	public $base_url = null;

	/**
	 * Attachments for the message.
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @see li3_mailer\net\mail\Message::detach()
	 * @see li3_mailer\net\mail\Message::attachments()
	 * @var array
	 */
	protected $_attachments = array();

	/**
	 * Mime type auto-detection for attachments.
	 * Content types indexed by extension.
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @var array
	 */
	protected $_mime_types = array(
		'aif'  => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'avi'  => 'video/avi',
		'bmp'  => 'image/bmp',
		'bz2'  => 'application/x-bz2',
		'csv'  => 'text/csv',
		'dmg'  => 'application/x-apple-diskimage',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'eml'  => 'message/rfc822',
		'aps'  => 'application/postscript',
		'exe'  => 'application/x-ms-dos-executable',
		'flv'  => 'video/x-flv',
		'gif'  => 'image/gif',
		'gz'   => 'application/x-gzip',
		'hqx'  => 'application/stuffit',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'jar'  => 'application/x-java-archive',
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'm3u'  => 'audio/x-mpegurl',
		'm4a'  => 'audio/mp4',
		'mdb'  => 'application/x-msaccess',
		'mid'  => 'audio/midi',
		'midi' => 'audio/midi',
		'mov'  => 'video/quicktime',
		'mp3'  => 'audio/mpeg',
		'mp4'  => 'video/mp4',
		'mpeg' => 'video/mpeg',
		'mpg'  => 'video/mpeg',
		'odg'  => 'vnd.oasis.opendocument.graphics',
		'odp'  => 'vnd.oasis.opendocument.presentation',
		'odt'  => 'vnd.oasis.opendocument.text',
		'ods'  => 'vnd.oasis.opendocument.spreadsheet',
		'ogg'  => 'audio/ogg',
		'pdf'  => 'application/pdf',
		'png'  => 'image/png',
		'ppt'  => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'ps'   => 'application/postscript',
		'rar'  => 'application/x-rar-compressed',
		'rtf'  => 'application/rtf',
		'tar'  => 'application/x-tar',
		'sit'  => 'application/x-stuffit',
		'svg'  => 'image/svg+xml',
		'tif'  => 'image/tiff',
		'tiff' => 'image/tiff',
		'ttf'  => 'application/x-font-truetype',
		'txt'  => 'text/plain',
		'vcf'  => 'text/x-vcard',
		'wav'  => 'audio/wav',
		'wma'  => 'audio/x-ms-wma',
		'wmv'  => 'audio/x-ms-wmv',
		'xls'  => 'application/excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xml'  => 'application/xml',
		'zip'  => 'application/zip'
	);

	/**
	 * Message grammar, used for checking generated Cotent-IDs.
	 *
	 * @see li3_mailer\net\mail\Message::generateId()
	 * @see li3_mailer\net\mail\Grammar
	 * @var object
	 */
	protected $_grammar;

	/**
	 * Classes used by `Message`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media' => 'li3_mailer\net\mail\Media', 'grammar' => 'li3_mailer\net\mail\Grammar'
	);

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge', 'mime_types' => 'merge');

	/**
	 * Adds config values to the public properties when a new object is created.
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @see li3_mailer\net\mail\Message::_init()
	 * @see li3_mailer\net\mail\Message::$_mime_types
	 * @see li3_mailer\net\mail\Message::$_classes
	 * @param array $config Supported options:
	 *        - the public properties, such as `'date'`, `'to'`, etc.,
	 *        - `'attach'` _array_: list of attachment configurations,
	 *          the values may be string paths or configurations indexed
	 *          by path (or integer index if path is not appropiate), see
	 *          `attach()` and `_init()`,
	 *        - `'mime_types'` _array_: list of (additional) mime types for
	 *          autodetecting attachment types, see `$_mime_types` and `attach()`,
	 *        - `'classes'` _array_: class dependencies, see `$_classes`.
	 */
	public function __construct(array $config = array()) {
		foreach (array_filter($config) as $key => $value) {
			$this->{$key} = $value;
		}
		$defaults = array('mime_types' => array());
		parent::__construct($config +  $defaults);
	}

	/**
	 * Initialize the message.
	 *
	 * Creates the attachments set in configuration (see constructor).
	 * The `'attach'` array can contain any of:
	 *
	 * - integer key with string path value (meaning empty configuration)
	 * - integer key with configuration array (meanining `null` path)
	 * - path as key with null (meaning empty configuration) or configuration array as value
	 *
	 * For available configuration options see `attach()`.
	 *
	 * Furthermore it initializes the grammar (used to validate generated Content-IDs,
	 * see `randomId()`) and the `$base_url` (see `discoverURL()`).
	 *
	 * @see li3_mailer\net\mail\Message::__construct()
	 * @see li3_mailer\net\mail\Message::attach()
	 * @see li3_mailer\net\mail\Message::randomId()
	 * @see li3_mailer\net\mail\Message::discoverURL()
	 * @return void
	 */
	protected function _init() {
		if (isset($this->_config['attach'])) {
			foreach ((array) $this->_config['attach'] as $path => $cfg) {
				if (is_int($path)) {
					$path = null;
				}
				if (is_string($cfg)) {
					$path = $cfg;
					$cfg = null;
				}
				$this->attach($path, (array) $cfg);
			}
		}
		$grammar = (array) (isset($this->_config['grammar']) ? $this->_config['grammar'] : null);
		$this->_grammar = $this->_instance('grammar', compact('grammar'));
		$this->base_url = $this->base_url ?: $this->discoverURL();
		if ($this->base_url && strpos($this->base_url, '://') === false) {
			$this->base_url = 'http://' . $this->base_url;
		}
		if ($this->base_url) {
			$this->base_url = rtrim($this->base_url, '/');
		}
	}

	/**
	 * Add a header to message.
	 *
	 * @param string $key Header name.
	 * @param string $value Header value (deletes the header if `false`).
	 * @return void
	 */
	public function header($key, $value) {
		if ($value === false) {
			unset($this->headers[$key]);
		} else {
			$this->headers[$key] = $value;
		}
	}

	/**
	 * Add body parts or get body for a given type.
	 *
	 * @param mixed $type Content-type.
	 * @param mixed $data Body parts to add if any.
	 * @param array $options Options:
	 *        - `'buffer'`: split the body string.
	 * @return mixed String or array body.
	 */
	public function body($type, $data = null, $options = array()) {
		$default = array('buffer' => null);
		$options += $default;
		if (!isset($this->body[$type])) {
			$this->body[$type] = array();
		}
		$this->body[$type] = array_merge((array) $this->body[$type], (array) $data);
		$body = join("\n", $this->body[$type]);
		return ($options['buffer']) ? str_split($body, $options['buffer']) : $body;
	}

	/**
	 * Get the list of types this as short-name => content-type pairs
	 * this message should be rendered in.
	 *
	 * @return array List of types.
	 */
	public function types() {
		$types = (array) $this->types;
		$media = $this->_classes['media'];
		return array_combine($types, array_map(function($type) use ($media) {
			return $media::type($type);
		}, $types));
	}

	/**
	 * Ensures the message's fields are valid according to the RFC 2822.
	 *
	 * @see http://tools.ietf.org/html/rfc2822
	 * @see li3_mailer\net\mail\Message::ensureValidDate()
	 * @see li3_mailer\net\mail\Message::ensureValidFrom()
	 * @see li3_mailer\net\mail\Message::ensureValidSender()
	 */
	public function ensureStandardCompliance() {
	    $this->ensureValidDate();
	    $this->ensureValidFrom();
	    $this->ensureValidSender();
	}

	/**
	 * Ensures that the message has a valid `$date` set. Sets the
	 * current time if not set.
	 *
	 * @see http://tools.ietf.org/html/rfc2822#section-3.6
	 * @see li3_mailer\net\mail\Message::$date
	 * @throws RuntimeException Throws an exception if the value
	 *         is not a valid timestamp.
	 * @return void
	 */
	 public function ensureValidDate() {
		if (!$this->date) {
			$this->date = time();
		}
		if (!is_int($this->date) || $this->date < 0 || $this->date > 2147483647) {
			throw new RuntimeException("Invalid date timestamp `{$this->date}` set for `Message`.");
		}
	 }

	/**
	 * Ensures that the message has a valid `$from` set.
	 *
	 * @see http://tools.ietf.org/html/rfc2822#section-3.6
	 * @see li3_mailer\net\mail\Message::$from
	 * @throws RuntimeException Throws an exception if `$from`
	 *         is empty or not an array or string.
	 * @return void
	 */
	 public function ensureValidFrom() {
		if (!$this->from) {
			throw new RuntimeException('`Message` should have at least one `$from` address.');
		} else if (!is_string($this->from) && !is_array($this->from)) {
			$type = gettype($this->from);
			throw new RuntimeException(
				"`Message`'s `\$from` field should be a string or an array, `{$type}` given."
			);
		}
	 }

	/**
	 * Sets `$sender'` if empty and there are multiple `$from` addresses
	 * (`$sender` will be set to the first) or removes `$sender` if set and
	 * is identical to the single `$from` address; and ensures `$sender` is a
	 * single address. According to the RFC 2822 (section 3.6.2.): 'If the
	 * originator of the message can be indicated by a single mailbox and the
	 * author and transmitter are identical, the "Sender:" field SHOULD NOT
	 * be used.  Otherwise, both fields SHOULD appear.'
	 *
	 * @see http://tools.ietf.org/html/rfc2822#section-3.6.2
	 * @see li3_mailer\net\mail\Message::$sender
	 * @throws RuntimeException Throws an exception if `$sender`
	 *         is set and is not a single address.
	 * @return void
	 */
	public function ensureValidSender() {
		$from = (array) $this->from;
		$sender = (array) $this->sender;
		if (!$sender && count($from) > 1) {
			$this->sender = array(key($from) => current($from));
		} else if ($sender && count($from) == 1 && $sender == $from) {
			$this->sender = null;
		}
		if ($this->sender && count((array) $this->sender) > 1) {
			throw new RuntimeException('`Message` should only have a single `$sender` address.');
		}
	}

	/**
	 * Attach content to the message.
	 *
	 * If `$path` is a string it is resolved with `Media::asset()` and its content will be
	 * attached (with setting the defaults for `'filename'` to file's basename and `'content-type'`
	 * from `$_mime_types` if file's extension is registered).
	 *
	 * If `$path` is `null` (or not a string) then `$options['data']` is used. It must be a string
	 * and will be used as the content body. If `'filename'` is given and its extension is
	 * registered in `$_mime_types` then it is used as the default value for `'content-type'`.
	 *
	 * Examples:
	 * {{{
	 *    // attach a simple file in asset directory
	 *    $message->attach('file.pdf');
	 *
	 *    // attach a simple file with absolute path
	 *    $message->attach('/path/to/file.pdf');
	 *
	 *    // attach a remote file
	 *    $message->attach('http://example.host/file.pdf');
	 *
	 *    // attach a simple file with different filename
	 *    $message->attach('/path/to/file.pdf', array(
	 *        'filename' => 'cool_file.pdf'
	 *    ));
	 *
	 *    // attach simple content with filename
	 *    $message->attach(null, array(
	 *        'data' => 'this is my content',
	 *        'filename' => 'cool.txt'
	 *    ));
	 *
	 *    // attach data with content type
	 *    $img_data = create_custom_image(...);
	 *    $message->attach(null, array(
	 *        'data' => $img_data,
	 *        'filename' => 'cool.png',
	 *        'content-type' => 'image/png'
	 *    ));
	 * }}}
	 *
	 * @see li3_mailer\net\mail\Media::asset()
	 * @see li3_mailer\net\mail\Message::$_mime_types
	 * @see li3_mailer\net\mail\Message::embed()
	 * @param string $path Path to file, may be null.
	 * @param array $options Available options are:
	 *        - `'data'` _string_: content body (if `$path` is a string this is ignored),
	 *        - `'disposition'` _string_: disposition, usually `'attachment'` or `'inline'`,
	 *          defaults to `'attachment'`,
	 *        - `'content-type'` _string_: content-type, defaults to `'application/octet-stream'`,
	 *        - `'filename'` _string_: filename,
	 *        - `'id'` _string_: content-id, useful for embedding, see `embed()`,
	 *        - `'library'` _boolean_ or _string_: name of the library to resolve path with (when
	 *          `$path` is string and is relative), defaults to `true` (meaning the default
	 *          library), see `Media::asset()`,
	 *        - `'check'` _boolean_: check if file exists (if `$path` is string), defaults to
	 *          `true`, see `Media::asset()`.
	 * @throws RuntimeException Throws an exception if neither `$path` nor `$options['data']` is
	 *         valid,  when `$path` is set but does not exists or when `$options['data']` is
	 *         not string (and `$path` is not string).
	 * @return object Message object this method was called on.
	 */
	public function attach($path, array $options = array()) {
		if (!is_string($path) && !isset($options['data'])) {
			throw new RuntimeException('Neither path nor data provided, cannot attach.');
		}
		$defaults = array(
			'disposition' => 'attachment', 'content-type' => 'application/octet-stream'
		);
		if (is_string($path)) {
			$media = $this->_classes['media'];
			$asset_defaults = array('check' => true, 'library' => true);
			$asset_path = $media::asset($path, $options + $asset_defaults);
			if ($asset_path === false) {
				throw new RuntimeException(
					"File at `{$path}` is not a valid asset, cannot attach."
				);
			}
			$attach_path = $path;
			$path = $asset_path;
			unset($options['data']);
			$defaults += array('filename' => basename($path));
			$extension = pathinfo($path, PATHINFO_EXTENSION);
			if (isset($this->_mime_types[$extension])) {
				$defaults['content-type'] = $this->_mime_types[$extension];
			}
			$options = compact('path', 'attach_path') + $options + $defaults;
		} else {
			if (!is_string($options['data'])) {
				$type = gettype($options['data']);
				throw new RuntimeException(
					"Data should be a string, `{$type}` given, cannot attach."
				);
			}
			if (isset($options['filename'])) {
				$extension = pathinfo($options['filename'], PATHINFO_EXTENSION);
				if (isset($this->_mime_types[$extension])) {
					$defaults['content-type'] = $this->_mime_types[$extension];
				}
			}
			$options += $defaults;
		}
		$this->_attachments[] = $options;
		return $this;
	}

	/**
	 * Detach content. `$path` should be the same identifier used to `attach()` content,
	 * e.g. `$path` or `$options['data']`.
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @param string $path Path (or data) used to attach content.
	 * @return object Message object this method was called on.
	 */
	public function detach($path) {
		$filter = function($cfg) use ($path) {
			switch (true) {
				case isset($cfg['attach_path']) && $cfg['attach_path'] === $path:
				case isset($cfg['data']) && $cfg['data'] === $path:
					return false;
			}
			return true;
		};
		$this->_attachments = array_filter($this->_attachments, $filter);
		return $this;
	}

	/**
	 * Retrieve all attachments.
	 *
	 * @see li3_mailer\net\mail\Message::attach()
	 * @return array Attachments.
	 */
	public function attachments() {
		return $this->_attachments;
	}


	/**
	 * Embed content. Sets default options, calls `attach()` with it and returns the Content-ID
	 * suitable for embedding.
	 *
	 * Example:
	 * {{{
	 *    //embed a picture
	 *    $cid = $message->embed('picture.png');
	 *    //use the Content-ID as the src in the body
	 *    $message->body('html', 'my image: <img src="cid:' . $cid . '" alt="my image"/>');
	 * }}}
	 *
	 * The default options set by this method are:
	 *
	 * - `'id'`: generates a random id with `randomId()`,
	 * - `'disposition'`: defaults to `'inline'`.
	 *
	 * @see li3_mailer\net\mail\Message::randomId()
	 * @see li3_mailer\net\mail\Message::attach()
	 * @param string $path See `attach()`.
	 * @param array $options See `attach()`.
	 * @return string Content-ID.
	 */
	public function embed($path, array $options = array()) {
		$options += array('id' => $this->randomId(), 'disposition' => 'inline');
		$this->attach($path, $options);
		return $options['id'];
	}

	/**
	 * Generate a random Content-ID for embedded attachments. Checks its
	 * validity with `$_grammar`.
	 *
	 * @see li3_mailer\net\mail\Message::embed()
	 * @see li3_mailer\net\mail\Message::$_grammar
	 * @see li3_mailer\net\mail\Grammar
	 * @see li3_mailer\net\mail\Grammar::isValidId()
	 * @return string Content-ID.
	 */
	protected function randomId() {
		$left = time() . '.' . uniqid();
		if (!empty($this->base_url)) {
			list($scheme, $url) = explode('://', $this->base_url);
			$parts = explode('/', $url, 2);
			$right = array_shift($parts);
		} else {
			$right = 'li3_mailer.generated';
		}
		$id = "{$left}@{$right}";
		if (!$this->_grammar->isValidId($id)) {
			$id = "{$left}@li3_mailer.generated";
		}
		return $id;
	}

	/**
	 * Try to discover base url from `$_SERVER` (with `Request`).
	 *
	 * @see li3_mailer\net\mail\Message::_init()
	 * @see lithium\action\Request
	 * @return string Base url if found, `null` otherwise.
	 */
	protected static function discoverURL() {
		$request = new Request();
		if ($host = $request->env('HTTP_HOST')) {
			$scheme = $request->env('HTTPS') ? 'https://' : 'http://';
			$base = $request->env('base');
			return $scheme . $host . $base;
		}
		return null;
	}
}

?>