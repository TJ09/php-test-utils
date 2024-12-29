<?php

namespace Technoized\TestUtil;

trait TestWithSnapshots {
	private static array $seenSnapshots = [];

    #[\PHPUnit\Framework\Attributes\BeforeClass]
	public static function clearKnownSnapshots(): void {
		static::$seenSnapshots = [];
	}

    #[\PHPUnit\Framework\Attributes\AfterClass]
	public static function checkForMissingSnapshots(): void {
		$class_file = (new \ReflectionClass(get_called_class()))->getFilename();
		$all_snapshots = array_keys(iterator_to_array(new \RegexIterator(new \FilesystemIterator(self::getSnapshotDir()), '#/'.preg_quote(get_called_class()).'-.*.ser$#')));

		$orphaned_snapshots = array_diff($all_snapshots, static::$seenSnapshots);

		if (self::shouldRecord()) {
			foreach ($orphaned_snapshots as $file) {
				unlink($file);
			}
		} else {
			self::assertEmpty(
				$orphaned_snapshots,
				'Not all known snapshots were checked during the test. Remove orphaned snapshot file(s) '.implode(', ', $orphaned_snapshots).' or run phpunit with `-d --record`.',
			);
		}
	}

	private static function shouldRecord() {
		return in_array('--record', $_SERVER['argv'], true);
	}

	private static function getSnapshotDir(): string {
		$class_file = (new \ReflectionClass(get_called_class()))->getFilename();
		return dirname($class_file).'/__snapshots__';
	}
	private static function getSnapshotFile(string $name): string {
		return self::getSnapshotDir().'/'.get_called_class().'-'.$name.'.ser';
	}

	protected static function assertSnapshot(
		$name,
		$data,
		$comparator = null
	) {
		$snapshot_file = self::getSnapshotFile($name);
		static::$seenSnapshots[] = $snapshot_file;

		if (self::shouldRecord()) {
			self::recordSnapshot($name, $data);
			@mkdir(dirname($snapshot_file));
			file_put_contents($snapshot_file, serialize($data));
		}

		if (!file_exists($snapshot_file)) {
			self::fail('Snapshot "'.$name.'" not found. Record snapshots by running phpunit with `-d --record`.');
		} else {
			$snapshot = unserialize(file_get_contents($snapshot_file, true));
		}

		if (!$comparator) {
			self::assertSame(
				$snapshot,
				$data,
				'Snapshot comparison "'.$name.'" failed. Update snapshots by running phpunit with `-d --record`.',
			);
		} else {
			self::assertTrue(
				$comparator($snapshot, $data),
				'Snapshot comparison "'.$name.'" failed. Update snapshots by running phpunit with `-d --record`.',
			);
		}
	}

	protected static function recordSnapshot($name, $data) {
		$snapshot_file = self::getSnapshotFile($name);
		@mkdir(dirname($snapshot_file));
		file_put_contents($snapshot_file, serialize($data));
	}
}
