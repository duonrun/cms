<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Node\Schema\Registry;

class Meta
{
	/** @var array<class-string, Schema> */
	private array $cache = [];

	private readonly Registry $registry;

	public function __construct(?Registry $registry = null)
	{
		$this->registry = $registry ?? Registry::withDefaults();
	}

	/**
	 * @param class-string $class
	 */
	public function forClass(string $class): Schema
	{
		return $this->cache[$class] ??= new Schema($class, $this->registry);
	}

	/**
	 * @param class-string $class
	 */
	public function handle(string $class): string
	{
		return $this->forClass($class)->handle;
	}

	/**
	 * @param class-string $class
	 */
	public function label(string $class): string
	{
		return $this->forClass($class)->label;
	}

	/**
	 * @param class-string $class
	 */
	public function route(string $class): string|array
	{
		return $this->forClass($class)->route;
	}

	/**
	 * @param class-string $class
	 */
	public function routable(string $class): bool
	{
		return $this->forClass($class)->routable;
	}

	/**
	 * @param class-string $class
	 */
	public function renderable(string $class): bool
	{
		return $this->forClass($class)->renderable;
	}

	/**
	 * @param class-string $class
	 */
	public function titleField(string $class): ?string
	{
		return $this->forClass($class)->titleField;
	}

	/**
	 * @param class-string $class
	 */
	public function fieldOrder(string $class): ?array
	{
		return $this->forClass($class)->fieldOrder;
	}

	/**
	 * @param class-string $class
	 */
	public function deletable(string $class): bool
	{
		return $this->forClass($class)->deletable;
	}

	/**
	 * @param class-string $class
	 */
	public function get(string $class, string $key, mixed $default = null): mixed
	{
		return $this->forClass($class)->get($key, $default);
	}

	/**
	 * @param class-string $class
	 */
	public function isNode(string $class): bool
	{
		return class_exists($class);
	}

	public function clearCache(): void
	{
		$this->cache = [];
	}
}
