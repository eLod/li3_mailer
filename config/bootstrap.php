<?php

use lithium\core\Libraries;

/**
 * Register paths for mail template helpers.
 */
$existing = Libraries::paths('helper');
Libraries::paths(array('helper' => array_merge(array(
	'{:library}\extensions\helper\{:class}\{:name}',
	'{:library}\template\helper\{:class}\{:name}' => array('libraries' => 'li3_mailer')
), (array) $existing)));

/**
 * Add paths for delivery transport adapters from this library (plugin).
 */
$existing = Libraries::paths('adapter');
$key = '{:library}\{:namespace}\{:class}\adapter\{:name}';
$existing[$key]['libraries'] = array_merge(
    (array) $existing[$key]['libraries'],
    (array) 'li3_mailer'
);
Libraries::paths(array('adapter' => $existing));

/*
 * Ensure the mail template resources path exists.
 */
$path = Libraries::get(true, 'resources') . '/tmp/cache/mails';
if (!is_dir($path)) {
	mkdir($path);
}

/**
 * Load the file that configures the delivery system.
 */
require __DIR__ . '/bootstrap/delivery.php';

?>