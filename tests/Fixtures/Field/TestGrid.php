<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Grid;
use Duon\Cms\Schema\Columns;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Translate;

#[Label('Test Grid')]
#[Columns(12, 4)]
#[Translate]
class TestGrid extends Grid {}
