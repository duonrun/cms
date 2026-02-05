<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\Dialect\SqlDialect;

trait CompilesField
{
	private function compileField(
		string $fieldName,
		string $tableField,
		SqlDialect $dialect,
		bool $asIs = false,
	): string {
		$parts = explode('.', $fieldName);

		foreach ($parts as $part) {
			if ($part === '') {
				throw new ParserException('Invalid field name');
			}
		}

		return $dialect->jsonExtract($tableField, $fieldName, !$asIs);
	}
}
