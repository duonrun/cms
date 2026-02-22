<?php

declare(strict_types=1);

namespace Duon\Cms\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Syntax
{
	/** @var string[] */
	public array $syntaxes;

	public function __construct(string ...$syntaxes)
	{
		$this->syntaxes = $syntaxes;
	}
}
