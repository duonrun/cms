<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Schema\Label;

#[Label('Node With Custom Name Attribute')]
class NodeWithNameAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with name';
	}
}
