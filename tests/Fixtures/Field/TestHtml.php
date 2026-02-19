<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Html;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Translate;

#[Label('Test Html')]
#[Translate]
class TestHtml extends Html {}
