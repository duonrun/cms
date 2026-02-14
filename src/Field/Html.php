<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value\Html as HtmlValue;
use Duon\Sire\Schema;

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

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:html');

		if ($this->translate) {
			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$i18nSchema = new Schema(title: $this->label, keepUnknown: true);

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
