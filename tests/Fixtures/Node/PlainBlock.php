<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Schema\Deletable;
use Duon\Cms\Schema\Label;

#[Label('Plain Block')]
#[Deletable(false)]
class PlainBlock
{
	#[Label('Content')]
	protected Text $content;
}
