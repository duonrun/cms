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
}
