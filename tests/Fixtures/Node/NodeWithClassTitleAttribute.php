<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Schema\Title;

#[Title('heading')]
class NodeWithClassTitleAttribute
{
	protected Text $heading;
}
