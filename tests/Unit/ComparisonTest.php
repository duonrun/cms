<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\QueryCompiler;
use Duon\Cms\Tests\TestCase;

final class ComparisonTest extends TestCase
{
	private Context $context;

	protected function setUp(): void
	{
		$this->context = new Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testJsonStringQuoting(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		// JSON path expressions don't use PDO params; values are escaped in the path string
		// Input: " \"\" ' " -> space, two escaped quotes, space, apostrophe, space
		$result = $compiler->compile('field = " \"\" \' "');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value == \" \\\"\\\" ' \"')", $result->sql);
		$this->assertSame([], $result->params);

		// Input: '"""' -> Three double quotes
		$result = $compiler->compile("field = '\"\"\"'");
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "\\"\\"\\""\')', $result->sql);
		$this->assertSame([], $result->params);

		// Input: 'test\' " \" ' -> test, apostrophe, space, quote, space, backslash-quote, space
		$result = $compiler->compile("field = 'test\\' \" \\\" '");
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "test\' \\" \\\\\\" "\')', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testNumberOperand(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		$result = $compiler->compile('field = 13');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value == 13')", $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field.value.de = 13');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value.de == 13')", $result->sql);

		$result = $compiler->compile('field = 13.73');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value == 13.73')", $result->sql);

		$result = $compiler->compile('field.value.de = 13.73');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value.de == 13.73')", $result->sql);
	}

	public function testStringOperand(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		$result = $compiler->compile('field = "string"');
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "string"\')', $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile("field = 'string'");
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "string"\')', $result->sql);

		$result = $compiler->compile('field = /string/');
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "string"\')', $result->sql);

		$result = $compiler->compile("field.value.de = 'string'");
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value.de == "string"\')', $result->sql);
	}

	public function testBooleanOperand(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		$result = $compiler->compile('field = false');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value == false')", $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field = true');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value == true')", $result->sql);

		$result = $compiler->compile('field.value.de = false');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value.de == false')", $result->sql);
	}

	public function testOperatorRegexOperandPattern(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		$result = $compiler->compile('field ~ /^test$/');
		$this->assertSame("jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"^test\$\")')", $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field ~* /^test$/');
		$this->assertSame("jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"^test\$\" flag \"i\")')", $result->sql);

		$result = $compiler->compile('field !~ /^test$/');
		$this->assertSame("NOT jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"^test\$\")')", $result->sql);

		$result = $compiler->compile('field !~* /^test$/');
		$this->assertSame("NOT jsonb_path_exists(n.content, '\$.field.value ? (@ like_regex \"^test\$\" flag \"i\")')", $result->sql);
	}

	public function testOperatorLikeAndIlike(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// SQL expressions use parameterized queries
		$result = $compiler->compile('builtin ~~ "%like\"%"');
		$this->assertSame('builtin LIKE :p0', $result->sql);
		$this->assertSame(['p0' => '%like"%'], $result->params);

		$result = $compiler->compile('builtin ~~* /%ilike%/');
		$this->assertSame('builtin ILIKE :p0', $result->sql);
		$this->assertSame(['p0' => '%ilike%'], $result->params);

		$result = $compiler->compile('builtin !~~ /%unlike/');
		$this->assertSame('builtin NOT LIKE :p0', $result->sql);
		$this->assertSame(['p0' => '%unlike'], $result->params);

		$result = $compiler->compile('builtin !~~* /%iunlike/');
		$this->assertSame('builtin NOT ILIKE :p0', $result->sql);
		$this->assertSame(['p0' => '%iunlike'], $result->params);

		$result = $compiler->compile('field ~~ "%like\"%"');
		$this->assertSame("n.content->'field'->>'value' LIKE :p0", $result->sql);
		$this->assertSame(['p0' => '%like"%'], $result->params);

		// Field-to-field comparisons have no params
		$result = $compiler->compile('builtin ~~ field');
		$this->assertSame("builtin LIKE n.content->'field'->>'value'", $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field ~~ builtin');
		$this->assertSame("n.content->'field'->>'value' LIKE builtin", $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testRemainingOperators(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		// SQL expressions with string literals use params
		$result = $compiler->compile('builtin="string"');
		$this->assertSame('builtin = :p0', $result->sql);
		$this->assertSame(['p0' => 'string'], $result->params);

		$result = $compiler->compile('builtin!="string"');
		$this->assertSame('builtin != :p0', $result->sql);
		$this->assertSame(['p0' => 'string'], $result->params);

		// Numeric comparisons don't use params (safe as-is)
		$result = $compiler->compile('builtin>23');
		$this->assertSame('builtin > 23', $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('builtin>=23');
		$this->assertSame('builtin >= 23', $result->sql);

		$result = $compiler->compile('builtin<23');
		$this->assertSame('builtin < 23', $result->sql);

		$result = $compiler->compile('builtin<=23');
		$this->assertSame('builtin <= 23', $result->sql);

		// JSON path expressions (field comparisons) don't use params
		$result = $compiler->compile('field="string"');
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value == "string"\')', $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field>23');
		$this->assertSame("jsonb_path_match(n.content, '\$.field.value > 23')", $result->sql);

		// Field-to-field and field-to-builtin comparisons
		$result = $compiler->compile('builtin>field');
		$this->assertSame("builtin > n.content->'field'->>'value'", $result->sql);
		$this->assertSame([], $result->params);

		$result = $compiler->compile('field<=builtin');
		$this->assertSame("n.content->'field'->>'value' <= builtin", $result->sql);

		$result = $compiler->compile('field=field2');
		$this->assertSame("n.content->'field'->>'value' = n.content->'field2'->>'value'", $result->sql);
	}

	public function testMultilangFieldOperand(): void
	{
		$compiler = new QueryCompiler($this->context, []);

		$result = $compiler->compile('field.* = "test"');
		$this->assertSame('jsonb_path_match(n.content, \'$.field.value.* == "test"\')', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testMultilangFieldOperandSqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, []);

		$result = $compiler->compile('field.* = "test"');
		$this->assertSame(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.field.value') WHERE value = :p0)",
			$result->sql,
		);
		$this->assertSame(['p0' => 'test'], $result->params);
	}

	public function testMultilangFieldWithNumberSqlite(): void
	{
		$sqliteContext = new Context(
			$this->dbSqlite(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
		$compiler = new QueryCompiler($sqliteContext, []);

		$result = $compiler->compile('field.* > 5');
		$this->assertSame(
			"EXISTS (SELECT 1 FROM json_each(n.content, '\$.field.value') WHERE value > 5)",
			$result->sql,
		);
		$this->assertSame([], $result->params);
	}

	public function testBuiltinOperand(): void
	{
		$compiler = new QueryCompiler($this->context, ['test' => 'table.test']);

		$result = $compiler->compile('test = 1');
		$this->assertSame('table.test = 1', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testKeywordNow(): void
	{
		$compiler = new QueryCompiler($this->context, ['test' => 'test']);

		$result = $compiler->compile('test = now');
		$this->assertSame('test = NOW()', $result->sql);
		$this->assertSame([], $result->params);
	}

	public function testRejectLiteralOnLeftSide(): void
	{
		$this->throws(ParserOutputException::class, 'Only fields or ');

		$compiler = new QueryCompiler($this->context, []);

		$compiler->compile('"string" = 1');
	}
}
