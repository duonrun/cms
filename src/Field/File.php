<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Value;
use Duon\Sire\Schema;

class File extends Field implements Capability\Translatable, Capability\FileTranslatable, Capability\AllowsMultiple
{
	use Capability\DoesAllowMultiple;
	use Capability\IsTranslatable;
	use Capability\FileIsTranslatable;

	public function value(): Value\File|Value\Files
	{
		if ($this->multiple) {
			if ($this->translateFile) {
				return new Value\TranslatedFiles($this->owner, $this, $this->valueContext);
			}

			return new Value\Files($this->owner, $this, $this->valueContext);
		}

		if ($this->translateFile) {
			return new Value\TranslatedFile($this->owner, $this, $this->valueContext);
		}

		return new Value\File($this->owner, $this, $this->valueContext);
	}

	public function structure(mixed $value = null): array
	{
		if ($this->translateFile) {
			return $this->getTranslatableFileStructure('file', $value);
		}

		return $this->getFileStructure('file', $value);
	}

	public function schema(): Schema
	{
		$schema = new Schema(title: $this->label, keepUnknown: true);
		$schema->add('type', 'text', 'required', 'in:file');

		if ($this->translateFile) {
			// File-translatable: separate file arrays per locale
			$subSchema = new Schema(list: true, title: $this->label, keepUnknown: true);
			$subSchema->add('file', 'text');
			$subSchema->add('title', 'text');

			$i18nSchema = new Schema(title: $this->label, keepUnknown: true);
			$locales = $this->owner->locales();

			foreach ($locales as $locale) {
				$i18nSchema->add($locale->id, $subSchema);
			}

			$schema->add('files', $i18nSchema, ...$this->validators);
		} elseif ($this->translate) {
			// Text-translatable: shared files but translatable titles
			$fileSchema = new Schema(list: true, keepUnknown: true);
			$fileSchema->add('file', 'text', 'required');

			$locales = $this->owner->locales();
			$titleSchema = new Schema(title: $this->label, keepUnknown: true);

			foreach ($locales as $locale) {
				$titleSchema->add($locale->id, 'text');
			}

			$fileSchema->add('title', $titleSchema);
			$schema->add('files', $fileSchema, ...$this->validators);
		} else {
			// Non-translatable
			$fileSchema = new Schema(list: true, keepUnknown: true);
			$fileSchema->add('file', 'text', 'required');
			$fileSchema->add('title', 'text');
			$schema->add('files', $fileSchema, ...$this->validators);
		}

		return $schema;
	}
}
