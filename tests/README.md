# Duon CMS Testing Guide

This guide explains how to set up and run tests for the Duon CMS project.

## Test Architecture

The test suite combines three types of tests:

- **Unit Tests**: Fast tests for isolated components (lexer, parser, utilities, field capabilities)
- **Integration Tests**: Tests that interact with a real database directly
- **End-to-End (E2E) Tests**: Tests that exercise the full HTTP request/response cycle through the application

### Multi-Backend Support

The test suite supports both PostgreSQL and SQLite backends. The same test code runs on both backends, selected via an environment variable:

```bash
# Run tests on PostgreSQL (default)
composer test:pgsql

# Run tests on SQLite
composer test:sqlite

# Run tests on both backends sequentially
composer test:all

# Run default backend (currently PostgreSQL)
composer test
```

### Key Principles

1. **No Mocks in Integration Tests**: Integration tests use real database connections and actual data
2. **Transaction Isolation for Integration Tests**: Each integration test runs in a transaction that's rolled back after completion
3. **No Transactions for E2E Tests**: E2E tests disable transactions because the CMS creates separate database connections that cannot see uncommitted transaction data
4. **Fixture-Based**: Tests use SQL fixtures and helper methods for consistent test data
5. **Hybrid Setup**: Database schema is initialized once per test run, then transactions provide isolation for integration tests
6. **Backend-Neutral Helpers**: Test helper methods use driver-aware table names and SQL syntax

## Prerequisites

### Option A: PostgreSQL Setup

For running tests with PostgreSQL:

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Start if not running
sudo systemctl start postgresql

# Create user with CREATEDB privilege
sudo -u postgres createuser -d -P duoncms

# Create and initialize the database
./run recreate-db
./run migrate --apply
```

### Option B: SQLite Setup

For running tests with SQLite, no external setup is required. The test harness automatically:

1. Creates a temporary SQLite database file
2. Applies all migrations
3. Cleans up after the test run

SQLite tests are faster and require no external database server.

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `CMS_TEST_DRIVER` | Database backend: `sqlite` or `pgsql` | `pgsql` |
| `CMS_TEST_PGSQL_DSN` | Full PostgreSQL DSN | (derived from parts) |
| `CMS_TEST_PGSQL_HOST` | PostgreSQL host | `localhost` |
| `CMS_TEST_PGSQL_PORT` | PostgreSQL port | `5432` |
| `CMS_TEST_PGSQL_DB` | PostgreSQL database name | `duoncms` |
| `CMS_TEST_PGSQL_USER` | PostgreSQL username | `duoncms` |
| `CMS_TEST_PGSQL_PASSWORD` | PostgreSQL password | `duoncms` |

## Running Tests

### Run All Tests (Default Backend)

```bash
composer test
```

### Run Tests on Specific Backend

```bash
# PostgreSQL
composer test:pgsql

# SQLite
composer test:sqlite

# Both backends (for CI/release)
composer test:all
```

## How to Resume

- Run `composer test` to verify the suite.
- For PostgreSQL: if the database is out of date, run `./run recreate-db && ./run migrate --apply`.
- For SQLite: no setup needed; migrations are applied automatically.
- Use `composer coverage` when you need updated coverage numbers.

### Run Specific Test Suite

```bash
# Run a specific test file
vendor/bin/phpunit tests/Integration/NodeTest.php

