<?php

namespace Technoized\TestUtil;

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {
	protected function tearDown(): void {
		MockFunctions::resetAllMocks();
		MockAPC::clearAPC();
	}
}
