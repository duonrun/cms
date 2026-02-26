<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Validation\GridItemValidator;
use Duon\Cms\Validation\Shape as ValidationShape;
use Duon\Cms\Value\Grid as GridValue;
use Duon\Sire\Shape;

class Grid extends Field implements Capability\Translatable, Capability\Grid\Resizable
{
	use Capability\IsTranslatable;
	use Capability\Grid\IsResizable;

	public function __toString(): string
	{
		return 'Grid Field';
	}

	public function value(): GridValue
	{
		return new GridValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		$value = $value ?: $this->default;

		if (is_array($value)) {
			return ['type' => 'grid', 'columns' => $this->columns, 'minCellWidth' => $this->minCellWidth, 'value' => $value];
		}

		$result = ['type' => 'grid', 'columns' => $this->columns, 'minCellWidth' => $this->minCellWidth, 'value' => []];

		if ($this->translate) {
			foreach ($this->owner->locales() as $locale) {
				$result['value'][$locale->id] = [];
			}
		}

		return $result;
	}

	public function shape(): Shape
	{
		$shape = new ValidationShape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:grid');
		$shape->add('columns', 'int', 'required');

		$itemShape = new GridItemValidator(list: true, title: $this->label, keepUnknown: true);

		if ($this->translate) {
			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$i18nShape = new ValidationShape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$innerValidators = [];

				if ($this->isRequired() && $locale->id === $defaultLocale) {
					$innerValidators[] = 'required';
				}

				$i18nShape->add($locale->id, $itemShape, ...$innerValidators);
			}

			$shape->add('value', $i18nShape, ...$this->validators);
		} else {
			$shape->add('value', $itemShape, ...$this->validators);
		}

		return $shape;
	}
}
