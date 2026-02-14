<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Value;
use Duon\Cms\Value\ValueContext;
use Duon\Sire\Schema;
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

	/** @var Meta\Capability[] */
	protected array $capabilities = [];

	final public function __construct(
		public readonly string $name,
		public readonly FieldOwner $owner,
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

	abstract public function schema(): Schema;

	public function isset(): bool
	{
		return $this->value()->isset();
	}

	public function initCapabilities(ReflectionProperty $property): void
	{
		foreach ($property->getAttributes() as $attr) {
			$capability = $attr->newInstance();
			$capability->set($this);
			$this->capabilities[] = $capability;
		}
	}

	public function properties(): array
	{
		$properties = [
			'name' => $this->name,
			'type' => $this::class,
		];

		foreach ($this->capabilities as $capability) {
			$properties = array_merge($properties, $capability->properties($this));
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
