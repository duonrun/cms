<?php

declare(strict_types=1);

namespace Duon\Cms\Validation;

use Duon\Sire\Shape;

class TokenLogin extends Shape
{
	protected function rules(): void
	{
		$this->add('token', 'text', 'required', 'maxlen:512')->label(_('One-time token'));
	}
}
