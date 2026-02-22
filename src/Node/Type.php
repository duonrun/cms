<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

class Type
{
	public readonly string $class;
	public readonly string $classname;
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

	private readonly Schema $schema;

	/**
	 * @param class-string $class
	 */
	public function __construct(
		string $class,
		Types $types,
	) {
		$this->class = $class;
		$this->classname = basename(str_replace('\\', '/', $class));
		$this->schema = $types->schemaOf($class);
		$this->label = $this->schema->label;
		$this->handle = $this->schema->handle;
		$this->renderer = $this->schema->renderer;
		$this->routable = $this->schema->routable;
		$this->renderable = $this->schema->renderable;
		$this->route = $this->schema->route;
		$this->permission = $this->schema->permission;
		$this->titleField = $this->schema->titleField;
		$this->fieldOrder = $this->schema->fieldOrder;
		$this->deletable = $this->schema->deletable;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return match ($key) {
			'class' => $this->class,
			'classname' => $this->classname,
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
			default => $this->schema->get($key, $default),
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		return array_merge($this->schema->properties(), [
			'class' => $this->class,
			'classname' => $this->classname,
		]);
	}
}
