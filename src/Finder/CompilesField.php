<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Db\Dialect;

trait CompilesField
{
	abstract protected function dialect(): Dialect;

	private function compileField(
		string $fieldName,
		string $tableField,
		bool $asIs = false,
	): string {
		return $this->dialect()->compileField($fieldName, $tableField, $asIs);
	}
}
