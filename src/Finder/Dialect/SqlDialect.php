<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

interface SqlDialect
{
	public function driver(): string;

	public function table(string $name): string;

	public function jsonExtract(string $column, string $path, bool $text = true): string;

	/**
	 * Build a JSON field comparison expression.
	 *
	 * @return array{sql: string, paramValue: mixed} SQL fragment and the parameter value to bind
	 */
	public function jsonFieldCompare(
		string $column,
		string $path,
		string $operator,
		mixed $value,
		string $placeholder,
	): array;

	/**
	 * Build a JSON field regex expression.
	 *
	 * @return array{sql: string, paramValue: string} SQL fragment and the parameter value to bind
	 */
	public function jsonFieldRegex(
		string $column,
		string $path,
		string $pattern,
		bool $ignoreCase,
		bool $negate,
		string $placeholder,
	): array;

	public function jsonPathExists(string $column, string $path): string;

	public function like(string $left, string $right): string;

	public function ilike(string $left, string $right): string;

	public function unlike(string $left, string $right): string;

	public function iunlike(string $left, string $right): string;

	public function regex(string $left, string $right): string;

	public function iregex(string $left, string $right): string;

	public function notRegex(string $left, string $right): string;

	public function notIregex(string $left, string $right): string;

	public function fulltext(string $document, string $query): string;

	public function now(): string;
}
