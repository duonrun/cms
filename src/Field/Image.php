<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value;
use Duon\Sire\Shape;

class Image extends Field implements Capability\Translatable, Capability\FileTranslatable, Capability\AllowsMultiple
{
	use Capability\DoesAllowMultiple;
	use Capability\IsTranslatable;
	use Capability\FileIsTranslatable;

	public function value(): Value\Images|Value\Image
	{
		if ($this->multiple) {
			if ($this->translateFile) {
				return new Value\TranslatedImages($this->owner, $this, $this->valueContext);
			}

			return new Value\Images($this->owner, $this, $this->valueContext);
		}

		if ($this->translateFile) {
			return new Value\TranslatedImage($this->owner, $this, $this->valueContext);
		}

		return new Value\Image($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		if ($this->translateFile) {
			return $this->getTranslatableFileStructure('image', $value);
		}

		return $this->getFileStructure('image', $value);
	}

	public function schema(): Shape
	{
		$schema = new Shape(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:image');

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
