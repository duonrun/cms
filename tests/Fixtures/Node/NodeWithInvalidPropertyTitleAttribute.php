<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Schema\Title;

class NodeWithInvalidPropertyTitleAttribute
{
	#[Title]
	protected string $heading;
}
