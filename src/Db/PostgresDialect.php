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

final class PostgresDialect implements Dialect
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

		$count = count($parts);
		$arrow = $asJson ? '->' : '->>';

		if ($count === 1) {
			return "{$tableField}->'{$parts[0]}'{$arrow}'value'";
		}

		$middle = implode("'->'", array_slice($parts, 0, $count - 1));
		$end = array_slice($parts, -1)[0];

		return "{$tableField}->'{$middle}'{$arrow}'{$end}'";
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
		return "COALESCE(({$expression})::text, '') ILIKE {$needle}";
	}

	public function keyword(string $keyword): string
	{
		return match ($keyword) {
			'now' => 'NOW()',
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
			TokenType::Like => 'LIKE',
			TokenType::ILike => 'ILIKE',
			TokenType::Unequal => '!=',
			TokenType::Unlike => 'NOT LIKE',
			TokenType::IUnlike => 'NOT ILIKE',
			TokenType::In => 'IN',
			TokenType::NotIn => 'NOT IN',
			default => throw new ParserException('Invalid expression operator: ' . $type->name),
		};
	}

	private function compileComparison(Comparison $part, Context $context, array $builtins): string
	{
		if ($part->right->type === TokenType::Null) {
			return $this->compileNullComparison($part, $context, $builtins);
		}

		if ($part->left->type === TokenType::Path || $part->right->type === TokenType::Path) {
			return $this->compilePathComparison($part, $context);
		}

		switch ($part->operator->type) {
			case TokenType::Like:
			case TokenType::Unlike:
			case TokenType::ILike:
			case TokenType::IUnlike:
			case TokenType::In:
			case TokenType::NotIn:
				return $this->compileSqlComparison($part, $context, $builtins);
		}

		if ($part->left->type === TokenType::Field) {
			if ($part->right->type === TokenType::Builtin || $part->right->type === TokenType::Field) {
				return $this->compileSqlComparison($part, $context, $builtins);
			}

			return $this->compileJsonPathComparison($part, $context);
		}

		if ($part->left->type === TokenType::Builtin) {
			return $this->compileSqlComparison($part, $context, $builtins);
		}

		throw new ParserOutputException(
			$part->left,
			'Only fields or `path` are allowed on the left side of an expression.',
		);
	}

	private function compileExists(Exists $part, Context $context): string
	{
		if ($part->field->lexeme === '') {
			throw new ParserOutputException($part->field, 'Invalid field name in exists condition.');
		}

		return 'n.content @? '
			. $context->db->quote(
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

	private function compileJsonPathComparison(Comparison $part, Context $context): string
	{
		[$operator, $jsonOperator, $right, $negate] = match ($part->operator->type) {
			TokenType::Equal => ['@@', '==', $this->jsonLiteral($part->right, $context), false],
			TokenType::Regex => ['@?', '?', $this->regexLiteral($part->right, $context, false), false],
			TokenType::IRegex => ['@?', '?', $this->regexLiteral($part->right, $context, true), false],
			TokenType::NotRegex => ['@?', '?', $this->regexLiteral($part->right, $context, false), true],
			TokenType::INotRegex => ['@?', '?', $this->regexLiteral($part->right, $context, true), true],
			TokenType::In => ['@@', 'in', $this->jsonLiteral($part->right, $context), false],
			TokenType::NotIn => ['@@', 'nin', $this->jsonLiteral($part->right, $context), false],
			default => ['@@', $part->operator->lexeme, $this->jsonLiteral($part->right, $context), false],
		};

		return sprintf(
			"%sn.content %s '$.%s %s %s'",
			$negate ? 'NOT ' : '',
			$operator,
			$this->jsonField($part->left, $context),
			$jsonOperator,
			$right,
		);
	}

	private function compileSqlComparison(Comparison $part, Context $context, array $builtins): string
	{
		return sprintf(
			'%s %s %s',
			$this->operand($part->left, $context, $builtins),
			$this->sqlOperator($part->operator->type),
			$this->operand($part->right, $context, $builtins),
		);
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

	private function jsonField(Token $token, Context $context): string
	{
		$parts = explode('.', $token->lexeme);

		return match (count($parts)) {
			2 => $this->compileJsonField($parts, $context),
			1 => $parts[0] . '.value',
			default => $this->compileJsonAccessor($parts, $token),
		};
	}

	private function compileJsonField(array $segments, Context $context): string
	{
		return match ($segments[1]) {
			'*' => $segments[0] . '.value.*',
			'?' => $segments[0] . '.value.' . $context->localeId(),
			default => implode('.', $segments),
		};
	}

	private function compileJsonAccessor(array $segments, Token $token): string
	{
		$accessor = implode('.', $segments);

		if (strpos($accessor, '?') !== false) {
			throw new ParserOutputException(
				$token,
				'The questionmark is allowed after the first dot only.',
			);
		}

		return $accessor;
	}

	private function jsonLiteral(Token $token, Context $context): string
	{
		return match ($token->type) {
			TokenType::String => $this->quoteJsonString($token->lexeme, $context),
			TokenType::Number,
			TokenType::Boolean,
			TokenType::List,
			TokenType::Null => $token->lexeme,
			default => throw new ParserOutputException(
				$token,
				'The right hand side in a field expression must be a literal',
			),
		};
	}

	private function quoteJsonString(string $string, Context $context): string
	{
		return sprintf(
			'"%s"',
			preg_replace(
				'/(?<!\\\\)(")/',
				'\\"',
				trim($context->db->quote($string), "'"),
			),
		);
	}

	private function regexLiteral(Token $token, Context $context, bool $ignoreCase): string
	{
		if ($token->type !== TokenType::String) {
			throw new ParserOutputException(
				$token,
				'Only strings are allowed on the right side of a regex expressions.',
			);
		}

		$case = $ignoreCase ? ' flag "i"' : '';
		$pattern = '"' . trim($context->db->quote($token->lexeme), "'") . '"';

		return sprintf('(@ like_regex %s%s)', $pattern, $case);
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
			return $parts[0] . '.value.*';
		}

		return implode('.', $parts);
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
			TokenType::Like => [$localeClause, false, $this->pathScalarComparison($valueToken, 'LIKE', $context)],
			TokenType::Unlike => [$localeClause, true, $this->pathScalarComparison($valueToken, 'LIKE', $context)],
			TokenType::ILike => [$localeClause, false, $this->pathScalarComparison($valueToken, 'ILIKE', $context)],
			TokenType::IUnlike => [$localeClause, true, $this->pathScalarComparison($valueToken, 'ILIKE', $context)],
			TokenType::Regex => [$localeClause, false, $this->pathScalarComparison($valueToken, '~', $context)],
			TokenType::NotRegex => [$localeClause, true, $this->pathScalarComparison($valueToken, '~', $context)],
			TokenType::IRegex => [$localeClause, false, $this->pathScalarComparison($valueToken, '~*', $context)],
			TokenType::INotRegex => [$localeClause, true, $this->pathScalarComparison($valueToken, '~*', $context)],
			TokenType::In => [$localeClause, false, $this->pathScalarComparison($valueToken, 'IN', $context)],
			TokenType::NotIn => [$localeClause, true, $this->pathScalarComparison($valueToken, 'IN', $context)],
			TokenType::Greater => [$localeClause, false, $this->pathScalarComparison($valueToken, '>', $context)],
			TokenType::GreaterEqual => [$localeClause, false, $this->pathScalarComparison($valueToken, '>=', $context)],
			TokenType::Less => [$localeClause, false, $this->pathScalarComparison($valueToken, '<', $context)],
			TokenType::LessEqual => [$localeClause, false, $this->pathScalarComparison($valueToken, '<=', $context)],
			default => throw new ParserOutputException($valueToken, 'Operator not supported for path expressions.'),
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
