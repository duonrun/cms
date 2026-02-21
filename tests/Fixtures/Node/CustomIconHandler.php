<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Schema\Handler;

class CustomIconHandler extends Handler
{
	public function resolve(object $meta, string $nodeClass): array
	{
		return ['icon' => $meta->value];
	}
}
