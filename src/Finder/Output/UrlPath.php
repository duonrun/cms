<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
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
		[$pathColumn, $valueToken] = $this->resolveOperands();

		$value = match ($valueToken->type) {
			TokenType::String => $params->add($valueToken->lexeme),
			default => throw new ParserOutputException(
				$valueToken,
				'Path comparisons require a string value.',
			),
		};

		return match ($this->operator->type) {
			TokenType::Equal => "up.path = {$value}",
			TokenType::Unequal => "up.path != {$value}",
			TokenType::Like => $this->dialect->like('up.path', $value),
			TokenType::Unlike => $this->dialect->unlike('up.path', $value),
			TokenType::ILike => $this->dialect->ilike('up.path', $value),
			TokenType::IUnlike => $this->dialect->iunlike('up.path', $value),
			default => throw new ParserOutputException(
				$this->operator,
				'Only =, !=, ~, !~, ~~, !~~ operators are supported for path queries.',
			),
		};
	}

	private function resolveOperands(): array
	{
		if ($this->left->type === TokenType::Path) {
			return [$this->left, $this->right];
		}

		return [$this->right, $this->left];
	}
}
