<?php

namespace Technoized\TestUtil;

abstract class MockAPC {
	public static function clearAPC(): void {
		$iterator = new \APCUIterator(null, \APC_ITER_KEY);
		foreach ($iterator as $key => $_) {
			apcu_delete($key);
		}
	}
}
