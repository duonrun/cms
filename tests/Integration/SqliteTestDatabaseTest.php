<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Cms\Tests\Support\TestDbConfig;

final class SqliteTestDatabaseTest extends IntegrationTestCase
{
	public function testSqliteTestDatabaseIsInitialized(): void
	{
		if (TestDbConfig::driver() !== 'sqlite') {
			$this->markTestSkipped('SQLite test database is only created for sqlite runs');
		}

		$db = $this->db();
		$table = $db->execute(
			"SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'cms_users'",
		)->one();

		$this->assertNotEmpty($table);

		$systemUser = $db->execute(
			"SELECT uid FROM cms_users WHERE userrole = 'system' LIMIT 1",
		)->one();

		$this->assertSame('0000000000000', $systemUser['uid']);
	}
}
