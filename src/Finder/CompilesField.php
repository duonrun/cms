<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Exception\ParserException;
use Duon\Cms\Finder\Dialect\SqlDialect;

trait CompilesField
{
	/**
	 * Compile a field name to a dialect-specific JSON access expression.
	 *
	 * @param string $fieldName The field name (e.g., 'title', 'title.value', 'meta.description')
	 * @param string $column The table column containing JSON (e.g., 'n.content')
	 * @param SqlDialect $dialect The SQL dialect to use
	 * @param bool $preserveType If true, preserve JSON type; if false, extract as text
	 */
	private function compileField(
		string $fieldName,
		string $column,
		SqlDialect $dialect,
		bool $preserveType = false,
	): string {
		$parts = explode('.', $fieldName);

		foreach ($parts as $part) {
			if ($part === '') {
				throw new ParserException('Invalid field name');
			}
		}

		// Build the JSON path (e.g., 'title.value' or 'meta.description')
		$path = count($parts) === 1
			? $parts[0] . '.value'
			: implode('.', $parts);

		return $preserveType
			? $dialect->jsonExtract($column, $path)
			: $dialect->jsonExtractText($column, $path);
	}
}
