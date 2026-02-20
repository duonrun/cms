<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Handle;

#[Handle('node-with-custom-handle-attribute')]
class NodeWithHandleAttribute implements Title
{
	public function title(): string
	{
		return 'with handle';
	}
}
