<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

/**
 * Counter for generating unique parameter names across a compiled query.
 */
final class ParamCounter
{
	private int $index = 0;

	public function next(): int
	{
		return $this->index++;
	}

	public function current(): int
	{
		return $this->index;
	}

	public function reset(): void
	{
		$this->index = 0;
	}
}
