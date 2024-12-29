<?php

namespace Technoized\TestUtil;

abstract class MockFunctions {
	/** @var array<string, string> */
	private static array $mockedMethods = [];

	/**
	 * @param mixed $value
	 */
	public static function mockReturn(string $method, $value): void {
		self::$mockedMethods[$method] = $method;
		if (extension_loaded('uopz')) {
			if (ini_get('uopz.disable')) {
				/**
                 * @psalm-suppress InternalClass
                 * @psalm-suppress InternalMethod
                 */
				throw new \PHPUnit\Framework\SkippedWithMessageException('uopz is disabled by configuration (uopz.disable)');
			}
			if (strpos($method, '::') !== false) {
				list($class, $method) = explode('::', $method);

				uopz_set_return($class, $method, $value, false);
			} else {
				/** @psalm-suppress MixedArgument Psalm can't tell the difference between the different overloads*/
				uopz_set_return($method, $value, false);
			}
		} else {
			throw new \LogicException('Mocking is not supported');
		}
	}

	public static function mockImplementation(string $method, callable $replacement): void {
		self::$mockedMethods[$method] = $method;
		if (extension_loaded('uopz')) {
			if (ini_get('uopz.disable')) {
				/**
                 * @psalm-suppress InternalClass
                 * @psalm-suppress InternalMethod
                 */
				throw new \PHPUnit\Framework\SkippedWithMessageException('uopz is disabled by configuration (uopz.disable)');
			}
			if (strpos($method, '::') !== false) {
				list($class, $method) = explode('::', $method);

				uopz_set_return($class, $method, $replacement, true);
			} else {
				uopz_set_return($method, $replacement, true);
			}
		} else {
			throw new \LogicException('Mocking is not supported');
		}
	}

	public static function isMocked(string $method): bool {
		return isset(self::$mockedMethods[$method]);
	}

	public static function unmock(string $method): void {
		unset(self::$mockedMethods[$method]);
		if (extension_loaded('uopz')) {
			if (ini_get('uopz.disable')) {
				/**
                 * @psalm-suppress InternalClass
                 * @psalm-suppress InternalMethod
                 */
				throw new \PHPUnit\Framework\SkippedWithMessageException('uopz is disabled by configuration (uopz.disable)');
			}
			if (strpos($method, '::') !== false) {
                /** @var class-string $class */
				list($class, $method) = explode('::', $method);

				uopz_unset_return($class, $method);
			} else {
				/** @psalm-suppress TooFewArguments psalm is wrong. */
				uopz_unset_return($method);
			}
		} else {
			throw new \LogicException('Mocking is not supported');
		}
	}

	public static function resetAllMocks(): void {
		foreach (self::$mockedMethods as $method) {
			self::unmock($method);
		}
	}
}
