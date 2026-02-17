<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Required;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Name;

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
