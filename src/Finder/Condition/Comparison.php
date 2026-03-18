<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Condition;

use Duon\Cms\Finder\Input\Token;

final readonly class Comparison implements Part
{
	public function __construct(
		public Token $left,
		public Token $operator,
		public Token $right,
	) {}
}
