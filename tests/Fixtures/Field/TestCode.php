<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Field;

use Duon\Cms\Field\Code;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Syntax;
use Duon\Cms\Schema\Translate;

#[Label('Test Code')]
#[Translate]
#[Syntax('php', 'javascript')]
class TestCode extends Code {}
