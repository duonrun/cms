<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Grid;
use Duon\Cms\Field\Meta\Columns;
use Duon\Cms\Field\Meta\Label;
use Duon\Cms\Field\Meta\Translate;

#[Label('Test Grid')]
#[Columns(12, 4)]
#[Translate]
class TestGrid extends Grid {}
