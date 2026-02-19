<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Text;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Translate;

#[Label('Test Text')]
#[Translate]
class TestText extends Text {}
