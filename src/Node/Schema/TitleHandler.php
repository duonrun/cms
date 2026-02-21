<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Schema;

class TitleHandler extends Handler
{
	public function resolve(object $meta, string $nodeClass): array
	{
		return ['titleField' => $meta->field];
	}
}
