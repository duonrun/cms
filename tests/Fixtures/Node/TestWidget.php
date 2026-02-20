<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Label;

#[Label('Test Widget')]
class TestWidget implements Title
{
	#[Label('Title')]
	public Text $title;

	public function title(): string
	{
		return $this->title?->value()->unwrap() ?? 'Test Widget';
	}
}
