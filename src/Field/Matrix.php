<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\MatrixValue;
use Duon\Cms\Value\ValueContext;
use Duon\Sire\Schema;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class Matrix extends Field implements Capability\AllowsMultiple
{
	use Capability\DoesAllowMultiple;

	protected array $subfields = [];
	protected bool $subfieldsInitialized = false;

	public function value(): MatrixValue
	{
		$this->initSubfields();

		return new MatrixValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		$this->initSubfields();
		$value = $value ?? $this->valueContext->data['value'] ?? $this->default ?? [];

		if (!is_array($value)) {
			$value = [];
		}

		$structures = [];

		foreach ($value as $itemData) {
			$itemStructure = [];

			foreach ($this->subfields as $name => $subfield) {
				$subfieldData = $itemData[$name]['value'] ?? null;
				$itemStructure[$name] = $subfield->structure($subfieldData);
			}

			$structures[] = $itemStructure;
		}

		return [
			'type' => 'matrix',
			'value' => $structures,
		];
	}

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:matrix');

		$itemSchema = new Schema(title: $this->label, keepUnknown: true);

		foreach ($this->subfields as $name => $subfield) {
			$itemSchema->add($name, $subfield->schema());
		}

		if ($this->multiple) {
			$schema->add('value', 'list', ...$this->validators);
			$schema->add('value.*', $itemSchema);
		} else {
			$schema->add('value', $itemSchema, ...$this->validators);
		}

		return $schema;
	}

	public function getSubfields(): array
	{
		$this->initSubfields();

		return $this->subfields;
	}

	public function properties(): array
	{
		$this->initSubfields();

		$result = parent::properties();
		// Override type with base Matrix class so the UI can find the right component
		$result['type'] = Matrix::class;
		$result['subfields'] = [];

		foreach ($this->subfields as $subfield) {
			$result['subfields'][] = $subfield->properties();
		}

		return $result;
	}

	protected function initSubfields(): void
	{
		if ($this->subfieldsInitialized) {
			return;
		}

		$this->subfieldsInitialized = true;
		$matrixClass = $this::class;
		$reflection = new ReflectionClass($matrixClass);

		foreach ($reflection->getProperties(ReflectionProperty::IS_PROTECTED) as $property) {
			$type = $property->getType();

			if (!$type || !($type instanceof ReflectionNamedType)) {
				continue;
			}

			$fieldClass = $type->getName();

			if (!is_subclass_of($fieldClass, Field::class)) {
				continue;
			}

			$subfield = new $fieldClass(
				$property->getName(),
				$this->owner,
				new ValueContext($property->getName(), []),
			);

			$subfield->initCapabilities($property);
			$this->subfields[$property->getName()] = $subfield;
		}
	}
}
