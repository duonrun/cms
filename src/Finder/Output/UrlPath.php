<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\QueryParams;

final readonly class UrlPath extends Expression implements Output
{
	public function __construct(
		public Token $left,
		public Token $operator,
		public Token $right,
		public SqlDialect $dialect,
	) {}

	public function get(QueryParams $params): string
	{
		return '';
	}
}
