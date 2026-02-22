<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Node\Schema\Registry;

class Types
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
	public function schemaOf(string $class): Schema
	{
		return $this->cache[$class] ??= new Schema($class, $this->registry);
	}

	/**
	 * @param class-string $class
	 */
	public function handle(string $class): string
	{
		return $this->schemaOf($class)->handle;
	}

	/**
	 * @param class-string $class
	 */
	public function label(string $class): string
	{
		return $this->schemaOf($class)->label;
	}

	/**
	 * @param class-string $class
	 */
	public function route(string $class): string|array
	{
		return $this->schemaOf($class)->route;
	}

	/**
	 * @param class-string $class
	 */
	public function routable(string $class): bool
	{
		return $this->schemaOf($class)->routable;
	}

	/**
	 * @param class-string $class
	 */
	public function renderable(string $class): bool
	{
		return $this->schemaOf($class)->renderable;
	}

	/**
	 * @param class-string $class
	 */
	public function titleField(string $class): ?string
	{
		return $this->schemaOf($class)->titleField;
	}

	/**
	 * @param class-string $class
	 */
	public function fieldOrder(string $class): ?array
	{
		return $this->schemaOf($class)->fieldOrder;
	}

	/**
	 * @param class-string $class
	 */
	public function deletable(string $class): bool
	{
		return $this->schemaOf($class)->deletable;
	}

	/**
	 * @param class-string $class
	 */
	public function get(string $class, string $key, mixed $default = null): mixed
	{
		return $this->schemaOf($class)->get($key, $default);
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
