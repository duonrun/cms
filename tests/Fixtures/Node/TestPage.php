<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Route;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Translate;

#[Name('Test Page')]
#[Route('/test/{uid}')]
class TestPage implements HasTitle
{
	#[Label('Title')]
	#[Translate]
	public Text $title;

	public function title(): string
	{
		return $this->title?->value()->unwrap() ?? 'Test Page';
	}
}
