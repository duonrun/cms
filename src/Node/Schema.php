<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Exception\NoSuchProperty;
use Duon\Cms\Node\Schema\Registry;
use ReflectionClass;

class Schema
{
	/** @var array<string, mixed> */
	private array $properties;

	private readonly Registry $registry;

	/**
	 * @param class-string $nodeClass
	 */
	public function __construct(
		private readonly string $nodeClass,
		?Registry $registry = null,
	) {
		$this->registry = $registry ?? new Registry();
		$resolved = $this->resolveAttributes();
		$this->properties = $this->registry->resolveDefaults($this->nodeClass, $resolved);
	}

	public function __get(string $key): mixed
	{
		if (!$this->has($key)) {
			throw new NoSuchProperty("The node schema '{$this->nodeClass}' doesn't have the property '{$key}'");
		}

		return $this->get($key);
	}

	public function __isset(string $key): bool
	{
		return $this->has($key) && $this->properties[$key] !== null;
	}

	/**
	 * Get a schema property by key.
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		if (array_key_exists($key, $this->properties)) {
			return $this->properties[$key];
		}

		return $default;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->properties);
	}

	/**
	 * Return all schema properties as an array.
	 *
	 * @return array<string, mixed>
	 */
	public function properties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resolveAttributes(): array
	{
		$reflection = new ReflectionClass($this->nodeClass);
		$resolved = [];

		foreach ($reflection->getAttributes() as $attribute) {
			$instance = $attribute->newInstance();
			$handler = $this->registry->getHandler($instance);

			if ($handler !== null) {
				$resolved = array_merge($resolved, $handler->resolve($instance, $this->nodeClass));
			}
		}

		return $resolved;
	}
}
