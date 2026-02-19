<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta\Handler;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\FileTranslatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Schema\MetaHandler;
use Duon\Cms\Schema\TranslateFile;

use function Duon\Cms\Schema\capabilityErrorMessage;

/** @implements MetaHandler<TranslateFile> */
class TranslateFileHandler implements MetaHandler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof FileTranslatable) {
			$field->translateFile(true);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, FileTranslatable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof FileTranslatable) {
			return ['translateFile' => $field->getTranslateFile()];
		}

		return [];
	}
}
