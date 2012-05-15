<?php

namespace li3_mailer\tests\mocks\template;

class MailWithoutRender extends \li3_mailer\template\Mail {

	public function render($process, array $data = array(), array $options = array()) {
		return compact('process', 'data', 'options');
	}
}

?>