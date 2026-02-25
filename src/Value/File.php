<?php

declare(strict_types=1);

namespace Duon\Cms\Value;

use Duon\Cms\Assets;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\File\Translatable as FileTranslatable;
use Duon\Cms\Field\Capability\Translatable;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\Owner;

/**
 * @property-read Field&FileTranslatable&Translatable $field
 */
class File extends Value
{
	public function __construct(
		Owner $owner,
		Field&FileTranslatable&Translatable $field,
		ValueContext $context,
		protected int $index = 0,
	) {
		parent::__construct($owner, $field, $context);
	}

	public function __toString(): string
	{
		return $this->publicPath(false);
	}

	public function title(): string
	{
		return $this->textValue('title', $this->index);
	}

	public function url(bool $bust = false): string
	{
		if ($url = filter_var($this->getFile($this->index)->url($bust), FILTER_VALIDATE_URL)) {
			return $url;
		}

		throw new RuntimeException('Invalid file url');
	}

	public function publicPath(bool $bust = false): string
	{
		return filter_var($this->getFile($this->index)->publicPath($bust), FILTER_SANITIZE_URL);
	}

	public function filename(): string
	{
		return $this->getFileName($this->index);
	}

	public function mimetype(): string
	{
		return mime_content_type($this->getFile($this->index)->path());
	}

	public function unwrap(): ?array
	{
		return $this->data['files'][0] ?? null;
	}

	public function json(): mixed
	{
		return $this->data;
	}

	public function count(): int
	{
		return count($this->data['files'] ?? []);
	}

	public function isset(): bool
	{
		return isset($this->data['files'][0]) ? true : false;
	}

	protected function getFileName(int $index): string
	{
		return $this->data['files'][$index]['file'];
	}

	protected function textValue(string $key, int $index): string
	{
		if ($this->field->isTranslatable()) {
			return $this->translated($key, $index);
		}

		return $this->data['files'][$this->index][$key] ?? '';
	}

	protected function translated(string $key, int $index): string
	{
		$locale = $this->locale;

		while ($locale) {
			$value = $this->data['files'][$index][$key][$locale->id] ?? null;

			if ($value) {
				return $value;
			}

			$locale = $locale->fallback();
		}

		return '';
	}

	protected function getFile(int $index): Assets\File
	{
		return $this->getAssets()->file($this->assetsPath() . $this->getFileName($index));
	}
}
