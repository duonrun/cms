<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Image;
use Duon\Cms\Field\Option;
use Duon\Cms\Field\Video;
use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Columns;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Multiple;
use Duon\Cms\Schema\Options;
use Duon\Cms\Schema\Translate;
use Duon\Cms\Schema\TranslateFile;

#[Label('Test Media Document')]
class TestMediaDocument implements Title
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
