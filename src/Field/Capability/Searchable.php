<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Capability;

use Duon\Cms\Schema\FulltextWeight;

interface Searchable
{
	public function fulltext(FulltextWeight $fulltextWeight): static;

	public function getFulltextWeight(): ?FulltextWeight;
}
