<?php

declare(strict_types=1);

namespace Duon\Cms\Validation;

use Duon\Cms\Field\Field;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Locales;
use Duon\Cms\Node\Factory;
use Duon\Sire\Shape;

class ValidatorFactory
{
	protected readonly Shape $schema;

	public function __construct(
		protected readonly object $node,
		protected readonly Locales $locales,
		private readonly FieldHydrator $hydrator = new FieldHydrator(),
	) {
		$this->schema = new Shape(keepUnknown: true);
		$this->schema->add('uid', 'text', 'required', 'maxlen:64');
		$this->schema->add('published', 'bool', 'required');
		$this->schema->add('locked', 'bool', 'required');
		$this->schema->add('hidden', 'bool', 'required');
	}

	public function create(): Shape
	{
		$contentSchema = new Shape(title: 'Content', keepUnknown: true);

		foreach (Factory::fieldNamesFor($this->node) as $fieldName) {
			$this->add($contentSchema, $fieldName, $this->hydrator->getField($this->node, $fieldName));
		}

		$this->schema->add('content', $contentSchema);

		return $this->schema;
	}

	protected function add(Shape $schema, string $fieldName, Field $field): void
	{
		$schema->add($fieldName, $field->schema())->label($field->getLabel());
	}
}
