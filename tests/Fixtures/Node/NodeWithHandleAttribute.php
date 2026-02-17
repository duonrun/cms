<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Handle;

#[Handle('node-with-custom-handle-attribute')]
class NodeWithHandleAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with handle';
	}
}
