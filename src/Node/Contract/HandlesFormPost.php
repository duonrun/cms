<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Contract;

use Duon\Core\Response;

interface HandlesFormPost
{
	public function formPost(?array $body): Response;
}
