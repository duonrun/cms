<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Node\Schema\Registry;
use ReflectionClass;

class Schema
{
	public readonly string $label;
	public readonly string $handle;
	public readonly string $renderer;
	public readonly bool $routable;
	public readonly bool $renderable;
	public readonly string|array $route;
	public readonly string|array $permission;
	public readonly ?string $titleField;

	/** @var string[]|null */
	public readonly ?array $fieldOrder;
	public readonly bool $deletable;

	/** @var array<string, mixed> */
	private array $extra = [];

	/**
	 * @param class-string $nodeClass
	 */
	public function __construct(
		private readonly string $nodeClass,
		?Registry $registry = null,
	) {
		$resolved = $this->resolveAttributes($registry);
		$this->applyDefaults($resolved);
	}

	/**
	 * Get a custom schema property by key.
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->extra[$key] ?? $default;
	}

	/**
	 * Return all schema properties (built-in + custom) as an array.
	 *
	 * @return array<string, mixed>
	 */
	public function properties(): array
	{
		return array_merge([
			'label' => $this->label,
			'handle' => $this->handle,
			'renderer' => $this->renderer,
			'routable' => $this->routable,
			'renderable' => $this->renderable,
			'route' => $this->route,
			'permission' => $this->permission,
			'titleField' => $this->titleField,
			'fieldOrder' => $this->fieldOrder,
			'deletable' => $this->deletable,
		], $this->extra);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resolveAttributes(?Registry $registry): array
	{
		$reflection = new ReflectionClass($this->nodeClass);
		$resolved = [];

		foreach ($reflection->getAttributes() as $attribute) {
			$instance = $attribute->newInstance();
			$handler = $registry?->getHandler($instance);

			if ($handler !== null) {
				$resolved = array_merge($resolved, $handler->resolve($instance, $this->nodeClass));
			}
		}

		return $resolved;
	}

	/**
	 * @param array<string, mixed> $resolved
	 */
	private function applyDefaults(array $resolved): void
	{
		$className = $this->getClassName();

		$this->handle = $resolved['handle'] ?? $this->deriveHandle($className);
		$this->label = $resolved['label'] ?? $className;
		$this->renderer = $resolved['renderer'] ?? $this->handle;
		$this->route = $resolved['route'] ?? '';
		$this->routable = $resolved['routable'] ?? false;
		$this->renderable = $resolved['renderable'] ?? $this->renderer !== '';
		$this->permission = $resolved['permission'] ?? [
			'read' => 'everyone',
			'create' => 'authenticated',
			'change' => 'authenticated',
			'deeete' => 'authenticated',
		];
		$this->titleField = $resolved['titleField'] ?? null;
		$this->fieldOrder = $resolved['fieldOrder'] ?? null;
		$this->deletable = $resolved['deletable'] ?? true;

		// Collect any extra keys not part of the built-in set
		$builtinKeys = [
			'handle', 'label', 'renderer', 'route', 'routable',
			'renderable', 'permission', 'titleField', 'fieldOrder', 'deletable',
		];

		foreach ($resolved as $key => $value) {
			if (!in_array($key, $builtinKeys, true)) {
				$this->extra[$key] = $value;
			}
		}
	}

	private function deriveHandle(string $className): string
	{
		return ltrim(
			strtolower(preg_replace(
				'/[A-Z]([A-Z](?![a-z]))*/',
				'-$0',
				$className,
			)),
			'-',
		);
	}

	private function getClassName(): string
	{
		return basename(str_replace('\\', '/', $this->nodeClass));
	}
}
