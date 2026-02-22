<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title as TitleContract;
use Duon\Cms\Node\Type;
use Duon\Cms\Schema\Label;

#[Label('Type Injected Node')]
class NodeWithInjectedType implements TitleContract
{
	public function __construct(
		private readonly Type $type,
	) {}

	public function title(): string
	{
		return $this->type->label;
	}

	public function typeHandle(): string
	{
		return $this->type->handle;
	}
}
