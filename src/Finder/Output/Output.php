<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\CompiledQuery;

interface Output
{
	public function get(): CompiledQuery;
}
