<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Number;
use Duon\Cms\Schema\Title;

#[Title('count')]
class NodeWithNumericTitleField
{
	public Number $count;
}
