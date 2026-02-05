<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\ParamCounter;

final readonly class NullComparison extends Expression implements Output
{
	public function __construct(
		private Token $left,
		private Token $operator,
		private Token $right,
		private Context $context,
		private array $builtins,
		private ?ParamCounter $paramCounter = null,
	) {}

	protected function getDialect(): ?SqlDialect
	{
		return $this->context->dialect();
	}

	public function get(): CompiledQuery
	{
		switch ($this->operator->type) {
			case TokenType::Equal:
				return $this->getSqlExpression(true);
			case TokenType::Unequal:
				return $this->getSqlExpression(false);
		}

		throw new ParserOutputException(
			$this->operator,
			'Only equal (=) or unequal (!=) operators are allowed in queries with an null value.',
		);
	}

	private function getSqlExpression(bool $equal): CompiledQuery
	{
		$params = [];
		$paramIndex = $this->paramCounter?->current() ?? 0;

		$sql = sprintf(
			'%s %s %s',
			$this->getOperand($this->left, $this->builtins, $params, $paramIndex),
			$equal ? 'IS' : 'IS NOT',
			$this->getOperand($this->right, $this->builtins, $params, $paramIndex),
		);

		// Update the shared counter if we have one
		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}
}
