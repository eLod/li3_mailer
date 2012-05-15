<?php

namespace li3_mailer\tests\cases\net\mail;

use li3_mailer\net\mail\Grammar;

class GrammarTest extends \lithium\test\Unit {

	public function testToken() {
		$grammar = array('foo' => 'bar', 'baz' => 'qux');
		$g = new Grammar(compact('grammar'));
		$this->assertFalse(is_null($g->token('NO-WS-CTL')));
		$this->assertNull($g->token('nonexistent'));
		foreach ($grammar as $key => $expected) {
			$this->assertEqual($expected, $g->token($key));
		}
		$tokens = $g->token();
		foreach ($grammar as $key => $expected) {
			$this->assertTrue(array_key_exists($key, $tokens));
			$this->assertEqual($expected, $tokens[$key]);
		}
		$g->token('extra', 'value');
		$this->assertEqual('value', $g->token('extra'));
		$tokens = $g->token();
		$this->assertTrue(array_key_exists('extra', $tokens));
		$this->assertEqual('value', $tokens['extra']);
	}

	public function testIsValidId() {
		$valid = array(
			'a@b', 'example@host', '1234567890@li3_mailer.generated',
			'123{}$#!+|^%@li3_mailer.generated'
		);
		$invalid = array('a@b@c', 'a@@b', 'a<@b', 'a>@b', 'a"@b');
		$grammar = new Grammar();
		foreach ($valid as $id) {
			$this->assertTrue($grammar->isValidId($id));
		}
		foreach ($invalid as $id) {
			$this->assertFalse($grammar->isValidId($id));
		}
	}
}

?>