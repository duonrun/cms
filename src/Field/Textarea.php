<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Sire\Schema;

class Textarea extends Text implements Capability\Translatable
{
	use Capability\IsTranslatable;

	public function structure(mixed $value = null): array
	{
		return $this->getTranslatableStructure('textarea', $value);
	}

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:textarea');

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
