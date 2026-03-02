<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Children;

#[Children(PlainPage::class, PlainBlock::class)]
class NodeWithChildrenAttribute implements Title
{
	public function title(): string
	{
		return 'children';
	}
}
