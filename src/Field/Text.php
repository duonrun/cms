<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Text as TextValue;
use Duon\Sire\Shape;

class Text extends Field implements Capability\Translatable
{
	use Capability\IsTranslatable;

	public function value(): TextValue
	{
		return new TextValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return $this->getTranslatableStructure('text', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:text');

		if ($this->translate) {
			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$i18nSchema = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$localeValidators = [];

				if ($this->isRequired() && $locale->id === $defaultLocale) {
					$localeValidators[] = 'required';
				}

				$i18nSchema->add($locale->id, 'text', ...$localeValidators);
			}

			$schema->add('value', $i18nSchema, ...$this->validators);
		} else {
			$schema->add('value', 'text', ...$this->validators);
		}

		return $schema;
	}
}
