<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Route;

#[Label('Custom Node')]
#[Route('/custom/{uid}')]
#[CustomIcon('star')]
class NodeWithCustomAttribute implements Title
{
	public function title(): string
	{
		return 'custom';
	}
}
