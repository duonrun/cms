<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value;
use Duon\Sire\Shape;

class Video extends Field implements Capability\Translatable, Capability\FileTranslatable, Capability\AllowsMultiple
{
	use Capability\IsTranslatable;
	use Capability\FileIsTranslatable;
	use Capability\DoesAllowMultiple;

	public function value(): Value\Video
	{
		if ($this->translateFile) {
			return new Value\Video($this->owner, $this, $this->valueContext);
		}

		return new Value\Video($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		if ($this->translateFile) {
			return $this->getTranslatableFileStructure('video', $value);
		}

		return $this->getFileStructure('video', $value);
	}

	public function shape(): Shape
	{
		$shape = new Shape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:video');

		if ($this->translateFile) {
			// File-translatable: separate file arrays per locale
			$subShape = new Shape(list: true, title: $this->label, keepUnknown: true);
			$subShape->add('file', 'text');
			$subShape->add('title', 'text');

			$i18nShape = new Shape(title: $this->label, keepUnknown: true);
			$locales = $this->owner->locales();

			foreach ($locales as $locale) {
				$i18nShape->add($locale->id, $subShape);
			}

			$shape->add('files', $i18nShape, ...$this->validators);
		} elseif ($this->translate) {
			// Text-translatable: shared files but translatable titles
			$fileShape = new Shape(list: true, keepUnknown: true);
			$fileShape->add('file', 'text', 'required');

			$locales = $this->owner->locales();
			$defaultLocale = $locales->getDefault()->id;
			$titleShape = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$localeValidators = [];

				if ($this->isRequired() && $locale->id === $defaultLocale) {
					$localeValidators[] = 'required';
				}

				$titleShape->add($locale->id, 'text', ...$localeValidators);
			}

			$fileShape->add('title', $titleShape);
			$shape->add('files', $fileShape, ...$this->validators);
		} else {
			// Non-translatable
			$fileShape = new Shape(list: true, keepUnknown: true);
			$fileShape->add('file', 'text', 'required');
			$fileShape->add('title', 'text');
			$shape->add('files', $fileShape, ...$this->validators);
		}

		return $shape;
	}
}
