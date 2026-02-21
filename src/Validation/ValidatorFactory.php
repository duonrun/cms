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
	protected readonly Shape $shape;

	public function __construct(
		protected readonly object $node,
		protected readonly Locales $locales,
		private readonly FieldHydrator $hydrator = new FieldHydrator(),
	) {
		$this->shape = new Shape(keepUnknown: true);
		$this->shape->add('uid', 'text', 'required', 'maxlen:64');
		$this->shape->add('published', 'bool', 'required');
		$this->shape->add('locked', 'bool', 'required');
		$this->shape->add('hidden', 'bool', 'required');
	}

	public function create(): Shape
	{
		$contentShape = new Shape(title: 'Content', keepUnknown: true);

		foreach (Factory::fieldNamesFor($this->node) as $fieldName) {
			$this->add($contentShape, $fieldName, $this->hydrator->getField($this->node, $fieldName));
		}

		$this->shape->add('content', $contentShape);

		return $this->shape;
	}

	protected function add(Shape $shape, string $fieldName, Field $field): void
	{
		$shape->add($fieldName, $field->shape())->label($field->getLabel());
	}
}
