<?php

declare(strict_types=1);

namespace Duon\Cms\Db;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserException;
use Duon\Cms\Exception\ParserOutputException;
use Duon\Cms\Finder\Condition\Comparison;
use Duon\Cms\Finder\Condition\Exists;
use Duon\Cms\Finder\Condition\Part;
use Duon\Cms\Finder\Condition\TokenPart;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;

final class SqliteDialect implements Dialect
{
	public function table(string $name): string
	{
		return 'cms.' . $name;
	}

	public function compileField(string $fieldName, string $tableField, bool $asJson = false): string
	{
		$parts = explode('.', $fieldName);

		foreach ($parts as $part) {
			if ($part === '') {
				throw new ParserException('Invalid field name');
			}
		}

		$path = count($parts) === 1
			? '$.' . $parts[0] . '.value'
			: '$.' . implode('.', $parts);

		return "json_extract({$tableField}, '{$path}')";
	}

	public function compileConditionPart(Part $part, Context $context, array $builtins): string
	{
		return match (true) {
			$part instanceof Comparison => $this->compileComparison($part, $context, $builtins),
			$part instanceof Exists => $this->compileExists($part, $context),
			$part instanceof TokenPart => $part->sql,
			default => throw new ParserException('Unsupported condition part'),
		};
	}

	public function compileSearchMatch(string $expression, string $needle): string
	{
		return "LOWER(COALESCE(CAST({$expression} AS TEXT), '')) LIKE LOWER({$needle})";
	}

	public function keyword(string $keyword): string
	{
		return match ($keyword) {
			'now' => "datetime('now')",
			default => throw new ParserException('Unknown keyword: ' . $keyword),
		};
	}

	public function sqlOperator(TokenType $type): string
	{
		return match ($type) {
			TokenType::Equal => '=',
			TokenType::Greater => '>',
			TokenType::GreaterEqual => '>=',
			TokenType::Less => '<',
			TokenType::LessEqual => '<=',
			TokenType::Like,
			TokenType::ILike => 'LIKE',
			TokenType::Unequal => '!=',
			TokenType::Unlike,
			TokenType::IUnlike => 'NOT LIKE',
			TokenType::In => 'IN',
			TokenType::NotIn => 'NOT IN',
			default => throw new ParserException('Invalid expression operator: ' . $type->name),
		};
	}

	private function compileComparison(Comparison $part, Context $context, array $builtins): string
	{
		if (in_array($part->operator->type, [
			TokenType::Regex,
			TokenType::IRegex,
			TokenType::NotRegex,
			TokenType::INotRegex,
		], true)) {
			throw new ParserOutputException($part->operator, 'Regex operators are not supported for SQLite queries.');
		}

		if ($part->left->type === TokenType::Path || $part->right->type === TokenType::Path) {
			return $this->compilePathComparison($part, $context);
		}

		if ($part->right->type === TokenType::Null) {
			return $this->compileNullComparison($part, $context, $builtins);
		}

		return sprintf(
			'%s %s %s',
			$this->operand($part->left, $context, $builtins),
			$this->sqlOperator($part->operator->type),
			$this->operand($part->right, $context, $builtins),
		);
	}

	private function compileExists(Exists $part, Context $context): string
	{
		if ($part->field->lexeme === '') {
			throw new ParserOutputException($part->field, 'Invalid field name in exists condition.');
		}

		return sprintf(
			"json_type(n.content, '%s') IS NOT NULL",
			'$.' . $this->fieldPath($part->field->lexeme, $part->field, $context),
		);
	}

	private function compileNullComparison(Comparison $part, Context $context, array $builtins): string
	{
		return match ($part->operator->type) {
			TokenType::Equal => sprintf(
				'%s IS %s',
				$this->operand($part->left, $context, $builtins),
				$this->operand($part->right, $context, $builtins),
			),
			TokenType::Unequal => sprintf(
				'%s IS NOT %s',
				$this->operand($part->left, $context, $builtins),
				$this->operand($part->right, $context, $builtins),
			),
			default => throw new ParserOutputException(
				$part->operator,
				'Only equal (=) or unequal (!=) operators are allowed in queries with an null value.',
			),
		};
	}

	private function operand(Token $token, Context $context, array $builtins): string
	{
		return match ($token->type) {
			TokenType::Boolean => strtolower($token->lexeme),
			TokenType::Field => $this->compileField($token->lexeme, 'n.content'),
			TokenType::Builtin => $builtins[$token->lexeme],
			TokenType::Keyword => $this->keyword($token->lexeme),
			TokenType::Null => 'NULL',
			TokenType::Number => $token->lexeme,
			TokenType::String => $context->db->quote($token->lexeme),
			TokenType::List => $token->lexeme,
			default => throw new ParserOutputException($token, 'Unsupported operand type.'),
		};
	}

