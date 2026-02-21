<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value;
use Duon\Sire\Shape;

class Picture extends Field implements Capability\Translatable, Capability\FileTranslatable, Capability\AllowsMultiple
{
	use Capability\DoesAllowMultiple;
	use Capability\IsTranslatable;
	use Capability\FileIsTranslatable;

	// TODO: translateFile and multiple
	public function value(): Value\Picture
	{
		if ($this->translateFile) {
			return new Value\TranslatedPicture($this->owner, $this, $this->valueContext);
		}

		return new Value\Picture($this->owner, $this, $this->valueContext);
	}

	public function properties(): array
	{
		$value = $this->value();
		$count = $value->count();

		// Generate thumbs
		// TODO: add it to the api data. Currently we assume in the frontend that they are existing
		for ($i = 0; $i < $count; $i++) {
			$value->width(400)->url(false, $i);
		}

		return parent::properties();
	}

	public function structure(mixed $value = null): array
	{
		return $this->getFileStructure('picture', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:picture');

		if ($this->translateFile) {
			// File-translatable: separate file arrays per locale
			$subSchema = new Shape(list: true, title: $this->label, keepUnknown: true);
			$subSchema->add('file', 'text');
			$subSchema->add('title', 'text');
			$subSchema->add('alt', 'text');

			$i18nSchema = new Shape(title: $this->label, keepUnknown: true);
			$locales = $this->owner->locales();

			foreach ($locales as $locale) {
				$i18nSchema->add($locale->id, $subSchema);
			}

			$schema->add('files', $i18nSchema, ...$this->validators);
		} elseif ($this->translate) {
			// Text-translatable: shared files but translatable titles and alt text
			$fileSchema = new Shape(list: true, keepUnknown: true);
			$fileSchema->add('file', 'text', 'required');

			$locales = $this->owner->locales();
			$titleSchema = new Shape(title: $this->label, keepUnknown: true);
			$altSchema = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$titleSchema->add($locale->id, 'text');
				$altSchema->add($locale->id, 'text');
			}

			$fileSchema->add('title', $titleSchema);
			$fileSchema->add('alt', $altSchema);
			$schema->add('files', $fileSchema, ...$this->validators);
		} else {
			// Non-translatable
			$fileSchema = new Shape(list: true, keepUnknown: true);
			$fileSchema->add('file', 'text', 'required');
			$fileSchema->add('title', 'text');
			$fileSchema->add('alt', 'text');
			$schema->add('files', $fileSchema, ...$this->validators);
		}

		return $schema;
	}
}
