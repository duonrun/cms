<?php

declare(strict_types=1);

namespace Duon\Cms\Field\Schema;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\Capability\Limitable;
use Duon\Cms\Field\Field;

class LimitHandler extends Handler
{
	public function apply(object $meta, Field $field): void
	{
		if ($field instanceof Limitable) {
			$field->limit($meta->max, $meta->min);

			return;
		}

		throw new RuntimeException($this->capabilityErrorMessage($field, Limitable::class));
	}

	public function properties(object $meta, Field $field): array
	{
		if ($field instanceof Limitable) {
			return [
				'limit' => [
					'min' => $field->getLimitMin(),
					'max' => $field->getLimitMax(),
				],
			];
		}

		return [];
	}
}
