<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Tests\Support\TestDbConfig;
use Duon\Cms\Tests\TestCase;

final class TestDbConfigTest extends TestCase
{
	/** @var array<string, string|false> */
	private array $envBackup = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->backupEnv('CMS_TEST_DRIVER', 'CMS_TEST_PGSQL_DSN', 'CMS_TEST_SQLITE_PATH');
	}

	protected function tearDown(): void
	{
		$this->restoreEnv();
		parent::tearDown();
	}

	public function testDriverDefaultsToPgsql(): void
	{
		$this->unsetEnv('CMS_TEST_DRIVER');

		$this->assertSame('pgsql', TestDbConfig::driver());
	}

	public function testDriverReadsEnv(): void
	{
		$this->setEnv('CMS_TEST_DRIVER', 'sqlite');

		$this->assertSame('sqlite', TestDbConfig::driver());
	}

	public function testDriverFallsBackToPgsqlOnInvalid(): void
	{
		$this->setEnv('CMS_TEST_DRIVER', 'mysql');

		$this->assertSame('pgsql', TestDbConfig::driver());
	}

	public function testDsnUsesPgsqlOverride(): void
	{
		$this->setEnv('CMS_TEST_DRIVER', 'pgsql');
		$this->setEnv('CMS_TEST_PGSQL_DSN', 'pgsql:host=test;dbname=duoncms');

		$this->assertSame('pgsql:host=test;dbname=duoncms', TestDbConfig::dsn());
	}

	public function testSqliteDsnUsesPath(): void
	{
		$this->setEnv('CMS_TEST_DRIVER', 'sqlite');
		$this->setEnv('CMS_TEST_SQLITE_PATH', '/tmp/duon-cms.sqlite');

		$this->assertSame('sqlite:/tmp/duon-cms.sqlite', TestDbConfig::dsn());
	}

	public function testSqlitePathDefaultsToTempFile(): void
	{
		$this->setEnv('CMS_TEST_DRIVER', 'sqlite');
		$this->unsetEnv('CMS_TEST_SQLITE_PATH');

		$this->assertStringEndsWith('duon-cms-test.sqlite', TestDbConfig::sqlitePath());
	}

	private function backupEnv(string ...$keys): void
	{
		foreach ($keys as $key) {
			$this->envBackup[$key] = getenv($key);
		}
	}

	private function restoreEnv(): void
	{
		foreach ($this->envBackup as $key => $value) {
			if ($value === false) {
				$this->unsetEnv($key);
				continue;
			}

			$this->setEnv($key, $value);
		}
	}

	private function setEnv(string $key, string $value): void
	{
		putenv($key . '=' . $value);
		$_ENV[$key] = $value;
	}

	private function unsetEnv(string $key): void
	{
		putenv($key);
		unset($_ENV[$key]);
	}
}
