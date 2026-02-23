<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Capability;

interface SyntaxAware
{
	public function syntaxes(array $syntaxes): void;

	public function getSyntaxes(): array;

	public function getDefaultSyntax(): string;
}
