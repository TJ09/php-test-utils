<?php

use Technoized\TestUtil\MockFunctions;

function __test_mock_function() {
    return MockFunctionsTest::ORIG_VALUE;
}

class MockFunctionsTest extends \PHPUnit\Framework\TestCase {
    const ORIG_VALUE = 4;
    const MOCKED_VALUE = 6;

    public static function foo() {
        return self::ORIG_VALUE;
    }

    public function testMockReturnFunction(): void {
        self::assertSame(__test_mock_function(), self::ORIG_VALUE);
        MockFunctions::mockReturn('__test_mock_function', self::MOCKED_VALUE);

        self::assertTrue(MockFunctions::isMocked('__test_mock_function'));
        self::assertSame(__test_mock_function(), self::MOCKED_VALUE);

        MockFunctions::unmock('__test_mock_function');
        self::assertSame(__test_mock_function(), self::ORIG_VALUE);
    }

    public function testMockReturnMethod(): void {
        self::assertSame(self::foo(), self::ORIG_VALUE);
        MockFunctions::mockReturn(static::class.'::foo', self::MOCKED_VALUE);

        self::assertTrue(MockFunctions::isMocked(static::class.'::foo'));
        self::assertSame(self::foo(), self::MOCKED_VALUE);

        MockFunctions::unmock(static::class.'::foo');
        self::assertSame(self::foo(), self::ORIG_VALUE);
    }

    public function testMockImplementationFunction(): void {
        self::assertSame(__test_mock_function(), self::ORIG_VALUE);
        MockFunctions::mockImplementation(
            '__test_mock_function',
            function() {
                return MockFunctionsTest::MOCKED_VALUE;
            },
        );

        self::assertTrue(MockFunctions::isMocked('__test_mock_function'));
        self::assertSame(__test_mock_function(), self::MOCKED_VALUE);

        MockFunctions::unmock('__test_mock_function');
        self::assertSame(__test_mock_function(), self::ORIG_VALUE);
    }

    public function testMockImplementationMethod(): void {
        self::assertSame(self::foo(), self::ORIG_VALUE);
        MockFunctions::mockImplementation(
            static::class.'::foo',
            function() {
                return MockFunctionsTest::MOCKED_VALUE;
            },
        );

        self::assertTrue(MockFunctions::isMocked(static::class.'::foo'));
        self::assertSame(self::foo(), self::MOCKED_VALUE);

        MockFunctions::unmock(static::class.'::foo');
        self::assertSame(self::foo(), self::ORIG_VALUE);
    }

    public function testResetAllMocks(): void {
        MockFunctions::mockReturn('__test_mock_function', self::MOCKED_VALUE);
        MockFunctions::mockReturn(static::class.'::foo', self::MOCKED_VALUE);
        self::assertSame(__test_mock_function(), self::MOCKED_VALUE);
        self::assertSame(self::foo(), self::MOCKED_VALUE);

        MockFunctions::resetAllMocks();
        self::assertSame(__test_mock_function(), self::ORIG_VALUE);
        self::assertSame(self::foo(), self::ORIG_VALUE);
    }
}
