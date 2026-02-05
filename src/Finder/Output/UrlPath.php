<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;

final readonly class UrlPath extends Expression implements Output
{
	public function __construct(
		public Token $left,
		public Token $operator,
		public Token $right,
		public ?Context $context = null,
	) {}

	protected function getDialect(): ?SqlDialect
	{
		return $this->context?->dialect();
	}

	public function get(): CompiledQuery
	{
		// TODO: Implement in Step 5.5
		return CompiledQuery::empty();
	}
}
