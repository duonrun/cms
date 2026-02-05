<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\CompiledQuery;
use Duon\Cms\Finder\CompilesField;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;

abstract readonly class Expression
{
	use CompilesField;

	protected function getOperator(TokenType $type): string
	{
		return match ($type) {
			TokenType::LeftParen => '(',
			TokenType::RightParen => ')',
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
			TokenType::And => 'AND',
			TokenType::Or => 'OR',
			TokenType::In => 'IN',
			TokenType::NotIn => 'NOT IN',
			default => throw new ParserException('Invalid expression operator: ' . $type->name),
		};
	}

	/**
	 * Get the SQL operand and any parameters for a token.
	 *
	 * @param array<string, scalar|null> $params Reference to params array to add to
	 * @param int $paramIndex Reference to param counter for unique names
	 * @return string SQL fragment (may contain parameter placeholders)
	 */
	protected function getOperand(
		Token $token,
		array $builtins,
		array &$params,
		int &$paramIndex,
	): string {
		return match ($token->type) {
			TokenType::Boolean => strtolower($token->lexeme),
			TokenType::Field => $this->compileField($token->lexeme, 'n.content'),
			TokenType::Builtin => $builtins[$token->lexeme],
			TokenType::Keyword => $this->translateKeyword($token->lexeme),
			TokenType::Null => 'NULL',
			TokenType::Number => $token->lexeme,
			TokenType::String => $this->addParam($token->lexeme, $params, $paramIndex),
			TokenType::List => $this->compileList($token, $params, $paramIndex),
		};
	}

	/**
	 * Add a parameter and return its placeholder name.
	 */
	protected function addParam(
		string|int|float|bool|null $value,
		array &$params,
		int &$paramIndex,
	): string {
		$name = 'p' . $paramIndex++;
		$params[$name] = $value;

		return ':' . $name;
	}

	/**
	 * Compile a list token into IN (...) format with parameters.
	 */
	protected function compileList(Token $token, array &$params, int &$paramIndex): string
	{
		// The token lexeme contains the raw list items separated by comma
		// We need to parse and parameterize each item
		$items = $token->getListItems();
		$placeholders = [];

		foreach ($items as $item) {
			$placeholders[] = $this->addParam($item, $params, $paramIndex);
		}

		return '(' . implode(', ', $placeholders) . ')';
	}

	protected function translateKeyword(string $keyword): string
	{
		return match ($keyword) {
			'now' => 'NOW()',
		};
	}
}
