<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\CompilesField;
use Duon\Cms\Finder\Input\Token;
use Duon\Cms\Finder\Input\TokenType;
use Duon\Cms\Finder\QueryParams;

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

	protected function getOperand(Token $token, QueryParams $params, array $builtins): string
	{
		return match ($token->type) {
			TokenType::Boolean => $params->add(strtolower($token->lexeme) === 'true'),
			TokenType::Field => $this->compileField($token->lexeme, 'n.content'),
			TokenType::Builtin => $builtins[$token->lexeme],
			TokenType::Keyword => $this->translateKeyword($token->lexeme),
			TokenType::Null => 'NULL',
			TokenType::Number => $params->add($token->lexeme),
			TokenType::String => $params->add($token->lexeme),
			TokenType::List => $this->compileList($token, $params),
		};
	}

	private function compileList(Token $token, QueryParams $params): string
	{
		$items = $token->items;

		if ($items === null || $items === []) {
			throw new ParserException('Invalid query: empty list');
		}

		$itemType = $items[0]->type;
		$placeholders = [];

		foreach ($items as $item) {
			if ($item->type !== $itemType) {
				throw new ParserException('Invalid query: mixed list item types');
			}

			$placeholders[] = match ($item->type) {
				TokenType::String => $params->add($item->lexeme),
				TokenType::Number => $params->add($item->lexeme),
				default => throw new ParserException(
					'Invalid query: token type not supported in list',
				),
			};
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
