<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasInit;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Page;

#[Page]
#[Name('Plain Page With Init')]
class PlainPageWithInit implements HasInit
{
	#[Label('Title')]
	protected Text $title;

	public bool $initialized = false;

	public function init(): void
	{
		$this->initialized = true;
	}

	public function title(): string
	{
		return $this->title?->value()->unwrap() ?? '';
	}
}
