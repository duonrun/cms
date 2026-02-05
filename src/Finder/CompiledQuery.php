<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

/**
 * Represents compiled SQL with named parameters.
 *
 * This class encapsulates SQL output from the Finder query compiler
 * along with any bound parameters, preventing SQL injection by ensuring
 * user values are passed through PDO parameter binding rather than
 * string interpolation.
 */
final readonly class CompiledQuery
{
	/**
	 * @param string $sql The SQL fragment with named placeholders
	 * @param array<string, scalar|null> $params Named parameters to bind
	 */
	public function __construct(
		public string $sql,
		public array $params = [],
	) {}

	/**
	 * Merge this query with another, concatenating SQL and merging params.
	 */
	public function merge(CompiledQuery $other): self
	{
		return new self(
			$this->sql . $other->sql,
			array_merge($this->params, $other->params),
		);
	}

	/**
	 * Create an empty compiled query.
	 */
	public static function empty(): self
	{
		return new self('', []);
	}

	/**
	 * Create a compiled query with only SQL (no params).
	 */
	public static function sql(string $sql): self
	{
		return new self($sql);
	}
}
