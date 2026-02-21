<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Html as HtmlValue;
use Duon\Sire\Shape;

class Html extends Field implements Capability\Translatable
{
	use Capability\IsTranslatable;

	public function value(): HtmlValue
	{
		return new HtmlValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getTranslatableStructure('html', $value);
	}

	public function shape(): Shape
	{
		$shape = new Shape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:html');

		if ($this->translate) {
			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$i18nShape = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$localeValidators = [];

				if ($this->isRequired() && $locale->id === $defaultLocale) {
					$localeValidators[] = 'required';
				}

				$i18nShape->add($locale->id, 'text', ...$localeValidators);
			}

			$shape->add('value', $i18nShape, ...$this->validators);
		} else {
			$shape->add('value', 'text', ...$this->validators);
		}

		return $shape;
	}
}
