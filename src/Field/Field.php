<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Schema\Handler;
use Duon\Cms\Field\Schema\Registry;
use Duon\Cms\Value\Value;
use Duon\Cms\Value\ValueContext;
use Duon\Sire\Shape;
use ReflectionProperty;

abstract class Field implements
	Capability\Defaultable,
	Capability\Describable,
	Capability\Hidable,
	Capability\Immutable,
	Capability\Labelable,
	Capability\Requirable,
	Capability\Resizable,
	Capability\Validatable
{
	use Capability\IsRequirable;
	use Capability\IsLabelable;
	use Capability\IsDescribable;
	use Capability\IsHidable;
	use Capability\IsImmutable;
	use Capability\IsDefaultable;
	use Capability\IsResizable;
	use Capability\IsValidatable;

	public readonly string $type;

	/** @var list<array{object, Handler}> */
	protected array $meta = [];

	protected ?Registry $schemaRegistry = null;

	final public function __construct(
		public readonly string $name,
		public readonly Owner $owner,
		protected readonly ValueContext $valueContext,
	) {
		$this->type = $this::class;
	}

	public function __toString(): string
	{
		return $this->value()->__toString();
	}

	abstract public function value(): Value;

	abstract public function structure(mixed $value = null): array;

	abstract public function shape(): Shape;

	public function isset(): bool
	{
		return $this->value()->isset();
	}

	public function initSchema(ReflectionProperty $property, Registry $registry): void
	{
		$this->schemaRegistry = $registry;

		foreach ($property->getAttributes() as $attr) {
			$instance = $attr->newInstance();
			$handler = $registry->getHandler($instance);

			if ($handler === null) {
				continue;
			}

			$handler->apply($instance, $this);
			$this->meta[] = [$instance, $handler];
		}
	}

	public function schemaRegistry(): Registry
	{
		return $this->schemaRegistry ??= Registry::withDefaults();
	}

	public function properties(): array
	{
		$properties = [
			'name' => $this->name,
			'type' => $this::class,
		];

		foreach ($this->meta as [$meta, $handler]) {
			$properties = array_merge($properties, $handler->properties($meta, $this));
		}

		if ($this instanceof Capability\Limitable) {
			$properties['limit'] = [
				'min' => $this->getLimitMin(),
				'max' => $this->getLimitMax(),
			];
		}

		return $properties;
	}

	public function getFileStructure(string $type, mixed $value = null): array
	{
		if ($value === null) {
			if ($this->default === null) {
				$value = [];
			} else {
				$value = $this->default;
			}
		}

		return ['type' => $type, 'files' => $value];
	}

	public function getSimpleStructure(string $type, mixed $value = null): array
	{
		$value = $value ?: $this->default;

		return ['type' => $type, 'value' => $value];
	}
}
