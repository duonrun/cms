<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Render;

#[Render('template-defined-by-render-attribute')]
class NodeWithRenderAttribute implements Title
{
	public function title(): string
	{
		return 'with render';
	}
}
