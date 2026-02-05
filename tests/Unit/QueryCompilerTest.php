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
			$this->db(),
			$this->request(),
			$this->config(),
			$this->registry(),
			$this->factory(),
		);
	}

	public function testSimpleAndQuery(): void
	{
		$this->assertCompiled(
			'field=1 & builtin=2',
			['builtin' => 'builtin'],
			'n.content @@ :q1 AND builtin = :q2',
			['q1' => '$.field.value == 1', 'q2' => '2'],
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
		$this->assertCompiled(
			'field @ ["v1", "v2", "v3", "v4"]',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' IN (:q1, :q2, :q3, :q4)",
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3', 'q4' => 'v4'],
		);
		$this->assertCompiled(
			'field @ [1, 2, 3, 4.513]',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' IN (:q1, :q2, :q3, :q4)",
			['q1' => '1', 'q2' => '2', 'q3' => '3', 'q4' => '4.513'],
		);
		$this->assertCompiled(
			'field !@ ["v1", "v2", "v3", "v4"]',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' NOT IN (:q1, :q2, :q3, :q4)",
			['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3', 'q4' => 'v4'],
		);
		$this->assertCompiled(
			'field !@ [1, 0.0002, 3, 4]',
			['builtin' => 'builtin'],
			"n.content->'field'->>'value' NOT IN (:q1, :q2, :q3, :q4)",
			['q1' => '1', 'q2' => '0.0002', 'q3' => '3', 'q4' => '4'],
		);
	}

	public function testSimpleOrQuery(): void
	{
		$this->assertCompiled(
			'field=1 | builtin=2',
			['builtin' => 'builtin'],
			'n.content @@ :q1 OR builtin = :q2',
			['q1' => '$.field.value == 1', 'q2' => '2'],
		);
	}

	public function testNestedQuery1(): void
	{
		$this->assertCompiled(
			'field=1 & (builtin=2|builtin=3)',
			['builtin' => 'n.builtin'],
			'n.content @@ :q1 AND (n.builtin = :q2 OR n.builtin = :q3)',
			['q1' => '$.field.value == 1', 'q2' => '2', 'q3' => '3'],
		);
	}

	public function testNestedQuery2(): void
	{
		$this->assertCompiled(
			"field=1 & (another='test'|(builtin>2 & builtin<5))",
			['builtin' => 'n.builtin', 'another' => 't.another'],
			'n.content @@ :q1 AND (t.another = :q2 OR (n.builtin > :q3 AND n.builtin < :q4))',
			['q1' => '$.field.value == 1', 'q2' => 'test', 'q3' => '2', 'q4' => '5'],
		);
	}

	public function testNestedQuery3(): void
	{
		$this->assertCompiled(
			"(builtin = 1 | field=1) & (another='test'|(builtin>2 & builtin<5))",
			['builtin' => 'n.builtin', 'another' => 't.another'],
			'(n.builtin = :q1 OR n.content @@ :q2) AND (t.another = :q3 OR (n.builtin > :q4 AND n.builtin < :q5))',
			[
				'q1' => '1',
				'q2' => '$.field.value == 1',
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
}
