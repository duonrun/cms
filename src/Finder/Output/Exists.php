<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Input\Token;

final readonly class Exists extends Expression implements Output
{
	public function __construct(
		private Token $token,
	) {}

	public function get(): CompiledQuery
	{
		return CompiledQuery::empty();
	}
}
