<?php

use Technoized\TestUtil\MockAPC;

class MockAPCTest extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		if (!extension_loaded('apcu')) {
			$this->markTestSkipped(
				'The APCu extension is not available.',
			);
		}
	}

	public function testRemoveAll(): void {
		apcu_store('test_key_1', 1);
		apcu_store('test_key_2', 2);
		apcu_store('test_key_3', 3);

		self::assertSame(1, apcu_fetch('test_key_1'));
		self::assertSame(2, apcu_fetch('test_key_2'));
		self::assertSame(3, apcu_fetch('test_key_3'));

		MockAPC::clearAPC();

		self::assertFalse(apcu_fetch('test_key_1'));
		self::assertFalse(apcu_fetch('test_key_2'));
		self::assertFalse(apcu_fetch('test_key_3'));
	}
}
