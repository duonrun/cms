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

	/** @return array<string, scalar|null> */
	public function all(): array
	{
		return $this->params;
	}
}
