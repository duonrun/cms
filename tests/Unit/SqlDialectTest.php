<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Dialect\DialectFactory;
use Duon\Cms\Finder\Dialect\PostgresDialect;
use Duon\Cms\Finder\Dialect\SqliteDialect;
use Duon\Cms\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlDialectTest extends TestCase
{
	protected function tearDown(): void
	{
		parent::tearDown();
		DialectFactory::reset();
	}

	public function testDialectFactoryReturnsPostgresDialect(): void
	{
		$dialect = DialectFactory::forDriver('pgsql');

		$this->assertInstanceOf(PostgresDialect::class, $dialect);
		$this->assertEquals('pgsql', $dialect->driver());
	}

	public function testDialectFactoryReturnsSqliteDialect(): void
	{
		$dialect = DialectFactory::forDriver('sqlite');

		$this->assertInstanceOf(SqliteDialect::class, $dialect);
		$this->assertEquals('sqlite', $dialect->driver());
	}

	public function testDialectFactoryThrowsForUnsupportedDriver(): void
	{
		$this->throws(RuntimeException::class, "Unsupported database driver for Finder: 'mysql'");

		DialectFactory::forDriver('mysql');
	}

	public function testDialectFactoryCachesInstance(): void
	{
		$first = DialectFactory::forDriver('pgsql');
		$second = DialectFactory::forDriver('pgsql');

		$this->assertSame($first, $second);
	}

	public function testDialectFactoryResetClearsCache(): void
	{
		$first = DialectFactory::forDriver('pgsql');
		DialectFactory::reset();
		$second = DialectFactory::forDriver('pgsql');

		$this->assertNotSame($first, $second);
	}

	// PostgreSQL table names
	public function testPostgresTableNameSchemaQualified(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals('cms.nodes', $dialect->table('cms', 'nodes'));
		$this->assertEquals('cms.users', $dialect->table('cms', 'users'));
		$this->assertEquals('audit.nodes', $dialect->table('audit', 'nodes'));
	}

	// SQLite table names
	public function testSqliteTableNameFlattened(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals('cms_nodes', $dialect->table('cms', 'nodes'));
		$this->assertEquals('cms_users', $dialect->table('cms', 'users'));
		$this->assertEquals('audit_nodes', $dialect->table('audit', 'nodes'));
	}

	// PostgreSQL JSON extraction
	public function testPostgresJsonExtractText(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals("n.content->>'title'", $dialect->jsonExtractText('n.content', 'title'));
		$this->assertEquals(
			"n.content->'title'->>'en'",
			$dialect->jsonExtractText('n.content', 'title.en'),
		);
		$this->assertEquals(
			"n.content->'field'->'value'->>'en'",
			$dialect->jsonExtractText('n.content', 'field.value.en'),
		);
	}

	public function testPostgresJsonExtract(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals("n.content->'active'", $dialect->jsonExtract('n.content', 'active'));
		$this->assertEquals(
			"n.content->'field'->'value'",
			$dialect->jsonExtract('n.content', 'field.value'),
		);
	}

	// SQLite JSON extraction
	public function testSqliteJsonExtractText(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals(
			"json_extract(n.content, '\$.title')",
			$dialect->jsonExtractText('n.content', 'title'),
		);
		$this->assertEquals(
			"json_extract(n.content, '\$.title.en')",
			$dialect->jsonExtractText('n.content', 'title.en'),
		);
	}

	public function testSqliteJsonExtract(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals(
			"json_extract(n.content, '\$.active')",
			$dialect->jsonExtract('n.content', 'active'),
		);
	}

	// Pattern matching operators
	public function testPostgresLikeOperators(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals('col LIKE :p0', $dialect->like('col', ':p0'));
		$this->assertEquals('col ILIKE :p0', $dialect->ilike('col', ':p0'));
	}

	public function testSqliteLikeOperators(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals('col LIKE :p0', $dialect->like('col', ':p0'));
		$this->assertEquals('col LIKE :p0 COLLATE NOCASE', $dialect->ilike('col', ':p0'));
	}

	// Regex operators
	public function testPostgresRegexOperators(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals('col ~ :p0', $dialect->regex('col', ':p0'));
		$this->assertEquals('col ~* :p0', $dialect->iregex('col', ':p0'));
	}

	public function testSqliteRegexOperators(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals('col REGEXP :p0', $dialect->regex('col', ':p0'));
		$this->assertEquals('regexp_i(col, :p0)', $dialect->iregex('col', ':p0'));
	}

	// JSON exists
	public function testPostgresJsonExists(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals("n.content ? 'title'", $dialect->jsonExists('n.content', 'title'));
		$this->assertEquals(
			"n.content->'field'->'value' IS NOT NULL",
			$dialect->jsonExists('n.content', 'field.value'),
		);
	}

	public function testSqliteJsonExists(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals(
			"json_type(n.content, '\$.title') IS NOT NULL",
			$dialect->jsonExists('n.content', 'title'),
		);
		$this->assertEquals(
			"json_type(n.content, '\$.field.value') IS NOT NULL",
			$dialect->jsonExists('n.content', 'field.value'),
		);
	}

	// Now function
	public function testPostgresNow(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals('NOW()', $dialect->now());
	}

	public function testSqliteNow(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals("datetime('now')", $dialect->now());
	}

	// Wildcard locale matching
	public function testPostgresJsonWildcardMatch(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals(
			"n.content @@ '\$.field.value.* == :p0'",
			$dialect->jsonWildcardMatch('n.content', 'field.value', '=', ':p0'),
		);
		$this->assertEquals(
			"n.content @@ '\$.title.value.* != :p0'",
			$dialect->jsonWildcardMatch('n.content', 'title.value', '!=', ':p0'),
		);
		$this->assertEquals(
			"n.content @@ '\$.count.value.* > 5'",
			$dialect->jsonWildcardMatch('n.content', 'count.value', '>', '5'),
		);
	}

	public function testSqliteJsonWildcardMatch(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.field.value') WHERE value = :p0)",
			$dialect->jsonWildcardMatch('n.content', 'field.value', '=', ':p0'),
		);
		$this->assertEquals(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.title.value') WHERE value != :p0)",
			$dialect->jsonWildcardMatch('n.content', 'title.value', '!=', ':p0'),
		);
		$this->assertEquals(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.count.value') WHERE value > 5)",
			$dialect->jsonWildcardMatch('n.content', 'count.value', '>', '5'),
		);
	}

	// NOT LIKE operators
	public function testPostgresUnlikeOperators(): void
	{
		$dialect = new PostgresDialect();

		$this->assertEquals('col NOT LIKE :p0', $dialect->unlike('col', ':p0'));
		$this->assertEquals('col NOT ILIKE :p0', $dialect->iunlike('col', ':p0'));
	}

	public function testSqliteUnlikeOperators(): void
	{
		$dialect = new SqliteDialect();

		$this->assertEquals('col NOT LIKE :p0', $dialect->unlike('col', ':p0'));
		$this->assertEquals('col NOT LIKE :p0 COLLATE NOCASE', $dialect->iunlike('col', ':p0'));
	}
}
