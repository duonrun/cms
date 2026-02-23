<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Capability;

use Duon\Cms\Exception\RuntimeException;

trait IsLimitable
{
	protected ?int $limitMin = null;
	protected ?int $limitMax = null;

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
		return $this->limitMin ?? $this->defaultLimitMin();
	}

	public function getLimitMax(): int
	{
		return $this->limitMax ?? $this->defaultLimitMax();
	}

	protected function defaultLimitMin(): int
	{
		return 0;
	}

	protected function defaultLimitMax(): int
	{
		return 1;
	}
}
