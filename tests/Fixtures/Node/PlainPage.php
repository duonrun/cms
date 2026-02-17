<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\FieldOrder;
use Duon\Cms\Node\Meta\Name;
use Duon\Cms\Node\Meta\Page;
use Duon\Cms\Node\Meta\Route;
use Duon\Cms\Node\Meta\Title;

#[Page]
#[Name('Plain Page')]
#[Route('/plain-page/{uid}')]
#[Title('heading')]
#[FieldOrder('heading', 'body')]
class PlainPage implements HasTitle
{
	#[Label('Heading')]
	#[Translate]
	protected Text $heading;

	#[Label('Body')]
	#[Translate]
	protected Text $body;

	public function title(): string
	{
		return $this->heading?->value()->unwrap() ?? 'Untitled';
	}
}
