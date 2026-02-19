<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Schema\FieldOrder;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Name;
use Duon\Cms\Schema\Route;
use Duon\Cms\Schema\Title;
use Duon\Cms\Schema\Translate;

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
