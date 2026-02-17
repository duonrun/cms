<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasInit;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Route;

#[Name('Plain Page With Init')]
#[Route('/plain-page-with-init/{uid}')]
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
