<?php

namespace li3_mailer\net\mail\transport\adapter;

function mail($to, $subject, $body, $headers) {
	return compact('to', 'subject', 'body', 'headers');
}

namespace li3_mailer\tests\mocks\net\mail\transport\adapter;

class Simple extends \li3_mailer\net\mail\transport\adapter\Simple {
}

?>