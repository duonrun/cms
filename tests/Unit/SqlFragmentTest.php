<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Finder\SqlFragment;
use Duon\Cms\Tests\TestCase;

final class SqlFragmentTest extends TestCase
{
	public function testAndMergesSqlAndParams(): void
	{
		$left = new SqlFragment('a = :a', ['a' => 1]);
		$right = new SqlFragment('b = :b', ['b' => 2]);

		$result = $left->and($right);

		$this->assertSame('(a = :a) AND (b = :b)', $result->sql);
		$this->assertSame(['a' => 1, 'b' => 2], $result->params);
	}

	public function testAndSkipsEmptyFragments(): void
	{
		$fragment = new SqlFragment('a = 1');

		$this->assertSame($fragment, SqlFragment::empty()->and($fragment));
		$this->assertSame($fragment, $fragment->and(SqlFragment::empty()));
	}
}
