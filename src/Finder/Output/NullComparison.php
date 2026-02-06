<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\QueryParams;

final readonly class NullComparison extends Expression implements Output
{
	public function __construct(
		private Token $left,
		private Token $operator,
		private Token $right,
		private Context $context,
		private SqlDialect $dialect,
		private array $builtins,
	) {}

	public function get(QueryParams $params): string
	{
		switch ($this->operator->type) {
			case TokenType::Equal:
				return $this->getSqlExpression(true, $params);
			case TokenType::Unequal:
				return $this->getSqlExpression(false, $params);
		}

		throw new ParserOutputException(
			$this->operator,
			'Only equal (=) or unequal (!=) operators are allowed in queries with an null value.',
		);
	}

	private function getSqlExpression(bool $equal, QueryParams $params): string
	{
		return sprintf(
			'%s %s %s',
			$this->getOperand($this->left, $params, $this->builtins, $this->dialect),
			$equal ? 'IS' : 'IS NOT',
			$this->getOperand($this->right, $params, $this->builtins, $this->dialect),
		);
	}
}
