<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\QueryCompiler;
use Duon\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

	#[DataProvider('jsonStringValues')]
	public function testJsonStringQuoting(string $value): void
	{
		$encoded = $this->jsonString($value);
		$query = 'field = ' . $encoded;
		$expectedPath = '$.field.value == ' . $encoded;

		$this->assertJsonPathQuery($query, 'n.content @@ :q1', $expectedPath);
	}

	public static function jsonStringValues(): array
	{
		return [
			'double quotes' => ['""'],
			'single quote' => ["'"],
			'mixed quotes' => ['He said "hi" and it\'s ok'],
		];
	}

	public function testNumberOperand(): void
	{
		$this->assertJsonPathQuery('field = 13', 'n.content @@ :q1', '$.field.value == 13');
		$this->assertJsonPathQuery('field.value.de = 13', 'n.content @@ :q1', '$.field.value.de == 13');
		$this->assertJsonPathQuery('field = 13.73', 'n.content @@ :q1', '$.field.value == 13.73');
		$this->assertJsonPathQuery('field.value.de = 13.73', 'n.content @@ :q1', '$.field.value.de == 13.73');
	}

	public function testStringOperand(): void
	{
		$encoded = $this->jsonString('string');

		$this->assertJsonPathQuery('field = "string"', 'n.content @@ :q1', '$.field.value == ' . $encoded);
		$this->assertJsonPathQuery("field = 'string'", 'n.content @@ :q1', '$.field.value == ' . $encoded);
		$this->assertJsonPathQuery('field = /string/', 'n.content @@ :q1', '$.field.value == ' . $encoded);
		$this->assertJsonPathQuery("field.value.de = 'string'", 'n.content @@ :q1', '$.field.value.de == ' . $encoded);
		$this->assertJsonPathQuery('field.value.de = "string"', 'n.content @@ :q1', '$.field.value.de == ' . $encoded);
		$this->assertJsonPathQuery('field.value.de = /string/', 'n.content @@ :q1', '$.field.value.de == ' . $encoded);
	}

	public function testBooleanOperand(): void
	{
		$this->assertJsonPathQuery('field = false', 'n.content @@ :q1', '$.field.value == false');
		$this->assertJsonPathQuery('field = true', 'n.content @@ :q1', '$.field.value == true');
		$this->assertJsonPathQuery('field.value.de = false', 'n.content @@ :q1', '$.field.value.de == false');
		$this->assertJsonPathQuery('field.value.de = true', 'n.content @@ :q1', '$.field.value.de == true');
	}

	public function testOperatorRegexOperandPattern(): void
	{
		$pattern = $this->jsonString('^test$');

		$this->assertJsonPathQuery(
			'field ~ /^test$/',
			'n.content @? :q1',
			'$.field.value ? (@ like_regex ' . $pattern . ')',
		);
		$this->assertJsonPathQuery(
			'field ~* /^test$/',
			'n.content @? :q1',
			'$.field.value ? (@ like_regex ' . $pattern . ' flag "i")',
		);
		$this->assertJsonPathQuery(
			'field !~ /^test$/',
			'NOT n.content @? :q1',
			'$.field.value ? (@ like_regex ' . $pattern . ')',
		);
		$this->assertJsonPathQuery(
			'field !~* /^test$/',
			'NOT n.content @? :q1',
			'$.field.value ? (@ like_regex ' . $pattern . ' flag "i")',
		);
	}

	public function testOperatorLikeAndIlike(): void
	{
		$this->assertCompiled(
			'builtin ~~ "%like\"%"',
			['builtin' => 'builtin'],
			'builtin LIKE :q1',
			['q1' => '%like"%'],
		);
		$this->assertCompiled(
			'builtin ~~* /%ilike%/',
			['builtin' => 'builtin'],
			'builtin ILIKE :q1',
			['q1' => '%ilike%'],
		);
		$this->assertCompiled(
			'builtin !~~ /%unlike/',
			['builtin' => 'builtin'],
			'builtin NOT LIKE :q1',
			['q1' => '%unlike'],
		);
		$this->assertCompiled(
			'builtin !~~* /%iunlike/',
			['builtin' => 'builtin'],
			'builtin NOT ILIKE :q1',
			['q1' => '%iunlike'],
		);

		$this->assertCompiled(
			'field ~~ "%like\"%"',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' LIKE :q1",
			['q1' => '%like"%'],
		);
		$this->assertCompiled(
			'field ~~* /%ilike%/',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' ILIKE :q1",
			['q1' => '%ilike%'],
		);
		$this->assertCompiled(
			'field !~~ /%unlike/',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' NOT LIKE :q1",
			['q1' => '%unlike'],
		);
		$this->assertCompiled(
			'field !~~* /%iunlike/',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' NOT ILIKE :q1",
			['q1' => '%iunlike'],
		);

		$this->assertCompiled(
			'builtin ~~ field',
			['builtin' => 'builtin'],
			"builtin LIKE n.content->'field'->>'value'",
			[],
		);
		$this->assertCompiled(
			'field ~~ builtin',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' LIKE builtin",
			[],
		);
	}

	public function testRemainingOperators(): void
	{
		$this->assertCompiled(
			'builtin="string"',
			['builtin' => 'builtin'],
			'builtin = :q1',
			['q1' => 'string'],
		);
		$this->assertCompiled(
			'builtin!="string"',
			['builtin' => 'builtin'],
			'builtin != :q1',
			['q1' => 'string'],
		);
		$this->assertCompiled(
			'builtin>23',
			['builtin' => 'builtin'],
			'builtin > :q1',
			['q1' => '23'],
		);
		$this->assertCompiled(
			'builtin>=23',
			['builtin' => 'builtin'],
			'builtin >= :q1',
			['q1' => '23'],
		);
		$this->assertCompiled(
			'builtin<23',
			['builtin' => 'builtin'],
			'builtin < :q1',
			['q1' => '23'],
		);
		$this->assertCompiled(
			'builtin<=23',
			['builtin' => 'builtin'],
			'builtin <= :q1',
			['q1' => '23'],
		);

		$encoded = $this->jsonString('string');
		$this->assertJsonPathQuery('field="string"', 'n.content @@ :q1', '$.field.value == ' . $encoded);
		$this->assertJsonPathQuery('field!="string"', 'n.content @@ :q1', '$.field.value != ' . $encoded);
		$this->assertJsonPathQuery('field>23', 'n.content @@ :q1', '$.field.value > 23');
		$this->assertJsonPathQuery('field>=23', 'n.content @@ :q1', '$.field.value >= 23');
		$this->assertJsonPathQuery('field<23', 'n.content @@ :q1', '$.field.value < 23');
		$this->assertJsonPathQuery('field<=23', 'n.content @@ :q1', '$.field.value <= 23');

		$this->assertCompiled(
			'builtin>field',
			['builtin' => 'builtin'],
			"builtin > n.content->'field'->>'value'",
			[],
		);
		$this->assertCompiled(
			'field<=builtin',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' <= builtin",
			[],
		);
		$this->assertCompiled(
			'field=field2',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' = n.content->'field2'->>'value'",
			[],
		);
	}

	public function testMultilangFieldOperand(): void
	{
		$encoded = $this->jsonString('test');
		$this->assertJsonPathQuery('field.* = "test"', 'n.content @@ :q1', '$.field.value.* == ' . $encoded);
	}

	public function testBuiltinOperand(): void
	{
		$this->assertCompiled(
			'test = 1',
			['test' => 'table.test'],
			'table.test = :q1',
			['q1' => '1'],
		);
	}

	public function testKeywordNow(): void
	{
		$this->assertCompiled(
			'test = now',
			['test' => 'test'],
			'test = NOW()',
			[],
		);
	}

	public function testRejectLiteralOnLeftSide(): void
	{
		$this->throws(ParserOutputException::class, 'Only fields or ');

		$compiler = new QueryCompiler($this->context, []);

		$compiler->compile('"string" = 1');
	}

	private function assertCompiled(
		string $query,
		array $builtins,
		string $expectedSql,
		array $expectedParams,
	): void {
		$compiled = $this->compile($query, $builtins);

		$this->assertSame($expectedSql, $compiled->sql);
		$this->assertSame($expectedParams, $compiled->params);
	}

	private function assertJsonPathQuery(string $query, string $expectedSql, string $expectedPath): void
	{
		$compiled = $this->compile($query);

		$this->assertSame($expectedSql, $compiled->sql);
		$this->assertSame(['q1' => $expectedPath], $compiled->params);
	}

	private function compile(string $query, array $builtins = []): CompiledQuery
	{
		$compiler = new QueryCompiler($this->context, $builtins);

		return $compiler->compile($query);
	}

	private function jsonString(string $value): string
	{
		$encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $encoded === false ? '""' : $encoded;
	}
}
