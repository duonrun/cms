<?php

declare(strict_types=1);

namespace Duon\Cms\Finder\Dialect;

final class PostgresDialect implements SqlDialect
{
	public function driver(): string
	{
		return 'pgsql';
	}

	public function table(string $name): string
	{
		if (str_contains($name, '.')) {
			return $name;
		}

		return 'cms.' . $name;
	}

	public function jsonExtract(string $column, string $path, bool $text = true): string
	{
		$parts = explode('.', $path);
		$arrow = $text ? '->>' : '->';
		$count = count($parts);

		if ($count === 1) {
			return "{$column}->'{$parts[0]}'{$arrow}'value'";
		}

		$middle = implode("'->'", array_slice($parts, 0, $count - 1));
		$end = array_slice($parts, -1)[0];

		return "{$column}->'{$middle}'{$arrow}'{$end}'";
	}

	public function jsonFieldCompare(
		string $column,
		string $path,
		string $operator,
		mixed $value,
		string $placeholder,
	): array {
		$jsonPathOperator = $this->mapOperatorToJsonPath($operator);
		$jsonValue = $this->formatJsonPathValue($value);
		$expression = "\$.{$path} {$jsonPathOperator} {$jsonValue}";

		return [
			'sql' => "{$column} @@ {$placeholder}",
			'paramValue' => $expression,
		];
	}

	public function jsonFieldRegex(
		string $column,
		string $path,
		string $pattern,
		bool $ignoreCase,
		bool $negate,
		string $placeholder,
	): array {
		$flag = $ignoreCase ? ' flag "i"' : '';
		$expression = "\$.{$path} ? (@ like_regex {$this->jsonPathString($pattern)}{$flag})";
		$sql = "jsonb_path_exists({$column}, {$placeholder})";

		return [
			'sql' => $negate ? "NOT {$sql}" : $sql,
			'paramValue' => $expression,
		];
	}

	public function jsonPathExists(string $column, string $path): string
	{
		return "{$column} @@ 'exists(\$.{$path})'";
	}

	public function like(string $left, string $right): string
	{
		return "{$left} LIKE {$right}";
	}

	public function ilike(string $left, string $right): string
	{
		return "{$left} ILIKE {$right}";
	}

	public function unlike(string $left, string $right): string
	{
		return "{$left} NOT LIKE {$right}";
	}

	public function iunlike(string $left, string $right): string
	{
		return "{$left} NOT ILIKE {$right}";
	}

	public function regex(string $left, string $right): string
	{
		return "{$left} ~ {$right}";
	}

	public function iregex(string $left, string $right): string
	{
		return "{$left} ~* {$right}";
	}

	public function notRegex(string $left, string $right): string
	{
		return "{$left} !~ {$right}";
	}

	public function notIregex(string $left, string $right): string
	{
		return "{$left} !~* {$right}";
	}

	public function fulltext(string $document, string $query): string
	{
		return "{$document}.document @@ websearch_to_tsquery('simple', {$query})";
	}

	public function now(): string
	{
		return 'NOW()';
	}

	private function mapOperatorToJsonPath(string $operator): string
	{
		return match ($operator) {
			'=' => '==',
			'!=' => '!=',
			'>' => '>',
			'>=' => '>=',
			'<' => '<',
			'<=' => '<=',
			default => $operator,
		};
	}

	private function formatJsonPathValue(mixed $value): string
	{
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		if (is_int($value) || is_float($value)) {
			return (string) $value;
		}

		if (is_numeric($value)) {
			return $value;
		}

		if ($value === null) {
			return 'null';
		}

		return $this->jsonPathString((string) $value);
	}

	private function jsonPathString(string $string): string
	{
		$encoded = json_encode($string, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($encoded === false) {
			return '""';
		}

		return $encoded;
	}
}
