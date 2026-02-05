<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Finder\Dialect\PostgresDialect;
use Duon\Cms\Finder\Dialect\SqlDialectFactory;
use Duon\Cms\Finder\Dialect\SqliteDialect;
use Duon\Cms\Tests\TestCase;

final class SqlDialectTest extends TestCase
{
	public function testPostgresDialectSelection(): void
	{
		$dialect = SqlDialectFactory::fromDriver('pgsql');

		$this->assertInstanceOf(PostgresDialect::class, $dialect);
		$this->assertSame('cms.nodes', $dialect->table('nodes'));
		$this->assertSame('audit.nodes', $dialect->table('audit.nodes'));
	}

	public function testSqliteDialectSelection(): void
	{
		$dialect = SqlDialectFactory::fromDriver('sqlite');

		$this->assertInstanceOf(SqliteDialect::class, $dialect);
		$this->assertSame('cms_nodes', $dialect->table('nodes'));
		$this->assertSame('audit_nodes', $dialect->table('audit.nodes'));
	}

	public function testSqliteWildcardJsonFieldCompare(): void
	{
		$dialect = new SqliteDialect();
		$result = $dialect->jsonFieldCompare('n.content', 'title.value.*', '=', 'Test Homepage', ':q1');

		$this->assertSame(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.title.value') WHERE value = :q1)",
			$result['sql'],
		);
		$this->assertSame('Test Homepage', $result['paramValue']);
	}

	public function testSqliteWildcardJsonFieldRegex(): void
	{
		$dialect = new SqliteDialect();
		$result = $dialect->jsonFieldRegex('n.content', 'title.value.*', '^Test', false, false, ':q1');

		$this->assertSame(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.title.value') WHERE value REGEXP :q1)",
			$result['sql'],
		);
		$this->assertSame('^Test', $result['paramValue']);
	}
}
