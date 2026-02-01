<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Html;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Translate;

#[Label('Test Html')]
#[Translate]
class TestHtml extends Html {}
