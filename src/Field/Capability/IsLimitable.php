<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Capability;

use Duon\Cms\Exception\RuntimeException;

trait IsLimitable
{
	protected int $limitMin = 0;
	protected int $limitMax = 999;

	public function limit(int $max, int $min = 0): static
	{
		if ($max < 1) {
			throw new RuntimeException('Limit max must be >= 1');
		}

		if ($min < 0) {
			throw new RuntimeException('Limit min must be >= 0');
		}

		if ($min > $max) {
			throw new RuntimeException('Limit min must be <= max');
		}

		$this->limitMax = $max;
		$this->limitMin = $min;

		return $this;
	}

	public function getLimitMin(): int
	{
		return $this->limitMin;
	}

	public function getLimitMax(): int
	{
		return $this->limitMax;
	}
}
