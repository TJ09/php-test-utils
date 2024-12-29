<?php

use org\bovigo\vfs\vfsStream;
use Technoized\TestUtil\MockFunctions;

use Technoized\TestUtil\TestWithSnapshots;

class TestWithSnapshotsTest extends \PHPUnit\Framework\TestCase {
	use TestWithSnapshots;

	protected function setUp(): void {
		vfsStream::setup('root');

		$_SERVER['argv'] = [];

		self::$seenSnapshots = [];

		MockFunctions::mockReturn(
			static::class.'::getSnapshotDir',
			vfsStream::url('root'),
		);
	}

	public function testSnapshotRecord(): void {
		$snapshot_file = $this->getSnapshotFile(__METHOD__);
		self::assertFileDoesNotExist($snapshot_file);

		$_SERVER['argv'] = ['--record'];

		self::assertTrue(self::shouldRecord());
		self::assertSnapshot(__METHOD__, 4);

		self::assertFileExists($snapshot_file);
		self::assertSame(unserialize(file_get_contents($snapshot_file)), 4);
	}

	public function testOrphanedSnapshot(): void {
		$snapshot_file = $this->getSnapshotFile(__METHOD__);
		@mkdir(dirname($snapshot_file));
		file_put_contents($snapshot_file, '');

		self::assertFileExists($snapshot_file);
		self::expectException(\PHPUnit\Framework\ExpectationFailedException::class);
		self::expectExceptionMessage('Not all known snapshots were checked');
		self::checkForMissingSnapshots();
	}

	public function testOrphanedSnapshotRecord(): void {
		$_SERVER['argv'] = ['--record'];
		self::assertTrue(self::shouldRecord());

		$snapshot_file = $this->getSnapshotFile(__METHOD__);
		@mkdir(dirname($snapshot_file));
		file_put_contents($snapshot_file, '');

		self::assertFileExists($snapshot_file);
		self::checkForMissingSnapshots();
		self::assertFileDoesNotExist($snapshot_file);
	}

	public function testNonexistentSnapshot(): void {
		$snapshot_file = $this->getSnapshotFile(__METHOD__);
		self::assertFileDoesNotExist($snapshot_file);
		self::assertFalse(self::shouldRecord());

		self::expectException(\PHPUnit\Framework\AssertionFailedError::class);

		self::assertSnapshot(__METHOD__, 5);
	}

	public function testExistingSnapshot(): void {
		self::recordSnapshot(__METHOD__, 6);
		self::assertFalse(self::shouldRecord());
		self::assertSnapshot(__METHOD__, 6);
	}
}
