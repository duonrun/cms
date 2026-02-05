<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Input;

use Duon\Cms\Exception\ParserException;

readonly class Token
{
	/** @param list<Token>|null $items */
	public function __construct(
		public TokenGroup $group,
		public TokenType $type,
		public int $position,
		public string $lexeme,
		private ?int $length = null,
		public ?array $items = null,
	) {}

	public static function fromList(
		TokenGroup $group,
		TokenType $type,
		int $position,
		/** @param array<Token> */
		array $list,
		int $length,
	): self {
		self::validateList($list);

		return new self($group, $type, $position, '', $length, $list);
	}

	public function len(): int
	{
		return $this->length ?: strlen($this->lexeme);
	}

	/** @param $list array<Token> */
	private static function validateList(array $list): void
	{
		$type = null;

		foreach ($list as $item) {
			if ($type === null) {
				$type = $item->type;
			} else {
				if ($type !== $item->type) {
					throw new ParserException('Invalid query: mixed list item types');
				}
			}

			if ($type !== TokenType::String && $type !== TokenType::Number) {
				throw new ParserException('Invalid query: token type not supported in list');
			}
		}
	}
}
