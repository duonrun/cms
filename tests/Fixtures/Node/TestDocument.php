<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Meta\DefaultValue;
use Duon\Cms\Field\Meta\Description;
use Duon\Cms\Field\Meta\Hidden;
use Duon\Cms\Field\Meta\Immutable;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Required;
use Duon\Cms\Field\Meta\Rows;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Meta\Validate;
use Duon\Cms\Field\Meta\Width;
use Duon\Cms\Field\Text;
use Duon\Cms\Field\Textarea;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Document;
use Duon\Cms\Node\Meta\Name;

#[Document]
#[Name('Test Document')]
class TestDocument implements HasTitle
{
	#[Label('Document Title')]
	#[Required]
	#[Validate('minLength:3', 'maxLength:100')]
	public Text $title;

	#[Label('Introduction')]
	#[Description('A brief introduction to the document')]
	#[Rows(5)]
	#[Width(12)]
	#[Translate]
	public Textarea $intro;

	#[Hidden]
	#[Immutable]
	#[DefaultValue('auto-generated-id')]
	public Text $internalId;

	public function title(): string
	{
		return $this->title?->value()->unwrap() ?? 'Test Document';
	}
}
