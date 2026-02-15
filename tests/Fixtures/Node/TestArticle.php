<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Text;
use Duon\Cms\Field\Textarea;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Page;

#[Page]
#[Name('Test Article')]
class TestArticle implements HasTitle
{
	#[Label('Title')]
	#[Translate]
	public Text $title;

	#[Label('Content')]
	#[Translate]
	public Textarea $content;

	public function title(): string
	{
		return $this->title?->value()->unwrap() ?? 'Test Article';
	}
}
