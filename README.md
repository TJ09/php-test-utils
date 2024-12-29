# Test Utils

Some common test utils used across projects. Likely not useful outside of projects that happen to use them.

Includes:

* **MockFunctions** A wrapper around [`uopz`](https://www.php.net/manual/en/book.uopz.php) to make it easy-ish to stub out functions in tests.
* **BaseTestCase** Test case class that will clear mocks and APC after each test.
* **TestPDO** Allows copying an existing database's schema into temporary tables in another database, presenting a clean slate for tests.
* **TestWithSnapshots** Provides assertions for tests to assert that some data matches a previously-recorded state.
