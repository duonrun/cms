<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Image;
use Duon\Cms\Field\Meta\Columns;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Multiple;
use Duon\Cms\Field\Meta\Options;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Meta\TranslateFile;
use Duon\Cms\Field\Option;
use Duon\Cms\Field\Video;
use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Document;
use Duon\Cms\Node\Meta\Name;

#[Document]
#[Name('Test Media Document')]
class TestMediaDocument implements HasTitle
{
	#[Label('Gallery')]
	#[Multiple]
	#[TranslateFile]
	public Image $gallery;

	#[Label('Video')]
	#[TranslateFile]
	public Video $video;

	#[Label('Content Grid')]
	#[Columns(12, 2)]
	#[Translate]
	public Grid $contentGrid;

	#[Label('Category')]
	#[Options(['news', 'blog', 'tutorial'])]
	public Option $category;

	public function title(): string
	{
		return 'Test Media Document';
	}
}