# Run a specific test method
vendor/bin/phpunit --filter testCreateAndRetrieveNode tests/Integration/NodeTest.php
```

### Run Only Unit Tests

```bash
vendor/bin/phpunit --testsuite unit
```

### Run Only End-to-End Tests

```bash
vendor/bin/phpunit --testsuite end2end
```

### Run Tests by Group (if tagged)

```bash
# Run tests marked with @group integration
vendor/bin/phpunit --group integration
```

*Note: E2E tests are in the `end2end` test suite by configuration in phpunit.dist.xml*

### Generate Coverage Report

```bash
composer coverage
```

Open `coverage/index.html` in your browser to view the report.

## Test Structure

### Directory Layout

```
tests/
├── TestCase.php                  # Base class for all tests
├── IntegrationTestCase.php       # Base class for integration tests
├── End2EndTestCase.php           # Base class for end-to-end tests
├── Fixtures/
│   ├── data/                     # SQL fixture files
│   │   ├── basic-types.sql       # Common content types
│   │   ├── test-users.sql        # Test user accounts
│   │   └── sample-nodes.sql      # Sample content nodes
│   ├── Node/                     # Test node classes
│   │   ├── TestDocument.php
│   │   ├── TestMediaDocument.php
│   │   └── TestPage.php
│   └── templates/                # Template files for E2E tests
├── Integration/                  # Integration tests
│   ├── *Test.php                 # Direct database tests
├── End2End/                      # End-to-end tests
│   ├── NodeCrudTest.php          # Node CRUD API tests
│   └── RoutingTest.php           # Routing and rendering tests
└── *Test.php                     # Unit test files
```

### Base Test Classes

#### `TestCase`

Base class for all tests, provides:
- **Database helpers**: `db()`, `config()`, `registry()`, `factory()`
- **HTTP helpers**: `request()`, `psrRequest()`, `setMethod()`, `setRequestUri()`
- **Utility helpers**: `fullTrim()`

#### `IntegrationTestCase`

Extends `TestCase` for integration tests, provides:
- **Automatic transaction isolation**: Sets `$useTransactions = true` (each test runs in a transaction that rolls back)
- **Database initialization**: Checks schema exists on first test class run
- **Fixture loading**: `loadFixtures(...$fixtures)`
- **Test data creation**: `createTestType()`, `createTestNode()`, `createTestUser()`, `createTestPath()`
- **Context creation**: `createContext()`
- **Finder creation**: `createFinder()`

#### `End2EndTestCase`

Extends `IntegrationTestCase` for end-to-end HTTP tests, provides:
- **Application setup**: `createApp()` initializes the full CMS application
- **Authentication helpers**:
  - `createAuthenticatedUser(role)` - Creates a user with auth token
  - `authenticateAs(role)` - Sets default auth token for subsequent requests
- **HTTP request helpers**: `makeRequest(method, uri, options)` - Simulates HTTP requests through the app
- **Response assertions**:
  - `assertResponseOk(response)` - Assert status code is 2xx
  - `assertResponseStatus(expected, response)` - Assert specific status code
  - `getJsonResponse(response)` - Decode response body as JSON
- **Disabled transactions**: Sets `$useTransactions = false` because the CMS creates separate DB connections
- **Automatic cleanup**: Cleans up created test data including FK-referenced records

## Writing Tests

### Unit Test Example

```php
<?php

namespace Duon\Cms\Tests;

use Duon\Cms\Tests\TestCase;

final class PasswordTest extends TestCase
{
    public function testPasswordHashing(): void
    {
        $password = 'secret123';
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $this->assertTrue(password_verify($password, $hash));
    }
}
```

### Integration Test Example

```php
<?php

namespace Duon\Cms\Tests;

use Duon\Cms\Tests\IntegrationTestCase;

final class MyIntegrationTest extends IntegrationTestCase
{
    public function testNodeCreation(): void
    {
        $typeId = $this->createTestType('my-test-type', 'page');

        $nodeId = $this->createTestNode([
            'type' => $typeId,
            'content' => ['title' => ['type' => 'text', 'value' => ['en' => 'Test']]],
        ]);

        $node = $this->db()->execute(
            'SELECT * FROM cms.nodes WHERE node = :id',
            ['id' => $nodeId]
        )->one();

        $this->assertNotNull($node);
        $this->assertEquals($typeId, $node['type']);
    }
}
```

### End-to-End Test Example

```php
<?php

namespace Duon\Cms\Tests\End2End;

use Duon\Cms\Tests\End2EndTestCase;

final class NodeCrudTest extends End2EndTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Load test data fixtures
        $this->loadFixtures('basic-types', 'sample-nodes');
    }

    public function testCreateNode(): void
    {
        // Authenticate before making API requests
        $this->authenticateAs('editor');

        // Create a node type
        $this->createTestType('create-test-page', 'page');

        // Prepare node data with required fields
        $nodeData = [
            'uid' => 'my-new-node',
            'published' => true,
            'locked' => false,
            'hidden' => false,
            'paths' => [
                'en' => '/my-new-node',  // Required for page nodes
            ],
            'content' => [
                'title' => ['type' => 'text', 'value' => ['en' => 'My Node']],
            ],
        ];

        // Make HTTP request through the app
        $response = $this->makeRequest('POST', '/panel/api/node/create-test-page', [
            'body' => $nodeData,
        ]);

        // Assert response
        $this->assertResponseOk($response);
        $data = $this->getJsonResponse($response);
        $this->assertTrue($data['success']);
    }
}
```

### Using Fixtures

```php
public function testWithFixtures(): void
{
    // Load SQL fixtures
    $this->loadFixtures('basic-types', 'sample-nodes');

    // Use Finder to query fixture data
    $finder = $this->createFinder();
    $nodes = $finder->nodes()->types('test-page')->get();

    $this->assertNotEmpty($nodes);
}
```

## Test Database Workflow

### Integration Tests - Transaction Isolation

1. **First test class runs** → Database schema is checked (one-time)
2. **Test begins** → Transaction starts (`BEGIN`)
3. **Test executes** → All database operations happen in transaction
4. **Test completes** → Transaction rolls back (`ROLLBACK`)
5. **Next test begins** → Clean database state (transaction starts)

This ensures:
- ✅ Each test has a clean database state
- ✅ No test data persists between tests
- ✅ Tests can run in any order
- ✅ Fast execution (no database recreation)

### End-to-End Tests - No Transactions

E2E tests **disable transactions** because the CMS creates separate database connections:

1. **Test begins** → NO transaction (disabled in `End2EndTestCase`)
2. **Application runs** → Creates its own DB connection, inserts data
3. **Test completes** → Explicit cleanup: deletes created test data
4. **Next test begins** → Clean database state

The cleanup process handles foreign key constraints by deleting in proper order:
1. Delete FK-referenced records (audit records, fulltext index, etc.)
2. Delete the main records
3. Delete created types

This prevents FK constraint violations during cleanup.

### When to Recreate the Database

Recreate the test database when:
- Migrations have been added or modified
- Database structure has changed
- Tests are failing due to schema issues
- You want a completely fresh start

```bash
./run recreate-db && ./run migrate --apply
```

## Troubleshooting

### "Test database not initialized"

**Error:**
```
RuntimeException: Test database not initialized. Run: ./run recreate-db && ./run migrate --apply
```

**Solution:**
```bash
./run recreate-db && ./run migrate --apply
```

### "Migrations not applied"

**Error:**
```
RuntimeException: Migrations not applied to test database. Run: ./run migrate --apply
```

**Solution:**
```bash
./run migrate --apply
```

### "Authentication failed"

**Error:**
```
PDOException: SQLSTATE[28000] authentication failed for user "duoncms"
```

**Solution:**
Ensure the database user exists with the correct password:

```bash
sudo -u postgres createuser -d duoncms
sudo -u postgres psql -c "ALTER USER duoncms WITH PASSWORD 'duoncms';"
```

### "Permission denied to create database"

**Error:**
```
PDOException: permission denied to create database
```

**Solution:**
Grant CREATEDB privilege to the user:

```bash
sudo -u postgres psql -c "ALTER USER duoncms CREATEDB;"
```

### Database Connection Configuration

Test database configuration is centralized in `tests/Support/TestDbConfig.php`:

```php
// PostgreSQL (default)
// Database: duoncms
// User: duoncms
// Password: duoncms
// Host: localhost
// Port: 5432

