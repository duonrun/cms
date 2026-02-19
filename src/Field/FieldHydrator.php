<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Schema\MetaRegistry;
use Duon\Cms\Value\ValueContext;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

class FieldHydrator
{
	public function __construct(
		private readonly MetaRegistry $metaRegistry = new MetaRegistry(),
	) {}

	/**
	 * Scan $target for Field-typed properties, instantiate each Field with
	 * the given FieldOwner and content data, then set them on the target.
	 *
	 * @return string[] Discovered field names
	 */
	public function hydrate(object $target, array $content, FieldOwner $owner): array
	{
		$fieldNames = [];
		$rc = new ReflectionClass($target);

		foreach ($rc->getProperties() as $property) {
			$name = $property->getName();

			if (!$property->hasType()) {
				continue;
			}

			$type = $property->getType();

			if ($type::class === ReflectionUnionType::class) {
				continue;
			}

			$typeName = $type->getName();

			if (is_subclass_of($typeName, Field::class)) {
				if ($property->isInitialized($target)) {
					continue;
				}

				$property->setValue($target, $this->initField($property, $typeName, $content, $owner));
				$fieldNames[] = $name;
			}
		}

		return $fieldNames;
	}

	public function getField(object $target, string $name): Field
	{
		$rc = new ReflectionClass($target);

		return $rc->getProperty($name)->getValue($target);
	}

	/**
	 * @return Field[]
	 */
	public function getFields(object $target, array $fieldNames): array
	{
		$rc = new ReflectionClass($target);
		$fields = [];

		foreach ($fieldNames as $name) {
			$fields[$name] = $rc->getProperty($name)->getValue($target);
		}

		return $fields;
	}

	public function metaRegistry(): MetaRegistry
	{
		return $this->metaRegistry;
	}

	protected function initField(
		ReflectionProperty $property,
		string $fieldType,
		array $content,
		FieldOwner $owner,
	): Field {
		$fieldName = $property->getName();
		$data = $content[$fieldName] ?? [];
		$field = new $fieldType($fieldName, $owner, new ValueContext($fieldName, $data));

		$field->initMeta($property, $this->metaRegistry);

		return $field;
	}
}