	private function fieldPath(string $field, Token $token, Context $context): string
	{
		$parts = explode('.', $field);

		foreach ($parts as $part) {
			if ($part === '') {
				throw new ParserOutputException($token, 'Invalid field name in exists condition.');
			}
		}

		if (count($parts) === 1) {
			return $parts[0] . '.value';
		}

		if (count($parts) === 2 && $parts[1] === '?') {
			return $parts[0] . '.value.' . $context->localeId();
		}

		if (count($parts) > 2 && in_array('?', $parts, true)) {
			throw new ParserOutputException($token, 'The questionmark is allowed after the first dot only.');
		}

		if (count($parts) === 2 && $parts[1] === '*') {
			return $parts[0] . '.value';
		}

		return implode('.', $parts);
	}

	private function compilePathComparison(Comparison $part, Context $context): string
	{
		[$pathToken, $valueToken, $operator] = $this->normalizePathComparison($part);
		[$localeClause, $isNegated, $condition] = $this->pathCondition($pathToken, $valueToken, $operator, $context);

		return sprintf(
			'%sEXISTS (SELECT 1 FROM %s p WHERE p.node = n.node AND p.inactive IS NULL%s AND %s)',
			$isNegated ? 'NOT ' : '',
			$this->table('urlpaths'),
			$localeClause,
			$condition,
		);
	}

	/** @return array{0: Token, 1: Token, 2: TokenType} */
	private function normalizePathComparison(Comparison $part): array
	{
		if ($part->left->type === TokenType::Path) {
			return [$part->left, $part->right, $part->operator->type];
		}

		if ($part->right->type !== TokenType::Path) {
			throw new ParserOutputException($part->left, 'A path expression requires a path operand.');
		}

		return [$part->right, $part->left, $this->reversePathOperator($part->operator->type)];
	}

	private function reversePathOperator(TokenType $type): TokenType
	{
		return match ($type) {
			TokenType::Greater => TokenType::Less,
			TokenType::GreaterEqual => TokenType::LessEqual,
			TokenType::Less => TokenType::Greater,
			TokenType::LessEqual => TokenType::GreaterEqual,
			default => $type,
		};
	}

	/** @return array{0: string, 1: bool, 2: string} */
	private function pathCondition(Token $pathToken, Token $valueToken, TokenType $operator, Context $context): array
	{
		$localeClause = $this->pathLocaleClause($pathToken, $context);

		return match ($operator) {
			TokenType::Equal => [$localeClause, false, $this->pathIsComparison($valueToken, true, $context)],
			TokenType::Unequal => [$localeClause, true, $this->pathIsComparison($valueToken, true, $context)],
			TokenType::Like,
			TokenType::ILike => [$localeClause, false, $this->pathScalarComparison($valueToken, 'LIKE', $context)],
			TokenType::Unlike,
			TokenType::IUnlike => [$localeClause, true, $this->pathScalarComparison($valueToken, 'LIKE', $context)],
			TokenType::In => [$localeClause, false, $this->pathScalarComparison($valueToken, 'IN', $context)],
			TokenType::NotIn => [$localeClause, true, $this->pathScalarComparison($valueToken, 'IN', $context)],
			TokenType::Greater => [$localeClause, false, $this->pathScalarComparison($valueToken, '>', $context)],
			TokenType::GreaterEqual => [$localeClause, false, $this->pathScalarComparison($valueToken, '>=', $context)],
			TokenType::Less => [$localeClause, false, $this->pathScalarComparison($valueToken, '<', $context)],
			TokenType::LessEqual => [$localeClause, false, $this->pathScalarComparison($valueToken, '<=', $context)],
			default => throw new ParserOutputException($valueToken, 'Operator not supported for SQLite path expressions.'),
		};
	}

	private function pathIsComparison(Token $valueToken, bool $allowNull, Context $context): string
	{
		if ($valueToken->type === TokenType::Null) {
			if (!$allowNull) {
				throw new ParserOutputException($valueToken, 'NULL is not supported for this path comparison.');
			}

			return 'p.path IS NULL';
		}

		return 'p.path = ' . $this->pathLiteral($valueToken, $context);
	}

	private function pathScalarComparison(Token $valueToken, string $operator, Context $context): string
	{
		if ($valueToken->type === TokenType::Null) {
			throw new ParserOutputException($valueToken, 'NULL is only supported with = or != for path expressions.');
		}

		return 'p.path ' . $operator . ' ' . $this->pathLiteral($valueToken, $context);
	}

	private function pathLiteral(Token $token, Context $context): string
	{
		return match ($token->type) {
			TokenType::String => $context->db->quote($token->lexeme),
			TokenType::Number,
			TokenType::Boolean => $context->db->quote($token->lexeme),
			TokenType::List => $token->lexeme,
			default => throw new ParserOutputException($token, 'Path comparisons only support literal values.'),
		};
	}

	private function pathLocaleClause(Token $pathToken, Context $context): string
	{
		$parts = explode('.', $pathToken->lexeme);

		if (count($parts) === 1 || (count($parts) === 2 && $parts[1] === '*')) {
			return '';
		}

		if (count($parts) !== 2) {
			throw new ParserOutputException($pathToken, 'Invalid path selector. Use path, path.?, path.*, or path.<locale>.');
		}

		$selector = $parts[1] === '?' ? $context->localeId() : $parts[1];

		if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $selector) !== 1) {
			throw new ParserOutputException($pathToken, 'Invalid locale in path selector.');
		}

		return ' AND p.locale = ' . $context->db->quote($selector);
	}
}
