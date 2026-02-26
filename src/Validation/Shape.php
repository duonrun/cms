<?php

declare(strict_types=1);

namespace Duon\Cms\Validation;

use Duon\Sire\Contract\Shape as ShapeContract;
use Duon\Sire\Contract\TypeCasterRegistry as TypeCasterRegistryContract;
use Duon\Sire\Contract\ValidatorDefinitionParser as ValidatorDefinitionParserContract;
use Duon\Sire\Contract\ValidatorRegistry as ValidatorRegistryContract;
use Duon\Sire\Shape as SireShape;
use Duon\Sire\Validator;
use Duon\Sire\ValidatorRegistry;
use Duon\Sire\Value;
use Override;

class Shape extends SireShape
{
	public function __construct(
		bool $list = false,
		bool $keepUnknown = false,
		array $langs = [],
		?string $title = null,
		?ValidatorRegistryContract $validatorRegistry = null,
		?ValidatorDefinitionParserContract $validatorDefinitionParser = null,
		?TypeCasterRegistryContract $typeCasterRegistry = null,
	) {
		$validatorRegistry ??= ValidatorRegistry::withDefaults()->withMany([
			'minitems' => new Validator(
				'minitems',
				'Has fewer than the minimum number of %4$s items',
				static function (Value $value, string ...$args): bool {
					if (!is_array($value->value)) {
						return false;
					}

					return count($value->value) >= (int) ($args[0] ?? 0);
				},
				false,
			),
			'maxitems' => new Validator(
				'maxitems',
				'Has more than the maximum allowed number of %4$s items',
				static function (Value $value, string ...$args): bool {
					if (!is_array($value->value)) {
						return false;
					}

					return count($value->value) <= (int) ($args[0] ?? 0);
				},
				true,
			),
		]);

		parent::__construct(
			$list,
			$keepUnknown,
			$langs,
			$title,
			$validatorRegistry,
			$validatorDefinitionParser,
			$typeCasterRegistry,
		);
	}

	#[Override]
	protected function toSubValues(mixed $pristine, ShapeContract $shape): Value
	{
		if ($pristine === null) {
			$pristine = [];
		}

		return parent::toSubValues($pristine, $shape);
	}
}
