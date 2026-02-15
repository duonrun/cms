<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Page;

#[Page]
#[Name('Node With Custom Name Attribute')]
class NodeWithNameAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with name';
	}
}
