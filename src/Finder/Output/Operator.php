<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;

class Operator implements Output
{
	public function __construct(
		public Token $token,
	) {}

	public function get(): CompiledQuery
	{
		$sql = match ($this->token->type) {
			TokenType::And => ' AND ',
			TokenType::Or => ' OR ',
			default => throw new ParserException('Invalid boolean operator'),
		};

		return CompiledQuery::sql($sql);
	}
}
