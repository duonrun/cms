<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Text;
use Duon\Cms\Field\Textarea;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Schema\DefaultValue;
use Duon\Cms\Schema\Description;
use Duon\Cms\Schema\Hidden;
use Duon\Cms\Schema\Immutable;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Name;
use Duon\Cms\Schema\Required;
use Duon\Cms\Schema\Rows;
use Duon\Cms\Schema\Translate;
use Duon\Cms\Schema\Validate;
use Duon\Cms\Schema\Width;

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
