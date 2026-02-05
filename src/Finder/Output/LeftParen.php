<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\QueryParams;

class LeftParen implements Output
{
	public function __construct(
		public Token $token,
	) {}

	public function get(QueryParams $params): string
	{
		return '(';
	}
}
