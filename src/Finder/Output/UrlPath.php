<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Input\Token;

final readonly class UrlPath extends Expression implements Output
{
	public function __construct(
		public Token $left,
		public Token $operator,
		public Token $right,
	) {}

	public function get(): CompiledQuery
	{
		return CompiledQuery::empty();
	}
}
