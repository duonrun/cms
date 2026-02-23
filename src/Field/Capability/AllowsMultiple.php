<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Capability;

interface AllowsMultiple
{
	public function multiple(bool $multiple = true): static;

	public function getMultiple(): bool;
}
