<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Input\Token;

class LeftParen implements Output
{
	public function __construct(
		public Token $token,
	) {}

	public function get(): CompiledQuery
	{
		return CompiledQuery::sql('(');
	}
}
