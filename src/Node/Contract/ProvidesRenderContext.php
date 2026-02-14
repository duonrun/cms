<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Contract;

interface ProvidesRenderContext
{
	public function renderContext(): array;
}
