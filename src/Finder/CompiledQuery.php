<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

/** @psalm-immutable */
final readonly class CompiledQuery
{
	/** @param array<string, scalar|null> $params */
	public function __construct(
		public string $sql,
		public array $params,
	) {}
}
