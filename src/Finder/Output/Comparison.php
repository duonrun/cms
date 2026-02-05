<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Context;
use Duon\Cms\Exception\ParserOutputException;
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
		private array $builtins,
	) {}

	public function get(QueryParams $params): string
	{
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

			return $this->getJsonPathExpression($params);
		}

		if ($this->left->type === TokenType::Builtin) {
			return $this->getSqlExpression($params);
		}

		throw new ParserOutputException(
			$this->left,
			'Only fields or `path` are allowed on the left side of an expression.',
		);
	}

	private function getJsonPathExpression(QueryParams $params): string
	{
		[$operator, $jsonOperator, $right, $negate] = match ($this->operator->type) {
			TokenType::Equal => ['@@', '==', $this->getJsonPathRight(), false],
			TokenType::Regex => ['@?', '?', $this->getRegex(false), false],
			TokenType::IRegex => ['@?', '?', $this->getRegex(true), false],
			TokenType::NotRegex => ['@?', '?', $this->getRegex(false), true],
			TokenType::INotRegex => ['@?', '?', $this->getRegex(true), true],
			TokenType::In => ['@@', 'in', $this->getJsonPathRight(), false],
			TokenType::NotIn => ['@@', 'nin', $this->getJsonPathRight(), false],
			default => ['@@', $this->operator->lexeme, $this->getJsonPathRight(), false],
		};

		$left = $this->getField();
		$path = sprintf('$.%s %s %s', $left, $jsonOperator, $right);
		$placeholder = $params->add($path);

		return sprintf(
			'%sn.content %s %s',
			$negate ? 'NOT ' : '',
			$operator,
			$placeholder,
		);
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

		return sprintf(
			'(@ like_regex %s%s)',
			$this->jsonPathString($this->right->lexeme),
			$case,
		);
	}

	private function getField(): string
	{
		$parts = explode('.', $this->left->lexeme);

		return match (count($parts)) {
			2 => $this->compileField($parts),
			1 => $parts[0] . '.value',
			default => $this->compileAccessor($parts),
		};
	}

	private function compileField(array $segments): string
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

	private function getJsonPathRight(): string
	{
		return match ($this->right->type) {
			TokenType::String => $this->jsonPathString($this->right->lexeme),
			TokenType::Number => $this->right->lexeme,
			TokenType::Boolean => strtolower($this->right->lexeme),
			TokenType::List => $this->jsonPathList($this->right),
			TokenType::Null => 'null',
			default => throw new ParserOutputException(
				$this->right,
				'The right hand side in a field expression must be a literal',
			),
		};
	}

	private function getSqlExpression(QueryParams $params): string
	{
		return sprintf(
			'%s %s %s',
			$this->getOperand($this->left, $params, $this->builtins),
			$this->getOperator($this->operator->type),
			$this->getOperand($this->right, $params, $this->builtins),
		);
	}

	private function jsonPathString(string $string): string
	{
		$encoded = json_encode($string, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($encoded === false) {
			return '""';
		}

		return $encoded;
	}

	private function jsonPathList(Token $token): string
	{
		$items = $token->items;

		if ($items === null || $items === []) {
			throw new ParserOutputException($token, 'Invalid query: empty list');
		}

		$itemType = $items[0]->type;
		$values = [];

		foreach ($items as $item) {
			if ($item->type !== $itemType) {
				throw new ParserOutputException($token, 'Invalid query: mixed list item types');
			}

			$values[] = match ($item->type) {
				TokenType::String => $this->jsonPathString($item->lexeme),
				TokenType::Number => $item->lexeme,
				default => throw new ParserOutputException(
					$token,
					'Invalid query: token type not supported in list',
				),
			};
		}

		return '(' . implode(', ', $values) . ')';
	}
}
