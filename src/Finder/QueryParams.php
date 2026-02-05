<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

final class QueryParams
{
	private int $index = 0;
	/** @var array<string, scalar|null> */
	private array $params = [];

	public function __construct(
		private readonly string $prefix = 'q',
	) {}

	public function add(string|int|bool|null $value): string
	{
		$this->index++;
		$name = $this->prefix . $this->index;
		$this->params[$name] = $value;

		return ':' . $name;
	}

	public function placeholder(): string
	{
		$this->index++;

		return ':' . $this->prefix . $this->index;
	}

	public function set(string $placeholder, string|int|bool|null $value): void
	{
		$name = ltrim($placeholder, ':');
		$this->params[$name] = $value;
	}

	/** @return array<string, scalar|null> */
	public function all(): array
	{
		return $this->params;
	}
}
