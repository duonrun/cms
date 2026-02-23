<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Field\Field;
use Duon\Cms\Value;
use Duon\Sire\Shape;

class Picture extends Field implements
	Capability\AllowsMultiple,
	Capability\File\Translatable,
	Capability\Translatable
{
	use Capability\DoesAllowMultiple;
	use Capability\File\IsTranslatable;
	use Capability\IsTranslatable;

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

	public function shape(): Shape
	{
		$shape = new Shape(title: $this->label, keepUnknown: true);
		$shape->add('type', 'text', 'required', 'in:picture');

		if ($this->translateFile) {
			// File-translatable: separate file arrays per locale
			$subShape = new Shape(list: true, title: $this->label, keepUnknown: true);
			$subShape->add('file', 'text');
			$subShape->add('title', 'text');
			$subShape->add('alt', 'text');

			$i18nShape = new Shape(title: $this->label, keepUnknown: true);
			$locales = $this->owner->locales();

			foreach ($locales as $locale) {
				$i18nShape->add($locale->id, $subShape);
			}

			$shape->add('files', $i18nShape, ...$this->validators);
		} elseif ($this->translate) {
			// Text-translatable: shared files but translatable titles and alt text
			$fileShape = new Shape(list: true, keepUnknown: true);
			$fileShape->add('file', 'text', 'required');

			$locales = $this->owner->locales();
			$titleShape = new Shape(title: $this->label, keepUnknown: true);
			$altShape = new Shape(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$titleShape->add($locale->id, 'text');
				$altShape->add($locale->id, 'text');
			}

			$fileShape->add('title', $titleShape);
			$fileShape->add('alt', $altShape);
			$shape->add('files', $fileShape, ...$this->validators);
		} else {
			// Non-translatable
			$fileShape = new Shape(list: true, keepUnknown: true);
			$fileShape->add('file', 'text', 'required');
			$fileShape->add('title', 'text');
			$fileShape->add('alt', 'text');
			$shape->add('files', $fileShape, ...$this->validators);
		}

		return $shape;
	}
}
