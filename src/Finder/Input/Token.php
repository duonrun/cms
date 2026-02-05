<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Input;

use Duon\Cms\Exception\ParserException;

readonly class Token
{
	/**
	 * @param array<string|int|float>|null $listItems List items for TokenType::List
	 */
	public function __construct(
		public TokenGroup $group,
		public TokenType $type,
		public int $position,
		public string $lexeme,
		private ?int $length = null,
		private ?array $listItems = null,
	) {}

	/**
	 * Create a token from a list of item tokens.
	 *
	 * @param array<Token> $list
	 */
	public static function fromList(
		TokenGroup $group,
		TokenType $type,
		int $position,
		array $list,
		int $length,
	): self {
		$items = self::extractListItems($list);

		return new self(
			$group,
			$type,
			$position,
			self::formatLexeme($items),
			$length,
			$items,
		);
	}

	public function len(): int
	{
		return $this->length ?: strlen($this->lexeme);
	}

	/**
	 * Get the list items for a List token.
	 *
	 * @return array<string|int|float>
	 */
	public function getListItems(): array
	{
		if ($this->type !== TokenType::List) {
			throw new ParserException('getListItems() can only be called on List tokens');
		}

		return $this->listItems ?? [];
	}

	/**
	 * Extract values from list item tokens.
	 *
	 * @param array<Token> $list
	 * @return array<string|int|float>
	 */
	private static function extractListItems(array $list): array
	{
		$result = [];
		$type = null;

		foreach ($list as $item) {
			if ($type === null) {
				$type = $item->type;
			} elseif ($type !== $item->type) {
				throw new ParserException('Invalid query: mixed list item types');
			}

			if ($type === TokenType::String) {
				$result[] = $item->lexeme;
			} elseif ($type === TokenType::Number) {
				// Preserve as string but mark it as numeric
				$result[] = $item->lexeme;
			} else {
				throw new ParserException('Invalid query: token type not supported in list');
			}
		}

		return $result;
	}

	/**
	 * Format list items as a human-readable lexeme.
	 *
	 * @param array<string|int|float> $items
	 */
	private static function formatLexeme(array $items): string
	{
		return '[' . implode(', ', $items) . ']';
	}
}
