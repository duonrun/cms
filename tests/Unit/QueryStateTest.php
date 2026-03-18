<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Finder\QueryState;
use Duon\Cms\Finder\SqlFragment;
use Duon\Cms\Tests\TestCase;

final class QueryStateTest extends TestCase
{
	public function testBaseParamsKeepConditionAndFlagsTogether(): void
	{
		$state = QueryState::defaults()
			->withFilters(new SqlFragment('a = :a', ['a' => 1]))
			->withTypes(new SqlFragment('t.handle = :type', ['type' => 'page']))
			->withPublished(null)
			->withHidden(true);

		$this->assertSame([
			'condition' => '(a = :a) AND (t.handle = :type)',
			'deleted' => false,
			'hidden' => true,
			'a' => 1,
			'type' => 'page',
		], $state->baseParams());
	}

	public function testFindParamsIncludeOrderLimitAndOffset(): void
	{
		$state = QueryState::defaults()
			->withOrder('n.uid ASC')
			->withLimit(10)
			->withOffset(5);

		$this->assertSame([
			'condition' => '',
			'deleted' => false,
			'published' => true,
			'hidden' => false,
			'order' => 'n.uid ASC',
			'limit' => 10,
			'offset' => 5,
		], $state->findParams());
	}
}
