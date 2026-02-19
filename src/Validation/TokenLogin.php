<?php

declare(strict_types=1);

namespace Duon\Cms\Validation;

use Duon\Sire\Schema;

class TokenLogin extends Schema
{
	protected function rules(): void
	{
		$this->add('token', 'text', 'required', 'maxlen:512')->label(_('One-time token'));
	}
}
