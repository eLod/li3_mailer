<?php

namespace li3_mailer\template\helper\mail;

/**
 * A template helper that assists in generating HTML content.
 * See the view helper that this class extends.
 *
 * @see lithium\template\helper\Html
 */
class Html extends \lithium\template\helper\Html {
	/**
	 * Returns a charset meta-tag for declaring the encoding of the document.
	 *
	 * @see lithium\template\helper\Html::charset()
	 * @param string $encoding Character encoding to be used in the meta tag.
	 *        Defaults to the encoding of the `Messagee` object attached to the
	 *        current context. The default encoding of that object is `UTF-8`.
	 *        The string given here is not manipulated in any way, so that
	 *        values are rendered literally.
	 * @return string A meta tag containing the specified encoding (literally).
	 */
	public function charset($encoding = null) {
		$encoding = $encoding ?: $this->_context->message()->charset;
		return parent::charset($encoding);
	}

	/**
	 * Creates a formatted <img /> element. Unless `$path` is an URL
	 * it embeds the image into the message and the `src` attribute of the
	 * <img /> element will get set to the Content-ID of the attachment.
	 * This behaviour can be overriden with setting the special option
	 * `'embed'` to `false` (which defaults to `true`). If `'embed'`
	 * is `false` the URL is calculated with `http\Media`, so it will
	 * return the web accessible path (see the context's `'path'` handler).
	 * Optionally `$path` may be an array and then it is resolved to an
	 * URL with context's `'url'` handler.
	 *
	 * Examples:
	 * {{{
	 *    // will embed an image
	 *    $this->image('/path/to/my/image.png');
	 *
	 *    // will embed an image relative to mail asset path
	 *    $this->image('my/image.png');
	 *
	 *    // will use an URL
	 *    $this->image('http://my.url/image.png');
	 *
	 *    // will use an URL computed with http\Media
	 *    // (path is relative to `app/webroot/img`)
	 *    $this->image('my/image.png', array('embed' => false));
	 *
	 *    // will use an URL computed with http\Media
	 *    $this->image('/path/to/my/image.png', array('embed' => false));
	 * }}}
	 *
	 * It is possible to pass extra options for path resolving and attaching,
	 * like:
	 * {{{
	 *    // embed the image from a particular library's mail asset path
	 *    $this->image('my/image.png', array('library' => 'foo'));
	 *
	 *    // embed the image with a different filename
	 *    $this->image('my/image.png', array('filename' => 'cool.png'));
	 *
	 *    // embed data
	 *    $this->image(null, array(
	 *        'data' => $img_data',
	 *        'content-type' => 'image/png',
	 *        'filename' => 'cool.png'
	 *    ));
	 * }}}
	 *
	 * @see li3_mailer\template\mail\adapter\File::_init()
	 * @param string $path Path to the image file.
	 * @param array $options Array of HTML attributes and other options.
	 * @return string Formatted <img /> element.
	 * @filter This method can be filtered.
	 */
	public function image($path, array $options = array()) {
		$embedPattern = '/^[a-z0-9-]+:\/\//i';
		$embed = !(is_string($path) && preg_match($embedPattern, $path));
		$defaults = compact('embed');
		$options += array('alt' => '');
		list($scope, $options) = $this->_options($defaults, $options);
		$clear = array('library', 'check', 'data', 'content-type', 'filename');
		$options = array_diff_key($options, array_fill_keys($clear, null));
		$path = is_array($path) ? $this->_context->url($path) : $path;
		$params = compact('path', 'options', 'scope');
		$method = __METHOD__;

		$filter = function($self, $params, $chain) use ($method) {
			extract($params);
			$args = array($method, 'image', compact('path', 'options'), $scope);
			return $self->invokeMethod('_render', $args);
		};
		return $this->_filter($method, $params, $filter);
	}
}

?>