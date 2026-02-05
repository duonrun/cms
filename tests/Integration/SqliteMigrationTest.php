<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\TestCase;
use PDO;

final class SqliteMigrationTest extends TestCase
{
	public function testSqliteInstallMigrationCreatesCoreTables(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			$this->markTestSkipped('Unable to create temporary sqlite file');
		}

		try {
			$pdo = new PDO('sqlite:' . $path, null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			]);
			$migrationPath = self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql';
			$sql = file_get_contents($migrationPath);

			$this->assertNotFalse($sql);
			$pdo->exec((string) $sql);

			$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
				->fetchAll(PDO::FETCH_COLUMN);
			$expected = [
				'cms_userroles',
				'cms_users',
				'cms_authtokens',
				'cms_onetimetokens',
				'cms_loginsessions',
				'cms_types',
				'cms_nodes',
				'cms_urlpaths',
				'cms_drafts',
				'cms_menus',
				'cms_menuitems',
				'cms_topics',
				'cms_tags',
				'cms_nodetags',
			];

			foreach ($expected as $table) {
				$this->assertContains($table, $tables);
			}
		} finally {
			$this->cleanupSqlitePath($path);
		}
	}

	public function testSqliteSeedDataCreatesUserRolesAndSystemUser(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			$this->markTestSkipped('Unable to create temporary sqlite file');
		}

		try {
			$pdo = new PDO('sqlite:' . $path, null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			]);
			$pdo->exec('PRAGMA foreign_keys = ON');

			// Apply DDL and seed data migrations
			$migrations = [
				self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql',
				self::root() . '/db/migrations/install/sqlite/000000-000001-init-audit.sql',
				self::root() . '/db/migrations/install/sqlite/000000-000002-init-data.sql',
			];

			foreach ($migrations as $migrationPath) {
				$sql = file_get_contents($migrationPath);
				$this->assertNotFalse($sql);
				$pdo->exec((string) $sql);
			}

			// Verify user roles
			$roles = $pdo->query('SELECT userrole FROM cms_userroles ORDER BY userrole')
				->fetchAll(PDO::FETCH_COLUMN);
			$this->assertSame(['admin', 'editor', 'superuser', 'system'], $roles);

			// Verify system user
			$user = $pdo->query("SELECT uid, username, email, userrole, active FROM cms_users WHERE uid = '0000000000000'")
				->fetch(PDO::FETCH_ASSOC);
			$this->assertNotFalse($user);
			$this->assertSame('0000000000000', $user['uid']);
			$this->assertSame('system', $user['username']);
			$this->assertSame('system@duon.dev', $user['email']);
			$this->assertSame('system', $user['userrole']);
			$this->assertSame(1, (int) $user['active']);
		} finally {
			$this->cleanupSqlitePath($path);
		}
	}

	private function cleanupSqlitePath(string $path): void
	{
		$walPath = $path . '-wal';
		$shmPath = $path . '-shm';

		if (is_file($walPath)) {
			unlink($walPath);
		}
		if (is_file($shmPath)) {
			unlink($shmPath);
		}
		if (is_file($path)) {
			unlink($path);
		}
	}
}
