<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\QueryParams;

class Operator implements Output
{
	public function __construct(
		public Token $token,
	) {}

	public function get(QueryParams $params): string
	{
		return match ($this->token->type) {
			TokenType::And => ' AND ',
			TokenType::Or => ' OR ',
			default => throw new ParserException('Invalid boolean operator'),
		};
	}
}
