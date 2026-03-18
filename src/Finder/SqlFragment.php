<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

final readonly class SqlFragment
{
	/** @param array<string, scalar|null> $params */
	public function __construct(
		public string $sql = '',
		public array $params = [],
	) {}

	public static function empty(): self
	{
		return new self();
	}

	public function isEmpty(): bool
	{
		return trim($this->sql) === '';
	}

	public function and(self $other): self
	{
		if ($this->isEmpty()) {
			return $other;
		}

		if ($other->isEmpty()) {
			return $this;
		}

		return new self(
			"({$this->sql}) AND ({$other->sql})",
			[...$this->params, ...$other->params],
		);
	}
}
