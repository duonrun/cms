<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Input;

use Duon\Cms\Exception\ParserException;
use Duon\Quma\Database;

readonly class Token
{
	public function __construct(
		public TokenGroup $group,
		public TokenType $type,
		public int $position,
		public string $lexeme,
		private ?int $length = null,
	) {}

	public static function fromList(
		TokenGroup $group,
		TokenType $type,
		int $position,
		/** @param array<Token> */
		array $list,
		int $length,
		Database $db,
	): self {
		return new self($group, $type, $position, self::transformList($list, $db), $length);
	}

	public function len(): int
	{
		return $this->length ?: strlen($this->lexeme);
	}

	/** @param $list array<Token> */
	private static function transformList(array $list, Database $db): string
	{
		$result = [];
		$type = null;

		foreach ($list as $item) {
			if ($type === null) {
				$type = $item->type;
			} else {
				if ($type !== $item->type) {
					throw new ParserException('Invalid query: mixed list item types');
				}
			}

			if ($type === TokenType::String || $type === TokenType::Number) {
				$result[] = $db->quote($item->lexeme);
			} else {
				throw new ParserException('Invalid query: token type not supported in list');
			}
		}

		return '(' . implode(', ', $result) . ')';
	}
}
