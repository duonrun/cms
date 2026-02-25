<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Schema\Title;

class NodeWithPropertyTitleAttribute
{
	#[Title]
	protected Text $heading;

	protected Text $body;
}
