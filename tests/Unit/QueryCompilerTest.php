<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserException;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Dialect\SqlDialectFactory;
use Duon\Cms\Finder\QueryCompiler;
use Duon\Cms\Tests\TestCase;

final class QueryCompilerTest extends TestCase
{
	private Context $context;

	protected function setup(): void
	{
		$this->context = new Context(
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testSimpleAndQuery(): void
	{
		$field = $this->fieldComparison('field.value', '=', '1', ':q1');
		$this->assertCompiled(
			'field=1 & builtin=2',
			['builtin' => 'builtin'],
			$field['sql'] . ' AND builtin = :q2',
			array_merge($field['params'], ['q2' => '2']),
		);
	}

	public function testInAndNotInQueryWithBuiltin(): void
	{
		$this->assertCompiled(
			'builtin @ ["v1", "v2", "v3"]',
			['builtin' => 'builtin'],
			'builtin IN (:q1, :q2, :q3)',
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3'],
		);
		$this->assertCompiled(
			'builtin @ [1, 2, 3, 4]',
			['builtin' => 'builtin'],
			'builtin IN (:q1, :q2, :q3, :q4)',
			['q1' => '1', 'q2' => '2', 'q3' => '3', 'q4' => '4'],
		);
		$this->assertCompiled(
			'builtin !@ ["v1", "v2", "v3"]',
			['builtin' => 'builtin'],
			'builtin NOT IN (:q1, :q2, :q3)',
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3'],
		);
		$this->assertCompiled(
			'builtin !@ [1, 2, 3, 4]',
			['builtin' => 'builtin'],
			'builtin NOT IN (:q1, :q2, :q3, :q4)',
			['q1' => '1', 'q2' => '2', 'q3' => '3', 'q4' => '4'],
		);
	}

	public function testInAndNotInQueryWithField(): void
	{
		$field = $this->fieldExpr('field');
		$numericField = $this->numericFieldExpr('field');
		$this->assertCompiled(
			'field @ ["v1", "v2", "v3", "v4"]',
			['builtin' => 'builtin'],
			"{$field} IN (:q1, :q2, :q3, :q4)",
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3', 'q4' => 'v4'],
		);
		$this->assertCompiled(
			'field @ [1, 2, 3, 4.513]',
			['builtin' => 'builtin'],
			"{$numericField} IN (:q1, :q2, :q3, :q4)",
			['q1' => '1', 'q2' => '2', 'q3' => '3', 'q4' => '4.513'],
		);
		$this->assertCompiled(
			'field !@ ["v1", "v2", "v3", "v4"]',
			['builtin' => 'builtin'],
			"{$field} NOT IN (:q1, :q2, :q3, :q4)",
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3', 'q4' => 'v4'],
		);
		$this->assertCompiled(
			'field !@ [1, 0.0002, 3, 4]',
			['builtin' => 'builtin'],
			"{$numericField} NOT IN (:q1, :q2, :q3, :q4)",
			['q1' => '1', 'q2' => '0.0002', 'q3' => '3', 'q4' => '4'],
		);
	}

	public function testSimpleOrQuery(): void
	{
		$field = $this->fieldComparison('field.value', '=', '1', ':q1');
		$this->assertCompiled(
			'field=1 | builtin=2',
			['builtin' => 'builtin'],
			$field['sql'] . ' OR builtin = :q2',
			array_merge($field['params'], ['q2' => '2']),
		);
	}

	public function testNestedQuery1(): void
	{
		$field = $this->fieldComparison('field.value', '=', '1', ':q1');
		$this->assertCompiled(
			'field=1 & (builtin=2|builtin=3)',
			['builtin' => 'n.builtin'],
			$field['sql'] . ' AND (n.builtin = :q2 OR n.builtin = :q3)',
			array_merge($field['params'], ['q2' => '2', 'q3' => '3']),
		);
	}

	public function testNestedQuery2(): void
	{
		$field = $this->fieldComparison('field.value', '=', '1', ':q1');
		$this->assertCompiled(
			"field=1 & (another='test'|(builtin>2 & builtin<5))",
			['builtin' => 'n.builtin', 'another' => 't.another'],
			$field['sql'] . ' AND (t.another = :q2 OR (n.builtin > :q3 AND n.builtin < :q4))',
			array_merge($field['params'], ['q2' => 'test', 'q3' => '2', 'q4' => '5']),
		);
	}

	public function testNestedQuery3(): void
	{
		$field = $this->fieldComparison('field.value', '=', '1', ':q2');
		$this->assertCompiled(
			"(builtin = 1 | field=1) & (another='test'|(builtin>2 & builtin<5))",
			['builtin' => 'n.builtin', 'another' => 't.another'],
			'(n.builtin = :q1 OR ' . $field['sql'] . ') AND (t.another = :q3 OR (n.builtin > :q4 AND n.builtin < :q5))',
			[
				'q1' => '1',
				...$field['params'],
				'q3' => 'test',
				'q4' => '2',
				'q5' => '5',
			],
		);
	}

	public function testNullQuery(): void
	{
		$this->assertCompiled(
			'builtin = null',
			['builtin' => 'n.builtin'],
			'n.builtin IS NULL',
			[],
		);
	}

	public function testNotNullQuery(): void
	{
		$this->assertCompiled(
			'builtin != null',
			['builtin' => 'n.builtin'],
			'n.builtin IS NOT NULL',
			[],
		);
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

	public function testSqlInjectionBecomesBoundValue(): void
	{
		$compiler = new QueryCompiler($this->context, ['builtin' => 'builtin']);

		$compiled = $compiler->compile("builtin = \"value' OR 1=1 --\"");

		$this->assertSame('builtin = :q1', $compiled->sql);
		$this->assertSame(['q1' => "value' OR 1=1 --"], $compiled->params);
		$this->assertStringNotContainsString('OR 1=1', $compiled->sql);
	}

	private function assertCompiled(
		string $query,
		array $builtins,
		string $expectedSql,
		array $expectedParams,
	): void {
		$compiler = new QueryCompiler($this->context, $builtins);
		$compiled = $compiler->compile($query);

		$this->assertSame($expectedSql, $compiled->sql);
		$this->assertSame($expectedParams, $compiled->params);
	}

	private function dialect(): SqlDialect
	{
		return SqlDialectFactory::fromDriver($this->context->db->getPdoDriver());
	}

	private function fieldExpr(string $path): string
	{
		return $this->dialect()->jsonExtract('n.content', $path);
	}

	private function numericFieldExpr(string $path): string
	{
		$field = $this->fieldExpr($path);

		return $this->dialect()->driver() === 'sqlite'
			? "CAST({$field} AS NUMERIC)"
			: $field;
	}

	private function fieldComparison(string $path, string $operator, string $value, string $placeholder): array
	{
		$result = $this->dialect()->jsonFieldCompare('n.content', $path, $operator, $value, $placeholder);
		$paramName = ltrim($placeholder, ':');

		return [
			'sql' => $result['sql'],
			'params' => [$paramName => $result['paramValue']],
		];
	}
}
