<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Dialect\SqlDialectFactory;
use Duon\Cms\Finder\QueryCompiler;
use Duon\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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
			'jsonb_path_exists(n.content, :q1)',
			'$.field.value ? (@ like_regex ' . $pattern . ')',
		);
		$this->assertJsonPathQuery(
			'field ~* /^test$/',
			'jsonb_path_exists(n.content, :q1)',
			'$.field.value ? (@ like_regex ' . $pattern . ' flag "i")',
		);
		$this->assertJsonPathQuery(
			'field !~ /^test$/',
			'NOT jsonb_path_exists(n.content, :q1)',
			'$.field.value ? (@ like_regex ' . $pattern . ')',
		);
		$this->assertJsonPathQuery(
			'field !~* /^test$/',
			'NOT jsonb_path_exists(n.content, :q1)',
			'$.field.value ? (@ like_regex ' . $pattern . ' flag "i")',
		);
	}

	public function testOperatorLikeAndIlike(): void
	{
		$dialect = $this->dialect();
		$fieldExpr = $this->fieldExpr('field');
		$this->assertCompiled(
			'builtin ~~ "%like\"%"',
			['builtin' => 'builtin'],
			$dialect->like('builtin', ':q1'),
			['q1' => '%like"%'],
		);
		$this->assertCompiled(
			'builtin ~~* /%ilike%/',
			['builtin' => 'builtin'],
			$dialect->ilike('builtin', ':q1'),
			['q1' => '%ilike%'],
		);
		$this->assertCompiled(
			'builtin !~~ /%unlike/',
			['builtin' => 'builtin'],
			$dialect->unlike('builtin', ':q1'),
			['q1' => '%unlike'],
		);
		$this->assertCompiled(
			'builtin !~~* /%iunlike/',
			['builtin' => 'builtin'],
			$dialect->iunlike('builtin', ':q1'),
			['q1' => '%iunlike'],
		);

		$this->assertCompiled(
			'field ~~ "%like\"%"',
			['builtin' => 'builtin'],
			$dialect->like($fieldExpr, ':q1'),
			['q1' => '%like"%'],
		);
		$this->assertCompiled(
			'field ~~* /%ilike%/',
			['builtin' => 'builtin'],
			$dialect->ilike($fieldExpr, ':q1'),
			['q1' => '%ilike%'],
		);
		$this->assertCompiled(
			'field !~~ /%unlike/',
			['builtin' => 'builtin'],
			$dialect->unlike($fieldExpr, ':q1'),
			['q1' => '%unlike'],
		);
		$this->assertCompiled(
			'field !~~* /%iunlike/',
			['builtin' => 'builtin'],
			$dialect->iunlike($fieldExpr, ':q1'),
			['q1' => '%iunlike'],
		);

		$this->assertCompiled(
			'builtin ~~ field',
			['builtin' => 'builtin'],
			$dialect->like('builtin', $fieldExpr),
			[],
		);
		$this->assertCompiled(
			'field ~~ builtin',
			['builtin' => 'builtin'],
			$dialect->like($fieldExpr, 'builtin'),
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
			"builtin > {$this->fieldExpr('field')}",
			[],
		);
		$this->assertCompiled(
			'field<=builtin',
			['builtin' => 'builtin'],
			"{$this->fieldExpr('field')} <= builtin",
			[],
		);
		$this->assertCompiled(
			'field=field2',
			['builtin' => 'builtin'],
			$this->fieldExpr('field') . ' = ' . $this->fieldExpr('field2'),
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
			'test = ' . $this->dialect()->now(),
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

		if ($this->driver() !== 'sqlite') {
			$this->assertSame($expectedSql, $compiled->sql);
			$this->assertSame(['q1' => $expectedPath], $compiled->params);

			return;
		}

		$expected = $this->sqliteExpectation($expectedPath, $expectedSql);

		$this->assertSame($expected['sql'], $compiled->sql);
		$this->assertSame(['q1' => $expected['param']], $compiled->params);
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

	private function driver(): string
	{
		return $this->context->db->getPdoDriver();
	}

	private function dialect(): SqlDialect
	{
		return SqlDialectFactory::fromDriver($this->driver());
	}

	private function fieldExpr(string $path): string
	{
		return $this->dialect()->jsonExtract('n.content', $path);
	}

	private function sqliteExpectation(string $expectedPath, string $expectedSql): array
	{
		$regexMatch = preg_match(
			'/^\$\.(.+?) \? \(@ like_regex (.+?)( flag "i")?\)$/',
			$expectedPath,
			$regexMatches,
		);

		if ($regexMatch === 1) {
			$path = $regexMatches[1];
			$pattern = $this->decodeJsonValue($regexMatches[2]);
			$ignoreCase = !empty($regexMatches[3]);
			$negate = str_starts_with($expectedSql, 'NOT ');
			$isWildcard = str_ends_with($path, '.*');
			$basePath = $isWildcard ? substr($path, 0, -2) : $path;

			if ($isWildcard) {
				$source = "json_each(n.content, '\$.{$basePath}')";
				$expr = $ignoreCase ? 'regexp_i(value, :q1)' : 'value REGEXP :q1';
				$condition = $negate ? "NOT ({$expr})" : $expr;

				return [
					'sql' => "EXISTS (SELECT 1 FROM {$source} WHERE {$condition})",
					'param' => $pattern,
				];
			}

			$field = "json_extract(n.content, '\$.{$basePath}')";
			$expr = $ignoreCase ? "regexp_i({$field}, :q1)" : "{$field} REGEXP :q1";
			$sql = $negate ? "NOT ({$expr})" : $expr;

			return [
				'sql' => $sql,
				'param' => $pattern,
			];
		}

		$compareMatch = preg_match(
			'/^\$\.(.+?)\s+(==|!=|>=|<=|>|<)\s+(.+)$/',
			$expectedPath,
			$matches,
		);

		if ($compareMatch !== 1) {
			throw new RuntimeException('Unsupported JSON path expression for sqlite');
		}

		$path = $matches[1];
		$operator = match ($matches[2]) {
			'==' => '=',
			default => $matches[2],
		};
		$value = $this->decodeJsonValue($matches[3]);
		$isWildcard = str_ends_with($path, '.*');
		$basePath = $isWildcard ? substr($path, 0, -2) : $path;
		$needsNumericCast = is_string($value) && is_numeric($value) && in_array($operator, ['>', '>=', '<', '<='], true);

		if ($isWildcard) {
			$source = "json_each(n.content, '\$.{$basePath}')";
			$left = $needsNumericCast ? 'CAST(value AS NUMERIC)' : 'value';

			return [
				'sql' => "EXISTS (SELECT 1 FROM {$source} WHERE {$left} {$operator} :q1)",
				'param' => $value,
			];
		}

		$field = "json_extract(n.content, '\$.{$basePath}')";
		if ($needsNumericCast) {
			$field = "CAST({$field} AS NUMERIC)";
		}

		return [
			'sql' => "{$field} {$operator} :q1",
			'param' => $value,
		];
	}

	private function decodeJsonValue(string $value): string|bool|null
	{
		$value = trim($value);

		if ($value === 'true') {
			return true;
		}

		if ($value === 'false') {
			return false;
		}

		if ($value === 'null') {
			return null;
		}

		if (str_starts_with($value, '"')) {
			$decoded = json_decode($value, true);

			return is_string($decoded) ? $decoded : '';
		}

		return $value;
	}
}
