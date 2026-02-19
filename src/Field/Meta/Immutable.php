<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Immutable {} // We can't use Readonly as it is a keyword of PHP
