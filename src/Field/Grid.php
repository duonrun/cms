<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Validation\GridItemValidator;
use Duon\Cms\Value\Grid as GridValue;
use Duon\Sire\Shape;
use ValueError;

class Grid extends Field implements Capability\Translatable, Capability\GridResizable
{
	use Capability\IsTranslatable;
	use Capability\GridIsResizable;

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

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:grid');
		$schema->add('columns', 'int', 'required');

		$itemSchema = new GridItemValidator(list: true, title: $this->label, keepUnknown: true);

		if ($this->translate) {
			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$i18nSchema = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$innerValidators = [];

				if ($this->isRequired() && $locale->id === $defaultLocale) {
					$innerValidators[] = 'required';
				}

				$i18nSchema->add($locale->id, $itemSchema, ...$innerValidators);
			}

			$schema->add('value', $i18nSchema, ...$this->validators);
		} else {
			$schema->add('value', $itemSchema, ...$this->validators);
		}

		return $schema;
	}
}
