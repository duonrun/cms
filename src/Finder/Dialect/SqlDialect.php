<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

interface SqlDialect
{
	public function driver(): string;

	public function table(string $name): string;

	public function jsonExtract(string $column, string $path, bool $text = true): string;

	public function like(string $left, string $right): string;

	public function ilike(string $left, string $right): string;

	public function regex(string $left, string $right): string;

	public function iregex(string $left, string $right): string;

	public function fulltext(string $document, string $query): string;
}
