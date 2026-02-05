<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserException;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\QueryCompiler;
use Duon\Cms\Tests\TestCase;

final class QueryCompilerTest extends TestCase
{
	private Context $context;

	protected function setup(): void
	{
		$this->context = new Context(
			$this->dbPgsql(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testSimpleAndQuery(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		$result = $compiler->compile('field=1 & builtin=2');
		$this->assertSame("jsonb_path_match(n.content, '$.field.value == 1') AND builtin = 2", $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testInAndNotInQueryWithBuiltin(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// IN queries with builtins use parameterized values
		$result = $compiler->compile("builtin @ ['v1'  , 'v\"2''v3\'']");
		$this->assertSame('builtin IN (:p0, :p1, :p2)', $result->sql);
		$this->assertSame(['p0' => 'v1', 'p1' => 'v"2', 'p2' => "v3'"], $result->params);

		$result = $compiler->compile("builtin @ [,1, 2,3 4]");
		$this->assertSame('builtin IN (:p0, :p1, :p2, :p3)', $result->sql);
		$this->assertSame(['p0' => '1', 'p1' => '2', 'p2' => '3', 'p3' => '4'], $result->params);

		$result = $compiler->compile("builtin !@ ['\'v1''v2''v3']");
		$this->assertSame('builtin NOT IN (:p0, :p1, :p2)', $result->sql);
		$this->assertSame(['p0' => "'v1", 'p1' => 'v2', 'p2' => 'v3'], $result->params);

		$result = $compiler->compile("builtin !@ [1    2  3,,4]");
		$this->assertSame('builtin NOT IN (:p0, :p1, :p2, :p3)', $result->sql);
		$this->assertSame(['p0' => '1', 'p1' => '2', 'p2' => '3', 'p3' => '4'], $result->params);
	}

	public function testInAndNotInQueryWithField(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// IN with fields uses parameterized values
		$result = $compiler->compile("field @ ['v1', 'v2' , 'v\'3''v4' ,]");
		$this->assertSame("n.content->'field'->>'value' IN (:p0, :p1, :p2, :p3)", $result->sql);
		$this->assertSame(['p0' => 'v1', 'p1' => 'v2', 'p2' => "v'3", 'p3' => 'v4'], $result->params);

		$result = $compiler->compile("field @ [1,2 , 3 4.513]");
		$this->assertSame("n.content->'field'->>'value' IN (:p0, :p1, :p2, :p3)", $result->sql);
		$this->assertSame(['p0' => '1', 'p1' => '2', 'p2' => '3', 'p3' => '4.513'], $result->params);

		$result = $compiler->compile("field !@ [, 'v1''v2''v3''v4' ,]");
		$this->assertSame("n.content->'field'->>'value' NOT IN (:p0, :p1, :p2, :p3)", $result->sql);
		$this->assertSame(['p0' => 'v1', 'p1' => 'v2', 'p2' => 'v3', 'p3' => 'v4'], $result->params);

		$result = $compiler->compile("field !@ [, 1 0.0002 , 3 , ,4 ,]");
		$this->assertSame("n.content->'field'->>'value' NOT IN (:p0, :p1, :p2, :p3)", $result->sql);
		$this->assertSame(['p0' => '1', 'p1' => '0.0002', 'p2' => '3', 'p3' => '4'], $result->params);
	}

	public function testInAndNotInQueryWithFieldSqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, ['builtin' => 'builtin']);

		// IN with fields uses json_extract on SQLite
		$result = $compiler->compile("field @ ['v1', 'v2']");
		$this->assertSame(
			"json_extract(n.content, '\$.field.value') IN (:p0, :p1)",
			$result->sql,
		);
		$this->assertSame(['p0' => 'v1', 'p1' => 'v2'], $result->params);

		$result = $compiler->compile("field !@ [1, 2, 3]");
		$this->assertSame(
			"json_extract(n.content, '\$.field.value') NOT IN (:p0, :p1, :p2)",
			$result->sql,
		);
		$this->assertSame(['p0' => '1', 'p1' => '2', 'p2' => '3'], $result->params);
	}

	public function testSimpleOrQuery(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		$result = $compiler->compile('field=1 | builtin=2');
		$this->assertSame("jsonb_path_match(n.content, '$.field.value == 1') OR builtin = 2", $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testNestedQuery1(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin']);

		$result = $compiler->compile('field=1 & (builtin=2|builtin=3)');
		$this->assertSame(
			"jsonb_path_match(n.content, '$.field.value == 1') AND (n.builtin = 2 OR n.builtin = 3)",
			$result->sql,
		);
		$this->assertSame([], $result->params);
	}

	public function testNestedQuery2(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin', 'another' => 't.another']);

		$result = $compiler->compile("field=1 & (another='test'|(builtin>2 & builtin<5))");
		$this->assertSame(
			"jsonb_path_match(n.content, '$.field.value == 1') AND (t.another = :p0 OR (n.builtin > 2 AND n.builtin < 5))",
			$result->sql,
		);
		$this->assertSame(['p0' => 'test'], $result->params);
	}

	public function testNestedQuery3(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin', 'another' => 't.another']);

		$result = $compiler->compile("(builtin = 1 | field=1) & (another='test'|(builtin>2 & builtin<5))");
		$this->assertSame(
			"(n.builtin = 1 OR jsonb_path_match(n.content, '$.field.value == 1'))"
				. ' AND '
				. "(t.another = :p0 OR (n.builtin > 2 AND n.builtin < 5))",
			$result->sql,
		);
		$this->assertSame(['p0' => 'test'], $result->params);
	}

	public function testNullQuery(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin']);

		$result = $compiler->compile('builtin = null');
		$this->assertSame('n.builtin IS NULL', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testNotNullQuery(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin']);

		$result = $compiler->compile('builtin != null');
		$this->assertSame('n.builtin IS NOT NULL', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testNullQueryWrongPosition(): void
	{
		$this->throws(ParserException::class, 'Parse error at position 1. Invalid position for a null value.');

		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin']);

		$compiler->compile('null = builtin');
	}

	public function testNullQueryWrongOperant(): void
	{
		$this->throws(ParserOutputException::class, 'Only equal (=) or unequal (!=) operators are allowed');

		$compiler = new QueryCompiler($this->context, ['builtin' => 'n.builtin']);

		$compiler->compile('builtin ~ null');
	}

	public function testParametersAreUniqueAcrossMultipleExpressions(): void
	{
		$compiler = new QueryCompiler($this->context, ['a' => 'a', 'b' => 'b']);

		$result = $compiler->compile("a = 'first' & b = 'second'");
		$this->assertSame('a = :p0 AND b = :p1', $result->sql);
		$this->assertSame(['p0' => 'first', 'p1' => 'second'], $result->params);
	}

	public function testSqlInjectionPreventedViaParameters(): void
	{
		$compiler = new QueryCompiler($this->context, ['field' => 'field']);

		// Attempt SQL injection via string value
		$result = $compiler->compile("field = \"'; DROP TABLE users; --\"");
		$this->assertSame('field = :p0', $result->sql);
		$this->assertSame(['p0' => "'; DROP TABLE users; --"], $result->params);

		// The malicious value is safely in params, not in SQL
		$this->assertStringNotContainsString('DROP TABLE', $result->sql);
	}

	public function testRegexQueryPostgres(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// Case-sensitive regex - uses jsonb_path_exists to avoid PDO ? conflict
		$result = $compiler->compile("field ~ 'pattern'");
		$this->assertSame("jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"pattern\")')", $result->sql);
		$this->assertSame([], $result->params);

		// Case-insensitive regex
		$result = $compiler->compile("field ~* 'pattern'");
		$this->assertSame("jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"pattern\" flag \"i\")')", $result->sql);
		$this->assertSame([], $result->params);

		// Negated regex
		$result = $compiler->compile("field !~ 'pattern'");
		$this->assertSame("NOT jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"pattern\")')", $result->sql);
		$this->assertSame([], $result->params);

		// Negated case-insensitive regex
		$result = $compiler->compile("field !~* 'pattern'");
		$this->assertSame("NOT jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"pattern\" flag \"i\")')", $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testRegexQuerySqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, ['builtin' => 'builtin']);

		// Case-sensitive regex
		$result = $compiler->compile("field ~ 'pattern'");
		$this->assertSame(
			"json_extract(n.content, '\$.field.value') REGEXP :p0",
			$result->sql,
		);
		$this->assertSame(['p0' => 'pattern'], $result->params);

		// Case-insensitive regex
		$result = $compiler->compile("field ~* 'pattern'");
		$this->assertSame(
			"regexp_i(json_extract(n.content, '\$.field.value'), :p0)",
			$result->sql,
		);
		$this->assertSame(['p0' => 'pattern'], $result->params);

		// Negated regex
		$result = $compiler->compile("field !~ 'pattern'");
		$this->assertSame(
			"NOT (json_extract(n.content, '\$.field.value') REGEXP :p0)",
			$result->sql,
		);
		$this->assertSame(['p0' => 'pattern'], $result->params);

		// Negated case-insensitive regex
		$result = $compiler->compile("field !~* 'pattern'");
		$this->assertSame(
			"NOT (regexp_i(json_extract(n.content, '\$.field.value'), :p0))",
			$result->sql,
		);
		$this->assertSame(['p0' => 'pattern'], $result->params);
	}

	public function testExistsQueryPostgres(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// Simple field existence check - uses jsonb_exists() to avoid PDO ? conflict
		$result = $compiler->compile('title');
		$this->assertSame("jsonb_exists(n.content, 'title')", $result->sql);
		$this->assertSame([], $result->params);

		// Combined with other conditions
		$result = $compiler->compile('title & builtin = 1');
		$this->assertSame("jsonb_exists(n.content, 'title') AND builtin = 1", $result->sql);
	}

	public function testExistsQuerySqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, ['builtin' => 'builtin']);

		// Simple field existence check
		$result = $compiler->compile('title');
		$this->assertSame(
			"json_type(n.content, '\$.title') IS NOT NULL",
			$result->sql,
		);
		$this->assertSame([], $result->params);
	}

	public function testUrlPathQueryPostgres(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// Exact path match
		$result = $compiler->compile("path = '/home'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms.urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path = :p0)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/home'], $result->params);

		// Path with locale filter
		$result = $compiler->compile("path.en = '/english'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms.urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path = :p0 AND up.locale = :p1)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/english', 'p1' => 'en'], $result->params);

		// Path with LIKE pattern
		$result = $compiler->compile("path ~~ '/products/%'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms.urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path LIKE :p0)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/products/%'], $result->params);

		// Path not equal (negated EXISTS)
		$result = $compiler->compile("path != '/home'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms.urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path != :p0)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/home'], $result->params);
	}

	public function testUrlPathQuerySqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, ['builtin' => 'builtin']);

		// Exact path match (uses flattened table name)
		$result = $compiler->compile("path = '/home'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms_urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path = :p0)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/home'], $result->params);

		// Path with locale and ILIKE (SQLite uses COLLATE NOCASE)
		$result = $compiler->compile("path.de ~~* '/produkte%'");
		$this->assertSame(
			'EXISTS (SELECT 1 FROM cms_urlpaths up WHERE up.node = n.node AND up.inactive IS NULL AND up.path LIKE :p0 COLLATE NOCASE AND up.locale = :p1)',
			$result->sql,
		);
		$this->assertSame(['p0' => '/produkte%', 'p1' => 'de'], $result->params);
	}
}
