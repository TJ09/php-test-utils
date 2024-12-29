<?php

namespace Technoized\TestUtil;

/**
 * Custom PDO implementation that takes an existing database and copies the
 * schema into temporary tables in a second database, creating a fresh
 * environment to play with in tests.
 *
 * Sample usage:
 *
 * protected function setUp(): void {
 *   $real_pdo = new PDO(...);
 *   $this->db = new TestPDO(...);
 *   $this->db->mock($real_pdo);
 * }
 * protected function tearDown(): void {
 *   // Empty out all tables in between tests.
 *   $this->db->clear();
 * }
 */
class TestPDO extends \PDO {
	/** @var array<string> */
	private array $mockedTables = [];
	/** @var array<string, 1> */
	private array $modifiedTables = [];
	/** @var array<string> */
	private array $ignoredTables = [];

	/**
	 * @param string[] $ignored_tables
	 */
	public function mock(\PDO $pdo, array $ignored_tables = []): void {
		$db_query = $pdo->query('SELECT DATABASE()');
		$database = (string)$db_query->fetchColumn(0);
		$db_query->closeCursor();

		$self_table_query = parent::query('SHOW TABLES');
		while ($table = (string)$self_table_query->fetchColumn(0)) {
			parent::exec("DROP TABLE IF EXISTS `{$table}`");
			parent::exec("DROP VIEW IF EXISTS `{$table}`");
		}

		$self_table_query->closeCursor();

		$table_query = $pdo->query('SHOW TABLES');
		while ($table = (string)$table_query->fetchColumn(0)) {
			$create_query = $pdo->query('SHOW CREATE TABLE `'.$table.'`');

			$create_stmt = (string)$create_query->fetchColumn(1);

			// Create ephemeral tables that are scoped to this test.
			$create_stmt = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $create_stmt);
			// Temp tables can't have constraints
			$create_stmt = preg_replace("/,\n\s*CONSTRAINT (.+)[A-Z]/", '', $create_stmt);
			// or partitions
			$create_stmt = preg_replace('/PARTITION BY (.+)PARTITIONS [0-9]+/ms', '', $create_stmt);
			$create_stmt = preg_replace("/PARTITION BY RANGE \(.+\)\s*\((\s*PARTITION `.+` VALUES LESS THAN (\([0-9]+\)|MAXVALUE).*,?\s*)+\)/ms", '', $create_stmt);
			// or be compressed
			$create_stmt = preg_replace("/\bROW_FORMAT=COMPRESSED/", '', $create_stmt);
			$create_stmt = preg_replace("/\bAUTO_INCREMENT=([0-9]+)/", '', $create_stmt);

			if (array_search($table, $ignored_tables, true) !== false) {
				$create_stmt = "CREATE VIEW `{$table}` AS SELECT * FROM `{$database}`.`{$table}`";
				$this->ignoredTables[] = $table;
			}

			try {
				parent::exec($create_stmt);
				$this->mockedTables[] = $table;
			} catch(\PDOException $e) {
				trigger_error("Failed to import {$table} into test DB: {$e->getMessage()}\n".$create_stmt, E_USER_NOTICE);
			}
		}
		$table_query->closeCursor();

		register_shutdown_function(function() {
			foreach ($this->ignoredTables as $table) {
				parent::exec("DROP VIEW IF EXISTS `{$table}`");
			}
		});
	}

	/**
	 * As an optimization, we only clear tables that appear to have been touched
	 * rather than running truncate on every single table.
	 */
	private function markTablesAsModified(string $query): void {
		foreach ($this->mockedTables as $table) {
			if (stripos($query, $table) !== false) {
				$this->modifiedTables[$table] = 1;
			}
		}
	}

	/**
	 * @psalm-suppress MissingParamType - Overriding PDO is untypeable
	 * @psalm-suppress MixedInferredReturnType
	 * @psalm-suppress MixedReturnStatement
	 * @param string $query
	 */
	public function prepare($query, $options = null): \PDOStatement {
		$this->markTablesAsModified($query);
		return call_user_func_array(array(parent::class, 'prepare'), func_get_args());
	}

	/**
	 * @psalm-suppress MissingParamType - Overriding PDO is untypeable
	 * @psalm-suppress MixedInferredReturnType
	 * @psalm-suppress MixedReturnStatement
	 */
	public function query(string $query, ...$args): \PDOStatement {
		$this->markTablesAsModified($query);
		return call_user_func_array(array(parent::class, 'query'), func_get_args());
	}

	/**
     * @psalm-suppress MixedInferredReturnType - Overriding PDO is untypeable
	 * @psalm-suppress MixedReturnStatement
	 */
	public function exec(string $statement): int|false {
		$this->markTablesAsModified($statement);
		return call_user_func_array(array(parent::class, 'exec'), func_get_args());
	}

	public function clear(): void {
		foreach ($this->modifiedTables as $table => $_) {
			if (array_search($table, $this->ignoredTables, true) !== false) {
				continue;
			}
			parent::exec('TRUNCATE TABLE `'.$table.'`');
		}

		$this->modifiedTables = [];
	}
}
