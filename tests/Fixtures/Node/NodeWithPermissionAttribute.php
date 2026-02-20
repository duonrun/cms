<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Permission;

#[Permission([
	'read' => 'me',
])]
class NodeWithPermissionAttribute implements Title
{
	public function title(): string
	{
		return 'with permission';
	}
}