// SQLite
// File: {temp_dir}/cms_test_{pid}.sqlite
// Auto-created and migrated per test run
```

Configure via environment variables (see Prerequisites section above).

## CI/CD Integration

### GitHub Actions Example (Multi-Backend)

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test-sqlite:
    name: SQLite Tests
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: pdo, pdo_sqlite

      - name: Install dependencies
        run: composer install

      - name: Run SQLite tests
        run: composer test:sqlite

  test-pgsql:
    name: PostgreSQL Tests
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: duoncms
          POSTGRES_USER: duoncms
          POSTGRES_PASSWORD: duoncms
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: pdo, pdo_pgsql

      - name: Install dependencies
        run: composer install

      - name: Initialize test database
        run: |
          ./run recreate-db
          ./run migrate --apply

      - name: Run PostgreSQL tests
        run: composer test:pgsql

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

## Best Practices

### DO

- ✅ Extend `IntegrationTestCase` for database tests
- ✅ Extend `End2EndTestCase` for HTTP tests
- ✅ Use `createTestType()`, `createTestNode()` helpers for test data
- ✅ Load fixtures in `setUp()` when needed across all test methods
- ✅ Use descriptive test names (`testFinderReturnsNodesOfSpecificType`, `testCreateNodeReturns201`)
- ✅ Follow Arrange-Act-Assert pattern
- ✅ Clean up is automatic (via transactions for integration, via explicit cleanup for E2E)
- ✅ For E2E tests: Call `$this->authenticateAs('editor')` before making API requests
- ✅ For page nodes: Include `paths` data with URL paths for required locales
- ✅ For page nodes: Include all required schema fields (`uid`, `published`, `locked`, `hidden`)
- ✅ Make test data unique (use `uniqid()` for node UIDs and paths to avoid conflicts between test runs)

### DON'T

- ❌ Use mocks for database interactions in integration tests
- ❌ Rely on test execution order
- ❌ Share state between tests
- ❌ Create permanent test data outside of transactions (integration tests) or without tracking (E2E tests)
- ❌ Use the same type handle for multiple tests in the same class without unique suffixes
- ❌ Skip `paths` for page nodes (they're required for database validation)
- ❌ Forget to authenticate before making API requests to protected endpoints

## Performance

Expected test execution times:
- **Unit tests**: < 1 second
- **Integration tests**: 5-15 seconds (depending on fixture data)
- **Full test suite**: ~10-20 seconds

To optimize:
- Minimize fixture data (only load what's needed)
- Use helper methods instead of loading large SQL files
- Consider splitting large integration tests into smaller focused tests

## Future Enhancements

- [x] Add end-to-end tests (HTTP request/response cycle)
- [x] Add authentication integration tests (via E2E tests)
- [x] Add URL path resolution tests (via E2E routing tests)
- [x] Multi-backend support (PostgreSQL + SQLite)
- [ ] Tag tests with `@group integration` for filtering
- [ ] Add full-text search integration tests
- [ ] Database seeder for realistic test data
- [ ] Parallel test execution
- [ ] API documentation generation from E2E tests
- [ ] Load testing for performance benchmarks
