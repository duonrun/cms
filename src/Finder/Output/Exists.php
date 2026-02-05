<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\QueryParams;

final readonly class Exists extends Expression implements Output
{
	public function __construct(
		private Token $token,
		private SqlDialect $dialect,
	) {}

	public function get(QueryParams $params): string
	{
		$parts = explode('.', $this->token->lexeme);
		$path = count($parts) === 1
			? $parts[0] . '.value'
			: implode('.', $parts);

		return $this->dialect->jsonPathExists('n.content', $path);
	}
}
