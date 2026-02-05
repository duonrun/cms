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

final readonly class Comparison extends Expression implements Output
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
			case TokenType::Like:
			case TokenType::Unlike:
			case TokenType::ILike:
			case TokenType::IUnlike:
			case TokenType::In:
			case TokenType::NotIn:
				return $this->getSqlExpression();
		}

		if ($this->left->type === TokenType::Field) {
			if (
				$this->right->type === TokenType::Builtin
				|| $this->right->type === TokenType::Field
			) {
				return $this->getSqlExpression();
			}

			return $this->getJsonFieldExpression();
		}

		if ($this->left->type === TokenType::Builtin) {
			return $this->getSqlExpression();
		}

		throw new ParserOutputException(
			$this->left,
			'Only fields or `path` are allowed on the left side of an expression.',
		);
	}

	/**
	 * Generate a dialect-specific JSON field comparison expression.
	 *
	 * PostgreSQL uses jsonpath operators (@@ / @?).
	 * SQLite uses json_extract() with standard SQL operators.
	 */
	private function getJsonFieldExpression(): CompiledQuery
	{
		$dialect = $this->context->dialect();

		// For SQLite, we always use SQL-style expressions with json_extract()
		if ($dialect->driver() === 'sqlite') {
			return $this->getSqliteJsonExpression();
		}

		// PostgreSQL uses jsonpath expressions
		return $this->getPostgresJsonPathExpression();
	}

	/**
	 * Generate SQLite JSON expression using json_extract().
	 */
	private function getSqliteJsonExpression(): CompiledQuery
	{
		$dialect = $this->context->dialect();
		$params = [];
		$paramIndex = $this->paramCounter?->current() ?? 0;

		$left = $this->getFieldPath();

		// Handle wildcard locale queries (field.* = "value")
		if (str_ends_with($left, '.*')) {
			return $this->getSqliteWildcardExpression($left, $params, $paramIndex);
		}

		$column = $dialect->jsonExtractText('n.content', $left);

		// Handle regex operators specially
		if (in_array($this->operator->type, [
			TokenType::Regex,
			TokenType::IRegex,
			TokenType::NotRegex,
			TokenType::INotRegex,
		], true)) {
			$paramName = $this->addParam($this->right->lexeme, $params, $paramIndex);
			$negate = in_array($this->operator->type, [TokenType::NotRegex, TokenType::INotRegex], true);

			$regexExpr = match ($this->operator->type) {
				TokenType::Regex, TokenType::NotRegex => $dialect->regex($column, $paramName),
				TokenType::IRegex, TokenType::INotRegex => $dialect->iregex($column, $paramName),
			};

			$sql = $negate ? "NOT ({$regexExpr})" : $regexExpr;

			if ($this->paramCounter !== null) {
				while ($this->paramCounter->current() < $paramIndex) {
					$this->paramCounter->next();
				}
			}

			return new CompiledQuery($sql, $params);
		}

		// Standard comparison operators
		$operator = $this->getOperator($this->operator->type);

		// Get the right-hand side value as a parameter
		$right = match ($this->right->type) {
			TokenType::String => $this->addParam($this->right->lexeme, $params, $paramIndex),
			TokenType::Number => $this->right->lexeme,
			TokenType::Boolean => $this->formatBoolean($this->right->lexeme, $dialect),
			TokenType::Null => 'NULL',
			TokenType::List => $this->compileList($this->right, $params, $paramIndex),
			default => throw new ParserOutputException(
				$this->right,
				'The right hand side in a field expression must be a literal',
			),
		};

		$sql = "{$column} {$operator} {$right}";

		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}

	/**
	 * Generate SQLite wildcard locale expression using json_each().
	 */
	private function getSqliteWildcardExpression(
		string $fieldPath,
		array &$params,
		int &$paramIndex,
	): CompiledQuery {
		$dialect = $this->context->dialect();

		// Remove the trailing .* to get the base path
		$basePath = substr($fieldPath, 0, -2);
		$operator = $this->getOperator($this->operator->type);

		// Get the right-hand side value as a parameter
		$right = match ($this->right->type) {
			TokenType::String => $this->addParam($this->right->lexeme, $params, $paramIndex),
			TokenType::Number => $this->right->lexeme,
			TokenType::Boolean => $this->formatBoolean($this->right->lexeme, $dialect),
			TokenType::Null => 'NULL',
			default => throw new ParserOutputException(
				$this->right,
				'Wildcard locale queries only support scalar values',
			),
		};

		$sql = $dialect->jsonWildcardMatch('n.content', $basePath, $operator, $right);

		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}

	/**
	 * Generate PostgreSQL jsonpath expression (original behavior).
	 */
	private function getPostgresJsonPathExpression(): CompiledQuery
	{
		[$operator, $jsonOperator, $right, $negate] = match ($this->operator->type) {
			TokenType::Equal => ['@@', '==', $this->getRight(), false],
			TokenType::Regex => ['@?', '?', $this->getRegex(false), false],
			TokenType::IRegex => ['@?', '?', $this->getRegex(true), false],
			TokenType::NotRegex => ['@?', '?', $this->getRegex(false), true],
			TokenType::INotRegex => ['@?', '?', $this->getRegex(true), true],
			TokenType::In => ['@@', 'in', $this->getRight(), false],
			TokenType::NotIn => ['@@', 'nin', $this->getRight(), false],
			default => ['@@', $this->operator->lexeme, $this->getRight(), false],
		};

		$left = $this->getFieldPath();

		$sql = sprintf(
			"%sn.content %s '$.%s %s %s'",
			$negate ? 'NOT ' : '',
			$operator,
			$left,
			$jsonOperator,
			$right,
		);

		// JSON path expressions don't use PDO parameters because values
		// are embedded in the jsonpath string. Escaping is handled by
		// quoteJsonPathString() for strings and getRegex() for patterns.
		return CompiledQuery::sql($sql);
	}

	private function getRegex(bool $ignoreCase): string
	{
		if (!($this->right->type === TokenType::String)) {
			throw new ParserOutputException(
				$this->right,
				'Only strings are allowed on the right side of a regex expressions.',
			);
		}

		$case = $ignoreCase ? ' flag "i"' : '';
		$pattern = $this->quoteJsonPathString($this->right->lexeme);

		return sprintf('(@ like_regex %s%s)', $pattern, $case);
	}

	/**
	 * Get the field path, handling locale placeholders.
	 */
	private function getFieldPath(): string
	{
		$parts = explode('.', $this->left->lexeme);

		return match (count($parts)) {
			2 => $this->compileFieldSegments($parts),
			1 => $parts[0] . '.value',
			default => $this->compileAccessor($parts),
		};
	}

	private function compileFieldSegments(array $segments): string
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

		if (str_contains($accessor, '?')) {
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

	private function getRight(): string
	{
		return match ($this->right->type) {
			TokenType::String => $this->quoteJsonPathString($this->right->lexeme),
			TokenType::Number,
			TokenType::Boolean,
			TokenType::Null => $this->right->lexeme,
			TokenType::List => $this->formatJsonPathList(),
			default => throw new ParserOutputException(
				$this->right,
				'The right hand side in a field expression must be a literal',
			),
		};
	}

	private function getSqlExpression(): CompiledQuery
	{
		$dialect = $this->context->dialect();
		$params = [];
		$paramIndex = $this->paramCounter?->current() ?? 0;

		$leftOperand = $this->getOperand($this->left, $this->builtins, $params, $paramIndex);
		$rightOperand = $this->getOperand($this->right, $this->builtins, $params, $paramIndex);

		// Handle LIKE/ILIKE with dialect
		$sql = match ($this->operator->type) {
			TokenType::Like => $dialect->like($leftOperand, $rightOperand),
			TokenType::Unlike => $dialect->unlike($leftOperand, $rightOperand),
			TokenType::ILike => $dialect->ilike($leftOperand, $rightOperand),
			TokenType::IUnlike => $dialect->iunlike($leftOperand, $rightOperand),
			default => sprintf(
				'%s %s %s',
				$leftOperand,
				$this->getOperator($this->operator->type),
				$rightOperand,
			),
		};

		// Update the shared counter if we have one
		if ($this->paramCounter !== null) {
			while ($this->paramCounter->current() < $paramIndex) {
				$this->paramCounter->next();
			}
		}

		return new CompiledQuery($sql, $params);
	}

	/**
	 * Quote a string for use in PostgreSQL jsonpath expressions.
	 *
	 * Jsonpath string literals use double quotes. Special characters that
	 * must be escaped: backslash (\) and double quote (").
	 */
	private function quoteJsonPathString(string $value): string
	{
		// Escape backslashes first, then double quotes
		$escaped = str_replace('\\', '\\\\', $value);
		$escaped = str_replace('"', '\\"', $escaped);

		return '"' . $escaped . '"';
	}

	/**
	 * Format a list for jsonpath IN expression.
	 */
	private function formatJsonPathList(): string
	{
		$items = $this->right->getListItems();
		$formatted = [];

		foreach ($items as $item) {
			if (is_numeric($item)) {
				$formatted[] = $item;
			} else {
				$formatted[] = $this->quoteJsonPathString((string) $item);
			}
		}

		return '[' . implode(', ', $formatted) . ']';
	}
}
