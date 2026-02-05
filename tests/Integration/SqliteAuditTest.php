<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Tests\TestCase;
use PDO;
use PDOException;

final class SqliteAuditTest extends TestCase
{
	public function testSqliteAuditTriggersCaptureUserNodeDraftUpdates(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = $this->createSqlitePath();

		try {
			$pdo = $this->createSqliteConnection($path);
			$this->applySqliteMigrations($pdo);
			$this->seedAuditFixtures($pdo);

			$oldUserChanged = (string) $pdo->query('SELECT changed FROM cms_users WHERE usr = 1')
				->fetchColumn();
			$pdo->exec("UPDATE cms_users SET email = 'new@example.com' WHERE usr = 1");

			$this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM audit_users WHERE usr = 1')
				->fetchColumn());
			$this->assertSame(
				$oldUserChanged,
				(string) $pdo->query('SELECT changed FROM audit_users WHERE usr = 1')
					->fetchColumn(),
			);
			$this->assertSame(
				'user@example.com',
				(string) $pdo->query('SELECT email FROM audit_users WHERE usr = 1')
					->fetchColumn(),
			);

			$oldNodeChanged = (string) $pdo->query('SELECT changed FROM cms_nodes WHERE node = 1')
				->fetchColumn();
			$pdo->exec('UPDATE cms_nodes SET hidden = 1 WHERE node = 1');

			$this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM audit_nodes WHERE node = 1')
				->fetchColumn());
			$this->assertSame(
				$oldNodeChanged,
				(string) $pdo->query('SELECT changed FROM audit_nodes WHERE node = 1')
					->fetchColumn(),
			);
			$this->assertSame(
				0,
				(int) $pdo->query('SELECT hidden FROM audit_nodes WHERE node = 1')
					->fetchColumn(),
			);

			$oldDraftChanged = (string) $pdo->query('SELECT changed FROM cms_drafts WHERE node = 1')
				->fetchColumn();
			$pdo->exec("UPDATE cms_drafts SET content = '{\"title\":\"Updated\"}', changed = CURRENT_TIMESTAMP WHERE node = 1");

			$this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM audit_drafts WHERE node = 1')
				->fetchColumn());
			$this->assertSame(
				$oldDraftChanged,
				(string) $pdo->query('SELECT changed FROM audit_drafts WHERE node = 1')
					->fetchColumn(),
			);
			$this->assertSame(
				'{"title":"Draft"}',
				(string) $pdo->query('SELECT content FROM audit_drafts WHERE node = 1')
					->fetchColumn(),
			);
		} finally {
			$this->cleanupSqlitePath($path);
		}
	}

	public function testSqliteRejectsNodeDeletionWhenReferencedByMenu(): void
	{
		if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
			$this->markTestSkipped('pdo_sqlite is not available');
		}

		$path = $this->createSqlitePath();

		try {
			$pdo = $this->createSqliteConnection($path);
			$this->applySqliteMigrations($pdo);
			$this->seedAuditFixtures($pdo);

			$pdo->exec("INSERT INTO cms_menus (menu, description) VALUES ('main', 'Main')");
			$pdo->exec(
				"INSERT INTO cms_menuitems (item, parent, menu, displayorder, data) "
				. "VALUES ('home', NULL, 'main', 1, '{\"type\":\"node\",\"node\":\"1\"}')",
			);

			$this->expectException(PDOException::class);
			$this->expectExceptionMessage('node is still referenced in a menu');
			$pdo->exec('UPDATE cms_nodes SET deleted = CURRENT_TIMESTAMP WHERE node = 1');
		} finally {
			$this->cleanupSqlitePath($path);
		}
	}

	private function createSqlitePath(): string
	{
		$path = tempnam(sys_get_temp_dir(), 'duon-cms-sqlite-');

		if ($path === false) {
			$this->markTestSkipped('Unable to create temporary sqlite file');
		}

		return $path;
	}

	private function createSqliteConnection(string $path): PDO
	{
		$pdo = new PDO('sqlite:' . $path, null, null, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
		$pdo->exec('PRAGMA foreign_keys = ON');

		return $pdo;
	}

	private function applySqliteMigrations(PDO $pdo): void
	{
		$paths = [
			self::root() . '/db/migrations/install/sqlite/000000-000000-init-ddl.sql',
			self::root() . '/db/migrations/install/sqlite/000000-000001-init-audit.sql',
		];

		foreach ($paths as $path) {
			$sql = file_get_contents($path);
			$this->assertNotFalse($sql);
			$pdo->exec((string) $sql);
		}
	}

	private function seedAuditFixtures(PDO $pdo): void
	{
		$pdo->exec("INSERT INTO cms_userroles (userrole) VALUES ('admin')");
		$pdo->exec(
			"INSERT INTO cms_users (usr, uid, username, email, pwhash, userrole, active, data, creator, editor) "
			. "VALUES (1, 'user-1', 'admin', 'user@example.com', 'hash', 'admin', 1, '{}', 1, 1)",
		);
		$pdo->exec("INSERT INTO cms_types (type, handle, kind) VALUES (1, 'page', 'page')");
		$pdo->exec(
			"INSERT INTO cms_nodes (node, uid, parent, published, hidden, locked, type, creator, editor, content) "
			. "VALUES (1, 'node-1', NULL, 1, 0, 0, 1, 1, 1, '{\"title\":\"Home\"}')",
		);
		$pdo->exec(
			"INSERT INTO cms_drafts (node, changed, editor, content) "
			. "VALUES (1, CURRENT_TIMESTAMP, 1, '{\"title\":\"Draft\"}')",
		);
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
