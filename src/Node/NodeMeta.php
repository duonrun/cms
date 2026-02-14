<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

class NodeMeta
{
	/** @var array<class-string, Meta> */
	private static array $cache = [];

	/**
	 * @param class-string $class
	 */
	public static function forClass(string $class): Meta
	{
		return self::$cache[$class] ??= new Meta($class);
	}

	/**
	 * @param class-string $class
	 */
	public static function kind(string $class): string
	{
		return self::forClass($class)->kind;
	}

	/**
	 * @param class-string $class
	 */
	public static function handle(string $class): string
	{
		return self::forClass($class)->handle;
	}

	/**
	 * @param class-string $class
	 */
	public static function name(string $class): string
	{
		return self::forClass($class)->name;
	}

	/**
	 * @param class-string $class
	 */
	public static function route(string $class): string|array
	{
		return self::forClass($class)->route;
	}

	/**
	 * @param class-string $class
	 */
	public static function titleField(string $class): ?string
	{
		return self::forClass($class)->titleField;
	}

	/**
	 * @param class-string $class
	 */
	public static function fieldOrder(string $class): ?array
	{
		return self::forClass($class)->fieldOrder;
	}

	/**
	 * @param class-string $class
	 */
	public static function deletable(string $class): bool
	{
		return self::forClass($class)->deletable;
	}

	/**
	 * @param class-string $class
	 */
	public static function isNode(string $class): bool
	{
		return self::forClass($class)->hasKindAttribute || is_subclass_of($class, Node::class);
	}

	public static function clearCache(): void
	{
		self::$cache = [];
	}
}
