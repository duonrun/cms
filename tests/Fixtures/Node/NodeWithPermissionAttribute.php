<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Permission;

#[Permission([
	'read' => 'me',
])]
class NodeWithPermissionAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with permission';
	}
}
