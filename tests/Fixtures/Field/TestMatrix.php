<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Matrix;
use Duon\Cms\Field\Text;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Required;
use Duon\Cms\Schema\Translate;

#[Label('Test Matrix')]
class TestMatrix extends Matrix
{
	#[Label('Titel'), Required, Translate]
	protected Text $title;

	#[Label('Inhalt'), Translate]
	protected Grid $content;
}
