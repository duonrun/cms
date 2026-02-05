<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Output;

use Duon\Cms\Finder\QueryParams;

interface Output
{
	public function get(QueryParams $params): string;
}
