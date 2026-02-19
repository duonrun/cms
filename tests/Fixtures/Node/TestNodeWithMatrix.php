<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Name;
use Duon\Cms\Schema\Required;
use Duon\Cms\Schema\Translate;

#[Name('Test Node With Matrix')]
class TestNodeWithMatrix implements HasTitle
{
	#[Label('Titel'), Required, Translate]
	protected Text $title;

	#[Label('My Matrix Field'), Required]
	protected TestMatrix $matrix;

	public function title(): string
	{
		return strip_tags($this->title->value()->unwrap());
	}
}
