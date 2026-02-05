<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Tests\Support\TestDbConfig;
use Duon\Cms\Tests\TestCase;
use InvalidArgumentException;
use PDO;

/**
 * @internal
 *
 * @coversNothing
 */
final class TestDbConfigTest extends TestCase
{
	protected function tearDown(): void
	{
		parent::tearDown();
		TestDbConfig::reset();
	}

	public function testDefaultDriverIsPgsql(): void
	{
		// Save current env
		$original = getenv('CMS_TEST_DRIVER');
		putenv('CMS_TEST_DRIVER');
		TestDbConfig::reset();

		$config = TestDbConfig::getInstance();

		$this->assertSame('pgsql', $config->driver());
		$this->assertTrue($config->isPgsql());
		$this->assertFalse($config->isSqlite());

		// Restore
		if ($original !== false) {
			putenv("CMS_TEST_DRIVER={$original}");
		}
	}

	public function testSqliteDriverFromEnv(): void
	{
		$original = getenv('CMS_TEST_DRIVER');
		putenv('CMS_TEST_DRIVER=sqlite');
		TestDbConfig::reset();

		$config = TestDbConfig::getInstance();

		$this->assertSame('sqlite', $config->driver());
		$this->assertTrue($config->isSqlite());
		$this->assertFalse($config->isPgsql());
		$this->assertStringStartsWith('sqlite:', $config->dsn());

		// Restore
		if ($original !== false) {
			putenv("CMS_TEST_DRIVER={$original}");
		} else {
			putenv('CMS_TEST_DRIVER');
		}
	}

	public function testPgsqlDriverFromEnv(): void
	{
		$original = getenv('CMS_TEST_DRIVER');
		putenv('CMS_TEST_DRIVER=pgsql');
		TestDbConfig::reset();

		$config = TestDbConfig::getInstance();

		$this->assertSame('pgsql', $config->driver());
		$this->assertTrue($config->isPgsql());
		$this->assertStringStartsWith('pgsql:', $config->dsn());

		// Restore
		if ($original !== false) {
			putenv("CMS_TEST_DRIVER={$original}");
		} else {
			putenv('CMS_TEST_DRIVER');
		}
	}

	public function testInvalidDriverThrows(): void
	{
		$original = getenv('CMS_TEST_DRIVER');
		putenv('CMS_TEST_DRIVER=mysql');
		TestDbConfig::reset();

		try {
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage('Invalid CMS_TEST_DRIVER');

			TestDbConfig::getInstance();
		} finally {
			// Restore env before next test
			if ($original !== false) {
				putenv("CMS_TEST_DRIVER={$original}");
			} else {
				putenv('CMS_TEST_DRIVER');
			}
		}
	}

	public function testSqlDirsReturnsDriverMap(): void
	{
		$config = TestDbConfig::getInstance();
		$root = '/test/root';

		$dirs = $config->sqlDirs($root);

		$this->assertArrayHasKey('pgsql', $dirs);
		$this->assertArrayHasKey('sqlite', $dirs);
		$this->assertSame('/test/root/db/sql/pgsql', $dirs['pgsql']);
		$this->assertSame('/test/root/db/sql/sqlite', $dirs['sqlite']);
	}

	public function testMigrationDirsReturnsDriverMap(): void
	{
		$config = TestDbConfig::getInstance();
		$root = '/test/root';

		$dirs = $config->migrationDirs($root);

		$this->assertArrayHasKey('install', $dirs);
		$this->assertArrayHasKey('default', $dirs);
		$this->assertSame('/test/root/db/migrations/install/pgsql', $dirs['install'][0]['pgsql']);
		$this->assertSame('/test/root/db/migrations/install/sqlite', $dirs['install'][0]['sqlite']);
	}

	public function testFetchModeIsAssoc(): void
	{
		$config = TestDbConfig::getInstance();

		$this->assertSame(PDO::FETCH_ASSOC, $config->fetchMode());
	}

	public function testDescribeReturnsReadableString(): void
	{
		$config = TestDbConfig::getInstance();
		$description = $config->describe();

		$this->assertIsString($description);
		$this->assertNotEmpty($description);

		// Should sanitize password if pgsql
		if ($config->isPgsql()) {
			$this->assertStringContainsString('PGSQL', $description);
			// Password should be replaced with ***
			$this->assertStringContainsString('password=***', $description);
		} else {
			$this->assertStringContainsString('SQLITE', $description);
		}
	}
}
