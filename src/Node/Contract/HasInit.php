<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Contract;

interface HasInit
{
	public function init(): void;
}
