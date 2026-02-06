<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\QueryParams;

final readonly class Comparison extends Expression implements Output
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
		if ($this->left->type === TokenType::Builtin && $this->left->lexeme === 'fulltext') {
			return $this->getFulltextExpression($params);
		}

		switch ($this->operator->type) {
			case TokenType::Like:
			case TokenType::Unlike:
			case TokenType::ILike:
			case TokenType::IUnlike:
			case TokenType::In:
			case TokenType::NotIn:
				return $this->getSqlExpression($params);
		}

		if ($this->left->type === TokenType::Field) {
			if (
				$this->right->type === TokenType::Builtin
				|| $this->right->type === TokenType::Field
			) {
				return $this->getSqlExpression($params);
			}

			if ($this->isRegexOperator()) {
				return $this->getJsonFieldRegexExpression($params);
			}

			return $this->getJsonFieldCompareExpression($params);
		}

		if ($this->left->type === TokenType::Builtin) {
			return $this->getSqlExpression($params);
		}

		throw new ParserOutputException(
			$this->left,
			'Only fields or `path` are allowed on the left side of an expression.',
		);
	}

	private function getFulltextExpression(QueryParams $params): string
	{
		$this->assertFulltextEnabled();
		$this->assertFulltextOperator();

		$query = $this->getFulltextQuery();
		$placeholder = $params->placeholder();
		$params->set($placeholder, $query);

		$table = $this->dialect->table('fulltext');
		$predicate = $this->dialect->fulltext($table, $placeholder);
		$sql = "EXISTS (SELECT 1 FROM {$table} WHERE {$table}.node = n.node AND {$predicate})";

		if ($this->isFulltextNegated()) {
			return "NOT ({$sql})";
		}

		return $sql;
	}

	private function assertFulltextEnabled(): void
	{
		if ($this->context->config->fulltextEnabled($this->dialect->driver())) {
			return;
		}

		throw new ParserOutputException(
			$this->left,
			'Fulltext search is disabled for this database driver.',
		);
	}

	private function assertFulltextOperator(): void
	{
		if (in_array($this->operator->type, $this->fulltextOperators(), true)) {
			return;
		}

		throw new ParserOutputException(
			$this->operator,
			'Fulltext queries only support =, !=, ~~, ~~*, !~~, and !~~* operators.',
		);
	}

	private function getFulltextQuery(): string
	{
		if ($this->right->type !== TokenType::String) {
			throw new ParserOutputException(
				$this->right,
				'Fulltext queries require a string literal on the right side.',
			);
		}

		return $this->right->lexeme;
	}

	/**
	 * @return list<TokenType>
	 */
	private function fulltextOperators(): array
	{
		return [
			TokenType::Equal,
			TokenType::Unequal,
			TokenType::Like,
			TokenType::Unlike,
			TokenType::ILike,
			TokenType::IUnlike,
		];
	}

	private function isFulltextNegated(): bool
	{
		return in_array(
			$this->operator->type,
			[TokenType::Unequal, TokenType::Unlike, TokenType::IUnlike],
			true,
		);
	}

	private function getJsonFieldCompareExpression(QueryParams $params): string
	{
		$field = $this->getField();
		$operator = $this->mapOperator();
		$value = $this->getRightValue();
		$placeholder = $params->placeholder();

		$result = $this->dialect->jsonFieldCompare('n.content', $field, $operator, $value, $placeholder);
		$params->set($placeholder, $result['paramValue']);

		return $result['sql'];
	}

	private function getJsonFieldRegexExpression(QueryParams $params): string
	{
		if ($this->right->type !== TokenType::String) {
			throw new ParserOutputException(
				$this->right,
				'Only strings are allowed on the right side of a regex expression.',
			);
		}

		$field = $this->getField();
		$pattern = $this->right->lexeme;
		$ignoreCase = in_array($this->operator->type, [TokenType::IRegex, TokenType::INotRegex], true);
		$negate = in_array($this->operator->type, [TokenType::NotRegex, TokenType::INotRegex], true);
		$placeholder = $params->placeholder();

		$result = $this->dialect->jsonFieldRegex('n.content', $field, $pattern, $ignoreCase, $negate, $placeholder);
		$params->set($placeholder, $result['paramValue']);

		return $result['sql'];
	}

	private function isRegexOperator(): bool
	{
		return in_array($this->operator->type, [
			TokenType::Regex,
			TokenType::IRegex,
			TokenType::NotRegex,
			TokenType::INotRegex,
		], true);
	}

	private function mapOperator(): string
	{
		return match ($this->operator->type) {
			TokenType::Equal => '=',
			TokenType::Unequal => '!=',
			TokenType::Greater => '>',
			TokenType::GreaterEqual => '>=',
			TokenType::Less => '<',
			TokenType::LessEqual => '<=',
			default => throw new ParserOutputException(
				$this->operator,
				'Unsupported operator for JSON field comparison.',
			),
		};
	}

	private function getRightValue(): mixed
	{
		return match ($this->right->type) {
			TokenType::String => $this->right->lexeme,
			TokenType::Number => $this->right->lexeme,
			TokenType::Boolean => strtolower($this->right->lexeme) === 'true',
			TokenType::Null => null,
			default => throw new ParserOutputException(
				$this->right,
				'The right hand side in a field expression must be a literal',
			),
		};
	}

	private function getField(): string
	{
		$parts = explode('.', $this->left->lexeme);

		return match (count($parts)) {
			2 => $this->compileFieldPath($parts),
			1 => $parts[0] . '.value',
			default => $this->compileAccessor($parts),
		};
	}

	private function compileFieldPath(array $segments): string
	{
		return match ($segments[1]) {
			'*' => $segments[0] . '.value.*',
			'?' => $segments[0] . '.value.' . $this->getCurrentLocale(),
			default => implode('.', $segments),
		};
	}

	private function compileAccessor(array $segments): string
	{
		$accessor = implode('.', $segments);

		if (strpos($accessor, '?') !== false) {
			throw new ParserOutputException(
				$this->left,
				'The questionmark is allowed after the first dot only.',
			);
		}

		return $accessor;
	}

	private function getCurrentLocale(): string
	{
		return $this->context->localeId();
	}

	private function getSqlExpression(QueryParams $params): string
	{
		$left = $this->getOperand($this->left, $params, $this->builtins, $this->dialect);
		$right = $this->getOperand($this->right, $params, $this->builtins, $this->dialect);

		if (
			$this->dialect->driver() === 'sqlite'
			&& in_array($this->operator->type, [TokenType::In, TokenType::NotIn], true)
			&& $this->left->type === TokenType::Field
			&& $this->right->type === TokenType::List
			&& $this->listIsNumeric($this->right)
		) {
			$left = "CAST({$left} AS NUMERIC)";
		}

		return $this->getOperatorExpression($this->operator->type, $this->dialect, $left, $right);
	}

	private function listIsNumeric(Token $token): bool
	{
		$items = $token->items;

		if ($items === null || $items === []) {
			return false;
		}

		return $items[0]->type === TokenType::Number;
	}
}
