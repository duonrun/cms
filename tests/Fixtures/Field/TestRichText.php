<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\RichText;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Translate;

#[Label('Test RichText')]
#[Translate]
class TestRichText extends RichText {}
