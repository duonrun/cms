<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value\Youtube as YoutubeValue;
use Duon\Sire\Schema;

class Iframe extends Field implements Capability\Translatable
{
	use Capability\IsTranslatable;

	public function value(): YoutubeValue
	{
		return new YoutubeValue($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		return array_merge($this->getSimpleStructure('iframe', $value), [
			'iframeWidth' => '100%',
			'iframeHeight' => '75%',
		]);
	}

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:iframe');

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
