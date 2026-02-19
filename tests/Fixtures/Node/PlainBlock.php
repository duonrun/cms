<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Node\Meta\Deletable;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Schema\Label;

#[Name('Plain Block')]
#[Deletable(false)]
class PlainBlock
{
	#[Label('Content')]
	protected Text $content;
}
