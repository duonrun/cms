<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Schema;

class PermissionHandler extends Handler
{
	public function resolve(object $meta, string $nodeClass): array
	{
		return ['permission' => $meta->value];
	}
}
