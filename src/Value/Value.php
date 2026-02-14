<?php

declare(strict_types=1);

namespace Duon\Cms\Value;

use Duon\Cms\Assets\Assets;
use Duon\Cms\Exception\NoSuchProperty;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\FieldOwner;
use Duon\Cms\Locale;

abstract class Value
{
	public readonly string $fieldType;
	protected readonly Locale $locale;
	protected readonly Locale $defaultLocale;
	protected readonly string $fieldName;
	protected readonly array $data;

	public function __construct(
		protected readonly FieldOwner $owner,
		protected readonly Field $field,
		protected readonly ValueContext $context,
	) {
		$this->locale = $owner->locale();
		$this->defaultLocale = $owner->defaultLocale();
		$this->data = $context->data;
		$this->fieldName = $context->fieldName;
		$this->fieldType = $field->type;
	}

	public function __get(string $name): mixed
	{
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}

		throw new NoSuchProperty("The field '{$this->fieldName}' doesn't have the property '{$name}'");
	}

	abstract public function __toString(): string;

	abstract public function isset(): bool;

	abstract public function json(): mixed;

	abstract public function unwrap(): mixed;

	public function styleClass(): ?string
	{
		return $this->data['class'] ?? null;
	}

	public function elementId(): ?string
	{
		return $this->data['id'] ?? null;
	}

	protected function assetsPath(): string
	{
		return 'node/' . $this->owner->uid() . '/';
	}

	protected function getAssets(): Assets
	{
		static $assets = null;

		if (!$assets) {
			$assets = new Assets($this->owner->request(), $this->owner->config());
		}

		return $assets;
	}
}
