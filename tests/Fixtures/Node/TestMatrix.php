<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Matrix;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Required;
use Duon\Cms\Field\Meta\Translate;
use Duon\Cms\Field\Text;

class TestMatrix extends Matrix
{
	#[Label('Titel'), Required, Translate]
	protected Text $title;

	#[Label('Inhalt'), Translate]
	protected Grid $content;
}
