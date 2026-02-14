<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Meta;

use Attribute;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Validatable;
use Duon\Cms\Field\Field;

use function Duon\Cms\Field\Meta\capabilityErrorMessage;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate implements Capability
{
	protected array $validators = [];

	public function __construct(string ...$validators)
	{
		$this->validators = $validators;
	}

	public function set(Field $field): void
	{
		if ($field instanceof Validatable) {
			$field->addValidators(...$this->validators);

			return;
		}

		throw new RuntimeException(capabilityErrorMessage($field, Validatable::class));
	}

	public function properties(Field $field): array
	{
		if ($field instanceof Validatable) {
			return ['validators' => $field->validators()];
		}

		return [];
	}
}
